<div class="overflow-hidden position-relative card h-100">
    @if ($package->image)
        <img class="card-img-top" draggable="false" src="{{ $package->image }}" alt="{{ $package->name }}">
    @endif
    <div class="card-body">
        @if(isset($package->banner) && $package->banner)
            <div class="position-absolute top-0 start-0 end-0 package-banner shadow text-center" style="background-color: {{ $package->banner->color }}; color: {{ color_contrast($package->banner->color) }}">
                <small class="text-uppercase fw-bold">{{ $package->banner->text }}</small>
            </div>
        @endif

        <h2 class="card-title fs-4">{{ $package->name }}</h2>
        <p class="card-subtitle fs-5 mb-3">
            @if ($package->price->discounted)
                <del class="small text-danger">{{ tebex_format_price($package->price->normal) }}</del>
                {{ tebex_format_price($package->price->discounted) }}
            @else
                {{ tebex_format_price($package->price->normal) }}
            @endif
            <span><small>{{ setting('tebex.shop.vat.status') ? trans('tebex::messages.vat.ttc') : trans('tebex::messages.vat.ht') }}</small></span>
        </p>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="openPackageModal({{ json_encode($package) }})">
                @if ($package->isInCart)
                    <i class="bi bi-pencil-square"></i> {{ trans('messages.actions.edit') }}
                @else
                    <i class="bi bi-cart-plus"></i> {{ trans('messages.actions.add') }}
                @endif
            </button>
        </div>
    </div>
</div>
