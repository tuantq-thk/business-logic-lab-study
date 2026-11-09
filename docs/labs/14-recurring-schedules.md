# BL-14 — Ngày giờ lặp lại và tác vụ định kỳ

> **Domain minh họa:** Nhắc hóa đơn hàng tháng  
> **Nhóm:** recurring / schedules  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao tính lần chạy kế tiếp khi ngày trong tháng không tồn tại hoặc timezone đổi DST?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Account owner; scheduler; reminder worker.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-14-01 | Recurrence rule được giới hạn (ví dụ monthly day-of-month), không tuyên bố hỗ trợ mọi RRULE. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-14-02 | Local wall time + IANA timezone là ý định; next occurrence giải ra instant theo policy. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-14-03 | 31 hàng tháng: clamp cuối tháng, skip tháng, hay last-day semantics phải chọn. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-14-04 | DST nonexistent/ambiguous time policy explicit. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-14-05 | Mỗi scheduled occurrence có idempotent identity; worker retry không gửi lặp. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-14-01:** next_run sau occurrence đã xử lý theo rule.
- **INV-14-02:** Một occurrence key chỉ xử lý một lần logic.
- **INV-14-03:** Timezone được giữ theo user/account, không suy từ server.

## 3. Scenarios cần mô hình hóa

- **SC-14-00:** Jan 31 policy across leap/non-leap February.
- **SC-14-01:** DST gap and duplicate local hour policy.
- **SC-14-02:** Timezone change behavior (future times only vs all).
- **SC-14-03:** Two schedulers claim same occurrence; unique key/lock prevents duplicate.
- **SC-14-04:** Clock/timezone independent deterministic tests.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `reminder_schedules` | `id, owner_id, timezone, rule_kind, day_of_month nullable, local_time, next_run_at_utc, active` | index active+next_run. |
| `reminder_occurrences` | `id, schedule_id, occurrence_key, scheduled_at_utc, status, sent_at nullable` | unique(schedule_id,occurrence_key). |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: next_run sau occurrence đã xử lý theo rule. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Store rule components explicitly for supported recurrence; avoid arbitrary cron text from user. `next_run_at_utc` indexed scheduler query. Preserve timezone identifier. Occurrence unique key computed from local scheduled date/time + rule version.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`RecurrenceRule` value object with calendar arithmetic; `CalculateNextRun` pure; `ReminderOccurrence` state. Scheduler claims due work with safe locking/claim protocol.

### Factory

ScheduleFactory monthly31/last-day/DST timezone; OccurrenceFactory pending/sent. Test dates deterministic with Carbon freeze but domain function receives explicit instant.

### Seeder

Cases on Jan 31, Feb, DST zone sample; production VN no DST does not remove need to model timezone correctly for portability.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/RecurringSchedules/...
├── Http/Controllers/RecurringSchedules/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/RecurringSchedules/...
├── Domain/RecurringSchedules/ # pure rules/value objects nếu giúp rõ
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

Get next local calendar occurrence from previous local date using policy → resolve timezone ambiguity/gap → persist UTC instant and occurrence key atomically → worker claims once and sends. Avoid adding fixed 30 days for “monthly”.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Restricted explicit recurrence is explainable and safe; general RFC-style recurrence increases complexity. Precompute next_run simplifies scheduler query but must update atomically; calculate every scan is simpler but expensive. UTC alone loses wall-time intent.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** AddDays(30) drift; naive local timestamp duplicate; scheduler runs twice; timezone database rules change; occurrence generated after schedule edit.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- AddDays(30) drift; naive local timestamp duplicate; scheduler runs twice; timezone database rules change; occurrence generated after schedule edit.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Jan 31 policy across leap/non-leap February.
- [ ] DST gap and duplicate local hour policy.
- [ ] Timezone change behavior (future times only vs all).
- [ ] Two schedulers claim same occurrence; unique key/lock prevents duplicate.
- [ ] Clock/timezone independent deterministic tests.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** AddDays(30) drift; naive local timestamp duplicate; scheduler runs twice; timezone database rules change; occurrence generated after schedule edit.

**Mở rộng sau MVP:** Multiple recurrence patterns, holidays/business days, pause/resume. Version rules so changing schedule doesn’t rewrite sent history.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Calculate next three reminders for day 31 and DST case. Hỏi: “mỗi tháng” có tương đương 30 ngày không?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
