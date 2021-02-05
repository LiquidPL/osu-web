<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Http\Controllers;

use App\Libraries\User\DatadogLoginAttempt;
use App\Libraries\User\ForceReactivation;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use NoCaptcha;
use PragmaRX\Google2FA\Google2FA;

class SessionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', ['only' => [
            'store',
        ]]);

        return parent::__construct();
    }

    public function store()
    {
        $request = request();

        if ($request->attributes->get('csrf') === false) {
            DatadogLoginAttempt::log('invalid_csrf');

            abort(403, 'Reload page and try again');
        }

        $params = get_params($request->all(), null, ['username:string', 'password:string', 'remember:bool', 'g-recaptcha-response:string']);
        $username = presence(trim($params['username'] ?? null));
        $password = presence($params['password'] ?? null);
        $remember = $params['remember'] ?? false;

        if ($username === null) {
            DatadogLoginAttempt::log('missing_username');

            abort(422);
        }

        if ($password === null) {
            DatadogLoginAttempt::log('missing_password');

            abort(422);
        }

        if (captcha_triggered()) {
            $token = presence($params['g-recaptcha-response'] ?? null);
            $validCaptcha = false;

            if ($token !== null) {
                $validCaptcha = NoCaptcha::verifyResponse($token);
            }

            if (!$validCaptcha) {
                if ($token === null) {
                    DatadogLoginAttempt::log('missing_captcha');
                } else {
                    DatadogLoginAttempt::log('invalid_captcha');
                }

                return $this->triggerCaptcha(trans('users.login.invalid_captcha'), 422);
            }
        }

        $ip = $request->getClientIp();

        /** @var User $user */
        $user = User::findForLogin($username);

        if ($user === null && strpos($username, '@') !== false && !config('osu.user.allow_email_login')) {
            $authError = trans('users.login.email_login_disabled');
        } else {
            $authError = User::attemptLogin($user, $password, $ip);
        }

        if ($authError === null) {
            $forceReactivation = new ForceReactivation($user, $request);

            if ($forceReactivation->isRequired()) {
                $forceReactivation->run();

                return ujs_redirect(route('password-reset'));
            }

            if ($user->isUsingTwoFactorAuth()) {
                $request->session()->put([
                    'user_id' => $user->user_id,
                    'remember' => $remember,
                ]);

                return [
                    'twoFactorChallenge' => true,
                    'login_box' => view('layout._popup_two_factor')->render(),
                ];
            }

            $this->login($user, $remember);

            return $this->sendLoginResponse();
        }

        if (captcha_triggered()) {
            return $this->triggerCaptcha($authError);
        }

        return error_popup($authError, 403);
    }

    public function destroy()
    {
        if (Auth::check()) {
            logout();
        }

        if (get_bool(request('redirect_home'))) {
            return ujs_redirect(route('home'));
        }

        return captcha_triggered() ? ['captcha_triggered' => true] : [];
    }

    public function twoFactorChallenge(Request $request)
    {
        $userId = presence($request->session()->get('user_id'));
        $remember = $request->session()->get('remember') ?? false;
        $token = presence(trim($request->get('token')));

        if ($userId === null) {
            DatadogLoginAttempt::log('two_factor_no_user_id');
            abort(422);
        }

        /** @var User $user */
        $user = User::find($userId);

        if ($user === null) {
            DatadogLoginAttempt::log('two_factor_wrong_user_id');
            abort(422);
        }

        if ((new Google2FA())->verify($token, $user->two_factor_secret)) {
            $this->login($user, $remember);

            return $this->sendLoginResponse();
        }

        return error_popup(trans('users.login.invalid_token'), 403);
    }

    private function sendLoginResponse()
    {
        return [
            'header' => view('layout._header_user')->render(),
            'header_popup' => view('layout._popup_user')->render(),
            'user' => Auth::user()->defaultJson(),
        ];
    }

    private function triggerCaptcha($message, $returnCode = 403)
    {
        return response([
            'error' => $message,
            'captcha_triggered' => true,
        ], $returnCode);
    }
}
