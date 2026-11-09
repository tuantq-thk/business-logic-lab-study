# BL-10 — Import CSV có preview và lỗi từng dòng

> **Domain minh họa:** Nhập danh mục sản phẩm  
> **Nhóm:** csv / import  
> **Trạng thái:** kế hoạch phân tích; chưa phải evidence triển khai  
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao người dùng xem trước thay đổi và xử lý file có dòng đúng, sai và trùng?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Operator tải file; reviewer xác nhận; import worker khi file lớn.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-10-01 | File phải theo schema/version xác định; header/encoding/delimiter xử lý rõ. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-10-02 | Preview không ghi dữ liệu và báo row number + field + stable error code. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-10-03 | Duplicate policy chọn một: reject entire file, skip duplicates, update existing; không để ngầm. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-10-04 | Commit revalidates khi dữ liệu nền có thể đổi giữa preview và apply. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-10-05 | Batch size/transaction boundary có nghĩa: all-or-nothing hay partial success. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-10-01:** Một file/job không nhập cùng logical row hai lần do retry.
- **INV-10-02:** Import result đếm accepted/rejected/skipped khớp các dòng xử lý.
- **INV-10-03:** CSV formulas và file content không trở thành data leak trong exports/logs.

## 3. Scenarios cần mô hình hóa

- **SC-10-00:** Malformed headers/quoted commas/BOM/encoding.
- **SC-10-01:** Rows valid, invalid, duplicate by selected policy.
- **SC-10-02:** Preview performs no writes.
- **SC-10-03:** Apply retry doesn’t duplicate rows; concurrent same SKU respects unique.
- **SC-10-04:** Large file limits, formula injection in exported error report.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `import_jobs` | `id, actor_id, file_hash, status, mode, summary_json, created_at` | idempotent job tracking. |
| `import_rows` | `optional for persisted preview: id, import_job_id, row_number, normalized_payload, errors, status` | chỉ cần nếu preview qua nhiều request. |
| `products` | `id, sku unique, name, price_minor, active` | target entity. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Một file/job không nhập cùng logical row hai lần do retry. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Unique SKU collation/case policy rõ; import job key/file hash có scope/retention. Không lưu upload binary trong DB mặc định; storage private + expiry. JSON row errors okay for job result, not as generic product fields.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`ParseProductCsv`, `PreviewProductImport`, `ApplyProductImport` use cases; parser yields typed row DTO; validator separates syntax/business; persistence service batch. Laravel Excel library only if requirements justify dependency; CSV native streaming fine for bounded files.

### Factory

ProductFactory with unique SKU; ImportJobFactory statuses; ImportRowFactory valid/error. Avoid factories generating same SKU accidentally.

### Seeder

Seed existing SKUs to demonstrate duplicate policy; not seed actual uploaded file. Keep downloadable sample CSV in docs/examples.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/CsvImport/...
├── Http/Controllers/CsvImport/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/CsvImport/...
├── Domain/CsvImport/ # pure rules/value objects nếu giúp rõ
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

Validate upload bounds/mime/encoding → stream parse rows → normalize → collect row errors → preview report → on apply re-run checks and persist under selected transaction policy. Large job queue with idempotent row keys/import_job+row number.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Whole-file transaction simpler and atomic but can lock long and rollback all. Per-batch commits scale but require partial status/retry semantics. Persisting preview allows review but stores sensitive data and gets stale; recompute at apply. Loading entire CSV easy but memory-risk.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Preview and apply data drift; worker times out after partial commit; duplicate on retry; CSV formula injection; temp file exposed; memory exhaustion.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Preview and apply data drift; worker times out after partial commit; duplicate on retry; CSV formula injection; temp file exposed; memory exhaustion.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Malformed headers/quoted commas/BOM/encoding.
- [ ] Rows valid, invalid, duplicate by selected policy.
- [ ] Preview performs no writes.
- [ ] Apply retry doesn’t duplicate rows; concurrent same SKU respects unique.
- [ ] Large file limits, formula injection in exported error report.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Khi triển khai cần ghi kết quả, runtime/DB cụ thể và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Preview and apply data drift; worker times out after partial commit; duplicate on retry; CSV formula injection; temp file exposed; memory exhaustion.

**Mở rộng sau MVP:** Import mappings, dry-run diff, cancellation, resumable chunks, signed download. Add queue only when size/runtime requirement exists.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** File sample has valid, invalid, duplicate rows; show preview then apply and retry. Hỏi: transaction của một file nên bao trùm bao nhiêu dòng?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
