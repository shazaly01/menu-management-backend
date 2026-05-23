<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * العلاقة مع الطلبات: الطاولة الواحدة يمكن أن يرتبط بها عدة طلبات
     */
    public function remoteOrders(): HasMany
    {
        return $this->hasMany(RemoteOrder::class);
    }
}
