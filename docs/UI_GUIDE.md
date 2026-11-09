# UI Guide — Giao diện thư viện Business Logic Lab

> Tài liệu này định hướng trải nghiệm của learning shell và cách mỗi ví dụ trình bày business logic. UI hỗ trợ hiểu/nghiệm thử rule; không biến repo thành sản phẩm quản lý một domain duy nhất.

## 1. Mục tiêu UI

- Giúp người học tìm một bài theo logic/kỹ năng, không cần biết trước tên domain.
- Cho xem demo và giải thích cùng nhau: rule, input, kết quả, trường hợp biên và liên kết test/code.
- Bộc lộ trạng thái feature rõ ràng: planned, implemented, enabled, disabled, evidence-backed.
- Làm nổi bật quyết định nghiệp vụ và dữ liệu tối thiểu thay vì form CRUD dài.
- Đảm bảo mọi kiểm tra quan trọng vẫn nằm phía server.

**UI không có mục tiêu:** giả lập sản phẩm production đầy đủ, trang trí dashboard không phục vụ học tập, hoặc chứa logic nghiệp vụ chính trong React.

## 2. Người dùng và nhiệm vụ

| Người dùng | Nhiệm vụ chính | Thông tin cần thấy |
|---|---|---|
| Learner/reviewer | Tìm lab, hiểu rule, chạy scenario | Domain, mục tiêu, độ khó, trạng thái, demo steps |
| Demo presenter | Trình bày thành công và failure path | Reset data, sample input, kết quả và bằng chứng |
| Admin/maintainer | Bật/tắt lab, xem trạng thái triển khai | Effective state, default/override, lý do, thời điểm/actor |
| Contributor | Thêm lab, đề xuất chỉnh sửa | Template, taxonomy, checklist, link file/code/tests |

## 3. Information architecture

```text
Home / Lab Library
├── Search + filters (topic, difficulty, state, domain)
├── Lab detail
│   ├── Overview & learning outcome
│   ├── Business rules / decision table
│   ├── Interactive demo
│   ├── Edge cases / failure modes
│   ├── Data model & technical choices
│   ├── Tests & evidence
│   └── Source/docs links
└── Admin (if runtime toggle is enabled)
    ├── Feature catalog
    └── Toggle audit/history
```

Global menu là catalog đa domain; không có menu sản phẩm như “Orders/Customers/Inventory” ở cấp toàn app. Những tên domain chỉ xuất hiện trong từng lab.

## 4. Trang thư viện lab

### Card/list fields

- Tên logic, ví dụ domain, một câu hỏi nghiệp vụ.
- Skills: transactions, state machine, time, pricing, permissions...
- Difficulty/status: planned, in progress, implemented, evidence-backed.
- Availability: enabled/disabled, chỉ dành cho admin view; learner disabled lab không được link.
- CTA: “Mở bài” / “Xem phân tích”; không dùng “Quản lý” nếu chưa có demo tương tác.

### Search/filter

- Search tên, mô tả và tag; debounce UI nhưng query vẫn validate server-side.
- Filter theo kỹ năng và trạng thái; default chỉ lab enabled và implemented.
- Sort ổn định: relevance hoặc title; pagination/size nếu catalog lớn.
- Empty state hướng dẫn clear filters; không tạo giả dữ liệu.

## 5. Trang chi tiết lab

Một layout lặp lại giúp người học nhận ra cấu trúc:

1. **Problem:** actor đang gặp vấn đề gì?
2. **Rule panel:** danh sách rule có IDs và trạng thái đạt/chưa đạt trong demo.
3. **Scenario controls:** happy path, boundary, failure/concurrency simulator nếu lab hỗ trợ.
4. **Outcome:** kết quả dễ đọc, error code, thay đổi state/data.
5. **Why:** giải thích ngắn vì sao rule cho kết quả đó.
6. **Data view:** chỉ field quan trọng; có thể bật xem technical details.
7. **Evidence:** link tới test, migration, ADR; trạng thái evidence không giả thành xanh nếu chưa chạy.
8. **Trade-off:** phương án hiện tại và giới hạn.

### Progressive disclosure

Mặc định cho thấy mục tiêu, thao tác demo và kết quả. Phần SQL, lock timeline, schema detail, stack trace đã sanitize mở rộng theo nhu cầu. Không đặt toàn bộ bài viết 12k chữ thành một form/accordion khó điều hướng; docs Markdown vẫn là nguồn chi tiết.

## 6. UI toggle và trạng thái

Mỗi state có nghĩa riêng:

| State | Hiển thị cho learner | Admin control |
|---|---|---|
| Planned/unimplemented | Card “đang lên kế hoạch” tùy chính sách, không có demo link | Không bật được |
| Implemented + enabled | Có thể mở lab; backend vẫn authorize | Toggle on |
| Implemented + disabled | Ẩn link hoặc card disabled có thông báo | Toggle off |
| Evidence-backed | Badge thể hiện test/evidence đã ghi, không ngụ ý production-ready | Không phải toggle state |
| Retired | Ẩn khỏi catalog mặc định, còn link lịch sử nếu cần | Không toggle |

Admin toggle row nên hiển thị label, mô tả, group, `implemented`, effective state, source (default/override), last actor/time và optional reason. Nút switch có accessible label dạng “Bật lab X”. Nếu save thất bại, revert optimistic state và hiện lỗi.

Toggle backend enforcement được mô tả chi tiết tại [BL-00 Feature Catalog và bật/tắt lab](labs/00-feature-catalog-toggle.md).

## 7. UI states và feedback

Mỗi tương tác cần có:

- **Loading:** disable duplicate submit; aria-busy cho vùng cập nhật.
- **Success:** nói rõ điều gì đổi và kết quả kế tiếp.
- **Validation error:** gắn field + summary; giữ input hợp lệ.
- **Business conflict:** nêu rule bị vi phạm và bước người dùng có thể làm.
- **Forbidden/disabled:** không lộ dữ liệu; hướng dẫn phù hợp theo policy.
- **Empty:** giải thích chưa có dữ liệu và cách tạo sample.
- **Network/server failure:** giữ dữ liệu đã nhập nếu an toàn; reference ID nếu backend cung cấp.

Không dùng màu làm tín hiệu duy nhất; trạng thái luôn có text/icon. Các lỗi concurrency nên hiển thị conflict/reload action thay generic “Có lỗi”.

## 8. UI technology recommendation

### Đề xuất cho demo Laravel

Laravel + Inertia + React + TypeScript là lựa chọn phù hợp nếu muốn menu phong phú, tương tác kịch bản và đã có React experience. Inertia giữ một ứng dụng, auth/session và validation flow Laravel; chưa cần tách API/backend/frontend độc lập. Blade thuần cũng là lựa chọn tốt nếu demo chủ yếu là form đơn giản và ưu tiên tập trung backend.

| Lựa chọn | Ưu điểm | Chi phí | Dùng khi |
|---|---|---|---|
| Blade | Ít stack, server state rõ, triển khai nhanh | Tương tác scenario phức tạp cần thêm JS | Docs/catalog/form đơn giản |
| Inertia + React/TS | UX component tốt, typed props, một app | Cần quản lý component/state/build | Menu lab, interactive simulators |
| SPA + REST API riêng | Tách deploy/client, API portfolio | Auth, contracts, CORS, error consistency, hai app surface | Khi API/mobile/client độc lập là learning goal |

Không xây design system lớn hoặc Redux layer trước khi có nhu cầu. Mỗi lab giữ component local; shared components chỉ cho shell, status badge, rule table, result panel và form primitives.

## 9. Accessibility và responsive

- Landmark semantic (`header`, `nav`, `main`), đúng heading hierarchy.
- Keyboard navigation và visible focus; dialog/switch accessible.
- Label/help/error programmatically associated với form control.
- Contrast đạt mức dễ đọc; color plus icon/text.
- Table có header; mobile chuyển sang card/list khi table không dùng được.
- Không bắt buộc drag/hover để thao tác demo.
- Respect reduced-motion; animation ngắn chỉ để báo state transition.
- Test at mobile width, keyboard-only và screen-reader labels cho controls quan trọng.

## 10. Visual direction

- Phong cách: technical notebook / lab bench — nền sáng trung tính, typography dễ đọc, accent xanh lam hoặc teal dùng cho action, amber cho warning, đỏ cho error.
- Một lab page cần có hierarchy rõ: câu hỏi → rule → thao tác → kết quả → bằng chứng.
- Card không nên chỉ là gradient và icon; phải mô tả logic/kỹ năng.
- Code, SQL, timeline dùng monospaced block có copy button; không cắt nội dung quan trọng.
- Dark mode optional; ưu tiên contrast/highlight code trước.
- Icon dùng nhất quán cho trạng thái/nhóm, kèm text label; không emoji rải rác trong form nghiệp vụ.

## 11. Frontend/backend boundaries

- Backend trả effective feature catalog và quyền hiển thị; React không tự đoán config.
- Backend kiểm tra feature enabled trên route/action, sau đó kiểm tra authorization nghiệp vụ.
- Request UI chỉ gửi user-editable input; price/status/tenant/expiry authoritative ở backend.
- Inertia props/API DTO tối thiểu; không gửi models nhạy cảm nguyên trạng.
- Validation errors và business conflicts map thành stable error codes nếu frontend cần branching.
- UI demo không sửa DB trực tiếp; mọi mutation đi qua use case.
- Simulation mode (nếu có) phải phân biệt rõ với hành vi production/integration thật.

## 12. Content/UI integration

Mỗi lab README/doc nên cung cấp frontmatter hoặc metadata tương đương:

```yaml
key: inventory.reservation
 title: Tồn kho, giữ hàng và capacity
 question: Làm sao ngăn bán vượt tồn khi nhiều đơn cùng mua?
 topics: [invariant, transaction, concurrency]
 difficulty: intermediate
 status: planned
 implemented: false
 default_enabled: false
```

YAML trên là minh họa, không phải schema đã chốt. Nếu metadata được generate thành UI catalog, có một nguồn canonical (registry/file manifest) và validate tại build; không duy trì cùng nội dung bằng tay ở ba nơi.

## 13. UI acceptance checklist

- [ ] Learner tìm lab theo logic/kỹ năng, không phải chỉ domain.
- [ ] Mỗi lab card nêu problem/question và trạng thái thật.
- [ ] Trang lab theo cùng hierarchy nhưng có thể tùy biến controls theo bài.
- [ ] Disabled/unimplemented routes bị backend chặn.
- [ ] Toggle khác permission; UI và backend dùng cùng resolver.
- [ ] Loading, empty, success, validation, conflict, forbidden states rõ.
- [ ] Keyboard/accessibility/responsive cơ bản được rà.
- [ ] Không có sample/benchmark/evidence giả.
- [ ] UI phục vụ giải thích logic; không phát sinh CRUD field không phục vụ bài học.

## 14. Trình tự xây UI

1. Wireframe library/index và lab detail; dùng mock content.
2. Chốt metadata contract và backend props.
3. Xây shared shell/card/rule table/result/error components.
4. Kết nối một lab end-to-end trước khi chuẩn hóa component cho mọi lab.
5. Thêm admin toggle sau khi resolver/gate hoạt động.
6. Kiểm tra responsive/accessibility và trạng thái lỗi.
7. Mở rộng scenario controls theo từng lab; không làm một simulator tổng quát quá sớm.
