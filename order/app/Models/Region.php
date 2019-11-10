<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'pre_region';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'parent_code'];
}
