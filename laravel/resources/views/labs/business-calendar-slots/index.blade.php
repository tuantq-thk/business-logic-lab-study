<x-labs.shell :title="__('business_calendar_slots.page.title')" :description="__('business_calendar_slots.page.description')">
        <main id="main-content" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-10">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('business_calendar_slots.page.back') }}</a>
            <section class="mt-6 max-w-3xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('business_calendar_slots.page.eyebrow') }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">{{ __('business_calendar_slots.page.heading') }}</h1>
                <p class="mt-4 text-sm leading-6 text-slate-600">{{ __('business_calendar_slots.page.description') }}</p>
            </section>

            <x-labs.learning-overview lab="business-calendar-slots" />

            @if ($errors->any())
                <div role="alert" aria-live="assertive" class="mt-7 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><p class="font-semibold">{{ __('business_calendar_slots.errors.form_heading') }}</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @if ($generationError)<div role="alert" class="mt-7 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $generationError }}</div>@endif

            <section id="business-calendar-slots-practice" class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div><h2 class="text-lg font-semibold">{{ __('business_calendar_slots.form.title') }}</h2><p class="mt-1 text-xs text-slate-500">{{ __('business_calendar_slots.form.description') }}</p></div>
                    @if ($room)<p class="text-xs text-slate-500">{{ __('business_calendar_slots.form.timezone') }}: <span class="font-mono text-slate-700">{{ $room->timezone }}</span></p>@endif
                </div>

                <form method="GET" action="{{ route('labs.business-calendar-slots.index') }}" data-calendar-form class="grid gap-x-4 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2 xl:col-span-2"><label for="room-id" class="block text-xs font-semibold text-slate-600">{{ __('business_calendar_slots.form.room') }}</label><select id="room-id" name="room_id" @disabled($rooms->isEmpty()) class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-cyan-600 focus:ring-cyan-600">@forelse ($rooms as $candidate)<option value="{{ $candidate->id }}" @selected($room?->id === $candidate->id)>{{ __($candidate->name_key) }}</option>@empty<option>{{ __('business_calendar_slots.form.no_rooms') }}</option>@endforelse</select></div>
                    <div><label for="calendar-date" class="block text-xs font-semibold text-slate-600">{{ __('business_calendar_slots.form.date') }}</label><input id="calendar-date" type="date" name="date" value="{{ $date }}" required aria-invalid="{{ $errors->has('date') ? 'true' : 'false' }}" aria-describedby="calendar-date-help @error('date')calendar-date-error @enderror" @class(['mt-1.5 block h-11 w-full rounded-lg border bg-white px-3 text-sm focus:border-cyan-600 focus:ring-cyan-600', 'border-red-400' => $errors->has('date'), 'border-slate-200' => ! $errors->has('date')])><p id="calendar-date-help" class="mt-1.5 text-[11px] text-slate-500">{{ __('business_calendar_slots.form.date_hint') }}</p>@error('date')<p id="calendar-date-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="slot-duration" class="block text-xs font-semibold text-slate-600">{{ __('business_calendar_slots.form.duration') }}</label><input id="slot-duration" type="number" name="duration" min="15" max="480" value="{{ $duration }}" required aria-invalid="{{ $errors->has('duration') ? 'true' : 'false' }}" aria-describedby="slot-duration-help @error('duration')slot-duration-error @enderror" @class(['mt-1.5 block h-11 w-full rounded-lg border bg-white px-3 text-sm focus:border-cyan-600 focus:ring-cyan-600', 'border-red-400' => $errors->has('duration'), 'border-slate-200' => ! $errors->has('duration')])><p id="slot-duration-help" class="mt-1.5 text-[11px] text-slate-500">{{ __('business_calendar_slots.form.duration_hint') }}</p>@error('duration')<p id="slot-duration-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="slot-step" class="block text-xs font-semibold text-slate-600">{{ __('business_calendar_slots.form.step') }}</label><input id="slot-step" type="number" name="step" min="5" max="120" value="{{ $step }}" required aria-invalid="{{ $errors->has('step') ? 'true' : 'false' }}" aria-describedby="slot-step-help @error('step')slot-step-error @enderror" @class(['mt-1.5 block h-11 w-full rounded-lg border bg-white px-3 text-sm focus:border-cyan-600 focus:ring-cyan-600', 'border-red-400' => $errors->has('step'), 'border-slate-200' => ! $errors->has('step')])><p id="slot-step-help" class="mt-1.5 text-[11px] text-slate-500">{{ __('business_calendar_slots.form.step_hint') }}</p>@error('step')<p id="slot-step-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                    <div class="md:col-span-2 xl:col-span-4 xl:flex xl:justify-end"><button type="submit" data-calendar-submit data-loading-label="{{ __('business_calendar_slots.page.loading') }}" @disabled($rooms->isEmpty()) class="mt-1 h-11 w-full rounded-lg bg-[#102a43] px-5 text-sm font-semibold text-white transition hover:bg-[#173f60] disabled:cursor-not-allowed disabled:opacity-50 xl:w-auto">{{ __('business_calendar_slots.form.submit') }}</button></div>
                </form>
                <p data-calendar-status class="sr-only" aria-live="polite"></p>
            </section>

            @if ($rooms->isEmpty())<p role="status" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">{{ __('business_calendar_slots.form.no_rooms') }}</p>@endif

            <section class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.7fr)] lg:items-start">
                <aside class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 sm:p-6">
                    <p class="font-mono text-[10px] uppercase tracking-wider text-cyan-700">{{ __('business_calendar_slots.slots.eyebrow') }}</p>
                    <div class="mt-2 flex items-baseline justify-between gap-3"><h2 class="text-xl font-semibold">{{ __('business_calendar_slots.rules.title') }}</h2><span class="rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold text-cyan-800">{{ trans_choice('business_calendar_slots.slots.count', count($slots), ['count' => count($slots)]) }}</span></div>
                    <ul class="mt-5 space-y-3 text-sm leading-5 text-slate-700"><li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-cyan-600"></span>{{ __('business_calendar_slots.rules.weekly') }}</li><li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-cyan-600"></span>{{ __('business_calendar_slots.rules.override') }}</li><li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-cyan-600"></span>{{ __('business_calendar_slots.rules.containment') }}</li><li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-cyan-600"></span>{{ __('business_calendar_slots.rules.past') }}</li></ul>
                </aside>

                <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">{{ __('business_calendar_slots.slots.local') }}</p><h2 class="mt-1 text-xl font-semibold">{{ __('business_calendar_slots.slots.title') }}</h2></div><time class="font-mono text-xs text-slate-500" datetime="{{ $date }}">{{ $date }}</time></div>
                    @if (count($slots) === 0)<p class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm leading-6 text-slate-500">{{ __('business_calendar_slots.slots.empty') }}</p>@else<div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">@foreach ($slots as $slot)<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3.5 transition hover:-translate-y-0.5 hover:shadow-sm"><p class="font-mono text-sm font-semibold text-emerald-900">{{ $slot->startsAt->format('H:i') }} – {{ $slot->endsAt->format('H:i') }}</p><p class="mt-1.5 text-xs text-emerald-700">{{ __('business_calendar_slots.slots.local') }}</p></div>@endforeach</div>@endif
                </section>
            </section>
        </main>
</x-labs.shell>
