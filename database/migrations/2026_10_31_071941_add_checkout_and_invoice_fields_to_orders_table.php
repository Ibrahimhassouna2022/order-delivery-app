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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_location_id')->nullable()->change();
            $table->string('invoice_number')->nullable()->unique()->after('status');
            $table->timestamp('payment_approved_at')->nullable()->after('invoice_number');
            $table->string('fulfillment_type')->default('delivery')->after('payment_approved_at');
            $table->foreignId('delivery_zone_id')
                ->nullable()
                ->after('fulfillment_type')
                ->constrained('delivery_zones')
                ->nullOnDelete();
            $table->string('delivery_address')->nullable()->after('delivery_zone_id');
            $table->string('delivery_phone', 30)->nullable()->after('delivery_address');
            $table->text('notes')->nullable()->after('delivery_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropColumn([
                'invoice_number',
                'payment_approved_at',
                'fulfillment_type',
                'delivery_address',
                'delivery_phone',
                'notes',
            ]);
            $table->foreignId('delivery_location_id')->nullable(false)->change();
        });
    }
};
