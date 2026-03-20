<div class="list-group mb-4">
    @if(setting('tebex.shop.home_status', true))
        <a href="{{ route('tebex.index') }}" class="list-group-item @if(!isset($category)) active @endif">
            {{ trans('tebex::messages.home.home') }}
        </a>
    @endif
    @foreach($categories as $cat)
        <a href="{{ route('tebex.categories.show', $cat->id) }}" class="list-group-item @if(isset($category) && $cat->id == $category->id) active @endif">
            {{ $cat->name }}
        </a>
        @foreach($cat->subcategories as $subcat)
            <a href="{{ route('tebex.categories.show', $subcat->id) }}" class="list-group-item ps-5 @if(isset($category) && $subcat->id == $category->id) active @endif">
                <i class="bi bi-arrow-return-right"></i>
                {{ $subcat->name }}
            </a>
        @endforeach
    @endforeach
</div>

<div class="d-grid gap-2 mb-4">
    @include('tebex::user.profile')

    @if(auth()->check() || session('tebex.username'))
        <a href="{{ route('tebex.cart.show') }}" class="w-100 btn btn-primary d-block">
            <i class="bi bi-cart"></i> {{ trans('tebex::messages.cart.cart') }}
            <span id="cart-count-badge" class="badge bg-danger ms-1">{{ tebex_cart_count() }}</span>
        </a>
    @endif
</div>

@include('tebex::user.modal')
