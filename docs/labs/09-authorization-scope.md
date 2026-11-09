# BL-09 — Phân quyền theo chủ sở hữu và phạm vi

> **Domain minh họa:** Tài liệu thuộc tổ chức  
> **Nhóm:** authorization / scope  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao đảm bảo actor chỉ xem/sửa tài liệu nằm trong scope của mình dù đoán được ID?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Người dùng; member tổ chức; manager; administrator theo policy cụ thể.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-09-01 | Mỗi tài liệu thuộc đúng một organization. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-09-02 | Read/update/delete mỗi hành động có policy riêng. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-09-03 | Membership và role phải được xác minh server-side; client-supplied organization_id không có thẩm quyền. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-09-04 | Query được scope ngay từ đầu hoặc policy kiểm tra object sau load; lựa chọn tránh lộ existence tùy threat model. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-09-05 | Admin cross-organization access phải là ngoại lệ audit được, không phải bypass ẩn. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-09-01:** Mọi truy cập data đều nằm trong authorized scope.
- **INV-09-02:** Tạo tài liệu không thể gán vào org mà actor không thuộc.
- **INV-09-03:** Permission revoke có hiệu lực theo consistency model đã định.

## 3. Scenarios cần mô hình hóa

- **SC-09-00:** User A cannot read/update/delete B document by changing ID.
- **SC-09-01:** Membership disabled; owner leaves org; role matrix.
- **SC-09-02:** Create with forged organization id rejected/overridden.
- **SC-09-03:** Background job uses explicit tenant context.
- **SC-09-04:** Admin bypass audit and boundaries tested.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `organizations` | `id, name` | tenant boundary. |
| `organization_memberships` | `id, organization_id, user_id, role, active` | unique(org,user). |
| `documents` | `id, organization_id, owner_id, title, body` | FK organization/owner; indexes org+owner. |
| `document_permissions` | `optional` | chỉ khi ACL per-document thật sự cần. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Mọi truy cập data đều nằm trong authorized scope. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Unique `(organization_id,user_id)` membership; composite indexes `(organization_id,id)` và `(organization_id,owner_id)` tùy query. FK document.organization_id; ON DELETE policy deliberate. Tránh denormalized org_id thiếu consistency nếu child joins cần enforcement.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`DocumentPolicy` handles abilities; `OrganizationScopedDocumentQuery`/relationship scopes for collection; FormRequest validates shape only. Service loads actor scope and saves. Do not trust route model binding alone for authorization.

### Factory

OrganizationFactory; MembershipFactory states roles; DocumentFactory state belongsTo org+owner membership. Factory callback must maintain same tenant invariant.

### Seeder

Two organizations with similar ids/users, cross-tenant fixtures; role matrix. Avoid only one tenant seed as it hides isolation bugs.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/AuthorizationScope/...
├── Http/Controllers/AuthorizationScope/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/AuthorizationScope/...
├── Domain/AuthorizationScope/ # pure rules/value objects nếu giúp rõ
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

Authenticate → resolve permitted organization context → query document within scope (or load then authorize) → authorize action → validate mutation → persist with org id from trusted context. Never use request org id as final authority.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Global scopes reduce omission risk but can surprise jobs/admin and are easy to bypass; explicit scoped queries are verbose but visible. 404 vs 403 controls existence disclosure. RBAC simple roles; ABAC/ACL flexible but policy complexity grows.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Route is scoped but export/search endpoint is not; global scope bypassed in raw query; cached result key lacks tenant; async job loses tenant context.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Route is scoped but export/search endpoint is not; global scope bypassed in raw query; cached result key lacks tenant; async job loses tenant context.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] User A cannot read/update/delete B document by changing ID.
- [ ] Membership disabled; owner leaves org; role matrix.
- [ ] Create with forged organization id rejected/overridden.
- [ ] Background job uses explicit tenant context.
- [ ] Admin bypass audit and boundaries tested.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Route is scoped but export/search endpoint is not; global scope bypassed in raw query; cached result key lacks tenant; async job loses tenant context.

**Mở rộng sau MVP:** Row-level security where available, nested org hierarchy, temporary sharing, audit, cache isolation. Decide if tenant isolation is security boundary or just UI filter.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Đăng nhập hai user/tổ chức, sửa ID request và thử export/search. Hỏi: tại sao policy ở UI không đủ?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
