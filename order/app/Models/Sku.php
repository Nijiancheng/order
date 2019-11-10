<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    const STATUS_DEL = 90;
    const STATUS_NORMAL= 10;
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'pre_product';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['product_id', 'version', 'quantity', 'price', 'weight','sale_num','status'];

}
