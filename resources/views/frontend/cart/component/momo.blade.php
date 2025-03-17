@if ($momo['m2signature'] == $momo['partnerSignature']) 
    <div class="alert alert-success">
        <strong>Tình trạng thanh toán: </strong>Thành công
        <p>Số tiền: {{ number_format(request()->input('amount')/100, 0, ',', '.') }}đ</p>
        <p>Mã giao dịch: {{ request()->input('transId') }}</p>
    </div>
@else 
    <div class="alert alert-danger">
        Giao dịch thanh toán online không thành công. Vui lòng liên hệ: {{ $system['contact']['hotline'] ?? '0905620486' }}
    </div>
@endif