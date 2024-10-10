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
        Schema::create('vaccine_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyText('address');
            // The max number of people this center can vaccinate daily. Assuming the capacity is
            // well within the range of what an unsigned small integer column can hold (up to 65535)
            // and, can at least vaccinate 50 persons a day.
            $table->smallInteger('daily_capacity')->unsigned()->default(50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccine_centers');
    }
};
