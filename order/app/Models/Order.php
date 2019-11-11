<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const STATUS_HAND = 10;
    const STATUS_SUCCESS = 20;
    const STATUS_CLOSE = 90;
    const DELIVERY_STATUS_UNSHIPPED = 10;
    const DELIVERY_STATUS_SHIPPED = 20;
    const DELIVERY_STATUS_RECEIVED = 30;
    const PAYMENT_STATUS_UNPAID = 10;
    const PAYMENT_STATUS_PAID = 20;
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'pre_order';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['number', 'user_id', 'product_fee', 'express_fee', 'total_fee','status','delivery_status','payment_status','receiver_name','receiver_province','receiver_city','receiver_district','receiver_detail','receiver_mobile'];
}
