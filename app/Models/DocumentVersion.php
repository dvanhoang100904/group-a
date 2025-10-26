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

    /** Loc theo document id */
    public function scopeByDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    /** Loc theo version id */
    public function scopeByVersion($query, $versionId)
    {
        return $query->where('version_id', $versionId);
    }

    /** Sap xep moi nhat theo version number va create at */
    public function scopeLatestOrder($query)
    {
        return $query->orderByDesc('version_number')->orderByDesc('created_at');
    }

    /** Sap theo ngay tao moi nhat */
    public function scopeLatestCreated($query)
    {
        return $query->orderByDesc('created_at');
    }

    /** Chi lay preview con hieu luc */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /** tim kiem loc theo keyword, user, status, ngay */
    public function scopeFilter($query, array $filters = [])
    {
        return $query
            ->when(!empty($filters['keyword']), function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('change_note', 'like', "%{$filters['keyword']}%")
                        ->orWhere('version_number', 'like', "%{$filters['keyword']}%");
                });
            })
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($q) use ($filters) {
                $q->where('is_current_version', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']));
    }

    /** Lay phien ban hien tai cua 1 tai lieu */
    public function scopeCurrent($query)
    {
        return $query->where('is_current_version', true);
    }

    /** Kiem tra co the xoa phien ban hay khong */
    public function isDeletable(): bool
    {
        return !$this->is_current_version;
    }

    /** Danh dau phien ban nay la hien tai */
    public function markAsCurrent()
    {
        $this->update(['is_current_version' => true]);
    }

    /** Bo current version hien tai cua document */
    public static function clearCurrent(int $documentId): void
    {
        static::where('document_id', $documentId)
            ->where('is_current_version', true)
            ->update(['is_current_version' => false]);
    }

    /** Preview moi nhat con hieu luc */
    public function latestPreview()
    {
        return $this->hasOne(DocumentPreview::class, 'version_id')
            ->select('preview_id', 'version_id', 'preview_path', 'expires_at', 'created_at')
            ->active()
            ->latestCreated();
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
