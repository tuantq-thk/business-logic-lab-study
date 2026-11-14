<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('booking_overlap.page.title') }}</title>
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
            <a href="{{ route('dashboard') }}" class="inline-flex text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('booking_overlap.page.back') }}</a>

            <section class="lab-reading-column mt-6">
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-700"><span>BL-05</span><span class="text-slate-300">/</span><span>{{ __('booking_overlap.page.eyebrow') }}</span><span class="rounded-full bg-amber-100 px-2.5 py-1 tracking-normal text-amber-800">{{ __('booking_overlap.learning.level') }}</span></div>
                <h1 class="mt-4 max-w-5xl text-3xl font-semibold leading-tight tracking-[-0.04em] sm:text-5xl">{{ __('booking_overlap.page.heading') }}</h1>
                <p class="lab-card-copy mt-5 text-base leading-7 text-slate-600">{{ __('booking_overlap.page.description') }}</p>
            </section>

            <section class="mt-8" aria-labelledby="learning-path-heading">
                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="lab-section-flag">{{ __('booking_overlap.learning.path_title') }}</p>
                        <p id="learning-path-heading" class="mt-2 text-sm text-slate-600">{{ __('booking_overlap.learning.path_description') }}</p>
                    </div>
                </div>
                <div class="lab-path">
                    @foreach (['one' => 'learning-objectives', 'two' => 'theory-heading', 'three' => 'code-flow', 'four' => 'threat-heading', 'five' => 'practice'] as $pathStep => $target)
                        <div class="min-w-0 flex-1">
                            <a href="#{{ $target }}" class="lab-path-step block no-underline">
                                <span class="lab-path-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="mt-1 min-w-0 text-sm font-semibold text-slate-800 md:mt-2">{{ __('booking_overlap.learning.path_'.$pathStep) }}</span>
                            </a>
                        </div>
                        @if (! $loop->last)
                            <div class="lab-path-connector" aria-hidden="true"></div>
                        @endif
                    @endforeach
                </div>
            </section>

            <section class="mt-10 grid gap-4 md:grid-cols-3" aria-labelledby="learning-objectives">
                <h2 id="learning-objectives" class="sr-only">{{ __('booking_overlap.learning.objectives_title') }}</h2>
                @foreach (['one', 'two', 'three'] as $objective)
                    <article class="lab-card border border-indigo-200 bg-indigo-50 p-5 sm:p-6"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-indigo-700">{{ __('booking_overlap.learning.objective_'.$objective.'_label') }}</p><h3 class="mt-2 text-base font-semibold leading-6 text-slate-900">{{ __('booking_overlap.learning.objective_'.$objective.'_title') }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ __('booking_overlap.learning.objective_'.$objective.'_description') }}</p></article>
                @endforeach
            </section>

            <section class="mt-10 grid gap-6" aria-labelledby="theory-heading">
                <article class="lab-card border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-col gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('booking_overlap.learning.theory_label') }}</p>
                            <h2 id="theory-heading" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.theory_title') }}</h2>
                        </div>
                        <span class="w-fit shrink-0 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">{{ __('booking_overlap.learning.theory_badge') }}</span>
                    </div>
                    <p class="lab-card-copy mt-6 text-base leading-7 text-slate-600">{{ __('booking_overlap.learning.theory_description') }}</p>
                    <div class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50/70 p-5">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-indigo-700">{{ __('booking_overlap.learning.theory_principle_label') }}</p>
                        <p class="mt-2 text-sm font-medium leading-7 text-indigo-950">{{ __('booking_overlap.learning.theory_principle') }}</p>
                    </div>
                    <div class="lab-theory-grid mt-8">
                        @foreach (['interval', 'resource', 'state'] as $item)
                            <div class="lab-theory-point">
                                <h3 class="text-sm font-semibold leading-6 text-slate-900">{{ __('booking_overlap.learning.theory_'.$item.'_title') }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('booking_overlap.learning.theory_'.$item) }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="lab-card border border-slate-900 bg-slate-900 p-6 text-white shadow-sm sm:p-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-300">{{ __('booking_overlap.learning.invariant_label') }}</p>
                            <h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.invariant_title') }}</h2>
                        </div>
                        <span class="text-xs text-slate-400">{{ __('booking_overlap.learning.theory_badge') }}</span>
                    </div>
                    <pre class="lab-code-block mt-6 rounded-xl border border-white/10 bg-white/5 p-5 font-mono text-xs leading-7 text-cyan-100 sm:text-sm"><code>{{ __('booking_overlap.learning.invariant_code') }}</code></pre>
                    <p class="lab-card-copy mt-5 text-sm leading-6 text-slate-300">{{ __('booking_overlap.learning.invariant_description') }}</p>
                </article>
            </section>

            <section id="code-flow" class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <article class="lab-card border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('booking_overlap.learning.flow_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.flow_title') }}</h2></div><span class="text-xs text-slate-400">{{ __('booking_overlap.learning.flow_badge') }}</span></div><ol class="mt-7 space-y-5">@foreach (['one', 'two', 'three', 'four', 'five'] as $step)<li class="flex gap-4"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-cyan-100 font-mono text-xs font-bold text-cyan-800">{{ $loop->iteration }}</span><div class="min-w-0"><h3 class="text-sm font-semibold leading-6 text-slate-900">{{ __('booking_overlap.learning.flow_'.$step.'_title') }}</h3><p class="mt-1 text-sm leading-6 text-slate-600">{{ __('booking_overlap.learning.flow_'.$step) }}</p></div></li>@endforeach</ol></article>
                <article class="lab-card border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('booking_overlap.learning.implementation_label') }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.implementation_title') }}</h2><p class="lab-card-copy mt-4 text-sm leading-7 text-slate-600">{{ __('booking_overlap.learning.implementation_description') }}</p><pre class="lab-code-block mt-6 rounded-xl bg-slate-900 p-5 font-mono text-xs leading-7 text-emerald-100"><code>DB::transaction(function () {
    lock room row
    check active overlap
    insert booking
}, attempts: 3);</code></pre><dl class="mt-6 grid gap-3 text-xs sm:grid-cols-2"><div class="rounded-lg bg-slate-50 p-4"><dt class="font-semibold text-slate-500">{{ __('booking_overlap.learning.implementation_lock') }}</dt><dd class="mt-1 leading-5 text-slate-700">{{ __('booking_overlap.learning.implementation_lock_value') }}</dd></div><div class="rounded-lg bg-slate-50 p-4"><dt class="font-semibold text-slate-500">{{ __('booking_overlap.learning.implementation_retry') }}</dt><dd class="mt-1 leading-5 text-slate-700">{{ __('booking_overlap.learning.implementation_retry_value') }}</dd></div></dl></article>
            </section>

            <section class="lab-card mt-6 border border-amber-200 bg-amber-50 p-6 sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-amber-700">{{ __('booking_overlap.learning.notes_label') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.notes_title') }}</h2>
                    </div>
                    <span class="w-fit rounded-full bg-white/70 px-3 py-1 text-xs font-semibold text-amber-800">{{ __('booking_overlap.learning.notes_badge') }}</span>
                </div>
                <div class="mt-5 rounded-xl border border-amber-200 bg-white/60 p-5">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-amber-700">{{ __('booking_overlap.learning.notes_decision_label') }}</p>
                    <p class="mt-2 text-sm leading-7 text-slate-700">{{ __('booking_overlap.learning.notes_decision') }}</p>
                </div>
                <div class="lab-tradeoff">
                    @foreach (['one' => 'safe', 'two' => 'cost', 'three' => 'limit'] as $note => $variant)
                        <article class="lab-tradeoff-card lab-tradeoff-card--{{ $variant }}">
                            <p class="text-sm font-semibold text-slate-900">{{ __('booking_overlap.learning.notes_'.$note.'_title') }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ __('booking_overlap.learning.notes_'.$note) }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mt-10" aria-labelledby="maturity-heading">
                <div class="lab-reading-column">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-indigo-700">{{ __('booking_overlap.learning.maturity_label') }}</p>
                    <h2 id="maturity-heading" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.maturity_title') }}</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ __('booking_overlap.learning.maturity_description') }}</p>
                </div>
                <div class="lab-review-grid lab-review-grid--three mt-6">
                    @foreach (['baseline', 'operational', 'evidence'] as $level)
                        <article class="lab-review-card {{ $level === 'operational' ? 'lab-review-card--accent' : '' }}">
                            <span class="lab-review-label {{ $level === 'evidence' ? 'bg-slate-900 text-cyan-200' : 'bg-white text-indigo-700' }}">{{ __('booking_overlap.learning.maturity_'.$level.'_label') }}</span>
                            <h3 class="mt-4 text-lg font-semibold leading-6 text-slate-900">{{ __('booking_overlap.learning.maturity_'.$level.'_title') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('booking_overlap.learning.maturity_'.$level.'_description') }}</p>
                            <ul class="lab-review-list text-slate-700">
                                @foreach (explode('|', __('booking_overlap.learning.maturity_'.$level.'_points')) as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="lab-card mt-10 border border-slate-800 bg-slate-950 p-6 text-white shadow-sm sm:p-8" aria-labelledby="threat-heading">
                <div class="max-w-4xl">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-rose-300">{{ __('booking_overlap.learning.threat_label') }}</p>
                    <h2 id="threat-heading" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.threat_title') }}</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-300">{{ __('booking_overlap.learning.threat_description') }}</p>
                </div>
                <div class="lab-review-grid lab-review-grid--two mt-7">
                    @foreach (['attack', 'defense'] as $side)
                        <article class="rounded-xl border border-white/10 bg-white/5 p-5">
                            <h3 class="text-base font-semibold text-white">{{ __('booking_overlap.learning.threat_'.$side.'_title') }}</h3>
                            <ul class="lab-review-list text-slate-300">
                                @foreach (explode('|', __('booking_overlap.learning.threat_'.$side.'_points')) as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mt-10" aria-labelledby="pitfall-heading">
                <div class="lab-reading-column">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-orange-700">{{ __('booking_overlap.learning.pitfall_label') }}</p>
                    <h2 id="pitfall-heading" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.pitfall_title') }}</h2>
                </div>
                <div class="lab-review-grid lab-review-grid--two mt-6">
                    @foreach (['1', '2', '3', '4'] as $pitfall)
                        <article class="lab-review-card lab-review-card--risk">
                            <h3 class="text-base font-semibold leading-6 text-slate-900">{{ __('booking_overlap.learning.pitfall_'.$pitfall.'_title') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ __('booking_overlap.learning.pitfall_'.$pitfall.'_description') }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mt-10 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 sm:p-8" aria-labelledby="review-heading">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-emerald-700">{{ __('booking_overlap.learning.review_label') }}</p>
                <h2 id="review-heading" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('booking_overlap.learning.review_title') }}</h2>
                <ul class="lab-severity text-slate-700 sm:grid-cols-2">
                    @foreach (explode('|', __('booking_overlap.learning.review_points')) as $point)
                        <li class="border-l-2 border-emerald-500 pl-4 text-sm leading-6">{{ $point }}</li>
                    @endforeach
                </ul>
            </section>

            <div class="my-10 flex items-center gap-4" aria-hidden="true"><div class="h-px flex-1 bg-slate-200"></div><span class="rounded-full border border-slate-200 bg-white px-4 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ __('booking_overlap.practice.divider') }}</span><div class="h-px flex-1 bg-slate-200"></div></div>

            <section id="practice" aria-labelledby="practice-title"><div class="max-w-3xl"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ __('booking_overlap.practice.eyebrow') }}</p><h2 id="practice-title" class="mt-2 text-3xl font-semibold tracking-[-0.03em]">{{ __('booking_overlap.practice.title') }}</h2><p class="mt-3 text-sm leading-6 text-slate-600">{{ __('booking_overlap.practice.description') }}</p></div>

                @if (session('status'))<div role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
                @if ($errors->any())<div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="mb-5"><h3 class="text-lg font-semibold">{{ __('booking_overlap.form.title') }}</h3><p class="mt-1 text-xs text-slate-500">{{ __('booking_overlap.form.description') }}</p></div><form method="GET" action="{{ route('labs.booking-overlap.index') }}" class="grid gap-4 md:grid-cols-[1.4fr_1fr_auto] md:items-end"><div><label for="booking-room" class="block text-xs font-semibold text-slate-600">{{ __('booking_overlap.form.room') }}</label><select id="booking-room" name="room_id" class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-cyan-600 focus:ring-cyan-600">@foreach ($rooms as $candidate)<option value="{{ $candidate->id }}" @selected($room?->id === $candidate->id)>{{ __($candidate->name_key) }}</option>@endforeach</select></div><div><label for="booking-date" class="block text-xs font-semibold text-slate-600">{{ __('booking_overlap.form.date') }}</label><input id="booking-date" type="date" name="date" value="{{ $date }}" required class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:ring-cyan-600"></div><button type="submit" class="h-11 rounded-lg bg-[#102a43] px-5 text-sm font-semibold text-white hover:bg-[#173f60]">{{ __('booking_overlap.form.apply') }}</button></form></section>

                <section class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start"><div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex items-end justify-between gap-3 border-b border-slate-100 pb-4"><div><p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">{{ __('booking_overlap.slots.local') }}</p><h3 class="mt-1 text-xl font-semibold">{{ __('booking_overlap.slots.title') }}</h3></div><time class="font-mono text-xs text-slate-500" datetime="{{ $date }}">{{ $date }}</time></div>@if (count($slots) === 0)<p class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">{{ __('booking_overlap.slots.empty') }}</p>@else<div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">@foreach ($slots as $slot)<form method="POST" action="{{ route('labs.booking-overlap.store') }}" data-calendar-form class="min-w-0">@csrf<input type="hidden" name="room_id" value="{{ $room->id }}"><input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="start" value="{{ $slot->startsAt->format('H:i') }}"><input type="hidden" name="duration" value="{{ $duration }}"><input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><button type="submit" data-calendar-submit data-loading-label="{{ __('booking_overlap.page.book') }}" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3.5 text-left transition hover:-translate-y-0.5 hover:border-emerald-400 hover:shadow-sm"><span class="block font-mono text-sm font-semibold text-emerald-900">{{ $slot->startsAt->format('H:i') }} – {{ $slot->endsAt->format('H:i') }}</span><span class="mt-1.5 block text-xs text-emerald-700">{{ __('booking_overlap.page.book') }}</span></button></form>@endforeach</div>@endif</div><aside class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 sm:p-6"><h3 class="text-xl font-semibold">{{ __('booking_overlap.bookings.title') }}</h3>@if ($bookings->isEmpty())<p class="mt-5 rounded-xl border border-dashed border-cyan-300 bg-white/50 p-5 text-sm text-slate-600">{{ __('booking_overlap.bookings.empty') }}</p>@else<div class="mt-5 space-y-3">@foreach ($bookings as $booking)<div class="rounded-xl border border-white/80 bg-white/75 p-4"><p class="font-mono text-sm font-semibold text-slate-900">{{ $booking->starts_at_utc->setTimezone($room->timezone)->format('Y-m-d H:i') }} – {{ $booking->ends_at_utc->setTimezone($room->timezone)->format('H:i') }}</p><p class="mt-1 text-xs text-slate-600">{{ __('booking_overlap.bookings.by', ['name' => $booking->user->name]) }}</p><p class="mt-1 text-xs text-slate-500">{{ __('booking_overlap.bookings.status', ['status' => $booking->status->value]) }}</p>@can('cancel', $booking)<form method="POST" action="{{ route('labs.booking-overlap.cancel', $booking) }}" class="mt-3">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-700 hover:text-red-900">{{ __('booking_overlap.page.cancel') }}</button></form>@endcan</div>@endforeach</div>@endif</aside></section>
            </section>
        </main>
    </body>
</html>
