# BL-06 — Validation theo ngữ cảnh nghiệp vụ

> **Domain minh họa:** Đăng ký hội thảo  
> **Nhóm:** contextual / validation  
> **Trạng thái:** đã triển khai local; bằng chứng chức năng trên SQLite đã pass; bằng chứng MySQL/concurrency đang chờ
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.

**Triển khai hiện tại:** đăng ký workshop với FormRequest cho shape validation, `RegistrationPolicy` cho contextual eligibility, transaction lock trên event, unique event+attendee và server-side invite code verification.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao tách input sai định dạng khỏi input không hợp lệ theo policy hiện hành?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Người đăng ký; organizer; hệ thống eligibility.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-06-01 | Email/required/format là input validation; eligibility là business rule. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-06-02 | Đăng ký chỉ mở trong registration window và user chưa đăng ký. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-06-03 | Một số loại vé cần điều kiện (ví dụ invite code); lỗi trả mã/message có thể hành động. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-06-04 | Rule được kiểm tra lại trong use case tại thời điểm ghi, không chỉ ở frontend. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-06-05 | Nguồn dữ liệu eligibility authoritative; không tin các field client gửi. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-06-01:** Không có đăng ký duplicate theo event+attendee nếu policy single registration.
- **INV-06-02:** Không có signup sau khi hết capacity (phối hợp BL-03).
- **INV-06-03:** Error mapping không làm lộ dữ liệu nhạy cảm.

## 3. Scenarios cần mô hình hóa

- **SC-06-00:** Malformed input vs valid shape but ineligible.
- **SC-06-01:** Boundary thời điểm mở/đóng registration, dùng injected clock.
- **SC-06-02:** Duplicate request; race capacity ở DB-level.
- **SC-06-03:** Actor không đủ điều kiện không biết dữ liệu nhạy cảm.
- **SC-06-04:** API/UI mapping giữ error code nhất quán.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `events` | `id, registration_opens_at, registration_closes_at, status` | window policy. |
| `event_registrations` | `id, event_id, attendee_id, status, created_at` | unique (event_id, attendee_id). |
| `eligibility_rules` | `phụ thuộc scope; có thể config đơn giản` | Không tạo bảng rule engine tổng quát khi chỉ có một rule. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Không có đăng ký duplicate theo event+attendee nếu policy single registration. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Event times timezone-aware convention (UTC instants); unique(event_id, attendee_id); FK; index event/status nếu list. Eligibility policy phức tạp có thể nằm trong bảng cụ thể nhưng tránh generic JSON rule engine trước bằng chứng.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`RegisterForEvent` use case; `RegistrationPolicy` pure decision receiving event, actor, clock and eligibility facts. FormRequest chỉ shape/syntax. Return typed result/error code; controller maps to HTTP/UI.

### Factory

EventFactory states draft/open/closed; AttendeeFactory; RegistrationFactory duplicate fixtures; factory dates không lệ thuộc thời gian hệ thống.

### Seeder

Một event mở, đóng, full; một attendee eligible/ineligible; invite code demo không chứa secret thực.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/ContextualValidation/...
├── Http/Controllers/ContextualValidation/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/ContextualValidation/...
├── Domain/ContextualValidation/ # pure rules/value objects nếu giúp rõ
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

Validate transport input → load event+policy facts → evaluate business eligibility → transaction recheck uniqueness/capacity → persist. Với dữ liệu mutable, policy check có thể cần lock/version; tránh TOCTOU giữa validation và write.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Laravel FormRequest cho cấu trúc request tiện và rõ; nhồi business rules vào request làm khó reuse/transaction. Domain policy object dễ test; không cần general validation framework. Error enum ổn định hơn so sánh message text.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Chỉ validate ở frontend; policy check trước transaction rồi capacity thay đổi; exception bị map thành 500; message lộ attendee existence.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Chỉ validate ở frontend; policy check trước transaction rồi capacity thay đổi; exception bị map thành 500; message lộ attendee existence.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Malformed input vs valid shape but ineligible.
- [ ] Boundary thời điểm mở/đóng registration, dùng injected clock.
- [ ] Duplicate request; race capacity ở DB-level.
- [ ] Actor không đủ điều kiện không biết dữ liệu nhạy cảm.
- [ ] API/UI mapping giữ error code nhất quán.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Chỉ validate ở frontend; policy check trước transaction rồi capacity thay đổi; exception bị map thành 500; message lộ attendee existence.

**Mở rộng sau MVP:** Multi-step application, review workflow, policy configuration, localization. Tách validation transport/domain không đồng nghĩa mỗi rule cần class riêng.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Cùng payload hợp lệ định dạng nhưng hai event cho kết quả khác vì rule. Hỏi: đâu là nơi phải validate lại khi submit?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
