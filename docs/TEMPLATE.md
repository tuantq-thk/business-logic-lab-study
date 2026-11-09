# [BL-NN] — [Tên logic nghiệp vụ]

> **Domain minh họa:** [domain nhỏ, cụ thể]  
> **Nhóm:** [workflow / tiền / capacity / thời gian / dữ liệu / authorization / reliability]  
> **Trạng thái tài liệu:** draft | reviewed | evidence-backed  
> **Độ khó:** nền tảng | trung cấp | nâng cao  
> **Phụ thuộc:** [lab liên quan nếu có]  
> **Mục tiêu học:** [năng lực có thể chứng minh sau lab]

## 1. Tóm tắt cho người học

- **Bài toán:** ...
- **Câu hỏi nghiệp vụ trung tâm:** ...
- **Phạm vi lab:** ...
- **Ngoài phạm vi:** ...
- **Tiêu chí thành công:** ...

## 2. Bối cảnh, actor và thuật ngữ

| Thuật ngữ | Định nghĩa trong lab | Không nhầm với |
|---|---|---|

Liệt kê actor, động cơ, quyền và dữ liệu họ được phép thấy. Ghi rõ giả định chưa được xác nhận; không biến giả định thành requirement.

## 3. Quy tắc nghiệp vụ và invariant

| ID | Rule | Điều kiện | Kết quả | Nơi bảo vệ | Mức ưu tiên |
|---|---|---|---|---|---|

### Invariant

- `INV-01`: ...

Nêu cách xử lý xung đột giữa rules, boundary inclusivity, timezone/currency/units và trạng thái bị loại trừ.

## 4. Scenarios và decision table

| Scenario | Input/state | Expected outcome | Error code | Persistence side effect |
|---|---|---|---|---|

Bao gồm happy path, invalid, boundary, duplicate, retry, authorization, stale data và concurrency khi có liên quan. Phân biệt rule với implementation detail.

## 5. Mô hình dữ liệu

### ERD/text relationship

```text
[Aggregate] 1 ── * [History/Child]
```

### Bảng và field tối thiểu

| Table | Field | Ý nghĩa/rule dùng | Nullability/default | Constraint/index | Vì sao không thêm field khác |
|---|---|---|---|---|---|

Ghi rõ source of truth, snapshot vs live reference, deletion/retention, units, collation, timezone, unique scope và tenant boundary.

## 6. Migration plan

1. Migration thứ tự nào và vì sao?
2. FK, unique, check, index nào bảo vệ invariant?
3. Constraint nào không thể biểu diễn thuần bằng schema?
4. Rollback có mất dữ liệu không? Expand/backfill/contract có cần không?
5. DB engine/version nào cần cho semantics; cách kiểm chứng migration?

Đưa snippet migration minh họa phần quan trọng; ghi rõ nếu snippet chưa phải migration chạy được đầy đủ.

## 7. Eloquent Model, Factory và Seeder

### Models

| Model | Quan hệ | Cast/value object | Điều không cho phép |
|---|---|---|---|

Nêu mass-assignment policy, enum/status handling, model event usage và vì sao logic đặt ở model/use case/domain service.

### Factories

- States hợp lệ cần có:
- Invariant factory phải giữ:
- Khi nào dùng factory callback/sequence:
- Vì sao tránh random data ở test ranh giới:

### Seeders

- Seed demo nào cho từng scenario:
- Phân biệt seed demo và test factory:
- Idempotence/reset policy:
- Không seed secret/PII thật:

## 8. Tổ chức code và luồng use case

```text
app/...
  Http/Requests/...
  Http/Controllers/...
  Policies/...
  Models/...
  Actions|Services/...
  Events|Jobs/...
  ...
```

| Thành phần | Trách nhiệm | Không làm |
|---|---|---|

### Luồng xử lý

`HTTP input → authentication/authorization → validation → application use case → domain decision → transaction/persistence → event/response`

Chỉ ra ranh giới transaction, lock, retry, side effects và error mapping. Tách PHP thuần khỏi Laravel khi điều đó giúp kiểm thử/quy tắc rõ hơn; không thêm layer cho đủ tên.

## 9. Thuật toán/logic tham chiếu

Viết pseudo-code hoặc bảng quyết định đủ cụ thể để độc lập với framework. Xác định thứ tự checks, tính xác định, clock/rounding và atomicity.

## 10. Hai hoặc nhiều phương án triển khai

| Phương án | Ưu điểm | Nhược điểm/rủi ro | Chọn khi |
|---|---|---|---|

So sánh giải pháp đơn giản nhất với giải pháp cần cho scale/concurrency. Ghi rõ bằng chứng cần có trước khi nâng cấp.

## 11. Transaction, concurrency và failure modes

- Race/TOCTOU:
- Lock/CAS/unique constraint:
- Deadlock/retry:
- Timeout/partial failure:
- Idempotency/compensation:
- Observability/reconciliation:

Nếu không áp dụng, giải thích lý do và điều kiện làm kết luận thay đổi.

## 12. Security, privacy và authorization

Threats, actor scope, IDOR, injection, secrets, PII, retention, rate/size limits và audit. Ghi security boundary ở backend; UI hiding không phải access control.

## 13. Test plan và bằng chứng

| Test level | Claim được chứng minh | Fixture/DB/runtime | Giới hạn |
|---|---|---|---|

Liệt kê unit, feature, DB integration, concurrency, job/provider fake và negative tests. Không nói đã test nếu mới là kế hoạch. Dùng MySQL/Redis thật khi hành vi phụ thuộc engine đó.

## 14. Trade-offs và kiến trúc

- Thiết kế được chọn và lý do:
- Đơn giản hơn có thể dùng:
- Chi phí vận hành/bảo trì:
- Khi nào cần nâng cấp:
- Abstraction cố ý chưa tạo:

## 15. Demo/UI/API

Nêu trạng thái loading/empty/success/conflict/error, API semantics/status code nếu có, cách reset sample data, demo script 3–7 phút và test mở ra để chứng minh.

## 16. Triển khai theo chặng

1. Logic thuần + decision tests.
2. Schema/migration + persistence tests.
3. Use case + permissions.
4. Concurrency/reliability nếu cần.
5. Demo/UI/observability.

Mỗi chặng có acceptance criteria và out-of-scope.

## 17. Definition of Done

- [ ] Problem, scope, actors, glossary rõ.
- [ ] Rules có IDs, invariant và boundary.
- [ ] Schema/migration constraints có lý do.
- [ ] Model/factory/seeder phản ánh scenario hợp lệ.
- [ ] Backend validation/authorization đúng ranh giới.
- [ ] Tests chứng minh claims đúng runtime.
- [ ] Trade-offs, failure modes và giới hạn được nêu.
- [ ] Demo tái lập được; chưa có evidence thì không ghi “đã xác minh”.

## 18. Câu hỏi review phản biện

1. Quy tắc nào DB bảo vệ được tốt hơn app code?
2. Có thể tạo dữ liệu sai bằng một entry point khác không?
3. Điều gì xảy ra nếu hai request cùng đúng riêng lẻ nhưng chạy đồng thời?
4. Retry sau timeout có lặp side effect không?
5. Model/field nào chưa có business rule chứng minh?
6. Phương án đơn giản hơn có đủ không?
7. Tuyên bố nào trong tài liệu chưa có nguồn hoặc test evidence?
