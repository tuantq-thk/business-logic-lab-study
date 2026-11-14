# BL-04 — Lịch làm việc, ngày nghỉ và sinh slot

> **Domain minh họa:** Đặt phòng họp  
> **Nhóm:** business / calendar / slots  
> **Trạng thái:** implemented locally; SQLite + MySQL functional evidence đã pass; booking overlap thuộc BL-05
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Khung giờ nào có thể chọn khi lịch tuần, ngoại lệ ngày và thời lượng phòng cùng áp dụng?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor trong MVP:** learner xem availability; quản trị viên cung cấp dữ liệu lịch qua seeder; service tính availability. Booking/đặt trùng chưa thuộc BL-04.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-04-01 | Lịch tuần được diễn giải trong timezone của địa điểm/phòng. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-04-02 | Ngoại lệ ngày cụ thể có thể đóng cả ngày hoặc thay thế lịch tuần; semantics override phải rõ. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-04-03 | Khoảng phục vụ là half-open [start,end); slot phải nằm trọn trong cửa sổ mở. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-04-04 | Duration và slot step là hai khái niệm khác nhau; buffer nếu có phải quy định thuộc trước/sau. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-04-05 | Không trả slot bắt đầu trong quá khứ theo clock được inject. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-04-01:** Không có slot ngoài business interval hoặc đi qua break/closure.
- **INV-04-02:** Một ngày override có precedence xác định so với weekly rule.
- **INV-04-03:** Mọi instant được so sánh nhất quán; lưu timezone/IANA cho địa điểm, persist instants UTC khi lưu sự kiện.

## 3. Scenarios cần mô hình hóa

- **SC-04-00:** Weekly open/closed weekday; override đóng/mở/thay ca.
- **SC-04-01:** Candidate vừa chạm giờ đóng được chấp nhận nếu end==close; vượt một phút bị loại.
- **SC-04-02:** Break giữa hai shifts; duration không chia hết step.
- **SC-04-03:** Past slots theo frozen clock.
- **SC-04-04:** DST gap/overlap nếu product timezone hỗ trợ; nếu không, scope ghi rõ.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `rooms` | `id, name, timezone` | timezone IANA. |
| `weekly_opening_hours` | `id, room_id, weekday, opens_at_local, closes_at_local` | unique (room_id, weekday, opens_at_local); hỗ trợ nhiều ca bằng nhiều row. |
| `calendar_overrides` | `id, room_id, local_date, kind, opens_at_local nullable, closes_at_local nullable, reason nullable` | unique (room_id, local_date) nếu chỉ 1 override/ngày; nếu nhiều interval cần bảng child. |
| `room_bookings` | `id, room_id, starts_at_utc, ends_at_utc, status` | lịch thực tế được BL-05 xử lý. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Không có slot ngoài business interval hoặc đi qua break/closure. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Dùng DATE cho local_date; TIME cho giờ địa phương; timezone IANA trên room. Không lưu lịch tuần bằng chuỗi JSON nếu cần query/constraint. Unique room+weekday+start và room+date override. Validate opens < closes; cross-midnight phải được hỗ trợ rõ hoặc loại khỏi scope. Migration không thể tự hiểu DST policy.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`OpeningWindow`/`CalendarOverride` value objects; `AvailabilityService` pure calculation nhận LocalDate, timezone, duration, step, now. Eloquent repositories chỉ tải rules/overrides. Không phụ thuộc `now()` global trong domain function.

### Factory

RoomFactory dùng timezone `Asia/Ho_Chi_Minh`; OpeningHoursFactory states weekday/closed day; OverrideFactory closed/special-open. Seed deterministically; factory không tạo interval sai.

### Seeder

Một phòng mở hai ca có giờ nghỉ; một ngày nghỉ; một ngày mở đặc biệt; dùng sample date cố định trong docs. Tests freeze clock.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/BusinessCalendarSlots/...
├── Http/Controllers/BusinessCalendarSlots/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/BusinessCalendarSlots/...
├── Domain/BusinessCalendarSlots/ # pure rules/value objects nếu giúp rõ
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

Chọn effective windows: nếu có override thì áp semantics đã chốt, nếu không dùng weekly windows. Convert local boundaries theo timezone/date; sinh candidate start theo step; candidate end=start+duration; lọc full containment và now. DST gap/ambiguous local time: phải có chính sách explicit nếu timezone có DST.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Sinh slot on-demand đơn giản, tránh dữ liệu stale; precomputed slots thuận query nhưng cần invalidation khi lịch đổi. Một bảng window lặp theo ngày dễ query; lưu JSON gọn nhưng khó index/constraint. Quy tắc ngày nghỉ cần precedence minh bạch.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Timezone server bị dùng thay timezone địa điểm; overnight interval hiểu sai ngày; cập nhật lịch không làm mới cache; UTC/local conversion hai lần; booking lưu local time không offset.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Timezone server bị dùng thay timezone địa điểm; overnight interval hiểu sai ngày; cập nhật lịch không làm mới cache; UTC/local conversion hai lần; booking lưu local time không offset.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [x] Weekly open/closed weekday; override đóng/mở/thay ca.
- [x] Candidate vừa chạm giờ đóng được chấp nhận nếu end==close; vượt một phút bị loại.
- [x] Break giữa hai shifts; duration không chia hết step.
- [x] Past slots theo injected clock.
- [x] DST gap bị từ chối rõ ràng; DST overlap và booking overlap để scope sau.

Evidence hiện tại: 82 tests, 417 assertions passed trên SQLite và MySQL 9.5.0 (`laravel_test`); migration, FK, unique key, CHECK constraint và UI route đã chạy. Chưa claim booking concurrency/overlap vì đó là BL-05; DST ambiguous overlap chưa được chọn policy.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Timezone server bị dùng thay timezone địa điểm; overnight interval hiểu sai ngày; cập nhật lịch không làm mới cache; UTC/local conversion hai lần; booking lưu local time không offset.

**Mở rộng sau MVP:** Holiday calendars, recurring exceptions, multiple rooms, capacity, cache versioning. Availability không đảm bảo booking còn trống nếu concurrent request; cần BL-05.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** So lịch tuần với ngày override, xem slot trước/sau break. Hỏi: generator có nên kiểm tra booking đã tồn tại không?

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

- `app/Features/BusinessCalendarSlots/Services/SlotGenerator.php`: pure slot generation theo local date/timezone, half-open containment và injected clock.
- `app/Features/BusinessCalendarSlots/Services/NextAvailableDateFinder.php`: chọn ngày khả dụng gần nhất theo timezone phòng, weekly hours và override.
- `app/Features/BusinessCalendarSlots/Actions/GenerateRoomSlots.php`: chọn effective weekly windows hoặc date override.
- `app/Features/BusinessCalendarSlots/Models`: room, weekly opening hours và calendar override.
- `database/migrations/2026_11_14_100000..100003`: tables, indexes, FK và MySQL checks.
- `resources/views/labs/business-calendar-slots/index.blade.php`: demo chọn phòng/ngày/duration/step và hiển thị local slots.
- `tests/Unit/BusinessCalendarSlots/SlotGeneratorTest.php`: boundary, break, past slot và DST gap.
- `tests/Feature/BusinessCalendarSlots/BusinessCalendarSlotsTest.php`: route, weekly schedule và closed override.

## 17. Verification limits

- `composer test`: 82 tests, 417 assertions trên SQLite in-memory.
- `composer test:mysql`: 82 tests, 417 assertions trên MySQL 9.5.0 với database `laravel_test`.
- Chưa có booking table hoặc overlap lock; BL-05 sẽ chịu trách nhiệm chứng minh phần đó.
- Chưa chọn semantics cho DST ambiguous local time khi đồng hồ lùi; hiện chỉ reject local time không tồn tại trong DST gap.
