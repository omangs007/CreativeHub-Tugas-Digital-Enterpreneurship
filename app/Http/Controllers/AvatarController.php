<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

/**
 * Foto profil user yang sedang login. Nama file dibuat acak oleh server.
 */
class AvatarController extends Controller
{
    private const DISK = 'public';

    public function update(Request $request): RedirectResponse
    {
        $request->validateWithBag('avatar', [
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:1024', 'dimensions:min_width=100,min_height=100,max_width=3000,max_height=3000'],
        ], [], ['avatar' => 'foto profil']);

        $user = $request->user();
        $old = $user->avatar_path;

        $user->avatar_path = $request->file('avatar')->store("avatars/{$user->id}", self::DISK);
        $user->save();

        if ($old) {
            Storage::disk(self::DISK)->delete($old);
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }
}
