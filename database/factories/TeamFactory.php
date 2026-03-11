<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => 'Team ' . $this->faker->city(),
            // Secara otomatis membuat Divisi baru jika tidak didefinisikan
            'division_id' => Division::factory(),

            'created_by_id' => User::factory(),
            'updated_by_id' => User::factory(),

            // uuid tidak perlu diisi karena sudah ada di boot() model Team
        ];
    }
}
