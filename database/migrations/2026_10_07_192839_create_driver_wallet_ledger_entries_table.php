<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('driver_wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('delivery_credit_order_id')
                ->nullable()
                ->unique()
                ->constrained('orders')
                ->restrictOnDelete();
            $table->string('entry_type');
            $table->decimal('amount', 12, 3);
            $table->char('currency_code', 3);
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['driver_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_wallet_ledger_entries');
    }
};
