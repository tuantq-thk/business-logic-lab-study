# BL-02 Technical Implementation Record

> Cập nhật: 2026-11-13
> Phạm vi: `laravel/`
> Trạng thái: evidence-backed locally; chưa commit/push.

## 1. Mục tiêu và boundary

BL-02 chứng minh một quote tiền tệ xác định khi có nhiều discount rule. MVP có pure calculation, DB-backed product/promotion catalog, form quote tương tác server-side và persisted quote snapshot; chưa tạo checkout, payment provider, usage limit hay concurrency reservation.

Policy đã chốt:

- amount là integer minor unit, ví dụ USD cents;
- percentage discount làm tròn half-up ở minor unit;
- rule chỉ đủ điều kiện khi `active` và `minimumSubtotalMinor <= subtotal`;
- stackable rules chạy theo `priority` tăng dần trên base còn lại;
- exclusive rules tính trên subtotal gốc; chọn exclusive discount lớn nhất;
- kết quả stacked và exclusive cạnh tranh, chọn discount lớn hơn; hòa thì giữ stacked;
- total không âm; mọi line phải cùng currency uppercase ISO-4217 code dạng 3 ký tự.
- MySQL enforce thêm các invariant snapshot bằng `CHECK`; SQLite test fallback vẫn dựa trên application validation vì SQLite migration path không hỗ trợ cùng cách alter constraint.

## 2. Folder/module map

```text
app/Features/PricingDiscounts/
├── Catalog/PricingCatalog.php
├── Actions/CalculateCartQuote.php
├── Actions/BuildPricingQuote.php
├── Actions/CreatePricingQuote.php
├── Actions/SavePricingPromotion.php
├── Actions/DeletePricingPromotion.php
├── Exceptions/PricingSelectionUnavailable.php
├── Enums/DiscountKind.php
├── Models/PricingProduct.php
├── Models/PricingPromotion.php
├── Models/PricingQuote.php
├── Models/PricingQuoteLine.php
├── Models/PricingQuoteDiscount.php
├── Http/Requests/QuoteCartRequest.php
├── Http/Controllers/PricingDiscountsController.php
└── ValueObjects/
    ├── CartLine.php
    ├── DiscountRule.php
    ├── Money.php
    └── PriceQuote.php
```

`PricingCatalog` đọc product/promotion hiện hành từ database. Controller nhận validated quantity/rule selection, dùng Post/Redirect/Get để tránh refresh POST gây `MethodNotAllowedHttpException`, rồi render. `BuildPricingQuote` dựng immutable draft; `CalculateCartQuote` giữ pure calculation; `CreatePricingQuote` persist quote, line snapshot và applied discount trong một transaction. Product/promotion là Eloquent models trong feature module; giá/label/code do server sở hữu. Mỗi POST tạo một quote mới; UI chỉ paginate 5 quote gần nhất nhưng database không đặt limit quote.

## 3. Flow và security boundary

```text
authenticated user
  → lab.available middleware
  → PricingDiscountsController@index|quote|show
  → QuoteCartRequest validates client choices against active DB promotions
  → PricingCatalog loads active products/promotions
  → BuildPricingQuote
  → CalculateCartQuote
  → CreatePricingQuote transaction
  → quote/line/discount snapshots
  → PRG → localized Blade + recent history/detail
```

Route GET `/labs/pricing-discounts`, POST `/labs/pricing-discounts/quote` và GET `/labs/pricing-discounts/quotes/{quote}` đều có `auth` và `lab.available`. Catalog menu và direct URL dùng cùng backend effective state. Client chỉ gửi quantity và mã rule; request kiểm tra promotion đang active, còn unit price và discount definition luôn được dựng từ server. Quote snapshot được scoped theo `created_by`, nên user không thể xem lịch sử quote của actor khác.

## 4. Test seams

| Seam | Chứng minh | Giới hạn |
|---|---|---|
| `CalculateCartQuote::handle()` | subtotal, rounding, threshold, stacking/exclusion, floor, currency consistency | không chứng minh DB schema |
| pricing route + catalog middleware | enabled render, interactive quote, validation error và disabled direct URL 404 | không phải browser visual QA |
| Create quote feature test | DB quote/line/discount snapshot, PRG, price snapshot sau catalog đổi | chưa chứng minh concurrent catalog edit |
| registry/seeder hiện hữu | metadata key được provision và locale resolve | không chứng minh pricing policy persistence |

## 5. Files changed

- `app/Features/PricingDiscounts/**`: module code.
- `database/migrations/2026_11_13_130000...130004`: products, promotions, quote snapshots và child rows.
- `database/migrations/2026_11_13_130005_add_pricing_integrity_checks.php`: MySQL `CHECK` cho percentage/window/quote total/line total.
- `database/seeders/PricingDiscountsSeeder.php`: idempotent demo catalog/promotion provisioning.
- `database/factories/PricingDiscounts/*`: valid product/promotion fixtures.
- `app/Features/PricingDiscounts/Http/Controllers/Admin/PricingPromotionController.php`: admin CRUD boundary; persistence is delegated to feature actions.
- `app/Features/PricingDiscounts/Actions/SavePricingPromotion.php`, `DeletePricingPromotion.php`: transactional promotion writes.
- `app/Features/PricingDiscounts/Http/Requests/SavePricingPromotionRequest.php`: admin authorization and promotion validation.
- `resources/views/labs/pricing-discounts/index.blade.php`: compact admin promotion CRUD UI is embedded in the BL-02 view and is rendered only for `manage-catalog` actors; amounts use the feature currency formatter and there is no separate admin CRUD view.
- `config/labs.php`, `routes/web.php`: catalog registry và route.
- `lang/en|vi/labs.php`, `lang/en|vi/pricing_discounts.php`: translation keys, không hardcode presentation text trong registry/controller.
- `resources/views/labs/pricing-discounts/index.blade.php`: flow/timeline-style demo và quote breakdown.
- `app/Features/PricingDiscounts/Http/Requests/QuoteCartRequest.php`: whitelist và validate quantity/rule selection.
- `tests/Unit/PricingDiscounts/PricingQuoteTest.php`: pure rule matrix nhỏ.
- `tests/Feature/PricingDiscounts/PricingDiscountsTest.php`: route/catalog visibility.
- `tests/Feature/AdminCatalogTest.php`: seeder registry count/key contract.
- `tests/Feature/PricingDiscounts/AdminPromotionTest.php`: admin permission, CRUD, delete preservation và snapshot contract.

## 6. Evidence và giới hạn

Verification hiện tại:

| Check | Kết quả |
|---|---|
| Pricing-specific Pest trên SQLite | 23 tests, 110 assertions passed |
| Full Pest trên SQLite | 61 tests, 341 assertions passed |
| Full Pest trên MySQL `laravel-test` | 61 tests, 341 assertions passed |
| MySQL migration `130005` down/up | Passed; CHECK constraints rollback và apply lại trên `laravel-test` |
| Pint (`--dirty --test`) | Passed |
| Vite production build | Passed; có cảnh báo optional `fontaine` từ plugin fonts |
| Route resolution | Pricing GET/POST/detail và admin CRUD routes tồn tại; GET admin compatibility route redirect về BL-02 |
| `git diff --check` | Passed |

Đây là bằng chứng cho pure pricing behavior, DB-backed interactive quote, persisted snapshot và integration với catalog/runtime gate. Không có concurrency harness cho BL-02 vì quote creation hiện không có usage-limit/shared-capacity invariant; concurrency thuộc BL-03.

Chưa claim:

1. Decimal/currency exponent ngoài policy USD minor-unit demo.
2. Promotion edits giữa quote và checkout vẫn chưa có checkout reprice policy.
3. Usage limit atomicity, locking hoặc concurrency.
4. Admin CRUD cho product chưa có; product seeder là provisioning boundary. Promotion CRUD đã có.
5. Browser visual/accessibility audit.

## 7. Next step theo roadmap

Promotion CRUD đã nằm cùng view BL-02 và được bảo vệ bởi `manage-catalog`; route GET admin cũ chỉ redirect về view này để giữ compatibility. Product admin CRUD và policy reprice khi checkout vẫn ngoài scope. Không nên thêm usage-limit concurrency vào BL-02; BL-03 mới là lab dành cho inventory/capacity và MySQL race evidence.
