<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperAccount
 */
class Account extends Model
{
    use SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'name',
        'identifier',
        'status',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\App\Models\User, \App\Models\AccountUser, $this>
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, AccountUser::class);
    }
}
