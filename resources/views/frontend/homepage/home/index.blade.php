@extends('frontend.homepage.layout')

@section('content')
<div id="homepage" class="homepage">
    <div class="panel-main-slide">
        <div class="uk-container uk-container-center">
            <div class="uk-grid uk-grid-medium">
                <div class="uk-width-large">
                    @include('frontend.component.slide')
                </div>
                <!-- <div class="uk-width-large-1-3">
                    @if(count($slides['banner']['item']))
                    <div class="banner-wrapper">
                        <div class="uk-grid uk-grid-small">
                            @foreach($slides['banner']['item'] as $key => $val)
                            <div class="uk-width-small-1-2 uk-width-medium-1-1">
                                <div class="banner-item">
                                    <a href="{{ $val['canonical'] }}" title="{{ $val['description'] }}"><img src="{{ $val['image'] }}" alt="{{ $val['image'] }}"></a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div> -->
            </div>
        </div>
    </div>

    @if(isset($widgets['flash-sale']))
    <div class="panel-flash-sale" id="#flash-sale">
        <div class="uk-container uk-container-center">
            <!-- <div class="main-heading">
             
            </div> -->
            <div class="panel-head" style="text-align: center;">
                    <!-- Lấy ra 10 sản phẩm mới nhất-->
                    <h2 class="heading-1"><span style="color: #484848; text-align: center;">Sản phẩm mới</span></h2> 
                </div>
            <div class="panel-body">
                <div class="uk-grid uk-grid-medium">
                    @foreach ($widgets['flash-sale']->object as $key => $product)
                    <div class="uk-width-1-2 uk-width-small-1-2 uk-width-medium-1-3 uk-width-large-1-5 mb20">
                        @include('frontend.component.product-item', ['product' => $product])
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="panel-general page">
        <div class="uk-container uk-container-center">
            @if(isset($widgets['product']->object) && count($widgets['product']->object))
            @foreach($widgets['product']->object as $key => $category)
            @php
            $catName = $category->languages->first()->pivot->name;
            $catCanonical = write_url($category->languages->first()->pivot->canonical)
            @endphp
            <div class="panel-product">
                {{-- <style>
                            /* .main-heading:before {
                                filter: brightness(96%);
                                content: '';
                                display: block;
                                position: absolute;
                                left: 0;
                                top: 0;
                                width: 120px;
                                height: 40px;
                            
                } */
                </style> --}}
                <!-- <div class="main-heading">
                   
                </div> -->
                <div class="panel-head" style="text-align: center;">
                        <div class="uk-flex uk-flex-middle uk-flex-space-between" style="text-align: center;">
                            <h2 class="heading-1"><a href="{{ $catCanonical }}" title="{{ $catName }}" style="color: #484848;">{{ $catName }}</a></h2>
                            <a href="{{ $catCanonical }}" class="readmore">Tất cả sản phẩm</a>
                        </div>
                    </div>
                <div class="panel-body">
                    @if(count($category->products))
                    <div class="uk-grid uk-grid-medium">
                        @foreach($category->products as $index => $product)
                        <div class="uk-width-1-2 uk-width-small-1-2 uk-width-medium-1-3 uk-width-large-1-5 mb20">
                            @include('frontend.component.product-item', ['product' => $product])
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>

    @if(isset($widgets['posts']->object))
    @foreach($widgets['posts']->object as $key => $val)
    @php
    $catName = $val->languages->first()->pivot->name;
    $catCanonical = write_url($val->languages->first()->pivot->canonical);
    @endphp
    <div class="panel-news" style="text-align: center;">
        <div class="uk-container uk-container-center" >
            <div class="panel-head"  style="text-align: center; ">
                <h2 class="heading-1" style="color: #484848;     text-transform: uppercase;
    font-weight: 700;
    font-size: 18px;
"><span><?php echo $catName ?></span></h2>
            </div>
            <div class="panel-body">
                @if(count($val->posts))
                <div class="uk-grid uk-grid-medium">
                    @foreach($val->posts as $post)
                    @php
                    $name = $post->languages->first()->pivot->name;
                    $canonical = write_url($post->languages->first()->pivot->canonical);
                    $createdAt = convertDateTime($post->created_at, 'd/m/Y');
                    $description = cutnchar(strip_tags($post->languages->first()->pivot->description), 100);
                    $image = $post->image;
                    @endphp
                    <div class="uk-width-1-2 uk-width-small-1-2 uk-width-medium-1-3 uk-width-large-1-5">
                        <div class="news-item">
                            <a href="{{ $canonical }}" class="image img-cover"><img src="{{ $image }}" alt="{{ $name }}"></a>
                            <div class="info">
                                <h3 class="title"><a href="{{ $canonical }}" title="{{ $name }}">{{ $name }}</a></h3>
                                <div class="description">{!! $description !!}</div>
                                <div class="uk-flex uk-flex-middle uk-flex-space-between">
                                    <a href="{{ $canonical }}" class="readmore">Xem thêm</a>
                                    <span class="created_at">{{ $createdAt }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    @endif

</div>
@endsection