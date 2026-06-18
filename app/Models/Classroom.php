<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'division', 'format', 'learning_mode', 'age_group', 'level', 'is_active'])]
class Classroom extends Model
{
    use HasFactory;

    public const DIVISION_CODING = 'coding';
    public const DIVISION_ENGLISH = 'english';

    public const FORMAT_PRIVATE = 'private';
    public const FORMAT_SEMI = 'semi';

    public const MODE_SYNCHRONOUS = 'synchronous';
    public const MODE_SELF_PACED = 'self_paced';

    public const AGE_KIDS = 'kids';
    public const AGE_TEENS_ADULT = 'teens_adult';

    public const LEVEL_1 = 'l1';
    public const LEVEL_2 = 'l2';
    public const LEVEL_3 = 'l3';

    protected static function booted(): void
    {
        // learning_mode is an explicit, overridable column, but if it is left
        // blank we derive a sensible default from the division/format mapping.
        static::saving(function (Classroom $classroom): void {
            if (blank($classroom->learning_mode)) {
                $classroom->learning_mode = static::defaultLearningMode($classroom->division, $classroom->format);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * English + Semi is the only synchronous (live cohort) combination today;
     * everything else is self-paced.
     */
    public static function defaultLearningMode(?string $division, ?string $format): string
    {
        return $division === self::DIVISION_ENGLISH && $format === self::FORMAT_SEMI
            ? self::MODE_SYNCHRONOUS
            : self::MODE_SELF_PACED;
    }

    public static function learningModeOptions(): array
    {
        return [
            self::MODE_SYNCHRONOUS => 'Synchronous (live cohort)',
            self::MODE_SELF_PACED => 'Self-paced',
        ];
    }

    public static function levelOptions(): array
    {
        return [
            self::LEVEL_1 => 'Level 1',
            self::LEVEL_2 => 'Level 2',
            self::LEVEL_3 => 'Level 3',
        ];
    }

    public static function divisionOptions(): array
    {
        return [
            self::DIVISION_CODING => 'Coding',
            self::DIVISION_ENGLISH => 'English',
        ];
    }

    public static function formatOptions(): array
    {
        return [
            self::FORMAT_PRIVATE => 'Private',
            self::FORMAT_SEMI => 'Semi',
        ];
    }

    public static function ageOptions(): array
    {
        return [
            self::AGE_KIDS => 'Kids',
            self::AGE_TEENS_ADULT => 'Teens/Adult',
        ];
    }

    public static function makeName(?string $division, ?string $format, ?string $ageGroup): string
    {
        return collect([
            self::divisionOptions()[$division] ?? null,
            self::formatOptions()[$format] ?? null,
            self::ageOptions()[$ageGroup] ?? null,
        ])->filter()->join(' · ');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'classroom_student')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isPrivate(): bool
    {
        return $this->format === self::FORMAT_PRIVATE;
    }

    public function isSemi(): bool
    {
        return $this->format === self::FORMAT_SEMI;
    }

    public function isSynchronous(): bool
    {
        return $this->learning_mode === self::MODE_SYNCHRONOUS;
    }

    public function isSelfPaced(): bool
    {
        return ! $this->isSynchronous();
    }

    public function learningModeLabel(): string
    {
        return self::learningModeOptions()[$this->learning_mode] ?? $this->learning_mode;
    }

    public function levelLabel(): ?string
    {
        return $this->level ? (self::levelOptions()[$this->level] ?? $this->level) : null;
    }

    public function divisionLabel(): string
    {
        return self::divisionOptions()[$this->division] ?? $this->division;
    }

    public function formatLabel(): string
    {
        return self::formatOptions()[$this->format] ?? $this->format;
    }

    public function ageLabel(): string
    {
        return self::ageOptions()[$this->age_group] ?? $this->age_group;
    }
}
