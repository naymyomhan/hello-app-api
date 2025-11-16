<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LinkedSocialAccount;
use App\Models\User;
use App\Traits\ResponseTrait;
use Exception;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthController extends Controller
{
    use ResponseTrait;

    public function loginWithGoogle()
    {
        $socialiteUser = null;

        $accessToken = request()->get('access_token');
        $provider = request()->get('provider');

        try {
            $socialiteUser = Socialite::driver($provider)->userFromToken($accessToken);
        } catch (Exception $exception) {
            // return $exception;
            return $this->fail('Invalid credentials provided.');
        }
        if ($socialiteUser) {
            return $this->findOrCreate($socialiteUser, $provider);
        }
        return $socialiteUser;
    }

    protected function findOrCreate(SocialiteUser $socialiteUser, string $provider)
    {
        $linkedSocialAccount = LinkedSocialAccount::where('provider_name', $provider)
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        if ($linkedSocialAccount) {
            $user =  $linkedSocialAccount->user;
            $user->save();

            $token = $linkedSocialAccount->user->createToken("USER-TOKEN")->plainTextToken;
            $data = [
                'token' => $token,
            ];
            if ($user->is_baned) {
                return $this->fail('Your account is banned', 401);
            }
            return $this->success("Login account successful", $data);
        } else {
            $user = null;

            if ($email = $socialiteUser->getEmail()) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->save();
                }
            }

            if (!$user) {
                $user = User::create([
                    'name' => $socialiteUser->getName(),
                    'email' => $socialiteUser->getEmail(),
                    'provider_name' => $provider,
                    'provider_id' => $socialiteUser->getId()
                ]);
            }

            $user->linkedSocialAccounts()->create([
                'provider_id' => $socialiteUser->getId(),
                'provider_name' => $provider,
            ]);

            $token = $user->createToken("USER-TOKEN")->plainTextToken;
            $data = [
                'token' => $token,
            ];

            if ($user->is_baned) {
                return $this->fail('Your account is banned', 401);
            }
            return $this->success("Login account successful", $data);
        }
    }
}
