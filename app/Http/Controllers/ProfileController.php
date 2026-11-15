<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    // @desc    Update profile info
    // @route   PUT /profile
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'profile_is_public' => ['sometimes', 'boolean'],
        ]);

        $user->fill($validatedData);
        $user->profile_is_public = $request->boolean('profile_is_public');

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return redirect()->route('dashboard')->with('success', 'Profile info updated!');
    }

    public function show(User $user): View
    {
        abort_unless($user->profile_is_public || auth()->id() === $user->id, 404);

        $user->load('workExperiences');

        return view('profiles.show', ['candidate' => $user]);
    }
}
