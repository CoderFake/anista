<div class="panel-foot">
    <h2 class="cart-heading"><span>Phương thức thanh toán</span></h2>
    <div class="cart-method mb30">
        @foreach(__('payment.method') as $key => $val)
        <label for="{{ $val['name'] }}" class="uk-flex uk-flex-middle method-item">
            <input 
                type="radio"
                name="method"
                value="{{ $val['name'] }}"
                @if (old('method', '') == $val['name'] || (!old('method') && $key == 0)) checked @endif
                id="{{ $val['name'] }}"
            >
            <span class="image"><img src="{{ $val['image'] }}" alt="{{ $val['title'] }}"></span>
            <span class="title">{{ $val['title'] }}</span>
        </label>
        @endforeach
    </div>
    <div class="cart-return mb10">
        <span>{!! __('payment.return') !!}</span>
    </div>
    
</div>

<style>
.cart-method {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.method-item {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #e3e3e3;
    transition: all 0.3s ease;
    cursor: pointer;
}

.method-item:hover {
    border-color: #007bff;
    box-shadow: 0 0 10px rgba(0,123,255,0.1);
}

.method-item input[type="radio"] {
    margin-right: 15px;
}

.method-item .image {
    margin-right: 15px;
}

.method-item .image img {
    height: 30px;
    width: auto;
    max-width: 50px;
    display: block;
}

.method-item .title {
    font-weight: 500;
}

.method-item input[type="radio"]:checked + .image + .title {
    color: #007bff;
    font-weight: bold;
}
</style>