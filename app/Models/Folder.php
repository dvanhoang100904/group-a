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
        'parent_folder_id',
        'user_id'
    ];

    protected $casts = [
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

    // =========================
    // 🆕 QUAN HỆ MỚI CHO FOLDER SHARES
    // =========================

    /** Các chia sẻ của folder này */
    public function shares(): HasMany
    {
        return $this->hasMany(FolderShare::class, 'folder_id', 'folder_id');
    }

    /** Người được chia sẻ folder này */
    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'folder_shares', 'folder_id', 'shared_with_id')
            ->withPivot(['permission', 'created_at']);
    }

    /**
     * Kiểm tra user có quyền truy cập folder không
     */
    public function canAccess(User $user): bool
    {
        // Chủ sở hữu có toàn quyền
        if ($this->user_id === $user->user_id) {
            return true;
        }

        // Kiểm tra chia sẻ trực tiếp
        if ($this->shares()->where('shared_with_id', $user->user_id)->exists()) {
            return true;
        }

        // Kiểm tra chia sẻ thông qua folder cha (tính kế thừa)
        if ($this->parent_folder_id) {
            $parentFolder = Folder::find($this->parent_folder_id);
            return $parentFolder ? $parentFolder->canAccess($user) : false;
        }

        return false;
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

    /**
     * Scope: Lấy folders mà user có quyền truy cập
     */
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // Folder của chính user
            $q->where('user_id', $userId)
                // Hoặc được chia sẻ trực tiếp
                ->orWhereHas('shares', function ($shareQuery) use ($userId) {
                    $shareQuery->where('shared_with_id', $userId);
                })
                // HOẶC folder con của folder được chia sẻ (kế thừa quyền)
                ->orWhereHas('parentFolder.shares', function ($shareQuery) use ($userId) {
                    $shareQuery->where('shared_with_id', $userId);
                })
                // HOẶC folder cháu (đệ quy) của folder được chia sẻ
                ->orWhereHas('parentFolder.parentFolder.shares', function ($shareQuery) use ($userId) {
                    $shareQuery->where('shared_with_id', $userId);
                })
                // Có thể thêm nhiều cấp độ hơn nếu cần
                ->orWhereHas('parentFolder.parentFolder.parentFolder.shares', function ($shareQuery) use ($userId) {
                    $shareQuery->where('shared_with_id', $userId);
                });
        });
    }
}
