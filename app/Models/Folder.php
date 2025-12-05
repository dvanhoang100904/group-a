<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    /**
     * Accessor để Vue Tree hiển thị đúng ID
     */
    public function getIdAttribute()
    {
        return $this->folder_id;
    }

    /**
     * Scope để lấy tất cả folder user có quyền truy cập (bao gồm kế thừa)
     */
    public function scopeAccessibleByWithInheritance(Builder $query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            // 1. Folder của chính user
            $query->where('user_id', $userId);

            // 2. Folder được chia sẻ TRỰC TIẾP với user
            $query->orWhereHas('shares', function ($q) use ($userId) {
                $q->where('shared_with_id', $userId);
            });

            // 3. Folder có ANCESTOR được chia sẻ với user
            $query->orWhere(function ($subQuery) use ($userId) {
                // Lấy tất cả folders mà user được chia sẻ
                $sharedFolderIds = FolderShare::where('shared_with_id', $userId)
                    ->pluck('folder_id')
                    ->toArray();

                if (!empty($sharedFolderIds)) {
                    // Tìm tất cả descendants của các folder được chia sẻ
                    foreach ($sharedFolderIds as $sharedFolderId) {
                        $descendantIds = $this->getAllDescendantIds($sharedFolderId);
                        if (!empty($descendantIds)) {
                            $subQuery->orWhereIn('folder_id', $descendantIds);
                        }
                    }
                }
            });
        });
    }

    /**
     * Kiểm tra user có quyền truy cập folder (bao gồm kế thừa)
     */
    public function isAccessibleBy($userId, $permission = 'view'): bool
    {
        if ($this->user_id == $userId) {
            return true;
        }

        // Kiểm tra chia sẻ trực tiếp
        $directShare = $this->shares()
            ->where('shared_with_id', $userId)
            ->when($permission, function ($q) use ($permission) {
                $q->where('permission', $permission);
            })
            ->exists();

        if ($directShare) {
            return true;
        }

        // Kiểm tra chia sẻ kế thừa từ folder cha
        return $this->hasInheritedAccess($userId, $permission);
    }

    /**
     * Kiểm tra quyền truy cập kế thừa
     */
    private function hasInheritedAccess($userId, $permission = 'view'): bool
    {
        // Lấy tất cả ancestors của folder này
        $ancestors = $this->getAncestors();

        foreach ($ancestors as $ancestor) {
            $share = $ancestor->shares()
                ->where('shared_with_id', $userId)
                ->when($permission, function ($q) use ($permission) {
                    $q->where('permission', $permission);
                })
                ->exists();

            if ($share) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lấy tất cả ancestors của folder
     */
    public function getAncestors()
    {
        $ancestors = collect();
        $current = $this;
        $maxDepth = 10;
        $depth = 0;

        while ($current->parentFolder && $depth < $maxDepth) {
            $ancestors->push($current->parentFolder);
            $current = $current->parentFolder;
            $depth++;
        }

        return $ancestors;
    }

    /**
     * Kiểm tra user có quyền edit nội dung folder (bao gồm kế thừa)
     */
    public function canUserEdit($userId): bool
    {
        if ($this->user_id === $userId) {
            return true;
        }

        $directShare = $this->shares()
            ->where('shared_with_id', $userId)
            ->where('permission', 'edit')
            ->exists();

        if ($directShare) {
            return true;
        }

        return $this->hasParentWithEditPermission($userId);
    }

    /**
     * Kiểm tra folder cha có được chia sẻ với quyền edit
     */
    private function hasParentWithEditPermission($userId): bool
    {
        $current = $this;
        $maxDepth = 10;
        $depth = 0;

        while ($current->parent_folder_id && $depth < $maxDepth) {
            $parent = Folder::with('shares')->find($current->parent_folder_id);
            if (!$parent) {
                break;
            }

            // Kiểm tra parent có được share với quyền edit không
            $parentShare = $parent->shares()
                ->where('shared_with_id', $userId)
                ->where('permission', 'edit')
                ->exists();

            if ($parentShare) {
                return true;
            }

            $current = $parent;
            $depth++;
        }

        return false;
    }
    /**
     * Kiểm tra user có quyền xóa folder
     */
    public function canUserDelete($userId): bool
    {
        if ($this->user_id === $userId) {
            return true;
        }
        return $this->hasParentWithEditPermission($userId);
    }

    /**
     * Kiểm tra user có quyền xem folder (bao gồm kế thừa)
     */
    public function canUserView($userId): bool
    {
        if ($this->user_id === $userId) {
            return true;
        }

        // Kiểm tra chia sẻ trực tiếp
        $directShare = $this->shares()
            ->where('shared_with_id', $userId)
            ->exists();

        if ($directShare) {
            return true;
        }

        // Kiểm tra kế thừa từ folder cha
        return $this->hasParentWithViewPermission($userId);
    }

    private function hasParentWithViewPermission($userId): bool
    {
        $current = $this;
        $maxDepth = 10;
        $depth = 0;

        while ($current->parent_folder_id && $depth < $maxDepth) {
            $parent = Folder::with('shares')->find($current->parent_folder_id);
            if (!$parent) {
                break;
            }

            // Kiểm tra parent có được share không (view hoặc edit)
            $parentShare = $parent->shares()
                ->where('shared_with_id', $userId)
                ->exists();

            if ($parentShare) {
                return true;
            }

            $current = $parent;
            $depth++;
        }

        return false;
    }
    /**
     * Kiểm tra folder này có phải là descendant của folder được share không
     */
    public function isDescendantOfSharedFolder($userId): bool
    {
        $sharedFolderIds = FolderShare::where('shared_with_id', $userId)
            ->pluck('folder_id')
            ->toArray();

        if (empty($sharedFolderIds)) {
            return false;
        }

        // Kiểm tra đệ quy: folder này có ancestor nào trong sharedFolderIds không
        $current = $this;
        $maxDepth = 10; // Giới hạn độ sâu
        $depth = 0;

        while ($current->parent_folder_id && $depth < $maxDepth) {
            $parent = Folder::find($current->parent_folder_id);
            if (!$parent) {
                break;
            }

            // Nếu parent nằm trong danh sách được share -> đây là descendant
            if (in_array($parent->folder_id, $sharedFolderIds)) {
                return true;
            }

            $current = $parent;
            $depth++;
        }

        return false;
    }
    /**
     * Scope đơn giản hơn để lấy tất cả folder user có thể xem
     */
    public function scopeVisibleToUser(Builder $query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // 1. Folder của chính user
            $q->where('user_id', $userId)

                // 2. Folder được chia sẻ trực tiếp với user
                ->orWhereHas('shares', function ($shareQuery) use ($userId) {
                    $shareQuery->where('shared_with_id', $userId);
                })

                // 3. Folder có ANY ancestor được chia sẻ với user
                ->orWhere(function ($subQuery) use ($userId) {
                    // Lấy tất cả folder IDs mà user được chia sẻ
                    $sharedFolderIds = FolderShare::where('shared_with_id', $userId)
                        ->pluck('folder_id')
                        ->toArray();

                    if (!empty($sharedFolderIds)) {
                        // Lấy tất cả descendants của các folder được share
                        $allDescendantIds = [];
                        foreach ($sharedFolderIds as $sharedFolderId) {
                            $descendantIds = $this->getAllDescendantIds($sharedFolderId);
                            $allDescendantIds = array_merge($allDescendantIds, $descendantIds);
                        }

                        if (!empty($allDescendantIds)) {
                            $subQuery->whereIn('folders.folder_id', $allDescendantIds);
                        }
                    }
                });
        });
    }
    /**
     * Lấy tất cả descendant IDs của một folder (đệ quy)
     */
    public function getAllDescendantIds($folderId)
    {
        $descendantIds = [];

        // Lấy cấp 1
        $level1 = Folder::where('parent_folder_id', $folderId)
            ->pluck('folder_id')
            ->toArray();

        $descendantIds = array_merge($descendantIds, $level1);

        // Lấy cấp 2 (con của cấp 1)
        if (!empty($level1)) {
            $level2 = Folder::whereIn('parent_folder_id', $level1)
                ->pluck('folder_id')
                ->toArray();

            $descendantIds = array_merge($descendantIds, $level2);
        }

        // Lấy cấp 3 (con của cấp 2)
        if (!empty($level2)) {
            $level3 = Folder::whereIn('parent_folder_id', $level2)
                ->pluck('folder_id')
                ->toArray();

            $descendantIds = array_merge($descendantIds, $level3);
        }

        // Lấy cấp 4 (con của cấp 3)
        if (!empty($level3)) {
            $level4 = Folder::whereIn('parent_folder_id', $level3)
                ->pluck('folder_id')
                ->toArray();

            $descendantIds = array_merge($descendantIds, $level4);
        }

        // Có thể thêm nhiều cấp hơn nếu cần

        return array_unique($descendantIds);
    }
}
