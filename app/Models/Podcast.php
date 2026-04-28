<?php
// app/Models/Podcast.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Storage;


class Podcast extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'episode_number',
        'season_number',
        'audio_url',
        'duration',
        'cover_image',
        'category_id',
        'guests',
        'is_published',
        'published_at'
    ];

     protected $casts = [
        'guests' => 'array', // Esto es CLAVE - convierte automáticamente JSON a array
        'is_published' => 'boolean',
        'published_at' => 'datetime'
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

    public function getCoverImageAttribute($value)
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

    public function scopeRecent($query, $limit = 5)
    {
        return $query->orderBy('published_at', 'desc')->limit($limit);
    }

    // Methods
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) return null;
        
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getEpisodeDisplayAttribute()
    {
        $parts = [];
        if ($this->season_number) $parts[] = "Temporada {$this->season_number}";
        if ($this->episode_number) $parts[] = "Episodio {$this->episode_number}";
        
        return implode(' • ', $parts);
    }

    public function getGuestsListAttribute()
    {
        if (!$this->guests || empty($this->guests)) return null;
        return implode(', ', $this->guests);
    }
}