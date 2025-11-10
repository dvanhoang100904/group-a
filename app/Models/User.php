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

    /** Folders */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'user_id', 'user_id');
    }

    /** Folder Logs */
    public function folderLogs(): HasMany
    {
        return $this->hasMany(FolderLog::class, 'moved_by', 'user_id');
    }

    /** Documents */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'user_id', 'user_id');
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

    /** Document Previews */
    public function generatedPreviews(): HasMany
    {
        return $this->hasMany(DocumentPreview::class, 'generated_by', 'user_id');
    }

    /** Activities */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'user_id', 'user_id');
    }

    /** Reports */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'user_id', 'user_id');
    }
}
