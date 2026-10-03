<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'project' => ['nullable', 'integer', 'exists:projects,id'],
        ]);
        $projects = Project::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);
        $projectId = isset($validated['project']) ? (int) $validated['project'] : $projects->first()?->id;

        return Inertia::render('tasks/index', [
            'projects' => $projects,
            'selectedProjectId' => $projectId,
            'tasks' => $projectId === null ? [] : Task::query()
                ->where('project_id', $projectId)->orderBy('priority')->orderBy('id')
                ->get(['id', 'name', 'priority', 'created_at', 'updated_at']),
        ]);
    }

    public function store(TaskRequest $request, Project $project): RedirectResponse
    {
        DB::transaction(function () use ($request, $project): void {
            Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $project->tasks()->create([
                'name' => $request->validated('name'),
                'priority' => ((int) $project->tasks()->max('priority')) + 1,
            ]);
        }, 3);

        return to_route('home', ['project' => $project->id]);
    }

    public function update(TaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        $task->update($request->safe()->only('name'));

        return to_route('home', ['project' => $project->id]);
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        DB::transaction(function () use ($project, $task): void {
            Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $task = $project->tasks()->findOrFail($task->id);
            $priority = $task->priority;
            $task->delete();
            $project->tasks()->where('priority', '>', $priority)->decrement('priority');
        }, 3);

        return to_route('home', ['project' => $project->id]);
    }
}
