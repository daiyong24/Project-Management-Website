<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ActivityDemoSeeder extends Seeder
{
    public function run()
    {
        $henry = User::firstOrCreate(
            ['email' => 'henry@example.com'],
            ['name' => 'Henry', 'password' => Hash::make('password'), 'role' => 'admin']
        );
        $alicia = User::firstOrCreate(
            ['email' => 'alicia@example.com'],
            ['name' => 'Alicia', 'password' => Hash::make('password'), 'role' => 'user']
        );
        $mira = User::firstOrCreate(
            ['email' => 'mira@example.com'],
            ['name' => 'Mira', 'password' => Hash::make('password'), 'role' => 'user']
        );
        $daniel = User::firstOrCreate(
            ['email' => 'daniel@example.com'],
            ['name' => 'Daniel', 'password' => Hash::make('password'), 'role' => 'user']
        );
        $ben = User::firstOrCreate(
            ['email' => 'ben@example.com'],
            ['name' => 'Ben', 'password' => Hash::make('password'), 'role' => 'user']
        );

        $website = Project::create([
            'title' => 'Website Redesign',
            'description' => 'Rebuild the marketing website from scratch.',
            'created_by' => $alicia->id,
        ]);
        $website->users()->sync([$alicia->id, $daniel->id, $ben->id]);

        $ops = Project::create([
            'title' => 'Operations Portal',
            'description' => 'Internal dashboard for operations team.',
            'created_by' => $mira->id,
        ]);
        $ops->users()->sync([$mira->id, $daniel->id]);

        $loginTask = $this->makeTask([
            'project_id' => $website->id,
            'user_id' => $alicia->id,
            'title' => 'Build Login UI',
            'description' => 'Login page mockup and implementation based on the provided Figma.',
            'status' => 'In Progress',
            'task_name' => 'Login UI',
            'assigned_to_user_id' => $daniel->id,
            'due_date' => '2026-05-10',
        ]);
        $this->makeStatusUpdate($loginTask, $daniel, 'In Progress');
        $this->makeComment($loginTask, $alicia, 'Please finish before Friday.');
        $this->makeComment($loginTask, $daniel, 'Starting today. Will follow the Figma closely.');

        $dashTask = $this->makeTask([
            'project_id' => $website->id,
            'user_id' => $alicia->id,
            'title' => 'Dashboard Cards',
            'description' => 'Create summary cards for the dashboard page.',
            'status' => 'Pending',
            'task_name' => 'Dashboard Cards',
            'assigned_to_user_id' => $daniel->id,
            'due_date' => '2026-05-12',
        ]);
        $this->makeComment($dashTask, $alicia, 'Include pending, in-progress, and completed counts.');

        $heroTask = $this->makeTask([
            'project_id' => $website->id,
            'user_id' => $alicia->id,
            'title' => 'Home Hero Section',
            'description' => 'Design and build the home page hero block.',
            'status' => 'Completed',
            'task_name' => 'Home Hero',
            'assigned_to_user_id' => $ben->id,
            'due_date' => '2026-04-28',
            'is_completed' => true,
        ]);
        $this->makeStatusUpdate($heroTask, $ben, 'In Progress');
        $this->makeStatusUpdate($heroTask, $ben, 'Completed');
        $this->makeComment($heroTask, $ben, 'Published to staging for review.');
        $this->makeComment($heroTask, $alicia, 'Looks great. Approved.');

        $reportTask = $this->makeTask([
            'project_id' => $ops->id,
            'user_id' => $mira->id,
            'title' => 'Sprint Report',
            'description' => 'Compile weekly sprint summary for leadership.',
            'status' => 'Completed',
            'task_name' => 'Sprint Report',
            'assigned_to_user_id' => $daniel->id,
            'due_date' => '2026-05-14',
            'is_completed' => true,
        ]);
        $this->makeStatusUpdate($reportTask, $daniel, 'Completed');
        $this->makeComment($reportTask, $daniel, 'Final report attached in shared drive.');
        $this->makeComment($reportTask, $mira, 'Thanks, forwarding to admin.');

        $exportTask = $this->makeTask([
            'project_id' => $ops->id,
            'user_id' => $mira->id,
            'title' => 'Data Export Tool',
            'description' => 'Build an export tool for CSV + JSON downloads.',
            'status' => 'Pending',
            'task_name' => 'Data Export',
            'assigned_to_user_id' => $daniel->id,
            'due_date' => '2026-05-20',
        ]);
    }

    private function makeTask(array $attrs): Activity
    {
        return Activity::create(array_merge([
            'type' => 'Assignment',
            'status' => 'Pending',
            'is_completed' => false,
        ], $attrs));
    }

    private function makeComment(Activity $task, User $author, string $note): Activity
    {
        return Activity::create([
            'project_id' => $task->project_id,
            'user_id' => $author->id,
            'parent_activity_id' => $task->id,
            'title' => 'Comment',
            'description' => $author->name . ' commented on "' . $task->title . '"',
            'type' => 'Comment',
            'status' => 'Pending',
            'task_name' => $task->task_name,
            'note' => $note,
        ]);
    }

    private function makeStatusUpdate(Activity $task, User $author, string $newStatus): Activity
    {
        return Activity::create([
            'project_id' => $task->project_id,
            'user_id' => $author->id,
            'parent_activity_id' => $task->id,
            'title' => 'Status Updated',
            'description' => $author->name . ' changed status to ' . $newStatus,
            'type' => 'Status Update',
            'status' => $newStatus,
            'task_name' => $task->task_name,
        ]);
    }
}
