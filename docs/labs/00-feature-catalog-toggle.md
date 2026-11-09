# BL-00 — Feature Catalog và bật/tắt lab

> **Vai trò:** lab đầu tiên của learning shell  
> **Domain minh họa:** chính thư viện bài học  
> **Nhóm:** feature availability, authorization, configuration  
> **Trạng thái:** kế hoạch phân tích; chưa phải implementation evidence  
> **Mục tiêu:** phân biệt feature catalog, feature flag, quyền truy cập và trạng thái triển khai; thiết kế một toggle có backend enforcement.

## 1. Bài toán và phạm vi

Repo có nhiều bài demo nhưng không phải bài nào cũng đã code xong hoặc nên hiển thị cho mọi người. Người quản trị cần bật/tắt khả năng truy cập một lab khi demo. Người dùng cần nhìn thấy lab khả dụng trong menu. Khi lab bị tắt, truy cập URL trực tiếp hoặc gọi endpoint không được thực thi use case.

**Phạm vi MVP:** catalog đăng ký lab; state `implemented` và `enabled`; trang admin toggle; menu frontend nhận danh sách khả dụng từ backend; server-side gate; audit thay đổi; test an toàn.

**Ngoài phạm vi MVP:** percentage rollout, targeting theo user/tenant, A/B test, remote flag SaaS, multivariate config, kill switch cho từng class/method, feature flag client-side độc lập.

Toggle này quản lý **khả năng có sẵn của lab**; không thay thế authentication, authorization, business rule hoặc cấu hình nghiệp vụ.

## 2. Từ vựng và trạng thái

| Khái niệm | Ý nghĩa | Ví dụ |
|---|---|---|
| Catalog/registry | Danh sách lab hợp lệ trong source code | `booking.overlap` |
| Implemented | Lab có route/use case thực sự | false nếu mới có tài liệu |
| Enabled | Admin cho phép lab mở trong môi trường ứng dụng | true/false |
| Authorized | Actor hiện tại có quyền thực hiện hành động | user được phép/admin |
| Default | Giá trị an toàn khi chưa có override | false cho lab chưa hoàn thiện |
| Override | Giá trị runtime lưu DB thay default | enabled bởi admin |
| Retired | Lab bị bỏ hoặc thay thế | không xuất hiện trong menu |

Không dùng một boolean `active` để đồng thời diễn tả implemented, enabled, permission và lifecycle. Các chiều này trả lời câu hỏi khác nhau.

## 3. Actors và quy tắc nghiệp vụ

- **Visitor/user:** chỉ thấy lab được expose cho actor và enabled.
- **Admin:** được bật/tắt các lab có trong registry; không thể tạo flag key tùy ý.
- **Application:** từ chối route/action của disabled hoặc unimplemented lab.

| ID | Rule | Enforcement |
|---|---|---|
| BR-00-01 | Chỉ registry key đã khai báo mới có thể được cấu hình. | Registry resolver + validation; DB FK không thể tham chiếu code registry thuần. |
| BR-00-02 | Lab chưa implemented không được bật. | Admin use case kiểm tra metadata từ registry. |
| BR-00-03 | Tắt lab chặn backend route/action, kể cả direct URL. | Middleware/policy hoặc route gate server-side. |
| BR-00-04 | Chỉ admin được thay đổi enabled state. | Authentication + policy trên endpoint. |
| BR-00-05 | Toggle không cấp permission nghiệp vụ. | Mọi use case vẫn kiểm tra authorization riêng. |
| BR-00-06 | Core catalog/admin route không thể tự disable. | Registry đánh dấu non-toggleable; UI và server cùng enforce. |
| BR-00-07 | Admin change và audit entry cùng commit. | Transaction DB. |
| BR-00-08 | Mọi lần đổi state có actor, old/new value và thời điểm. | Audit append-only. |
| BR-00-09 | Giá trị flag key lạ trong DB hoặc request bị fail closed và được quan sát. | Resolver/validation; không tự enable theo input. |
| BR-00-10 | Menu phản ánh cùng nguồn cấu hình backend với route gate. | Inertia props/API response từ resolver server. |

### Invariants

- Không thể bật flag không có trong registry.
- `enabled = true` không hợp lệ khi `implemented = false`.
- Core admin/catalog luôn truy cập được admin đã xác thực.
- Menu không phải access-control boundary.
- Toggle operation idempotent theo trạng thái mong muốn; audit chỉ ghi thay đổi thực sự, hoặc audit mọi command nếu policy yêu cầu—chọn một.

## 4. Registry: metadata nằm ở code

Registry là nguồn chân lý cho danh tính và metadata ổn định; DB chỉ lưu runtime override. Không lưu toàn bộ catalog lặp lại trong DB vì có thể lệch khỏi code đã deploy.

```php
// Conceptual shape, not a complete implementation
[
    'key' => 'booking.overlap',
    'label' => 'Đặt lịch chống trùng',
    'description' => 'Kiểm tra giao nhau và bảo vệ khi đặt đồng thời.',
    'group' => 'Scheduling',
    'implemented' => true,
    'default_enabled' => false,
    'toggleable' => true,
    'route_names' => ['labs.booking-overlap.*'],
]
```

**Metadata ở code:** key, label, description, group, route association, implemented, toggleable, default.  
**Override ở DB:** key, enabled, actor/time audit hoặc revision.  
**Không nhét vào flag:** permission, pricing, schedule, tenant entitlement phức tạp. Nếu cần targeting/rollout về sau, ghi ADR và thiết kế riêng.

Key là identifier ổn định, lowercase dotted slug. Đổi label không đổi key; rename key cần migration mapping hoặc deprecated alias.

## 5. Schema, migration và dữ liệu

### `feature_flag_overrides`

| Field | Gợi ý | Lý do |
|---|---|---|
| `id` | bigint/ULID thống nhất convention repo | PK |
| `key` | varchar, unique | Registry key; index lookup |
| `enabled` | boolean | Runtime override value |
| `updated_by` | nullable FK users | Actor nếu đổi bởi admin; nullable cho deploy/system |
| `created_at`, `updated_at` | timestamps | Metadata persistence |
| `version` (optional) | integer | Optimistic concurrency nếu admin edit đồng thời hoặc cache invalidation cần revision |

`NULL row` nên có nghĩa **không override**, không cần ghi default vào DB cho mọi registry item. Effective value: `override ?? registry.default_enabled`. Với mẫu đơn giản, `enabled` non-null khi row tồn tại.

### `feature_flag_audits`

| Field | Gợi ý | Lý do |
|---|---|---|
| `id` | bigint/ULID | PK |
| `flag_key` | string | Không FK nếu catalog thuộc source code |
| `actor_id` | nullable FK users | System/deploy event có thể không có user |
| `old_enabled`, `new_enabled` | boolean | Diff dễ truy vấn |
| `reason` | nullable string/text có giới hạn | Giải thích admin intent nếu yêu cầu |
| `request_id` | nullable string | Correlation/trace |
| `created_at` | timestamp | Append-only event time |

### Migration và constraint

- Unique index trên override `key`.
- FK `updated_by`/`actor_id` với delete behavior có chủ đích; audit actor nên nullable on delete hoặc giữ actor snapshot theo policy.
- Audit insert cùng transaction với override update.
- Seed default overrides chỉ khi cần; không bắt buộc vì registry cung cấp default.
- Không dùng DB `ENUM` cho key hoặc state nếu catalog thay đổi theo deploy.
- Khi key bị xóa khỏi code nhưng còn DB override: resolver báo unknown stale key; có command/diagnostic dọn dẹp có audit, không silently bật/tắt.
- Vì config có thể cache, cập nhật version/clear cache sau commit. Không cache quyết định lâu hơn consistency requirement.

## 6. Models, factories, seeders

### Models

- `FeatureFlagOverride`: casts `enabled` boolean; guarded key bởi server registry; không cho mass assignment trực tiếp từ request.
- `FeatureFlagAudit`: insert-only qua domain/application service; không expose update/delete route.
- `FeatureDefinition`: có thể là immutable DTO/value object từ registry, không cần Eloquent table/model.
- `FeatureCatalog`: đọc registry + override và trả effective state; single source cho menu và gate.

Không đặt registry trong Eloquent model static method nếu resolver cần được test/cache/configure; cũng không cần một repository abstraction nếu Eloquent lookup đơn giản.

### Factory

- `FeatureFlagOverrideFactory` chỉ tạo key hợp lệ trong registry ở trạng thái enabled/disabled.
- Trường hợp unknown/stale key tạo trực tiếp trong test nhằm kiểm tra fail-closed, không dùng làm factory state phổ biến.
- `FeatureFlagAuditFactory` giữ quan hệ old/new và actor nhất quán.

### Seeder

- Registry metadata không cần seed vào DB.
- Seeder local/demo có thể bật một vài lab đã implemented; idempotent bằng `updateOrCreate` chỉ khi seed thật sự là source of override.
- Không chạy seeder dev để ghi đè quyết định toggle runtime trên môi trường production.
- Việc bootstrap quyền admin phải nằm ở auth/role setup riêng, không tạo admin mặc định với password yếu.

## 7. Cấu trúc Laravel code

```text
app/
├── FeatureCatalog/
│   ├── FeatureDefinition.php
│   ├── FeatureRegistry.php
│   ├── FeatureCatalog.php
│   └── FeatureState.php
├── Models/FeatureFlagOverride.php
├── Models/FeatureFlagAudit.php
├── Actions/Features/SetFeatureEnabled.php
├── Policies/FeatureFlagPolicy.php
├── Http/Controllers/Admin/FeatureFlagController.php
├── Http/Requests/Admin/UpdateFeatureFlagRequest.php
└── Http/Middleware/EnsureFeatureEnabled.php
```

Tên folder có thể theo Laravel convention repository thực tế; không bắt buộc tạo module framework riêng. Registry/config metadata không nên bị trộn với controller.

### Luồng toggle admin

`Admin request → authenticate → authorize manage flags → validate key/value/reason → resolve registry item → ensure implemented+toggleable → transaction update override + audit → invalidate cache/revision → return effective state`

### Luồng truy cập lab

`Request → authenticate nếu cần → resolve registry item → check implemented → resolve effective enabled → deny if off → authorize actor/resource → execute business use case`

Feature availability chạy trước use case; quyền nghiệp vụ tiếp tục được kiểm tra ngay sau đó. Với route public, định nghĩa riêng nếu disabled phải trả 404 hay 403; không rò rỉ route hidden nếu đó là yêu cầu.

## 8. Toggle mechanics và consistency

### MVP khuyến nghị

1. Static registry ở code.
2. DB override chỉ cho runtime admin toggle.
3. Một `FeatureCatalog` resolver dùng chung cho Inertia shared props/API, admin list và middleware.
4. Middleware chặn route; use case nhạy cảm có thể gọi explicit guard nếu nhiều entry point (HTTP, job, command).
5. Cache chỉ thêm sau khi đo; nếu dùng cache, invalidate sau transaction commit và có versioning/TTL rõ.

### Cache/worker consistency

- Request đang chạy có thể đã resolve trạng thái trước khi admin tắt feature. Xác định semantics: toggle ngăn request mới hay phải hủy request đang thực thi? Thường MVP chặn request mới là đủ.
- Queue job đã enqueue trước khi tắt: job tiếp tục hay bị hủy? Quyết định theo semantics; availability toggle không thay authorization recheck.
- Với nhiều app instances, process-local cache có thể làm trạng thái lệch. Dùng shared cache/DB resolver hoặc TTL thấp + invalidation broadcast tùy yêu cầu.
- Không gọi DB cho mỗi route và mọi menu item nếu scale lớn; có thể batch resolve hoặc cache catalog snapshot.

## 9. UI/UX cho admin toggle

- Hiển thị label, mô tả, group, trạng thái implemented, trạng thái effective, nguồn trạng thái (`default` hay `admin override`), thời điểm/người đổi.
- Disable toggle với lab chưa implemented/non-toggleable; giải thích lý do.
- Confirm khi tắt lab đang có người sử dụng; MVP có thể chỉ cảnh báo vì route request kế tiếp bị chặn.
- Yêu cầu reason cho thay đổi nếu cần audit; không bắt user nhập reason rỗng vô nghĩa.
- Trạng thái lỗi/save pending/success rõ; optimistic UI chỉ dùng khi rollback state khi server từ chối.
- Frontend menu nhận danh sách enabled từ backend; không duplicate một `features.ts` khác nguồn sự thật.
- Toggle không đồng nhất với switch permission người dùng; UI/admin route vẫn phải policy-protected.

Chi tiết component, layout, accessibility và responsive nằm trong [UI Guide](../UI_GUIDE.md).

## 10. API/HTTP behavior

Gợi ý semantics (chốt theo app):

- `GET /admin/features`: list registry metadata + effective state, chỉ admin.
- `PATCH /admin/features/{key}` body `{ enabled: boolean, reason?: string }`.
- Unknown key: 404/422 theo API convention; không tạo dynamic flag.
- Lab disabled: 404 nếu muốn không disclose hoặc 403 nếu muốn giải thích; dùng nhất quán.
- Admin unauthorized: 403; unauthenticated: 401/redirect theo session app.
- Update idempotent: `enabled=true` gửi nhiều lần không tạo duplicate override; audit policy quyết định ghi no-op command hay chỉ state transition.

Không cần public API riêng nếu learning app dùng Inertia/server rendering; route contract nội bộ đủ.

## 11. Failure/security model

| Failure/threat | Cách xử lý |
|---|---|
| Direct URL khi flag off | Backend middleware/action gate chặn |
| User thường gọi endpoint toggle | Policy kiểm tra trên server |
| Gửi unknown key | Registry allowlist, reject |
| Bật lab chưa code | Reject; registry `implemented=false` |
| Menu props stale | Backend gate vẫn có hiệu lực; resolver/cache invalidation |
| Cache clear lỗi | Effective resolver có fallback an toàn; log/metric; core admin không bị khóa |
| DB override stale key | Diagnostic hiển thị unknown; không silently enable |
| CSRF/session misuse | Laravel session CSRF protection, authz policy, rate/CSRF tests |
| Admin tự khóa quản trị | Core catalog/admin non-toggleable và invariant server-side |
| Audit payload có secret | Chỉ lưu key/state/actor/reason safe, không lưu request body tùy tiện |

Fail-safe không có nghĩa “tắt toàn bộ app khi DB/cache lỗi”. Chọn behavior theo nhóm: lab không core có thể đóng (fail closed); trang admin/diagnostic cần đường hoạt động có kiểm soát. Tránh default-on cho lab security-sensitive.

## 12. Phương án và trade-off

| Cách | Ưu điểm | Nhược điểm | Chọn khi |
|---|---|---|---|
| Config/static registry only | Đơn giản, review qua Git, không DB/admin race | Bật/tắt cần deploy | Bắt đầu learning shell hoặc môi trường demo cá nhân |
| Registry code + DB override | Runtime toggle, audit, admin UI | Cache consistency, stale keys, DB failure behavior | Cần bật/tắt khi app đang chạy |
| SaaS/remote feature flag | Targeting/rollout/analytics | Vendor, latency, cost, SDK/fallback complexity | Product có rollout/experimentation thật |
| Boolean rải trong components | Dễ bắt đầu nhưng không nhất quán | Backend bypass, key drift, khó audit | Không chọn làm kiến trúc chuẩn |

**Khuyến nghị:** bắt đầu registry tĩnh; thêm DB override khi có UI/runtime need. Toggle lab không cần percentage rollout. Không dùng feature flag thay migration/deployment strategy.

## 13. Test plan

- Registry keys unique, metadata schema hợp lệ.
- Default effective state khi chưa có override.
- Override runtime thay state đúng sau commit/refresh.
- Unknown key, stale DB key và unimplemented lab không được bật.
- Direct URL/action khi disabled bị chặn; enabled vẫn phải qua authorization.
- User không phải admin không toggle được.
- Toggle và audit atomic; rollback nếu audit insert lỗi.
- Concurrent admin updates có last-write-wins hoặc optimistic conflict policy rõ.
- Cache invalidation giữa app instances nếu cache thực sự được bật.
- Core admin route vẫn hoạt động khi các lab khác tắt.

Feature/unit tests kiểm logic registry; MySQL integration tests cho unique/transaction; test cache/worker phù hợp runtime thật. Không cần test concurrency nếu app đơn user và toggle chỉ là config deploy-time.

## 14. Thứ tự triển khai

1. Static registry + menu chỉ hiện lab có `implemented && effectiveEnabled`.
2. Middleware backend gate và direct URL tests.
3. Admin policy/permission riêng.
4. DB override + audit transaction.
5. UI management và reason/state metadata.
6. Cache/invalidation chỉ khi cần; đo query trước.
7. Operational flow cho stale keys, core lab protection, reset demo.

### Acceptance criteria

- Admin có thể bật/tắt một lab implemented.
- Menu và backend dùng cùng resolver.
- Disabled lab không thể gọi trực tiếp.
- Toggle không cấp quyền vào dữ liệu/use case.
- Unknown/unimplemented/core key được xử lý đúng.
- Audit và override không lệch nhau.
- Có hướng dẫn reset/dev seed và test evidence khi implementation hoàn tất.

## 15. Câu hỏi review

1. Toggle này có thật sự cần DB runtime hay config deploy-time đủ?
2. Nếu admin tắt lab trong lúc request/job đang chạy, semantics là gì?
3. Có đường gọi thứ hai (API, job, command) bỏ qua middleware không?
4. Core lab nào không thể tự khóa?
5. Stale key sau deploy được phát hiện/dọn bằng cách nào?
6. Toggle có đang bị dùng nhầm cho authorization hoặc business policy không?
7. Ai có quyền bật lab có rủi ro cao; có cần audit reason/approval không?
