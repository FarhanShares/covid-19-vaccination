<?php

namespace Database\Factories;

use App\Models\VaccineCenter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nid = fake()->randomDigitNotZero() * 100_000_000_0000
            + fake()->randomNumber(9); // ensure a 13 digit random number

        $vid = VaccineCenter::inRandomOrder()->first()->id
            ?? VaccineCenter::factory()->create()->id; // ensure a vaccine center id

        return [
            'nid' => $nid,
            'dob' => fake()->dateTime(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'vaccine_center_id' => $vid,
            'vaccine_appointment_id' => null,
        ];
    }
}
