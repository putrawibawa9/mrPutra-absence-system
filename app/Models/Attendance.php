<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['attendance_batch_id', 'student_id', 'teacher_id', 'payment_id', 'material_link_id', 'date', 'teaching_minutes', 'notes', 'learning_journal', 'homework_content'])]
class Attendance extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'teaching_minutes' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AttendanceBatch::class, 'attendance_batch_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'attendance_teacher', 'attendance_id', 'teacher_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function materialLink(): BelongsTo
    {
        return $this->belongsTo(MaterialLink::class);
    }

    public function materialLinks(): BelongsToMany
    {
        return $this->belongsToMany(MaterialLink::class, 'attendance_material_link')
            ->withTimestamps()
            ->orderBy('title');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
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
