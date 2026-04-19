<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'progress_note', 'progress_percentage'])]
class Progress extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'progresses';

    protected $touches = ['project'];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'progress_percentage' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
