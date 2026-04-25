@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/projects.css') }}">

<div class="projects-form-container">
    <div class="form-header">
        <h1>Create New Project</h1>
        <p>Set up a new project and add team members.</p>
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

    <form method="POST" action="{{ route('projects.store') }}">
        @csrf

        <div class="form-group">
            <label for="title">Project Title *</label>
            <input id="title" type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Website Redesign">
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" required placeholder="Describe the project goals and scope...">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
            <label>Team Members</label>
            <div class="users-checkboxes">
                @foreach ($users as $user)
                    <div class="user-checkbox">
                        @php
                            $isCreator = $user->id === auth()->id();
                        @endphp

                        <input
                            id="user_{{ $user->id }}"
                            type="checkbox"
                            name="users[]"
                            value="{{ $user->id }}"
                            {{ $isCreator ? 'checked disabled' : '' }}
                            {{ (!$isCreator && is_array(old('users')) && in_array($user->id, old('users'))) ? 'checked' : '' }}
                        >

                        @if ($isCreator)
                            <!-- ensure creator is always submitted -->
                            <input type="hidden" name="users[]" value="{{ $user->id }}">
                        @endif

                        <label for="user_{{ $user->id }}">
                            {{ $user->name }} {{ $isCreator ? '(You)' : '' }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Create Project</button>
            <a href="{{ route('projects.index') }}" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
@endsection
