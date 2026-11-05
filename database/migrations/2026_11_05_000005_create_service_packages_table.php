<?php

use App\Enums\PackageTier;
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
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('tier', 20);
            $table->string('name', 100);
            $table->text('description');
            $table->unsignedBigInteger('price'); // Rupiah utuh, bukan float
            $table->unsignedSmallInteger('delivery_days');
            $table->unsignedSmallInteger('revisions')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'tier']);
            $table->index(['service_id', 'is_active']);
        });

        $tiers = implode(', ', array_map(fn (PackageTier $t) => "'{$t->value}'", PackageTier::cases()));

        DB::statement("ALTER TABLE service_packages ADD CONSTRAINT service_packages_tier_check CHECK (tier IN ({$tiers}))");
        DB::statement('ALTER TABLE service_packages ADD CONSTRAINT service_packages_price_check CHECK (price > 0)');
        DB::statement('ALTER TABLE service_packages ADD CONSTRAINT service_packages_delivery_days_check CHECK (delivery_days > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
