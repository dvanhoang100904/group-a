<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'document_id';
    protected $fillable = [
        'title',
        'description',
        'status',
        'user_id',
        'folder_id',
        'type_id',
        'subject_id'
    ];

    protected $casts = [
        'status' => 'boolean',
        'user_id' => 'integer',
        'folder_id' => 'integer',
        'type_id' => 'integer',
        'subject_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /** Document Versions */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id', 'document_id');
    }

    public function previews()
    {
        return $this->hasMany(DocumentPreview::class, 'document_id');
    }

    /** Document Accesses */
    public function accesses(): HasMany
    {
        return $this->hasMany(DocumentAccess::class, 'document_id', 'document_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'document_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'document_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'document_tags', 'document_id', 'tag_id');
    }
}
