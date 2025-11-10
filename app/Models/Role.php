<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'name',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /** Document Accesses */
    public function documentAccesses(): HasMany
    {
        return $this->hasMany(DocumentAccess::class, 'granted_to_role_id', 'role_id');
    }
}
