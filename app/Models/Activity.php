<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'parent_activity_id',
        'title',
        'description',
        'type',
        'status',
        'task_name',
        'assigned_to_user_id',
        'due_date',
        'note',
        'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function parent()
    {
        return $this->belongsTo(Activity::class, 'parent_activity_id');
    }

    public function children()
    {
        return $this->hasMany(Activity::class, 'parent_activity_id')->oldest();
    }

    public function comments()
    {
        return $this->children()->where('type', 'Comment');
    }

    public function statusUpdates()
    {
        return $this->children()->where('type', 'Status Update');
    }

    public function scopeTasks($query)
    {
        return $query->whereNull('parent_activity_id');
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['keyword'] ?? null, function ($q, $keyword) {
                $like = '%' . $keyword . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('task_name', 'like', $like)
                        ->orWhere('note', 'like', $like)
                        ->orWhereHas('children', function ($childQ) use ($like) {
                            $childQ->where('note', 'like', $like)
                                ->orWhere('description', 'like', $like);
                        });
                });
            })
            ->when($filters['project_id'] ?? null, fn ($q, $v) => $q->where('project_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['assignee_id'] ?? null, fn ($q, $v) => $q->where('assigned_to_user_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }
}
