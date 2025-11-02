<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function folders()
    {
        return $this->hasMany(Folder::class, 'user_id');
    }

    public function folderLogs()
    {
        return $this->hasMany(FolderLog::class, 'moved_by');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'user_id');
    }
    public function documentVersions()
    {
        return $this->hasMany(DocumentVersion::class, 'user_id');
    }

    public function grantedAccesses()
    {
        return $this->hasMany(DocumentAccess::class, 'granted_by');
    }

    public function receivedAccesses()
    {
        return $this->hasMany(DocumentAccess::class, 'granted_to_user_id');
    }

    public function generatedPreviews()
    {
        return $this->hasMany(DocumentPreview::class, 'generated_by');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'user_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'user_id');
    }
}
