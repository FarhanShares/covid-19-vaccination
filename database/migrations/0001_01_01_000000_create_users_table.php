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
        /**
         * <Notes by Farhan Israq>
         *
         * Modified the typical default users table that is shipped with Laravel to suit our needs.
         *
         * We'll be using nid and date_of_birth to validate (simulated) whether a user can register
         * or check his status. In real world we might want to enforce extra security measures,
         * e.g. validation by OTP, keeping it just simple for our use case.
         *
         * The nid column has a unique index, which will also create a regular index. Similarly, the
         * dob column has a regular index. This will give us faster status look-ups.
         *
         * The status and create_at columns are indexed too, mainly to facilitate a boost in
         * scheduling process (handled by a scheduled job).
         *
         * We don't need the password field or email_verified_at sort of fields for our use case
         * at the moment. Neither we'll be requiring password reset sort of features now.
         */
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('nid')->unique()->comment('National ID');
            $table->date('dob')->index()->comment('Date of birth');
            $table->string('name')->comment('Full name');
            $table->string('email')->unique();

            $table->enum('status', App\Support\Enums\VaccinationStatus::cases())->index();

            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
