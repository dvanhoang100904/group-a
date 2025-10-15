<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function version()
    {
        return $this->belongsTo(DocumentVersion::class, 'version_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }
}
