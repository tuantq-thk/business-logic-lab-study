<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Learning shell · {{ config('app.name', 'Business Logic Lab') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f8fb] text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-semibold"><span class="grid h-9 w-9 place-items-center rounded-lg bg-[#102a43] text-xs text-white">BL</span>Business Logic Lab</a>
                <div class="flex items-center gap-4 text-sm">
                    @can('manage-catalog')
                        <a href="{{ route('admin.catalog.index') }}" class="font-semibold text-cyan-700 hover:text-cyan-900">Admin catalog</a>
                    @endcan
                    <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-semibold text-cyan-700 hover:text-cyan-900">Đăng xuất</button></form>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
            <div class="max-w-3xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-700">Your learning shell</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em] sm:text-5xl">Chào {{ auth()->user()->name }}, lab đang chờ bạn.</h1>
                <p class="mt-5 text-base leading-8 text-slate-600">Khu vực tài khoản đã hoạt động. Trạng thái catalog được resolve từ backend, không do frontend tự đoán.</p>
            </div>
            <section class="mt-12 rounded-2xl border border-dashed border-slate-300 bg-white p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        @if ($catalogSetting->isEnabled())
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Catalog đang bật</span>
                            <h2 class="mt-4 text-xl font-semibold">Catalog đã được mở</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">Catalog feature đã enabled, nhưng lab registry thật chưa được kết nối. Không hiển thị dữ liệu mẫu.</p>
                        @else
                            <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Catalog đang tắt</span>
                            <h2 class="mt-4 text-xl font-semibold">Catalog chưa được mở</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">Admin có thể bật catalog khi registry và evidence của lab đã sẵn sàng.</p>
                        @endif
                    </div>
                    <span class="font-mono text-xs text-slate-400">source: {{ $catalogSetting->stateSource() }}</span>
                </div>
            </section>
        </main>
    </body>
</html>
