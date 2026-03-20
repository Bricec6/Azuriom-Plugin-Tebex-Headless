@extends('layouts.app')

@section('title', $category->name)

@section('content')
<div class="row">
    <h1 class="mb-3">{{ $category->name }}</h1>

    <div class="col-lg-3">
        @include('tebex::categories._sidebar')
    </div>

    <div class="col-lg-9">
        <div class="row gy-4">
            @forelse($category->packages as $package)
                <div class="col-md-4">
                    @include('tebex::packages.card')
                </div>
            @empty
                <div class="col">
                    <div class="alert alert-warning" role="alert">
                        {{ trans('tebex::messages.categories.empty') }}
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@include('tebex::packages.show')
@endsection

@push('scripts')
    <script src="{{ plugin_asset('tebex', 'js/script.js') }}"></script>
    <script>
        window.api = "{{ route('tebex.api.buy') }}";
        window.cartAddApi = "{{ route('tebex.packages.add') }}";
        window.cartShowUrl = "{{ route('tebex.cart.show') }}";
        window.title = "{{ trans('tebex::messages.modal.mc_pseudo') }}";
        window.buy = "{{ trans('tebex::messages.packages.buy') }}";
        window.addToCartBtnText = "Add to Cart";
        window.errorUser = "{{ trans('tebex::messages.modal.bad_username') }}";
        window.cancel = "{{ trans('tebex::messages.packages.cancel') }}";

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-package-url]').forEach(function(el) {
                el.addEventListener('click', function(ev) {
                    ev.preventDefault();

                    axios.get(el.dataset['packageUrl']).then(function(response) {
                        const itemModal = document.getElementById('itemModal');
                        itemModal.innerHTML = response.data;
                        new bootstrap.Modal(itemModal).show();
                    }).catch(function(error) {
                        console.error('Error loading package details:', error);
                        showBootstrapToast('Error', 'Failed to load package details', 'danger');
                    });
                });
            });
        });
    </script>
    @guest
        <script>
            var pseudo = ""
        </script>
    @else
        <script>
            var pseudo = "{{ Auth::user()->name }}"
        </script>
    @endguest
@endpush
