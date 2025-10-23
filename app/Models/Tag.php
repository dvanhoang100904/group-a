<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'tags';
    protected $primaryKey = 'tag_id';
    protected $fillable = [
        'name',
        'description',
    ];

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_tags', 'tag_id', 'document_id');
    }
}
