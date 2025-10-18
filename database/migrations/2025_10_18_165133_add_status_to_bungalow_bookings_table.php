<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bungalow_bookings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])
                  ->default('pending')
                  ->after('check_out_date');
        });
    }

    public function down(): void
    {
        Schema::table('bungalow_bookings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
