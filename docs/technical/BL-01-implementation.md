# BL-01 Technical Implementation Record

> Cập nhật: 2026-11-13  
> Phạm vi: `laravel/`  
> Trạng thái: implemented locally; chưa commit release.

## 1. Mục tiêu và business decision

BL-01 mô hình hóa support ticket với current status, transition history, authorization và concurrent update protection.

Actor rule hiện tại:

```text
admin OR ticket owner (created_by)
```

`manager/staff` không còn là actor model của BL-01.

## 2. Feature module structure

```text
app/Features/StateTransition/
├── Actions/
│   ├── ChangeSupportTicketStatus.php
│   └── CreateSupportTicket.php
├── Enums/SupportTicketStatus.php
├── Exceptions/
│   ├── InvalidTicketTransition.php
│   ├── StaleTicket.php
│   └── TicketTransitionReasonRequired.php
├── Http/
│   ├── Controllers/StateTransitionController.php
│   └── Requests/
│       ├── ChangeSupportTicketStatusRequest.php
│       └── CreateSupportTicketRequest.php
├── Models/
│   ├── SupportTicket.php
│   └── TicketStatusHistory.php
└── Policies/SupportTicketPolicy.php
```

Infrastructure và test:

```text
database/factories/StateTransition/SupportTicketFactory.php
database/seeders/StateTransition/StateTransitionSeeder.php
tests/Feature/StateTransition/StateTransitionTest.php
```

`User` vẫn ở shared `App\Models` vì identity dùng chung toàn application.

## 3. Status model và transition table

`SupportTicketStatus` là backed enum:

```text
open → in_progress → resolved → closed → open
```

| From | Allowed to | Điều kiện |
|---|---|---|
| `open` | `in_progress` | Actor được authorize. |
| `in_progress` | `resolved` | Actor được authorize. |
| `resolved` | `closed` | Actor được authorize. |
| `closed` | `open` | Actor được authorize và có reason. |

No-op và transition chéo bị reject bằng `InvalidTicketTransition`.

## 4. Authorization contract

Policy ability duy nhất cho status mutation:

```text
changeStatus(User $user, SupportTicket $ticket): bool
```

Rule:

```php
$user->isAdmin() || $ticket->created_by === $user->id
```

Cùng ability được dùng ở Blade, `ChangeSupportTicketStatusRequest::authorize()` và `ChangeSupportTicketStatus` action. UI không phải security boundary; backend vẫn authorize direct request.

## 5. Request/use-case flow

### Create ticket

`CreateSupportTicket` chịu trách nhiệm authorize, transaction, tạo ticket ở `open`/version `0`, tạo initial history `null → open` và return persisted ticket.

### Change status

`ChangeSupportTicketStatus::handle()`:

1. authorize actor trên route-bound ticket trước khi mở transaction, tránh actor trái quyền chiếm row lock;
2. bắt đầu transaction;
3. reload ticket theo primary key;
4. `lockForUpdate()` current row;
5. re-authorize actor trên current state để bảo vệ trước ownership thay đổi đồng thời;
6. compare `version` với `expectedVersion`;
7. validate transition table/reason;
8. update status, assignment, resolved timestamp và version;
9. insert history;
10. commit và return refreshed ticket.

`ChangeSupportTicketStatusRequest::authorize()` là HTTP pre-filter. Action vẫn tự authorize trước lock để giữ interface an toàn khi được gọi từ controller khác, command hoặc test; re-check sau lock bảo vệ trường hợp resource scope thay đổi giữa hai thời điểm.

Persistence dùng `saveOrFail()` để nếu model event làm save fail thì history insert không được tiếp tục.

## 6. Models và invariant

### `SupportTicket`

- enum cast cho `status`;
- datetime cast cho `resolved_at`;
- integer cast cho `version`;
- relations `histories`, `createdBy`, `assignedTo`;
- explicit factory contract sau khi model chuyển vào feature namespace;
- `scopeVisibleTo(User $user)` dùng chung cho resource visibility.

`visibleTo()` rule:

```text
admin → thấy tất cả
user khác → thấy ticket created_by hoặc assigned_to chính mình
```

Controller dùng scope thay vì tự viết resource predicate.

### `TicketStatusHistory`

History giữ ticket, from/to status, actor, reason và created timestamp. Model event chặn update/delete qua Eloquent instance. FK ticket dùng `restrictOnDelete()` để delete ticket không cascade xóa audit history.

### Quyết định append-only

BL-01 hiện chọn mức **application-enforced append-only**:

- `TicketStatusHistory` chặn update/delete qua Eloquent model events;
- request/action thông thường không thể sửa `status` hoặc `version` bằng mass assignment;
- `restrictOnDelete()` bảo vệ history khi xóa ticket ở database level.

Đây chưa phải database-enforced audit immutability. `DB::table()`, raw SQL hoặc database account có quyền ghi vẫn có thể bypass model events. Nếu history trở thành compliance/audit boundary, phase production phải bổ sung database privilege/trigger và chỉ cấp quyền insert cho writer được kiểm soát.

## 7. Mass-assignment boundary

`SupportTicket` không fillable các authoritative fields:

- `status`;
- `assigned_to`;
- `resolved_at`;
- `version`.

Trusted Actions/Seeder dùng `forceFill()` có chủ đích. Request payload không quyết định current state hoặc version của server.

## 8. Database contract

### `support_tickets`

- `subject`;
- `status` default `open`;
- nullable `assigned_to`;
- `created_by`;
- nullable `resolved_at`;
- `version` default `0`;
- index `(status, assigned_to, created_at)`.

### `ticket_status_histories`

- `ticket_id` FK `restrictOnDelete()`;
- nullable `from_status`;
- `to_status`;
- `actor_id` FK `restrictOnDelete()`;
- nullable reason tối đa 500 ký tự;
- `created_at` dùng current timestamp;
- index `(ticket_id, created_at, id)`.

Creator FK trên `support_tickets` hiện dùng `cascadeOnDelete()`. Vì history FK restrict, lifecycle delete user/creator cần được quyết định rõ trước production.

## 9. Factory/seeder contract

Factory tạo fixture nhất quán:

```text
open       version 0, history [open]
inProgress version 1, history [open, in_progress]
resolved   version 2, history [open, in_progress, resolved]
closed     version 3, history [open, in_progress, resolved, closed]
```

Factory explicit `$model` và model explicit `newFactory()` vì feature namespace không còn khớp convention factory mặc định.

Seeder tạo demo ticket cho từng status, idempotent theo subject và không giả lập quyền actor bằng manager/staff role.

## 10. Change log

### Initial domain

- Tạo support ticket/history schema.
- Tạo enum, model, factory, seeder.
- Tạo create/status-change Actions.
- Tạo policy/Form Request/controller/UI.
- Thêm transaction, row lock và optimistic version.
- Thêm rollback test khi history insert fail.

### Security/invariant hardening

- Bảo vệ status/version khỏi mass assignment.
- Enforce history update/delete rejection.
- Thêm actor/owner/admin permission matrix.
- Đổi history FK từ cascade sang restrict delete.
- Đổi policy ability thành `changeStatus`.
- Đổi persistence sang `saveOrFail()`.
- Tách `visibleTo()` khỏi controller.
- Chuyển registry display metadata sang translation keys và thêm locale matrix `vi`/`en`.
- Chuyển global catalog metadata/flash message sang translation keys.
- Dọn generic lab route thành `/catalog/labs/{labKey}` để tránh overlap với feature route BL-01.
- Giữ `LabDefinition` thuần key-based; presentation layer chịu trách nhiệm resolve text.
- Đưa authorization trước row lock và re-check sau khi lock.

Các file contract bị ảnh hưởng trong hardening gần nhất:

- `app/Features/StateTransition/Actions/ChangeSupportTicketStatus.php`;
- `app/Features/StateTransition/Policies/SupportTicketPolicy.php`;
- `app/Features/StateTransition/Models/SupportTicket.php`;
- `app/Features/StateTransition/Models/TicketStatusHistory.php`;
- `tests/Feature/StateTransition/StateTransitionTest.php`.

### Feature-module refactor

- Di chuyển domain classes vào `app/Features/StateTransition`.
- Đổi action thành `ChangeSupportTicketStatus`.
- Đổi request thành `ChangeSupportTicketStatusRequest`.
- Đổi controller method thành `changeStatus`.
- Di chuyển factory/seeder/test theo feature.
- Thêm explicit model/factory contract.
- Cập nhật route/provider/imports.

## 11. Test matrix và evidence

Coverage gồm admin/owner authorization, out-of-scope request, full transition matrix 4 × 4, stale conflict, history provenance, mass-assignment protection, history update/delete rejection, ticket delete protection, seeder consistency, rollback khi history fail, UI status control và `visibleTo()` resource scope.

Evidence:

| Kiểm tra | Kết quả |
|---|---|
| Pest SQLite | 38 tests, 230 assertions passed |
| Pest MySQL `laravel-test` | 38 tests, 230 assertions passed |
| MySQL migrate fresh + seed | Passed |
| Route resolution | Passed; route gọi feature controller `changeStatus` |
| Pint | Passed |
| `git diff --check` | Passed |
| Concurrency harness | Parent commit; child stale conflict; final state/version/history nhất quán |

Concurrency output:

```text
child=App\Features\StateTransition\Exceptions\StaleTicket
parent=committed
child_exit=0
final_status=resolved
final_version=2
history_count=3
```

Giới hạn: process đầu tiên của harness điều khiển transaction giữ lock; process thứ hai gọi Action thật. Đây là evidence cho lock/version protocol, chưa phải stress test hoặc proof cho mọi isolation level.

## 12. Open items

1. Quyết định lifecycle khi xóa creator vì creator FK hiện cascade nhưng history FK restrict.
2. Nếu cần audit compliance, bổ sung DB privilege/trigger cho history append-only.
3. Tách state machine pure module khi transition rule tăng đáng kể.
4. Bổ sung API error/DTO contract nếu feature mở public API.
