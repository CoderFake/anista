<div class="table-responsive payment-info">
    <div class="form-group">
        <label>Mã đơn hàng:</label>
        <span>{{ $_GET['vnp_TxnRef'] }}</span>
    </div>    
    <div class="form-group">
        <label>Số tiền:</label>
        <span>{{ number_format($_GET['vnp_Amount']/100, 0, ',', '.') }}đ</span>
    </div>  
    <div class="form-group">
        <label>Nội dung thanh toán:</label>
        <span>{{ $_GET['vnp_OrderInfo'] }}</span>
    </div> 
    <div class="form-group">
        <label>Mã phản hồi (vnp_ResponseCode):</label>
        <span>{{ $_GET['vnp_ResponseCode'] }}</span>
    </div> 
    <div class="form-group">
        <label>Mã GD Tại VNPAY:</label>
        <span>{{ $_GET['vnp_TransactionNo'] }}</span>
    </div> 
    <div class="form-group">
        <label>Mã Ngân hàng:</label>
        <span>{{ $_GET['vnp_BankCode'] }}</span>
    </div> 
    <div class="form-group">
        <label>Thời gian thanh toán:</label>
        <span>{{ date('d-m-Y H:i:s', strtotime($_GET['vnp_PayDate'])) }}</span>
    </div> 
    <div class="form-group">
        <label>Kết quả:</label>
        <span>
           
            @if ($secureHash == $vnp_SecureHash) 
                @if ($_GET['vnp_ResponseCode'] == '00') 
                    <span style='color:green; font-weight: bold;'>Giao dịch qua cổng VNPAY thành công</span>
                @else 
                    <span style='color:red; font-weight: bold;'>Giao dịch qua cổng VNPAY không thành công</span>
                @endif
            @else 
                <span style='color:red; font-weight: bold;'>Chữ ký không hợp lệ</span>
            @endif
        </span>
    </div> 
</div>

<style>
.payment-info {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin: 15px 0;
}

.payment-info .form-group {
    margin-bottom: 15px;
    display: flex;
}

.payment-info label {
    font-weight: bold;
    width: 250px;
}

.payment-info span {
    flex: 1;
}
</style>