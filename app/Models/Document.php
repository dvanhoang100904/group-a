<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'document_id';
    protected $fillable = [
        'title',
        'description',
        'status',
        'user_id',
        'folder_id',
        'type_id',
        'subject_id'
    ];

    /** Lay so version tiep theo */
    public function getNextVersionNumber(): int
    {
        $lastVersion = $this->versions()
            ->orderByDesc('version_number')
            ->first();
        return $lastVersion ? $lastVersion->version_number + 1 : 1;
    }

    /** Dat version moi la current, cac version khac false */
    public function setCurrentVersion($version)
    {
        $this->versions()
            ->where('version_id', '!=', $version->version_id)
            ->update(['is_current_version' => false]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function previews()
    {
        return $this->hasMany(DocumentPreview::class, 'document_id');
    }

    public function accesses()
    {
        return $this->hasMany(DocumentAccess::class, 'document_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'document_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'document_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'document_tags', 'document_id', 'tag_id');
    }
}
