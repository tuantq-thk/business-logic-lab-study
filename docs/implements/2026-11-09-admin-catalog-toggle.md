# Implementation Changelog: Admin Catalog Toggle

> Ngày triển khai: 2026-11-09
> Phạm vi source: laravel/
> Trạng thái: implemented
> Liên quan: Authenticate UI và docs/UI_GUIDE.md

## 1. Review gate trước khi mở rộng

Trước khi thêm admin, rà soát lại feature Authenticate theo ba lớp:

| Lớp | Kết quả | Bằng chứng |
|---|---|---|
| UI/auth wiring | Đạt | Fortify view mapping, login/register/reset views, /home auth guard |
| Runtime behavior | Đạt | 10 Pest tests, 38 assertions |
| Static/build/security | Đạt | Pint, Vite build, route list, git diff --check, composer audit |
| Browser visual QA | Chưa chạy | Project chưa cài browser test plugin và chưa có browser session |
| Production mail/browser/database | Chưa chứng minh | Local/test environment only |

### Khoảng trống được phát hiện và xử lý

1. Auth test trước đó mới chứng minh register và guard; đã bổ sung login/logout.
2. Role admin chưa tồn tại; đã thêm role column, User::isAdmin() và Gate.
3. Catalog chưa có persistence; đã thêm catalog_settings với default/override/effective state.
4. Có role admin nhưng chưa có provisioning path; đã thêm users:make-admin {email}.
5. Fortify config còn optional passkey/2FA surface dù chưa có UI; đã bỏ limiter/config tương ứng khỏi implementation active.

## 2. Mục tiêu và phạm vi

Admin có thể bật/tắt catalog từ UI, nhưng chỉ admin mới được xem và mutate admin screen. Learner nhìn thấy effective state ở learning shell; frontend không tự quyết định state.

### Phạm vi triển khai

- role learner mặc định và role admin;
- Gate manage-catalog;
- migration lưu một catalog setting canonical;
- admin index/update flow;
- UI toggle có accessible label, reason, actor/time/source;
- learner/admin authorization tests;
- CLI command để promote user thành admin.

### Chưa nằm trong phạm vi

- catalog lab registry;
- CRUD lab;
- audit log append-only nhiều bản ghi;
- permission matrix nhiều role;
- email notification khi catalog state thay đổi.

## 3. Data contract và state model

Table catalog_settings có một canonical row với key lab.catalog:

| Field | Ý nghĩa |
|---|---|
| key | stable identifier, hiện là lab.catalog |
| label / description / group | metadata hiển thị cho admin |
| implemented | catalog toggle capability đã được triển khai chưa |
| default_enabled | state mặc định khi chưa có override |
| enabled | nullable admin override; null nghĩa là dùng default |
| last_changed_by | user admin thực hiện thay đổi gần nhất |
| last_changed_at | thời điểm thay đổi gần nhất |
| reason | lý do do admin nhập, tối đa 500 ký tự |

Effective state được resolve bởi model:

    effective_enabled = enabled ?? default_enabled
    source = enabled === null ? "default" : "admin override"

State hiện tại mặc định là disabled. Migration seed tạo metadata thật, không tạo lab sample/evidence giả.

## 4. Trình tự triển khai

### Bước 1 — Tạo schema bằng migration

Tạo:

- database/migrations/2026_11_09_075358_add_role_to_users_table.php;
- database/migrations/2026_11_09_075329_create_catalog_settings_table.php.

Role mới có default learner, bảo đảm user hiện hữu không được nâng quyền ngoài ý muốn. Catalog setting có unique key và foreign key nullable tới user thay đổi gần nhất.

### Bước 2 — Đưa role vào domain model

App/Models/User thêm:

- ROLE_ADMIN;
- ROLE_LEARNER;
- isAdmin(): bool.

role không được đưa vào Fillable; việc thay đổi role không được phép đi qua request mass assignment.

### Bước 3 — Định nghĩa authorization boundary

AppServiceProvider đăng ký Gate:

    Gate::define('manage-catalog', fn (User $user): bool => $user->isAdmin());

Admin routes dùng auth và can:manage-catalog. Update FormRequest cũng authorize bằng cùng ability để mutation không phụ thuộc riêng vào UI/middleware.

### Bước 4 — Tạo model/resolver cho catalog state

App\Models\CatalogSetting sở hữu:

- isEnabled();
- stateSource();
- changedBy() relationship;
- boolean/datetime casts.

Controller và learning shell gọi model resolver; Blade không truy cập database trực tiếp.

### Bước 5 — Tạo HTTP boundary cho admin

Admin\CatalogController có hai action:

- index() — load canonical setting và actor relationship;
- update(UpdateCatalogSettingRequest $request) — chỉ update enabled, actor, timestamp và reason.

UpdateCatalogSettingRequest validate:

    'enabled' => ['required', 'boolean'],
    'reason' => ['nullable', 'string', 'max:500'],

Controller dùng validated boundary/FormRequest data, không nhận request all.

### Bước 6 — Tạo admin routes

    GET   /admin/catalog  -> admin.catalog.index
    PATCH /admin/catalog  -> admin.catalog.update

Middleware chain:

    web -> auth -> can:manage-catalog

Learner nhận 403; guest bị auth redirect.

### Bước 7 — Tạo admin UI

resources/views/admin/catalog/index.blade.php hiển thị:

- label, group, description;
- effective state;
- source default/admin override;
- checkbox toggle có accessible label;
- reason textarea với maxlength;
- last changed actor/time;
- success/error feedback;
- CSRF và PATCH method spoofing.

home.blade.php chỉ hiện link Admin catalog khi @can('manage-catalog'). Learner vẫn thấy catalog state từ backend nhưng không thấy control admin.

### Bước 8 — Tạo provisioning command

Chạy:

    php artisan users:make-admin user@example.com

Command chỉ promote user đã tồn tại và dùng forceFill ở CLI boundary. Nếu email không tồn tại, command trả failure và không tạo user mới.

### Bước 9 — Bổ sung test matrix

tests/Feature/AuthenticationTest.php hiện kiểm tra:

- learner login/logout;
- learner không truy cập admin catalog;
- admin xem admin catalog;
- admin toggle state;
- database lưu enabled/actor/reason;
- /home phản ánh Catalog đang bật.

### Bước 10 — Đưa dữ liệu khởi tạo vào database seeder

Tạo database/seeders/AdminCatalogSeeder.php và gọi seeder này từ DatabaseSeeder.

Seeder có hai nhánh:

1. Luôn upsert canonical catalog setting lab.catalog với default_enabled=false. updateOrCreate giúp chạy lại seeder không tạo duplicate row và không ghi đè admin override hiện tại.
2. Chỉ tạo hoặc promote admin user khi cả ADMIN_EMAIL và ADMIN_PASSWORD tồn tại trong environment. Password được hash bằng Hash::make; role được set qua forceFill, không đi qua mass assignment từ request.

Config đọc environment qua config/catalog.php:

    ADMIN_EMAIL=admin@example.com
    ADMIN_NAME="Catalog Admin"
    ADMIN_PASSWORD=<secret>

Không commit credential thật vào .env.example, source hoặc changelog. Nếu thiếu credential, seeder chỉ seed catalog metadata và cảnh báo rằng admin user đã được skip.

Migration create_catalog_settings_table chỉ còn schema definition. Dữ liệu khởi tạo thuộc seeder, tránh hidden data write trong migration và cho phép môi trường khác nhau chọn seed policy khác nhau.

Chạy:

    php artisan db:seed --class=AdminCatalogSeeder
    php artisan db:seed

DatabaseSeeder gọi AdminCatalogSeeder trước test user mặc định. Feature tests cũng seed riêng để giữ test setup explicit và không phụ thuộc vào thứ tự chạy migration.

## 5. Route và permission contract

| Method | URI | Guest | Learner | Admin |
|---|---|---:|---:|---:|
| GET | /admin/catalog | redirect login | 403 | 200 |
| PATCH | /admin/catalog | redirect login | 403 | update state |

Authentication và authorization khác nhau:

- auth xác định người gọi là user đã đăng nhập;
- manage-catalog xác định user đó có role admin;
- validation xác định payload có hợp lệ;
- model/database lưu effective state và provenance.

## 6. File inventory

- laravel/app/Models/User.php
- laravel/app/Models/CatalogSetting.php
- laravel/app/Providers/AppServiceProvider.php
- laravel/app/Http/Controllers/LearningShellController.php
- laravel/app/Http/Controllers/Admin/CatalogController.php
- laravel/app/Http/Requests/UpdateCatalogSettingRequest.php
- laravel/app/Console/Commands/MakeUserAdmin.php
- laravel/config/catalog.php
- laravel/database/seeders/AdminCatalogSeeder.php
- laravel/database/seeders/DatabaseSeeder.php
- laravel/.env.example
- laravel/database/migrations/2026_11_09_075358_add_role_to_users_table.php
- laravel/database/migrations/2026_11_09_075329_create_catalog_settings_table.php
- laravel/routes/web.php
- laravel/resources/views/home.blade.php
- laravel/resources/views/admin/catalog/index.blade.php
- laravel/tests/Feature/AuthenticationTest.php

## 7. Verification evidence

Chạy trong laravel/:

    vendor/bin/pint --dirty --format agent
    php artisan migrate:fresh --env=testing --no-interaction
    php artisan route:list --path=admin
    php artisan list | rg 'users:make-admin'
    php artisan test --compact
    php artisan db:seed --class=AdminCatalogSeeder --env=testing
    composer audit --no-interaction

Kết quả:

- Pint: passed;
- migrations: passed;
- admin routes: 2 routes registered;
- users:make-admin: command registered;
- Pest: 10 passed, 38 assertions;
- Admin catalog seeder: idempotent; không tạo admin nếu thiếu environment credentials.
- Composer audit: No security vulnerability advisories found.

Chưa chứng minh:

- browser visual/responsive QA;
- real mail delivery;
- production DB migration on populated data;
- actual admin bootstrap, nếu chưa cung cấp ADMIN_EMAIL và ADMIN_PASSWORD;
- catalog route enforcement for future lab registry, vì registry chưa tồn tại.

## 8. Điều kiện để qua feature tiếp theo

Có thể chuyển sang feature mới ở mức auth/admin foundation vì:

- role boundary đã có server-side enforcement;
- admin mutation có validation, CSRF, actor/time/reason provenance;
- learner/admin permission matrix đã có test;
- catalog state không bị frontend tự đoán;
- evidence và giới hạn đã được ghi rõ.

Feature catalog tiếp theo phải tái sử dụng CatalogSetting::isEnabled() hoặc một resolver tương đương ở backend trước khi render/route lab; không được chỉ ẩn link bằng Blade.
