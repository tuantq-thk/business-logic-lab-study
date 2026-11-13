<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('catalog.title') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('catalog.brand') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('shell.logout') }}</button></form>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-5 py-12 sm:px-8">
            <div class="max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('catalog.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em]">{{ __('catalog.heading') }}</h1>
                <p class="mt-4 text-base leading-7 text-slate-600">{{ __('catalog.description') }}</p>
            </div>
            @if (session('status'))<div role="status" class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
            @if ($errors->any())<div role="alert" class="mt-8 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

            <section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-6 border-b border-slate-100 pb-7 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-3"><h2 class="text-xl font-semibold">{{ __($catalogSetting->label_key ?? 'catalog.global.label') }}</h2><span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-800">{{ __($catalogSetting->group_key ?? 'catalog.global.group') }}</span></div>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">{{ __($catalogSetting->description_key ?? 'catalog.global.description') }}</p>
                    </div>
                    <div class="text-left sm:text-right"><p class="text-xs uppercase tracking-wider text-slate-400">{{ __('catalog.effective_state') }}</p><p class="mt-1 text-lg font-semibold {{ $catalogSetting->isEnabled() ? 'text-emerald-700' : 'text-slate-700' }}">{{ $catalogSetting->isEnabled() ? __('catalog.enabled') : __('catalog.disabled') }}</p><p class="font-mono text-xs text-slate-400">{{ __('catalog.source', ['source' => $catalogSetting->stateSource()]) }}</p></div>
                </div>
                <form method="POST" action="{{ route('admin.catalog.update') }}" class="mt-7 space-y-6">
                    @csrf
                    @method('PATCH')
                    <label class="flex cursor-pointer items-start gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-cyan-300"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked($catalogSetting->isEnabled()) class="mt-1 h-5 w-5 rounded border-slate-300 text-cyan-700 focus:ring-cyan-600" aria-label="{{ __('catalog.catalog_aria') }}"><span><span class="block font-semibold text-slate-800">{{ __('catalog.catalog_visible') }}</span><span class="mt-1 block text-sm leading-6 text-slate-500">{{ __('catalog.catalog_disabled_note') }}</span></span></label>
                    <div><label for="catalog-reason" class="mb-2 block text-sm font-medium text-slate-800">{{ __('catalog.reason') }} <span class="font-normal text-slate-400">{{ __('catalog.optional') }}</span></label><textarea id="catalog-reason" name="reason" rows="3" maxlength="500" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="{{ __('catalog.catalog_reason_placeholder') }}">{{ old('reason') }}</textarea>@error('reason')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs leading-5 text-slate-400">{{ __('catalog.last_change', ['date' => $catalogSetting->last_changed_at?->format('Y-m-d H:i') ?? __('catalog.no_override')]) }}{{ $catalogSetting->changedBy ? ' · '.$catalogSetting->changedBy->name : '' }}</p><button type="submit" class="rounded-xl bg-[#102a43] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:bg-[#173f60] focus:outline-none focus:ring-4 focus:ring-cyan-200">{{ __('catalog.save_catalog') }}</button></div>
                </form>
            </section>

            <div class="mt-10 space-y-5">
                <div><p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('catalog.per_lab') }}</p><h2 class="mt-3 text-2xl font-semibold">{{ __('catalog.runtime_heading') }}</h2></div>
                @foreach ($labs as $entry)
                    @php($lab = $entry->definition)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div><div class="flex flex-wrap items-center gap-3"><h3 class="text-xl font-semibold">{{ __($lab->labelKey) }}</h3><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ __($lab->groupKey) }}</span></div><p class="mt-3 text-sm leading-6 text-slate-500">{{ __($lab->descriptionKey) }}</p><p class="mt-3 font-mono text-xs text-slate-400">{{ __('catalog.lab_route_meta', ['key' => $lab->key, 'route' => implode(', ', $lab->routeNames)]) }}</p></div>
                            <div class="text-left sm:text-right"><p class="text-xs uppercase tracking-wider text-slate-400">{{ __('catalog.effective_state') }}</p><p class="mt-1 text-lg font-semibold {{ $entry->isEnabled() && $lab->implemented ? 'text-emerald-700' : 'text-slate-700' }}">{{ $entry->isEnabled() && $lab->implemented ? __('catalog.enabled') : __('catalog.disabled') }}</p><p class="font-mono text-xs text-slate-400">{{ __('catalog.source', ['source' => $entry->setting->stateSource()]) }}</p></div>
                        </div>
                        @if ($lab->implemented && $lab->toggleable)
                            <form method="POST" action="{{ route('admin.catalog.labs.update', $lab->key) }}" class="mt-7 space-y-6 border-t border-slate-100 pt-6">
                                @csrf
                                @method('PATCH')
                                <label class="flex cursor-pointer items-start gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-cyan-300"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked($entry->isEnabled()) class="mt-1 h-5 w-5 rounded border-slate-300 text-cyan-700 focus:ring-cyan-600" aria-label="{{ __('catalog.lab_aria', ['label' => __($lab->labelKey)]) }}"><span><span class="block font-semibold text-slate-800">{{ __('catalog.lab_visible') }}</span><span class="mt-1 block text-sm leading-6 text-slate-500">{{ __('catalog.lab_disabled_note') }}</span></span></label>
                                <div><label for="reason-{{ $lab->key }}" class="mb-2 block text-sm font-medium text-slate-800">{{ __('catalog.reason') }} <span class="font-normal text-slate-400">{{ __('catalog.optional') }}</span></label><textarea id="reason-{{ $lab->key }}" name="reason" rows="3" maxlength="500" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="{{ __('catalog.lab_reason_placeholder') }}"></textarea></div>
                                <div class="flex justify-end"><button type="submit" class="rounded-xl bg-[#102a43] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:bg-[#173f60] focus:outline-none focus:ring-4 focus:ring-cyan-200">{{ __('catalog.save_lab') }}</button></div>
                            </form>
                        @else
                            <p class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">{{ __('catalog.not_implemented') }}</p>
                        @endif
                    </section>
                @endforeach
            </div>
        </main>
    </body>
</html>
