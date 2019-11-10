<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    const STATUS_WAIT = 10;
    const STATUS_NORMAL = 20;
    const STATUS_DEL = 90;
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'pre_cart';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['user_id', 'sku_id', 'quantity', 'status'];
}
