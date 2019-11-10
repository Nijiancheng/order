<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Address;
use App\Models\Sku;
use App\Models\Cart;
use Illuminate\Support\Str;

class OrderServiceController extends Controller
{

    public function create(Request $request)
    {
        $order = [];
        $info = json_decode($request->getContent(), true);
        if (empty($info['address_id'])) {
            return $this->failed('地址不可为空');
        }
        //判断会员id参数是否存在
        if (empty($info['user_id'])) {
            return $this->failed('缺少会员id');
        }
        $address = Address::find($info['address_id']);
        //判断地址是否存在
        if (!empty($address)) {
            return $this->failed('地址不存在');
        }
        //判断会员与地址是否对应
        if($address['user_id'] != $info['user_id']){
            return $this->failed('该会员没有此地址');
        }

        $order['receiver_province']=$address->province;
        $order['receiver_city']=$address->city;
        $order['receiver_district']=$address->district;
        $order['receiver_detail']=$address->detail;
        $order['receiver_mobile']=$address->mobile;
        $order['receiver_name']=$address->name;
        $order['user_id']=$address->user_id;
        $order['status']=Order::STATUS_HAND;
        $order['delivery_status']=Order::DELIVERY_STATUS_UNSHIPPED;
        $order['payment_status']=Order::PAYMENT_STATUS_UNPAID;

        //判断购物车id参数是否存在
        if (empty($info['cart_id'])){
            return $this->failed('购物车不存在');
        }
        //获取待下单的购物车
        $cart=[];
        foreach ($info['cart_id'] as $key=>$val){
            $cart[$val] = Cart::where('id','=',$val)->where('user_id','=',$info['user_id'])->where('status','=',Cart::STATUS_WAIT)->get();
        }
        //判断代下单的购物车
        if (empty($cart)){
            return $this->failed('没有待下单的购物车');
        }
        //获取购物车内所有商品详细信息
        foreach ($cart as $k=>$v){
            $sku = Sku::where('id','=',$v['sku_id'])->get();
            if($sku->status  == Sku::STATUS_DEL){
                return $this->failed('商品库存已被删除');
            }else if($sku->quantity < $v['quantity']){
                return $this->failed('商品库存不足');
            }else{
                $product = Product::find($sku->product_id);
                if(empty($product)){
                    return $this->failed('商品不存在');
                } else if($product['status'] ==Product::STATUS_DEL){
                    return $this->failed('商品已删除');
                }else if($product['status'] == Product::STATUS_NO){
                    return $this->failed('商品已下架');
                }else{
                    $order['product_fee'] = $v['quantity']*$sku->price;
                    Sku::where('id','=',$v['sku_id'])->update(['quantity'=>$sku->quantity-$v['quantity'],'sale_num'=>$sku->sale_num+$v['quantity']]);
                    $express = Express::where('id','=',$product->express_id);
                    if($order['product_fee'] > $express->min_money){
                        $order['express_fee']=0;
                    }else{
                        $order['express_fee'] = $v['quantity']*$sku->weight/$express->weight*$express->fee;
                    }
                    $order['total_fee'] =  $order['product_fee']+$order['express_fee'];
                    $order['number'] = date('YmdHia')+Str::random(18);
                    $res =Order::create($order);
                    if(!empty($res)){
                        $result = $this->order_item_create($res->id,$sku,$product,$v);
                        if($result){
                            Cart::where('id','=',$v['id'])->update(['status'=>Cart::STATUS_NORMAL]);
                        }else{
                            return $this->failed('添加订单详情失败');
                        }
                    }else{
                        return $this->failed('添加订单失败');
                    }
                }

            }
        }
    }

    public function order_item_create($order_id,$sku,$product,$v){
        $order_item = [];
        $order_item['order_id']=$order_id;
        $order_item['product_id']=$sku->product_id;
        $order_item['product_full_name']=$product->name+$sku->version;
        $order_item['sku_id']=$sku->id;
        $order_item['quantity']=$v['quantity'];
        $order_item['price']=$sku->price;

        $res = OrderItem::create($order_item);
        if(!empty($res)){
            return true;
        }else{
            return false;
        }
    }
}
