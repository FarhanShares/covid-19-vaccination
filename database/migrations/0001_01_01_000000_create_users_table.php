<?php

use Illuminate\Support\Facades\Schema;
use App\Support\Enums\VaccinationStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
         * We'll be using 'nid' and 'dob' columns to validate (simulated: always passing) whether
         * a user can register or check his status. Currently 'dob' intentionally gets passing status
         * with any input, both in look-up status and registration pages. In real world, we might want
         * to enforce this sort of security measures, keeping it just simple for our current use case.
         *
         * The 'nid' column has a unique index, which will also create a regular index. Similarly, the
         * 'dob' column has a regular index. This will give us faster status look-ups.
         *
         * The status and create_at columns are indexed too, mainly to facilitate a boost in
         * scheduling process (handled by a scheduled job).
         *
         * We don't need the password field or email_verified_at sort of fields for our use case
         * at the moment. Neither we'll be requiring password reset sort of features now.
         */
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Since bigint is 20 bytes, it can easily hold 17 or 13 digits Bangladeshi NIDs
            $table->bigInteger('nid')->unique()->comment('National ID');
            $table->date('dob')->index()->comment('Date of birth');
            $table->string('name')->comment('Full name');
            $table->string('email')->unique();

            /**
             * Status will be casted and only hold values from "App\Support\Enums\VaccinationStatus"
             * The use of tiny integer is due to a slight edge over enum column in following ways:
             * - It tends to perform slightly better than ENUM in read and write operations,
             *   especially in larger datasets or complex queries.
             * - Indexing is slightly better on integer columns.
             * - Removes the necessity of DB's string-to-integer mapping process for enum columns.
             */
            $table->tinyInteger('status')->default(VaccinationStatus::NOT_SCHEDULED->value)->index();

            // We'll add vaccine_center_id after creating vaccine_centers table

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
