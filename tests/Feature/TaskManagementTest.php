<?php

use App\Models\Project;
use App\Models\Task;
use Inertia\Testing\AssertableInertia as Assert;

test('renders an empty task board without projects', function () {
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->component('tasks/index')->has('projects', 0)->has('tasks', 0)->where('selectedProjectId', null));
});

test('shows only the selected project tasks in priority order', function () {
    $project = Project::factory()->create();
    $tasks = Task::factory()->for($project)->count(2)->sequence(['priority' => 2], ['priority' => 1])->create();
    Task::factory()->create();

    $this->get(route('home', ['project' => $project->id]))->assertInertia(fn (Assert $page) => $page
        ->component('tasks/index')->has('tasks', 2)->where('tasks.0.id', $tasks[1]->id)
        ->where('tasks.1.id', $tasks[0]->id)->where('selectedProjectId', $project->id));
});

test('creates and selects a project', function () {
    $this->post(route('projects.store'), ['name' => 'Website'])->assertSessionHasNoErrors()
        ->assertRedirect(route('home', ['project' => Project::query()->sole()->id]));

    $this->assertDatabaseHas('projects', ['name' => 'Website']);
});

test('rejects duplicate project names', function () {
    $project = Project::factory()->create();

    $this->post(route('projects.store'), ['name' => $project->name])->assertSessionHasErrors('name');

    $this->assertDatabaseCount('projects', 1);
});

test('appends new tasks and saves timestamps without accepting a supplied priority', function () {
    $project = Project::factory()->create();
    Task::factory()->for($project)->create();
    $this->freezeTime();

    $this->post(route('tasks.store', $project), ['name' => 'Ship website', 'priority' => 99])
        ->assertSessionHasNoErrors()->assertRedirect(route('home', ['project' => $project->id]));

    $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'name' => 'Ship website', 'priority' => 2,
        'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()]);
});

test('edits a task name while preserving its project and priority', function () {
    $task = Task::factory()->create();

    $this->patch(route('tasks.update', [$task->project_id, $task]), ['name' => 'Updated task', 'priority' => 99])
        ->assertSessionHasNoErrors()->assertRedirect(route('home', ['project' => $task->project_id]));

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'name' => 'Updated task', 'priority' => 1, 'project_id' => $task->project_id]);
});

test('deletes a task and closes the priority gap only within its project', function () {
    $project = Project::factory()->create();
    $tasks = Task::factory()->for($project)->count(3)->sequence(['priority' => 1], ['priority' => 2], ['priority' => 3])->create();
    $other = Task::factory()->create();

    $this->delete(route('tasks.destroy', [$project, $tasks[1]]))->assertRedirect(route('home', ['project' => $project->id]));

    $this->assertModelMissing($tasks[1]);
    expect($tasks[0]->fresh()->priority)->toBe(1);
    expect($tasks[2]->fresh()->priority)->toBe(2);
    expect($other->fresh()->priority)->toBe(1);
});

test('reorders every task with contiguous priorities and preserves other projects', function () {
    $project = Project::factory()->create();
    $tasks = Task::factory()->for($project)->count(3)->sequence(['priority' => 1], ['priority' => 2], ['priority' => 3])->create();
    $other = Task::factory()->create();

    $this->patch(route('tasks.reorder', $project), ['task_ids' => [$tasks[2]->id, $tasks[0]->id, $tasks[1]->id]])
        ->assertSessionHasNoErrors()->assertRedirect(route('home', ['project' => $project->id]));

    expect($project->tasks()->orderBy('priority')->pluck('id')->all())->toBe([$tasks[2]->id, $tasks[0]->id, $tasks[1]->id]);
    expect($project->tasks()->orderBy('priority')->pluck('priority')->all())->toBe([1, 2, 3]);
    expect($other->fresh()->priority)->toBe(1);
});

test('rejects incomplete duplicate foreign and unknown task orders without changing priorities', function (string $scenario) {
    $project = Project::factory()->create();
    $tasks = Task::factory()->for($project)->count(2)->sequence(['priority' => 1], ['priority' => 2])->create();
    $other = Task::factory()->create();
    $ids = match ($scenario) {
        'incomplete' => [$tasks[1]->id],
        'duplicate' => [$tasks[0]->id, $tasks[0]->id],
        'foreign' => [$tasks[1]->id, $other->id],
        'unknown' => [$tasks[1]->id, 99999],
        'empty' => [],
    };

    $response = $this->patch(route('tasks.reorder', $project), ['task_ids' => $ids]);
    $response->assertSessionHasErrors($scenario === 'duplicate' ? 'task_ids.0' : 'task_ids');

    expect($project->tasks()->orderBy('priority')->pluck('id')->all())->toBe($tasks->modelKeys());
    expect($other->fresh()->priority)->toBe(1);
})->with(['incomplete', 'duplicate', 'foreign', 'unknown', 'empty']);

test('rejects edits and deletes through a different project', function (string $method) {
    $task = Task::factory()->create();
    $project = Project::factory()->create();

    $this->{$method}(route($method === 'patch' ? 'tasks.update' : 'tasks.destroy', [$project, $task]), ['name' => 'Wrong project'])
        ->assertNotFound();

    $this->assertModelExists($task);
    expect($task->fresh()->name)->toBe($task->name);
})->with(['patch', 'delete']);

test('rejects invalid task names without saving a task', function (mixed $name) {
    $project = Project::factory()->create();

    $this->post(route('tasks.store', $project), ['name' => $name])->assertSessionHasErrors('name');

    $this->assertDatabaseCount('tasks', 0);
})->with(['missing' => null, 'blank' => '   ', 'too long' => str_repeat('a', 256), 'non string' => [['invalid']]]);
