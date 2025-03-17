<?php

namespace App\Http\Controllers\Frontend\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\OrderRepositoryInterface as OrderRepository;
use App\Repositories\Interfaces\OrderPaymentRepositoryInterface as OrderPaymentRepository;
use Illuminate\Support\Facades\Log;

class MomoController extends Controller
{
    protected $orderRepository;
    protected $orderPaymentRepository;

    public function __construct(
        OrderRepository $orderRepository,
        OrderPaymentRepository $orderPaymentRepository = null
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    public function momo_return(Request $request)
    {
        $orderId = $request->input('orderId');
        $requestId = $request->input('requestId');
        $amount = $request->input('amount');
        $orderInfo = $request->input('orderInfo');
        $resultCode = $request->input('resultCode');
        $message = $request->input('message');
        $transId = $request->input('transId');
        $extraData = $request->input('extraData');
        $signature = $request->input('signature');
        
        Log::info('MoMo return callback received', [
            'order_id' => $orderId,
            'result_code' => $resultCode,
            'trans_id' => $transId,
            'all_params' => $request->all()
        ]);
        
        $orderCode = preg_replace('/[^0-9]/', '', $orderId);
        
        try {
            $order = $this->orderRepository->findByCondition([
                ['code', '=', $orderCode]
            ]);
            
            if (!$order) {
                Log::error('Order not found', ['order_code' => $orderCode]);
                return redirect()->route('home.index')->with('error', 'Không tìm thấy đơn hàng');
            }
            
            if ($resultCode == '0') {
                try {
                    $this->orderRepository->update($order->id, ['payment' => 'paid']);
                    Log::info('Order payment status updated successfully', ['order_id' => $order->id]);
                    
                    if ($this->orderPaymentRepository) {
                        $this->orderPaymentRepository->create([
                            'order_id' => $order->id,
                            'method_name' => 'momo',
                            'payment_id' => $transId,
                            'payment_detail' => $request->all()
                        ]);
                        Log::info('Payment details saved successfully', ['order_id' => $order->id]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to update order payment status', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                Log::warning('MoMo payment failed', [
                    'order_id' => $order->id,
                    'result_code' => $resultCode,
                    'message' => $message
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing MoMo callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('home.index')->with('error', 'Có lỗi xảy ra trong quá trình xử lý thanh toán');
        }
        
        return redirect()->route('cart.success', ['code' => $order->code]);
    }
    
    public function momo_ipn(Request $request)
    {
        
        $orderId = $request->input('orderId');
        $resultCode = $request->input('resultCode');
        $transId = $request->input('transId');
        
        Log::info('MoMo IPN callback received', [
            'order_id' => $orderId,
            'result_code' => $resultCode,
            'trans_id' => $transId
        ]);
        
        $orderCode = preg_replace('/[^0-9]/', '', $orderId);
        
        try {
            $order = $this->orderRepository->findByCondition([
                ['code', '=', $orderCode]
            ]);
            
            if (!$order) {
                Log::error('Order not found in IPN', ['order_code' => $orderCode]);
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($resultCode == '0') {
                $this->orderRepository->update($order->id, ['payment' => 'paid']);
                Log::info('Order payment status updated by IPN', ['order_id' => $order->id]);

                if ($this->orderPaymentRepository) {
                    $this->orderPaymentRepository->create([
                        'order_id' => $order->id,
                        'method_name' => 'momo',
                        'payment_id' => $transId,
                        'payment_detail' => $request->all()
                    ]);
                }
                
                return response()->json(['message' => 'Success'], 200);
            } else {
                Log::warning('MoMo IPN payment failed', [
                    'order_id' => $order->id,
                    'result_code' => $resultCode
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing MoMo IPN', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return response()->json(['message' => 'Payment failed'], 400);
    }
}