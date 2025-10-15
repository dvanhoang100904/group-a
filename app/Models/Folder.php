<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $table = 'folders';
    protected $primaryKey = 'folder_id';
    protected $fillable = ['name', 'status', 'parent_folder_id', 'user_id'];

    public function parentFolder()
    {
        return $this->belongsTo(Folder::class, 'parent_folder_id');
    }

    public function childFolders()
    {
        return $this->hasMany(Folder::class, 'parent_folder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    public function logs()
    {
        return $this->hasMany(FolderLog::class, 'from_folder_id');
    }
}
