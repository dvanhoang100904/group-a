<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'department_id';

    protected $fillable = ['code', 'name', 'description', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    /** Subjects */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'department_id', 'department_id');
    }
}
