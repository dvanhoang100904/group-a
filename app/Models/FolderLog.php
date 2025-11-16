<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderLog extends Model
{
    protected $table = 'folder_logs';
    protected $primaryKey = 'log_id';
    protected $fillable = [
        'document_id',
        'from_folder_id',
        'to_folder_id',
        'moved_by',
        'moved_at'
    ];

    protected $casts = [
        'document_id' => 'integer',
        'from_folder_id' => 'integer',
        'to_folder_id' => 'integer',
        'moved_by' => 'integer',
        'moved_at' => 'datetime',
    ];

    /** Document */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    /** Folder */
    public function fromFolder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'from_folder_id', 'folder_id');
    }

    /** Folder */
    public function toFolder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'to_folder_id', 'folder_id');
    }

    /** User */
    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by', 'user_id');
    }
}
