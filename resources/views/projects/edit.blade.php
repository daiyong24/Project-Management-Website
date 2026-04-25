@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/projects.css') }}">

<div class="projects-form-container">
    <div class="form-header">
        <h1>Edit Project</h1>
        <p>Update project details and team members.</p>
    </div>

    @if ($errors->any())
        <div class="error-alert">
            <strong>Please fix the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('projects.update', $project) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="title">Project Title *</label>
            <input id="title" type="text" name="title" value="{{ old('title', $project->title) }}" required placeholder="e.g. Website Redesign">
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" required placeholder="Describe the project goals and scope...">{{ old('description', $project->description) }}</textarea>
        </div>

        <div class="form-group">
            <label>Team Members</label>
            <div class="users-checkboxes">
                @foreach ($users as $user)
                    <div class="user-checkbox">
                        <input id="user_{{ $user->id }}" type="checkbox" name="users[]" value="{{ $user->id }}"
                            {{ (in_array($user->id, $assignedUserIds) || (is_array(old('users')) && in_array($user->id, old('users')))) ? 'checked' : '' }}>
                        <label for="user_{{ $user->id }}">{{ $user->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Save Changes</button>
            <a href="{{ route('projects.index') }}" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
@endsection
