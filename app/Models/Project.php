<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['project_title', 'client_name', 'total_cost'])]
class Project extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_cost' => 'integer',
        ];
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(Progress::class)->latest('created_at');
    }

    public function latestProgress(): HasOne
    {
        return $this->hasOne(Progress::class)->latestOfMany();
    }

    public function formattedTotalCost(): string
    {
        return 'Rp '.number_format($this->total_cost, 0, ',', '.');
    }
}
