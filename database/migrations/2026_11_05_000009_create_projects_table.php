<?php

use App\Enums\ProjectStatus;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->unsignedBigInteger('budget_min')->nullable(); // Rupiah utuh
            $table->unsignedBigInteger('budget_max')->nullable(); // Rupiah utuh
            $table->date('deadline')->nullable();
            $table->string('status', 20)->default(ProjectStatus::Open->value);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('customer_id');
        });

        $statuses = implode(', ', array_map(fn (ProjectStatus $s) => "'{$s->value}'", ProjectStatus::cases()));

        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status IN ({$statuses}))");
        DB::statement('ALTER TABLE projects ADD CONSTRAINT projects_budget_check CHECK (budget_min IS NULL OR budget_max IS NULL OR budget_min <= budget_max)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
