<section id="{{ $lab }}-architecture" class="lab-layer-map mt-8" aria-labelledby="{{ $lab }}-layer-map-title">
    <div class="max-w-3xl">
        <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ __('lab_learning.layers.label') }}</p>
        <h2 id="{{ $lab }}-layer-map-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">{{ __('lab_learning.layers.title') }}</h2>
        <p class="mt-3 text-sm leading-7 text-slate-600">{{ __('lab_learning.layers.description') }}</p>
    </div>
    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (trans('lab_learning.layers.items') as $layer)
            <article class="lab-layer-card">
                <span class="lab-layer-index">{{ $layer['number'] }}</span>
                <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $layer['title'] }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $layer['purpose'] }}</p>
                <pre class="lab-code-block mt-4 rounded-lg bg-slate-950 p-3 font-mono text-[11px] leading-5 text-cyan-100"><code>{{ $layer['code'] }}</code></pre>
                <p class="mt-3 text-xs font-medium leading-5 text-slate-500"><span class="font-semibold text-slate-700">{{ __('lab_learning.layers.avoid') }}:</span> {{ $layer['avoid'] }}</p>
            </article>
        @endforeach
    </div>
</section>
