@props(['lab', 'phase' => 'all'])

@php($learning = trans('lab_advanced.'.$lab))

@if ($phase === 'all' || $phase === 'foundation')
<section class="mt-8 grid gap-6" aria-labelledby="{{ $lab }}-data-contract-title">
    <article id="{{ $lab }}-data-contract" class="lab-card bg-white p-6 shadow-sm sm:p-8">
        <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ $learning['data_label'] }}</p>
        <h2 id="{{ $lab }}-data-contract-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['data_title'] }}</h2>
        <p class="mt-4 max-w-4xl text-sm leading-7 text-slate-600">{{ $learning['data_description'] }}</p>
        <div class="mt-6 overflow-x-auto rounded-xl bg-slate-50">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3 font-semibold">{{ __('lab_advanced.shared.element') }}</th><th class="px-4 py-3 font-semibold">{{ __('lab_advanced.shared.contract') }}</th><th class="px-4 py-3 font-semibold">{{ __('lab_advanced.shared.reason') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @foreach ($learning['data_rows'] as $row)
                        <tr><th scope="row" class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $row['element'] }}</th><td class="px-4 py-3 leading-6">{{ $row['contract'] }}</td><td class="px-4 py-3 leading-6">{{ $row['reason'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
</section>

<section id="{{ $lab }}-decision-table" class="mt-8" aria-labelledby="{{ $lab }}-decision-title">
    <div class="max-w-4xl"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ $learning['decision_label'] }}</p><h2 id="{{ $lab }}-decision-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['decision_title'] }}</h2><p class="mt-3 text-sm leading-7 text-slate-600">{{ $learning['decision_description'] }}</p></div>
    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        @foreach ($learning['decision_rows'] as $row)
            <article class="lab-card bg-indigo-50 p-5"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-indigo-700">{{ $row['when'] }}</p><h3 class="mt-2 text-base font-semibold leading-6 text-slate-900">{{ $row['result'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $row['evidence'] }}</p></article>
        @endforeach
    </div>
</section>
@endif

@if ($phase === 'all' || $phase === 'evidence')
<section id="{{ $lab }}-test-evidence" class="mt-8 grid gap-6 lg:grid-cols-[0.8fr_1.2fr]" aria-labelledby="{{ $lab }}-test-title">
    <article class="lab-card bg-emerald-50 p-6 sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-emerald-700">{{ $learning['test_label'] }}</p><h2 id="{{ $lab }}-test-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['test_title'] }}</h2><p class="mt-4 text-sm leading-7 text-slate-700">{{ $learning['test_description'] }}</p><div class="mt-5 flex flex-wrap gap-2 text-[10px] font-semibold"><span class="rounded-full bg-white px-2.5 py-1 text-emerald-800">{{ __('lab_advanced.shared.verified_automated') }}</span><span class="rounded-full bg-white px-2.5 py-1 text-amber-800">{{ __('lab_advanced.shared.conceptual_requirement') }}</span><span class="rounded-full bg-white px-2.5 py-1 text-slate-600">{{ __('lab_advanced.shared.not_covered') }}</span></div><ul class="lab-review-list mt-6 text-slate-700">@foreach ($learning['test_principles'] as $principle)<li>{{ $principle }}</li>@endforeach</ul></article>
    <div class="grid gap-3">
        @foreach ($learning['test_rows'] as $row)
            <article class="rounded-xl bg-white p-4 shadow-sm"><div class="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between"><div><h3 class="text-sm font-semibold text-slate-900">{{ $row['scenario'] }}</h3><span class="mt-1 inline-block font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $row['level'] }}</span></div><span class="lab-evidence-status lab-evidence-status--{{ $row['status'] }}">{{ __('lab_advanced.evidence_status.'.$row['status']) }}</span></div><p class="mt-2 text-sm leading-6 text-slate-600">{{ $row['assertion'] }}</p></article>
        @endforeach
    </div>
</section>

<section id="{{ $lab }}-performance" class="mt-8 grid gap-6 lg:grid-cols-2" aria-labelledby="{{ $lab }}-performance-title">
    <article class="lab-card bg-slate-900 p-6 text-white sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-300">{{ $learning['performance_label'] }}</p><h2 id="{{ $lab }}-performance-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['performance_title'] }}</h2><p class="mt-4 text-sm leading-7 text-slate-300">{{ $learning['performance_description'] }}</p></article>
    <article class="lab-card bg-white p-6 shadow-sm sm:p-8"><h3 class="text-base font-semibold text-slate-900">{{ __('lab_advanced.shared.measure_before_optimize') }}</h3><ul class="lab-review-list mt-4 text-sm text-slate-600">@foreach ($learning['performance_points'] as $point)<li>{{ $point }}</li>@endforeach</ul></article>
</section>
@endif

@if ($phase === 'all' || $phase === 'tail')
<section id="{{ $lab }}-pitfalls" class="mt-8" aria-labelledby="{{ $lab }}-pitfalls-title">
    <div class="max-w-4xl"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-amber-700">{{ $learning['pitfall_label'] }}</p><h2 id="{{ $lab }}-pitfalls-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['pitfall_title'] }}</h2></div>
    <div class="mt-5 grid gap-4 md:grid-cols-3">@foreach ($learning['pitfalls'] as $pitfall)<article class="lab-tradeoff-card lab-tradeoff-card--pitfall"><h3 class="text-sm font-semibold text-slate-900">{{ $pitfall['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-700">{{ $pitfall['description'] }}</p></article>@endforeach</div>
</section>
<section id="{{ $lab }}-maturity" class="mt-8" aria-labelledby="{{ $lab }}-maturity-title">
    <div class="max-w-4xl"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-violet-700">{{ $learning['maturity_label'] }}</p><h2 id="{{ $lab }}-maturity-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['maturity_title'] }}</h2></div>
    <div class="mt-5 grid gap-4 md:grid-cols-3">@foreach ($learning['maturity'] as $level)<article class="lab-card bg-violet-50 p-5"><span class="font-mono text-[10px] font-semibold uppercase tracking-wider text-violet-700">{{ $level['level'] }}</span><h3 class="mt-2 text-base font-semibold text-slate-900">{{ $level['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $level['description'] }}</p></article>@endforeach</div>
</section>

@endif
@if ($phase === 'all' || $phase === 'tail' || $phase === 'observability')
<section id="{{ $lab }}-observability" class="mt-8 grid gap-6 lg:grid-cols-3" aria-labelledby="{{ $lab }}-observability-title">
    <div class="lg:col-span-3"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ $learning['observability_label'] }}</p><h2 id="{{ $lab }}-observability-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['observability_title'] }}</h2><p class="mt-3 max-w-4xl text-sm leading-7 text-slate-600">{{ $learning['observability_description'] }}</p></div>
    @foreach ($learning['observability'] as $item)<article class="lab-card bg-white p-5 shadow-sm"><h3 class="text-sm font-semibold text-slate-900">{{ $item['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['description'] }}</p></article>@endforeach
</section>
<section id="{{ $lab }}-limitations" class="mt-8 rounded-2xl bg-slate-100 p-6 sm:p-8" aria-labelledby="{{ $lab }}-limitations-title">
    <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ __('lab_advanced.shared.limitations_label') }}</p>
    <h2 id="{{ $lab }}-limitations-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('lab_advanced.shared.limitations_title') }}</h2>
    <p class="mt-3 max-w-4xl text-sm leading-7 text-slate-600">{{ __('lab_advanced.shared.limitations_description') }}</p>
    <ul class="lab-review-list mt-5 text-slate-700">@foreach (__('lab_advanced.domain_limitations.'.$lab) as $limitation)<li>{{ $limitation }}</li>@endforeach</ul>
</section>
@endif
