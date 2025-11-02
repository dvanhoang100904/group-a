<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolderLog extends Model
{
    protected $table = 'folder_logs';
    protected $primaryKey = 'log_id';
    protected $fillable = ['document_id', 'from_folder_id', 'to_folder_id', 'moved_by', 'moved_at'];

    protected $casts = [
        'document_id' => 'integer',
        'from_folder_id' => 'integer',
        'to_folder_id' => 'integer',
        'moved_by' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function fromFolder()
    {
        return $this->belongsTo(Folder::class, 'from_folder_id');
    }

    public function toFolder()
    {
        return $this->belongsTo(Folder::class, 'to_folder_id');
    }

    public function movedBy()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
