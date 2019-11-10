<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    const STATUS_YES = 1;
    const STATUS_NO = 0;
    const STATUS_DEL = 90;
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
    protected $fillable = ['name', 'express_id', 'status', 'quantity', 'sale_num'];
}
