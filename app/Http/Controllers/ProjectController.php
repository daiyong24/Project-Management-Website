<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * Display a listing of all projects.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('viewAny', Project::class);

        // Get projects based on user role
        if ($user->role === 'admin') {
            // Admin sees all projects
            $projects = Project::with(['creator', 'users', 'activities'])
                ->latest()
                ->paginate(10);
        } else {
            // Users see projects they're assigned to
            $projects = Project::whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                ->with(['creator', 'users', 'activities'])
                ->latest()
                ->paginate(10);
        }

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project.
     */
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

    /**
     * Store a newly created project in storage.
     */
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

        // Create the project
        $project = Project::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'created_by' => $user->id,
        ]);

        // Attach users if provided
        if (!empty($validated['users'])) {
            $project->users()->attach($validated['users']);
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('view', $project);

        $project->load(['creator', 'users', 'activities' => function ($query) {
            $query->latest();
        }]);

        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified project.
     */
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

    /**
     * Update the specified project in storage.
     */
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

        // Sync users
        if (isset($validated['users'])) {
            $project->users()->sync($validated['users']);
        } else {
            $project->users()->detach();
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project from storage.
     */
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
