<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $table = 'activities';
    protected $primaryKey = 'activity_id';
    protected $fillable = [
        'action',
        'action_detail',
        'ip_address',
        'user_agent',
        'document_id',
        'user_id',
        'version_id',
        'folder_id'
    ];

    protected $casts = [
        'action_detail' => 'array',
        'document_id' => 'integer',
        'user_id' => 'integer',
        'version_id' => 'integer',
        'folder_id' => 'integer',
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

    /** Document Version */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'version_id', 'version_id');
    }

    /** Folder */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id', 'folder_id');
    }
}
