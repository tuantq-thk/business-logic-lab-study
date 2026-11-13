<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('shell.home.title', ['brand' => config('app.name', __('shell.brand'))]) }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('shell.brand') }}</a>
                <div class="flex items-center gap-4 text-sm">
                    @can('manage-catalog')
                        <a href="{{ route('admin.catalog.index') }}" class="font-semibold text-cyan-700 hover:text-cyan-900">{{ __('shell.admin_catalog') }}</a>
                    @endcan
                    <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-semibold text-cyan-700 hover:text-cyan-900">{{ __('shell.logout') }}</button></form>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
            <div class="max-w-3xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('shell.home.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em] sm:text-5xl">{{ __('shell.home.heading', ['name' => auth()->user()->name]) }}</h1>
                <p class="mt-5 text-base leading-8 text-slate-600">{{ __('shell.home.description') }}</p>
            </div>
            <section class="mt-12 rounded-2xl border border-dashed border-slate-300 bg-white p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="w-full">
                        @if ($catalogSetting->isEnabled())
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ __('shell.home.enabled') }}</span>
                            <h2 class="mt-4 text-xl font-semibold">{{ __('shell.home.enabled_title') }}</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">{{ __('shell.home.enabled_description') }}</p>
                            @if (count($labs) > 0)
                                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                    @foreach ($labs as $lab)
                                        <a href="{{ route($lab->primaryRouteName()) }}" class="rounded-xl border border-slate-200 p-5 transition hover:border-cyan-300 hover:shadow-sm">
                                            <p class="font-mono text-xs text-cyan-700">{{ $lab->key }}</p>
                                            <h3 class="mt-2 font-semibold">{{ __($lab->labelKey) }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ __($lab->descriptionKey) }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">{{ __('shell.home.empty') }}</p>
                            @endif
                        @else
                            <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">{{ __('shell.home.disabled') }}</span>
                            <h2 class="mt-4 text-xl font-semibold">{{ __('shell.home.disabled_title') }}</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">{{ __('shell.home.disabled_description') }}</p>
                            <p class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">{{ __('shell.home.disabled_empty') }}</p>
                        @endif
                    </div>
                    <span class="font-mono text-xs text-slate-400">{{ __('shell.source', ['source' => $catalogSetting->stateSource()]) }}</span>
                </div>
            </section>
        </main>
    </body>
</html>
