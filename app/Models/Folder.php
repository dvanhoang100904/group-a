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
        'status' => 'string',
        'parent_folder_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Accessor: Escape output khi lấy name
     */
    public function getNameAttribute($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Mutator: Sanitize input khi set name
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

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

    /**
     * Scope: Lấy folders của user hiện tại
     */
    public function scopeCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }

    /**
     * Scope: Lấy folders với điều kiện bảo mật
     */
    public function scopeSecure($query, $userId = null)
    {
        $userId = $userId ?: auth()->id();
        return $query->where('user_id', $userId);
    }
}
