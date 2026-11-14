# Business Logic Lab — Laravel learning shell

Đây là application thực thi cho tài liệu ở thư mục gốc. Laravel chỉ là phương tiện để minh họa business rule, persistence, authorization, transaction và test evidence; đây không phải một sản phẩm CRUD hoàn chỉnh.

## Trạng thái implementation

| Phần | Trạng thái | Ghi chú |
|---|---|---|
| Authentication | implemented | Laravel Fortify + Blade; email verification/2FA/passkey chưa bật |
| BL-00 Catalog & Toggle | implemented locally | Registry source code, DB runtime override, admin gate |
| BL-01 State transition | evidence-backed locally | Ticket history, transaction, row lock, optimistic version |
| BL-02 Pricing & discounts | evidence-backed locally | Pure calculation, quote snapshot, promotion CRUD |
| BL-03 Inventory & capacity | implemented locally | SQLite + isolated MySQL functional tests; concurrency harness pending |
| BL-04 Business calendar & slots | implemented locally | SQLite + isolated MySQL functional tests; booking overlap thuộc BL-05 |
| BL-05 Booking overlap | implemented locally | SQLite + isolated MySQL functional tests; concurrency harness pending |
| BL-06 Contextual validation | implemented locally | SQLite functional evidence; MySQL/concurrency pending |
| BL-07 Cancellation & rescheduling | implemented locally; verification pending | Course locks, atomic history, refund intent; chưa chạy test/concurrency evidence |
| BL-08 trở đi | planned | Xem tài liệu lab tương ứng, chưa có source implementation |

Chi tiết quyết định và giới hạn nằm ở [technical implementation records](../docs/INDEX.md#technical-implementation-records).

## Yêu cầu

- PHP 8.4+
- Laravel 13
- Composer
- Node.js/npm để build asset
- SQLite cho chạy nhanh; MySQL được dùng khi cần chứng minh locking, CHECK constraint hoặc semantics phụ thuộc engine

## Cài đặt và chạy

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Mở `/` để xem learning shell. Dùng `php artisan db:seed` để provision dữ liệu demo. Seeder không được dùng để ghi đè runtime toggle đã do admin chọn.

Muốn tạo admin local:

```bash
php artisan users:make-admin user@example.com
```

Hoặc cấu hình `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` theo `config/catalog.php` rồi chạy `php artisan db:seed --class=AdminCatalogSeeder`. Không commit secret vào repository.

## Verification

```bash
php artisan test --compact
composer test:mysql # cần database laravel_test trên MySQL
vendor/bin/pint --dirty --test
npm run build
php artisan route:list --except-vendor
git diff --check
```

Test suite hiện tại bao phủ authentication, catalog visibility/admin behavior, state transition và pricing. Các tài liệu kỹ thuật ghi rõ DB/runtime và giới hạn của từng claim; đừng suy luận MySQL locking hoặc constraint behavior từ SQLite.

## Routes chính

- `/` — public landing page.
- `/home` — learning shell sau đăng nhập.
- `/admin/catalog` — admin catalog toggle.
- `/labs/state-transition` — BL-01.
- `/labs/pricing-discounts` — BL-02.

## Tài liệu liên quan

- [README tổng](../README.md)
- [Roadmap](../docs/PLAN.md)
- [Mục lục lab](../docs/INDEX.md)
- [BL-00 implementation](../docs/technical/BL-00-implementation.md)
- [BL-01 implementation](../docs/technical/BL-01-implementation.md)
- [BL-02 implementation](../docs/technical/BL-02-implementation.md)

## Không nên hiểu nhầm

- Seeder demo không thay thế factory test.
- UI ẩn lab không phải authorization boundary; backend gate mới là nguồn enforce.
- “Evidence-backed locally” không đồng nghĩa production-ready.
- Chưa có browser visual/accessibility QA và chưa có toàn bộ MySQL/production operational evidence trong mọi môi trường.
