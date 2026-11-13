<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('state_transition.page.title') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>{{ __('welcome.brand') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('welcome.nav.logout') }}</button></form>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-12 sm:px-8">
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">{{ __('state_transition.page.back') }}</a>

            <section class="mt-8 max-w-4xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">{{ __('state_transition.page.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em] sm:text-5xl">{{ __('state_transition.page.heading') }}</h1>
                <p class="mt-5 text-base leading-8 text-slate-600">{{ __('state_transition.page.description') }}</p>
            </section>

            <section class="mt-8 grid gap-4 lg:grid-cols-3" aria-label="{{ __('state_transition.flow.aria_label') }}">
                <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                    <p class="font-mono text-xs font-semibold uppercase tracking-wider text-cyan-700">{{ __('state_transition.flow.request.label') }}</p>
                    <h2 class="mt-3 font-semibold text-slate-900">{{ __('state_transition.flow.request.title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('state_transition.flow.request.description') }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    <p class="font-mono text-xs font-semibold uppercase tracking-wider text-amber-700">{{ __('state_transition.flow.decision.label') }}</p>
                    <h2 class="mt-3 font-semibold text-slate-900">{{ __('state_transition.flow.decision.title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('state_transition.flow.decision.description') }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="font-mono text-xs font-semibold uppercase tracking-wider text-emerald-700">{{ __('state_transition.flow.commit.label') }}</p>
                    <h2 class="mt-3 font-semibold text-slate-900">{{ __('state_transition.flow.commit.title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('state_transition.flow.commit.description') }}</p>
                </article>
            </section>

            @if (session('status'))
                <div role="status" class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="font-mono text-xs uppercase tracking-wider text-slate-400">{{ __('state_transition.create.eyebrow') }}</p><h2 class="mt-2 text-xl font-semibold">{{ __('state_transition.create.title') }}</h2></div>
                    <p class="max-w-md text-sm leading-6 text-slate-500">{{ __('state_transition.create.description') }}</p>
                </div>
                <form method="POST" action="{{ route('labs.state-transition.store') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start">
                    @csrf
                    <div class="flex-1">
                        <label for="subject" class="sr-only">{{ __('state_transition.create.subject_label') }}</label>
                        <input id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="{{ __('state_transition.create.subject_placeholder') }}">
                        @error('subject')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-xl bg-[#102a43] px-5 py-3 text-sm font-semibold text-white hover:bg-[#173f60]">{{ __('state_transition.create.submit') }}</button>
                </form>
            </section>

            <section class="mt-10">
                <div class="flex items-end justify-between gap-4"><div><p class="font-mono text-xs uppercase tracking-wider text-slate-400">{{ __('state_transition.tickets.eyebrow') }}</p><h2 class="mt-2 text-2xl font-semibold">{{ __('state_transition.tickets.title') }}</h2></div><span class="text-sm text-slate-500">{{ trans_choice('state_transition.tickets.count', $tickets->count(), ['count' => $tickets->count()]) }}</span></div>
                @if ($tickets->isEmpty())
                    <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500">{{ __('state_transition.tickets.empty') }}</div>
                @else
                    <div class="mt-5 space-y-6">
                        @foreach ($tickets as $ticket)
                            @php($currentStep = array_search($ticket->status, $statuses, true))
                            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 p-6 sm:p-8">
                                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="font-mono text-xs text-slate-400">{{ __('state_transition.tickets.meta', ['id' => $ticket->id, 'version' => $ticket->version]) }}</p>
                                            <h3 class="mt-2 text-xl font-semibold">{{ $ticket->subject }}</h3>
                                            <p class="mt-2 text-sm text-slate-500">{{ __('state_transition.tickets.created_by', ['name' => $ticket->createdBy->name]) }}{{ $ticket->assignedTo ? __('state_transition.tickets.assigned_to', ['name' => $ticket->assignedTo->name]) : '' }}</p>
                                        </div>
                                        <span class="inline-flex w-fit rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-800">{{ $ticket->status->label() }}</span>
                                    </div>

                                    <div class="mt-8">
                                        <div class="mb-3 flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-400"><span>{{ __('state_transition.progress.label') }}</span><span>{{ __('state_transition.progress.step', ['current' => $currentStep + 1, 'total' => count($statuses)]) }}</span></div>
                                        <ol class="grid grid-cols-4 gap-2" aria-label="{{ __('state_transition.progress.aria_label') }}">
                                            @foreach ($statuses as $stepIndex => $status)
                                                @php($isDone = $stepIndex < $currentStep)
                                                @php($isCurrent = $stepIndex === $currentStep)
                                                <li class="relative min-w-0 text-center">
                                                    @if ($stepIndex > 0)<span class="absolute left-[-50%] right-[50%] top-4 -z-0 h-0.5 {{ $stepIndex <= $currentStep ? 'bg-cyan-500' : 'bg-slate-200' }}" aria-hidden="true"></span>@endif
                                                    <span class="relative z-10 mx-auto grid h-8 w-8 place-items-center rounded-full border-2 text-xs font-bold {{ $isCurrent ? 'border-cyan-700 bg-cyan-700 text-white' : ($isDone ? 'border-cyan-500 bg-cyan-50 text-cyan-700' : 'border-slate-200 bg-white text-slate-400') }}">{{ $isDone ? '✓' : $stepIndex + 1 }}</span>
                                                    <span class="mt-2 block truncate text-xs font-semibold {{ $isCurrent ? 'text-cyan-800' : ($isDone ? 'text-slate-700' : 'text-slate-400') }}">{{ $status->label() }}</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                </div>

                                <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_0.9fr]">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('state_transition.history.title') }}</p>
                                        <ol class="relative mt-4 space-y-5 border-l-2 border-slate-200 pl-6">
                                            @foreach ($ticket->histories as $history)
                                                <li class="relative">
                                                    <span class="absolute -left-[31px] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-cyan-600 ring-1 ring-cyan-200" aria-hidden="true"></span>
                                                    <p class="text-sm font-semibold text-slate-800">{{ $history->from_status?->label() ?? __('state_transition.history.created') }} <span class="px-1 text-slate-400">→</span> {{ $history->to_status->label() }}</p>
                                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $history->actor->name }} · {{ $history->created_at?->format('d/m/Y H:i') }}{{ $history->reason ? ' · '.$history->reason : '' }}</p>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>

                                    @can('changeStatus', $ticket)
                                        <form method="POST" action="{{ route('labs.state-transition.transition', $ticket) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            @csrf
                                            @method('PATCH')
                                            @if ((int) session('error_ticket_id') === $ticket->id && $errors->any())
                                                <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">{{ $errors->first() }}</div>
                                            @endif
                                            <p class="font-mono text-xs uppercase tracking-wider text-slate-400">{{ __('state_transition.action.eyebrow') }}</p>
                                            <label for="to-status-{{ $ticket->id }}" class="mt-3 block text-sm font-semibold text-slate-800">{{ __('state_transition.action.status_label') }}</label>
                                            <input type="hidden" name="version" value="{{ $ticket->version }}">
                                            <select id="to-status-{{ $ticket->id }}" name="to_status" class="mt-3 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                                @foreach ($statuses as $status)<option value="{{ $status->value }}" @selected($status === $ticket->status)>{{ $status->label() }}</option>@endforeach
                                            </select>
                                            <label for="reason-{{ $ticket->id }}" class="mt-4 block text-sm font-semibold text-slate-800">{{ __('state_transition.action.reason_label') }} <span class="font-normal text-slate-400">{{ __('state_transition.action.reason_required') }}</span></label>
                                            <textarea id="reason-{{ $ticket->id }}" name="reason" rows="3" maxlength="500" class="mt-3 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="{{ __('state_transition.action.reason_placeholder') }}"></textarea>
                                            <p class="mt-3 text-xs leading-5 text-slate-500">{{ __('state_transition.action.helper') }}</p>
                                            <button class="mt-4 w-full rounded-xl bg-[#102a43] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173f60]">{{ __('state_transition.action.submit') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </body>
</html>
