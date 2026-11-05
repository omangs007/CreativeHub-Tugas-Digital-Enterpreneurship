<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 40)->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->string('method', 30);
            $table->string('provider', 30)->default('dummy');
            $table->string('provider_reference', 100)->nullable();

            // Snapshot nominal dari order (integer Rupiah) agar laporan konsisten.
            $table->char('currency', 3)->default('IDR');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('commission_amount');
            $table->unsignedBigInteger('freelancer_amount');

            $table->string('status', 20)->default(PaymentStatus::Pending->value);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
            $table->index('created_at');
            $table->index('customer_id');
        });

        $methods = implode(', ', array_map(fn (PaymentMethod $m) => "'{$m->value}'", PaymentMethod::cases()));
        $statuses = implode(', ', array_map(fn (PaymentStatus $s) => "'{$s->value}'", PaymentStatus::cases()));

        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ({$methods}))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ({$statuses}))");
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount > 0 AND amount = commission_amount + freelancer_amount)');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_paid_at_check CHECK (status <> 'success' OR paid_at IS NOT NULL)");

        // Satu pembayaran berhasil dan satu pembayaran menunggu per order, dijaga database.
        DB::statement("CREATE UNIQUE INDEX payments_one_success_per_order ON payments (order_id) WHERE status = 'success'");
        DB::statement("CREATE UNIQUE INDEX payments_one_pending_per_order ON payments (order_id) WHERE status = 'pending'");

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('event', 50);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
    }
};
