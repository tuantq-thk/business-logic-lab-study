# BL-02 — Giá, giảm giá và thứ tự áp dụng

> **Domain minh họa:** Giỏ hàng đặt mua sách  
> **Nhóm:** pricing / discounts  
> **Trạng thái:** evidence-backed locally; implementation đã có trong `laravel/app/Features/PricingDiscounts`
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** phân tích được rule, dữ liệu tối thiểu và cách chứng minh hành vi bằng code/test.
## 1. Tóm tắt và phạm vi

**Bài toán:** Làm sao tính tổng tiền xác định được khi nhiều rule giảm giá cùng đủ điều kiện?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây, có form quote tương tác server-side. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** Người mua; hệ thống pricing; quản trị viên cấu hình promotion.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
|---|---|---|
| BR-02-01 | Đơn giá được chụp tại thời điểm quote/order, không đọc giá mới để sửa đơn cũ. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-02-02 | Tính subtotal từ quantity × unit price; áp khuyến mãi theo thứ tự được định nghĩa. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-02-03 | Mỗi promotion có thời gian hiệu lực, điều kiện tối thiểu và scope sản phẩm rõ. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-02-04 | Chính sách stacking ghi rõ promotion được cộng dồn, loại trừ hay chọn mức giảm tốt nhất. | Chốt boundary, actor và thời điểm áp dụng trong test. |
| BR-02-05 | Giảm giá không làm tổng âm; rounding/currency quy định thống nhất. | Chốt boundary, actor và thời điểm áp dụng trong test. |

### Invariants

- **INV-02-01:** Tổng các line và discount khớp grand total theo một phép tính xác định.
- **INV-02-02:** Tiền không đi qua float nhị phân.
- **INV-02-03:** Quote có thể tái dựng: lưu giá/discount/currency/rule identifier dùng khi đặt.

## 3. Scenarios cần mô hình hóa

- **SC-02-00:** Bảng test subtotal, fixed/percentage, threshold, stacking/exclusion.
- **SC-02-01:** Boundary starts/ends; quantity 0/âm; promotion hết hạn.
- **SC-02-02:** Rounding từng line vs tổng đơn; đảm bảo chọn một policy.
- **SC-02-03:** Quote giữ giá và discount snapshot sau khi catalog đổi; checkout/reprice là bài toán phase sau.
- **SC-02-04:** Không thể áp code khác currency hoặc vượt mức giảm.

Decision table của lab nối mỗi scenario với rule ID, expected result, error code và side effect. Không gom các lỗi nghiệp vụ khác nhau thành một thông báo chung nếu người dùng có thể xử lý khác nhau.

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
|---|---|---|
| `pricing_products` | `id, sku unique, name_key, unit_price_minor, currency, active` | giá hiện hành; minor unit chỉ hợp lệ khi currency/exponent được định nghĩa. |
| `pricing_promotions` | `id, code unique, kind, value, minimum_subtotal_minor, priority, stackable, starts_at, ends_at, active` | policy promotion hiện hành và cửa sổ hiệu lực. |
| `pricing_quotes` | `id, created_by, currency, subtotal_minor, discount_minor, total_minor` | snapshot tổng tại thời điểm quote. |
| `pricing_quote_lines` | `quote_id, product_id nullable, sku_snapshot, name_snapshot, quantity, unit_price_minor, line_total_minor` | giữ lịch sử dù product đổi giá/tên hoặc bị xóa. |
| `pricing_quote_discounts` | `quote_id, promotion_id nullable, code_snapshot, discount_minor` | giữ rule và discount thực sự đã áp dụng. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Tổng các line và discount khớp grand total theo một phép tính xác định. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Dùng BIGINT unsigned cho amount minor; quantity integer dương. FK quote→line cascade, snapshot→product/promotion null-on-delete; unique promotion code và index cửa sổ hiệu lực. Nếu nhiều currency, currency code bắt buộc; không cộng số tiền khác currency. Check constraints có thể bổ sung khi DB version/policy được chốt.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`Money` value object, `PriceQuote` immutable DTO, Eloquent Models cho catalog/snapshot; calculation không đặt trong model event.

### Factory

ProductFactory dùng giá nguyên và currency rõ; PromotionFactory states percentage/fixed/expired/eligible; Cart/Order factory có line totals nhất quán. Factory state không tự ý tạo discount giả.

### Seeder

Seeder hiện có 2 product và 3 promotion: hai rule stackable, một rule exclusive và minimum subtotal. Factory hỗ trợ tạo product/promotion fixtures; expired/window-bound scenarios được dựng riêng trong test.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
laravel/app/Features/PricingDiscounts/
├── Catalog/PricingCatalog.php
├── Actions/CalculateCartQuote.php
├── Actions/BuildPricingQuote.php
├── Actions/CreatePricingQuote.php
├── Models/PricingProduct.php
├── Models/PricingPromotion.php
├── Models/PricingQuote*.php
├── Enums/DiscountKind.php
├── Http/Requests/QuoteCartRequest.php
├── Http/Controllers/PricingDiscountsController.php
└── ValueObjects/
    ├── CartLine.php
    ├── DiscountRule.php
    ├── Money.php
    └── PriceQuote.php
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

`HTTP request → auth + lab availability middleware → validate → load DB catalog → calculate pure quote → persist snapshot (POST) → PRG/render`

### Thuật toán tham chiếu

Normalize input → load authoritative catalog/policy → validate quantity/scope → build immutable quote → apply rules theo priority → round theo currency policy → persist quote snapshot khi checkout. Reprice lúc checkout nếu quote hết hạn; trả diff thay âm thầm tính khác.

Input cần normalize một lần; thời gian/clock và rounding nên được cố định để determinism. BL-02 hiện tạo quote snapshot từ catalog tại request và không claim checkout reprice hoặc usage-limit atomicity.

## 9. Phương án triển khai và trade-off

Integer minor units gọn và an toàn cho currency chuẩn nhưng phải xử lý exponent/rounding theo currency. Decimal DB có scale rõ hợp lý khi cần phép tính SQL; phải chọn precision/rounding. Float không phù hợp. Pricing engine tổng quát quá sớm làm business rule khó hiểu; bắt đầu bằng policy cụ thể.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
|---|---|---|---|
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Promotion thay đổi giữa quote và checkout; hai đơn cuối cùng dùng lượt discount cuối; rounding lệch giữa frontend/backend; currency exponent bị giả định 2 chữ số; replay quote cũ.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Các phase sau cần xử lý promotion thay đổi giữa quote và checkout, usage limit đồng thời, rounding lệch frontend/backend, currency exponent và replay quote cũ.

## 12. Test plan và evidence

| Tầng | Kiểm tra | Evidence cần ghi |
|---|---|---|
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [x] Bảng test subtotal, fixed/percentage, threshold, stacking/exclusion.
- [x] Boundary starts/ends; quantity 0/âm; promotion hết hạn.
- [x] Rounding từng line vs tổng đơn; đảm bảo chọn một policy.
- [x] Quote giữ giá/discount snapshot sau catalog đổi.
- [x] Không thể áp code khác currency hoặc vượt mức giảm.

### Evidence hiện tại

- Pure pricing tests bao phủ subtotal, fixed/percentage, half-up rounding, threshold, stacking/exclusive, zero floor và mixed currency.
- Feature tests bao phủ catalog gate, validation, PRG quote flow, quote snapshot, actor scope, repeated quotes và admin promotion CRUD/permission.
- Full suite hiện tại: 61 tests, 341 assertions trên SQLite; pricing-specific suite trong technical record: 23 tests, 110 assertions.
- MySQL integrity checks cho snapshot invariants đã được migration và technical record ghi nhận.

### Giới hạn hiện tại

- Chưa có checkout, payment provider, product admin CRUD, usage limit, reservation hoặc concurrency harness.
- Currency policy hiện là integer minor unit cho demo USD; chưa chứng minh exponent/rounding policy cho mọi ISO-4217 currency.
- Chưa có browser visual/accessibility QA. Cần chạy lại MySQL trong môi trường hiện tại trước khi dùng kết quả đó như evidence mới nhất.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Promotion thay đổi giữa quote và checkout; hai đơn cuối cùng dùng lượt discount cuối; rounding lệch giữa frontend/backend; currency exponent bị giả định 2 chữ số; replay quote cũ.

**Mở rộng sau MVP:** Promotion usage limit cần atomic reservation; tax/shipping; price book theo vùng; nhiều currency; promotion priority versioning.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Cùng giỏ áp hai rule theo hai thứ tự để thấy kết quả; show snapshot và test rounding. Hỏi: giá nào là nguồn sự thật sau khi đơn đã đặt?

## 15. Chia chặng triển khai

1. Chốt glossary, assumptions, rule IDs, decision table.
2. Viết pure rule và test trước nếu rule xác định.
3. Thiết kế migration/constraints; kiểm tra schema trên MySQL.
4. Viết model/factory/seeder theo trạng thái hợp lệ.
5. Thêm use case, authorization và transaction boundary.
6. Bổ sung integration/concurrency/failure tests phù hợp.
7. Tạo UI/API demo, docs chạy/reset và ghi evidence.

### BL-02 đã triển khai

1. Chốt policy MVP: minor units dạng integer, percentage half-up, rule threshold theo subtotal, stackable áp tuần tự theo priority; exclusive cạnh tranh với kết quả stacked và chọn discount lớn hơn.
2. Tạo immutable value objects `Money`, `CartLine`, `DiscountRule`, `PriceQuote` và enum `DiscountKind` trong feature module.
3. Tạo `CalculateCartQuote` làm application action thuần, không phụ thuộc Eloquent hay request.
4. Thêm demo route/UI, registry metadata, runtime catalog gate và translation `vi`/`en`.
5. Thêm form tương tác: quantity từng line, chọn/bỏ promotion và POST server-side để tính lại quote.
6. Thêm nhiều promotion scenario (`SAVE300`, `BULK15`) và preset UI cho small/stacking/exclusive flow.
7. Thêm recent quote history và quote snapshot detail có actor scope.
8. Thêm Admin Promotion CRUD với validation code/type/value/window và preserve quote snapshots khi xóa promotion.
9. Viết unit test cho subtotal, rounding, threshold, stacking/exclusion, zero floor và mixed currency; feature test cho route enabled/disabled, valid quote, validation error, locale, DB snapshot, actor scope, repeated quotes và admin permission matrix.

Persistence của product/promotion catalog và quote snapshot đã thuộc BL-02 MVP. Admin CRUD promotion đã thuộc scope; product admin CRUD, checkout reprice, payment provider, usage limit và concurrency chưa thuộc scope; không claim đã triển khai.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
