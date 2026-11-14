<x-labs.shell
    :title="__('shell.lab.title', ['label' => __($lab->labelKey), 'brand' => __('shell.brand')])"
    :description="__($lab->descriptionKey)"
>
        <main id="main-content" class="mx-auto max-w-5xl px-5 py-12 sm:px-8">
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
</x-labs.shell>
