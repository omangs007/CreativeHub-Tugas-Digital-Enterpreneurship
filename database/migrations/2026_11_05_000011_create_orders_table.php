<?php

use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->restrictOnDelete();

            // Sumber order: tepat satu dari paket layanan atau proposal proyek.
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('proposal_id')->nullable()->unique()->constrained()->restrictOnDelete();

            // Snapshot saat order dibuat; tidak berubah walau layanan/proposal diedit.
            $table->string('title', 150);
            $table->string('package_tier', 20)->nullable();
            $table->string('package_name', 100)->nullable();
            $table->text('scope_description');
            $table->unsignedSmallInteger('delivery_days');
            $table->unsignedSmallInteger('revisions_allowed')->default(0);
            $table->unsignedSmallInteger('revisions_used')->default(0);

            // Nominal Rupiah utuh; dihitung server.
            $table->char('currency', 3)->default('IDR');
            $table->unsignedBigInteger('subtotal_amount');
            $table->unsignedInteger('commission_rate_bps'); // basis poin, 1000 = 10%
            $table->unsignedBigInteger('commission_amount');
            $table->unsignedBigInteger('freelancer_amount');
            $table->unsignedBigInteger('total_amount');

            $table->string('status', 30)->default(OrderStatus::PendingPayment->value);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['freelancer_id', 'status']);
            $table->index('created_at');
        });

        $statuses = implode(', ', array_map(fn (OrderStatus $s) => "'{$s->value}'", OrderStatus::cases()));

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ({$statuses}))");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_single_source_check CHECK (
            (service_package_id IS NOT NULL AND service_id IS NOT NULL AND proposal_id IS NULL AND project_id IS NULL)
            OR (proposal_id IS NOT NULL AND project_id IS NOT NULL AND service_package_id IS NULL AND service_id IS NULL)
        )');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amounts_check CHECK (
            total_amount > 0
            AND total_amount = subtotal_amount
            AND total_amount = commission_amount + freelancer_amount
        )');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_distinct_parties_check CHECK (customer_id <> freelancer_id)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_revisions_check CHECK (revisions_used <= revisions_allowed)');

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('orders');
    }
};
