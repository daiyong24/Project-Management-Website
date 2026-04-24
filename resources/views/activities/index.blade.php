@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/activities.css') }}">
<style>
.activities-grid-row-nested { padding:.75rem 1rem; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
.activities-grid-row-nested details summary { cursor:pointer; color:#4b5563; font-size:.85rem; list-style:none; }
.activities-grid-row-nested details summary::-webkit-details-marker { display:none; }
.activities-grid-row-nested details[open] summary { margin-bottom:.5rem; color:#111827; font-weight:500; }
.nested-child-row { display:flex; justify-content:space-between; align-items:center; padding:.4rem .5rem; font-size:.85rem; border-bottom:1px dashed #e5e7eb; }
.nested-child-row:last-of-type { border-bottom:none; }
.nested-child-row .meta { color:#9ca3af; font-size:.78rem; }
.nested-child-row.nested-status { color:#1d4ed8; }
.nested-child-row.nested-comment { color:#374151; }
.nested-comment-form { display:flex; gap:.5rem; margin-top:.5rem; padding-top:.5rem; border-top:1px solid #e5e7eb; }
.nested-comment-form textarea { flex:1; padding:.4rem .5rem; border:1px solid #d1d5db; border-radius:6px; font-family:inherit; font-size:.85rem; resize:vertical; }
.inline-remove-btn { background:none; border:none; color:#dc2626; cursor:pointer; font-size:.9rem; padding:0 .25rem; line-height:1; }
.status-select-inline { padding:.25rem .5rem; border:1px solid #d1d5db; border-radius:6px; font-size:.8rem; background:#fff; }
.filter-actions { display:flex; gap:.5rem; align-items:flex-end; }
</style>

<div class="activities-wrapper">
    <div class="activities-header">
        <div class="activities-header-top">
            <a href="{{ url()->previous() }}" class="activities-back-btn">Back</a>
            <div class="header-actions">
                <span class="role-chip role-{{ $currentRole }}">{{ ucfirst($currentRole) }}</span>
                @if ($rolePermissions['actions']['createActivity'])
                    <a href="{{ route('activities.create') }}" class="action-btn primary-btn">+ New Activity</a>
                @endif
            </div>
        </div>

        <div class="activities-header-layout">
            <div class="activities-header-text">
                <p class="activities-kicker">Logged in as {{ $currentUserName }}</p>
                <h1>Activity Grid</h1>
                <p class="activities-subtitle">{{ $rolePermissions['summary'] }}</p>
            </div>

            <div class="activities-stats" aria-label="Activity summary">
                <div class="stat-box">
                    <span>{{ $activityStats['total'] }}</span>
                    <small>Total</small>
                </div>
                <div class="stat-box">
                    <span>{{ $activityStats['pending'] }}</span>
                    <small>Pending</small>
                </div>
                <div class="stat-box">
                    <span>{{ $activityStats['inProgress'] }}</span>
                    <small>In Progress</small>
                </div>
                <div class="stat-box">
                    <span>{{ $activityStats['completed'] }}</span>
                    <small>Completed</small>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('activities.index') }}" class="activities-filter-card" aria-label="Activity filters">
        <div class="filter-field search-field">
            <label for="activity-search">Search</label>
            <input id="activity-search" type="search" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Title, description, task, comment">
        </div>

        <div class="filter-field">
            <label for="activity-project-filter">Project</label>
            <select id="activity-project-filter" name="project_id">
                <option value="">All projects</option>
                @foreach ($projectOptions as $project)
                    <option value="{{ $project->id }}" {{ (string)($filters['project_id'] ?? '') === (string)$project->id ? 'selected' : '' }}>{{ $project->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="activity-status-filter">Status</label>
            <select id="activity-status-filter" name="status">
                <option value="">All statuses</option>
                @foreach ($activityStatuses as $status)
                    <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="activity-assignee-filter">Assignee</label>
            <select id="activity-assignee-filter" name="assignee_id">
                <option value="">Anyone</option>
                @foreach ($assigneeOptions as $user)
                    <option value="{{ $user->id }}" {{ (string)($filters['assignee_id'] ?? '') === (string)$user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field filter-actions">
            <button type="submit" class="action-btn primary-btn">Apply</button>
            <a href="{{ route('activities.index', ['reset' => 1]) }}" class="action-btn secondary-btn">Clear</a>
        </div>
    </form>

    @if (session('activity_success'))
        <div class="activity-alert success-alert">{{ session('activity_success') }}</div>
    @endif

    @if ($errors->any())
        <div class="activity-alert error-alert">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="activities-grid-card">
        <div class="activities-grid-toolbar">
            <div>
                <span class="grid-view-label">Grid view</span>
                <span class="grid-view-count">{{ $activityStats['total'] }} records</span>
            </div>
        </div>

        <div class="activities-grid-table">
            <div class="activities-grid-head">
                <div class="col-no">No</div>
                <div class="col-project">Project</div>
                <div class="col-task">Task</div>
                <div class="col-type">Type</div>
                <div class="col-person">Created By</div>
                <div class="col-person">Assigned To</div>
                <div class="col-status">Status</div>
                <div class="col-date">Created At</div>
                <div class="col-comment">Activity / Comment</div>
                <div class="col-actions">Actions</div>
            </div>

            @forelse ($activities as $index => $activity)
                @php
                    $typeClass = 'badge-assignment';
                    $statusClass = 'status-default';
                    if ($activity['status'] === 'Pending') $statusClass = 'status-pending';
                    elseif ($activity['status'] === 'In Progress') $statusClass = 'status-progress';
                    elseif ($activity['status'] === 'Completed') $statusClass = 'status-completed';

                    $canUpdateStatus = $rolePermissions['actions']['updateStatus'];
                    $canComment = $rolePermissions['actions']['addComment'];
                    $isTaskCreator = $activity['createdById'] === $currentUserId;
                    $canEditActivity = ($rolePermissions['actions']['editActivity'] ?? false) && ($currentRole === 'admin' || $isTaskCreator);
                    $canDeleteActivity = $rolePermissions['actions']['deleteActivity'] && ($currentRole === 'admin' || $isTaskCreator);

                    $childCount = count($activity['comments']) + count($activity['status_history']);
                @endphp

                <div class="activities-grid-row">
                    <div class="grid-cell col-no">{{ $index + 1 }}</div>

                    <div class="grid-cell col-project">
                        <span class="project-name">{{ $activity['project'] }}</span>
                    </div>

                    <div class="grid-cell col-task">
                        <span>{{ $activity['task'] }}</span>
                        @if ($activity['dueDate'])
                            <small>Due {{ $activity['dueDate'] }}</small>
                        @endif
                    </div>

                    <div class="grid-cell col-type">
                        <span class="type-badge {{ $typeClass }}">{{ $activity['type'] }}</span>
                    </div>

                    <div class="grid-cell col-person">
                        <div class="person-cell">
                            <span class="person-avatar">{{ strtoupper(substr($activity['createdBy'], 0, 1)) }}</span>
                            <span>{{ $activity['createdBy'] }}</span>
                        </div>
                    </div>

                    <div class="grid-cell col-person">
                        <div class="person-cell">
                            <span class="person-avatar alt-avatar">{{ strtoupper(substr($activity['assignedTo'] ?: '—', 0, 1)) }}</span>
                            <span>{{ $activity['assignedTo'] ?: '—' }}</span>
                        </div>
                    </div>

                    <div class="grid-cell col-status">
                        @if ($canUpdateStatus)
                            <form method="POST" action="{{ route('activities.status', $activity['id']) }}" style="margin:0;">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="status-select-inline" onchange="this.form.submit()">
                                    @foreach (['Pending', 'In Progress', 'Completed'] as $s)
                                        <option value="{{ $s }}" @if($activity['status'] === $s) selected @endif>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="status-pill {{ $statusClass }}">{{ $activity['status'] }}</span>
                        @endif
                    </div>

                    <div class="grid-cell col-date">{{ $activity['createdAt'] }}</div>

                    <div class="grid-cell col-comment">
                        <div class="comment-title">{{ $activity['title'] }}</div>
                        <div class="comment-desc">{{ $activity['description'] }}</div>
                    </div>

                    <div class="grid-cell col-actions">
                        <div class="row-actions">
                            @if ($canEditActivity)
                                <a href="{{ route('activities.edit', $activity['id']) }}" class="action-btn secondary-btn">Edit</a>
                            @endif
                            @if ($canDeleteActivity)
                                <form method="POST" action="{{ route('activities.destroy', $activity['id']) }}" onsubmit="return confirm('Delete this activity and all its comments?')" style="display:inline;margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn danger-btn">Remove</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($childCount > 0 || $canComment)
                    <div class="activities-grid-row-nested">
                        <details data-activity-id="{{ $activity['id'] }}">
                            <summary>
                                💬 {{ count($activity['comments']) }} {{ count($activity['comments']) === 1 ? 'comment' : 'comments' }}
                                · 🔄 {{ count($activity['status_history']) }} status {{ count($activity['status_history']) === 1 ? 'update' : 'updates' }}
                            </summary>

                            @foreach ($activity['status_history'] as $sh)
                                <div class="nested-child-row nested-status">
                                    <span>🔄 <strong>{{ $sh['author'] }}</strong> changed status to <strong>{{ $sh['status'] }}</strong></span>
                                    <span class="meta">{{ $sh['createdAt'] }}</span>
                                </div>
                            @endforeach

                            @foreach ($activity['comments'] as $c)
                                <div class="nested-child-row nested-comment">
                                    <span>💬 <strong>{{ $c['author'] }}:</strong> {{ $c['note'] }}</span>
                                    <span class="meta">
                                        {{ $c['createdAt'] }}
                                        @if ($rolePermissions['actions']['deleteComment'] && ($currentRole === 'admin' || $c['author_id'] === $currentUserId))
                                            <form method="POST" action="{{ route('activities.destroy', $c['id']) }}" onsubmit="return confirm('Delete this comment?')" style="display:inline;margin:0 0 0 .25rem;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-remove-btn" title="Delete">×</button>
                                            </form>
                                        @endif
                                    </span>
                                </div>
                            @endforeach

                            @if ($canComment)
                                <form method="POST" action="{{ route('activities.comments.store', $activity['id']) }}" class="nested-comment-form">
                                    @csrf
                                    <textarea name="note" rows="1" placeholder="Add a comment..." required></textarea>
                                    <button type="submit" class="action-btn primary-btn">Comment</button>
                                </form>
                            @endif
                        </details>
                    </div>
                @endif
            @empty
                <div class="empty-state">
                    <h2>No activities</h2>
                    <p>No activities visible under your current role and filters.</p>
                </div>
            @endforelse
        </div>

        @if ($activities->hasPages())
            <div class="grid-pagination server-pagination">
                <span class="pagination-summary">
                    Page {{ $activities->currentPage() }} of {{ $activities->lastPage() }} &mdash; {{ $activities->total() }} total
                </span>
                <div class="pagination-actions">
                    @if ($activities->onFirstPage())
                        <span class="action-btn secondary-btn disabled">Previous</span>
                    @else
                        <a href="{{ $activities->previousPageUrl() }}" class="action-btn secondary-btn">Previous</a>
                    @endif

                    @if ($activities->hasMorePages())
                        <a href="{{ $activities->nextPageUrl() }}" class="action-btn secondary-btn">Next</a>
                    @else
                        <span class="action-btn secondary-btn disabled">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('details[data-activity-id]').forEach(function (d) {
        var key = 'activity-details-' + d.dataset.activityId;
        if (sessionStorage.getItem(key) === 'open') {
            d.open = true;
        }
        d.addEventListener('toggle', function () {
            sessionStorage.setItem(key, d.open ? 'open' : 'closed');
        });
    });
});
</script>
@endsection
