<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $project = Project::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:projects,name'],
        ]));

        return to_route('home', ['project' => $project->id]);
    }
}
