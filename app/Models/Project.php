<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function getCompletionRateAttribute(): int
    {
        $total = $this->activities()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->activities()->where('status', 'Completed')->count();

        return (int) round(($done / $total) * 100);
    }
}
