<x-layouts.auth :title="__('auth_ui.forgot.title')">
    <div><p class="font-mono text-xs uppercase tracking-[0.2em] text-cyan-700">{{ __('auth_ui.forgot.eyebrow') }}</p><h1 class="mt-3 text-3xl font-semibold tracking-[-0.03em] text-slate-950">{{ __('auth_ui.forgot.heading') }}</h1><p class="mt-3 text-sm leading-6 text-slate-500">{{ __('auth_ui.forgot.description') }}</p></div>
    <x-auth-errors />
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">@csrf<x-auth-field name="email" :label="__('auth_ui.email')" type="email" autocomplete="email" placeholder="you@example.com" /><button type="submit" class="w-full rounded-xl bg-[#102a43] px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:bg-[#173f60] focus:outline-none focus:ring-4 focus:ring-cyan-200">{{ __('auth_ui.forgot.submit') }}</button></form>
    <p class="mt-8 text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="font-semibold text-cyan-700 hover:text-cyan-900">{{ __('auth_ui.forgot.back') }}</a></p>
</x-layouts.auth>
