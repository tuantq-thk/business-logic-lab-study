# BL-07 — Hủy, đổi lịch và chính sách hoàn tác

> **Domain minh họa:** Đăng ký khóa học có hạn chót hủy  
> **Nhóm:** cancellation / rescheduling  
> **Trạng thái:** implemented locally; chưa chạy verification cho BL-07
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân biệt cancel và reschedule, giữ capacity/history nguyên tử và ghi refund intent idempotent.
## 1. Tóm tắt và phạm vi

**Bài toán:** Khi thao tác đảo ngược xảy ra, phải giải phóng tài nguyên, tính phí và giữ dấu vết thế nào?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Attendee; admin/organizer được phép override hủy; refund intent nội bộ chưa có worker/provider.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-07-01 | Attendee hủy/đổi lịch khi `now < cancel_cutoff_at_utc`; tại hoặc sau cutoff chỉ admin được hủy với reason. | Cutoff lưu UTC; reschedule không có override. |
| BR-07-02 | Chỉ registration `confirmed` được mutate; attendee hủy/đổi lịch của mình, admin được hủy registration của attendee khác. | Scope và state được kiểm tra sau khi lock registration. |
| BR-07-03 | Confirmed registration chiếm một seat; cancel/reschedule và history cùng transaction. | Lock course row trước capacity count; registration state quyết định release. |
| BR-07-04 | Cancellation tạo full refund intent bằng số tiền đã thu; không gọi provider. | Một intent/registration, unique idempotency key, currency snapshot. |
| BR-07-05 | Reschedule chuyển cùng registration sang course open, chưa bắt đầu, chưa qua cutoff, còn chỗ và cùng giá/currency. | Khóa source/target theo id tăng dần; không sửa enrollment âm thầm. |

### Invariants

- **INV-07-01:** Một đăng ký không được release capacity hai lần.
- **INV-07-02:** History lưu old/new schedule, actor, reason, policy version.
- **INV-07-03:** Refund intent không vượt amount đã thu.

`now == cutoff` bị từ chối với attendee. Admin override chỉ áp dụng cancel, cần reason không rỗng và reason được lưu trong history. Re-enrollment vào cùng course sau cancel không được hỗ trợ trong lab này.

## 3. Scenarios cần mô hình hóa

- **SC-07-00:** Before/at/after cutoff boundary.
- **SC-07-01:** Cancel twice; only one capacity release.
- **SC-07-02:** Wrong actor and override reason.
- **SC-07-03:** Concurrent cancel/reschedule.
- **SC-07-04:** Refund intent commit nguyên tử; provider failure/retry nằm ngoài implementation hiện tại.

Decision table và error mapping được thể hiện trong learning UI và `CancellationReschedulingController`; MySQL race evidence vẫn pending.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `cancellation_courses` | start/cutoff UTC, capacity, price minor unit, currency, status | Nguồn capacity và cutoff chính thức. |
| `cancellation_course_registrations` | `course_id, attendee_id, status, paid_amount_minor, currency` | Current registration; unique course/attendee. |
| `cancellation_registration_histories` | actor, action, reason, old/new course IDs and start-time snapshots, policy version | Append-only explanation for state changes. |
| `cancellation_refund_intents` | registration, amount/currency, status, idempotency key | Một full-refund intent nội bộ cho registration bị hủy. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Một đăng ký không được release capacity hai lần. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Unique registration per course/attendee; index `(course_id,status)` cho capacity; FK history; refund unique theo registration/idempotency key. Không hard-delete registration; money dùng minor unit và currency.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

Use cases `CancelCourseRegistration` và `RescheduleCourseRegistration`; `CourseRegistrationMutationPolicy` giữ cutoff/actor rule; `RegistrationHistory` immutable. Thời điểm được đọc sau khi lock để tránh dùng cutoff stale.

### Factory

`CourseFactory` và `CourseRegistrationFactory` tạo fixture nhất quán. History và refund intent chỉ được tạo qua actions để không tạo audit/compensation rời khỏi mutation nguyên tử.

### Seeder

Seeder tạo hai cohort cùng giá/currency và một registration demo cho `test@example.com`; không seed payment secret hoặc PII mới.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/Features/CancellationRescheduling/
├── Actions/CancelCourseRegistration.php
├── Actions/RescheduleCourseRegistration.php
├── Domain/CourseRegistrationMutationPolicy.php
├── Http/Requests/MutateCourseRegistrationRequest.php
└── Models/...
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

`HTTP input → authenticate → scoped registration load → validate shape → lock course(s) and registration → evaluate current policy/time → persist state + history + refund intent → commit → map result`

### Thuật toán tham chiếu

Cancel locks the course then registration. Reschedule locks source/target courses by ascending id, then registration. Both read the clock after locks. Cancellation writes status, history and (if paid amount is positive) one full-amount refund intent in one transaction. No provider call or worker runs in this lab.

Input reason được trim một lần. Thời gian lấy sau khi khóa để không dùng cutoff đã stale; mọi state có thể đổi giữa validation và write được re-read trong boundary persistence.

## 9. Phương án triển khai và trade-off

Soft cancel/status retains history and supports restoration but filters must exclude canceled. Hard delete simple but erases evidence and can break references. Compensation is safer than pretending distributed external action is atomic.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** seat released twice; stale registration mutation; destination capacity race. Refund-provider success followed by local rollback is a future failure mode because this lab does not call a provider.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Ownership tampering, admin override without reason, repeated cancel, target course becoming full and a database error after state update.

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
- [ ] Refund intent is atomic with local state. Provider failure/retry is future work and is not covered by this implementation.

Đây vẫn là **kế hoạch test**, không phải khẳng định tests đã chạy. Implementation hiện chưa có automated evidence; xem [technical record](../technical/BL-07-implementation.md) để biết giới hạn và bước verification kế tiếp. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** seat released twice; stale registration mutation; target course fills; database error after state update. Provider refund succeeding before local rollback is future work because no provider is called here.

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

## 15. Chia chặng triển khai và trạng thái

1. Chốt glossary, assumptions, rule IDs, decision table — **hoàn tất**.
2. Thiết kế migration, model, factory và demo seeder — **code đã thêm; chưa chạy**.
3. Thêm use case, authorization, transaction boundary và UI EN/VI — **code đã thêm; chưa chạy**.
4. Chạy migration và feature tests trên SQLite — **pending**.
5. Chạy migration MySQL và two-connection concurrency harness — **pending**.
6. Chỉ thêm provider worker/outbox khi refund provider thật thuộc scope — **ngoài scope hiện tại**.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
