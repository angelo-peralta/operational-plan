<?php

namespace App\Actions\Authentication;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteMicrosoftEntraLogin
{
    public function handle(Request $request): User
    {
        $expectedState = $request->session()->pull(BeginMicrosoftEntraLogin::STATE_SESSION_KEY);
        $codeVerifier = $request->session()->pull(BeginMicrosoftEntraLogin::CODE_VERIFIER_SESSION_KEY);
        $state = $request->query('state');
        $code = $request->query('code');

        if ($request->query->has('error')) {
            $this->fail(__('Microsoft sign-in was cancelled or denied.'));
        }

        if (! is_string($expectedState)
            || ! is_string($state)
            || ! hash_equals($expectedState, $state)
            || ! is_string($codeVerifier)
            || ! is_string($code)
            || $code === '') {
            $this->fail(__('The Microsoft sign-in request expired or was invalid. Please try again.'));
        }

        $tenantId = $this->requiredUuidConfiguration('tenant_id');
        $clientId = $this->requiredUuidConfiguration('client_id');
        $clientSecret = $this->requiredStringConfiguration('client_secret');
        $redirectUri = $this->redirectUri();

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post($this->tenantEndpoint($tenantId, 'token'), [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
                'code_verifier' => $codeVerifier,
            ]);

        $accessToken = $tokenResponse->json('access_token');

        if (! $tokenResponse->successful() || ! is_string($accessToken) || $accessToken === '') {
            $this->fail();
        }

        $graphResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->get($this->graphEndpoint('/me'), [
                '$select' => 'id,displayName,mail,userPrincipalName',
            ]);

        if (! $graphResponse->successful()) {
            $this->fail();
        }

        $objectId = $graphResponse->json('id');
        $displayName = $graphResponse->json('displayName');
        $profile = $graphResponse->json();
        $email = $this->emailFromGraphResponse(is_array($profile) ? $profile : null);

        if (! is_string($objectId) || ! Str::isUuid($objectId) || $email === null) {
            $this->fail();
        }

        return $this->resolveAuthorizedUser(
            tenantId: Str::lower($tenantId),
            objectId: Str::lower($objectId),
            email: $email,
            displayName: is_string($displayName) ? Str::squish($displayName) : null,
        );
    }

    private function resolveAuthorizedUser(
        string $tenantId,
        string $objectId,
        string $email,
        ?string $displayName,
    ): User {
        return DB::transaction(function () use ($tenantId, $objectId, $email, $displayName): User {
            $user = User::query()
                ->where('entra_tenant_id', $tenantId)
                ->where('entra_object_id', $objectId)
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
                    ->lockForUpdate()
                    ->first();

                if ($user === null
                    || $user->entra_tenant_id !== null
                    || $user->entra_object_id !== null) {
                    $this->fail(__('Your Microsoft account is not authorized for this application.'));
                }
            }

            $emailBelongsToAnotherUser = User::query()
                ->whereKeyNot($user->id)
                ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
                ->exists();

            if ($emailBelongsToAnotherUser) {
                $this->fail(__('Your Microsoft account could not be linked. Contact an administrator.'));
            }

            $user->forceFill([
                'entra_tenant_id' => $tenantId,
                'entra_object_id' => $objectId,
                'name' => $displayName !== null && $displayName !== '' ? $displayName : $user->name,
                'email' => Str::lower($email),
                'email_verified_at' => now(),
            ])->save();

            return $user;
        });
    }

    /** @param array<string, mixed>|null $profile */
    private function emailFromGraphResponse(?array $profile): ?string
    {
        if ($profile === null) {
            return null;
        }

        foreach (['mail', 'userPrincipalName'] as $key) {
            $value = $profile[$key] ?? null;

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
                return Str::lower($value);
            }
        }

        return null;
    }

    private function requiredUuidConfiguration(string $key): string
    {
        $value = $this->requiredStringConfiguration($key);

        abort_unless(Str::isUuid($value), 503, __('Microsoft sign-in is not configured.'));

        return $value;
    }

    private function requiredStringConfiguration(string $key): string
    {
        $value = config("services.microsoft.{$key}");

        abort_unless(
            is_string($value) && $value !== '',
            503,
            __('Microsoft sign-in is not configured.'),
        );

        return $value;
    }

    private function redirectUri(): string
    {
        $configuredRedirectUri = config('services.microsoft.redirect_uri');
        $redirectUri = is_string($configuredRedirectUri) && $configuredRedirectUri !== ''
            ? $configuredRedirectUri
            : route('auth.microsoft.callback');

        abort_unless(
            filter_var($redirectUri, FILTER_VALIDATE_URL) !== false,
            503,
            __('Microsoft sign-in is not configured.'),
        );

        return $redirectUri;
    }

    private function tenantEndpoint(string $tenantId, string $endpoint): string
    {
        $authority = rtrim((string) config('services.microsoft.authority'), '/');

        abort_unless(
            Str::startsWith($authority, 'https://'),
            503,
            __('Microsoft sign-in is not configured.'),
        );

        return "{$authority}/{$tenantId}/oauth2/v2.0/{$endpoint}";
    }

    private function graphEndpoint(string $path): string
    {
        $graphUrl = rtrim((string) config('services.microsoft.graph_url'), '/');

        abort_unless(
            Str::startsWith($graphUrl, 'https://'),
            503,
            __('Microsoft sign-in is not configured.'),
        );

        return $graphUrl.$path;
    }

    private function fail(string $message = 'Microsoft sign-in could not be completed. Please try again.'): never
    {
        throw ValidationException::withMessages([
            'microsoft' => __($message),
        ]);
    }
}
