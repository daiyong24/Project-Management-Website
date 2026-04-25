@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/projects.css') }}">

<div class="projects-container">

    <div class="projects-header">
        <h1>Projects</h1>
        <a href="{{ route('projects.create') }}" class="btn-create">+ New Project</a>
    </div>

    @if(session('success'))
        <div style="margin-bottom:15px; padding:10px; background:#d4edda; color:#155724; border-radius:5px;">
            {{ session('success') }}
        </div>
    @endif

    @if($projects->isEmpty())
        <div class="empty">
            No projects yet. <a href="{{ route('projects.create') }}">Create one</a>.
        </div>
    @else

        <div class="projects-grid">
            @foreach($projects as $project)
                <div class="project-card">

                    <div class="project-title">{{ $project->title }}</div>
                    <div class="project-description">{{ $project->description }}</div>

                    <div class="project-meta">
                        <span>By {{ optional($project->creator)->name ?? 'Unknown' }}</span>
                        <span>{{ $project->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="project-stats">
                        <span>{{ $project->activities->count() }} Activities</span>
                        <span>{{ $project->completion_rate }}%</span>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $project->completion_rate }}%"></div>
                    </div>

                    @if($project->users->isNotEmpty())
                        <div style="margin-top:10px;">
                            @foreach($project->users as $user)
                                <span class="user-badge">{{ $user->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="project-actions">

                        @if(auth()->id() === $project->created_by || auth()->user()->role === 'admin')
                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-edit">Edit</a>

                            <form method="POST" action="{{ route('projects.destroy', $project) }}" style="flex:1;" onsubmit="return confirm('Delete this project?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-delete" style="width:100%">Delete</button>
                            </form>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

        <div style="margin-top: 25px;">
            {{ $projects->links() }}
        </div>

    @endif
</div>
@endsection