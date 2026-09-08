<?php

namespace App\Http\Controllers;

use App\Actions\Authentication\BeginMicrosoftEntraLogin;
use App\Actions\Authentication\CompleteMicrosoftEntraLogin;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class MicrosoftEntraController extends Controller
{
    public function redirect(
        Request $request,
        BeginMicrosoftEntraLogin $beginMicrosoftEntraLogin,
    ): Response {
        return redirect()->away($beginMicrosoftEntraLogin->handle($request));
    }

    public function callback(
        Request $request,
        CompleteMicrosoftEntraLogin $completeMicrosoftEntraLogin,
        LoginResponseContract $loginResponse,
    ): Response {
        try {
            $user = $completeMicrosoftEntraLogin->handle($request);
        } catch (ValidationException $exception) {
            return redirect()->route('login')->withErrors($exception->errors());
        } catch (ConnectionException $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'microsoft' => __('Microsoft sign-in is temporarily unavailable. Please try again.'),
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $loginResponse->toResponse($request);
    }
}
