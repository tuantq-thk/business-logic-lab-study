<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('shell.lab.title', ['label' => __($lab->labelKey), 'brand' => __('shell.brand')]) }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('shell.brand') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('shell.lab.logout') }}</button></form>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-5 py-12 sm:px-8">
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('shell.lab.back') }}</a>
            <section class="mt-8 max-w-3xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('shell.lab_meta', ['key' => $lab->key]) }}</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em]">{{ __($lab->labelKey) }}</h1>
                <p class="mt-5 text-base leading-8 text-slate-600">{{ __($lab->descriptionKey) }}</p>
                <div class="mt-8 rounded-xl border border-cyan-100 bg-cyan-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-cyan-800">{{ __('shell.lab.question') }}</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ __($lab->questionKey) }}</p>
                </div>
                <p class="mt-8 text-sm leading-6 text-slate-500">{{ __('shell.lab.description') }}</p>
            </section>
        </main>
    </body>
</html>
