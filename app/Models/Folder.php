<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    protected $table = 'folders';
    protected $primaryKey = 'folder_id';

    protected $fillable = [
        'name',
        'status',
        'parent_folder_id',
        'user_id'
    ];

    protected $casts = [
        'parent_folder_id' => 'integer',
        'user_id' => 'integer',
    ];

    /** Folder */
    public function parentFolder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_folder_id', 'folder_id');
    }

    /** Folders */
    public function childFolders(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_folder_id', 'folder_id');
    }

    /** User */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Documents */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id', 'folder_id');
    }

    /** Folder Logs */
    public function logs(): HasMany
    {
        return $this->hasMany(FolderLog::class, 'from_folder_id', 'folder_id');
    }
}
