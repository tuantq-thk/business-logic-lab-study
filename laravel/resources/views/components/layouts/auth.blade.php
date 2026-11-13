<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? __('auth_ui.layout_fallback') }} · {{ config('app.name', __('shell.brand')) }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <main class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.86fr)]">
            <section class="relative hidden overflow-hidden bg-[#102a43] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full border border-cyan-300/20"></div>
                <div class="absolute -bottom-40 -left-20 h-[30rem] w-[30rem] rounded-full border border-amber-300/10"></div>
                <a href="{{ route('home') }}" class="relative inline-flex w-fit items-center gap-3 text-sm font-semibold tracking-wide text-white"><span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-300 text-[#102a43] shadow-lg shadow-cyan-950/20">BL</span><span>{{ __('shell.brand') }}</span></a>
                <div class="relative max-w-xl"><p class="mb-5 font-mono text-xs uppercase tracking-[0.28em] text-cyan-200">{{ __('auth_ui.technical_notebook') }}</p><h1 class="max-w-lg text-5xl font-semibold leading-[1.08] tracking-[-0.04em] xl:text-6xl">{{ __('auth_ui.tagline') }}</h1><p class="mt-6 max-w-lg text-base leading-8 text-slate-300">{{ __('auth_ui.tagline_description') }}</p><div class="mt-10 flex flex-wrap gap-3 text-xs font-medium text-slate-200"><span class="rounded-full border border-white/15 px-3 py-2">{{ __('auth_ui.tags.rule_first') }}</span><span class="rounded-full border border-white/15 px-3 py-2">{{ __('auth_ui.tags.server_validated') }}</span><span class="rounded-full border border-white/15 px-3 py-2">{{ __('auth_ui.tags.evidence_backed') }}</span></div></div>
                <p class="relative text-xs text-slate-400">{{ __('auth_ui.tagline_footer') }}</p>
            </section>
            <section class="flex items-center justify-center px-5 py-10 sm:px-8"><div class="w-full max-w-md"><a href="{{ route('home') }}" class="mb-12 inline-flex items-center gap-2 text-sm font-semibold text-slate-700 lg:hidden"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('shell.brand') }}</a>{{ $slot }}</div></section>
        </main>
    </body>
</html>
