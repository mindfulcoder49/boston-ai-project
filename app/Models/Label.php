<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code_name',
        'language_code',
        'text',
    ];

    /**
     * Scope to filter labels by language.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $languageCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLanguage($query, $languageCode)
    {
        return $query->where('language_code', $languageCode);
    }

    /**
     * Get a label's text by code name and language.
     *
     * @param string $codeName
     * @param string $languageCode
     * @return string|null
     */
    public static function getText($codeName, $languageCode)
    {
        return self::where('code_name', $codeName)
            ->where('language_code', $languageCode)
            ->value('text');
    }
}
