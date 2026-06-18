<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'teacher_id', 'material_link_id', 'date', 'teaching_minutes', 'notes', 'learning_journal'])]
class AttendanceBatch extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'teaching_minutes' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function materialLink(): BelongsTo
    {
        return $this->belongsTo(MaterialLink::class);
    }

    public function materialLinks(): BelongsToMany
    {
        return $this->belongsToMany(MaterialLink::class, 'attendance_batch_material_link')
            ->withTimestamps()
            ->orderBy('title');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'attendance_batch_teacher', 'attendance_batch_id', 'teacher_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'attendance_batch_id');
    }

    public function materialLinkLabels(): string
    {
        $links = $this->relationLoaded('materialLinks') ? $this->materialLinks : collect();

        if ($links->isNotEmpty()) {
            return $links->pluck('title')->join(', ');
        }

        return $this->materialLink?->title ?? '-';
    }
}
