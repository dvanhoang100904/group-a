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
        'document_id' => 'integer',
        'user_id' => 'integer',
        'version_id' => 'integer',
        'folder_id' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Document Version */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'version_id', 'version_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }
}
