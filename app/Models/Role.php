<?php

namespace App\Models;

use App\Enum\UserType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperRole
 */
class Role extends Model
{
    use SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
        'type',
        'permissions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'        => UserType::class,
            'permissions' => 'array',
        ];
    }
}
