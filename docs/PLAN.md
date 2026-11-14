# Roadmap tổng thể — Business Logic Lab

## 1. Định hướng đã chốt

Repo là thư viện bài học đa domain, tập trung hiểu nghiệp vụ qua ví dụ. Không xây một ứng dụng garage/e-commerce/booking hoàn chỉnh. Mỗi lab chọn domain nhỏ để làm rõ một logic. Dùng Laravel/PHP như phương tiện triển khai, không lấy số lượng layer hoặc pattern làm thước đo chất lượng.

### Mục tiêu năng lực

Sau 6–8 lab được triển khai kỹ, người học có thể: hỏi làm rõ rule; mô hình hóa invariant; chọn schema constraints; phân biệt validation/auth/business logic; thiết kế transaction và retry; nhận ra race condition; chọn mức abstraction vừa đủ; chứng minh behavior bằng test đúng engine.

## 2. Review plan nguồn và điều chỉnh

Các điểm giữ lại: menu lab, toggle có backend gate, mỗi lab có rule/test/trade-off riêng, field tối thiểu, MySQL cho locking/concurrency, không đánh dấu complete thiếu evidence.

Các điểm cần chỉnh/bổ sung:

1. **Feature catalog/toggle là bài mở đầu của learning shell, không phải nền tảng khổng lồ.** Bắt đầu bằng registry tĩnh, backend gate và test direct URL; chỉ thêm DB override/admin UI khi cần bật/tắt runtime.
2. **Tách “logic thuần” và “bảo đảm persistence”.** Ví dụ interval overlap unit test khác với chống hai transaction cùng insert.
3. **Mỗi lab phải ghi assumptions/non-goals.** Không giả định chính sách tiền, timezone, tenant, cancellation hay idempotency giống mọi sản phẩm.
4. **Migration phải nêu rollout/data migration.** Schema mới và đổi schema có dữ liệu là hai tình huống khác nhau; destructive rollback không được coi là undo nghiệp vụ.
5. **Factories/seeders có invariant.** Factory hợp lệ nên sinh aggregate nhất quán; invalid fixture phải được tạo cố ý. Seeder demo không thay fixture test.
6. **Phân loại test evidence.** SQLite không chứng minh MySQL locking, isolation, collation, index plans hay constraint semantics.
7. **Side effect phân tán cần failure model.** Queue after-commit, outbox, provider dedupe và at-least-once được phân biệt; tránh hứa exactly-once.
8. **Bổ sung privacy/security/operations.** PII trong audit/export/job, file retention, tenant cache key, retry storms, deadlock handling và observability phải nằm trong review.
9. **Không ép DDD/Clean Architecture.** Giữ Laravel convention; tách domain code khi giúp diễn đạt/test rule, không tạo Repository/Service cho mọi CRUD.
10. **Phân biệt lab với feature độc lập về UI.** Các lab là tài liệu/code modules; menu chỉ là shell tùy implementation, không yêu cầu mọi ví dụ có trang CRUD đầy đủ.

## 3. Danh mục và nhóm năng lực

| # | Lab | Tần suất/chuyển giao | Phụ thuộc/chú ý |
|---|---|---|---|
| 01 | State transition & invariant | Rất cao, workflow phổ biến | Nền cho cancellation/audit |
| 02 | Pricing & discounts | Cao, commerce/billing | Currency/rounding phải chốt |
| 03 | Inventory/capacity | Cao, resource allocation | MySQL concurrency |
| 04 | Business calendar/slots | Trung bình-cao | Timezone/override |
| 05 | Booking overlap | Cao khi có resource/time | DB race, không chỉ interval formula |
| 06 | Contextual validation | Rất cao | TOCTOU/authorization |
| 07 | Cancellation/reschedule | Cao | Compensation, history |
| 08 | Idempotency | Cao với API/retry | Unique scope/retention |
| 09 | Authorization scope | Rất cao | BOLA/tenant boundaries |
| 10 | CSV import | Cao ở admin/data ops | Size, partial success |
| 11 | Search/filter/pagination | Rất cao | Query plan/index |
| 12 | Audit trail | Cao ở workflow nhạy cảm | PII/retention |
| 13 | Background notification | Cao | Queue delivery semantics |
| 14 | Recurring dates | Trung bình | Calendar/DST complexity |
| 15 | Webhook ingestion | Cao khi tích hợp | Signature/replay/dedup |
| 16 | Reservation hold TTL | Trung bình-cao | Expiry race/capacity |
| 17 | Optimistic locking | Cao với collaborative edits | Conflict UX |
| 18 | Rate limit/quota | Cao APIs/platform | Abuse vs billing semantics |
| 19 | Transactional outbox | Nâng cao, integration reliability | At-least-once + consumer dedup |
| 20 | Authorized background export | Cao ở admin/reporting | Scope, files, retention |

“Tần suất” là đánh giá định tính về khả năng gặp trong ứng dụng nghiệp vụ phổ biến, không phải market survey định lượng. Ưu tiên có thể đổi theo vị trí/công việc mục tiêu.

## 4. Lộ trình triển khai

### Wave 0 — Repo learning shell

- BL-00: Feature Catalog & Toggle bản nhỏ, là feature đầu tiên của demo shell.
- README, index, metadata key và UI Guide cho lab.
- Menu từ registry tĩnh; status `planned/in_progress/evidence-backed`.
- Runtime DB override/admin toggle khi demo cần bật/tắt ngay; backend gate và authorization tách riêng.
- Quy ước chạy test, seed/reset và ADR.

### Wave 1 — Rule, state, money

- 01 state transition.
- 02 pricing/discount.
- 06 contextual validation.
- Output: decision tables, pure rules, use case boundaries.

### Wave 2 — Persistence và concurrency

- 03 inventory/capacity.
- 04 calendar/slot.
- 05 overlap/concurrency.
- 08 idempotency.
- Output: MySQL integration harness, locking ADR, retry semantics.

### Wave 3 — Security, audit, operations

- 09 authorization scope.
- 07 cancellation/reschedule.
- 12 audit.
- 13 queues.
- Output: threat cases, side effect policy, queue retry evidence.

### Wave 4 — Data and integration

- 10 import; 11 query; 14 recurrence; 15 webhook; 16 TTL; 17 optimistic lock; 18 quota; 19 outbox; 20 export.
- Chọn thứ tự theo giá trị học/career; không cần làm tất cả để repo có giá trị.

## 5. Ước lượng scope của một lab

Mỗi lab chia tối thiểu thành:

1. **Analysis:** actors, assumptions, terms, rules, invariants, scenarios.
2. **Data:** ERD, field rationale, migration constraints/indexes, rollout.
3. **Implementation plan:** Laravel folder boundaries, models, factories/seeders, use case flow.
4. **Verification:** unit + feature + engine-specific tests; evidence template.
5. **Demo:** happy, failure/boundary, trade-off, test proof.

Không định lượng story point khi chưa chọn stack/runtime. Một lab P0 thường nên scope một use case chính, 2–4 tables tối đa nếu logic cho phép, một đường demo và test trọng yếu; số bảng không phải acceptance criterion.

## 6. Quyết định kiến trúc repo tài liệu

```text
README.md                         # định hướng và navigation
 docs/PLAN.md                     # danh mục, thứ tự, review decisions
 docs/TEMPLATE.md                 # hợp đồng nội dung mỗi lab
 docs/labs/NN-slug.md             # một file cho mỗi lab
 docs/decisions/ADR-NNN-*.md      # chỉ khi implementation chốt lựa chọn
```

Source Laravel hiện nằm cùng repo trong `laravel/`. Technical implementation records dùng để nối tài liệu phân tích với folder, route, migration, test và verification; mỗi record phải ghi rõ commit/runtime/evidence và giới hạn. Không copy toàn bộ code vào docs vì source là nơi canonical.

## 7. Tiêu chí ưu tiên lab kế tiếp

Chấm định tính 1–5: tần suất gặp, độ chuyển giao, giá trị học rule/data, cần thiết cho portfolio, phạm vi có thể chứng minh, phụ thuộc thấp. Không dùng điểm để giả vờ chính xác; thảo luận lý do cho điểm. Ưu tiên lab có invariant rõ và testable outcome.

## 8. Câu hỏi cần trả lời trước khi triển khai source

- PHP/Laravel/MySQL versions và deployment baseline nào sẽ pin?
- PHPUnit hay Pest, Pint/PHPStan level nào thực tế?
- Có cần React/UI demo từ đầu hay Markdown + Laravel routes đủ?
- Menu là local-only registry hay muốn admin runtime toggle thật?
- Portfolio có dùng GitHub public; sample data/English README cần ra sao?

Các câu hỏi không chặn việc dùng bộ tài liệu này; chúng cần được chốt trước khi tạo code và tuyên bố compatibility.

## 9. Exit criteria của roadmap

- Mỗi lab trong catalog có tài liệu theo template chung; catalog có thể mở rộng.
- BL-00, BL-01 và BL-02 đã được triển khai end-to-end; BL-03 và BL-04 đã có implementation local với SQLite + MySQL functional evidence. Concurrency harness cho BL-03/booking overlap của BL-05 vẫn là giới hạn được ghi rõ.
- Mỗi tài liệu ghi rõ trạng thái planning vs implemented vs verified.
- Chỉ claim đã kiểm chứng khi có command, environment, result và limits.
- Có thể thêm lab mới mà không đổi cấu trúc tất cả bài cũ.
