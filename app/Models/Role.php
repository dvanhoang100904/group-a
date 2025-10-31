<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    public function documentAccesses()
    {
        return $this->hasMany(DocumentAccess::class, 'granted_to_role_id');
    }
}
