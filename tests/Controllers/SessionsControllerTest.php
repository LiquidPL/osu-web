<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace Tests\Controllers;

use App\Models\Country;
use App\Models\LoginAttempt;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SessionsControllerTest extends TestCase
{
    public function testLogin()
    {
        $password = 'password1';
        $user = factory(User::class)->create(compact('password'));

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => $password,
        ])->assertSuccessful();

        $this->assertAuthenticated();
    }

    public function testLoginInactiveUser()
    {
        $password = 'password1';
        $countryAcronym = (Country::first() ?? factory(Country::class)->create())->getKey();
        $user = factory(User::class)->create(['password' => $password, 'country_acronym' => $countryAcronym]);
        $user->update(['user_lastvisit' => now()->subDays(config('osu.user.inactive_days_verification') + 1)]);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => $password,
        ], [
            'CF_IPCOUNTRY' => $countryAcronym,
        ])->assertSuccessful();

        $this->assertAuthenticated();

        $this->get(route('home'))->assertStatus(401);
    }

    public function testLoginInactiveUserDifferentCountry()
    {
        $password = 'password1';
        $user = factory(User::class)->create(compact('password'));
        $user->update(['user_lastvisit' => now()->subDays(config('osu.user.inactive_days_verification') + 1)]);

        $this->assertNotSame('', $user->fresh()->user_password);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => $password,
        ], [
            'CF_IPCOUNTRY' => '__',
        ])->assertStatus(302);

        $this->assertGuest();
        $this->assertSame('', $user->fresh()->user_password);
    }

    public function testLoginMissingParameters()
    {
        $password = 'password1';
        $user = factory(User::class)->create(compact('password'));

        $this->post(route('login'))->assertStatus(422);
        $this->assertGuest();

        $this->post(route('login'), ['username' => $user->username])->assertStatus(422);
        $this->assertGuest();

        $this->post(route('login'), compact('password'))->assertStatus(422);
        $this->assertGuest();
    }

    public function testLoginWrongPassword()
    {
        $password = 'password1';
        $user = factory(User::class)->create(compact('password'));

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => "{$password}1",
        ])->assertStatus(403);

        $this->assertGuest();

        $record = LoginAttempt::find('127.0.0.1');
        $this->assertTrue($record->containsUser($user, 'fail:'));
        $this->assertSame(1, $record->unique_ids);
        $this->assertSame(1, $record->failed_attempts);
        $this->assertSame(1, $record->total_attempts);
    }

    public function testLoginWrongPasswordTwiceDifferent()
    {
        $password = 'password1';
        $user = factory(User::class)->create(compact('password'));

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password2',
        ])->assertStatus(403);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password3',
        ])->assertStatus(403);

        $this->assertGuest();

        $record = LoginAttempt::find('127.0.0.1');
        $this->assertTrue($record->containsUser($user, 'fail:'));
        $this->assertSame(1, $record->unique_ids);
        $this->assertSame(2, $record->failed_attempts);
        $this->assertSame(2, $record->total_attempts);
    }

    public function testLoginWrongPasswordTwiceSame()
    {
        $password = 'password1';
        $wrongPassword = 'password2';
        $user = factory(User::class)->create(compact('password'));

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => $wrongPassword,
        ])->assertStatus(403);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => $wrongPassword,
        ])->assertStatus(403);

        $this->assertGuest();

        $record = LoginAttempt::find('127.0.0.1');
        $this->assertTrue($record->containsUser($user, 'fail:'));
        $this->assertSame(1, $record->unique_ids);
        $this->assertSame(1, $record->failed_attempts);
        $this->assertSame(1, $record->total_attempts);
    }

    public function testLoginWrongPasswordExtraFailedOnAnotherUser()
    {
        $password = 'password1';
        $ip = '127.0.0.1';
        $firstUser = factory(User::class)->create(compact('password'));
        LoginAttempt::logAttempt($ip, $firstUser, 'fail', 'password2');

        $record = LoginAttempt::find('127.0.0.1');
        $this->assertTrue($record->containsUser($firstUser, 'fail:'));
        $this->assertSame(1, $record->unique_ids);
        $this->assertSame(1, $record->failed_attempts);
        $this->assertSame(1, $record->total_attempts);

        $secondUser = factory(User::class)->create(compact('password'));

        $this->post(route('login'), [
            'username' => $secondUser->username,
            'password' => "{$password}1",
        ])->assertStatus(403);

        $this->assertGuest();

        $record = LoginAttempt::find('127.0.0.1');
        $this->assertTrue($record->containsUser($secondUser, 'fail:'));
        $this->assertSame(2, $record->unique_ids);
        $this->assertSame(3, $record->failed_attempts);
        $this->assertSame(2, $record->total_attempts);
    }

    public function testLoginTwoFactorChallenge()
    {
        $secret = 'K3HKR3TUCVHYKC6Q';
        $user1 = factory(User::class)->create(['two_factor_secret' => $secret]);

        $response = $this->post(route('login', [
            'username' => $user1->username,
            'password' => 'password',
        ]));

        $response->assertJson([
            'twoFactorChallenge' => true,
        ]);

        $user2 = factory(User::class)->create();

        $response = $this->post(route('login', [
            'username' => $user2->username,
            'password' => 'password',
        ]));

        $response->assertJsonMissing([
            'twoFactorChallenge' => true,
        ]);
    }

    public function testLoginWithTwoFactorEnabled()
    {
        $secret = 'K3HKR3TUCVHYKC6Q';
        $user = factory(User::class)->create(['two_factor_secret' => $secret]);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->post(route('login.two-factor-challenge', [
            'token' => (new Google2FA())->getCurrentOtp($secret),
        ]))->assertSuccessful();

        $this->assertAuthenticated();
    }

    public function testLoginWithInvalidToken()
    {
        $secret = 'K3HKR3TUCVHYKC6Q';
        $user = factory(User::class)->create(['two_factor_secret' => $secret]);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response = $this->post(route('login.two-factor-challenge', [
            'token' => strrev((new Google2FA())->getCurrentOtp($secret)),
        ]));

        $response
            ->assertStatus(403)
            ->assertJson([
                'error' => 'Invalid token',
            ]);
    }
}
