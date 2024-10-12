<?php

use App\Models\VaccineCenter;
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
        Schema::create('vaccine_center_daily_usages', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignIdFor(VaccineCenter::class)->constrained()->cascadeOnDelete();
            $table->smallInteger('usage_count')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccine_center_appointment_daily_usages');
    }
};
