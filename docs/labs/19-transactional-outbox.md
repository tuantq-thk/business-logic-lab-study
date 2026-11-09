# BL-19 — Transactional outbox và phát sự kiện đáng tin cậy

> **Domain minh họa:** Đơn hàng đồng bộ sang hệ thống vận chuyển  
> **Nhóm:** transactional / outbox  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao đảm bảo thay đổi DB và ý định phát event không bị lệch nhau khi broker gặp lỗi?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Order service; outbox publisher; broker; consumer.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-19-01 | Order state và outbox record commit trong cùng DB transaction. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-19-02 | Publisher gửi event rồi đánh dấu published; crash giữa send/mark có thể gửi lặp. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-19-03 | Consumer deduplicate theo event ID. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-19-04 | Event schema có version và không chứa dữ liệu thừa. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-19-05 | Ordering requirement (per aggregate/global/none) được nói rõ. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-19-01:** Không có order confirmed mà thiếu outbox intent.
- **INV-19-02:** Duplicate delivery không tạo side effect duplicate consumer.
- **INV-19-03:** Outbox event cuối cùng được retry/alert nếu publisher hoạt động.

## 3. Scenarios cần mô hình hóa

- **SC-19-00:** DB rollback leaves neither order change nor outbox.
- **SC-19-01:** Broker fail leaves message retryable.
- **SC-19-02:** Crash after send before mark creates duplicate safely handled.
- **SC-19-03:** Two publishers do not corrupt row state.
- **SC-19-04:** Schema version compatibility and poison message handling.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `orders` | `id, status, version` | aggregate. |
| `outbox_messages` | `id, event_id unique, aggregate_type, aggregate_id, event_type, schema_version, payload, occurred_at, published_at nullable, attempts, available_at` | index unpublished ordering/claim. |
| `consumer_inbox` | `consumer, event_id unique, processed_at` | consumer dedupe when needed. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Không có order confirmed mà thiếu outbox intent. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Event ID unique; index `(published_at,available_at,id)` may not perfectly match claim query; inspect plan and DB locking. Payload JSON with schema version. Retention/archive policy. Lock claim rows safely for DB version; SKIP LOCKED support must be verified.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`OutboxMessage` persistence entity; explicit application/domain event builder in use case; publisher job uses lease/claim; consumer idempotency. Avoid global model observers creating opaque outbox events.

### Factory

OutboxFactory unpublished/failed/published; deterministic event payload version; OrderFactory confirmed. Factories obey same aggregate/event relation.

### Seeder

Local pending/published sample for admin view; do not seed into production. Broker mocked in tests.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/TransactionalOutbox/...
├── Http/Controllers/TransactionalOutbox/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/TransactionalOutbox/...
├── Domain/TransactionalOutbox/ # pure rules/value objects nếu giúp rõ
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

Transaction update order + insert outbox; publisher claims due row(s), sends event, marks published; on crash it may resend; consumer inserts inbox event ID and applies business side effect transactionally. Alert after max attempts, support replay.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Direct publish inside transaction has dual-write gap and holds transaction on network. Outbox adds durable table/publisher operations and at-least-once semantics; consumer idempotency still required. CDC alternative reduces polling but adds platform dependency.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Mark published before broker ack loses event; message sent twice; outbox grows unbounded; poison event blocks queue; global ordering bottleneck.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Mark published before broker ack loses event; message sent twice; outbox grows unbounded; poison event blocks queue; global ordering bottleneck.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] DB rollback leaves neither order change nor outbox.
- [ ] Broker fail leaves message retryable.
- [ ] Crash after send before mark creates duplicate safely handled.
- [ ] Two publishers do not corrupt row state.
- [ ] Schema version compatibility and poison message handling.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Mark published before broker ack loses event; message sent twice; outbox grows unbounded; poison event blocks queue; global ordering bottleneck.

**Mở rộng sau MVP:** CDC publisher, partition by aggregate, dead-letter tooling, event contracts. Do not use outbox for every email if simple after-commit queue meets reliability need.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Simulate crash boundary after publish, redeliver at consumer, show dedupe. Hỏi: outbox đảm bảo exactly once không?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
