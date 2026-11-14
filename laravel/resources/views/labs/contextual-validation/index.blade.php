<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('contextual_validation.page.title') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('shell.brand') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('shell.logout') }}</button></form>
            </div>
        </header>

        <main class="lab-page mx-auto px-4 py-8 sm:px-6 lg:px-10 lg:py-12">
            <a href="{{ route('dashboard') }}" class="inline-flex text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('contextual_validation.page.back') }}</a>
            <section class="lab-reading-column mt-6">
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-700"><span>BL-06</span><span class="text-slate-300">/</span><span>{{ __('contextual_validation.page.eyebrow') }}</span><span class="rounded-full bg-amber-100 px-2.5 py-1 tracking-normal text-amber-800">{{ __('contextual_validation.learning.level') }}</span></div>
                <h1 class="mt-4 max-w-5xl text-3xl font-semibold leading-tight tracking-[-0.04em] sm:text-5xl">{{ __('contextual_validation.page.heading') }}</h1>
                <p class="lab-card-copy mt-5 text-base leading-7 text-slate-600">{{ __('contextual_validation.page.description') }}</p>
            </section>

            <section class="mt-8" aria-labelledby="contextual-path-heading">
                <p class="lab-section-flag">{{ __('contextual_validation.learning.path_title') }}</p>
                <p id="contextual-path-heading" class="mt-2 text-sm text-slate-600">{{ __('contextual_validation.learning.path_description') }}</p>
                <div class="lab-path mt-4">
                    @foreach (['one' => 'learning-objectives', 'two' => 'theory-heading', 'three' => 'code-flow', 'four' => 'code-examples', 'five' => 'threat-heading', 'six' => 'practice'] as $step => $target)
                        <div class="min-w-0 flex-1"><a href="#{{ $target }}" class="lab-path-step block no-underline"><span class="lab-path-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="mt-1 min-w-0 text-sm font-semibold text-slate-800 md:mt-2">{{ __('contextual_validation.learning.path_'.$step) }}</span></a></div>
                        @if (! $loop->last)<div class="lab-path-connector" aria-hidden="true"></div>@endif
                    @endforeach
                </div>
            </section>

            <section id="learning-objectives" class="mt-10 grid gap-4 md:grid-cols-3" aria-labelledby="objectives-title">
                <h2 id="objectives-title" class="sr-only">{{ __('contextual_validation.learning.objectives_title') }}</h2>
                @foreach (['one', 'two', 'three'] as $objective)
                    <article class="lab-card bg-indigo-50 p-5 sm:p-6"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-indigo-700">0{{ $loop->iteration }}</p><p class="mt-3 text-sm font-semibold leading-6 text-slate-900">{{ __('contextual_validation.learning.objective_'.$objective) }}</p></article>
                @endforeach
            </section>

            <section id="theory-heading" class="mt-10 grid gap-6" aria-labelledby="theory-title">
                <article class="lab-card bg-white p-6 shadow-sm sm:p-8">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('contextual_validation.learning.theory_label') }}</p>
                    <h2 id="theory-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.theory_title') }}</h2>
                    <p class="mt-5 text-base leading-7 text-slate-600">{{ __('contextual_validation.learning.theory_description') }}</p>
                    <div class="lab-theory-grid mt-7">@foreach (['transport', 'context', 'write'] as $concept)<div class="lab-theory-point"><h3 class="text-sm font-semibold text-slate-900">{{ __('contextual_validation.learning.theory_'.$concept) }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ __('contextual_validation.learning.theory_'.$concept.'_text') }}</p></div>@endforeach</div>
                </article>
                <article class="lab-card bg-slate-900 p-6 text-white shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-300">{{ __('contextual_validation.learning.invariant_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.invariant_title') }}</h2><pre class="lab-code-block mt-6 rounded-xl bg-white/5 p-5 font-mono text-xs leading-7 text-cyan-100 sm:text-sm"><code>{{ __('contextual_validation.learning.invariant_code') }}</code></pre><p class="mt-5 text-sm leading-6 text-slate-300">{{ __('contextual_validation.learning.invariant_description') }}</p></article>
            </section>

            <section id="code-flow" class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <article class="lab-card bg-white p-6 shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('contextual_validation.learning.flow_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.flow_title') }}</h2><ol class="mt-7 space-y-5">@foreach (['one', 'two', 'three', 'four', 'five'] as $step)<li class="flex gap-4"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-cyan-100 font-mono text-xs font-bold text-cyan-800">{{ $loop->iteration }}</span><p class="pt-1 text-sm leading-6 text-slate-600">{{ __('contextual_validation.learning.flow_'.$step) }}</p></li>@endforeach</ol></article>
                <article class="lab-card bg-white p-6 shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('contextual_validation.learning.implementation_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.implementation_title') }}</h2><p class="mt-4 text-sm leading-7 text-slate-600">{{ __('contextual_validation.learning.implementation_description') }}</p><dl class="mt-6 grid gap-3 text-xs"><div class="rounded-lg bg-slate-50 p-4"><dt class="font-semibold text-slate-500">{{ __('contextual_validation.learning.implementation_lock') }}</dt><dd class="mt-1 leading-5 text-slate-700">{{ __('contextual_validation.learning.implementation_lock_value') }}</dd></div><div class="rounded-lg bg-slate-50 p-4"><dt class="font-semibold text-slate-500">{{ __('contextual_validation.learning.implementation_constraint') }}</dt><dd class="mt-1 leading-5 text-slate-700">{{ __('contextual_validation.learning.implementation_constraint_value') }}</dd></div></dl></article>
            </section>

            <section id="code-examples" class="mt-10" aria-labelledby="code-examples-title">
                <div class="max-w-3xl">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ __('contextual_validation.learning.examples_label') }}</p>
                    <h2 id="code-examples-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.examples_title') }}</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ __('contextual_validation.learning.examples_description') }}</p>
                </div>
                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    @foreach (['request', 'controller', 'policy', 'database'] as $example)
                        <article class="lab-card overflow-hidden bg-slate-950 text-white shadow-sm">
                            <div class="p-5 sm:p-6">
                                <h3 class="text-lg font-semibold">{{ __('contextual_validation.learning.example_'.$example.'_title') }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-300">{{ __('contextual_validation.learning.example_'.$example.'_note') }}</p>
                            </div>
                            <pre class="lab-code-block overflow-x-auto border-t border-white/10 bg-black/20 p-5 font-mono text-xs leading-6 text-cyan-100 sm:p-6"><code>{{ __('contextual_validation.learning.example_'.$example.'_code') }}</code></pre>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="lab-card mt-6 bg-amber-50 p-6 sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-amber-700">{{ __('contextual_validation.learning.notes_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.notes_title') }}</h2><div class="lab-tradeoff mt-6">@foreach (['one' => 'safe', 'two' => 'cost', 'three' => 'limit'] as $note => $variant)<article class="lab-tradeoff-card lab-tradeoff-card--{{ $variant }}"><h3 class="text-sm font-semibold text-slate-900">{{ __('contextual_validation.learning.notes_'.$note.'_title') }}</h3><p class="mt-2 text-sm leading-6 text-slate-700">{{ __('contextual_validation.learning.notes_'.$note) }}</p></article>@endforeach</div></section>

            <section id="threat-heading" class="lab-card mt-10 bg-slate-950 p-6 text-white shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-rose-300">{{ __('contextual_validation.learning.threat_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.threat_title') }}</h2><ul class="lab-review-list mt-6 text-slate-300">@foreach (explode('|', __('contextual_validation.learning.threat_points')) as $point)<li>{{ $point }}</li>@endforeach</ul></section>

            <section class="mt-10 rounded-2xl bg-emerald-50 p-6 sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-emerald-700">{{ __('contextual_validation.learning.review_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('contextual_validation.learning.review_title') }}</h2><ul class="lab-severity text-slate-700 sm:grid-cols-2">@foreach (explode('|', __('contextual_validation.learning.review_points')) as $point)<li>{{ $point }}</li>@endforeach</ul></section>

            <div class="my-10 flex items-center gap-4" aria-hidden="true"><div class="h-px flex-1 bg-slate-200"></div><span class="rounded-full bg-white px-4 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ __('contextual_validation.form.title') }}</span><div class="h-px flex-1 bg-slate-200"></div></div>

            <section id="practice" aria-labelledby="practice-title"><div class="max-w-3xl"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ __('contextual_validation.form.title') }}</p><h2 id="practice-title" class="mt-2 text-3xl font-semibold tracking-[-0.03em]">{{ __('contextual_validation.form.submit') }}</h2><p class="mt-3 text-sm leading-6 text-slate-600">{{ __('contextual_validation.form.description') }}</p></div>
                @if (session('status'))<div role="status" class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
                @if ($errors->any())<div role="alert" class="mt-6 rounded-xl bg-red-50 px-4 py-3 text-sm leading-6 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <div class="mt-6 grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
                    <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-6"><h3 class="text-xl font-semibold">{{ __('contextual_validation.events.title') }}</h3><div class="mt-4 rounded-xl bg-cyan-50 px-4 py-3 text-sm leading-6 text-cyan-950"><p>{{ __('contextual_validation.events.how_to') }}</p><p class="mt-1 font-mono text-xs font-semibold text-cyan-800">{{ __('contextual_validation.events.invite_demo') }}</p></div><div class="mt-5 space-y-3">@foreach ($events as $event)<article class="rounded-xl bg-slate-50 p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div><h4 class="font-semibold text-slate-900">{{ $event->title }}</h4><p class="mt-1 text-xs text-slate-500">{{ __('contextual_validation.events.capacity', ['confirmed' => $event->confirmed_registrations_count, 'capacity' => $event->capacity]) }}</p></div><span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">{{ __('contextual_validation.events.status', ['status' => __('contextual_validation.enums.event_status.'.$event->status->value)]) }}</span></div><p class="mt-2 text-xs leading-5 text-slate-500">{{ __('contextual_validation.events.window', ['opens' => $event->registration_opens_at_utc->format('Y-m-d H:i'), 'closes' => $event->registration_closes_at_utc->format('Y-m-d H:i')]) }}</p><form method="POST" action="{{ route('labs.contextual-validation.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf<input type="hidden" name="event_id" value="{{ $event->id }}"><label for="ticket-type-{{ $event->id }}" class="text-xs font-semibold text-slate-600">{{ __('contextual_validation.form.ticket_type') }}<select id="ticket-type-{{ $event->id }}" name="ticket_type" class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100"><option value="standard" @selected(old('ticket_type', 'standard') === 'standard')>{{ __('contextual_validation.enums.ticket_type.standard') }}</option><option value="invite" @selected(old('ticket_type') === 'invite')>{{ __('contextual_validation.enums.ticket_type.invite') }}</option></select></label><label for="invite-code-{{ $event->id }}" class="text-xs font-semibold text-slate-600">{{ __('contextual_validation.form.invite_code') }}<input id="invite-code-{{ $event->id }}" name="invite_code" type="password" autocomplete="off" class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100"><span class="mt-1 block font-normal text-slate-500">{{ __('contextual_validation.form.invite_hint') }}</span></label><button type="submit" class="h-11 rounded-lg bg-[#102a43] px-4 text-sm font-semibold text-white hover:bg-[#173f60] focus:outline-none focus:ring-4 focus:ring-cyan-200 sm:col-span-2">{{ __('contextual_validation.form.submit') }}</button></form></article>@endforeach</div></section>
                    <aside class="rounded-2xl bg-cyan-50 p-5 sm:p-6"><h3 class="text-xl font-semibold">{{ __('contextual_validation.registrations.title') }}</h3>@if ($registrations->isEmpty())<p class="mt-5 text-sm text-slate-600">{{ __('contextual_validation.registrations.empty') }}</p>@else<div class="mt-5 space-y-3">@foreach ($registrations as $registration)<div class="rounded-xl bg-white/75 p-4 text-sm"><p class="font-semibold text-slate-900">{{ __('contextual_validation.registrations.registered', ['event' => $registration->event->title]) }}</p><p class="mt-1 text-xs text-slate-500">{{ __('contextual_validation.enums.ticket_type.'.$registration->ticket_type->value) }} · {{ __('contextual_validation.enums.registration_status.'.$registration->status->value) }}</p></div>@endforeach</div>@endif</aside>
                </div>
            </section>
        </main>
    </body>
</html>
