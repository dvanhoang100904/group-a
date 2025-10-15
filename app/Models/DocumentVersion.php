<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $table = 'document_versions';
    protected $primaryKey = 'version_id';
    protected $fillable = [
        'version_number',
        'file_path',
        'file_size',
        'mime_type',
        'is_current_version',
        'change_note',
        'document_id',
        'user_id'
    ];

    protected $casts = [
        'is_current_version' => 'boolean',
        'file_size' => 'integer'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function previews()
    {
        return $this->hasMany(DocumentPreview::class, 'version_id');
    }

    public function baseComparisons()
    {
        return $this->hasMany(VersionComparison::class, 'base_version_id');
    }

    public function compareComparisons()
    {
        return $this->hasMany(VersionComparison::class, 'compare_version_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'version_id');
    }
}
