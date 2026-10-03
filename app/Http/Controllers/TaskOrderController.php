<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderTasksRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskOrderController extends Controller
{
    public function __invoke(ReorderTasksRequest $request, Project $project): RedirectResponse
    {
        DB::transaction(function () use ($request, $project): void {
            Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $tasks = $project->tasks()->get()->keyBy('id');
            /** @var list<int|string> $submittedIds */
            $submittedIds = $request->validated('task_ids');
            $taskIds = array_map(intval(...), $submittedIds);

            if (count($taskIds) !== $tasks->count() || array_diff($taskIds, $tasks->modelKeys()) !== []) {
                throw ValidationException::withMessages([
                    'task_ids' => 'The task list has changed. Refresh the page and try again.',
                ]);
            }

            foreach ($taskIds as $position => $taskId) {
                $tasks[$taskId]->update(['priority' => $position + 1]);
            }
        }, 3);

        return to_route('home', ['project' => $project->id]);
    }
}
