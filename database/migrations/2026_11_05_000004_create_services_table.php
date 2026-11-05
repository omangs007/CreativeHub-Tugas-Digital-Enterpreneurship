<?php

use App\Enums\ServiceStatus;
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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->text('description');
            $table->string('thumbnail_path')->nullable();
            $table->string('status', 20)->default(ServiceStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('user_id');
        });

        $statuses = implode(', ', array_map(fn (ServiceStatus $s) => "'{$s->value}'", ServiceStatus::cases()));

        DB::statement("ALTER TABLE services ADD CONSTRAINT services_status_check CHECK (status IN ({$statuses}))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
