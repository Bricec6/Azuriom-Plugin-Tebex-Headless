@props([
    'ressourceType' => 'theme',
    'ressourceName' => 'nova',
])

@php
    // need to be refacto
     $ressource = null;
     $name = null;
     $isPlugin = null;

     if($ressourceType === "plugin"){
         $ressource = collect(app(Azuriom\Extensions\UpdateManager::class)->getPlugins(false));
     }
     else{
         $ressource = collect(app(Azuriom\Extensions\UpdateManager::class)->getThemes(false));
     }

     $current = $ressource->where('extension_id', $ressourceName)->first();

     $authors = ['Bricec6', 'Bryx Agency'];

     // Get themes
     $themes_own = collect(app(Azuriom\Extensions\UpdateManager::class)->getThemes(false))->whereIn('author.name', $authors);

     // Get plugins
     $plugin_installed = plugins()->plugins();

     $plugins_own = collect(app(Azuriom\Extensions\UpdateManager::class)->getPlugins(false))->whereIn('author.name', $authors);

     $viewPath = $ressourceType === 'plugin'
        ? $ressourceName . '::components.admin.partials.resource-listing'
        : 'components.admin.partials.resource-listing';

     $transKey = $ressourceType === 'plugin' ? $ressourceName : $ressourceType;
@endphp

<div>
    <div class="d-flex flex-wrap flex-xl-nowrap gap-3 gap-xl--5">
        <div class="w-100 card m-0">
            <div class="card-body">
                <h2 class="fw-semibold">{{ trans($transKey .'::intro.thanks_download', ['type' => $current['type']]) }}</h2>

                <p>
                    {{ trans($transKey .'::intro.hope_project') }}
                </p>

                <p>
                    {!! trans($transKey .'::intro.if_you_enjoy', [
                        'url' => $current['info_url'].'#if-you-like-the-resource-like-below-'
                    ]) !!}
                </p>

                <p><b>{{ trans($transKey .'::intro.need_help') }}</b> {{ trans($transKey .'::intro.click_support') }}</p>

                <div>
                    <ul class="list-unstyled d-flex gap-2 flex-wrap mb-0">
                        <li>
                            <a href="https://discord.gg/Gh2yBxUWvV" target="_blank"
                               class="btn btn-primary fw-bold text-uppercase px-3">
                                <i class="bi bi-discord me-1"></i>{{ trans($transKey .'::intro.support') }}
                            </a>
                        </li>
                        <li>
                            <a href="https://www.serveurliste.com" target="_blank"
                               class="btn btn-outline-warning fw-bold text-uppercase px-3">
                                <i class="bi bi-search-heart-fill me-1"></i>{{ trans($transKey .'::intro.list_server_on_serverliste') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col col-xl-4 d-flex flex-column gap-2">
            <div class="card mb-0 flex-grow-1">
                <div class="card-body d-flex flex-row gap-3">
                    <a href="https://discord.gg/Gh2yBxUWvV" target="_blank">
                        <img src="https://dixept.fr/logo.webp" alt="Logo Dixept" width="148" height="64"
                             class="img-fluid rounded-3">
                    </a>

                    <div>
                        <p class="fw-semibold mb-1">{{ trans($transKey .'::intro.developed_by') }} <a href="https://dixept.fr"
                                                                                                      target="_blank">Dixept</a>
                        </p>
                        <p class="opacity-75 mb-1">{{ trans($transKey .'::intro.unique_identity') }}</p>
                        <a href="https://discord.gg/Gh2yBxUWvV" target="_blank" class="btn btn-primary btn-sm">
                            {{ trans($transKey .'::intro.custom_theme_plugin') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mb-0">
                <div class="card-body">
                    <h2 class="fw-bold text-muted fs-5">{{ trans($transKey .'::intro.check_other_resources') }}</h2>

                    <div class="d-flex flex-wrap flex-md-row gap-2">
                        @include($viewPath, [
                            'icon' => "bi bi-palette",
                            'title' => trans($transKey .'::intro.themes'),
                            'resources' => $themes_own
                        ])

                        @include($viewPath, [
                              'icon' => "bi bi-plug",
                              'title' => trans($transKey .'::intro.plugins'),
                              'resources' => $plugins_own
                        ])
                    </div>
                </div>
            </div>

            <ul class="list-unstyled d-flex gap-2 flex-wrap mb-0">
                <li class="flex-grow-1">
                    <a href="{{ $current['info_url'].'#if-you-like-the-resource-like-below-' }}" target="_blank"
                       class="w-100 btn bg-danger bg-opacity-10 text-danger-emphasis border border-danger border-opacity-25">
                        {{ trans($transKey .'::intro.like_resource', [
                            'type' => $current['type'],
                            'likes' => $current['likes']
                        ]) }} <i class="bi bi-heart-fill"></i>
                    </a>
                </li>
                <li class="flex-grow-1">
                    <a href="https://discord.gg/Gh2yBxUWvV" target="_blank"
                       class="w-100 btn bg-primary bg-opacity-10 text-primary-emphasis border border-primary border-opacity-25">
                        Discord <i class="bi bi-discord"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 my-3">
    <ul class="list-unstyled d-flex gap-2 flex-wrap mb-0">
        <li>
            <a href="{{ route('admin.images.create') }}" target="_blank"
               class="btn btn-secondary fw-bold rounded-4 text-uppercase px-3">
                <i class="bi bi-link me-1"></i>{{ trans($transKey .'::intro.upload_image') }}
            </a>
        </li>
        <li>
            <a href="{{ route('admin.social-links.index') }}" target="_blank"
               class="btn btn-secondary fw-bold rounded-4 text-uppercase px-3">
                <i class="bi bi-link me-1"></i>{{ trans($transKey .'::intro.add_social') }}
            </a>
        </li>
        <li>
            <a href="https://icons.getbootstrap.com/" target="_blank"
               class="btn btn-secondary fw-bold rounded-4 text-uppercase px-3">
                <i class="bi bi-bootstrap-fill me-1"></i>{{ trans($transKey .'::intro.icones_bootstrap') }}
            </a>
        </li>
    </ul>
</div>
