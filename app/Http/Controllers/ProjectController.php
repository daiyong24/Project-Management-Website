<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('viewAny', Project::class);

        if ($user->role === 'admin') {
            $projects = Project::with(['creator', 'users', 'activities'])
                ->latest()
                ->paginate(10);
        } else {
            $projects = Project::whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                ->with(['creator', 'users', 'activities'])
                ->latest()
                ->paginate(10);
        }

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('create', Project::class);

        $users = User::where('role', '!=', 'admin')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('projects.create', compact('users'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('create', Project::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'users' => ['nullable', 'array'],
            'users.*' => ['exists:users,id'],
        ], [
            'title.required' => 'The project title is required.',
            'description.required' => 'The project description is required.',
            'users.*.exists' => 'One or more selected users do not exist.',
        ]);

        $project = Project::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'created_by' => $user->id,
        ]);

        if (!empty($validated['users'])) {
            $project->users()->attach($validated['users']);
        }

        return redirect()->route('projects.index', $project)
            ->with('success', 'Project created successfully.');
    }

    public function edit(Project $project)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $project);

        $project->load('users');

        $users = User::where('role', '!=', 'admin')
            ->orderBy('name')
            ->get(['id', 'name']);

        $assignedUserIds = $project->users->pluck('id')->toArray();

        return view('projects.edit', compact('project', 'users', 'assignedUserIds'));
    }

    public function update(Request $request, Project $project)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'users' => ['nullable', 'array'],
            'users.*' => ['exists:users,id'],
        ], [
            'title.required' => 'The project title is required.',
            'description.required' => 'The project description is required.',
            'users.*.exists' => 'One or more selected users do not exist.',
        ]);

        $project->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        if (isset($validated['users'])) {
            $project->users()->sync($validated['users']);
        } else {
            $project->users()->detach();
        }

        return redirect()->route('projects.index', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
