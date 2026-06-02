<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'mileage')) {
                $table->decimal('mileage', 8, 2)->nullable()->after('vehicle_type');
            }
        });

        Schema::table('shipping_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_addresses', 'round_trip_distance_km')) {
                $table->decimal('round_trip_distance_km', 10, 2)->nullable()->after('full_address');
            }
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'mileage')) {
                $table->dropColumn('mileage');
            }
        });

        Schema::table('shipping_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_addresses', 'round_trip_distance_km')) {
                $table->dropColumn('round_trip_distance_km');
            }
        });
    }
};
