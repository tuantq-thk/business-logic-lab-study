# BL-06 Technical Implementation Record

> Cập nhật: 2026-11-14
> Phạm vi: `laravel/`
> Trạng thái: implemented locally; SQLite functional evidence đã pass; MySQL/concurrency evidence pending.

## 1. Business boundary

BL-06 minh họa đăng ký hội thảo và tách ba câu hỏi thường bị trộn: request đúng hình dạng không, attendee có được phép trong context hiện tại không, và state có được ghi atomically khi cạnh tranh không.

```text
shape_is_valid
AND event_is_open
AND now >= registration_opens_at_utc
AND now < registration_closes_at_utc
AND confirmed_count < capacity
AND attendee_has_no_registration
AND ticket_eligibility_is_valid
```

`FormRequest` chỉ xử lý shape/syntax. `RegistrationPolicy` là pure decision nhận authoritative facts. `RegisterForEvent` load lại state và sở hữu transaction.

## 2. Data model

`contextual_validation_events` lưu title/description, UTC event và registration window, capacity, `draft|open|closed` status cùng nullable `invite_code_hash`.

`contextual_validation_registrations` lưu event, attendee, `standard|invite` ticket, `confirmed|cancelled` status; unique `(event_id, attendee_id)` bảo vệ single registration policy và index `(event_id, status)` hỗ trợ capacity count.

MySQL yêu cầu foreign key columns có index và khuyến nghị referenced key là unique/not-null; migration giữ FK và composite indexes rõ ràng. [MySQL foreign key constraints](https://dev.mysql.com/doc/refman/8.4/en/create-table-foreign-keys.html)

## 3. Write protocol

```text
transaction
  → SELECT event FOR UPDATE
  → check event + attendee duplicate
  → count confirmed registrations
  → verify invite eligibility server-side
  → evaluate RegistrationPolicy
  → insert registration
  → commit
```

Event row là serialization point cho capacity của một event. Unique constraint là backstop cho duplicate; nó không thay thế policy check. Transaction boundary dùng Laravel `DB::transaction`, tự rollback khi exception. [Laravel database transactions](https://laravel.com/docs/13.x/database#database-transactions)

## 4. Validation and authorization boundary

- `StoreEventRegistrationRequest`: required/integer/exists, enum ticket type, invite code string/length.
- Controller chỉ map HTTP input → use case và typed exceptions → localized messages.
- Action reload event trong transaction, không tin event instance trước khi lock.
- Invite code dùng `Hash::check` với hash server-side; không lưu plaintext và không render secret.
- Contextual errors tách khỏi malformed input: duplicate, full, closed window và ineligible có message khác nhau.

Laravel FormRequest encapsulates `authorize` và `rules`; BL-06 giữ policy ở use case để không query mutable business state trong transport layer. [Laravel validation](https://laravel.com/docs/13.x/validation), [Form Request validation](https://laravel.com/docs/13.x/validation#form-request-validation)

## 5. Failure and security review

- Stale form sau khi window đóng: action re-checks current event.
- Tampered `event_id`/`ticket_type`: server loads event và enum-validates input.
- Duplicate replay: application check + database unique constraint.
- Invite probing: generic eligibility error không xác nhận invite code hay attendee data.
- Last-capacity race: event row lock + count trong transaction; cần MySQL two-connection harness trước khi claim production-grade concurrency.
- Không gọi external network trong transaction.

## 6. Verification

| Check | Result |
|---|---|
| SQLite full suite | 96 tests, 449 assertions passed |
| BL-06 focused suite | 7 tests, 14 assertions passed |
| Pint | Passed |
| Translation parity | 82/82 EN/VI keys matched |
| MySQL functional/concurrency | Pending runtime access and two-connection harness |

Test claims hiện chứng minh functional behavior trên SQLite. Chưa claim MySQL locking/isolation cho tới khi chạy profile MySQL và harness hai connection.

## 7. UI learning contract

UI theo [`docs/BL_UI_TEMPLATE.md`](../BL_UI_TEMPLATE.md): hero → reading path → objectives → theory → pseudo-code invariant → code flow → trade-off → threat review → practice. Form chỉ xuất hiện sau phần giải thích để phân biệt “valid shape” và “eligible now”.

## 8. Next boundary

1. Chạy migration và full suite trên MySQL thật.
2. Thêm two-connection harness cạnh tranh capacity cuối cùng.
3. Thêm EXPLAIN cho event/status count khi dữ liệu lớn.
4. Chỉ sau đó cân nhắc cancellation/re-registration, waitlist hoặc nhiều eligibility source.
