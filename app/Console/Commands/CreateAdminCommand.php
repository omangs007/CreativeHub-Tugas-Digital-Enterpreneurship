<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Satu-satunya jalur membuat akun Admin: registrasi publik tidak pernah memberi role Admin.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Membuat akun Admin CreativeHub secara interaktif';

    public function handle(): int
    {
        $input = [
            'name' => $this->ask('Nama admin'),
            'email' => $this->ask('Email admin'),
            'password' => $this->secret('Password (minimal 8 karakter)'),
            'password_confirmation' => $this->secret('Ulangi password'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole(RoleName::Admin);

            return $user;
        });

        $this->info("Akun admin {$user->email} berhasil dibuat. Login di ".url('/admin'));

        return self::SUCCESS;
    }
}
