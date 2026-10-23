<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'zone',
        'capacity',
        'status',
    ];

    /**
     * Ep kieu du lieu tra ve cho API/Model
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function diningSessions(): HasMany
    {
        return $this->hasMany(DiningSession::class);
    }
}
