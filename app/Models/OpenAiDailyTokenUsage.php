<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenAiDailyTokenUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'usage_date',
        'token_limit',
        'input_tokens',
        'reserved_completion_tokens',
        'reserved_total_tokens',
        'request_count',
    ];

    protected $casts = [
        'usage_date' => 'date',
    ];
}
