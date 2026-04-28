<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Storage;


class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'bio',
        'avatar_url',
        'role',
        'social_links'
    ];

    protected $casts = [
        'social_links' => 'array'
    ];

    /**
     * Get the options for generating the slug.
     */

    // Relaciones
    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    // Scopes
    public function scopeHasArticles($query)
    {
        return $query->has('articles');
    }

    // Accessor para avatar_url
    public function getAvatarUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Si ya es una URL completa, devolverla
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        
        // Si no, generar la URL completa usando Storage
        return Storage::url($value);
    }
}