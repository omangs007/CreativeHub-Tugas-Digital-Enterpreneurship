<?php

use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('label', 100);
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
            $table->index('role_id');
        });

        // Role adalah data referensi yang wajib ada agar registrasi berjalan,
        // sehingga diisi di migration (bukan seeder) dan tersedia juga saat testing.
        $now = now();

        DB::table('roles')->insert(array_map(fn (RoleName $role) => [
            'name' => $role->value,
            'label' => $role->label(),
            'created_at' => $now,
            'updated_at' => $now,
        ], RoleName::cases()));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
