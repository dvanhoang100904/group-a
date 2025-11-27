<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'status' => 'boolean',
        'role_id' => 'integer',
    ];

    /** Role */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

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

    /** 
     * Tai lieu duoc chia se cho nguoi dung nay
     */
    public function grantedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(
            Document::class,
            'document_accesses',
            'granted_to_user_id',
            'document_id'
        )
            ->withPivot([
                'access_id',
                'granted_by',
                'can_view',
                'can_edit',
                'can_delete',
                'can_upload',
                'can_download',
                'can_share',
                'created_at'
            ])
            ->withTimestamps();
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

    // =========================
    // 🆕 QUAN HỆ MỚI CHO FOLDER SHARES
    // =========================

    /** Folder được chia sẻ cho user này */
    public function sharedFolders(): HasMany
    {
        return $this->hasMany(FolderShare::class, 'shared_with_id', 'user_id');
    }

    /** Folder mà user này đã chia sẻ cho người khác */
    public function sharedByMe(): HasMany
    {
        return $this->hasMany(FolderShare::class, 'owner_id', 'user_id');
    }

    /** Lấy tất cả folder user có quyền truy cập (sở hữu + được chia sẻ) */
    public function accessibleFolders()
    {
        $ownedFolderIds = $this->folders()->pluck('folder_id');
        $sharedFolderIds = $this->sharedFolders()->pluck('folder_id');

        $allFolderIds = $ownedFolderIds->merge($sharedFolderIds)->unique();

        return Folder::whereIn('folder_id', $allFolderIds);
    }
}
