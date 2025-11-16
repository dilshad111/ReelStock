<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reel_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels');
            $table->date('issue_date');
            $table->decimal('quantity_issued', 10, 2);
            $table->string('issued_to');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reel_issues');
    }
};
