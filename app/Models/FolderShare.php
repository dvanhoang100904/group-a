<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderShare extends Model
{
    protected $primaryKey = 'share_id';
    protected $fillable = ['folder_id', 'owner_id', 'shared_with_id', 'permission'];

    // Quan hệ với folder
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    // Quan hệ với người sở hữu
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Quan hệ với người được chia sẻ
    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_id');
    }
}
