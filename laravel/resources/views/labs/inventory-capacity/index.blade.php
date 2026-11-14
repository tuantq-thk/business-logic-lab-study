<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('inventory_capacity.page.title') }}</title>
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
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('inventory_capacity.page.back') }}</a>
            <section class="mt-5 max-w-3xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('inventory_capacity.page.eyebrow') }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">{{ __('inventory_capacity.page.heading') }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('inventory_capacity.page.description') }}</p>
            </section>

            <section class="mt-5 grid gap-3 lg:grid-cols-3" aria-label="{{ __('inventory_capacity.page.title') }}">
                @foreach (['request', 'lock', 'result'] as $step)
                    <article class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                        <p class="font-mono text-xs font-semibold uppercase tracking-wider text-cyan-700">{{ __('inventory_capacity.flow.'.$step.'.label') }}</p>
                        <h2 class="mt-2 text-sm font-semibold text-slate-900">{{ __('inventory_capacity.flow.'.$step.'.title') }}</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-600">{{ __('inventory_capacity.flow.'.$step.'.description') }}</p>
                    </article>
                @endforeach
            </section>

            @if (session('status'))<div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
            @if ($errors->any())<div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-end justify-between gap-4"><div><p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">{{ __('inventory_capacity.inventory.eyebrow') }}</p><h2 class="mt-1 text-xl font-semibold">{{ __('inventory_capacity.inventory.title') }}</h2></div><span class="text-xs text-slate-500">{{ $items->count() }} SKU</span></div>
                @if ($items->isEmpty())
                    <p class="mt-5 rounded-lg border border-dashed border-slate-300 p-5 text-sm text-slate-500">{{ __('inventory_capacity.inventory.empty') }}</p>
                @else
                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($items as $item)
                            <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3"><div><p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">{{ $item->sku }}</p><h3 class="mt-1 font-semibold">{{ __($item->name_key) }}</h3></div><span class="rounded-full {{ $item->availableQuantity() > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }} px-2 py-1 text-[10px] font-semibold">{{ $item->availableQuantity() }} {{ __('inventory_capacity.inventory.available') }}</span></div>
                                <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-xs"><div class="rounded-lg bg-white p-2"><dt class="text-slate-400">{{ __('inventory_capacity.inventory.on_hand') }}</dt><dd class="mt-1 font-mono font-semibold">{{ $item->on_hand }}</dd></div><div class="rounded-lg bg-white p-2"><dt class="text-slate-400">{{ __('inventory_capacity.inventory.reserved') }}</dt><dd class="mt-1 font-mono font-semibold">{{ $item->reserved_quantity }}</dd></div><div class="rounded-lg bg-white p-2"><dt class="text-slate-400">{{ __('inventory_capacity.inventory.available') }}</dt><dd class="mt-1 font-mono font-semibold">{{ $item->availableQuantity() }}</dd></div></dl>
                                <form method="POST" action="{{ route('labs.inventory-capacity.reserve', $item) }}" data-inventory-form class="mt-4 border-t border-slate-200 pt-4">@csrf
                                    <div class="grid grid-cols-2 gap-2"><label for="quantity-{{ $item->id }}" class="text-xs font-semibold text-slate-600">{{ __('inventory_capacity.inventory.quantity') }}</label><label for="ttl-{{ $item->id }}" class="text-xs font-semibold text-slate-600">{{ __('inventory_capacity.inventory.ttl') }}</label></div>
                                    <div class="grid grid-cols-2 gap-2"><input id="quantity-{{ $item->id }}" type="number" name="items[{{ $item->id }}][quantity]" min="1" max="1000000" value="{{ old('items.'.$item->id.'.quantity', 1) }}" required class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input id="ttl-{{ $item->id }}" type="number" name="items[{{ $item->id }}][ttl_minutes]" min="1" max="1440" value="{{ old('items.'.$item->id.'.ttl_minutes', 15) }}" required class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></div>
                                    <label for="order-ref-{{ $item->id }}" class="mt-3 block text-xs font-semibold text-slate-600">{{ __('inventory_capacity.inventory.order_ref') }}</label><input id="order-ref-{{ $item->id }}" name="items[{{ $item->id }}][order_ref]" maxlength="64" value="{{ old('items.'.$item->id.'.order_ref') }}" placeholder="{{ __('inventory_capacity.inventory.order_ref_placeholder') }}" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm uppercase">
                                    <button type="submit" data-inventory-submit data-loading-label="{{ __('inventory_capacity.page.loading') }}" class="mt-3 w-full rounded-lg bg-[#102a43] px-4 py-2.5 text-xs font-semibold text-white hover:bg-[#173f60]">{{ __('inventory_capacity.inventory.submit') }}</button>
                                </form>
                                @can('adjust', \App\Features\InventoryCapacity\Models\InventoryItem::class)
                                    <details class="mt-4 border-t border-slate-200 pt-3"><summary class="cursor-pointer text-xs font-semibold text-amber-800">{{ __('inventory_capacity.admin.title') }}</summary><form method="POST" action="{{ route('admin.inventory.items.adjust', $item) }}" data-inventory-form class="mt-3 space-y-2">@csrf @method('PATCH')<label for="delta-{{ $item->id }}" class="block text-xs font-semibold text-slate-600">{{ __('inventory_capacity.admin.delta') }}</label><input id="delta-{{ $item->id }}" type="number" name="delta" placeholder="{{ __('inventory_capacity.admin.delta_placeholder') }}" required class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><label for="reason-{{ $item->id }}" class="block text-xs font-semibold text-slate-600">{{ __('inventory_capacity.admin.reason') }}</label><input id="reason-{{ $item->id }}" name="reason" maxlength="500" placeholder="{{ __('inventory_capacity.admin.reason_placeholder') }}" required class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><label for="reference-{{ $item->id }}" class="block text-xs font-semibold text-slate-600">{{ __('inventory_capacity.admin.reference') }}</label><input id="reference-{{ $item->id }}" name="reference" maxlength="64" placeholder="{{ __('inventory_capacity.admin.reference_placeholder') }}" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><button type="submit" data-inventory-submit data-loading-label="{{ __('inventory_capacity.page.loading') }}" class="w-full rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100">{{ __('inventory_capacity.admin.submit') }}</button></form></details>
                                @endcan
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-end justify-between gap-4"><div><p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">{{ __('inventory_capacity.reservations.eyebrow') }}</p><h2 class="mt-1 text-xl font-semibold">{{ __('inventory_capacity.reservations.title') }}</h2></div><span class="text-xs text-slate-500">{{ $reservations->count() }}</span></div>
                @if ($reservations->isEmpty())<p class="mt-5 text-sm text-slate-500">{{ __('inventory_capacity.reservations.empty') }}</p>@else
                    <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="border-b border-slate-200 text-[10px] uppercase tracking-wider text-slate-400"><tr><th class="px-3 py-2">{{ __('inventory_capacity.reservations.reference') }}</th><th class="px-3 py-2">{{ __('inventory_capacity.reservations.item') }}</th><th class="px-3 py-2">{{ __('inventory_capacity.reservations.quantity') }}</th><th class="px-3 py-2">{{ __('inventory_capacity.reservations.status') }}</th><th class="px-3 py-2">{{ __('inventory_capacity.reservations.expires') }} ({{ __('inventory_capacity.inventory.timezone') }})</th><th class="px-3 py-2"></th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($reservations as $reservation)@php($displayStatus = $reservation->isActive() && $reservation->expires_at <= now() ? 'expired' : $reservation->status->value)@php($canMutate = $reservation->isActive() && $displayStatus === 'active')<tr><td class="px-3 py-3 font-mono text-xs">{{ $reservation->order_ref }}</td><td class="px-3 py-3">{{ __($reservation->inventoryItem->name_key) }}</td><td class="px-3 py-3">{{ $reservation->quantity }}</td><td class="px-3 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold">{{ __('inventory_capacity.status.'.$displayStatus) }}</span></td><td class="px-3 py-3 text-xs text-slate-500">{{ $reservation->expires_at?->setTimezone(config('app.timezone'))->format(__('inventory_capacity.date_format')) }}</td><td class="px-3 py-3">@if ($canMutate)<div class="flex gap-2"><form method="POST" action="{{ route('labs.inventory-capacity.consume', $reservation) }}" data-inventory-form>@csrf<button type="submit" data-inventory-submit data-loading-label="{{ __('inventory_capacity.page.loading') }}" class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('inventory_capacity.reservations.consume') }}</button></form><form method="POST" action="{{ route('labs.inventory-capacity.release', $reservation) }}" data-inventory-form>@csrf<button type="submit" data-inventory-submit data-loading-label="{{ __('inventory_capacity.page.loading') }}" class="rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ __('inventory_capacity.reservations.release') }}</button></form></div>@else<span class="text-xs text-slate-400">{{ __('inventory_capacity.reservations.terminal') }}</span>@endif</td></tr>@endforeach</tbody></table></div>
                @endif
            </section>
        </main>
    </body>
</html>
