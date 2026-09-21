<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'task_id' => null,
            'title' => fake()->words(3, true),
            'body' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
