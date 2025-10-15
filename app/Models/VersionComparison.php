<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VersionComparison extends Model
{
    protected $table = 'version_comparisons';
    protected $primaryKey = 'comparison_id';
    protected $fillable = [
        'diff_result',
        'base_version_id',
        'compare_version_id',
        'compared_by'
    ];

    public function baseVersion()
    {
        return $this->belongsTo(DocumentVersion::class, 'base_version_id');
    }

    public function compareVersion()
    {
        return $this->belongsTo(DocumentVersion::class, 'compare_version_id');
    }

    public function comparedBy()
    {
        return $this->belongsTo(User::class, 'compared_by');
    }
}
