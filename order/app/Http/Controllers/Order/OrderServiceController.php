<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Express;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address;
use App\Models\Order;
use App\Models\Sku;
use App\Models\Cart;
use \Exception;

class OrderServiceController extends Controller
{
    private $order = [];

    public function create(Request $request)
    {
        $info = json_decode($request->getContent(), true);
//        return $info;
        if (!array_key_exists('address_id', $info)) {
            return $this->failed('请传入地址id');
        }
        //判断会员id参数是否存在
        if (!array_key_exists('user_id', $info)) {
            return $this->failed('请传入会员id');
        }
        $address = Address::find($info['address_id']);
        //判断地址是否存在
        if (empty($address)) {
            return $this->failed('地址不可用');
        }
        //判断会员与地址是否对应
        if ($address['user_id'] != $info['user_id']) {
            return $this->failed('会员与地址不对应');
        }

        $this->order['receiver_province'] = $address->province;
        $this->order['receiver_city'] = $address->city;
        $this->order['receiver_district'] = $address->district;
        $this->order['receiver_detail'] = $address->detail;
        $this->order['receiver_mobile'] = $address->mobile;
        $this->order['receiver_name'] = $address->name;
        $this->order['user_id'] = $address->user_id;
        $this->order['status'] = Order::STATUS_HAND;
        $this->order['delivery_status'] = Order::DELIVERY_STATUS_UNSHIPPED;
        $this->order['payment_status'] = Order::PAYMENT_STATUS_UNPAID;
        $this->order['number'] = Str::random(32);


        //判断购物车id参数是否存在
        if (!array_key_exists('cart_id', $info)) {
            return $this->failed('请传入购物车id');
        }

        //获取待下单的购物车
        foreach ($info['cart_id'] as $key => $val) {
            $cart = Cart::find($val);
            if ($cart->status == Cart::STATUS_DEL) {
                return $this->failed('购物车已被删除');
            } elseif ($cart->status == Cart::STATUS_NORMAL) {
                return $this->failed('购物车已下单');
            } elseif ($cart->user_id != $info['user_id']) {
                return $this->failed('购物车与用户不匹配');
            }
            $carts[] = $cart;
        }
        //获取购物车内所有商品详细信息
        $products = [];
        foreach ($carts as $key => $val) {
            $sku = Sku::find($val['sku_id']);
            if ($sku->status == Sku::STATUS_DEL) {
                return $this->failed('商品库存已被删除');
            } elseif ($sku->quantity < $val['quantity']) {
                return $this->failed('商品库存不足');
            }
            $product = Product::find($sku->product_id);
            if (empty($product)) {
                return $this->failed('商品不存在');
            } elseif ($product->status == Product::STATUS_DEL) {
                return $this->failed('商品已删除');
            } elseif ($product['status'] == Product::STATUS_NO) {
                return $this->failed('商品已下架');
            }
            $skus = [
                'num' => $val['quantity'],
                'id' => $sku->id,
                'quantity' => $sku->quantity,
                'price' => $sku->price,
                'version' => $sku->version,
                'weight' => $sku->weight,
                'product_id' => $product->id,
                'name' => $product->name,
            ];
            $products[$product->express_id][] = $skus;
        }
        DB::beginTransaction();
        try {
            $result = $this->getPrice($products);
            if (!$result) {
                throw new Exception("库存修改失败");
            }
            $this->order['express_fee']=$result['express_fee'];
            $this->order['product_fee']=$result['product_fee'];
            $this->order['total_fee']=$result['total_fee'];
//            dump(['express_fee'=>$express_fee,'product_fee'=>$product_fee,'total_fee'=>$total_fee]);

            $res = Order::create($this->order);
            if (empty($res)) {
//                return $this->failed('添加订单失败');
                throw new Exception("订单添加失败");
            }
            $result = $this->order_item_create($res->id, $products);
            if (!$result) {
//                return $this->failed('添加订单详情失败');
                throw new Exception("订单添加详情失败");
            }

            foreach($carts as $k=>$v){
                $result = Cart::where('id', '=', $v['id'])->update(['status' => Cart::STATUS_NORMAL]);
                if(empty($result)){
                    throw new Exception("购物车修改状态失败");
                }
            }
            DB::commit();
            return $this->success('添加成功');
        } catch (Exception $e) {
//            接收异常处理并回滚
            DB::rollBack();
            return [
                'status' => false,
                'msg' => $e->getMessage(),
            ];
        }

    }

    //计算运费
    public function getPrice($products)
    {

        $product_fee = 0;
        $express_fee= 0;
        //按运费末班计算价格和运费
        foreach ($products as $key => $val) {
            $express = Express::find($key);
            if (empty($express)) {
                return $this->failed('运费模板不存在');
            } elseif ($express->status == Express::STATUS_DEL) {
                return $this->failed('运费模板已删除');
            }
            $price = 0;
            $weight = 0;
            foreach ($val as $k => $v) {
                $price += $v['price'] * $v['num'];
                $weight += $v['weight'] * $v['num'];
            }
            $product_fee += $price;
            $fee = 0;
            if ($price > $express->min_money) {
                $fee += 0;
            } else {
                $fee += $weight / $express->weight * $express->fee;
            }
            $express_fee += $fee;
        }
        $total_fee = $product_fee + $express_fee;
        return ['express_fee'=>$express_fee,'product_fee'=>$product_fee,'total_fee'=>$total_fee];
    }

    //创建订单详情
    public function order_item_create($order_id, $products)
    {
        foreach ($products as $key => $val) {
            foreach ($val as $k => $v) {
                $order_item=[];
                $order_item['order_id'] = $order_id;
                $order_item['product_id'] = $v['product_id'];
                $order_item['product_full_name'] = $v['name'] . $v['version'];
                $order_item['sku_id'] = $v['id'];
                $order_item['quantity'] = $v['num'];
                $order_item['price'] = $v['price'];
                dump($order_item);

                $res = OrderItem::create($order_item);
                if (empty($res)) {
                    return false;
                }
            }

        }
//        exit;
        return true;
    }

//    private function check()
//    {
//    }
//
//    private function msg()
//    {
//
//    }
}
