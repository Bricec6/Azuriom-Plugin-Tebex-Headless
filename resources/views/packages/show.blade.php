<div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-pkg-name"></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-pkg-description"></div>

                <div class="text-center mt-3" id="modal-pkg-image-container" style="display:none;">
                    <img class="img-fluid rounded" id="modal-pkg-image" src="" alt="" style="max-height: 300px;">
                </div>
            </div>
            <div class="modal-footer align-items-center justify-content-between">
                    <span class="flex-md-fill font-weight-bold">
                        <span id="modal-pkg-price-display" class="flex-md-fill font-weight-bold"></span>
                    </span>

                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-1">
                            <label for="quantity-modal" class="me-2">{{ trans('tebex::messages.fields.quantity') }}</label>
                            <input type="number"
                                   min="1"
                                   max="99"
                                   class="form-control text-center"
                                   id="quantity-modal"
                                   value="1"
                                   style="width: 80px;">
                        </div>

                        <div class="ms-auto d-flex flex-wrap gap-2">
                            <button type="button" id="btn-remove" class="btn btn-primary" style="display: none;" onclick="confirmRemove()">
                                <i class="bi bi-cart-x"></i> {{ trans('messages.actions.remove') }}
                            </button>

                            <button type="button" id="btn-add" class="btn btn-primary" onclick="checkUsernameAndAdd()">
                                <i class="bi bi-cart-plus"></i> {{ trans('tebex::messages.packages.buy') }}
                            </button>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPackage = null;
    let currentUnitPrice = 0;
    let isInCart = false;

    const tebexConfig = {
        currency: '{{ tebex_currency_symbol() }}',
        username: '{{ auth()->user()->name ?? session('tebex.username') }}',
        cart: @json(tebex_cart_items()),
        routes: {
            add: '{{ route('tebex.packages.add') }}',
            update: '{{ route('tebex.packages.update') }}',
            remove: '{{ route('tebex.packages.remove') }}'
        },
        csrf: '{{ csrf_token() }}'
    };

    /**
     * Opens the modal and initializes the state
     */
    function openPackageModal(pkg) {
        currentPackage = pkg;

        document.getElementById('modal-pkg-name').innerText = pkg.name;
        document.getElementById('modal-pkg-description').innerHTML = pkg.description;

        const imgContainer = document.getElementById('modal-pkg-image-container');
        const imgEl = document.getElementById('modal-pkg-image');
        if (pkg.image) {
            imgEl.src = pkg.image;
            imgContainer.style.display = 'block';
        } else {
            imgContainer.style.display = 'none';
        }

        currentUnitPrice = pkg.price.discounted ? parseFloat(pkg.price.discounted) : parseFloat(pkg.price.normal);
        const priceDisplay = document.getElementById('modal-pkg-price-display');

        if (pkg.price.discounted) {
            priceDisplay.innerHTML = `<del class="small text-danger me-2">${pkg.price.normal}${tebexConfig.currency}</del> ${pkg.price.discounted}${tebexConfig.currency}`;
        } else {
            priceDisplay.innerText = `${pkg.price.normal}${tebexConfig.currency}`;
        }

        const cartItem = tebexConfig.cart.find(item => item.package_id == pkg.id);
        const qtyInput = document.getElementById('quantity-modal');
        const btnRemove = document.getElementById('btn-remove');
        const btnAdd = document.getElementById('btn-add');

        if (cartItem) {
            isInCart = true;
            qtyInput.value = cartItem.quantity;

            btnRemove.style.display = 'inline-block';
            btnAdd.style.display = 'none';
        } else {
            isInCart = false;
            qtyInput.value = 1;

            btnRemove.style.display = 'none';
            btnAdd.style.display = 'inline-block';
        }

        new bootstrap.Modal(document.getElementById('packageModal')).show();
    }

    document.getElementById('quantity-modal').addEventListener('change', function() {
        let qty = parseInt(this.value);
        if (isNaN(qty) || qty < 1) { qty = 1; this.value = 1; }
        if (qty > 99) { qty = 99; this.value = 99; }

        if (isInCart) {
            fetch(tebexConfig.routes.update, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tebexConfig.csrf },
                body: JSON.stringify({ package_id: currentPackage.id, quantity: qty })
            })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const itemIndex = tebexConfig.cart.findIndex(i => i.package_id == currentPackage.id);
                        if(itemIndex > -1) {
                            tebexConfig.cart[itemIndex].quantity = qty;
                        }
                    }
                });
        }
    });

    function checkUsernameAndAdd() {
        const qty = parseInt(document.getElementById('quantity-modal').value);

        const performAdd = (username = null) => {
            const payload = { package_id: currentPackage.id, quantity: qty };
            if (username) payload.username = username;

            sendAction(tebexConfig.routes.add, 'POST', payload);
        };

        if (!tebexConfig.username) {
            const packageModalElement = document.getElementById('packageModal');
            const usernameModalElement = document.getElementById('usernameModal');

            if (usernameModalElement) {
                const openUsernameModal = () => {
                    bootstrap.Modal.getOrCreateInstance(usernameModalElement).show();
                };

                if (packageModalElement && packageModalElement.classList.contains('show')) {
                    packageModalElement.addEventListener('hidden.bs.modal', openUsernameModal, { once: true });
                    bootstrap.Modal.getOrCreateInstance(packageModalElement).hide();
                } else {
                    openUsernameModal();
                }
            }
            return;
        } else {
            performAdd();
        }
    }

    function confirmRemove() {
        if(confirm('{{ trans('tebex::messages.actions.confirm_delete') }}')) {
            sendAction(tebexConfig.routes.remove, 'DELETE', { package_id: currentPackage.id });
        }
    }

    function sendAction(url, method, data) {
        const btns = document.querySelectorAll('.modal-footer button');
        btns.forEach(b => b.disabled = true);

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tebexConfig.csrf, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error(err);
                btns.forEach(b => b.disabled = false);
            });
    }
</script>
