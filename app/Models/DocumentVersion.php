<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentVersion extends Model
{
    protected $table = 'document_versions';
    protected $primaryKey = 'version_id';
    protected $fillable = [
        'version_number',
        'file_path',
        'file_size',
        'mime_type',
        'is_current_version',
        'change_note',
        'document_id',
        'user_id'
    ];

    protected $casts = [
        'is_current_version' => 'boolean',
        'file_size' => 'integer',
        'document_id' => 'integer',
        'user_id' => 'integer',
    ];

    /** Document */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    /** User */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Document Previews */
    public function previews(): HasMany
    {
        return $this->hasMany(DocumentPreview::class, 'version_id', 'version_id');
    }

    /** Activities */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'version_id', 'version_id');
    }
}
