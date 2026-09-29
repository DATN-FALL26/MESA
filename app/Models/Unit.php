<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
    ];

    /**
     * Get the ingredients that use this unit.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'unit_id');
    }
}
