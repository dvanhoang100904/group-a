<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $table = 'tags';
    protected $primaryKey = 'tag_id';
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'image_path',
        'code',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /** Tự động tạo mã thẻ */
    protected static function booted()
    {
        static::creating(function ($tag) {
            if (!$tag->code) {
                $last = Tag::orderBy('tag_id', 'desc')->first();
                $next = $last ? ($last->tag_id + 1) : 1;
                $tag->code = 'TAG' . str_pad($next, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /** Documents */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_tags', 'tag_id', 'document_id');
    }
}
