<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /** User */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Folder */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id', 'folder_id');
    }

    /** Type */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id', 'type_id');
    }

    /** Subject */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    /** Document Versions */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id', 'document_id');
    }

    /** Document Previews */
    public function previews(): HasMany
    {
        return $this->hasMany(DocumentPreview::class, 'document_id', 'document_id');
    }

    /** Document Accesses */
    public function accesses(): HasMany
    {
        return $this->hasMany(DocumentAccess::class, 'document_id', 'document_id');
    }

    /** Reports */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'document_id', 'document_id');
    }

    /** Activities */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'document_id', 'document_id');
    }

    /** Tags */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tags', 'document_id', 'tag_id');
    }
}
