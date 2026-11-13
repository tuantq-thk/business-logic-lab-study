<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('pricing_discounts.page.title') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('shell.brand') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('shell.logout') }}</button></form>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-7 sm:px-6">
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('pricing_discounts.page.back') }}</a>
            <section class="mt-5 max-w-3xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('pricing_discounts.page.eyebrow') }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">{{ __('pricing_discounts.page.heading') }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('pricing_discounts.page.description') }}</p>
            </section>

            <section class="mt-5 grid gap-3 lg:grid-cols-3" aria-label="{{ __('pricing_discounts.flow.aria_label') }}">
                @foreach (['input', 'rules', 'quote'] as $step)
                    <article class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                        <p class="font-mono text-xs font-semibold uppercase tracking-wider text-cyan-700">{{ __('pricing_discounts.flow.'.$step.'.label') }}</p>
                        <h2 class="mt-2 text-sm font-semibold text-slate-900">{{ __('pricing_discounts.flow.'.$step.'.title') }}</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-600">{{ __('pricing_discounts.flow.'.$step.'.description') }}</p>
                    </article>
                @endforeach
            </section>

            @can('manage-catalog')
                <section class="mt-5 rounded-xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm sm:p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="font-mono text-[10px] uppercase tracking-wider text-amber-700">{{ __('pricing_discounts.admin.eyebrow') }}</p><h2 class="mt-1 text-lg font-semibold">{{ __('pricing_discounts.admin.heading') }}</h2><p class="mt-1 text-xs leading-5 text-slate-600">{{ __('pricing_discounts.admin.description') }}</p></div><span class="text-xs font-semibold text-amber-800">{{ trans_choice('pricing_discounts.admin.count', $adminPromotions->count(), ['count' => $adminPromotions->count()]) }}</span></div>
                    @if (session('status'))<div role="status" class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">{{ session('status') }}</div>@endif
                    @if ($errors->has('code') || $errors->has('kind') || $errors->has('value') || $errors->has('minimum_subtotal_minor') || $errors->has('priority') || $errors->has('starts_at') || $errors->has('ends_at'))<div role="alert" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">{{ $errors->first() }}</div>@endif
                    <details class="mt-4 rounded-lg border border-amber-200 bg-white p-3"><summary class="cursor-pointer text-sm font-semibold text-amber-900">{{ __('pricing_discounts.admin.create_title') }}</summary><form method="POST" action="{{ route('admin.pricing-promotions.store') }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">@csrf
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.code') }}<input name="code" value="{{ old('code') }}" maxlength="50" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm uppercase"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.kind') }}<select name="kind" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"><option value="percentage">{{ __('pricing_discounts.admin.percentage') }}</option><option value="fixed">{{ __('pricing_discounts.admin.fixed') }}</option></select></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.value') }}<input type="number" name="value" value="{{ old('value', 10) }}" min="0" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.minimum') }}<input type="number" name="minimum_subtotal_minor" value="{{ old('minimum_subtotal_minor', 0) }}" min="0" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.priority') }}<input type="number" name="priority" value="{{ old('priority', 10) }}" min="0" max="10000" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.starts_at') }}<input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.ends_at') }}<input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div class="flex items-end gap-3 text-xs"><label class="flex items-center gap-2"><input type="hidden" name="stackable" value="0"><input type="checkbox" name="stackable" value="1" class="rounded border-slate-300 text-cyan-700">{{ __('pricing_discounts.admin.stackable') }}</label><label class="flex items-center gap-2"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" checked class="rounded border-slate-300 text-cyan-700">{{ __('pricing_discounts.admin.active') }}</label></div>
                        <div class="flex items-end justify-end"><button class="rounded-lg bg-[#102a43] px-4 py-2.5 text-xs font-semibold text-white hover:bg-[#173f60]">{{ __('pricing_discounts.admin.create') }}</button></div>
                    </form></details>
                    @if ($adminPromotions->isEmpty())
                        <p class="mt-3 rounded-lg border border-dashed border-amber-200 bg-white px-3 py-4 text-xs text-slate-500">{{ __('pricing_discounts.admin.empty') }}</p>
                    @else
                    <div class="mt-3 space-y-2">@foreach ($adminPromotions as $promotion)<details class="rounded-lg border border-amber-100 bg-white p-3"><summary class="flex cursor-pointer list-none items-center justify-between gap-3"><span class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-semibold">{{ $promotion->code }}</span><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $promotion->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">{{ $promotion->active ? __('pricing_discounts.admin.active') : __('pricing_discounts.admin.inactive') }}</span></span><span class="text-xs text-slate-400">{{ $promotion->kind === 'percentage' ? $promotion->value.'%' : \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($promotion->value, 'USD') }} · {{ $promotion->stackable ? __('pricing_discounts.rules.stackable') : __('pricing_discounts.rules.exclusive') }}</span></summary><form method="POST" action="{{ route('admin.pricing-promotions.update', $promotion) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">@csrf @method('PATCH')
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.code') }}<input name="code" value="{{ old('code', $promotion->code) }}" maxlength="50" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm uppercase"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.kind') }}<select name="kind" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"><option value="percentage" @selected(old('kind', $promotion->kind) === 'percentage')>{{ __('pricing_discounts.admin.percentage') }}</option><option value="fixed" @selected(old('kind', $promotion->kind) === 'fixed')>{{ __('pricing_discounts.admin.fixed') }}</option></select></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.value') }}<input type="number" name="value" value="{{ old('value', $promotion->value) }}" min="0" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.minimum') }}<input type="number" name="minimum_subtotal_minor" value="{{ old('minimum_subtotal_minor', $promotion->minimum_subtotal_minor) }}" min="0" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.priority') }}<input type="number" name="priority" value="{{ old('priority', $promotion->priority) }}" min="0" max="10000" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.starts_at') }}<input type="datetime-local" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div><label class="block text-xs font-semibold text-slate-600">{{ __('pricing_discounts.admin.ends_at') }}<input type="datetime-local" name="ends_at" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label></div>
                        <div class="flex items-end gap-3 text-xs"><label class="flex items-center gap-2"><input type="hidden" name="stackable" value="0"><input type="checkbox" name="stackable" value="1" @checked(old('stackable', $promotion->stackable)) class="rounded border-slate-300 text-cyan-700">{{ __('pricing_discounts.admin.stackable') }}</label><label class="flex items-center gap-2"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $promotion->active)) class="rounded border-slate-300 text-cyan-700">{{ __('pricing_discounts.admin.active') }}</label></div>
                        <div class="flex items-end justify-end"><button class="rounded-lg bg-[#102a43] px-4 py-2.5 text-xs font-semibold text-white hover:bg-[#173f60]">{{ __('pricing_discounts.admin.save') }}</button></div>
                    </form><form method="POST" action="{{ route('admin.pricing-promotions.destroy', $promotion) }}" class="mt-3 flex justify-end">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-700 hover:text-red-900" onclick="return confirm('{{ __('pricing_discounts.admin.confirm_delete') }}')">{{ __('pricing_discounts.admin.delete') }}</button></form></details>@endforeach</div>
                    @endif
                </section>
            @endcan

            <section class="mt-6 grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-end justify-between gap-4">
                        <div><p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">{{ __('pricing_discounts.demo.eyebrow') }}</p><h2 class="mt-1 text-xl font-semibold">{{ __('pricing_discounts.demo.title') }}</h2></div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $quote->currency }}</span>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('pricing_discounts.demo.description') }}</p>
                    <p class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-cyan-700">{{ __('pricing_discounts.demo.source_db') }}</p>
                    @if ($errors->has('pricing') || $errors->has('quantities') || $errors->has('quantities.*') || $errors->has('rules') || $errors->has('rules.*'))
                        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
                            <p class="font-semibold">{{ __('pricing_discounts.demo.input_error') }}</p>
                            <ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('labs.pricing-discounts.quote') }}" data-quote-form class="mt-6 rounded-xl border border-cyan-100 bg-cyan-50 p-4">
                        @csrf
                        @php($oldRules = old('rules'))
                        @php($checkedRules = $oldRules === null ? $selectedRules : (is_array($oldRules) ? $oldRules : []))
                        <fieldset>
                            <legend class="text-sm font-semibold text-slate-800">{{ __('pricing_discounts.demo.configure') }}</legend>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($quote->lines as $line)
                                    <label class="block text-sm text-slate-700">{{ $line->name }}
                                        <input type="number" name="quantities[{{ $line->sku }}]" value="{{ old('quantities.'.$line->sku, $quantities[$line->sku]) }}" min="1" max="99" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <fieldset class="mt-4">
                            <legend class="text-sm font-semibold text-slate-800">{{ __('pricing_discounts.demo.choose_rules') }}</legend>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                @foreach ($rules as $rule)
                                    <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="rules[]" value="{{ $rule->code }}" @checked(in_array($rule->code, $checkedRules, true)) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-600">{{ $rule->code }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-cyan-100 pt-3">
                            <span class="mr-1 text-[11px] font-semibold text-slate-500">{{ __('pricing_discounts.demo.presets') }}</span>
                            <button type="button" data-quote-preset data-quote-quantities="1,1" data-quote-rules="WELCOME10" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-cyan-300 hover:text-cyan-800">{{ __('pricing_discounts.demo.preset_small') }}</button>
                            <button type="button" data-quote-preset data-quote-quantities="2,1" data-quote-rules="WELCOME10,STACK5" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-cyan-300 hover:text-cyan-800">{{ __('pricing_discounts.demo.preset_stacking') }}</button>
                            <button type="button" data-quote-preset data-quote-quantities="3,2" data-quote-rules="SAVE300,VIP700" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-cyan-300 hover:text-cyan-800">{{ __('pricing_discounts.demo.preset_exclusive') }}</button>
                        </div>
                        <button type="submit" data-quote-submit data-loading-label="{{ __('pricing_discounts.demo.calculating') }}" class="mt-4 rounded-lg bg-[#102a43] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173f60]">{{ __('pricing_discounts.demo.recalculate') }}</button>
                    </form>
                    <h3 class="mt-6 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('pricing_discounts.demo.lines') }}</h3>
                    <div class="mt-3 divide-y divide-slate-100">
                        @foreach ($quote->lines as $line)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div><p class="font-semibold">{{ $line->name }}</p><p class="mt-1 text-sm text-slate-500">{{ __('pricing_discounts.demo.quantity', ['quantity' => $line->quantity, 'price' => \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($line->unitPrice->amountMinor, $quote->currency)]) }}</p></div>
                                <span class="font-mono text-sm">{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($line->subtotalMinor(), $quote->currency) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <dl class="mt-5 space-y-3 border-t border-slate-200 pt-5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('pricing_discounts.demo.subtotal') }}</dt><dd>{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($quote->subtotalMinor, $quote->currency) }}</dd></div>
                        <div class="flex justify-between text-emerald-700"><dt>{{ __('pricing_discounts.demo.discount') }}</dt><dd>−{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($quote->discountMinor, $quote->currency) }}</dd></div>
                        <div class="flex justify-between text-lg font-semibold"><dt>{{ __('pricing_discounts.demo.total') }}</dt><dd>{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($quote->totalMinor, $quote->currency) }}</dd></div>
                    </dl>
                    <p class="mt-4 text-xs leading-5 text-slate-500">{{ __('pricing_discounts.demo.minor_unit_note') }}</p>
                </article>

                <aside class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-lg font-semibold">{{ __('pricing_discounts.rules.title') }}</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($rules as $rule)
                            @php($eligible = $quote->subtotalMinor >= $rule->minimum_subtotal_minor)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-2"><span class="font-mono text-xs font-semibold">{{ $rule->code }}</span><span class="text-[10px] font-semibold uppercase {{ $eligible ? 'text-emerald-700' : 'text-slate-400' }}">{{ $eligible ? __('pricing_discounts.rules.eligible') : __('pricing_discounts.rules.ineligible') }}</span></div>
                                <div class="mt-1 flex items-center justify-between gap-2"><p class="text-xs text-slate-600">{{ $rule->kind === 'percentage' ? __('pricing_discounts.rules.percentage', ['value' => $rule->value]) : __('pricing_discounts.rules.fixed', ['value' => \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($rule->value, $quote->currency)]) }}</p><span class="text-[10px] text-slate-400">{{ $rule->stackable ? __('pricing_discounts.rules.stackable') : __('pricing_discounts.rules.exclusive') }}</span></div>
                                @if ($rule->minimum_subtotal_minor > 0)<p class="mt-1 text-[10px] text-slate-400">{{ __('pricing_discounts.rules.minimum', ['value' => \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($rule->minimum_subtotal_minor, $quote->currency)]) }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                    <h3 class="mt-6 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('pricing_discounts.demo.applied') }}</h3>
                    @if ($quote->appliedRuleCodes === [])
                        <p class="mt-3 text-sm text-slate-500">{{ __('pricing_discounts.demo.none') }}</p>
                    @else
                        <ul class="mt-3 space-y-2">@foreach ($quote->appliedDiscounts as $discount)<li class="flex items-center justify-between rounded-lg bg-cyan-50 px-3 py-2 font-mono text-sm text-cyan-800"><span>{{ $discount['code'] }}</span><span>−{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($discount['amountMinor'], $quote->currency) }}</span></li>@endforeach</ul>
                    @endif
                </aside>
            </section>

            @if ($recentQuotes->isNotEmpty())
                <section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-end justify-between gap-4"><div><p class="font-mono text-xs uppercase tracking-wider text-slate-400">{{ __('pricing_discounts.history.eyebrow') }}</p><h2 class="mt-2 text-2xl font-semibold">{{ __('pricing_discounts.history.title') }}</h2></div><span class="text-sm text-slate-500">{{ __('pricing_discounts.history.retention') }}</span></div>
                    <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[640px] text-left text-sm"><thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400"><tr><th class="px-3 py-2">{{ __('pricing_discounts.history.quote') }}</th><th class="px-3 py-2">{{ __('pricing_discounts.history.items') }}</th><th class="px-3 py-2">{{ __('pricing_discounts.history.discount') }}</th><th class="px-3 py-2">{{ __('pricing_discounts.history.total') }}</th><th class="px-3 py-2">{{ __('pricing_discounts.history.created') }}</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($recentQuotes as $historyQuote)<tr><td class="px-3 py-2 font-mono text-slate-600"><a href="{{ route('labs.pricing-discounts.quotes.show', $historyQuote) }}" class="font-semibold text-cyan-700 hover:text-cyan-900">#{{ $historyQuote->id }}</a></td><td class="px-3 py-2 text-slate-600">{{ $historyQuote->lines->sum('quantity') }}</td><td class="px-3 py-2 text-emerald-700">−{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($historyQuote->discount_minor, $historyQuote->currency) }}</td><td class="px-3 py-2 font-semibold">{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($historyQuote->total_minor, $historyQuote->currency) }}</td><td class="px-3 py-2 text-slate-500">{{ $historyQuote->created_at?->format('d/m/Y H:i') }}</td></tr>@endforeach</tbody></table></div>
                </section>
            @endif
        </main>
    </body>
</html>
