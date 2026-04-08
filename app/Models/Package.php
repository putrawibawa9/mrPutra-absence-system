<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'total_sessions', 'price'])]
class Package extends Model
{
    use HasFactory;

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
