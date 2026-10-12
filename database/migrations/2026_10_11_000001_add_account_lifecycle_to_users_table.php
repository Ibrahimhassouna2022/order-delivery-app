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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('password');
            $table->string('locale', 5)->nullable()->after('is_guest');
            $table->timestamp('deletion_requested_at')->nullable()->after('locale');
            $table->timestamp('anonymized_at')->nullable()->after('deletion_requested_at');

            $table->index('deletion_requested_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deletion_requested_at']);
            $table->dropColumn(['is_guest', 'locale', 'deletion_requested_at', 'anonymized_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
