<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAccess extends Model
{
    protected $table = 'document_accesses';
    protected $primaryKey = 'access_id';
    protected $fillable = [
        'share_link',
        'granted_to_type',
        'can_view',
        'can_edit',
        'can_delete',
        'can_upload',
        'can_download',
        'can_share',
        'expiration_date',
        'no_expiry',
        'document_id',
        'granted_by',
        'granted_to_user_id',
        'granted_to_role_id'
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_upload' => 'boolean',
        'can_download' => 'boolean',
        'can_share' => 'boolean',
        'no_expiry' => 'boolean',
        'document_id' => 'integer',
        'granted_by' => 'integer',
        'granted_to_user_id' => 'integer',
        'granted_to_role_id' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function grantedToUser()
    {
        return $this->belongsTo(User::class, 'granted_to_user_id');
    }

    public function grantedToRole()
    {
        return $this->belongsTo(Role::class, 'granted_to_role_id');
    }
}
