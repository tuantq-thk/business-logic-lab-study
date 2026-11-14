# BL-07 Technical Implementation Record

> Cập nhật: 2026-11-14
> Phạm vi: `laravel/`
> Trạng thái: implementation đã thêm; chưa chạy verification, MySQL hoặc concurrency harness.

## 1. Business boundary

BL-07 minh họa đăng ký khóa học có cutoff UTC. Attendee được cancel/reschedule trước cutoff (`now < cutoff`). Tại và sau cutoff, admin được cancel với reason bắt buộc; override không áp dụng cho reschedule. Cancel ghi full refund intent bằng số tiền đã thu. Lab không quyết toán qua provider.

Reschedule giữ nguyên registration và chuyển `course_id` sang course đích cùng giá/currency. Course đích phải mở, chưa bắt đầu, chưa qua cutoff và còn capacity. Re-enrollment vào cùng course sau cancel, partial refund, phí hủy và multi-tenant policy nằm ngoài phạm vi.

## 2. Data model

- `cancellation_courses`: start/cutoff UTC, capacity, price minor unit, currency và `open|closed`.
- `cancellation_course_registrations`: course, attendee, `confirmed|cancelled`, amount/currency đã thu; unique `(course_id, attendee_id)`.
- `cancellation_registration_histories`: action, actor, reason, policy version, course id và snapshot giờ học cũ/mới.
- `cancellation_refund_intents`: amount/currency, `pending|completed|failed`, unique registration và idempotency key.

Capacity được suy ra từ confirmed registrations. Không có mutable counter bị lệch khỏi state. History giữ snapshot start time để course schedule về sau không đổi ý nghĩa của quyết định cũ.

## 3. Write protocol

```text
transaction
  → lock source course (and target course for reschedule), ascending id
  → lock registration and reject stale source/state
  → read UTC clock and verify actor scope, cutoff, target availability and capacity
  → update registration state/course
  → insert append-only history
  → for cancel, insert one full-paid-amount refund intent
  → commit
```

Không gọi network trong transaction. Deadlock retry chạy lại toàn transaction tối đa ba lần. Row locks chỉ bảo vệ writers dùng cùng protocol; MySQL concurrency evidence chưa được thu thập.

## 4. Validation, authorization và failure behavior

- `MutateCourseRegistrationRequest` kiểm tra action, id, target và reason shape.
- `CancelCourseRegistration` và `RescheduleCourseRegistration` re-read scope/state trong transaction; policy class áp cutoff/actor rule.
- Cutoff dùng strict `<`; đúng cutoff bị từ chối với attendee.
- Admin cancel registration của attendee khác sau cutoff yêu cầu reason; reschedule chỉ dành cho owner và luôn tuân cutoff.
- Reschedule sang course đầy, đóng, đã bắt đầu, qua cutoff hoặc khác price/currency bị từ chối.
- Cancel lặp, stale source và trạng thái không còn confirmed không tạo history/refund lần nữa.
- Mutation route yêu cầu auth, lab gate và named rate limiter.

## 5. UI learning contract

Trang BL-07 dùng shared lab shell, `learning-overview`, `layer-map` và `production-learning`. Theory, invariant, data contract, decision table, failure analysis và evidence status xuất hiện trước practice. EN/VI nội dung BL-07 nằm trong `lab_learning.php`, `lab_advanced.php` và `cancellation_rescheduling.php`.

## 6. Verification

| Check | Result |
|---|---|
| Automated tests | Not run |
| Migration on SQLite/MySQL | Not run |
| Two-connection concurrency | Pending |
| Translation parity/rendered UI | Not run |

Không có claim về runtime correctness cho tới khi migration và feature suite được chạy. Các test cần có: cutoff before/at/after, owner/admin, duplicate cancel, destination full, same-price/currency, stale concurrent mutation, refund intent uniqueness/amount, history snapshots và database rollback.

## 7. Next boundary

1. Thêm feature tests và chạy migration/suite trên SQLite.
2. Chạy cùng migration trên MySQL, kiểm tra FK/CHECK và concurrency bằng hai connection.
3. Nếu cần refund provider thật, thêm after-commit worker/outbox, retry policy và reconciliation riêng.
