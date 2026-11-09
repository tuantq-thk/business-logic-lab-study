# BL-12 — Audit trail và lịch sử nghiệp vụ

> **Domain minh họa:** Thay đổi hạn mức phê duyệt  
> **Nhóm:** audit / trail  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Cần lưu gì để sau này biết ai đã thay đổi dữ liệu quan trọng, từ giá trị nào sang giá trị nào?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Người sửa; approver; auditor đọc lịch sử.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-12-01 | Audit event ghi actor (hoặc system), subject, action, timestamp, correlation/request ref. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-12-02 | Chỉ ghi field nghiệp vụ cần audit; redact secret/PII theo policy. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-12-03 | Audit record append-only qua app; correction tạo event mới. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-12-04 | Nếu thay đổi và audit là một business transaction, ghi cùng transaction. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-12-05 | Ai được đọc audit trail có policy riêng. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-12-01:** Mỗi privileged change có evidence nếu policy yêu cầu.
- **INV-12-02:** Audit không bị chỉnh sửa/xóa bởi ordinary role.
- **INV-12-03:** Payload không lưu credential/token hoặc dữ liệu vượt mục đích.

## 3. Scenarios cần mô hình hóa

- **SC-12-00:** Authorized change has actor/reason/diff.
- **SC-12-01:** Rejected/no-op changes don’t produce misleading event.
- **SC-12-02:** DB failure rolls back both.
- **SC-12-03:** Sensitive field redacted; unauthorized audit read denied.
- **SC-12-04:** Application path cannot update/delete entries.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `approval_limits` | `id, organization_id, amount_minor, currency, version` | business state. |
| `audit_entries` | `id, actor_id nullable, subject_type, subject_id, action, before_json, after_json, reason, correlation_id, created_at` | append-only; index subject/time and actor/time. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Mỗi privileged change có evidence nếu policy yêu cầu. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Polymorphic subject thuận tiện nhưng FK khó enforce; nếu audit compliance cần chọn per-aggregate history hoặc typed subject table. JSON before/after size limit/redaction/schema evolution. Index `(subject_type,subject_id,created_at,id)`; retention/partition policy nếu volume lớn.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`AuditEntry` insert-only model; action use case computes safe diff; avoid generic observer capturing every field. Read access through audited query/service; no mass assign to audit model.

### Factory

AuditEntryFactory with realistic action and redacted payload. ApprovalLimitFactory with version. Ensure before/after coherent.

### Seeder

One change chain by admin and one system event. Never seed secrets/actual PII.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/AuditTrail/...
├── Http/Controllers/AuditTrail/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/AuditTrail/...
├── Domain/AuditTrail/ # pure rules/value objects nếu giúp rõ
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

Authorize → lock/version-check record → calculate diff → save state + audit entry same DB transaction → commit. For external event, outbox delivers sanitized audit event.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Observer is convenient for broad technical capture but hides business meaning and transaction origin. Explicit domain audit is clearer for critical actions. Full snapshots simplify reconstruction but risk PII/storage; diffs are compact but require schema-aware interpretation. Audit log is not automatically tamper-proof compliance storage.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Audit write silently swallowed; actor context absent in queue; generic observer logs password hash/token; row deleted while audit remains unlinked; claim “immutable” though DB admins can change it.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Audit write silently swallowed; actor context absent in queue; generic observer logs password hash/token; row deleted while audit remains unlinked; claim “immutable” though DB admins can change it.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Authorized change has actor/reason/diff.
- [ ] Rejected/no-op changes don’t produce misleading event.
- [ ] DB failure rolls back both.
- [ ] Sensitive field redacted; unauthorized audit read denied.
- [ ] Application path cannot update/delete entries.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Audit write silently swallowed; actor context absent in queue; generic observer logs password hash/token; row deleted while audit remains unlinked; claim “immutable” though DB admins can change it.

**Mở rộng sau MVP:** Hash chaining/external immutable storage only when threat/compliance requires; retention legal review; export controls.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Update limit with reason, inspect before/after; attempt to alter audit; show rollback test. Hỏi: application-level append-only có đảm bảo chống DBA sửa không?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
