<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    /** @return array{name: string, project_id: ProjectFactory, priority: int} */
    public function definition(): array
    {
        return ['name' => fake()->sentence(4), 'project_id' => Project::factory(), 'priority' => 1];
    }
}
