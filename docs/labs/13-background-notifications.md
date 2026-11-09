# BL-13 — Thông báo nền, retry và lỗi provider

> **Domain minh họa:** Email xác nhận đăng ký  
> **Nhóm:** background / notifications  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao gửi notification sau khi giao dịch chính thành công mà provider lỗi/retry không làm sai nghiệp vụ?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Registrant; web request; queue worker; mail provider.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-13-01 | Registration commit không phụ thuộc email provider availability. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-13-02 | Notification chỉ dispatch sau commit; worker nhận đủ immutable reference/data cần thiết. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-13-03 | Retry policy có attempts/backoff/timeout; terminal failures quan sát được. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-13-04 | Duplicate job không gây duplicate side effect vượt mức chấp nhận; email duplicate semantics nêu rõ. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-13-05 | Template không chứa dữ liệu vượt quyền/PII không cần. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-13-01:** Không gửi email xác nhận cho transaction rollback.
- **INV-13-02:** Mỗi logical notification có dedupe key nếu duplicate không chấp nhận.
- **INV-13-03:** Failed job có thể điều tra/replay an toàn.

## 3. Scenarios cần mô hình hóa

- **SC-13-00:** Rollback does not dispatch; after commit does.
- **SC-13-01:** Transient provider fail retries; permanent fail visible.
- **SC-13-02:** Duplicate job dedupe.
- **SC-13-03:** Payload/template escaping and recipient correctness.
- **SC-13-04:** Worker timeout and retry config consistent.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `event_registrations` | `id, attendee_id, event_id, status` | business record. |
| `notification_deliveries` | `id, registration_id, type, dedupe_key unique, status, attempts, last_error_code, sent_at nullable` | nếu cần audit delivery; Laravel queue tables cho job infra riêng. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Không gửi email xác nhận cho transaction rollback. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Laravel queue infrastructure tables theo driver/guide đã pin. Domain delivery table chỉ khi cần state/reporting; đừng duplicate queue state không mục đích. Unique dedupe key; error text sanitize/truncate.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`SendRegistrationConfirmation` queued job implements idempotent handler; `NotificationDelivery` if requirement; mail provider adapter. Dispatch after DB commit; job reloads authorized/safe data or uses snapshot policy.

### Factory

RegistrationFactory confirmed/pending; NotificationDeliveryFactory pending/failed/sent; provider fake in tests. Không cần seed real email credentials.

### Seeder

Mailpit/local sink config; registration demo và failed delivery row chỉ ở local/dev.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/BackgroundNotifications/...
├── Http/Controllers/BackgroundNotifications/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/BackgroundNotifications/...
├── Domain/BackgroundNotifications/ # pure rules/value objects nếu giúp rõ
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

Persist registration (+ outbox/delivery intent) → commit → enqueue → worker checks dedupe/status → call provider with timeout → mark sent; retry transient errors; permanent errors failed/alert. Exactly-once external email generally cannot be guaranteed just by queue; aim at at-least-once plus dedupe where possible.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Direct synchronous mail simple but increases latency and couples availability. Queue improves resilience but requires operations/retry/idempotency. Laravel afterCommit dispatch prevents premature job; transactional outbox needed if losing enqueue between DB commit and broker publish is unacceptable.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Queue dispatch lost after DB commit; duplicate email on worker crash after send before ack; retry storms; failed jobs contain PII; job references deleted record.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Queue dispatch lost after DB commit; duplicate email on worker crash after send before ack; retry storms; failed jobs contain PII; job references deleted record.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Rollback does not dispatch; after commit does.
- [ ] Transient provider fail retries; permanent fail visible.
- [ ] Duplicate job dedupe.
- [ ] Payload/template escaping and recipient correctness.
- [ ] Worker timeout and retry config consistent.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Queue dispatch lost after DB commit; duplicate email on worker crash after send before ack; retry storms; failed jobs contain PII; job references deleted record.

**Mở rộng sau MVP:** Outbox (BL-19), user preferences, localization, digest. Avoid building provider fallback unless requirement.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Fake provider fail once then succeed; prove registration persisted. Hỏi: queue retry có thể tạo gửi email hai lần không?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
