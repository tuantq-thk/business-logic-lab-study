# BL UI & Content Template Rule

> Mục đích: tạo một chuẩn dùng chung cho mọi trang Business Logic Lab, để người học luôn đi từ bài toán → mô hình → quyết định → code → bằng chứng → thực hành.

## 1. Nguyên tắc bắt buộc

1. **Learning first:** lý thuyết và invariant phải xuất hiện trước form chạy thử.
2. **Một nguồn sự thật:** UI minh họa state; backend/use case/database mới quyết định business rule.
3. **Một block, một nhiệm vụ:** không trộn theory, implementation và practice vào cùng một card.
4. **Nói rõ bằng chứng:** phân biệt `planned`, `implemented locally`, `verified` và giới hạn runtime/test engine.
5. **Dịch đầy đủ:** mọi text hiển thị phải có key EN/VI; pseudo-code, identifier, SQL và API syntax được giữ nguyên khi cần.
6. **Thuật ngữ có chủ đích:** tiếng Việt là ngôn ngữ chính; chỉ giữ invariant, transaction, idempotency, concurrency, deadlock, MySQL và thuật ngữ tương đương.
7. **Không dùng màu thay cho ý nghĩa:** màu phải đi cùng label/text; không truyền đạt warning/error chỉ bằng màu.
8. **Một shell dùng chung:** mọi lab implementation kế thừa `resources/views/components/labs/shell.blade.php`; view của lab chỉ chịu trách nhiệm nội dung domain.

## 2. Thứ tự section chuẩn

| Thứ tự | Section | Câu hỏi người học cần trả lời | Kiểu UI |
|---|---|---|---|
| 1 | Hero / context | Bài toán là gì, phạm vi tới đâu? | Heading + problem statement |
| 2 | Reading path | Nên đọc theo trình tự nào? | Stepper có đường nối |
| 3 | Learning objectives | Học xong chứng minh được gì? | 3–4 objective cards |
| 4 | Theory / mental model | Domain được mô hình hóa ra sao? | Concept board |
| 5 | Architecture / responsibility map | Mỗi layer chịu trách nhiệm gì và không chịu trách nhiệm gì? | Layer cards + code boundary |
| 6 | Data model / database contract | Schema, constraint và index bảo vệ invariant ra sao? | Contract table |
| 7 | Decision table | Mỗi tổ hợp điều kiện cho kết quả nào? | Outcome cards/table |
| 8 | Code flow | Request đi qua những boundary nào? | Timeline/numbered flow |
| 9 | Code examples | Code tối thiểu của từng layer trông như thế nào? | Responsibility cards |
| 10 | Test matrix / evidence | Bằng chứng nào chứng minh từng claim? | Scenario matrix |
| 11 | Performance / scalability | Query, lock, latency và bottleneck nằm ở đâu? | Budget + measurement panel |
| 12 | Trade-offs / limits | Thiết kế bảo vệ gì, tốn gì, chưa chứng minh gì? | Safe/cost/limit cards |
| 13 | Threat / failure analysis | Request cạnh tranh hoặc không đáng tin thì sao? | Attack/defense panel |
| 14 | Pitfalls / anti-pattern | Cách làm nào trông đúng nhưng nguy hiểm? | Warning cards |
| 15 | Maturity levels | Tối thiểu, vận hành tốt và nâng cao khác nhau thế nào? | 3-level comparison |
| 16 | Observability / operations | Khi production lỗi, biết gì và xử lý theo runbook nào? | Logs/metrics/runbook cards |
| 17 | Review checklist | Điều kiện nào cần đạt trước khi gọi là hoàn thiện? | Checklist |
| 18 | Practice | User có thể chạy thử điều gì? | Divider + form/result/error UI |

Không bỏ qua section 4–8 chỉ vì lab có form đơn giản. Nếu một mục không áp dụng, phải ghi rõ `Không áp dụng` và lý do.

## 3. Content contract

### Hero và reading path

- Hero có mã BL, tên logic, độ khó, câu hỏi nghiệp vụ, scope và non-goal.
- Reading path có các mốc chính của flow, có đường nối ngang trên desktop và dọc trên mobile; các section production vẫn phải có anchor để deep-link.
- Step number phải là text thật, không chỉ là decoration.

### Theory và invariant

- Có một nguyên tắc nền tảng viết bằng ngôn ngữ tự nhiên.
- Tách các khái niệm không thể thay thế nhau: interval/resource/state, actor/scope/policy, snapshot/live value...
- Mỗi concept trả lời: nó là gì, tại sao tồn tại, boundary nào dễ sai.
- Công thức hoặc decision table độc lập với framework; code block không dịch identifier/keyword làm sai nghĩa.
- Explanation phải chỉ rõ invariant được bảo vệ ở schema, transaction, policy, action hay test.

### Code flow và implementation

Luồng tối thiểu: `input → authentication → authorization → validation → domain decision → transaction/persistence → side effect → response`.

Chỉ rõ lock boundary, retry boundary, idempotency boundary và error mapping khi có. Pseudo-code phải nhất quán với source; không trình bày flow không tồn tại trong implementation.

### Trade-offs, maturity và failure

Mọi lab có ba trade-off card:

- **Bảo vệ gì:** invariant/guarantee đạt được.
- **Chi phí gì:** lock, latency, complexity, operational burden hoặc UX cost.
- **Giới hạn/bước tiếp theo:** claim chưa được chứng minh và evidence cần thêm.

Maturity tối thiểu gồm correctness, operational readiness và advanced evidence. Failure analysis phải xem stale UI/TOCTOU, retry/replay, tamper ownership, timezone/boundary, concurrency/deadlock/timeout và rate abuse.

## 4. CSS/visual contract

### Lab shell dùng chung

Shell chuẩn cung cấp sẵn:

- SEO metadata: `title`, `description`, canonical URL và Open Graph cơ bản.
- Header thống nhất với icon thương hiệu, dashboard link và logout.
- Reading progress bar ở đầu viewport.
- Nút back-to-top có aria label, chỉ xuất hiện sau khi cuộn.
- Skip link cho keyboard/screen reader và `prefers-reduced-motion` compatibility.
- Mini table of contents sticky theo bốn nhóm học tập, có active section bằng `IntersectionObserver`.
- Print stylesheet để xuất tài liệu mà không kéo theo form, navigation và control tương tác.
- SEO bổ sung Twitter metadata và JSON-LD `TechArticle`.

Mẫu sử dụng tối thiểu:

```blade
<x-labs.shell
    :title="__('domain.page.title')"
    :description="__('domain.page.description')"
>
    <main id="main-content" class="mx-auto max-w-7xl px-4 py-8">
        {{-- learning content + practice --}}
    </main>
</x-labs.shell>
```

Dùng `shell.lab.*` cho text của shell; không lặp lại `doctype`, `head`, header, progress bar hoặc back-to-top trong từng BL.

Phần learning dùng `resources/views/components/labs/learning-overview.blade.php` cho các lab cần khung nội dung chuẩn. Nội dung domain đặt trong `lang/en/lab_learning.php` và `lang/vi/lab_learning.php`; không hard-code nội dung lý thuyết trong component. `resources/views/components/labs/layer-map.blade.php` là block dùng chung để chỉ rõ mục đích, ví dụ code và trách nhiệm không thuộc về từng layer `Request → Controller/use case → Policy/domain → Database/test`. `resources/views/components/labs/production-learning.blade.php` cung cấp contract/data model, decision table, test evidence, performance, pitfalls, maturity và observability theo ba phase để giữ đúng thứ tự 18 section. BL-01 đến BL-06 đều phải dùng hai component này hoặc implementation tương đương có cùng contract.

Dùng component class chuẩn khi phù hợp:

- `.lab-page`, `.lab-card`, `.lab-card-copy`: canvas, card và text không tràn.
- `.lab-path`, `.lab-path-step`, `.lab-path-number`: stepper có line desktop/mobile.
- `.lab-theory-grid`, `.lab-theory-point`: concept board có đánh số.
- `.lab-layer-card`, `.lab-layer-index`: bản đồ mục đích từng layer, kèm code minh họa và anti-responsibility note.
- `.lab-code-block`: code/pseudo-code không làm vỡ layout.
- `.lab-tradeoff-card--safe`, `--cost`, `--limit`: guarantee/cost/limit.
- `.lab-review-card--accent`, `--risk`, `--dark`: maturity/risk/evidence.
- `.lab-mini-toc`, `[data-lab-toc-link]`: mục lục sticky và active section.

Quy tắc style:

- Không dùng `truncate` cho kiến thức; heading và paragraph phải đủ line-height.
- Grid dùng `minmax(0, 1fr)` để text dài không đẩy vỡ card.
- Mobile là layout chính sách, không phải desktop thu nhỏ.
- Dark panel chỉ dành cho invariant, code hoặc threat analysis.
- Mỗi trạng thái có text label; màu chỉ là tín hiệu phụ.
- Code/layer example phải ghi source location và test location; test evidence phải phân biệt verified, conceptual và not covered.
- Known limitations phải nói rõ claim nào chưa được chứng minh bằng load test, production telemetry hoặc external side-effect strategy.
- Giữ focus state, keyboard order, landmark và heading hierarchy khi thêm card.

## 5. Translation và accessibility contract

- EN/VI phải có cùng flattened key set.
- Không hard-code câu hiển thị trong Blade, trừ pseudo-code/framework syntax.
- Không chấp nhận raw translation key làm fallback.
- Với danh sách dùng delimiter, kiểm tra số item và tránh delimiter xuất hiện trong nội dung.
- Test route ở các locale quan trọng để phát hiện raw key.
- Dùng landmark semantic, đúng heading hierarchy, label/error liên kết với control và visible focus.
- Empty, loading, success, validation, conflict và forbidden state phải có cách đọc không phụ thuộc màu.

## 6. Acceptance checklist cho BL mới

- [ ] Đọc được toàn bộ flow mà chưa cần tương tác form.
- [ ] Có problem, scope, non-goal và learning objectives.
- [ ] Có theory, invariant/decision rule và code flow nhất quán với source.
- [ ] Có implementation, security và data boundary.
- [ ] Có architecture/layer map, data model contract và decision table.
- [ ] Có test matrix, performance budget, pitfalls, maturity và observability/runbook.
- [ ] Có trade-off theo cấu trúc bảo vệ/chi phí/giới hạn.
- [ ] Có maturity levels: tối thiểu, vận hành, nâng cao.
- [ ] Có threat/failure analysis và pitfalls.
- [ ] Practice nằm sau toàn bộ phần giải thích.
- [ ] EN/VI parity không thiếu key; không có raw key trên UI.
- [ ] Responsive, keyboard, focus, empty/error/success/conflict states được rà.
- [ ] View kế thừa lab shell chung, có SEO metadata, progress bar, skip link và back-to-top.
- [ ] Test evidence và giới hạn claim xuất hiện trong docs/UI phù hợp.
- [ ] Formatter, test, build và `git diff --check` đều pass.

## 7. Definition of reuse

BL mới có thể thay đổi accent hoặc component practice theo domain, nhưng không tự ý đổi thứ tự learning flow. Nếu cần đổi thứ tự, technical record phải ghi lý do vì sao thứ tự đó giúp hiểu rule tốt hơn. Mẫu tối thiểu phải có các section: objectives, theory, invariant, flow, implementation, code example, trade-off, threat/defense và review checklist trước practice.
