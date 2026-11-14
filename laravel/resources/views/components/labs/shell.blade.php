@props([
    'title',
    'description' => null,
    'canonical' => url()->current(),
])

@php($structuredData = json_encode(['@context' => 'https://schema.org', '@type' => 'TechArticle', 'headline' => $title, 'description' => $description ?: $title, 'url' => $canonical, 'inLanguage' => str_replace('_', '-', app()->getLocale())], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#102a43">
        <meta name="description" content="{{ $description ?: $title }}">
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description ?: $title }}">
        <meta property="og:url" content="{{ $canonical }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $title }}">
        <meta name="twitter:description" content="{{ $description ?: $title }}">
        <script type="application/ld+json">{!! $structuredData !!}</script>
        <title>{{ $title }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <a href="#main-content" class="lab-skip-link">{{ __('shell.lab.skip_to_content') }}</a>
        <header class="sticky top-0 z-40 border-b border-slate-200/90 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-sm font-semibold" aria-label="{{ __('shell.brand') }}">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-white" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v15.5H7.5A2.5 2.5 0 0 0 5 21V5.5Z"/><path d="M5 5.5V21M8.5 7H16M8.5 10H16"/></svg>
                    </span>
                    <span>{{ __('shell.brand') }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm font-semibold text-cyan-700 transition hover:text-cyan-900 focus:outline-none focus:ring-4 focus:ring-cyan-100">{{ __('shell.logout') }}</button>
                </form>
            </div>
        </header>

        <div class="lab-reading-progress" aria-hidden="true"><span id="lab-reading-progress-bar"></span></div>

        {{ $slot }}

        <button type="button" id="lab-back-to-top" class="lab-back-to-top" aria-label="{{ __('shell.lab.back_to_top') }}" title="{{ __('shell.lab.back_to_top') }}">
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2"><path d="m6 15 6-6 6 6"/><path d="M12 9v10"/></svg>
        </button>

        <script>
            (() => {
                const progress = document.getElementById('lab-reading-progress-bar');
                const backToTop = document.getElementById('lab-back-to-top');
                const tocLinks = [...document.querySelectorAll('[data-lab-toc-link]')];
                const tocTargets = tocLinks.map((link) => document.querySelector(link.getAttribute('href'))).filter(Boolean);
                const update = () => {
                    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
                    const percentage = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
                    progress.style.width = `${Math.min(100, Math.max(0, percentage))}%`;
                    backToTop.classList.toggle('is-visible', window.scrollY > 480);
                };
                let framePending = false;

                window.addEventListener('scroll', () => {
                    if (framePending) {
                        return;
                    }

                    framePending = true;
                    window.requestAnimationFrame(() => {
                        update();
                        framePending = false;
                    });
                }, { passive: true });
                backToTop.addEventListener('click', () => window.scrollTo({
                    top: 0,
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                }));
                if (tocTargets.length && 'IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                            tocLinks.forEach((link) => link.classList.toggle('is-active', link.getAttribute('href') === `#${entry.target.id}`));
                        });
                    }, { rootMargin: '-18% 0px -68% 0px', threshold: 0 });
                    tocTargets.forEach((target) => observer.observe(target));
                }
                update();
            })();
        </script>
        @stack('scripts')
    </body>
</html>
