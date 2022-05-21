<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'matematika' => $this->faker->numberBetween(20, 100),
            'fisika' => $this->faker->numberBetween(20, 100),
            'kimia' => $this->faker->numberBetween(20, 100),
            'biologi' => $this->faker->numberBetween(20, 100),
            'sejarah' => $this->faker->numberBetween(20, 100),
            'akuntansi' => $this->faker->numberBetween(20, 100),
            'sosiologi' => $this->faker->numberBetween(20, 100),
            'geografi' => $this->faker->numberBetween(20, 100),
        ];
    }
}
