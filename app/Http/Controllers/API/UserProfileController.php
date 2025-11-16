<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateIpWhiteListRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserApiResource;
use App\Models\LinkedSocialAccount;
use App\Models\Setting;
use App\Models\Topup;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    use ResponseTrait;

    /**
     * Get Profile
     */
    public function profile()
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->guard('sanctum')->user();

            $pending_topup = Topup::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($pending_topup) {
                $user->has_pending_topup = true;
            }

            $linked_sodial_account = LinkedSocialAccount::where('user_id', $user->id)
                ->first();

            if ($linked_sodial_account) {
                $user->is_google_login = true;
            } else {
                $user->is_google_login = false;
            }

            return $this->success("get user data successful", new UserApiResource($user));
        } catch (\Throwable $th) {
            return $this->fail($th->getMessage() ? $th->getMessage() : "server error", 500);
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth()->guard('sanctum')->user();
            $data = $request->validated();

            if ($request->hasFile('avatar')) {
                $user->clearMediaCollection('avatar');
                $user->addMediaFromRequest('avatar')
                    ->toMediaCollection('avatar');
                unset($data['avatar']);
            }

            $user->name = $data['name'] ?? $user->name;
            $user->update();

            return $this->success("Update profile successful", new UserApiResource($user));
        } catch (\Throwable $th) {
            return $this->fail($th->getMessage() ? $th->getMessage() : "server error", 500);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::guard('sanctum')->user();
            $data = $request->validated();

            if (!Hash::check($data['current_password'], $user->password)) {
                return $this->fail("Current password is incorrect.", 400);
            }

            $user->password = Hash::make($data['new_password']);
            $user->save();

            return $this->success("Password updated successfully.");
        } catch (\Throwable $th) {
            return $this->fail($th->getMessage() ?: "Server error", 500);
        }
    }


    public function deleteAccount(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success("Delete account successful");
    }
}
