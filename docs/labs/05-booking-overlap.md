# BL-05 — Overlap và đặt lịch chống trùng

> **Domain minh họa:** Đặt phòng họp theo phòng vật lý  
> **Nhóm:** booking / overlap  
> **Trạng thái:** implemented locally; SQLite + MySQL functional evidence đã pass; two-connection concurrency harness pending
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao một phòng không nhận hai lịch giao nhau, kể cả khi request đến đồng thời?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Người đặt; backend booking use case; quản trị viên có thể hủy lịch.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-05-01 | Khoảng hẹn dùng [start,end); sát biên không overlap. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-05-02 | Overlap nếu existing.start < requested.end AND existing.end > requested.start. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-05-03 | Chỉ trạng thái chiếm capacity (ví dụ confirmed/held) được tính conflict. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-05-04 | Booking phải gắn resource cụ thể; kiểm tra slot ở UI không thay thế kiểm tra khi ghi. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-05-05 | Hủy giải phóng resource theo transition hợp lệ. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-05-01:** Không có hai active booking chồng nhau cho cùng room.
- **INV-05-02:** ends_at > starts_at; timezone/UTC nhất quán.
- **INV-05-03:** Tài nguyên kiểm tra conflict là đúng resource được phân bổ.

## 3. Scenarios cần mô hình hóa

- **SC-05-00:** Overlap hoàn toàn, một phần, bao chứa, sát biên, khác resource.
- **SC-05-01:** Cancelled booking không block.
- **SC-05-02:** Concurrent integration test với MySQL và hai connections: chỉ một thành công.
- **SC-05-03:** Kiểm tra deadlock/retry policy và idempotent retry.
- **SC-05-04:** UI hiển thị available rồi request sau bị conflict vẫn xử lý đúng.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `rooms` | `id, name` | resource key. |
| `room_bookings` | `id, room_id, customer_id, starts_at_utc, ends_at_utc, status, idempotency_key nullable` | index room+status+start; index idempotency key theo scope. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Không có hai active booking chồng nhau cho cùng room. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

FK room; check end>start nếu DB support/check enforcement version đã xác minh. Composite index `(room_id, status, starts_at_utc, ends_at_utc)` cân theo query plan; index không tự ngăn range overlap. MySQL không có exclusion constraint tổng quát như một số DB. Cần chọn locking strategy: khóa row room cha trước check+insert (serialize per room), lock inventory slot rows, hoặc slot inventory materialized. Ghi ADR.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`RoomBooking` status enum; `CreateRoomBooking` use case điều phối transaction. `BookingInterval` pure object kiểm tra start<end/overlap. Đừng đặt logic conflict chỉ trong FormRequest vì race xảy ra sau validation.

### Factory

RoomFactory; BookingFactory active/cancelled với intervals cố định; factory helper tạo non-overlap fixtures. Không dùng random timestamps cho overlap tests.

### Seeder

Một phòng có lịch sát biên, một phòng có lịch active và một cancelled. Dùng UTC values có diễn giải local timezone trong README.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/BookingOverlap/...
├── Http/Controllers/BookingOverlap/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/BookingOverlap/...
├── Domain/BookingOverlap/ # pure rules/value objects nếu giúp rõ
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

Trong transaction, lock row room (SELECT ... FOR UPDATE); query overlap trên blocking statuses; nếu có thì conflict; insert booking; commit. Tất cả writers phải dùng cùng protocol. Alternative slot rows với unique(room_id, slot_start) nhưng duration đa dạng cần phân rã slot consistent.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Parent-row lock dễ giải thích nhưng mọi đặt phòng cùng phòng serialize. Slot inventory scalable hơn với mô hình fixed grid nhưng xử lý duration tùy biến khó. Optimistic check-then-insert không an toàn nếu không có DB invariant bảo vệ. Advisory lock cần vận hành/ownership cẩn thận.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Hai transaction check cùng lúc rồi cùng insert; app instances không dùng cùng lock strategy; lock row sai phạm vi; retry deadlock tạo booking kép; timezone mismatch.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Hai transaction check cùng lúc rồi cùng insert; app instances không dùng cùng lock strategy; lock row sai phạm vi; retry deadlock tạo booking kép; timezone mismatch.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [x] Overlap hoàn toàn, một phần, bao chứa, sát biên, khác resource.
- [x] Cancelled booking không block.
- [x] Idempotency key cùng interval trả lại booking cũ; khác interval bị reject.
- [x] Booking ngoài availability và local time/DST invalid có message rõ.
- [ ] Concurrent integration test với MySQL và hai connections: chỉ một thành công.
- [x] Transaction dùng row lock room và Laravel deadlock retry policy.
- [x] UI hiển thị available và xử lý conflict ở write path.

Evidence hiện tại: 89 tests, 435 assertions passed trên SQLite và MySQL 9.5.0 (`laravel_test`); migration, FK, unique key, CHECK constraint, route, policy, overlap boundary, idempotency, privacy scope và cancel flow đã chạy. Chưa claim two-connection race outcome hoặc deadlock harness thực tế.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Hai transaction check cùng lúc rồi cùng insert; app instances không dùng cùng lock strategy; lock row sai phạm vi; retry deadlock tạo booking kép; timezone mismatch.

**Mở rộng sau MVP:** Waitlist, slot capacity >1, recurring bookings, hold TTL. Không tuyên bố “chống trùng hoàn toàn” nếu test chỉ chạy SQLite hoặc tuần tự.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Mở hai connection tranh cùng phòng/interval và show test. Hỏi: chỉ unique index có thể ngăn overlap tùy ý không?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.

## 16. Implementation map

- `app/Features/BookingOverlap/ValueObjects/BookingInterval.php`: validate local interval và normalize UTC.
- `app/Features/BookingOverlap/Actions/CreateRoomBooking.php`: lock room, kiểm tra active overlap, idempotency và insert trong transaction.
- `app/Features/BookingOverlap/Actions/CancelRoomBooking.php`: authorize và cancel idempotently trong transaction.
- `app/Features/BookingOverlap/Models/RoomBooking.php`: booking persistence, status/datetime casts và relations.
- `database/migrations/2026_11_14_110000..110001`: booking table, indexes, FK và MySQL CHECK constraints.
- `resources/views/labs/booking-overlap/index.blade.php`: book available slot và cancel booking demo.
- UI BL-05 dùng learning-first template: objectives → theory → invariant → code flow → implementation/trade-offs → practice → evidence; có thể tái sử dụng cho các BL tiếp theo.
- `tests/Feature/BookingOverlap/BookingOverlapTest.php`: boundary, conflict, cancellation, idempotency và route coverage.

## 17. Verification limits

- `composer test`: 89 tests, 435 assertions trên SQLite in-memory.
- `composer test:mysql`: 89 tests, 435 assertions trên MySQL 9.5.0 với database `laravel_test`.
- Room-row lock protocol đã triển khai theo cùng thứ tự cho mọi create request; chưa có process barrier để chứng minh race outcome.
- BL-05 không tuyên bố overlap concurrency production-ready cho tới khi two-connection MySQL harness được chạy.
