<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        $currentUserName = $currentUser->name;
        $rolePermissions = $this->rolePermissions($currentRole);

        $activities = array_merge(
            $this->sessionActivities(),
            $this->activitiesForDemo($currentUserName, $currentRole)
        );

        if ($currentRole === 'author') {
            $activities = array_values(array_filter($activities, function ($activity) use ($currentUserName) {
                return $activity['owner'] === $currentUserName;
            }));
        }

        if ($currentRole === 'user') {
            $activities = array_values(array_filter($activities, function ($activity) use ($currentUserName) {
                return $activity['assignedTo'] === $currentUserName || $activity['createdBy'] === $currentUserName;
            }));
        }

        $activityTypes = collect($activities)->pluck('type')->unique()->values();
        $projects = collect($activities)->pluck('project')->unique()->values();
        $tasks = collect($activities)->pluck('task')->unique()->values();
        $people = collect($activities)
            ->flatMap(function ($activity) {
                return [$activity['createdBy'], $activity['assignedTo'], $activity['owner']];
            })
            ->filter()
            ->unique()
            ->values();
        $activityStats = [
            'total' => count($activities),
            'assignments' => collect($activities)->where('type', 'Assignment')->count(),
            'statusUpdates' => collect($activities)->where('type', 'Status Update')->count(),
            'comments' => collect($activities)->where('type', 'Comment')->count(),
        ];

        return view('activities.index', compact(
            'activities',
            'currentRole',
            'currentUserName',
            'rolePermissions',
            'activityTypes',
            'projects',
            'tasks',
            'people',
            'activityStats'
        ));
    }

    public function store(Request $request)
    {
        [$currentRole, $currentUser] = $this->activeUser();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        $currentUserName = $currentUser->name;
        $rolePermissions = $this->rolePermissions($currentRole);

        abort_unless($rolePermissions['actions']['createActivity'], 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::in($rolePermissions['activityTypes'])],
            'project' => ['required', 'string', 'max:255'],
            'task' => ['required', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['Pending', 'In Progress', 'Completed'])],
            'due_date' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $activity = $this->buildActivityPayload($validated, $currentRole, $currentUserName);

        $customActivities = $request->session()->get('custom_activities', []);
        array_unshift($customActivities, $activity);
        $request->session()->put('custom_activities', $customActivities);

        return redirect()
            ->route('activities.index')
            ->with('activity_success', 'New activity created successfully.');
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
            return ['user', Auth::guard('web')->user()];
        }

        return ['guest', null];
    }

    private function rolePermissions(string $role): array
    {
        $permissions = [
            'admin' => [
                'summary' => 'View all activity across the system and moderate inappropriate comments without editing activity history.',
                'can' => [
                    'View all activities',
                    'Remove inappropriate comments',
                    'Monitor system progress',
                ],
                'cannot' => [
                    'Manually edit old activity log records',
                ],
                'actions' => [
                    'addComment' => false,
                    'removeComment' => true,
                    'assignTask' => false,
                    'updateStatus' => false,
                    'manageProject' => true,
                    'createActivity' => false,
                ],
                'activityTypes' => [],
            ],
            'author' => [
                'summary' => 'View activity for your own projects and add comments, while keeping activity history read-only.',
                'can' => [
                    'View activity for your own projects',
                    'Add comments',
                ],
                'cannot' => [
                    'Edit another author project',
                    'Manage all users or change system roles',
                    'Manually edit activity logs',
                ],
                'actions' => [
                    'addComment' => true,
                    'removeComment' => false,
                    'assignTask' => false,
                    'updateStatus' => false,
                    'manageProject' => true,
                    'createActivity' => false,
                ],
                'activityTypes' => [],
            ],
            'user' => [
                'summary' => 'View assigned activity and add comments only. Activity records themselves stay read-only.',
                'can' => [
                    'View activities related to assigned tasks',
                    'Add comments',
                    'View allowed project details',
                ],
                'cannot' => [
                    'Create projects or tasks',
                    'Assign tasks or manage members',
                    'Edit or delete activity logs',
                ],
                'actions' => [
                    'addComment' => true,
                    'removeComment' => false,
                    'assignTask' => false,
                    'updateStatus' => false,
                    'manageProject' => false,
                    'createActivity' => false,
                ],
                'activityTypes' => [],
            ],
        ];

        return $permissions[$role];
    }

    private function sessionActivities(): array
    {
        return session('custom_activities', []);
    }

    private function buildActivityPayload(array $validated, string $currentRole, string $currentUserName): array
    {
        $type = $validated['type'];
        $assignedTo = $validated['assigned_to'] ?: $currentUserName;
        $status = $validated['status'] ?: ($type === 'Status Update' ? 'In Progress' : 'Pending');
        $dueDate = $validated['due_date']
            ? Carbon::parse($validated['due_date'])->format('Y-m-d')
            : Carbon::now()->addDays(7)->format('Y-m-d');
        $comment = $validated['comment'] ?? '';
        $owner = $currentRole === 'author' ? $currentUserName : ($currentRole === 'user' ? $assignedTo : $currentUserName);

        $title = $validated['title'];
        $description = $validated['description'];

        if ($type === 'Status Update') {
            $assignedTo = $currentUserName;
        }

        return [
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'project' => $validated['project'],
            'task' => $validated['task'],
            'createdBy' => $currentUserName,
            'assignedTo' => $assignedTo,
            'status' => $status,
            'dueDate' => $dueDate,
            'createdAt' => Carbon::now()->format('Y-m-d h:i A'),
            'comment' => $comment,
            'owner' => $owner,
        ];
    }

    private function activitiesForDemo(string $currentUserName, string $currentRole): array
    {
        $ownerName = $currentRole === 'author' ? $currentUserName : 'Henry';
        $assignedName = $currentRole === 'user' ? $currentUserName : 'Alicia';

        return [
            [
                'title' => 'Task Assigned',
                'description' => $ownerName . ' assigned "Login UI" to ' . $assignedName . '.',
                'type' => 'Assignment',
                'project' => 'Website Redesign',
                'task' => 'Login UI',
                'createdBy' => $ownerName,
                'assignedTo' => $assignedName,
                'status' => 'Pending',
                'dueDate' => '2026-05-10',
                'createdAt' => '2026-04-20 10:30 AM',
                'comment' => 'Please complete before Friday.',
                'owner' => $ownerName,
            ],
            [
                'title' => 'Status Updated',
                'description' => $assignedName . ' changed "Dashboard Cards" status to In Progress.',
                'type' => 'Status Update',
                'project' => 'Website Redesign',
                'task' => 'Dashboard Cards',
                'createdBy' => $assignedName,
                'assignedTo' => $assignedName,
                'status' => 'In Progress',
                'dueDate' => '2026-05-12',
                'createdAt' => '2026-04-20 11:10 AM',
                'comment' => 'Started UI implementation.',
                'owner' => $ownerName,
            ],
            [
                'title' => 'Comment Added',
                'description' => $assignedName . ' added a note on "Login UI".',
                'type' => 'Comment',
                'project' => 'Website Redesign',
                'task' => 'Login UI',
                'createdBy' => $assignedName,
                'assignedTo' => $assignedName,
                'status' => 'Pending',
                'dueDate' => '2026-05-10',
                'createdAt' => '2026-04-20 01:25 PM',
                'comment' => 'Need confirmation on the login button color before finalizing.',
                'owner' => $ownerName,
            ],
            [
                'title' => 'Task Assigned',
                'description' => 'Mira assigned "Sprint Report" to Daniel.',
                'type' => 'Assignment',
                'project' => 'Operations Portal',
                'task' => 'Sprint Report',
                'createdBy' => 'Mira',
                'assignedTo' => 'Daniel',
                'status' => 'Completed',
                'dueDate' => '2026-05-14',
                'createdAt' => '2026-04-21 09:05 AM',
                'comment' => 'Final report is ready for admin review.',
                'owner' => 'Mira',
            ],
        ];
    }
}
