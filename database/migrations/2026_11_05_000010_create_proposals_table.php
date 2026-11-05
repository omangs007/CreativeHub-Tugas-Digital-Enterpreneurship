<?php

use App\Enums\ProposalStatus;
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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('price'); // Rupiah utuh
            $table->unsignedSmallInteger('delivery_days');
            $table->unsignedSmallInteger('revisions')->default(0);
            $table->text('message');
            $table->string('status', 20)->default(ProposalStatus::Pending->value);
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('freelancer_id');
        });

        $statuses = implode(', ', array_map(fn (ProposalStatus $s) => "'{$s->value}'", ProposalStatus::cases()));

        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ({$statuses}))");
        DB::statement('ALTER TABLE proposals ADD CONSTRAINT proposals_price_check CHECK (price > 0)');
        DB::statement('ALTER TABLE proposals ADD CONSTRAINT proposals_delivery_days_check CHECK (delivery_days > 0)');

        // Satu proposal aktif (pending/diterima) per freelancer per proyek.
        DB::statement("CREATE UNIQUE INDEX proposals_one_active_per_freelancer ON proposals (project_id, freelancer_id) WHERE status IN ('pending', 'accepted')");
        // Maksimal satu proposal diterima per proyek, dijaga juga di level database.
        DB::statement("CREATE UNIQUE INDEX proposals_one_accepted_per_project ON proposals (project_id) WHERE status = 'accepted'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
