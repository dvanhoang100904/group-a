<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $table = 'tags';
    protected $primaryKey = 'tag_id';
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /** Documents */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_tags', 'tag_id', 'document_id');
    }
}
