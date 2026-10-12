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
        Schema::create('driver_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('offered');
            $table->unsignedInteger('distance_meters')->nullable();
            $table->decimal('fare', 12, 3)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['driver_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_assignments');
    }
};
