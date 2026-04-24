<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Video extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail_url',
        'video_url',
        'duration',
        'category_id',
        'type',
        'is_free',
        'views',
        'is_published',
        'published_at'
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views' => 'integer'
    ];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    // Relaciones
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->where('published_at', '<=', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    // Methods
    public function incrementViews()
    {
        $this->increment('views');
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) return null;
        
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }
        
        return $minutes . ' min';
    }

    public function getEmbedUrlAttribute()
    {
        // Para YouTube
        if (str_contains($this->video_url, 'youtube.com') || str_contains($this->video_url, 'youtu.be')) {
            parse_str(parse_url($this->video_url, PHP_URL_QUERY), $params);
            $videoId = $params['v'] ?? null;
            if (!$videoId && preg_match('/youtu\.be\/([^?]+)/', $this->video_url, $match)) {
                $videoId = $match[1];
            }
            return $videoId ? "https://www.youtube.com/embed/{$videoId}" : null;
        }
        
        // Para Vimeo
        if (str_contains($this->video_url, 'vimeo.com')) {
            $videoId = basename(parse_url($this->video_url, PHP_URL_PATH));
            return "https://player.vimeo.com/video/{$videoId}";
        }
        
        return $this->video_url;
    }
}