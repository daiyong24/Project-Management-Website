<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCommentRequest;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\Activity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Gate;

class ActivityController extends Controller
{
    private const FILTER_KEYS = ['keyword', 'project_id', 'status', 'assignee_id', 'from', 'to'];
    private const FILTER_COOKIE = 'activity_filter_prefs';

    public function index(Request $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('viewAny', Activity::class);
        }

        $currentUserName = $currentUser->name;
        $currentUserId = $currentUser->id;
        $rolePermissions = $this->rolePermissions($currentRole);

        $filters = $this->resolveFilters($request);

        $query = Activity::tasks()
            ->with([
                'project',
                'user',
                'assignedTo',
                'comments.user',
                'statusUpdates.user',
            ]);

        if ($currentRole === 'author') {
            $projectIds = Project::where('created_by', $currentUser->id)->pluck('id');
            $query->whereIn('project_id', $projectIds);
        } elseif ($currentRole === 'user') {
            $query->where(function ($inner) use ($currentUser) {
                $inner->where('user_id', $currentUser->id)
                    ->orWhere('assigned_to_user_id', $currentUser->id);
            });
        }

        $query->filter($filters);

        $statsQuery = clone $query;

        $paginator = $query->latest()->paginate(10)->withQueryString();
        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($task) => $this->presentTask($task))
        );

        $activities = $paginator;

        $projectOptions = $this->scopedProjects($currentRole, $currentUser);
        $assigneeOptions = User::query()
            ->whereIn('role', ['user', 'author'])
            ->orderBy('name')
            ->get(['id', 'name']);
        $activityStatuses = collect(['Pending', 'In Progress', 'Completed']);

        $activityStats = [
            'total' => $paginator->total(),
            'pending' => (clone $statsQuery)->where('status', 'Pending')->count(),
            'inProgress' => (clone $statsQuery)->where('status', 'In Progress')->count(),
            'completed' => (clone $statsQuery)->where('status', 'Completed')->count(),
        ];

        Cookie::queue(self::FILTER_COOKIE, json_encode($filters), 60 * 24 * 30);

        return view('activities.index', compact(
            'activities',
            'currentRole',
            'currentUserName',
            'currentUserId',
            'rolePermissions',
            'projectOptions',
            'assigneeOptions',
            'activityStatuses',
            'activityStats',
            'filters'
        ));
    }

    public function create(Request $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('create', Activity::class);
        }

        $projectOptions = $this->scopedProjects($currentRole, $currentUser);
        $userOptions = User::query()->orderBy('name')->get(['id', 'name']);
        $statuses = ['Pending', 'In Progress', 'Completed'];

        return view('activities.create', compact(
            'projectOptions',
            'userOptions',
            'statuses',
            'currentRole'
        ));
    }

    public function store(StoreActivityRequest $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('create', Activity::class);
        }

        $validated = $request->validated();
        $validated['user_id'] = $currentUser->id;
        $validated['type'] = 'Assignment';
        $validated['status'] = $validated['status'] ?? 'Pending';
        $validated['is_completed'] = $validated['status'] === 'Completed';

        Activity::create($validated);

        return redirect()
            ->route('activities.index')
            ->with('activity_success', 'Activity created successfully.');
    }

    public function edit(Activity $activity)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('update', $activity);
        }

        $projectOptions = $this->scopedProjects($currentRole, $currentUser);
        $userOptions = User::query()->orderBy('name')->get(['id', 'name']);

        return view('activities.edit', compact(
            'activity',
            'projectOptions',
            'userOptions',
            'currentRole'
        ));
    }

    public function update(Activity $activity, UpdateActivityRequest $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('update', $activity);
        }

        $activity->update($request->validated());

        return redirect()
            ->route('activities.index')
            ->with('activity_success', 'Activity updated successfully.');
    }

    public function addComment(Activity $activity, AddCommentRequest $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        abort_unless($activity->parent_activity_id === null, 404);

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('addComment', $activity);
        }

        Activity::create([
            'project_id' => $activity->project_id,
            'user_id' => $currentUser->id,
            'parent_activity_id' => $activity->id,
            'title' => 'Comment',
            'description' => $currentUser->name . ' commented on "' . $activity->title . '"',
            'type' => 'Comment',
            'status' => 'Pending',
            'task_name' => $activity->task_name,
            'note' => $request->validated()['note'],
        ]);

        return redirect()
            ->route('activities.index')
            ->with('activity_success', 'Comment added.');
    }

    public function updateStatus(Activity $activity, UpdateStatusRequest $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        abort_unless($activity->parent_activity_id === null, 404);

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('updateStatus', $activity);
        }

        $newStatus = $request->validated()['status'];

        if ($activity->status === $newStatus) {
            return redirect()
                ->route('activities.index')
                ->with('activity_success', 'Status unchanged.');
        }

        Activity::create([
            'project_id' => $activity->project_id,
            'user_id' => $currentUser->id,
            'parent_activity_id' => $activity->id,
            'title' => 'Status Updated',
            'description' => $currentUser->name . ' changed status to ' . $newStatus,
            'type' => 'Status Update',
            'status' => $newStatus,
            'task_name' => $activity->task_name,
        ]);

        $activity->update([
            'status' => $newStatus,
            'is_completed' => $newStatus === 'Completed',
        ]);

        return redirect()
            ->route('activities.index')
            ->with('activity_success', 'Status updated to ' . $newStatus . '.');
    }

    public function destroy(Activity $activity)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        if ($currentUser instanceof User) {
            Gate::forUser($currentUser)->authorize('delete', $activity);
        }

        $activity->delete();

        return redirect()
            ->route('activities.index')
            ->with('activity_success', 'Removed successfully.');
    }

    private function resolveFilters(Request $request): array
    {
        if ($request->boolean('reset')) {
            Cookie::queue(Cookie::forget(self::FILTER_COOKIE));
            return [];
        }

        if ($request->hasAny(self::FILTER_KEYS)) {
            return array_filter($request->only(self::FILTER_KEYS), fn ($v) => $v !== null && $v !== '');
        }

        $cookie = $request->cookie(self::FILTER_COOKIE);

        if ($cookie) {
            $decoded = json_decode($cookie, true);

            if (is_array($decoded)) {
                return array_intersect_key($decoded, array_flip(self::FILTER_KEYS));
            }
        }

        return [];
    }

    private function scopedProjects(string $role, $user)
    {
        $q = Project::query()->orderBy('title');

        if ($role === 'author') {
            $q->where('created_by', $user->id);
        } elseif ($role === 'user') {
            $q->whereHas('users', fn ($q2) => $q2->where('users.id', $user->id));
        }

        return $q->get(['id', 'title']);
    }

    private function presentTask(Activity $task): array
    {
        $assignedName = optional($task->assignedTo)->name;
        $createdName = optional($task->user)->name;

        $comments = $task->comments->map(fn ($c) => [
            'id' => $c->id,
            'author' => optional($c->user)->name ?? '',
            'author_id' => $c->user_id,
            'note' => $c->note ?? '',
            'createdAt' => optional($c->created_at)->format('Y-m-d h:i A') ?? '',
        ])->all();

        $statusHistory = $task->statusUpdates->map(fn ($s) => [
            'id' => $s->id,
            'author' => optional($s->user)->name ?? '',
            'status' => $s->status,
            'createdAt' => optional($s->created_at)->format('Y-m-d h:i A') ?? '',
        ])->all();

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type,
            'project' => optional($task->project)->title ?? '',
            'project_id' => $task->project_id,
            'task' => $task->task_name ?? '',
            'createdBy' => $createdName ?? '',
            'createdById' => $task->user_id,
            'assignedTo' => $assignedName ?? '',
            'assignedToId' => $task->assigned_to_user_id,
            'status' => $task->status,
            'dueDate' => optional($task->due_date)->format('Y-m-d') ?? '',
            'createdAt' => optional($task->created_at)->format('Y-m-d h:i A') ?? '',
            'note' => $task->note ?? '',
            'comments' => $comments,
            'status_history' => $statusHistory,
        ];
    }

    private function activeUser(): array
    {
        if (Auth::guard('admin')->check()) {
            return ['admin', Auth::guard('admin')->user()];
        }

        if (Auth::guard('author')->check()) {
            return ['author', Auth::guard('author')->user()];
        }

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $role = $user->role ?? 'user';
            return [$role, $user];
        }

        return ['guest', null];
    }

    private function rolePermissions(string $role): array
    {
        $permissions = [
            'admin' => [
                'summary' => 'View all activities, moderate comments, manage system progress.',
                'actions' => [
                    'createActivity' => true,
                    'editActivity' => true,
                    'updateStatus' => true,
                    'addComment' => true,
                    'deleteActivity' => true,
                    'deleteComment' => true,
                ],
            ],
            'author' => [
                'summary' => 'Create and manage activities in your own projects. Review progress and leave comments.',
                'actions' => [
                    'createActivity' => true,
                    'editActivity' => true,
                    'updateStatus' => true,
                    'addComment' => true,
                    'deleteActivity' => true,
                    'deleteComment' => true,
                ],
            ],
            'user' => [
                'summary' => 'View assigned activities, update their status, and add comments.',
                'actions' => [
                    'createActivity' => false,
                    'editActivity' => false,
                    'updateStatus' => true,
                    'addComment' => true,
                    'deleteActivity' => false,
                    'deleteComment' => true,
                ],
            ],
        ];

        return $permissions[$role] ?? $permissions['user'];
    }
}
