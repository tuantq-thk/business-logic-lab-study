# BL-07 — Hủy, đổi lịch và chính sách hoàn tác

> **Domain minh họa:** Đăng ký khóa học có hạn chót hủy  
> **Nhóm:** cancellation / rescheduling  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Khi thao tác đảo ngược xảy ra, phải giải phóng tài nguyên, tính phí và giữ dấu vết thế nào?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Người đăng ký; organizer; tác vụ hoàn tiền mô phỏng.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-07-01 | Hủy chỉ hợp lệ ở trạng thái cho phép và trước cutoff hoặc theo quyền override. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-07-02 | Cutoff được tính theo timezone/policy rõ; chính sách có thể khác theo loại đăng ký. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-07-03 | Hủy và release seat là cùng một thay đổi atomic trong DB. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-07-04 | Nếu có refund side effect, ghi ý định/outbox sau commit; không gọi provider trong DB transaction. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-07-05 | Reschedule là hủy + tạo lựa chọn mới theo một policy, không sửa start date âm thầm. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-07-01:** Một đăng ký không được release capacity hai lần.
- **INV-07-02:** History lưu old/new schedule, actor, reason, policy version.
- **INV-07-03:** Refund intent không vượt amount đã thu.

## 3. Scenarios cần mô hình hóa

- **SC-07-00:** Before/at/after cutoff boundary.
- **SC-07-01:** Cancel twice; only one capacity release.
- **SC-07-02:** Wrong actor and override reason.
- **SC-07-03:** Concurrent cancel/reschedule.
- **SC-07-04:** Outbox is atomic with local state; provider failure retried without duplicate refund.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `course_registrations` | `id, course_id, attendee_id, status, cancelled_at nullable` | current state. |
| `registration_histories` | `id, registration_id, action, from_state, to_state, actor_id, reason, policy_version, created_at` | append-only. |
| `refund_requests` | `id, registration_id, amount_minor, status, idempotency_key` | chỉ nếu lab mô phỏng refund. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Một đăng ký không được release capacity hai lần. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Unique registration per course/attendee tùy cho phép re-enroll. Index `(course_id,status)` capacity; FK history; refund idempotency unique scope. Không hard-delete canceled row; giữ audit. Money minor/currency explicit.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

Use cases `CancelRegistration`, `RescheduleRegistration`; `CancellationPolicy` pure decision; `RegistrationHistory` immutable. Side-effect adapter isolated; policy date/time injected.

### Factory

RegistrationFactory reserved/confirmed/cancelled; HistoryFactory consistent; RefundFactory pending/failed states. Ensure capacity test fixtures match counts.

### Seeder

Cases cutoff passed/not passed, override actor, refund pending. Use fixed policy fixture.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/CancellationRescheduling/...
├── Http/Controllers/CancellationRescheduling/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/CancellationRescheduling/...
├── Domain/CancellationRescheduling/ # pure rules/value objects nếu giúp rõ
├── Jobs/                         # chỉ khi có async work
└── Events/                       # chỉ khi event có consumer/use case
```

| Thành phần | Trách nhiệm | Tránh làm |
|---|---|---|
| Form Request | Shape, required/format, authorize request cơ bản | Quyết định rule cần transaction hoặc state mới nhất |
| Controller | HTTP mapping, gọi use case, response | Tính toán nghiệp vụ dài, xử lý lock |
| Action/use case | Điều phối một hành động, transaction boundary | Trở thành class một dòng cho mọi CRUD |
| Domain function/value object | Quy tắc thuần, xác định, test nhanh | Query Eloquent hoặc gửi email |
| Eloquent model | Persistence, relation, casts | Cho phép mass assignment bypass invariant |
| Policy | Quyền actor/resource/action | Kiểm tra feature enabled thay cho business authorization |

**Không bắt buộc** tạo Repository nếu Eloquent query đơn giản và chưa cần thay persistence. Nếu query phức tạp hoặc có nhiều adapter, ghi lý do trước khi thêm abstraction.

## 8. Luồng xử lý và thuật toán

`HTTP input → authenticate → authorize → validate shape → load authoritative state → evaluate business policy → transaction/constraint → persist outcome/history → dispatch side effect after commit → map result`

### Thuật toán tham chiếu

Lock registration and capacity row; re-read state/time; evaluate policy; update status + history + release count/reservation in transaction; write outbox/refund intent; commit; worker performs external action idempotently.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Soft cancel/status retains history and supports restoration but filters must exclude canceled. Hard delete simple but erases evidence and can break references. Compensation is safer than pretending distributed external action is atomic.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Refund succeeds but DB rolls back; seat released twice; policy timezone off by one hour/day; reschedule frees original spot but fails before new reservation without defined compensation.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Refund succeeds but DB rolls back; seat released twice; policy timezone off by one hour/day; reschedule frees original spot but fails before new reservation without defined compensation.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Before/at/after cutoff boundary.
- [ ] Cancel twice; only one capacity release.
- [ ] Wrong actor and override reason.
- [ ] Concurrent cancel/reschedule.
- [ ] Outbox is atomic with local state; provider failure retried without duplicate refund.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Refund succeeds but DB rolls back; seat released twice; policy timezone off by one hour/day; reschedule frees original spot but fails before new reservation without defined compensation.

**Mở rộng sau MVP:** Partial refunds, cancellation fee, waitlist promotion, policy versioning. Money integration is simulated unless provider settlement tested.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Hủy trước/sau cutoff; replay cancellation; show resource counter and history. Hỏi: làm sao đảm bảo khi gateway không nằm trong transaction?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
