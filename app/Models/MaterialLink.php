<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'title',
    'url',
    'description',
    'is_active',
])]
class MaterialLink extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teacherSchedules(): HasMany
    {
        return $this->hasMany(TeacherSchedule::class);
    }

    public function attendances(): BelongsToMany
    {
        return $this->belongsToMany(Attendance::class, 'attendance_material_link')
            ->withTimestamps();
    }

    public function attendanceBatches(): BelongsToMany
    {
        return $this->belongsToMany(AttendanceBatch::class, 'attendance_batch_material_link')
            ->withTimestamps();
    }

    public function statusLabel(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }
}
