<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tag extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug'
    ];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // Relaciones
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }

    // Scopes
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('articles')
                     ->orderBy('articles_count', 'desc')
                     ->limit($limit);
    }
}