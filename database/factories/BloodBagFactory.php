<?php

namespace Database\Factories;

use App\Models\BloodBag;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodBagFactory extends Factory
{
    protected $model = BloodBag::class;

    public function definition(): array
    {
        $collected = now()->subDays(fake()->numberBetween(1, 5));

        return [
            'bag_number' => 'BAG-' . now()->format('Ymd') . '-' . strtoupper(fake()->unique()->bothify('??###')),
            'blood_type' => fake()->randomElement(['A', 'B', 'AB', 'O']),
            'rhesus' => fake()->randomElement(['+', '-']),
            'component' => fake()->randomElement(['WB', 'PRC']),
            'collected_at' => $collected,
            'expired_at' => $collected->copy()->addDays(35),
            'status' => BloodBag::KARANTINA,
        ];
    }
}