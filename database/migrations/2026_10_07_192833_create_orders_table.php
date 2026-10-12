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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->foreignId('delivery_location_id')->constrained()->restrictOnDelete();
            $table->string('type')->default('food');
            $table->string('status')->default('placed');
            $table->string('idempotency_key');
            $table->char('currency_code', 3);
            $table->decimal('items_subtotal', 12, 3);
            $table->decimal('delivery_fee', 12, 3);
            $table->decimal('total', 12, 3);
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'idempotency_key']);
            $table->index(['status', 'created_at']);
            $table->index(['restaurant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
