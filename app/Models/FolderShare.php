<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderShare extends Model
{
    protected $table = 'folder_shares'; // ✅ THÊM DÒNG NÀY
    protected $primaryKey = 'share_id';

    protected $fillable = [
        'folder_id',
        'owner_id',
        'shared_with_id',
        'permission',
        'inherit_to_subfolders',
        'inherit_to_documents'
    ];

    protected $attributes = [
        'inherit_to_subfolders' => true,
        'inherit_to_documents' => true
    ];
    public $timestamps = true; // ✅ THÊM DÒNG NÀY

    /**
     * Validation rules
     */
    public static function rules()
    {
        return [
            'folder_id' => 'required|exists:folders,folder_id',
            'owner_id' => 'required|exists:users,user_id',
            'shared_with_id' => 'required|exists:users,user_id',
            'permission' => 'required|in:view,edit'
        ];
    }

    /** Quan hệ với folder */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id', 'folder_id');
    }

    /** Quan hệ với người sở hữu */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'user_id');
    }

    /** Quan hệ với người được chia sẻ */
    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_id', 'user_id');
    }

    /**
     * Kiểm tra đã tồn tại share chưa
     */
    public static function existsShare($folderId, $sharedWithId)
    {
        return self::where('folder_id', $folderId)
            ->where('shared_with_id', $sharedWithId)
            ->exists();
    }
}
