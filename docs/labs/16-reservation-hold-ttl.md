# BL-16 — Giữ chỗ tạm thời có TTL

> **Domain minh họa:** Ghế hội thảo  
> **Nhóm:** reservation / hold / ttl  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao chỗ đang checkout tạm bị giữ, tự hết hạn và không bị confirm đồng thời expiry?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Attendee; booking use case; expiry worker; payment simulator.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-16-01 | Hold chiếm một seat đến expires_at, sau đó không chiếm nếu chưa confirmed. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-16-02 | Hold lifetime tính theo server clock policy; client không chọn expiry. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-16-03 | Confirm và expire là mutually exclusive state transition atomic. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-16-04 | Payment success arriving after expiry follows explicit policy (reject/refund/reacquire). | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-16-05 | Expired rows/history retained according to audit needs. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-16-01:** Capacity active holds + confirmed ≤ total seats.
- **INV-16-02:** Một hold chỉ confirm/release/expire một lần.
- **INV-16-03:** Expired hold không thể confirm qua stale UI.

## 3. Scenarios cần mô hình hóa

- **SC-16-00:** Hold expires at exact boundary policy.
- **SC-16-01:** Concurrent create last seat.
- **SC-16-02:** Confirm vs expiry race with two connections.
- **SC-16-03:** Worker repeated; no double release.
- **SC-16-04:** Payment callback after expiration policy.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `events` | `id,total_capacity` | capacity. |
| `seat_holds` | `id,event_id,attendee_id,seat_no nullable,status,expires_at,confirmed_at nullable` | unique(event_id,seat_no) when assigned seat; active-state uniqueness requires design. |
| `registrations` | `id,event_id,attendee_id,status,hold_id unique` | confirmed result. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Capacity active holds + confirmed ≤ total seats. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

For assigned seats unique(event_id,seat_no) blocks duplicate regardless of status unless release clears seat or separate allocation table. For general capacity use atomic counter/slot rows with lock; DB partial unique index support differs by engine. Index `(status,expires_at)`; status string. FK and unique attendee policy.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`CreateSeatHold`, `ConfirmSeatHold`, `ExpireSeatHold`; state machine with expiry computed from injected clock; worker uses conditional update/lock. A Redis TTL may trigger cleanup but DB remains authoritative.

### Factory

HoldFactory states active/expired/confirmed; capacity fixtures matching event totals. Explicit expiresAt; no sleep-based tests.

### Seeder

Event with one available seat, one held nearing expiry, one confirmed. Reset demo safely.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/ReservationHoldTtl/...
├── Http/Controllers/ReservationHoldTtl/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/ReservationHoldTtl/...
├── Domain/ReservationHoldTtl/ # pure rules/value objects nếu giúp rõ
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

Transaction lock event/capacity row; reclaim expired holds or calculate availability; insert hold with expires_at; confirm/expire both use row lock and state check. Worker scans due rows in chunks and transitions idempotently.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

DB timestamp state is durable and queryable but cleanup needed. Redis TTL efficient but expiry notifications are not guaranteed exact; do not rely only on key expiry for business correctness. Per-seat rows easy to constrain; pooled capacity needs atomic counter.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** TTL key evicted early/late; worker backlog; app clock skew; hold count drift; confirm commits after expiration; stale client acts on expired hold.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- TTL key evicted early/late; worker backlog; app clock skew; hold count drift; confirm commits after expiration; stale client acts on expired hold.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Hold expires at exact boundary policy.
- [ ] Concurrent create last seat.
- [ ] Confirm vs expiry race with two connections.
- [ ] Worker repeated; no double release.
- [ ] Payment callback after expiration policy.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** TTL key evicted early/late; worker backlog; app clock skew; hold count drift; confirm commits after expiration; stale client acts on expired hold.

**Mở rộng sau MVP:** Payment authorization, grace period, waitlist promotion, reservation capacity pooling.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Freeze time before/after expiration and race confirm/expire. Hỏi: expiry có tự xảy ra đúng millisecond chỉ vì expires_at đã qua?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
