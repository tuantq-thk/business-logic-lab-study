# 🧠 Business Logic Lab

> **Học logic nghiệp vụ qua ví dụ đa domain** — từ tình huống thực tế đến business rules, dữ liệu, code Laravel và bằng chứng kiểm thử.

Mỗi lab là một lát cắt độc lập. Ví dụ có thể dùng support ticket, giỏ hàng, kho, phòng họp, webhook hoặc báo cáo; không bị ép vào cùng một sản phẩm hay bộ model.

## 🎯 Mục tiêu

- Phân tích actor, quy tắc, invariant và trường hợp biên trước khi code.
- Thiết kế migration/model tối thiểu để dữ liệu phản ánh nghiệp vụ.
- Chọn factory, seeder, transaction và kiến trúc vừa đủ.
- Nhận biết race condition, retry, quyền truy cập và failure modes.
- Bảo vệ quyết định bằng test/evidence thay vì chỉ trình bày thuật ngữ.

> 📌 Repo hiện gồm cả tài liệu phân tích và một Laravel learning shell đang chạy. BL-00, BL-01 và BL-02 đã có implementation trong `laravel/`; trạng thái evidence và giới hạn của từng bài được ghi trong technical implementation record. Các BL còn lại vẫn là blueprint, không được hiểu là đã triển khai.

## 🧭 Bắt đầu ở đây

1. Đọc [Roadmap](docs/PLAN.md) để hiểu định hướng, thứ tự học và các quyết định thiết kế.
2. Xem [Feature Catalog & Toggle](docs/labs/00-feature-catalog-toggle.md) — tính năng đầu tiên của learning shell: đăng ký lab, bật/tắt và chặn truy cập từ backend.
3. Tham khảo [UI Guide](docs/UI_GUIDE.md) để hiểu menu, trang lab, trạng thái giao diện và ranh giới frontend/backend.
4. Dùng [Template phân tích](docs/TEMPLATE.md) khi thêm hoặc review một lab.
5. Chọn bài trong [Mục lục lab](docs/INDEX.md) theo logic muốn luyện.

### Nếu muốn chạy code

```bash
cd laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan test --compact
```

PHP/Laravel version, database setup, seed policy và các giới hạn verification được ghi trong [Laravel learning shell README](laravel/README.md).

## 🧪 Các chủ đề đang có

| Chủ đề | Ví dụ tiêu biểu | Năng lực được luyện |
|---|---|---|
| 🔁 Workflow | State transition, hủy/đổi, audit | State machine, invariant, history |
| 💰 Giá và capacity | Discount, tồn kho, giữ chỗ | Money, rounding, transaction, concurrency |
| 🗓️ Thời gian | Calendar, booking, recurrence | Timezone, interval, boundary, expiry |
| 🛡️ Quyền và validation | Tenant scope, contextual validation | Policy, IDOR/BOLA, TOCTOU |
| 📦 Dữ liệu | CSV import, search, export | Query design, indexing, streaming, limits |
| 🔌 Reliability | Idempotency, queue, webhook, outbox | Retry, deduplication, eventual consistency |
| 🧰 Learning shell | Catalog, toggle, UI | Registry, route gating, trải nghiệm học |

Mục lục và danh sách bài có thể mở rộng theo nhu cầu; README không giới hạn kích thước repo trong tương lai.

## 📚 Danh mục tài liệu

- [🗺️ Roadmap và tiêu chí ưu tiên](docs/PLAN.md)
- [🧩 Feature Catalog & Toggle](docs/labs/00-feature-catalog-toggle.md)
- [🎨 UI Guide](docs/UI_GUIDE.md)
- [📝 Template phân tích chuyên sâu](docs/TEMPLATE.md)
- [📖 Mục lục các lab](docs/INDEX.md)

## 🧱 Nguyên tắc làm lab

- Domain là bối cảnh để học; mỗi ví dụ được chọn domain phù hợp riêng.
- Bắt đầu từ rule và invariant, không bắt đầu bằng danh sách field.
- Một field cần có rule, quyền, history hoặc query use case giải thích.
- Tách tính đúng của logic thuần khỏi bảo đảm của DB/transaction.
- Laravel conventions là mặc định; không tạo Service/Repository/DDD layer theo khuôn máy móc.
- Feature toggle điều khiển khả năng mở lab; không cấp quyền nghiệp vụ.
- Dùng MySQL cho bằng chứng phụ thuộc MySQL; không suy rộng từ SQLite.
- Không ghi “đã kiểm chứng” nếu mới có kế hoạch test.

## 📍 Trạng thái hiện tại

| Phần | Trạng thái | Bằng chứng/chú ý |
|---|---|---|
| Authentication và learning shell | implemented | Fortify + Blade, feature tests |
| BL-00 Catalog & Toggle | implemented locally | Runtime catalog/admin gate; chưa có append-only toggle audit |
| BL-01 State transition | evidence-backed locally | Transaction, row lock, optimistic version và stale-conflict harness |
| BL-02 Pricing & discounts | evidence-backed locally | Pure rules, quote snapshot, promotion CRUD; chưa có checkout/concurrency |
| BL-03 Inventory & capacity | implemented locally | Functional SQLite tests; MySQL concurrency/EXPLAIN pending |
| BL-04 Business calendar & slots | implemented locally | SQLite + MySQL functional tests; DST/booking concurrency còn giới hạn |
| BL-05 Booking overlap | implemented locally | SQLite + MySQL functional tests; two-connection concurrency harness pending |
| BL-06–BL-20 | planned | Chỉ có tài liệu phân tích |

“Evidence-backed” ở đây nghĩa là claim được kiểm tra trong runtime đã ghi, không có nghĩa production-ready. SQLite không thay thế evidence MySQL cho locking, isolation, index plan hoặc constraint semantics.

## 🖥️ Demo UI và công nghệ

UI là thư viện bài học có menu chọn lab, trang giải thích rule và vùng demo. Khuyến nghị triển khai một learning shell gọn; có thể dùng Laravel + Inertia + React/TypeScript cho scenario tương tác, hoặc Blade nếu bài chỉ cần form đơn giản. Chưa cần tách SPA/API riêng trừ khi API là nội dung cần học.

Mỗi trang demo ưu tiên: **bài toán → rule → thao tác → kết quả → edge case → test/evidence → trade-off**. Backend vẫn là nơi enforce feature availability, authorization và business rules.

## 🧑‍💻 Author

- **Author:** `tuantq`
- **Git:** [`tqt7`](https://github.com/tqt7)

## 🤝 Góp ý và đóng góp

Mọi nhận xét review và đóng góp đều được chào đón. Bạn có thể mở issue hoặc pull request để:

- Chỉ ra rule còn mơ hồ, thiếu actor, boundary hoặc trường hợp lỗi.
- Phản biện schema, migration, constraint, model/factory/seeder.
- Đề xuất test chứng minh claim, nhất là concurrency/security.
- Báo lỗi kỹ thuật, liên kết sai, thuật ngữ chưa thống nhất hoặc nội dung lặp.
- Đề xuất một logic nghiệp vụ mới kèm domain ví dụ, tần suất gặp và phạm vi đủ nhỏ.

Khi góp ý, vui lòng ghi **bài nào/mục nào**, **vấn đề cụ thể**, **bằng chứng hoặc tình huống tái hiện**, và **đề xuất sửa** nếu có. Với quyết định phụ thuộc DB/framework version, ghi rõ môi trường. Không đưa dữ liệu khách hàng, secret hoặc thông tin production vào issue/fixture.

## 🛣️ Hướng học đề xuất

1. State transition và invariant.
2. Giá/discount và validation theo ngữ cảnh.
3. Capacity, calendar và booking conflict.
4. Idempotency và authorization scope.
5. Audit, queue, import/query.
6. Chọn webhook, outbox, quota, locking hoặc export theo mục tiêu.

Không cần làm mọi lab cùng lúc. Hoàn thiện từng bài với rule rõ, code dễ theo dõi và evidence có thể tái lập sẽ tạo giá trị học tập tốt hơn mở rộng danh sách quá nhanh.
