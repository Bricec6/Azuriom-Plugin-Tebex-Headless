@extends('admin.layouts.admin')

@section('title', trans('tebex::admin.title'))

@section('content')

    @include('admin.elements.editor')

    @php
        // Get azuriom images
        $azuriomImages = \Azuriom\Models\Image::all();
    @endphp

    @include('tebex::components.admin.partials.intro', ['ressourceName' => 'tebex', 'ressourceType' => 'plugin'])

    <div class="card shadow mb-4">
        <div class="card-body">

            <form id="tebex-settings-form" action="{{ route('tebex.admin.index') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="public_key">{{ trans('tebex::admin.fields.public_key') }}</label>

                    <div class="input-group @error('public_key') has-validation @enderror" v-scope="{toggle: false}">
                        <input :type="toggle ? 'text' : 'password'" type="text" maxlength="255"
                               class="form-control @error('public_key') is-invalid @enderror" id="public_key"
                               name="public_key" value="{{ old('public_key', $public_key) }}">
                        <button @click="toggle = !toggle" type="button" class="btn btn-outline-primary">
                            <i class="bi" :class="toggle ? 'bi-eye' : 'bi-eye-slash'"></i>
                        </button>
                        <button type="button"
                                id="test-public-token-btn"
                                class="btn btn-success btn-sm">
                            {{ trans('tebex::admin.fields.test_public_token') }}
                        </button>
                        @error('public_key')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <small class="form-text">{{ trans('tebex::admin.fields.public_key_info') }} <a target='_blank'
                                                                                                  href="https://creator.tebex.io/developers/api-keys">https://creator.tebex.io/developers/api-keys</a></small>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="project_id">{{ trans('tebex::admin.fields.project_id') }}</label>
                        <div class="@error('project_id') has-validation @enderror">
                            <input type="text" class="form-control @error('project_id') is-invalid @enderror"
                                   id="project_id" name="project_id" value="{{ old('project_id', $tebex_project_id) }}">
                            @error('project_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"
                               for="private_key">{{ trans('tebex::admin.fields.private_key') }}</label>
                        <div class="@error('private_key') has-validation @enderror">
                            <input type="password" class="form-control @error('private_key') is-invalid @enderror"
                                   id="private_key" name="private_key" value=""
                                   placeholder="{{ trans('tebex::admin.fields.private_key_placeholder') }}">
                            @error('private_key')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <small
                            class="form-text text-muted">{{ trans('tebex::admin.fields.private_key_security_warning') }}</small>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"
                               for="tebex_title">{{ trans('tebex::admin.fields.shop_title') }}</label>

                        <div class="@error('tebex_title') has-validation @enderror">
                            <input type="text" class="form-control @error('tebex_title') is-invalid @enderror"
                                   id="tebex_title" name="tebex_title"
                                   value="{{ old('tebex_title', $tebex_shop_title) }}">

                            @error('tebex_title')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"
                               for="tebex_subtitle">{{ trans('tebex::admin.fields.shop_subtitle') }}</label>

                        <div class="@error('tebex_subtitle') has-validation @enderror">
                            <input type="text" class="form-control @error('tebex_subtitle') is-invalid @enderror"
                                   id="tebex_subtitle" name="tebex_subtitle"
                                   value="{{ old('tebex_subtitle', $tebex_shop_subtitle) }}">

                            @error('tebex_subtitle')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3 card card-body ">
                    <div class="mb-3 form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="home_status" name="home_status"
                               @if(setting('tebex.shop.home_status', true)) checked @endif>
                        <label class="form-check-label"
                               for="home_status">{{ trans('tebex::messages.home.toggle') }}</label>
                    </div>

                    <label class="form-label" for="home_message">{{ trans('tebex::messages.home.title') }}</label>
                    <textarea class="form-control html-editor @error('home_message') is-invalid @enderror"
                              id="home_message" name="home_message"
                              rows="5">{{ old('home_message', setting('tebex.shop.home.message', trans('tebex::messages.home.placeholder'))) }}</textarea>

                    @error('home_message')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>


                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> {{ trans('messages.actions.save') }}
                </button>
            </form>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const testButton = document.getElementById('test-public-token-btn');
            const publicKeyInput = document.getElementById('public_key');

            if (!testButton || !publicKeyInput) {
                return;
            }

            testButton.addEventListener('click', function () {
                const testForm = document.createElement('form');
                testForm.method = 'POST';
                testForm.action = '{{ route('tebex.admin.token.test') }}';
                testForm.style.display = 'none';

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                testForm.appendChild(csrf);

                const publicKey = document.createElement('input');
                publicKey.type = 'hidden';
                publicKey.name = 'public_key';
                publicKey.value = publicKeyInput.value;
                testForm.appendChild(publicKey);

                document.body.appendChild(testForm);
                testForm.submit();
            });
        });
    </script>
@endpush
