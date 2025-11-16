<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPreview extends Model
{
    protected $table = 'document_previews';
    protected $primaryKey = 'preview_id';
    protected $fillable = [
        'preview_path',
        'expires_at',
        'generated_by',
        'document_id',
        'version_id'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'generated_by' => 'integer',
        'document_id' => 'integer',
        'version_id' => 'integer',
    ];

    /** Document */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    /** Document Version */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'version_id', 'version_id');
    }

    /** User */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by', 'user_id');
    }
}
