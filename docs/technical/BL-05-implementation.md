# BL-05 Technical Implementation Record

> Cập nhật: 2026-11-14
> Phạm vi: `laravel/`
> Trạng thái: implemented locally; SQLite + MySQL functional evidence đã pass; concurrency harness pending.

## 1. Boundary và business decisions

BL-05 tạo và hủy booking phòng. Khoảng thời gian dùng half-open `[start, end)`: booking sát biên được phép, còn overlap dùng:

```text
existing.starts_at_utc < requested.ends_at_utc
AND existing.ends_at_utc > requested.starts_at_utc
```

Các trạng thái `held` và `confirmed` chiếm capacity; `cancelled` không block. Booking nhận local date/time theo timezone phòng rồi normalize sang UTC trước persistence. DST gap và timezone không hợp lệ bị reject.

## 2. Concurrency protocol

Mọi create path dùng cùng protocol:

```text
transaction(attempts: 3)
  → SELECT active room FOR UPDATE
  → lookup idempotency key của user
  → query active overlap theo room/status/UTC interval
  → insert confirmed booking
  → commit
```

Khóa row phòng là serialization point theo resource cụ thể. Unique/index không tự giải quyết arbitrary range overlap; index hỗ trợ lookup, còn invariant phụ thuộc transaction protocol. Deadlock retry bao quanh toàn transaction theo Laravel transaction API.

Laravel khuyến nghị đặt pessimistic lock trong transaction; `lockForUpdate()` giữ selected rows ổn định tới commit. [Laravel transactions/deadlock retry](https://laravel.com/docs/13.x/database#handling-deadlocks), [Laravel pessimistic locking](https://laravel.com/docs/13.x/queries#pessimistic-locking), [MySQL locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html).

## 3. Data model

`business_calendar_bookings` gồm:

- `room_id`, `user_id` FK với restrict delete;
- `starts_at_utc`, `ends_at_utc` timestamp;
- `status` `held|confirmed|cancelled`;
- nullable `idempotency_key` unique theo user;
- composite overlap index `(room_id, status, starts_at_utc, ends_at_utc)`.

MySQL CHECK bảo vệ `ends_at_utc > starts_at_utc` và allowlist status. SQLite vẫn được application layer bảo vệ và dùng functional test.

## 4. Use cases

### Create

`StoreRoomBookingRequest` validate shape. Controller map local input thành `BookingInterval`, xác nhận start/end còn nằm trong availability BL-04, rồi gọi `CreateRoomBooking`. Action tự lock authoritative room, kiểm tra idempotency, kiểm tra overlap và persist.

### Cancel

`RoomBookingPolicy` cho phép owner hoặc admin. `CancelRoomBooking` authorize trước, lock booking hiện tại, chuyển sang `cancelled`; repeated cancel không mutate thêm lần nữa.

## 5. Security/performance

- Backend không tin room/status/UTC interval do client tự quyết định.
- Learner chỉ đọc booking của chính mình; admin mới có thể xem danh sách booking của phòng.
- Booking route có auth, lab gate và named rate limiter.
- Policy được đăng ký explicit vì feature model nằm ngoài namespace mặc định.
- Lock chỉ trên một room row, transaction ngắn, không gọi network trong transaction.
- Active overlap query dùng status + room/time index; query plan cần được xác nhận lại khi data scale lớn.
- Idempotency key tránh duplicate booking khi client retry.

## 6. Verification

| Check | Result |
|---|---|
| SQLite functional suite | 89 tests, 435 assertions passed |
| MySQL functional profile | 89 tests, 435 assertions passed trên MySQL 9.5.0 / `laravel_test` |
| Pint | Passed |
| Booking migration/FK/CHECK | Passed trên MySQL |
| Boundary/conflict/cancel/idempotency | Passed |
| Two-connection concurrency harness | Pending |

Chưa claim production-grade concurrent outcome cho tới khi harness có hai connection/process và barrier chứng minh chỉ một booking thắng cùng room/interval. Deadlock retry policy đã nằm trong action nhưng chưa có deadlock injection test.

## 7. Next boundary

Phase tiếp theo nên thêm MySQL two-connection harness, EXPLAIN/EXPLAIN ANALYZE cho overlap query, rồi mới cân nhắc held TTL, recurring booking hoặc outbox/event side effects.

## 8. Learning-first UI template contract

Chuẩn tái sử dụng chính thức nằm tại [`docs/BL_UI_TEMPLATE.md`](../BL_UI_TEMPLATE.md). BL-05 là implementation tham chiếu đầu tiên của chuẩn này.

BL-05 hiện dùng layout chuẩn để tái sử dụng cho các lab sau, theo thứ tự từ khái niệm đến thao tác:

1. **Hero/context:** mã BL, độ khó, problem statement và boundary.
2. **Learning objectives:** 3–4 mục tiêu có thể kiểm chứng sau khi học.
3. **Theory/mental model:** glossary, invariant, state model và interval semantics.
4. **Decision rule:** công thức/predicate hoặc decision table diễn đạt rule trung tâm.
5. **Code flow:** request lifecycle từ input tới persistence, có bước và trách nhiệm rõ.
6. **Implementation:** pseudocode/framework mapping, lock/transaction/index/error boundary.
7. **Trade-offs/limits:** điều đã chứng minh, chưa chứng minh và lý do chọn thiết kế.
8. **Practice boundary:** divider rõ ràng, sau đó mới tới form, result, mutation và feedback.
9. **Evidence footer:** test command, runtime/DB, result và giới hạn hiện tại.

Mỗi section phải có translation key cho tất cả ngôn ngữ được hỗ trợ; code/predicate có thể giữ syntax kỹ thuật, còn explanation phải dịch đầy đủ. Template này tránh để UI thao tác che mất theory, đồng thời giúp người học nối được business rule → code path → database invariant → test evidence.
