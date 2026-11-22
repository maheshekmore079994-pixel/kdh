<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    protected $fillable = ['key', 'prefix', 'padding', 'next_number', 'reset_yearly', 'year'];

    protected function casts(): array
    {
        return [
            'padding' => 'integer',
            'next_number' => 'integer',
            'reset_yearly' => 'boolean',
            'year' => 'integer',
        ];
    }
}
