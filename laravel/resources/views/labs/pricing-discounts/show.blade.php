<x-labs.shell :title="__('pricing_discounts.detail.title', ['id' => $pricingQuote->id])">
        <main id="main-content" class="mx-auto max-w-5xl px-4 py-7 sm:px-6">
            <a href="{{ route('labs.pricing-discounts.index') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('pricing_discounts.detail.back') }}</a>
            <section class="mt-5 max-w-2xl">
                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-cyan-700">{{ __('pricing_discounts.detail.eyebrow') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.04em]">{{ __('pricing_discounts.detail.title', ['id' => $pricingQuote->id]) }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('pricing_discounts.detail.description') }}</p>
            </section>
            <section class="mt-6 grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">{{ __('pricing_discounts.detail.line') }}</h2>
                    <div class="mt-2 divide-y divide-slate-100">
                        @foreach ($pricingQuote->lines as $line)
                            <div class="flex items-center justify-between gap-4 py-3"><div><p class="font-semibold">{{ $line->name_snapshot }}</p><p class="mt-1 text-xs text-slate-500">{{ __('pricing_discounts.detail.quantity', ['quantity' => $line->quantity, 'price' => \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($line->unit_price_minor, $pricingQuote->currency)]) }}</p></div><span class="font-mono text-sm">{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($line->line_total_minor, $pricingQuote->currency) }}</span></div>
                        @endforeach
                    </div>
                    <dl class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm"><div class="flex justify-between"><dt class="text-slate-500">{{ __('pricing_discounts.detail.subtotal') }}</dt><dd>{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($pricingQuote->subtotal_minor, $pricingQuote->currency) }}</dd></div><div class="flex justify-between text-emerald-700"><dt>{{ __('pricing_discounts.detail.discount') }}</dt><dd>−{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($pricingQuote->discount_minor, $pricingQuote->currency) }}</dd></div><div class="flex justify-between text-lg font-semibold"><dt>{{ __('pricing_discounts.detail.total') }}</dt><dd>{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($pricingQuote->total_minor, $pricingQuote->currency) }}</dd></div></dl>
                </article>
                <aside class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"><h2 class="text-lg font-semibold">{{ __('pricing_discounts.detail.applied') }}</h2>@if ($pricingQuote->discounts->isEmpty())<p class="mt-3 text-sm text-slate-500">{{ __('pricing_discounts.detail.none') }}</p>@else<ul class="mt-3 space-y-2">@foreach ($pricingQuote->discounts as $discount)<li class="flex items-center justify-between rounded-lg bg-cyan-50 px-3 py-2 font-mono text-sm text-cyan-800"><span>{{ $discount->code_snapshot }}</span><span>−{{ \App\Features\PricingDiscounts\ValueObjects\Money::formatMinor($discount->discount_minor, $pricingQuote->currency) }}</span></li>@endforeach</ul>@endif</aside>
            </section>
        </main>
</x-labs.shell>
