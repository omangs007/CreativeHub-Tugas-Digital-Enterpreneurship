<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_admin_account(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Admin CreativeHub')
            ->expectsQuestion('Email admin', 'admin@creativehub.test')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-admin-123')
            ->expectsQuestion('Ulangi password', 'rahasia-admin-123')
            ->assertSuccessful();

        $admin = User::where('email', 'admin@creativehub.test')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('rahasia-admin-123', $admin->password));
    }

    public function test_command_rejects_mismatched_password(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Admin CreativeHub')
            ->expectsQuestion('Email admin', 'admin@creativehub.test')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-admin-123')
            ->expectsQuestion('Ulangi password', 'berbeda-123')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@creativehub.test']);
    }

    public function test_command_rejects_existing_email(): void
    {
        User::factory()->customer()->create(['email' => 'sudah@ada.test']);

        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Admin CreativeHub')
            ->expectsQuestion('Email admin', 'sudah@ada.test')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-admin-123')
            ->expectsQuestion('Ulangi password', 'rahasia-admin-123')
            ->assertFailed();

        $this->assertFalse(User::where('email', 'sudah@ada.test')->firstOrFail()->isAdmin());
    }
}
