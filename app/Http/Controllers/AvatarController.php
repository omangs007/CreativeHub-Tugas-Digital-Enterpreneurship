<?php

namespace App\Http\Controllers;

use App\Support\AvatarImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Foto profil user yang sedang login. Nama file dibuat acak oleh server.
 */
class AvatarController extends Controller
{
    private const DISK = 'public';

    /** Ukuran maksimal foto yang diunggah (KB); harus <= upload_max_filesize PHP. */
    public const MAX_KB = 5120;

    public function update(Request $request): RedirectResponse
    {
        $request->validateWithBag('avatar', [
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KB, 'dimensions:min_width=100,min_height=100,max_width=8000,max_height=8000'],
        ], [], ['avatar' => 'foto profil']);

        $user = $request->user();
        $old = $user->avatar_path;

        // Foto besar diperkecil otomatis menjadi 512×512 WebP.
        $stored = $request->file('avatar')->store("avatars/{$user->id}", self::DISK);

        try {
            $user->avatar_path = AvatarImage::normalize($stored, $user->id);
        } catch (RuntimeException) {
            Storage::disk(self::DISK)->delete($stored);

            throw ValidationException::withMessages([
                'avatar' => __('Berkas foto tidak dapat dibaca sebagai gambar. Gunakan JPG, PNG, atau WebP lain.'),
            ])->errorBag('avatar');
        }
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
