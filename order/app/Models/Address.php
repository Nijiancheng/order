<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    const STATUS_NORMAL = 10;
    const STATUS_DEL = 90;
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'pre_address';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['user_id', 'name', 'province', 'city', 'district','detail','mobile','status'];
}
