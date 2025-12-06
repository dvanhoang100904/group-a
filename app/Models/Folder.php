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
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Lấy folders với điều kiện bảo mật
     */
    public function scopeSecure($query, $userId = null)
    {
        // Ưu tiên userId được truyền vào
        if ($userId !== null) {
            return $query->where('user_id', $userId);
        }

        // Thử lấy user đăng nhập
        try {
            // Sử dụng Auth facade thay vì helper
            if (\Illuminate\Support\Facades\Auth::hasUser()) {
                $userId = \Illuminate\Support\Facades\Auth::id();
                if ($userId) {
                    return $query->where('user_id', $userId);
                }
            }
        } catch (\Exception $e) {
            // Auth không khả dụng
        }
        return $query->where('user_id', -1); // Hoặc whereRaw('1=0')
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
                    $allDescendantIds = [];

                    foreach ($sharedFolderIds as $sharedFolderId) {
                        // Sửa tên phương thức ở đây
                        $result = [];
                        $descendantIds = self::getAllDescendantIdsStatic($sharedFolderId, $result);
                        if (!empty($descendantIds)) {
                            $allDescendantIds = array_merge($allDescendantIds, $descendantIds);
                        }
                    }

                    if (!empty($allDescendantIds)) {
                        $subQuery->orWhereIn('folder_id', $allDescendantIds);
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
            $parent = Folder::find($current->parent_folder_id);
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
        if ($this->user_id == $userId) {
            return true;
        }

        $directShare = $this->shares()
            ->where('shared_with_id', $userId)
            ->exists();

        if ($directShare) {
            return false;
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

        // Kiểm tra đệ quy từ folder này lên đến root
        $current = $this;
        $maxDepth = 10;
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
     * Kiểm tra user có quyền chỉnh sửa folder (nội dung bên trong)
     */
    public function canUserEditContent($userId): bool
    {
        // Chủ sở hữu có toàn quyền
        if ($this->user_id == $userId) {
            return true;
        }

        // Folder được share trực tiếp với quyền edit
        $directShare = $this->shares()
            ->where('shared_with_id', $userId)
            ->where('permission', 'edit')
            ->exists();

        if ($directShare) {
            return true;
        }

        // Folder con trong folder được share với quyền edit
        return $this->hasParentWithEditPermission($userId);
    }

    /**
     * Scope đơn giản hơn để lấy tất cả folder user có thể xem
     */
    public function scopeVisibleToUser(Builder $query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // 1. Folder của chính user
            $q->where('user_id', $userId)

                // 2. Folder được chia sẻ TRỰC TIẾP với user
                ->orWhereHas('shares', function ($shareQuery) use ($userId) {
                    $shareQuery->where('shared_with_id', $userId);
                })

                // 3. 🔥 QUAN TRỌNG: Folder có ANY ancestor được chia sẻ với user
                // (bao gồm cả folder con/cháu bên trong folder được share)
                ->orWhere(function ($subQuery) use ($userId) {
                    // Lấy tất cả folder IDs mà user được chia sẻ
                    $sharedFolderIds = FolderShare::where('shared_with_id', $userId)
                        ->pluck('folder_id')
                        ->toArray();

                    if (!empty($sharedFolderIds)) {
                        // Tìm tất cả descendants của các folder được share
                        $allDescendantIds = $this->getAllDescendantIdsRecursiveForVisible($sharedFolderIds);

                        if (!empty($allDescendantIds)) {
                            $subQuery->whereIn('folders.folder_id', $allDescendantIds);
                        }
                    }
                });
        });
    }
    /**
     * Lấy tất cả descendant IDs đặc biệt cho việc hiển thị
     */
    private function getAllDescendantIdsRecursiveForVisible(array $parentIds): array
    {
        $allDescendantIds = [];

        // Lấy tất cả cấp con
        $currentLevel = $parentIds;
        $maxDepth = 10;
        $depth = 0;

        while (!empty($currentLevel) && $depth < $maxDepth) {
            // Lấy các folder có parent trong currentLevel
            $nextLevel = Folder::whereIn('parent_folder_id', $currentLevel)
                ->pluck('folder_id')
                ->toArray();

            if (!empty($nextLevel)) {
                $allDescendantIds = array_merge($allDescendantIds, $nextLevel);
                $currentLevel = $nextLevel;
            } else {
                break;
            }

            $depth++;
        }

        return array_unique($allDescendantIds);
    }
    /**
     * Lấy tất cả descendant IDs của một folder (đệ quy)
     */
    public static function getAllDescendantIdsStatic($folderId, &$result = [])
    {
        try {
            // Lấy tất cả folder con trực tiếp
            $children = self::where('parent_folder_id', $folderId)
                ->pluck('folder_id')
                ->toArray();

            if (!empty($children)) {
                foreach ($children as $childId) {
                    $result[] = $childId;
                    self::getAllDescendantIdsStatic($childId, $result);
                }
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
    /**
     * Kiểm tra folder này có phải là folder con trong folder được share không
     */
    public function isChildOfSharedFolder($userId): bool
    {
        if (!$this->parent_folder_id) {
            return false;
        }

        $parentFolder = Folder::find($this->parent_folder_id);
        if (!$parentFolder) {
            return false;
        }

        // Kiểm tra parent folder có được share với user không
        return $parentFolder->shares()
            ->where('shared_with_id', $userId)
            ->exists();
    }
}
