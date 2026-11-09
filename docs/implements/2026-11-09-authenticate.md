# Implementation Changelog: Authenticate UI

> Ngày triển khai: 2026-11-09
> Phạm vi source: `laravel/`
> Trạng thái: `implemented`
> Mục tiêu: bổ sung authentication backend bằng Laravel Fortify và tạo UI xác thực phù hợp với learning shell của Business Logic Lab.

## 1. Bối cảnh và mục tiêu

`laravel/` là Laravel 13 skeleton chưa có authentication UI. `docs/UI_GUIDE.md` yêu cầu giao diện phải phục vụ việc học business logic, có trạng thái rõ ràng, không biến ứng dụng thành dashboard CRUD và không đặt logic nghiệp vụ chính ở frontend.

Tính năng này giải quyết lớp account/session tối thiểu để learner có thể:

- mở landing page của learning shell;
- tạo tài khoản;
- đăng nhập và đăng xuất;
- truy cập vùng learning shell được bảo vệ;
- yêu cầu reset password và đặt password mới;
- nhận validation error và session status rõ ràng.

### Không nằm trong phạm vi

- Email verification chưa bật.
- 2FA và passkey chưa bật vì chưa có UI/flow tương ứng.
- Chưa tạo lab registry, search/filter lab hoặc scenario simulator.
- Chưa chạy visual QA trên browser thật; kiểm chứng hiện tại là feature test, route inspection, build và diff check.

## 2. Quyết định triển khai

### Chọn Fortify + Blade/Tailwind

Fortify được dùng làm authentication backend: route, controller, session, validation và password reset flow do package xử lý. UI được map bằng `FortifyServiceProvider`, không tự viết lại authentication controller.

Blade + Tailwind được chọn thay vì Inertia/React vì UI guide cho phép Blade khi flow đơn giản. Authentication là lớp hỗ trợ cho learning shell; thêm SPA stack ở bước này sẽ tạo thêm state/build surface nhưng chưa phục vụ một scenario business logic cụ thể.

### Tách trạng thái thật khỏi trạng thái tương lai

Landing page và `/home` nói rõ catalog lab đang `building`/`planned`, không tạo sample lab hoặc evidence giả. `/home` chỉ hiển thị account shell cho tới khi backend có registry thật.

### Không để scaffold vượt quá UI

`fortify:install` mặc định scaffold 2FA/passkey. Hai feature này được tắt trong `config/fortify.php` vì chưa có UI và chưa có flow kiểm chứng. Backend surface hiện khớp với feature đã triển khai.

## 3. Trình tự triển khai chi tiết

### Bước 1 — Đọc yêu cầu UI và khảo sát source

Đọc `docs/UI_GUIDE.md`, `laravel/AGENTS.md`, `laravel/CLAUDE.md`, sau đó kiểm tra:

- Laravel/framework version;
- PHP version;
- cấu trúc route/provider/model/view;
- package frontend và Tailwind setup;
- test harness và database testing config.

Kết quả khảo sát: Laravel `13.40.0`, PHP `8.4.27`, Tailwind `4.x`, chưa có Fortify/auth routes/views và test feature ban đầu chưa bật `RefreshDatabase`.

### Bước 2 — Cài Laravel Fortify

Chạy trong `laravel/`:

```bash
composer require laravel/fortify --no-interaction
```

Dependency chính được thêm:

```json
"laravel/fortify": "^1.41"
```

Composer cập nhật `composer.json`, `composer.lock` và package discovery nhận diện `laravel/fortify`.

### Bước 3 — Tạo scaffold Fortify

Chạy:

```bash
php artisan fortify:install --no-interaction
```

Scaffold tạo:

- `config/fortify.php`;
- `app/Providers/FortifyServiceProvider.php`;
- các action dưới `app/Actions/Fortify/`;
- provider registration trong `bootstrap/providers.php`;
- migration hỗ trợ optional features.

Sau đó các migration chỉ phục vụ 2FA/passkey được loại khỏi implementation vì hai feature đã tắt.

### Bước 4 — Map Fortify tới UI Blade

Trong `app/Providers/FortifyServiceProvider.php`, map các view:

```php
Fortify::loginView(fn () => view('auth.login'));
Fortify::registerView(fn () => view('auth.register'));
Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', [
    'request' => $request,
]));
```

Action backend của Fortify tiếp tục chịu trách nhiệm cho:

- tạo user;
- validate password;
- update profile/password;
- reset password;
- login throttling ở mức 5 requests/phút theo email + IP.

### Bước 5 — Cấu hình feature scope

`config/fortify.php` bật:

- registration;
- reset passwords;
- update profile information;
- update passwords.

Email verification, 2FA và passkey chưa bật. Đây là chủ ý để tránh route/feature không có UI tương ứng.

### Bước 6 — Tạo auth layout và shared components

Tạo:

- `resources/views/components/layouts/auth.blade.php` — auth shell responsive, visual direction technical notebook;
- `resources/views/components/auth-field.blade.php` — label, input, old value, error binding, `aria-invalid`;
- `resources/views/components/auth-errors.blade.php` — validation summary và session status.

Layout có:

- desktop split layout;
- mobile fallback;
- semantic form controls;
- visible focus ring;
- màu sắc không phải tín hiệu duy nhất vì lỗi luôn có text;
- CSRF meta token và `@vite` asset loading.

### Bước 7 — Tạo các auth screen

Tạo các view:

| View | Route sử dụng | Trách nhiệm |
|---|---|---|
| `auth/login.blade.php` | `GET/POST /login` | email, password, remember me, link quên password |
| `auth/register.blade.php` | `GET/POST /register` | name, email, password, confirmation |
| `auth/forgot-password.blade.php` | `GET/POST /forgot-password` | gửi reset link |
| `auth/reset-password.blade.php` | `GET /reset-password/{token}`, `POST /reset-password` | token, email, password mới |

Mọi form đều gửi POST về route Fortify, dùng `@csrf`, giữ old input khi validation fail và render field-level error.

### Bước 8 — Tạo public shell và protected shell

`resources/views/welcome.blade.php` được thay bằng landing page theo UI guide:

- question-first;
- rule/scenario/evidence hierarchy;
- trạng thái `building` rõ ràng;
- CTA đăng nhập/tạo tài khoản;
- không tạo catalog lab giả.

`resources/views/home.blade.php` là learning shell sau đăng nhập:

- hiển thị tên learner;
- có logout POST;
- hiển thị trạng thái catalog chưa kết nối;
- mô tả rõ registry/search/filter sẽ được nối ở feature sau.

### Bước 9 — Bổ sung route ứng dụng

Trong `routes/web.php`:

```php
Route::get('/', ...)->name('home');

Route::get('/home', ...)
    ->middleware('auth')
    ->name('dashboard');
```

Các route `/login`, `/register`, `/forgot-password`, `/reset-password` do Fortify tự đăng ký.

### Bước 10 — Sửa test harness và thêm feature tests

`tests/Pest.php` bật `RefreshDatabase` cho Feature tests:

```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
```

Tạo `tests/Feature/AuthenticationTest.php` để kiểm tra observable behavior:

1. public shell, login và register trả về `200`;
2. learner có thể register, được authenticate và redirect tới `/home`;
3. `/home` redirect guest về `/login`.

## 4. Route contract sau triển khai

| Method | URI | Nguồn đăng ký | Auth |
|---|---|---|---|
| `GET` | `/` | app route | guest/public |
| `GET` | `/home` | app route | `auth` |
| `GET` | `/login` | Fortify | guest |
| `POST` | `/login` | Fortify | guest |
| `POST` | `/logout` | Fortify | authenticated |
| `GET` | `/register` | Fortify | guest |
| `POST` | `/register` | Fortify | guest |
| `GET` | `/forgot-password` | Fortify | guest |
| `POST` | `/forgot-password` | Fortify | guest |
| `GET` | `/reset-password/{token}` | Fortify | guest |
| `POST` | `/reset-password` | Fortify | guest |

## 5. File inventory

### Dependency/config/provider

- `laravel/composer.json`
- `laravel/composer.lock`
- `laravel/config/fortify.php`
- `laravel/app/Providers/FortifyServiceProvider.php`
- `laravel/bootstrap/providers.php`
- `laravel/app/Actions/Fortify/CreateNewUser.php`
- `laravel/app/Actions/Fortify/PasswordValidationRules.php`
- `laravel/app/Actions/Fortify/ResetUserPassword.php`
- `laravel/app/Actions/Fortify/UpdateUserPassword.php`
- `laravel/app/Actions/Fortify/UpdateUserProfileInformation.php`

### UI

- `laravel/resources/views/welcome.blade.php`
- `laravel/resources/views/home.blade.php`
- `laravel/resources/views/components/layouts/auth.blade.php`
- `laravel/resources/views/components/auth-field.blade.php`
- `laravel/resources/views/components/auth-errors.blade.php`
- `laravel/resources/views/auth/login.blade.php`
- `laravel/resources/views/auth/register.blade.php`
- `laravel/resources/views/auth/forgot-password.blade.php`
- `laravel/resources/views/auth/reset-password.blade.php`

### Routes/tests

- `laravel/routes/web.php`
- `laravel/tests/Pest.php`
- `laravel/tests/Feature/AuthenticationTest.php`

## 6. Kiểm chứng đã chạy

Chạy trong `laravel/`:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
php artisan route:list --path=login
php artisan route:list --path=register
npm run build
git diff --check
```

Kết quả:

- Pest: `5 passed`, `17 assertions`;
- Pint: passed;
- login/register routes: Fortify routes hiện diện;
- Vite production build: passed;
- `git diff --check`: passed.

Vite có cảnh báo optional package `fontaine` cho optimized font fallback. Đây không phải build failure và không chặn UI hiện tại.

## 7. Giới hạn và bước tiếp theo

- Mail local đang dùng `MAIL_MAILER=log`; reset link cần đọc từ log trong môi trường local.
- Chưa bật email verification vì chưa có product policy và UI verification flow.
- Chưa có browser visual QA, keyboard walkthrough thực tế hoặc screen-reader audit.
- Chưa có lab registry/backend props; `/home` cố ý hiển thị empty/planned state thay vì sample data.
- Khi thêm feature auth mới, phải cập nhật đồng thời config, Fortify view mapping, route contract, tests và file implementation changelog.

## 8. Quy ước cho các implementation sau

Mỗi feature mới tạo một file riêng trong `docs/implements/` theo format:

```text
YYYY-MM-DD-<feature-slug>.md
```

Mỗi file cần ghi tối thiểu:

1. mục tiêu và phạm vi;
2. bối cảnh source trước thay đổi;
3. quyết định kiến trúc và lý do;
4. trình tự từng bước triển khai;
5. file/route/schema/UI contract bị ảnh hưởng;
6. test và command verification;
7. giới hạn, phần chưa chứng minh và bước tiếp theo.

Không ghi “đã hoàn tất” nếu mới chỉ static check; phải tách rõ lint, test, build, browser, database và production validation.

## 9. Review gate sau triển khai

Audit trước khi chuyển sang feature kế tiếp đã xác nhận:

- Fortify routes và custom Blade views đã được nối đúng;
- register, login, logout và protected shell có feature coverage;
- guest không vào được /home;
- validation/error summary có UI;
- RefreshDatabase đã được bật cho Feature tests;
- Pint, Pest, Vite build, route inspection và Composer audit đều đã chạy thành công.

Các giới hạn còn lại không chặn việc chuyển feature nhưng phải giữ trạng thái rõ ràng:

- chưa có browser visual QA thực tế;
- chưa bật email verification;
- password reset local dùng MAIL_MAILER=log;
- chưa có lab registry thật nên /home chưa render danh sách lab.
