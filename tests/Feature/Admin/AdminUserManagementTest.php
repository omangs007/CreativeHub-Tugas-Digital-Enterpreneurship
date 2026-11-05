<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Filament::setCurrentPanel('admin');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_freelancer_account(): void
    {
        $this->actingAs($this->admin)->get('/admin/users/create')->assertOk();

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Rina Desainer',
                'email' => 'Rina@Contoh.Test',
                'role_names' => [RoleName::Customer->value, RoleName::Freelancer->value],
                'password' => 'PasswordAwal123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'rina@contoh.test')->sole();
        $this->assertTrue($user->hasRole(RoleName::Customer));
        $this->assertTrue($user->hasRole(RoleName::Freelancer));
        $this->assertNotNull($user->freelancerProfile);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('PasswordAwal123', $user->password));

        $log = AuditLog::where('action', 'user.created_by_admin')->sole();
        $this->assertSame(['customer', 'freelancer'], $log->new_values['roles']);
        $this->assertArrayNotHasKey('password', $log->new_values);

        // Akun baru bisa langsung login di halaman publik.
        auth()->logout();
        $this->post('/login', ['email' => 'rina@contoh.test', 'password' => 'PasswordAwal123'])->assertRedirect('/dashboard');
    }

    public function test_create_is_validated(): void
    {
        User::factory()->create(['email' => 'sudah@ada.test']);

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm(['name' => '', 'email' => 'sudah@ada.test', 'role_names' => [], 'password' => '123'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'unique', 'role_names' => 'required', 'password']);
    }

    public function test_admin_can_edit_roles_and_reset_password(): void
    {
        $user = User::factory()->customer()->create(['name' => 'Nama Lama']);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertSchemaStateSet(['role_names' => ['customer'], 'name' => 'Nama Lama'])
            ->fillForm([
                'name' => 'Nama Baru',
                'role_names' => [RoleName::Customer->value, RoleName::Freelancer->value],
                'password' => 'PasswordReset456',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertTrue($user->hasRole(RoleName::Freelancer));
        $this->assertNotNull($user->freelancerProfile);
        $this->assertTrue(Hash::check('PasswordReset456', $user->password));

        $log = AuditLog::where('action', 'user.updated_by_admin')->sole();
        $this->assertSame('Nama Lama', $log->old_values['name']);
        $this->assertSame('direset admin', $log->new_values['password']);
        $this->assertStringNotContainsString('PasswordReset456', json_encode($log->new_values));
    }

    public function test_empty_password_keeps_existing_password(): void
    {
        $user = User::factory()->customer()->create();
        $hash = $user->password;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_admin_can_upload_and_remove_user_photo(): void
    {
        $user = User::factory()->freelancer()->create();

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['avatar_path' => UploadedFile::fake()->image('foto.png', 300, 300)])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = $user->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['avatar_path' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('dihapus', AuditLog::where('action', 'user.updated_by_admin')->latest('id')->first()->new_values['avatar']);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->fillForm(['role_names' => [RoleName::Customer->value]])
            ->call('save');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_non_admin_cannot_open_user_forms(): void
    {
        $user = User::factory()->customer()->create();
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($freelancer)->get("/admin/users/{$user->id}/edit")->assertForbidden();
    }

    public function test_list_shows_create_and_edit_actions(): void
    {
        $user = User::factory()->customer()->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertActionVisible('create')
            ->assertTableActionVisible('edit', $user)
            ->assertTableActionDoesNotExist('delete');
    }
}
