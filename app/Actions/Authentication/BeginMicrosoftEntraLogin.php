<?php

namespace App\Actions\Authentication;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BeginMicrosoftEntraLogin
{
    public const STATE_SESSION_KEY = 'auth.microsoft.state';

    public const CODE_VERIFIER_SESSION_KEY = 'auth.microsoft.code_verifier';

    public function handle(Request $request): string
    {
        $tenantId = $this->requiredUuidConfiguration('tenant_id');
        $clientId = $this->requiredUuidConfiguration('client_id');
        $redirectUri = $this->redirectUri();
        $state = Str::random(64);
        $codeVerifier = Str::random(96);

        $request->session()->put([
            self::STATE_SESSION_KEY => $state,
            self::CODE_VERIFIER_SESSION_KEY => $codeVerifier,
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => $state,
            'code_challenge' => $this->base64UrlEncode(hash('sha256', $codeVerifier, true)),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return $this->tenantEndpoint($tenantId, 'authorize').'?'.$query;
    }

    private function requiredUuidConfiguration(string $key): string
    {
        $value = config("services.microsoft.{$key}");

        abort_unless(
            is_string($value) && Str::isUuid($value),
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

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
