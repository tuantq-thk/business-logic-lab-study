<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('welcome.brand') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('welcome.brand') }}</a>
                <nav class="flex items-center gap-4 text-sm font-semibold">
                    @auth
                        <span class="hidden text-slate-500 sm:inline">{{ __('welcome.nav.greeting', ['name' => auth()->user()->name]) }}</span>
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-[#102a43] px-4 py-2.5 text-white hover:bg-[#173f60]">{{ __('welcome.nav.dashboard') }}</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-slate-600 hover:text-slate-950">{{ __('welcome.nav.logout') }}</button></form>
                    @else
                        <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-950">{{ __('welcome.nav.login') }}</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-[#102a43] px-4 py-2.5 text-white hover:bg-[#173f60]">{{ __('welcome.nav.register') }}</a>
                    @endauth
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
            <div class="grid gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                <section>
                    <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('welcome.hero.eyebrow') }}</p>
                    <h1 class="mt-5 max-w-3xl text-5xl font-semibold leading-[1.05] tracking-[-0.05em] sm:text-7xl">{{ __('welcome.hero.title') }}</h1>
                    <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-600">{{ __('welcome.hero.description') }}</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#102a43] px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 hover:bg-[#173f60]">{{ __('welcome.nav.continue') }}</a>
                            <a href="#principles" class="rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 hover:border-slate-400">{{ __('welcome.hero.principles') }}</a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-xl bg-[#102a43] px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 hover:bg-[#173f60]">{{ __('welcome.nav.register') }}</a>
                            <a href="#principles" class="rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 hover:border-slate-400">{{ __('welcome.hero.principles') }}</a>
                        @endauth
                    </div>
                </section>
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-5"><span class="font-mono text-xs uppercase tracking-widest text-slate-400">{{ __('welcome.lab_index.label') }}</span><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ __('welcome.lab_index.status') }}</span></div>
                    <div class="space-y-4 pt-6">
                        <div class="rounded-2xl border border-slate-200 p-5"><p class="font-mono text-xs text-cyan-700">{{ __('welcome.lab_index.question_label') }}</p><p class="mt-2 font-semibold">{{ __('welcome.lab_index.question') }}</p></div>
                        <div class="rounded-2xl border border-slate-200 p-5"><p class="font-mono text-xs text-cyan-700">{{ __('welcome.lab_index.scenario_label') }}</p><p class="mt-2 font-semibold">{{ __('welcome.lab_index.scenario') }}</p></div>
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5"><p class="font-mono text-xs text-slate-400">{{ __('welcome.lab_index.evidence_label') }}</p><p class="mt-2 text-sm text-slate-500">{{ __('welcome.lab_index.evidence') }}</p></div>
                    </div>
                </section>
            </div>
            <section id="principles" class="mt-24 grid gap-5 sm:grid-cols-3">
                <div class="border-t-2 border-cyan-600 pt-4"><p class="font-semibold">{{ __('welcome.principles.question.title') }}</p><p class="mt-2 text-sm leading-6 text-slate-500">{{ __('welcome.principles.question.text') }}</p></div>
                <div class="border-t-2 border-amber-500 pt-4"><p class="font-semibold">{{ __('welcome.principles.state.title') }}</p><p class="mt-2 text-sm leading-6 text-slate-500">{{ __('welcome.principles.state.text') }}</p></div>
                <div class="border-t-2 border-slate-400 pt-4"><p class="font-semibold">{{ __('welcome.principles.server.title') }}</p><p class="mt-2 text-sm leading-6 text-slate-500">{{ __('welcome.principles.server.text') }}</p></div>
            </section>
        </main>
    </body>
</html>
