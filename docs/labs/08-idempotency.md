# BL-08 — Idempotency và retry an toàn

> **Domain minh họa:** Tạo đơn hàng qua API  
> **Nhóm:** idempotency  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Nếu client timeout rồi gửi lại yêu cầu, hệ thống trả lại kết quả cũ hay tạo side effect mới?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** API client; order service; worker/DB.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-08-01 | Key được scoped theo actor/operation/tenant phù hợp. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-08-02 | Cùng key + cùng canonical payload trả cùng logical outcome. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-08-03 | Cùng key + payload khác bị conflict. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-08-04 | Request đang xử lý có trạng thái rõ; retry không chạy song song side effect lần hai. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-08-05 | Idempotency record và business result commit theo transaction hoặc protocol có recovery. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-08-01:** Unique scope(key) trong thời hạn retention.
- **INV-08-02:** Payload fingerprint ổn định/canonical; không lưu raw sensitive payload không cần thiết.
- **INV-08-03:** Một logical request tạo tối đa một order.

## 3. Scenarios cần mô hình hóa

- **SC-08-00:** Same key/payload sequential retry yields one order.
- **SC-08-01:** Same key different payload rejected.
- **SC-08-02:** Concurrent same key yields one side effect.
- **SC-08-03:** Crash before commit allows safe retry; crash after external effect handled by outbox/dedup.
- **SC-08-04:** Expired key behavior explicitly defined.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `idempotency_records` | `id, scope, key_hash, request_hash, status, resource_type, resource_id nullable, response_code nullable, response_body nullable, expires_at` | unique (scope,key_hash). |
| `orders` | `id, customer_id, total_minor, status` | business result. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Unique scope(key) trong thời hạn retention. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Unique composite index `(scope,key_hash)`; hashed key tránh lưu key nguyên nếu xem là credential; index expiry cho cleanup. Đặt max length/hash algorithm/version. Không lưu toàn bộ response chứa PII nếu chỉ cần resource reference.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`IdempotencyRecord`; `CreateOrder` orchestrates. Key canonicalization middleware can extract, nhưng quyết định retry domain ở service. Response replay serializer versioned nếu lưu body.

### Factory

IdempotencyRecordFactory completed/in_progress/conflict; OrderFactory. Key fixtures deterministic; không generate key ngẫu nhiên trong assertion.

### Seeder

Không seed operational idempotency data trong production; local demo có request samples resettable.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/Idempotency/...
├── Http/Controllers/Idempotency/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/Idempotency/...
├── Domain/Idempotency/ # pure rules/value objects nếu giúp rõ
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

Canonicalize/hash payload; transaction insert idempotency claim with unique key; duplicate claim loads existing: compare hash, return completed response or conflict/in-progress; owner executes and stores resource/outcome; commit. Nếu operation external, cần outbox/workflow; DB transaction alone insufficient.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Unique constraint + transaction là nền đơn giản; lock wait/duplicate handling phải rõ. Cache/Redis key nhanh nhưng durability/eviction semantics phức tạp. Storing full response eases exact replay but raises PII/version concerns. Retention must exceed expected retry window.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Key scope too broad leaks result between users; fingerprint ignores meaningful field; key expires too early; duplicate inserts mapped as generic 500; in-progress stuck forever.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Key scope too broad leaks result between users; fingerprint ignores meaningful field; key expires too early; duplicate inserts mapped as generic 500; in-progress stuck forever.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Same key/payload sequential retry yields one order.
- [ ] Same key different payload rejected.
- [ ] Concurrent same key yields one side effect.
- [ ] Crash before commit allows safe retry; crash after external effect handled by outbox/dedup.
- [ ] Expired key behavior explicitly defined.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Key scope too broad leaks result between users; fingerprint ignores meaningful field; key expires too early; duplicate inserts mapped as generic 500; in-progress stuck forever.

**Mở rộng sau MVP:** Async request status, provider idempotency key propagation, retention cleanup, response schema versioning.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Gửi request, giả lập timeout response rồi retry; đổi payload giữ key. Hỏi: disable nút gửi có xử lý case client retry không?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
