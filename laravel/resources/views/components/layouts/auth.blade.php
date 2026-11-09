<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Xác thực' }} · {{ config('app.name', 'Business Logic Lab') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <main class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.86fr)]">
            <section class="relative hidden overflow-hidden bg-[#102a43] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full border border-cyan-300/20"></div>
                <div class="absolute -bottom-40 -left-20 h-[30rem] w-[30rem] rounded-full border border-amber-300/10"></div>
                <a href="{{ route('home') }}" class="relative inline-flex w-fit items-center gap-3 text-sm font-semibold tracking-wide text-white"><span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-300 text-[#102a43] shadow-lg shadow-cyan-950/20">BL</span><span>Business Logic Lab</span></a>
                <div class="relative max-w-xl"><p class="mb-5 font-mono text-xs uppercase tracking-[0.28em] text-cyan-200">technical notebook / 01</p><h1 class="max-w-lg text-5xl font-semibold leading-[1.08] tracking-[-0.04em] xl:text-6xl">Học bằng cách nhìn thấy quyết định.</h1><p class="mt-6 max-w-lg text-base leading-8 text-slate-300">Mỗi lab bắt đầu bằng một câu hỏi nghiệp vụ, đi qua rule, scenario và kết quả có bằng chứng.</p><div class="mt-10 flex flex-wrap gap-3 text-xs font-medium text-slate-200"><span class="rounded-full border border-white/15 px-3 py-2">Rule-first</span><span class="rounded-full border border-white/15 px-3 py-2">Server-validated</span><span class="rounded-full border border-white/15 px-3 py-2">Evidence-backed</span></div></div>
                <p class="relative text-xs text-slate-400">Không phải dashboard CRUD. Đây là không gian để mổ xẻ business logic.</p>
            </section>
            <section class="flex items-center justify-center px-5 py-10 sm:px-8"><div class="w-full max-w-md"><a href="{{ route('home') }}" class="mb-12 inline-flex items-center gap-2 text-sm font-semibold text-slate-700 lg:hidden"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>Business Logic Lab</a>{{ $slot }}</div></section>
        </main>
    </body>
</html>
