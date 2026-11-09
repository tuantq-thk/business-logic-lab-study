# BL-18 — Rate limit, quota và giới hạn sử dụng

> **Domain minh họa:** API tạo báo cáo cho tài khoản  
> **Nhóm:** rate / limits / quotas  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao giới hạn hành động theo actor/plan/time window mà vẫn xử lý request đồng thời đúng?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** API client; account admin; rate limiter; quota billing policy.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-18-01 | Rate limit bảo vệ tần suất; quota giới hạn tổng usage theo kỳ—khác nhau. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-18-02 | Key scope xác định theo account/user/IP/endpoint; tránh key dễ spoof. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-18-03 | Quota reservation/consume/release policy rõ khi request thất bại. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-18-04 | Response báo retry/reset theo semantics; không lộ quota của tenant khác. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-18-05 | Admin bypass có audit và giới hạn. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-18-01:** Atomic counter không vượt hard quota nếu policy không cho overage.
- **INV-18-02:** Key partition không gộp khách khác.
- **INV-18-03:** Window/reset timezone policy xác định.

## 3. Scenarios cần mô hình hóa

- **SC-18-00:** Concurrent requests at remaining quota.
- **SC-18-01:** Tenant key isolation; spoofed headers.
- **SC-18-02:** Window boundary and reset.
- **SC-18-03:** Failed operation consumption policy.
- **SC-18-04:** Redis unavailable fallback behavior; do not silently remove security limit.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `usage_quotas` | `id, account_id, metric, period_start, limit_value, used_value, version` | unique(account,metric,period_start). |
| `usage_events` | `id, account_id, metric, quantity, request_ref, created_at` | optional ledger for billing/audit; unique request_ref scope. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Atomic counter không vượt hard quota nếu policy không cho overage. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Counters integer BIGINT; unique account+metric+period. TTL counters may use Redis with atomic Lua/INCR; durable billing usage should persist ledger or reconcile. Avoid storing IP plaintext longer than needed.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`QuotaPolicy` evaluates limits; `ConsumeQuota` atomic use case; middleware rate limiter handles request-rate separately. Backend policy derives account from auth, not arbitrary header.

### Factory

AccountFactory plans free/paid; QuotaFactory near/at/exceeded; UsageEventFactory idempotent request refs.

### Seeder

Accounts at 0%, 99%, limit; frozen period clock. Redis optional integration test only for rate limit semantics.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/RateLimitsQuotas/...
├── Http/Controllers/RateLimitsQuotas/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/RateLimitsQuotas/...
├── Domain/RateLimitsQuotas/ # pure rules/value objects nếu giúp rõ
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

Rate limit: atomic increment with window/expiry primitive. Quota: transaction/conditional update `used + q <= limit`, optionally idempotency ledger. Release reservation only if business operation guarantees no consumption.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Laravel cache limiter is straightforward for abuse throttling but not billing-grade durable quota. Redis atomic counters scale but need expiration/consistency; DB ledger auditable but higher write cost. Fixed window has boundary bursts; sliding/token bucket smoother but more complex.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Check then increment race; cache eviction grants quota; period uses server timezone; retry increments twice; abuse of forwarded IP header.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Check then increment race; cache eviction grants quota; period uses server timezone; retry increments twice; abuse of forwarded IP header.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Concurrent requests at remaining quota.
- [ ] Tenant key isolation; spoofed headers.
- [ ] Window boundary and reset.
- [ ] Failed operation consumption policy.
- [ ] Redis unavailable fallback behavior; do not silently remove security limit.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Check then increment race; cache eviction grants quota; period uses server timezone; retry increments twice; abuse of forwarded IP header.

**Mở rộng sau MVP:** Overage billing, plan changes mid-period, token bucket, distributed rate limiting.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Hai request tranh quota cuối, phân biệt 429 throttle và quota exhausted. Hỏi: đâu là durable source of truth cho usage tính tiền?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
