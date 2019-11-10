<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'pre_order_item';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['order_id', 'product_id', 'product_full_name', 'sku_id', 'quantity','price'];
}
