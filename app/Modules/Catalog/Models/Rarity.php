<?php

namespace App\Modules\Catalog\Models;

use Database\Factories\RarityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rarity extends Model
{
    /** @use HasFactory<RarityFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function newFactory(): RarityFactory
    {
        return RarityFactory::new();
    }
}
