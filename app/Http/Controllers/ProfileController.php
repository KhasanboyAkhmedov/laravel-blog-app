<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ActivityLoggerService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status'          => session('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        // Track what changed before saving
        $changes = $user->getDirty();

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (! empty($changes)) {
            ActivityLoggerService::logRaw(
                action:  'profile_updated',
                model:   User::class,
                modelId: $user->id,
                payload: [
                    'changed_fields' => array_keys($changes),
                    'email'          => $user->email,
                ],
            );
        }

        return Redirect::route('profile.edit')->with('message', 'Profile updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();

        ActivityLoggerService::logRaw(
            action:  'account_deleted',
            model:   User::class,
            modelId: $user->id,
            payload: [
                'name'  => $user->name,
                'email' => $user->email,
            ],
            userId: $user->id,
        );

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
