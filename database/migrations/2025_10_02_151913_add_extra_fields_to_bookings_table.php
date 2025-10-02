<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('user_address')->nullable()->after('user_phone');
            $table->integer('number_of_guests')->nullable()->after('user_address');
            $table->string('payment_method')->nullable()->after('number_of_guests');
            $table->string('booking_reference')->nullable()->after('payment_method');
            $table->text('cancellation_reason')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'user_address',
                'number_of_guests',
                'payment_method',
                'booking_reference',
                'cancellation_reason'
            ]);
        });
    }
}
