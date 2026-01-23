<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add 'returned_to_supplier' to the status enum
        DB::statement("ALTER TABLE reels MODIFY COLUMN status ENUM('in_stock', 'partially_used', 'fully_used', 'returned_to_supplier') NOT NULL DEFAULT 'in_stock'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove 'returned_to_supplier' from the status enum
        // First, reset any returned_to_supplier status to fully_used
        DB::statement("UPDATE reels SET status = 'fully_used' WHERE status = 'returned_to_supplier'");
        
        // Then remove the enum value
        DB::statement("ALTER TABLE reels MODIFY COLUMN status ENUM('in_stock', 'partially_used', 'fully_used') NOT NULL DEFAULT 'in_stock'");
    }
};
