@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/activities.css') }}">

<div class="activities-wrapper">
    <div class="activities-header">
        <div class="activities-header-top">
            <a href="{{ url()->previous() }}" class="activities-back-btn">Back</a>
            <div class="header-actions">
                <span class="role-chip role-{{ $currentRole }}">{{ ucfirst($currentRole) }}</span>
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
                    <span>{{ $activityStats['assignments'] }}</span>
                    <small>Assigned</small>
                </div>
                <div class="stat-box">
                    <span>{{ $activityStats['statusUpdates'] }}</span>
                    <small>Status</small>
                </div>
                <div class="stat-box">
                    <span>{{ $activityStats['comments'] }}</span>
                    <small>Comments</small>
                </div>
            </div>
        </div>
    </div>

    <section class="activities-filter-card" aria-label="Activity filters">
        <div class="filter-field search-field">
            <label for="activity-search">Search</label>
            <input id="activity-search" type="search" placeholder="Project, task, person, comment">
        </div>

        <div class="filter-field">
            <label for="activity-type-filter">Type</label>
            <select id="activity-type-filter">
                <option value="">All types</option>
                @foreach ($activityTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="activity-project-filter">Project</label>
            <select id="activity-project-filter">
                <option value="">All projects</option>
                @foreach ($projects as $project)
                    <option value="{{ $project }}">{{ $project }}</option>
                @endforeach
            </select>
        </div>
    </section>

    @if (session('activity_success'))
        <div class="activity-alert success-alert">{{ session('activity_success') }}</div>
    @endif

    @if ($errors->any())
        <div class="activity-alert error-alert">Please review the form and try again.</div>
    @endif

    <div class="activities-grid-card">
        <div class="activities-grid-toolbar">
            <div>
                <span class="grid-view-label">Grid view</span>
                <span class="grid-view-count" data-grid-count>{{ $activityStats['total'] }} records</span>
            </div>

            <div class="grid-toolbar-actions">
                <div class="toolbar-control">
                    <label for="activity-sort">Sort</label>
                    <select id="activity-sort">
                        <option value="date_desc">Newest first</option>
                        <option value="date_asc">Oldest first</option>
                        <option value="project_asc">Project A-Z</option>
                        <option value="status_asc">Status</option>
                    </select>
                </div>

                <div class="toolbar-control">
                    <label for="activity-page-size">Rows</label>
                    <select id="activity-page-size">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                    </select>
                </div>
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
                    $typeClass = 'badge-default';

                    if ($activity['type'] === 'Assignment') {
                        $typeClass = 'badge-assignment';
                    } elseif ($activity['type'] === 'Status Update') {
                        $typeClass = 'badge-status';
                    } elseif ($activity['type'] === 'Comment') {
                        $typeClass = 'badge-comment';
                    }

                    $statusClass = 'status-default';

                    if ($activity['status'] === 'Pending') {
                        $statusClass = 'status-pending';
                    } elseif ($activity['status'] === 'In Progress') {
                        $statusClass = 'status-progress';
                    } elseif ($activity['status'] === 'Completed') {
                        $statusClass = 'status-completed';
                    }

                    $searchText = strtolower(implode(' ', [
                        $activity['title'],
                        $activity['description'],
                        $activity['type'],
                        $activity['project'],
                        $activity['task'],
                        $activity['createdBy'],
                        $activity['assignedTo'],
                        $activity['status'],
                        $activity['comment'],
                    ]));

                    $createdTimestamp = \Carbon\Carbon::parse($activity['createdAt'])->timestamp;
                @endphp

                <div
                    class="activities-grid-row"
                    data-activity-card
                    data-search="{{ $searchText }}"
                    data-type="{{ $activity['type'] }}"
                    data-project="{{ $activity['project'] }}"
                    data-title="{{ $activity['title'] }}"
                    data-description="{{ $activity['description'] }}"
                    data-task="{{ $activity['task'] }}"
                    data-created-by="{{ $activity['createdBy'] }}"
                    data-assigned-to="{{ $activity['assignedTo'] }}"
                    data-status="{{ $activity['status'] }}"
                    data-created-at="{{ $activity['createdAt'] }}"
                    data-comment="{{ $activity['comment'] }}"
                    data-due-date="{{ $activity['dueDate'] }}"
                    data-sort-date="{{ $createdTimestamp }}"
                >
                    <div class="grid-cell col-no" data-row-number>{{ $index + 1 }}</div>

                    <div class="grid-cell col-project">
                        <span class="project-name" data-row-project>{{ $activity['project'] }}</span>
                    </div>

                    <div class="grid-cell col-task">
                        <span data-row-task>{{ $activity['task'] }}</span>
                        <small>Due <span data-row-due-date>{{ $activity['dueDate'] }}</span></small>
                    </div>

                    <div class="grid-cell col-type">
                        <span class="type-badge {{ $typeClass }}" data-row-type-badge>{{ $activity['type'] }}</span>
                    </div>

                    <div class="grid-cell col-person">
                        <div class="person-cell">
                            <span class="person-avatar">{{ strtoupper(substr($activity['createdBy'], 0, 1)) }}</span>
                            <span data-row-created-by>{{ $activity['createdBy'] }}</span>
                        </div>
                    </div>

                    <div class="grid-cell col-person">
                        <div class="person-cell">
                            <span class="person-avatar alt-avatar" data-row-assigned-avatar>{{ strtoupper(substr($activity['assignedTo'], 0, 1)) }}</span>
                            <span data-row-assigned-to>{{ $activity['assignedTo'] }}</span>
                        </div>
                    </div>

                    <div class="grid-cell col-status">
                        <span class="status-pill {{ $statusClass }}" data-row-status-pill>{{ $activity['status'] }}</span>
                    </div>

                    <div class="grid-cell col-date" data-row-created-at>{{ $activity['createdAt'] }}</div>

                    <div class="grid-cell col-comment">
                        <div class="comment-title" data-row-title>{{ $activity['title'] }}</div>
                        <div class="comment-desc" data-row-description>{{ $activity['description'] }}</div>

                        @if (!empty($activity['comment']))
                            <div class="comment-note" data-row-comment>{{ $activity['comment'] }}</div>
                        @else
                            <div class="comment-note is-empty" data-row-comment hidden></div>
                        @endif
                    </div>

                    <div class="grid-cell col-actions">
                        <div class="row-actions">
                            <button type="button" class="action-btn neutral-btn" data-row-action="view">View</button>

                            @if ($rolePermissions['actions']['addComment'])
                                <button type="button" class="action-btn primary-btn" data-row-action="comment">Comment</button>
                            @endif

                            @if ($rolePermissions['actions']['removeComment'] && $activity['type'] === 'Comment')
                                <button type="button" class="action-btn danger-btn" data-row-action="remove">Remove</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <h2>No activities available</h2>
                    <p>Your current role can only see activities connected to its permitted projects and tasks.</p>
                </div>
            @endforelse
        </div>

        <div class="empty-state" id="activity-filter-empty" hidden>
            <h2>No matching activities</h2>
            <p>Try a different search term, type, or project filter.</p>
        </div>

        <div class="grid-pagination" id="activity-pagination" hidden>
            <span class="pagination-summary" data-pagination-summary>Showing 0-0</span>
            <div class="pagination-actions">
                <button type="button" class="action-btn secondary-btn" id="activity-prev-page">Previous</button>
                <button type="button" class="action-btn secondary-btn" id="activity-next-page">Next</button>
            </div>
        </div>
    </div>
</div>

<div class="activity-modal-backdrop" id="activity-action-modal-backdrop" hidden>
    <div class="activity-modal action-modal" role="dialog" aria-modal="true" aria-labelledby="activity-action-modal-title">
        <div class="activity-modal-header">
            <div>
                <h2 id="activity-action-modal-title">Activity Details</h2>
                <p id="activity-action-modal-subtitle">Review the activity details in a read-only frontend view.</p>
            </div>
            <button type="button" class="modal-close-btn" data-close-action-modal aria-label="Close">×</button>
        </div>

        <div class="activity-form">
            <div class="action-modal-grid">
                <div class="action-summary-card">
                    <span class="summary-label">Title</span>
                    <strong id="action-summary-title">-</strong>
                </div>
                <div class="action-summary-card">
                    <span class="summary-label">Project</span>
                    <strong id="action-summary-project">-</strong>
                </div>
                <div class="action-summary-card">
                    <span class="summary-label">Task</span>
                    <strong id="action-summary-task">-</strong>
                </div>
                <div class="action-summary-card">
                    <span class="summary-label">Created By</span>
                    <strong id="action-summary-created-by">-</strong>
                </div>
            </div>

            <div class="modal-form-grid action-form-grid">
                <div class="filter-field" data-action-field="assigned_to">
                    <label for="action-assigned-to">Assigned To</label>
                    <input id="action-assigned-to" readonly>
                </div>

                <div class="filter-field" data-action-field="status">
                    <label for="action-status">Status</label>
                    <select id="action-status" disabled>
                        @foreach (['Pending', 'In Progress', 'Completed'] as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field" data-action-field="due_date">
                    <label for="action-due-date">Due Date</label>
                    <input id="action-due-date" type="date" readonly>
                </div>

                <div class="filter-field action-description-field" data-action-field="description">
                    <label for="action-description">Description</label>
                    <input id="action-description" readonly>
                </div>
            </div>

            <div class="filter-field full-width-field" data-action-field="comment">
                <label for="action-comment">Comment / Note</label>
                <textarea id="action-comment" rows="4" placeholder="Add your note"></textarea>
            </div>

            <div class="activity-alert info-alert" id="activity-action-feedback" hidden></div>

            <div class="activity-modal-footer">
                <button type="button" class="action-btn secondary-btn" data-close-action-modal>Close</button>
                <button type="button" class="action-btn primary-btn" id="activity-action-save">Apply Draft Change</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/activities.js') }}"></script>
@endsection
