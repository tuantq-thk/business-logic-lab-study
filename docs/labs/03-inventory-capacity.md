# BL-03 — Tồn kho, giữ hàng và capacity

> **Domain minh họa:** Kho của cửa hàng nhỏ  
> **Nhóm:** inventory / capacity  
> **Trạng thái:** implemented locally; SQLite evidence đã pass, MySQL concurrency evidence đang pending
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao ngăn bán vượt tồn khi nhiều đơn cùng mua đơn vị cuối?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor trong MVP:** user tạo reservation, admin consume/release/điều chỉnh và system expiry worker. Order/payment provider và nhân viên kho riêng chưa thuộc scope.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-03-01 | Available = on_hand − reserved; không thể reserve nếu available < requested. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-03-02 | Reservation có trạng thái active/consumed/released/expired và lượng dương. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-03-03 | Xác nhận đơn chuyển reserved sang consumed đúng một lần. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-03-04 | Hủy/timeout giải phóng reservation một lần; không âm tồn. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-03-05 | Điều chỉnh tồn cần actor và lý do; không sửa số dư âm bằng cách ghi đè không lịch sử. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-03-01:** on_hand ≥ 0; reserved tổng không vượt on_hand.
- **INV-03-02:** Mỗi reservation được consume/release tối đa một lần.
- **INV-03-03:** Stock ledger delta và balance nhất quán nếu chọn ledger model.

## 3. Scenarios cần mô hình hóa

- **SC-03-00:** Reserve vừa đủ; vượt tồn bị từ chối.
- **SC-03-01:** Hai concurrent requests tranh số cuối: chỉ một thành công.
- **SC-03-02:** Release/consume lặp không đổi tồn lần hai.
- **SC-03-03:** Expiry worker và confirm race: kết quả chỉ một chuyển trạng thái thắng.
- **SC-03-04:** Rollback giữ balance và reservation đồng bộ.

Trước khi code, lập decision table nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `inventory_items` | `id, sku unique, on_hand, version` | balance hiện tại cho conditional update. |
| `inventory_reservations` | `id, inventory_item_id, order_ref, quantity, status, expires_at` | unique (order_ref, inventory_item_id) nếu mỗi order chỉ có một reservation/item. |
| `inventory_movements` | `id, inventory_item_id, delta, reason, reference, actor_id, created_at` | ledger immutable tùy scope bài. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: on_hand ≥ 0; reserved tổng không vượt on_hand. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

`inventory_items.on_hand` và `reserved_quantity` BIGINT; check cả hai không âm và reserved không vượt on_hand; quantity integer >0. FK reservation/movement; index reservation `(status, expires_at)` cho expiry worker. Unique chống reservation lặp theo business key. `reserved_quantity` là projection/counter để khóa một inventory row và cập nhật nhanh; cần reconciliation query so với SUM(active reservations). Mô hình thay thế là tính SUM active holds trong transaction, dễ tránh counter drift hơn nhưng cần locking/canonical inventory row để ngăn race.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`InventoryItem`, `InventoryReservation`, `InventoryMovement`; use cases `ReserveStock`, `ConsumeReservation`, `ReleaseReservation`. Không cho controller chỉnh on_hand trực tiếp.

### Factory

InventoryItemFactory states inStock/low/zero; ReservationFactory active/expired/consumed với quantity hợp lệ; MovementFactory đồng bộ balance khi dùng ledger. Không seed random quantity và reservations làm invariant vỡ.

### Seeder

Một SKU còn nhiều, một SKU vừa đủ cho một đơn, một SKU hết. Seed stable ids/business references để demo dễ reset.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/InventoryCapacity/...
├── Http/Controllers/InventoryCapacity/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/InventoryCapacity/...
├── Domain/InventoryCapacity/ # pure rules/value objects nếu giúp rõ
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

Mô hình mẫu dùng `on_hand` + `reserved_quantity`: trong transaction khóa inventory row (hoặc CAS update `reserved_quantity = reserved_quantity + :q WHERE on_hand - reserved_quantity >= :q`), insert reservation; khi consume thì giảm cả `on_hand` và `reserved_quantity`; khi release chỉ giảm `reserved_quantity`. Kiểm tra affected rows và giữ các cập nhật cùng transaction. Nếu chọn derived SUM reservations, phải khóa một row cha ổn định hoặc dùng inventory-slot rows để hai request không cùng vượt capacity. Chứng minh concurrency trên MySQL.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Tính SUM reservations tránh counter duplicated state nhưng có query/lock cost; counter `reserved_quantity` đọc nhanh nhưng có thể drift và cần reconciliation. Ledger mạnh về audit nhưng cần projection/reconciliation. Row lock dễ hiểu nhưng serialize cùng SKU; optimistic/conditional update tăng throughput nhưng cần conflict handling.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Đơn tạo nhưng reservation rollback hoặc ngược lại; worker hết hạn chạy hai lần; update balance không ledger; tồn vật lý khác số hệ thống; lock nhiều SKU theo thứ tự khác gây deadlock.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Đơn tạo nhưng reservation rollback hoặc ngược lại; worker hết hạn chạy hai lần; update balance không ledger; tồn vật lý khác số hệ thống; lock nhiều SKU theo thứ tự khác gây deadlock.

## 12. Test plan và evidence

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [x] Reserve vừa đủ; vượt tồn bị từ chối.
- [ ] Hai concurrent requests tranh số cuối: chỉ một thành công — cần MySQL.
- [x] Release/consume lặp không đổi tồn lần hai.
- [x] Expiry/consume lifecycle được kiểm tra tuần tự; race thật cần MySQL.
- [x] Action transaction giữ balance và reservation đồng bộ trong functional tests.

Evidence hiện tại: 72 tests, 387 assertions passed trên SQLite; migration, InnoDB, foreign key, CHECK constraint, reconciliation và EXPLAIN cơ bản đã được xác nhận trên MySQL 9.5.0. Chưa claim hai-connection concurrency, deadlock retry thực tế hoặc isolation race harness. Chi tiết implementation map và verification limits nằm trong [BL-03 technical record](../technical/BL-03-implementation.md).

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Đơn tạo nhưng reservation rollback hoặc ngược lại; worker hết hạn chạy hai lần; update balance không ledger; tồn vật lý khác số hệ thống; lock nhiều SKU theo thứ tự khác gây deadlock.

**Mở rộng sau MVP:** Multi-warehouse, backorder, stock count reconciliation, reservation TTL, outbox event. Không gộp capacity chỗ ngồi với tồn vật lý nếu semantics khác.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Tạo hai request cùng mua đơn vị cuối, trình bày transaction/test thực tế. Hỏi: reserved là dữ liệu gốc hay giá trị tính ra?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
