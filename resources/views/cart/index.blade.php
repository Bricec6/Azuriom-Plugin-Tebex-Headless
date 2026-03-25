@extends('layouts.app')

@section('title', trans('tebex::messages.cart.cart'))

@push('styles')
    <style>
        .cart-items thead th {
            width: 40%;
        }

        .cart-items tbody td {
            width: 15%;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <h1>{{ trans('tebex::messages.cart.cart') }}</h1>

    <div class="card">
        <div class="card-body">
            @if(!empty($cart['items']))
                <div class="table-responsive">
                    <table class="table cart-items">
                        <thead class="table-dark">
                        <tr>
                            <th scope="col">{{ trans('messages.fields.name') }}</th>
                            <th scope="col">{{ trans('shop::messages.fields.price') }}</th>
                            <th scope="col">{{ trans('shop::messages.fields.total') }}</th>
                            <th scope="col" style="width: 150px;">{{ trans('shop::messages.fields.quantity') }}</th>
                            <th scope="col">{{ trans('messages.fields.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $globalTotal = 0; @endphp
                        @foreach($cart['items'] as $item)
                            @php
                                $price = $item['price'] ?? 0;
                                $lineTotal = $price * $item['quantity'];
                                $globalTotal += $lineTotal;
                            @endphp
                            <tr class="cart-item" data-package-id="{{ $item['package_id'] }}" data-unit-price="{{ $price }}">
                                <th scope="row">
                                    <div class="d-flex align-items-center">
                                        @if(!empty($item['image']))
                                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px; border-radius: 5px;">
                                        @endif
                                        <span>{{ $item['name'] ?? 'Package ID: ' . $item['package_id'] }}</span>
                                    </div>
                                </th>
                                <td>
                                    @if(isset($item['price']))
                                        @if(isset($item['price_discounted']) && $item['price_discounted'] !== null)
                                            <del class="small text-danger">{{ tebex_format_price($item['price_normal']) }}</del>
                                            {{ tebex_format_price($item['price_discounted']) }}
                                        @else
                                            {{ tebex_format_price($item['price']) }}
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="row-total fw-bold">
                                    @if(isset($item['price']))
                                        {{ tebex_format_price($lineTotal) }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="number"
                                           min="1"
                                           max="99"
                                           class="form-control item-quantity-input text-center"
                                           value="{{ $item['quantity'] }}"
                                           aria-label="{{ trans('shop::messages.fields.quantity') }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-item">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between">
                    <p class="text-muted">
                        {{ trans('tebex::messages.cart.info') }}
                    </p>
                    <form action="{{ route('tebex.cart.clear') }}" method="POST" class="text-end mb-4" onsubmit="return confirm('{{ trans('tebex::messages.actions.confirm_delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> {{ trans('tebex::messages.cart.clear') }}
                        </button>
                    </form>
                </div>

                <h5 class="text-end">
                    {{ trans('tebex::messages.cart.total') }} : <span id="cart-grand-total">{{ tebex_format_price($globalTotal) }}</span>
                </h5>
            @else
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-circle"></i> {{ trans('tebex::messages.cart.empty') }}
                </div>
            @endif

            @if(!empty($cart['items']))
                <div class="d-flex flex-wrap-reverse flex-md-nowrap align-items-end gap-3 mt-4">
                    <a href="{{ route('tebex.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> {{ trans('tebex::messages.cart.continue') }}
                    </a>

                    <form action="{{ route('tebex.cart.checkout') }}" method="post" class="ms-auto col-md-3">
                        @csrf
                        <div class="mb-2">
                            <label for="creator-code" class="form-label">{{ trans('tebex::messages.cart.creator_code') }}</label>
                            <input type="text"
                                   id="creator-code"
                                   name="creator_code"
                                   class="form-control"
                                   maxlength="64"
                                   value="{{ $creatorCode ?? '' }}"
                                   placeholder="{{ trans('tebex::messages.cart.creator_code_placeholder') }}">
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cart-check"></i> {{ trans('tebex::messages.cart.checkout') }}
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="d-flex mt-4">
                    <a href="{{ route('tebex.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> {{ trans('tebex::messages.cart.continue') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currencySymbol = '{{ tebex_currency_symbol() }}';

            function updateUI(row, newQuantity) {
                const unitPrice = parseFloat(row.dataset.unitPrice);
                const rowTotalEl = row.querySelector('.row-total');

                const newLineTotal = unitPrice * newQuantity;
                rowTotalEl.innerText = newLineTotal.toFixed(2) + currencySymbol;

                updateGrandTotal();
            }

            function updateGrandTotal() {
                let total = 0;
                document.querySelectorAll('.cart-item').forEach(row => {
                    const qty = parseInt(row.querySelector('.item-quantity-input').value);
                    const price = parseFloat(row.dataset.unitPrice);
                    if (!isNaN(qty) && !isNaN(price)) {
                        total += qty * price;
                    }
                });
                document.getElementById('cart-grand-total').innerText = total.toFixed(2) + currencySymbol;
            }

            document.querySelectorAll('.item-quantity-input').forEach(function(input) {
                input.addEventListener('change', function() {
                    const row = this.closest('.cart-item');
                    const packageId = row.dataset.packageId;
                    let newQty = parseInt(this.value);

                    if (isNaN(newQty) || newQty < 1) {
                        newQty = 1;
                        this.value = 1;
                    }
                    if (newQty > 99) {
                        newQty = 99;
                        this.value = 99;
                    }

                    updateUI(row, newQty);

                    updateItemQuantity(packageId, newQty);
                });
            });

            document.querySelectorAll('.remove-item').forEach(function(button) {
                button.addEventListener('click', function() {
                    if(confirm('{{ trans('tebex::messages.actions.confirm_delete') }}')) {
                        const row = this.closest('.cart-item');
                        const packageId = row.dataset.packageId;
                        removeItem(packageId);
                    }
                });
            });

            function updateItemQuantity(packageId, quantity) {
                fetch('{{ route('tebex.packages.update') }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        package_id: packageId,
                        quantity: quantity
                    })
                }).catch(error => {
                    console.error('Error updating quantity:', error);
                });
            }

            function removeItem(packageId) {
                fetch('{{ route('tebex.packages.remove') }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        package_id: packageId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error removing item:', error);
                    });
            }
        });
    </script>
@endpush
