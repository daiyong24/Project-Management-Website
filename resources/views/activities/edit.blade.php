@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/activities.css') }}">

<div class="activities-wrapper">
    <div class="activities-header">
        <div class="activities-header-top">
            <a href="{{ route('activities.index') }}" class="activities-back-btn">Back</a>
            <div class="header-actions">
                <span class="role-chip role-{{ $currentRole }}">{{ ucfirst($currentRole) }}</span>
            </div>
        </div>

        <div class="activities-header-layout">
            <div class="activities-header-text">
                <h1>Edit Activity</h1>
                <p class="activities-subtitle">Update activity details. Status and comments are managed separately on the main page.</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="activity-alert error-alert">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('activities.update', $activity->id) }}" class="activities-filter-card" style="flex-wrap:wrap;">
        @csrf
        @method('PUT')

        <div class="filter-field">
            <label for="project_id">Project</label>
            <select id="project_id" name="project_id" required>
                <option value="">Select project</option>
                @foreach ($projectOptions as $project)
                    <option value="{{ $project->id }}"
                        {{ (string) old('project_id', $activity->project_id) === (string) $project->id ? 'selected' : '' }}>
                        {{ $project->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="assigned_to_user_id">Assigned To</label>
            <select id="assigned_to_user_id" name="assigned_to_user_id" required>
                <option value="">Select user</option>
                @foreach ($userOptions as $u)
                    <option value="{{ $u->id }}"
                        {{ (string) old('assigned_to_user_id', $activity->assigned_to_user_id) === (string) $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="due_date">Due Date</label>
            <input id="due_date" type="date" name="due_date"
                value="{{ old('due_date', optional($activity->due_date)->format('Y-m-d')) }}">
        </div>

        <div class="filter-field" style="flex:1 1 100%;">
            <label for="title">Activity Title</label>
            <input id="title" name="title" required
                value="{{ old('title', $activity->title) }}">
        </div>

        <div class="filter-field" style="flex:1 1 100%;">
            <label for="task_name">Short Label</label>
            <input id="task_name" name="task_name"
                value="{{ old('task_name', $activity->task_name) }}">
        </div>

        <div class="filter-field" style="flex:1 1 100%;">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" required>{{ old('description', $activity->description) }}</textarea>
        </div>

        <div class="filter-field filter-actions" style="flex:1 1 100%;">
            <button type="submit" class="action-btn primary-btn">Save Changes</button>
            <a href="{{ route('activities.index') }}" class="action-btn secondary-btn">Cancel</a>
        </div>
    </form>
</div>
@endsection
