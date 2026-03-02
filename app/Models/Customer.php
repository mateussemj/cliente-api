<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'document',
        'cep',
        'street',
        'neighborhood',
        'city',
        'state',
    ];

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => 
                "{$attributes['street']}, {$attributes['neighborhood']} - {$attributes['city']}/{$attributes['state']} (CEP: {$attributes['cep']})"
        );
    }

    
    public function scopeByState(Builder $query, string $state): Builder
    {
        return $query->where('state', strtoupper($state));
    }
}
