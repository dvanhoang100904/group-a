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

    public function scopeLatestOrder($query)
    {
        return $query->orderByDesc('version_number')->orderByDesc('created_at');
    }

    public function scopeFilter($query, array $filters = [])
    {
        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('change_note', 'like', "%{$filters['keyword']}%")
                    ->orWhere('version_number', 'like', "%{$filters['keyword']}%");
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_current_version', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }



        return $query;
    }

    public function latestPreview()
    {
        return $this->hasOne(DocumentPreview::class, 'version_id')->latest('created_at');
    }

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
