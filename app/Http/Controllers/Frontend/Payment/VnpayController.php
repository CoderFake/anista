<?php

namespace App\Http\Controllers\Frontend\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\OrderRepositoryInterface as OrderRepository;
use App\Repositories\Interfaces\OrderPaymentRepositoryInterface as OrderPaymentRepository;
use Illuminate\Support\Facades\Log;

class VnpayController extends Controller
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

    public function vnpay_return(Request $request)
    {
        $vnp_TxnRef = $request->input('vnp_TxnRef');
        $vnp_Amount = $request->input('vnp_Amount');
        $vnp_ResponseCode = $request->input('vnp_ResponseCode');
        $vnp_TransactionNo = $request->input('vnp_TransactionNo');
        $vnp_BankCode = $request->input('vnp_BankCode');
        $vnp_PayDate = $request->input('vnp_PayDate');
        $vnp_SecureHash = $request->input('vnp_SecureHash');

        Log::info('VNPAY return callback received', [
            'order_code' => $vnp_TxnRef,
            'response_code' => $vnp_ResponseCode,
            'transaction_no' => $vnp_TransactionNo,
            'all_params' => $request->all()
        ]);
        
        try {
            $order = $this->orderRepository->findByCondition([
                ['code', '=', $vnp_TxnRef]
            ]);
            
            if (!$order) {
                Log::error('Order not found', ['order_code' => $vnp_TxnRef]);
                return redirect()->route('home.index')->with('error', 'Không tìm thấy đơn hàng');
            }
            
            $secureHash = $this->calculateSecureHash($request);
            $isValidSignature = ($secureHash == $vnp_SecureHash);
            
            Log::info('VNPAY signature validation', [
                'calculated_hash' => $secureHash,
                'received_hash' => $vnp_SecureHash,
                'is_valid' => $isValidSignature
            ]);
            
            if ($isValidSignature && $vnp_ResponseCode == '00') {
                try {
                    $this->orderRepository->update($order->id, ['payment' => 'paid']);
                    Log::info('Order payment status updated successfully', ['order_id' => $order->id]);
                    
                    if ($this->orderPaymentRepository) {
                        $this->orderPaymentRepository->create([
                            'order_id' => $order->id,
                            'method_name' => 'vnpay',
                            'payment_id' => $vnp_TransactionNo,
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
                Log::warning('VNPAY payment failed or invalid signature', [
                    'order_id' => $order->id,
                    'response_code' => $vnp_ResponseCode,
                    'is_valid_signature' => $isValidSignature
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing VNPAY callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('home.index')->with('error', 'Có lỗi xảy ra trong quá trình xử lý thanh toán');
        }
        
        return redirect()->route('cart.success', ['code' => $order->code]);
    }
    
    public function vnpay_ipn(Request $request)
    {

        $vnp_TxnRef = $request->input('vnp_TxnRef');
        $vnp_Amount = $request->input('vnp_Amount');
        $vnp_ResponseCode = $request->input('vnp_ResponseCode');
        $vnp_TransactionNo = $request->input('vnp_TransactionNo');
        $vnp_SecureHash = $request->input('vnp_SecureHash');
        
        Log::info('VNPAY IPN callback received', [
            'order_code' => $vnp_TxnRef,
            'response_code' => $vnp_ResponseCode,
            'transaction_no' => $vnp_TransactionNo
        ]);
        
        try {
            $order = $this->orderRepository->findByCondition([
                ['code', '=', $vnp_TxnRef]
            ]);
            
            if (!$order) {
                Log::error('Order not found in IPN', ['order_code' => $vnp_TxnRef]);
                return response('Order not found', 404);
            }
            
            $secureHash = $this->calculateSecureHash($request);
            $isValidSignature = ($secureHash == $vnp_SecureHash);
            
            if ($isValidSignature && $vnp_ResponseCode == '00') {
                $this->orderRepository->update($order->id, ['payment' => 'paid']);
                Log::info('Order payment status updated by IPN', ['order_id' => $order->id]);

                if ($this->orderPaymentRepository) {
                    $this->orderPaymentRepository->create([
                        'order_id' => $order->id,
                        'method_name' => 'vnpay',
                        'payment_id' => $vnp_TransactionNo,
                        'payment_detail' => $request->all()
                    ]);
                }
                
                return response('OK', 200);
            } else {
                Log::warning('VNPAY IPN validation failed', [
                    'order_id' => $order->id,
                    'is_valid_signature' => $isValidSignature,
                    'response_code' => $vnp_ResponseCode
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing VNPAY IPN', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return response('Failed', 400);
    }
    
    private function calculateSecureHash(Request $request)
    {
        $vnpParams = $request->all();
        
        if (isset($vnpParams['vnp_SecureHash'])) {
            unset($vnpParams['vnp_SecureHash']);
        }
        
        if (isset($vnpParams['vnp_SecureHashType'])) {
            unset($vnpParams['vnp_SecureHashType']);
        }
        
        ksort($vnpParams);
        
        $i = 0;
        $hashData = "";
        foreach ($vnpParams as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $vnp_HashSecret = vnpayConfig()['vnp_HashSecret'];
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        
        return $secureHash;
    }
}