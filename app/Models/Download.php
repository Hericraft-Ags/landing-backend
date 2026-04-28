<?php
// app/Models/Download.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Download extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'file_url',
        'file_size',
        'file_type',
        'icon_class',
        'category_id',
        'download_count',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'download_count' => 'integer'
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
        return $query->where('is_published', true);
    }

    public function scopeByType($query, $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('download_count', 'desc')->limit($limit);
    }

    // Methods
    public function incrementDownloads()
    {
        $this->increment('download_count');
    }

    public function getIconClassAttribute($value)
    {
        if ($value) return $value;
        
        // Icono por defecto según tipo de archivo
        return match($this->file_type) {
            'PDF' => 'fas fa-file-pdf',
            'PPTX', 'PPT' => 'fas fa-file-powerpoint',
            'XLSX', 'XLS' => 'fas fa-file-excel',
            'DOCX', 'DOC' => 'fas fa-file-word',
            default => 'fas fa-file',
        };
    }

    public function getFileSizeFormattedAttribute()
    {
        return $this->file_size ?? '—';
    }
}