<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\ProvinceRepositoryInterface as ProvinceRepository;
use App\Repositories\Interfaces\OrderRepositoryInterface as OrderRepository;
use App\Repositories\Interfaces\ProductRepositoryInterface as ProductRepository;
use App\Repositories\Interfaces\PromotionRepositoryInterface as PromotionRepository;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Classes\Vnpay;

use App\Classes\Momo;
use App\Classes\Paypal;
use App\Classes\Zalo;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    protected $provinceRepository;
    protected $orderRepository;
    protected $productRepository;
    protected $promotionRepository;
    protected $language;

    public function __construct(
        ProvinceRepository $provinceRepository,
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        PromotionRepository $promotionRepository
    ){
        $this->provinceRepository = $provinceRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->promotionRepository = $promotionRepository;
        $this->language = 1;
    }

    public function checkout(Request $request)
    {
        $provinces = $this->provinceRepository->all();
        $cartCaculate = $this->calculateCart();
        $cartPromotion = $this->getPromotion($request);
        
        $template = [
            'title' => 'Thanh toán đơn hàng',
            'provinces' => $provinces,
            'carts' => Cart::content(),
            'cartCaculate' => $cartCaculate,
            'cartPromotion' => $cartPromotion,
        ];
        
        return view('frontend.cart.index', $template);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'province_id' => 'required|not_in:0',
            'district_id' => 'required|not_in:0',
            'ward_id' => 'required|not_in:0',
            'address' => 'required',
            'method' => 'required',
        ], [
            'fullname.required' => 'Họ tên không được để trống',
            'phone.required' => 'Số điện thoại không được để trống',
            'phone.regex' => 'Số điện thoại không hợp lệ',
            'phone.min' => 'Số điện thoại phải có ít nhất 10 số',
            'province_id.required' => 'Vui lòng chọn tỉnh/thành phố',
            'province_id.not_in' => 'Vui lòng chọn tỉnh/thành phố',
            'district_id.required' => 'Vui lòng chọn quận/huyện',
            'district_id.not_in' => 'Vui lòng chọn quận/huyện',
            'ward_id.required' => 'Vui lòng chọn phường/xã',
            'ward_id.not_in' => 'Vui lòng chọn phường/xã',
            'address.required' => 'Địa chỉ không được để trống',
            'method.required' => 'Vui lòng chọn phương thức thanh toán',
        ]);

        $cartCaculate = $this->calculateCart();
        $cartPromotion = $this->getPromotion($request);
        $code = Carbon::now()->timestamp;
        
        $order = $this->orderRepository->create([
            'code' => $code,
            'fullname' => $request->input('fullname'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'province_id' => $request->input('province_id'),
            'district_id' => $request->input('district_id'),
            'ward_id' => $request->input('ward_id'),
            'address' => $request->input('address'),
            'description' => $request->input('description'),
            'promotion' => $cartPromotion,
            'cart' => $cartCaculate,
            'customer_id' => auth()->guard('customer')->check() ? auth()->guard('customer')->user()->id : null,
            'guest_cookie' => !auth()->guard('customer')->check() ? request()->cookie('guest_cookie') : null,
            'method' => $request->input('method'),
            'confirm' => 'pending',
            'payment' => 'unpaid',
            'delivery' => 'pending',
            'shipping' => 0,
        ]);

        foreach(Cart::content() as $item) {
            $order->products()->attach($item->id, [
                'uuid' => $item->options->uuid ?? null,
                'name' => $item->name,
                'qty' => $item->qty,
                'price' => $item->price,
                'priceOriginal' => $item->options->priceOriginal ?? $item->price,
                'option' => json_encode($item->options),
            ]);
        }

        $method = $request->input('method');
        if (in_array($method, ['vnpay', 'momo', 'paypal', 'zalopay'])) {
            return $this->processPayment($order->id, $method);
        }

        Cart::destroy();
        
        return redirect()->route('cart.success', ['code' => $order->code]);
    }


    public function success(Request $request, $code)
    {
        try {
            $order = $this->orderRepository->findByCondition([
                ['code', '=', $code]
            ]);
            
            if (!$order) {
                Log::error('Order not found', ['order_code' => $code]);
                return redirect()->route('home.index')->with('error', 'Không tìm thấy đơn hàng');
            }
            
            $template = null;
            $system = config('system');
            
            Log::info('Payment callback received', [
                'order_code' => $code,
                'request_params' => $request->all()
            ]);
            
            if ($request->has('vnp_ResponseCode')) {
                $secureHash = $request->input('vnp_SecureHash');
                $vnp_SecureHash = $this->calculateVnpSecureHash($request);
                $template = 'frontend.cart.component.vnpay';
                
                Log::info('VNPAY callback validation', [
                    'secure_hash' => $secureHash,
                    'calculated_hash' => $vnp_SecureHash,
                    'response_code' => $request->input('vnp_ResponseCode')
                ]);
                
                if ($request->input('vnp_ResponseCode') == '00' && $secureHash == $vnp_SecureHash) {
                    try {
                        $this->orderRepository->update($order->id, ['payment' => 'paid']);
                        Log::info('Order payment status updated', ['order_id' => $order->id]);
                    } catch (\Exception $e) {
                        Log::error('Failed to update order status', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } elseif ($request->has('resultCode') || $request->has('signature')) {
                $momo = [];
                $momo['m2signature'] = $request->input('signature');
                $momoConfig = momoConfig();
                $rawHash = "accessKey=" . $momoConfig['accessKey'] . 
                        "&amount=" . $request->input('amount') . 
                        "&extraData=" . $request->input('extraData') . 
                        "&orderId=" . $request->input('orderId') . 
                        "&orderInfo=" . $request->input('orderInfo') . 
                        "&orderType=" . $request->input('orderType') . 
                        "&partnerCode=" . $request->input('partnerCode') . 
                        "&payType=" . $request->input('payType') . 
                        "&requestId=" . $request->input('requestId');
                
                $momo['partnerSignature'] = hash_hmac("sha256", $rawHash, $momoConfig['secretKey']);
                $template = 'frontend.cart.component.momo';
                
                Log::info('MoMo callback validation', [
                    'signature' => $momo['m2signature'],
                    'calculated_signature' => $momo['partnerSignature'],
                    'result_code' => $request->input('resultCode')
                ]);
                
                if ($request->input('resultCode') == '0' && $momo['m2signature'] == $momo['partnerSignature']) {
                    try {
                        $this->orderRepository->update($order->id, ['payment' => 'paid']);
                        Log::info('Order payment status updated', ['order_id' => $order->id]);
                    } catch (\Exception $e) {
                        Log::error('Failed to update order status', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } elseif ($request->has('paymentId') || $request->has('PayerID')) {
                $template = 'frontend.cart.component.paypal';
            }
            
            return view('frontend.cart.success', [
                'order' => $order,
                'template' => $template,
                'secureHash' => $secureHash ?? null,
                'vnp_SecureHash' => $vnp_SecureHash ?? null,
                'momo' => $momo ?? null,
                'system' => $system
            ]);
        } catch (\Exception $e) {
            Log::error('Error in success page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('home.index')->with('error', 'Có lỗi xảy ra trong quá trình xử lý đơn hàng');
        }
    }
    private function calculateCart()
    {
        $cartTotal = 0;
        $cartItems = Cart::content();
        
        foreach ($cartItems as $item) {
            $cartTotal += $item->price * $item->qty;
        }
        
        return [
            'cartTotal' => $cartTotal,
            'cartItems' => $cartItems->count(),
            'cartContent' => $cartItems,
        ];
    }

    private function getPromotion(Request $request)
    {
        $promotionCode = $request->input('voucher', '');
        $discount = 0;
        $cartTotal = $this->calculateCart()['cartTotal'];
        
        $promotions = $this->promotionRepository->getPromotionByCartTotal();
        
        foreach ($promotions as $promotion) {
            $info = $promotion->discountInformation;
            
            if (isset($info['info']) && isset($info['info']['amountFrom'])) {
                for ($i = 0; $i < count($info['info']['amountFrom']); $i++) {
                    $amountFrom = convert_price($info['info']['amountFrom'][$i]);
                    $amountTo = convert_price($info['info']['amountTo'][$i]);
                    $amountValue = convert_price($info['info']['amountValue'][$i]);
                    
                    if ($cartTotal >= $amountFrom && $cartTotal <= $amountTo) {
                        $discount = $amountValue;
                        $promotionCode = $promotion->code;
                        break 2;
                    }
                }
            }
        }
        
        return [
            'code' => $promotionCode,
            'discount' => $discount,
        ];
    }

    private function calculateVnpSecureHash(Request $request)
    {
        $vnpParams = $request->query();
        unset($vnpParams['vnp_SecureHash']);
        unset($vnpParams['vnp_SecureHashType']);
        
        ksort($vnpParams);
        $i = 0;
        $hashData = "";
        
        foreach ($vnpParams as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $vnpSecretKey = vnpayConfig()['vnp_HashSecret'];
        return hash_hmac('sha512', $hashData, $vnpSecretKey);
    }
    
    public function processPayment($orderId, $method)
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            return redirect()->back()->with('error', 'Không tìm thấy đơn hàng');
        }

        switch ($method) {
            case 'vnpay':
                $vnpay = new Vnpay();
                $result = $vnpay->payment($order);
                return redirect()->to($result['url']);
            case 'momo':
                $momo = new Momo();
                $result = $momo->payment($order);
                return redirect()->to($result['url']);
            case 'paypal':
                $paypal = new Paypal();
                $result = $paypal->payment($order);
                return redirect()->to($result['url']);
            default:
                return redirect()->back()->with('error', 'Phương thức thanh toán không hợp lệ');
        }
    }
}