# BL-00 Technical Implementation Record

> Cập nhật: 2026-11-13  
> Phạm vi: `laravel/`  
> Trạng thái: implemented locally; chưa commit release.

## 1. Mục tiêu và state model

BL-00 quản lý khả năng xuất hiện và truy cập business-logic lab trong learning shell. Registry nằm trong source code; runtime enabled state nằm trong database; backend là nguồn sự thật cho cả menu và direct URL. Generic lab detail dùng `/catalog/labs/{labKey}`, còn lab có workflow riêng như BL-01 dùng route feature riêng để tránh route overlap.

Các chiều được tách riêng:

| State | Ý nghĩa |
|---|---|
| `implemented` | Lab đã có implementation/use case thật hay chưa. |
| `default_enabled` | Giá trị mặc định trong registry. |
| `enabled` | Nullable runtime override do admin lưu DB. |
| `toggleable` | Admin có được phép thay đổi lab hay không. |
| authorization | Actor có quyền admin hay không. |

Effective availability:

```text
catalog global enabled
AND registry implemented
AND lab runtime enabled
```

`enabled ?? default_enabled` là effective state của một `CatalogSetting`.

## 2. Registry và typed contract

Registry hiện ở `laravel/config/labs.php`, với metadata:

- `key`, `label_key`, `description_key`, `group_key`, `question_key`;
- `implemented`, `default_enabled`, `toggleable`;
- `route_names`.

`App\Services\LabDefinition` là immutable typed DTO thuần key-based; nó không resolve presentation text. Registry chỉ lưu metadata contract và translation keys. `LabCatalog::registry()` fail-fast nếu outer key lệch definition key hoặc translation key thiếu ở bất kỳ locale nào trong `config('app.supported_locales')`. Blade/presentation layer resolve các key qua Laravel Translator theo locale hiện tại. Global catalog metadata cũng được resolve từ translation keys thay vì dùng text persisted trong database làm nguồn hiển thị.

Request flow:

```text
config/labs.php
  → LabDefinition::fromConfig()
  → LabCatalog::entries()/visible()/isAvailable()
  → home menu
  → EnsureLabIsAvailable middleware
  → route/controller
```

Menu chỉ render lab đã available; đây không phải authorization boundary. `EnsureLabIsAvailable` gọi `LabCatalog::isAvailable()` và trả `404` khi lab unknown, unimplemented hoặc disabled. Generic lab detail dùng `/catalog/labs/{labKey}`; route riêng của lab workflow dùng namespace route riêng để không overlap.

## 3. Admin boundary

```text
auth
  → can:manage-catalog
  → UpdateCatalogSettingRequest
  → CatalogController
  → catalog_settings
```

Gate `manage-catalog` chỉ cho `User::isAdmin()`.

Admin có thể bật/tắt catalog global hoặc từng lab nếu `implemented && toggleable`, đồng thời ghi `last_changed_by`, `last_changed_at` và `reason`. Key và metadata registry không nhận trực tiếp từ request.

## 4. Database contract

Migration: `2026_11_09_075329_create_catalog_settings_table.php`.

`catalog_settings` gồm:

- unique `key`;
- `label_key`, `description_key`, `group_key` là translation-key metadata;
- label/description/group snapshot legacy để tương thích dữ liệu cũ, không phải source hiển thị;
- `implemented`, `default_enabled`;
- nullable `enabled` override;
- nullable `last_changed_by`, `last_changed_at`;
- reason tối đa 500 ký tự.

`CatalogSetting::isEnabled()` và `stateSource()` resolve effective state. UI resolve metadata bằng các `*_key` theo locale hiện tại, nên seed ở `vi` hay `en` không làm thay đổi nội dung hiển thị về sau.

Registry mới không tự ghi database trong request. `AdminCatalogSeeder` là provisioning/backfill contract idempotent: sau khi thêm lab mới hoặc đổi metadata key, chạy seeder để tạo/cập nhật `catalog_settings`. Nếu row runtime bị thiếu, resolver fail-fast thay vì âm thầm bật lab hoặc mutate database trong GET request.

BL-00 hiện lưu actor/time/reason cuối cùng, chưa có bảng audit append-only cho mọi lần toggle.

## 5. Folder/file map

```text
app/Services/LabDefinition.php
app/Services/LabCatalogEntry.php
app/Services/LabCatalog.php
app/Models/CatalogSetting.php
app/Http/Middleware/EnsureLabIsAvailable.php
app/Http/Controllers/Admin/CatalogController.php
app/Http/Requests/UpdateCatalogSettingRequest.php
config/labs.php
lang/vi/labs.php
lang/en/labs.php
lang/vi/catalog.php
lang/en/catalog.php
routes/web.php
database/seeders/AdminCatalogSeeder.php
database/migrations/2026_11_13_120000_add_translation_keys_to_catalog_settings_table.php
tests/Feature/AdminCatalogTest.php
tests/Feature/LabCatalogTest.php
```

BL-00 giữ ở application-level shared folders vì catalog/learning shell là capability dùng chung, không phải domain model riêng của BL-01.

## 6. Change log

### Foundation

- Tạo static registry cho lab đầu tiên.
- Tạo typed `LabDefinition` và `LabCatalogEntry`.
- Render menu từ backend catalog.
- Tạo lab route.
- Chặn direct URL khi catalog/lab disabled.
- Phân biệt enabled, disabled, unknown và unimplemented.

### Runtime/admin hardening

- Tạo `catalog_settings` persistence.
- Thêm per-lab runtime enabled state.
- Thêm admin UI toggle catalog global và từng lab.
- Thêm `manage-catalog` Gate.
- Thêm request validation và actor/reason metadata.
- Bảo vệ key/metadata khỏi mass assignment.
- Test global off, per-lab off, unknown, unimplemented và admin authorization.
- Test registry metadata theo locale `vi`/`en` và global catalog flash/metadata theo locale.
- Route generic được chuẩn hóa thành `/catalog/labs/{labKey}` để không tranh cùng URL với route feature BL-01.
- Validate registry key/translation key cho toàn bộ supported locales; backfill runtime setting giữ explicit qua `AdminCatalogSeeder`.

## 7. Evidence và giới hạn

BL-00 có feature tests cho catalog visibility/admin behavior và locale/key persistence. Verification suite hiện tại đã pass: full Pest 61 tests / 341 assertions trên SQLite và MySQL, bao gồm registry translation validation và BL-02 route; migration up/down trên SQLite, route list, Pint, Vite build và `git diff --check` cũng đã pass.

Chưa nằm trong scope:

1. append-only audit history cho mọi toggle;
2. distributed cache invalidation;
3. browser visual QA;
4. typed primary route thay cho convention `route_names[0]`.
