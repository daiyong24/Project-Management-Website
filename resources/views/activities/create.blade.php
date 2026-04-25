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
                <h1>New Activity</h1>
                <p class="activities-subtitle">Create a new activity and assign it to a team member.</p>
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

    <form method="POST" action="{{ route('activities.store') }}" class="activities-filter-card" style="flex-wrap:wrap;">
        @csrf

        <div class="filter-field">
            <label for="project_id">Project</label>
            <select id="project_id" name="project_id" required>
                <option value="">Select project</option>
                @foreach ($projectOptions as $project)
                    <option value="{{ $project->id }}"
                        {{ (string) old('project_id') === (string) $project->id ? 'selected' : '' }}>
                        {{ $project->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="assigned_to_user_id">Assign To</label>
            <select id="assigned_to_user_id" name="assigned_to_user_id" required>
                <option value="">Select user</option>
                @foreach ($userOptions as $u)
                    <option value="{{ $u->id }}"
                        {{ (string) old('assigned_to_user_id') === (string) $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="status">Initial Status</label>
            <select id="status" name="status">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}"
                        {{ old('status', 'Pending') === $status ? 'selected' : '' }}>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="due_date">Due Date</label>
            <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}">
        </div>

        <div class="filter-field" style="flex:1 1 100%;">
            <label for="title">Activity Title</label>
            <input id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. Build Login UI">
        </div>

        <div class="filter-field" style="flex:1 1 100%;">
            <label for="task_name">Short Label</label>
            <input id="task_name" name="task_name" value="{{ old('task_name') }}" placeholder="e.g. Login UI (optional)">
        </div>

        <div class="filter-field" style="flex:1 1 100%;">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" required placeholder="What needs to be done?">{{ old('description') }}</textarea>
        </div>

        <div class="filter-field filter-actions" style="flex:1 1 100%;">
            <button type="submit" class="action-btn primary-btn">Create Activity</button>
            <a href="{{ route('activities.index') }}" class="action-btn secondary-btn">Cancel</a>
        </div>
    </form>
</div>
@endsection
