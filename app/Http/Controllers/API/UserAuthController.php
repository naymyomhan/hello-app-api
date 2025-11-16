<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserAuthController extends Controller
{
    use ResponseTrait;

    public function register(UserRegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken("USER-TOKEN")->plainTextToken;

        return $this->success("Registration successful", ['token' => $token]);
    }

    public function login(UserLoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->fail('Email or password is incorrect', 401);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->is_baned) {
            return $this->fail('Your account have banned from our system', 401);
        }

        $token = $user->createToken("USER-TOKEN")->plainTextToken;

        return $this->success("Login successful", ['token' => $token]);
    }
    
    public function logout(Request $request)
    {   
        $request->user()->currentAccessToken()->delete();
        return $this->success("Logout successful");
    }
}
