<?php

use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Services\Wallet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Escrow (dana tertahan), saldo freelancer, dan penarikan dengan komisi platform.
 * Seluruh nominal integer Rupiah; integritas saldo dijaga constraint database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('held_balance')->default(0);
            $table->unsignedBigInteger('available_balance')->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE freelancer_wallets ADD CONSTRAINT freelancer_wallets_balance_check CHECK (held_balance >= 0 AND available_balance >= 0)');

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('freelancer_wallet_id')->constrained()->restrictOnDelete();

            // Snapshot perhitungan saat diajukan: amount = commission_amount + net_amount.
            $table->unsignedBigInteger('amount');
            $table->unsignedInteger('commission_rate_bps');
            $table->unsignedBigInteger('commission_amount');
            $table->unsignedBigInteger('net_amount');

            $table->string('bank_name', 60);
            $table->string('account_number', 30);
            $table->string('account_holder', 100);

            $table->string('status', 20)->default(WithdrawalStatus::Pending->value);
            $table->string('transfer_reference', 100)->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        $statuses = implode(', ', array_map(fn (WithdrawalStatus $s) => "'{$s->value}'", WithdrawalStatus::cases()));

        DB::statement("ALTER TABLE withdrawals ADD CONSTRAINT withdrawals_status_check CHECK (status IN ({$statuses}))");
        DB::statement('ALTER TABLE withdrawals ADD CONSTRAINT withdrawals_amount_check CHECK (
            amount > 0 AND net_amount > 0 AND amount = commission_amount + net_amount
        )');
        DB::statement("ALTER TABLE withdrawals ADD CONSTRAINT withdrawals_completed_check CHECK (
            status <> 'completed' OR (transfer_reference IS NOT NULL AND completed_at IS NOT NULL)
        )");

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('withdrawal_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('held_after');
            $table->unsignedBigInteger('available_after');
            $table->string('description', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['freelancer_wallet_id', 'created_at']);
        });

        $types = implode(', ', array_map(fn (WalletTransactionType $t) => "'{$t->value}'", WalletTransactionType::cases()));

        DB::statement("ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_type_check CHECK (type IN ({$types}))");
        DB::statement('ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_amount_check CHECK (amount > 0)');
        DB::statement('ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_source_check CHECK (
            (order_id IS NOT NULL AND withdrawal_id IS NULL) OR (order_id IS NULL AND withdrawal_id IS NOT NULL)
        )');

        // Anti transaksi ganda: tiap pesanan hanya sekali masuk escrow dan sekali dicairkan,
        // tiap penarikan hanya sekali didebit dan sekali dikembalikan.
        DB::statement('CREATE UNIQUE INDEX wallet_transactions_order_type_unique ON wallet_transactions (order_id, type) WHERE order_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX wallet_transactions_withdrawal_type_unique ON wallet_transactions (withdrawal_id, type) WHERE withdrawal_id IS NOT NULL');

        // Pesanan yang sudah dibayar sebelum fitur ini ada ikut dicatat agar saldo konsisten.
        app(Wallet::class)->backfillFromOrders();
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('freelancer_wallets');
    }
};
