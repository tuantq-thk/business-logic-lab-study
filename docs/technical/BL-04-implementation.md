# BL-04 Technical Implementation Record

> Cập nhật: 2026-11-14
> Phạm vi: `laravel/`
> Trạng thái: implemented locally; SQLite và isolated MySQL functional evidence đã pass.

## 1. Boundary và mục tiêu

BL-04 chứng minh cách diễn giải lịch tuần và ngoại lệ ngày theo timezone của phòng để sinh các slot có thể chọn. BL-04 không kiểm tra booking đã tồn tại, không lock để giữ slot và không giải quyết overlap; các invariant đó thuộc BL-05.

Actor trong MVP là learner xem availability, seeder cung cấp lịch mẫu và service tính kết quả. Không có admin CRUD lịch trong scope hiện tại.

## 2. Rule và quyết định

- `Room.timezone` là IANA timezone; `local_date` và `TIME` của lịch được hiểu trong timezone phòng.
- Không có override: dùng tất cả weekly windows của weekday đó.
- Có override `closed`: không có slot.
- Có override `open`: override thay toàn bộ weekly schedule trong ngày và cung cấp một window.
- Window dùng half-open semantics `[open, close)`; slot hợp lệ khi `start + duration <= close`.
- `duration` và `step` độc lập; step sinh candidate start, duration quyết định end.
- Slot đã bắt đầu trước injected clock bị loại.
- Local time không tồn tại trong DST gap bị reject; ambiguous fall-back time chưa được support vì chưa chốt product policy.

## 3. Data model

### `business_calendar_rooms`

| Field | Contract |
|---|---|
| `code` | unique stable room identifier |
| `name_key` | translation key, không lưu presentation text |
| `timezone` | IANA timezone, non-empty |
| `active` | chỉ room active được chọn trên UI |

### `business_calendar_weekly_hours`

`weekday` dùng `0=Sunday..6=Saturday` theo Carbon/PHP; `opens_at_local < closes_at_local`; nhiều ca trong ngày được biểu diễn bằng nhiều row. Lookup index là `(room_id, weekday, opens_at_local)`.

### `business_calendar_overrides`

Unique `(room_id, local_date)`. `closed` bắt buộc không có giờ; `open` bắt buộc có một window hợp lệ. Override có precedence cao hơn weekly hours.

Không tạo `room_bookings` trong BL-04. Availability không phải reservation và không giữ capacity.

## 4. Code flow

```text
GET input
  → AvailabilityRequest validates room/date/duration/step
  → controller loads active rooms
  → GenerateRoomSlots loads one override or weekly windows
  → SlotGenerator converts local windows in room timezone
  → candidate start/end filtered by containment and injected now
  → Blade renders local slots and rule explanation
```

`SlotGenerator` không query database và không gọi global `now()`, nên boundary tests deterministic. `NextAvailableDateFinder` tách riêng việc chọn ngày mặc định; Eloquent models chỉ giữ persistence relation/casts và controller chỉ orchestration.

## 5. Performance và database

- Slot generation là on-demand; không precompute dữ liệu dễ stale khi lịch đổi.
- Một request chỉ load active room list và các window của room/date đã chọn.
- Override dùng unique/index theo room/date.
- Weekly query dùng room/weekday/open time index.
- Không có transaction vì BL-04 chỉ đọc rule; booking transaction/locking để BL-05.
- MySQL CHECK constraints bảo vệ window/kind/weekday; action vẫn reject invalid local time ở application layer.

## 6. Security/UI

- Route yêu cầu auth và `lab.available`; room chỉ lấy `active=true` và request `room_id` có allowlist DB.
- UI không nhận timezone từ client; timezone lấy từ room authoritative state.
- GET form giữ date/duration/step, có labels, loading state và empty state.
- UI hiển thị rõ “local time” và cảnh báo booking overlap chưa thuộc lab này.
- Translation có `en/vi` parity cho catalog và BL-04 page.

## 7. Test evidence

| Check | Result |
|---|---|
| SQLite functional suite | 82 tests, 417 assertions passed |
| MySQL functional profile | 82 tests, 417 assertions passed trên MySQL 9.5.0 / `laravel_test` |
| MySQL migrations/FK/CHECK | Passed; migrations `100000..100003` applied |
| Pure slot boundaries | Passed: containment, break, step/duration, past clock |
| DST gap | Passed: invalid local time rejected |
| Browser visual/accessibility QA | Chưa chạy |
| Booking overlap/concurrency | Ngoài scope, chuyển BL-05 |

## 8. References

- Laravel 13 Eloquent date casting/timezone guidance: https://laravel.com/docs/13.x/eloquent-mutators#date-casting-serialization-and-timezones
- Laravel 13 request validation and timezone rule: https://laravel.com/docs/13.x/validation#available-validation-rules
- Laravel 13 request date retrieval: https://laravel.com/docs/13.x/requests#retrieving-input
- Laravel 13 migrations: https://laravel.com/docs/13.x/migrations

## 9. Next boundary

BL-05 sẽ thêm booking/resource overlap, ownership/authorization và MySQL two-connection concurrency proof. Không mở rộng BL-04 thành booking engine để giữ mỗi lab có một invariant trung tâm.
