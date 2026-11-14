@props(['lab'])

@php($learning = trans('lab_learning.'.$lab))

<section class="mt-10" aria-labelledby="{{ $lab }}-learning-title">
    <div class="lab-path-intro"><p class="lab-section-flag">{{ $learning['path_title'] }}</p><p class="mt-2 text-sm text-slate-600">{{ $learning['path_description'] }}</p></div>
    <x-labs.mini-toc :lab="$lab" />
</section>

<section id="{{ $lab }}-objectives" class="mt-8 grid gap-4 md:grid-cols-3" aria-labelledby="{{ $lab }}-learning-title">
    <h2 id="{{ $lab }}-learning-title" class="sr-only">{{ $learning['objectives_title'] }}</h2>
    @foreach ($learning['objectives'] as $objective)
        <article class="lab-card bg-indigo-50 p-5 sm:p-6"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-indigo-700">{{ $objective['label'] }}</p><h3 class="mt-2 text-base font-semibold leading-6 text-slate-900">{{ $objective['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $objective['description'] }}</p></article>
    @endforeach
</section>

<section id="{{ $lab }}-theory" class="mt-8 grid gap-6" aria-labelledby="{{ $lab }}-theory-title">
    <article class="lab-card bg-white p-6 shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $learning['theory_label'] }}</p><h2 id="{{ $lab }}-theory-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['theory_title'] }}</h2><p class="mt-4 text-base leading-7 text-slate-600">{{ $learning['theory_description'] }}</p><div class="lab-theory-grid mt-7">@foreach ($learning['concepts'] as $concept)<div class="lab-theory-point"><h3 class="text-sm font-semibold text-slate-900">{{ $concept['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $concept['description'] }}</p></div>@endforeach</div></article>
    <article id="{{ $lab }}-invariant" class="lab-card bg-slate-900 p-6 text-white shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-300">{{ $learning['invariant_label'] }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['invariant_title'] }}</h2><pre class="lab-code-block mt-6 rounded-xl bg-white/5 p-5 font-mono text-xs leading-7 text-cyan-100 sm:text-sm"><code>{{ $learning['invariant_code'] }}</code></pre><p class="mt-5 text-sm leading-6 text-slate-300">{{ $learning['invariant_description'] }}</p></article>
</section>

<section id="{{ $lab }}-flow" class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]" aria-labelledby="{{ $lab }}-flow-title">
    <article class="lab-card bg-white p-6 shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $learning['flow_label'] }}</p><h2 id="{{ $lab }}-flow-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['flow_title'] }}</h2><ol class="mt-7 space-y-5">@foreach ($learning['flow'] as $step)<li class="flex gap-4"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-cyan-100 font-mono text-xs font-bold text-cyan-800">{{ $loop->iteration }}</span><div><h3 class="text-sm font-semibold leading-6 text-slate-900">{{ $step['title'] }}</h3><p class="mt-1 text-sm leading-6 text-slate-600">{{ $step['description'] }}</p></div></li>@endforeach</ol></article>
    <article id="{{ $lab }}-implementation" class="lab-card bg-white p-6 shadow-sm sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $learning['implementation_label'] }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['implementation_title'] }}</h2><p class="mt-4 text-sm leading-7 text-slate-600">{{ $learning['implementation_description'] }}</p><dl class="mt-6 grid gap-3 text-xs">@foreach ($learning['boundaries'] as $boundary)<div class="rounded-lg bg-slate-50 p-4"><dt class="font-semibold text-slate-500">{{ $boundary['label'] }}</dt><dd class="mt-1 leading-5 text-slate-700">{{ $boundary['value'] }}</dd></div>@endforeach</dl></article>
</section>

<x-labs.layer-map :lab="$lab" />

<x-labs.production-learning :lab="$lab" phase="foundation" />

<section id="{{ $lab }}-examples" class="mt-8" aria-labelledby="{{ $lab }}-examples-title"><div class="max-w-3xl"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ $learning['examples_label'] }}</p><h2 id="{{ $lab }}-examples-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['examples_title'] }}</h2></div><div class="mt-5 grid gap-5 lg:grid-cols-2">@foreach ($learning['examples'] as $example)@php($layerNumber = str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT))<article class="lab-card overflow-hidden bg-slate-950 text-white"><div class="p-5"><h3 class="text-base font-semibold">{{ $example['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-300">{{ $example['description'] }}</p></div><pre class="lab-code-block overflow-x-auto border-t border-white/10 bg-black/20 p-5 font-mono text-xs leading-6 text-cyan-100"><code>{{ $example['code'] }}</code></pre></article>@endforeach</div></section>

<x-labs.production-learning :lab="$lab" phase="evidence" />

<section id="{{ $lab }}-tradeoffs" class="mt-8 grid gap-6 lg:grid-cols-2"><article class="lab-card bg-amber-50 p-6 sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-amber-700">{{ $learning['tradeoff_label'] }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['tradeoff_title'] }}</h2><div class="mt-6 grid gap-3">@foreach ($learning['tradeoffs'] as $tradeoff)<article class="lab-tradeoff-card {{ $tradeoff['class'] }}"><h3 class="text-sm font-semibold text-slate-900">{{ $tradeoff['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-700">{{ $tradeoff['description'] }}</p></article>@endforeach</div></article><article id="{{ $lab }}-threats" class="lab-card bg-slate-950 p-6 text-white sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-rose-300">{{ $learning['threat_label'] }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['threat_title'] }}</h2><ul class="lab-review-list mt-6 text-slate-300">@foreach ($learning['threats'] as $threat)<li>{{ $threat }}</li>@endforeach</ul></article></section>

<x-labs.production-learning :lab="$lab" phase="tail" />

<section id="{{ $lab }}-review" class="mt-8 rounded-2xl bg-emerald-50 p-6 sm:p-8"><p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-emerald-700">{{ $learning['review_label'] }}</p><h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ $learning['review_title'] }}</h2><ul class="lab-severity mt-5 text-slate-700 sm:grid-cols-2">@foreach ($learning['review'] as $item)<li>{{ $item }}</li>@endforeach</ul></section>
