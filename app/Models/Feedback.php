<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'rating',
    'message',
    'suggestion',
    'allow_testimonial',
])]
class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'allow_testimonial' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
