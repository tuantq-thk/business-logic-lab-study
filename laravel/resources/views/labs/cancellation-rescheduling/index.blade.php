<x-labs.shell :title="__('cancellation_rescheduling.page.title')" :description="__('cancellation_rescheduling.page.description')">
    @php($formatMinor = static fn (int $amount): string => intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT))

    <main id="main-content" class="lab-page mx-auto px-4 py-8 sm:px-6 lg:px-10 lg:py-12">
        <a href="{{ route('dashboard') }}" class="inline-flex text-sm font-semibold text-cyan-700 hover:text-cyan-900">
            {{ __('cancellation_rescheduling.page.back') }}
        </a>

        <header class="lab-reading-column mt-6">
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-700">
                <span>BL-07</span><span class="text-slate-300">/</span>
                <span>{{ __('cancellation_rescheduling.page.eyebrow') }}</span>
                <span class="rounded-full bg-amber-100 px-2.5 py-1 tracking-normal text-amber-800">
                    {{ __('cancellation_rescheduling.page.level') }}
                </span>
            </div>
            <h1 class="mt-4 max-w-5xl text-3xl font-semibold leading-tight tracking-[-0.04em] sm:text-5xl">
                {{ __('cancellation_rescheduling.page.heading') }}
            </h1>
            <p class="lab-card-copy mt-5 text-base leading-7 text-slate-600">
                {{ __('cancellation_rescheduling.page.description') }}
            </p>
        </header>

        <x-labs.learning-overview lab="cancellation-rescheduling" />

        <div class="my-10 flex items-center gap-4" aria-hidden="true">
            <div class="h-px flex-1 bg-slate-200"></div>
            <span class="rounded-full bg-white px-4 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-widest text-slate-500">
                {{ __('cancellation_rescheduling.practice.divider') }}
            </span>
            <div class="h-px flex-1 bg-slate-200"></div>
        </div>

        <section id="cancellation-rescheduling-practice" aria-labelledby="practice-title">
            <div class="max-w-3xl">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">
                    {{ __('cancellation_rescheduling.practice.label') }}
                </p>
                <h2 id="practice-title" class="mt-2 text-3xl font-semibold tracking-[-0.03em]">
                    {{ __('cancellation_rescheduling.practice.title') }}
                </h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    {{ __('cancellation_rescheduling.practice.description') }}
                </p>
            </div>

            @if (session('status'))
                <div role="status" class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mt-6 rounded-xl bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-6" aria-labelledby="registrations-title">
                    <h3 id="registrations-title" class="text-xl font-semibold">
                        {{ __('cancellation_rescheduling.practice.registrations') }}
                    </h3>

                    @forelse ($registrations as $registration)
                        @php($isOwner = (int) auth()->id() === (int) $registration->attendee_id)

                        <article class="mt-4 rounded-xl bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-semibold text-slate-900">{{ $registration->course->title }}</h4>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ __('cancellation_rescheduling.practice.starts', ['date' => $registration->course->starts_at_utc->format('Y-m-d H:i')]) }}
                                        · {{ __('cancellation_rescheduling.practice.status_'.$registration->status->value) }}
                                    </p>
                                    @if (! $isOwner && auth()->user()->isAdmin())
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ __('cancellation_rescheduling.practice.for_attendee', ['name' => $registration->attendee->name]) }}
                                        </p>
                                    @endif
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    {{ __('cancellation_rescheduling.practice.paid', ['currency' => $registration->currency, 'amount' => $formatMinor($registration->paid_amount_minor)]) }}
                                </span>
                            </div>

                            @if ($registration->status->value === 'confirmed')
                                <form method="POST" action="{{ route('labs.cancellation-rescheduling.mutate') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @csrf
                                    <input type="hidden" name="registration_id" value="{{ $registration->id }}">

                                    <label for="action-{{ $registration->id }}" class="text-xs font-semibold text-slate-600">
                                        {{ __('cancellation_rescheduling.practice.action') }}
                                        <select id="action-{{ $registration->id }}" name="action" class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm">
                                            <option value="cancel">{{ __('cancellation_rescheduling.practice.cancel') }}</option>
                                            @if ($isOwner)
                                                <option value="reschedule">{{ __('cancellation_rescheduling.practice.reschedule') }}</option>
                                            @endif
                                        </select>
                                    </label>

                                    @if ($isOwner)
                                        <label for="target-{{ $registration->id }}" class="text-xs font-semibold text-slate-600">
                                            {{ __('cancellation_rescheduling.practice.target') }}
                                            <select id="target-{{ $registration->id }}" name="target_course_id" class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm">
                                                <option value="">{{ __('cancellation_rescheduling.practice.choose_target') }}</option>
                                                @foreach ($courses->where('id', '!=', $registration->course_id) as $course)
                                                    <option value="{{ $course->id }}">
                                                        {{ $course->title }} · {{ $course->starts_at_utc->format('Y-m-d H:i') }} · {{ $course->confirmed_registrations_count }}/{{ $course->capacity }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endif

                                    <label for="reason-{{ $registration->id }}" class="text-xs font-semibold text-slate-600 sm:col-span-2">
                                        {{ __('cancellation_rescheduling.practice.reason') }}
                                        <input id="reason-{{ $registration->id }}" name="reason" maxlength="500" class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 px-3 text-sm" autocomplete="off">
                                        <span class="mt-1 block font-normal text-slate-500">
                                            {{ __('cancellation_rescheduling.practice.reason_hint') }}
                                        </span>
                                    </label>

                                    <button type="submit" class="h-11 rounded-lg bg-[#102a43] px-4 text-sm font-semibold text-white hover:bg-[#173f60] focus:outline-none focus:ring-4 focus:ring-cyan-200 sm:col-span-2">
                                        {{ __('cancellation_rescheduling.practice.submit') }}
                                    </button>
                                </form>
                            @endif

                            @if ($registration->histories->isNotEmpty())
                                <details class="mt-4">
                                    <summary class="cursor-pointer text-xs font-semibold text-cyan-800">
                                        {{ __('cancellation_rescheduling.practice.history') }}
                                    </summary>
                                    <ol class="mt-2 space-y-1 text-xs text-slate-600">
                                        @foreach ($registration->histories as $history)
                                            <li>
                                                {{ __('cancellation_rescheduling.practice.history_'.$history->action->value) }}
                                                · {{ $history->from_starts_at_utc?->format('Y-m-d H:i') }}
                                                → {{ $history->to_starts_at_utc?->format('Y-m-d H:i') ?? __('cancellation_rescheduling.practice.no_target') }}
                                                UTC · {{ $history->created_at?->format('Y-m-d H:i') }} UTC
                                                @if ($history->reason)
                                                    · {{ $history->reason }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                </details>
                            @endif

                            @if ($registration->refundIntent)
                                <p class="mt-3 text-xs text-slate-600">
                                    {{ __('cancellation_rescheduling.practice.refund', ['status' => __('cancellation_rescheduling.enums.refund_status.'.$registration->refundIntent->status->value), 'amount' => $formatMinor($registration->refundIntent->amount_minor), 'currency' => $registration->refundIntent->currency]) }}
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="mt-4 text-sm text-slate-600">
                            {{ __('cancellation_rescheduling.practice.empty') }}
                        </p>
                    @endforelse
                </section>

                <aside class="rounded-2xl bg-cyan-50 p-5 sm:p-6" aria-labelledby="courses-title">
                    <h3 id="courses-title" class="text-xl font-semibold">
                        {{ __('cancellation_rescheduling.practice.courses') }}
                    </h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($courses as $course)
                            <article class="rounded-xl bg-white/80 p-4">
                                <h4 class="text-sm font-semibold text-slate-900">{{ $course->title }}</h4>
                                <p class="mt-1 text-xs leading-5 text-slate-600">
                                    {{ __('cancellation_rescheduling.practice.course_meta', ['starts' => $course->starts_at_utc->format('Y-m-d H:i'), 'cutoff' => $course->cancel_cutoff_at_utc->format('Y-m-d H:i'), 'confirmed' => $course->confirmed_registrations_count, 'capacity' => $course->capacity]) }}
                                </p>
                            </article>
                        @empty
                            <p class="text-sm text-slate-600">{{ __('cancellation_rescheduling.practice.no_courses') }}</p>
                        @endforelse
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-600">
                        {{ __('cancellation_rescheduling.practice.override_note') }}
                    </p>
                </aside>
            </div>
        </section>
    </main>
</x-labs.shell>
