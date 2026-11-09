<x-layouts.auth title="Khôi phục mật khẩu">
    <div><p class="font-mono text-xs uppercase tracking-[0.2em] text-cyan-700">Account recovery</p><h1 class="mt-3 text-3xl font-semibold tracking-[-0.03em] text-slate-950">Đặt lại mật khẩu</h1><p class="mt-3 text-sm leading-6 text-slate-500">Nhập email đã đăng ký. Nếu tài khoản tồn tại, bạn sẽ nhận được hướng dẫn tiếp theo.</p></div>
    <x-auth-errors />
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">@csrf<x-auth-field name="email" label="Email" type="email" autocomplete="email" placeholder="you@example.com" /><button type="submit" class="w-full rounded-xl bg-[#102a43] px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:bg-[#173f60] focus:outline-none focus:ring-4 focus:ring-cyan-200">Gửi link khôi phục</button></form>
    <p class="mt-8 text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="font-semibold text-cyan-700 hover:text-cyan-900">Quay lại đăng nhập</a></p>
</x-layouts.auth>
