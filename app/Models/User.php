<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Document Versions */
    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'user_id', 'user_id');
    }

    /** Document Accesses */
    public function grantedAccesses(): HasMany
    {
        return $this->hasMany(DocumentAccess::class, 'granted_by', 'user_id');
    }

    /** Document Accesses */
    public function receivedAccesses(): HasMany
    {
        return $this->hasMany(DocumentAccess::class, 'granted_to_user_id', 'user_id');
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
