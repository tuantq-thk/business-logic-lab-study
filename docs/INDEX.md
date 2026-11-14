# Mục lục lab

## Learning shell

- [BL-00 — Feature Catalog và bật/tắt lab](labs/00-feature-catalog-toggle.md) — `implemented locally`; bài đầu tiên để quản lý khả năng demo và bảo vệ backend.

## Business logic labs

- [BL-01 — State transition và invariant](labs/01-state-transition.md) — `evidence-backed locally`; phiếu hỗ trợ.
- [BL-02 — Giá, giảm giá và thứ tự áp dụng](labs/02-pricing-discounts.md) — `evidence-backed locally`; giỏ hàng sách.
- [BL-03 — Tồn kho, giữ hàng và capacity](labs/03-inventory-capacity.md) — `implemented locally`; SQLite evidence pass, MySQL concurrency pending; kho cửa hàng.
- [BL-04 — Lịch làm việc, ngày nghỉ và sinh slot](labs/04-business-calendar-slots.md) — phòng họp.
- [BL-05 — Overlap và đặt lịch chống trùng](labs/05-booking-overlap.md) — lịch phòng theo resource.
- [BL-06 — Validation theo ngữ cảnh nghiệp vụ](labs/06-contextual-validation.md) — đăng ký hội thảo.
- [BL-07 — Hủy, đổi lịch và chính sách hoàn tác](labs/07-cancellation-rescheduling.md) — đăng ký khóa học.
- [BL-08 — Idempotency và retry an toàn](labs/08-idempotency.md) — tạo đơn qua API.
- [BL-09 — Phân quyền theo chủ sở hữu và phạm vi](labs/09-authorization-scope.md) — tài liệu tổ chức.
- [BL-10 — Import CSV có preview và lỗi từng dòng](labs/10-csv-import.md) — danh mục sản phẩm.
- [BL-11 — Tìm kiếm, lọc, sắp xếp và phân trang](labs/11-search-filter-pagination.md) — hàng đợi ticket.
- [BL-12 — Audit trail và lịch sử nghiệp vụ](labs/12-audit-trail.md) — hạn mức phê duyệt.
- [BL-13 — Thông báo nền, retry và lỗi provider](labs/13-background-notifications.md) — email xác nhận.
- [BL-14 — Ngày giờ lặp lại và tác vụ định kỳ](labs/14-recurring-schedules.md) — nhắc hóa đơn.
- [BL-15 — Webhook nhận sự kiện an toàn](labs/15-webhook-ingestion.md) — provider giả lập.
- [BL-16 — Giữ chỗ tạm thời có TTL](labs/16-reservation-hold-ttl.md) — ghế hội thảo.
- [BL-17 — Chỉnh sửa đồng thời và optimistic locking](labs/17-optimistic-locking.md) — hồ sơ khách hàng.
- [BL-18 — Rate limit, quota và giới hạn sử dụng](labs/18-rate-limits-quotas.md) — API báo cáo.
- [BL-19 — Transactional outbox và phát sự kiện đáng tin cậy](labs/19-transactional-outbox.md) — đồng bộ vận chuyển.
- [BL-20 — Export/báo cáo nền có phân quyền](labs/20-authorized-background-export.md) — xuất dữ liệu tổ chức.

## Hướng dẫn

- [Template phân tích chuyên sâu](TEMPLATE.md)
- [UI Guide](UI_GUIDE.md)
- [Roadmap](PLAN.md)

## Cách đọc trạng thái

- `planned`: mới có phân tích/blueprint, chưa có implementation evidence.
- `implemented locally`: đã có code và test local, nhưng evidence hoặc giới hạn vẫn còn hẹp.
- `evidence-backed locally`: claim chính đã được kiểm tra trong runtime được ghi ở technical record; không đồng nghĩa production-ready.

## Technical implementation records

- [BL-00 — Catalog visibility toggle implementation](technical/BL-00-implementation.md)
- [BL-01 — State transition implementation](technical/BL-01-implementation.md)
- [BL-02 — Pricing and discounts implementation](technical/BL-02-implementation.md)
- [BL-03 — Inventory and capacity implementation](technical/BL-03-implementation.md)
