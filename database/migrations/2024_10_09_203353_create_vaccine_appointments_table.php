<?php

use Illuminate\Support\Facades\Schema;
use App\Support\Enums\AppointmentStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vaccine_appointments', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index(); // Date of the scheduled vaccination
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vaccine_center_id')->constrained('vaccine_centers')->onDelete('cascade');
            $table->tinyInteger('status')->default(AppointmentStatus::SCHEDULED->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccine_appointments');
    }
};
