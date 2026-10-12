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
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('method');
            $table->string('provider')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('amount', 12, 3);
            $table->char('currency_code', 3);
            $table->string('provider_reference')->nullable();
            $table->string('idempotency_key');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'idempotency_key']);
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
