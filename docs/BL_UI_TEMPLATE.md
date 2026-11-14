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

## 2. Thứ tự section chuẩn

| Thứ tự | Section | Câu hỏi người học cần trả lời | Kiểu UI |
|---|---|---|---|
| 1 | Hero / context | Bài toán là gì, phạm vi tới đâu? | Heading + problem statement |
| 2 | Reading path | Nên đọc theo trình tự nào? | Stepper có đường nối |
| 3 | Learning objectives | Học xong chứng minh được gì? | 3–4 objective cards |
| 4 | Theory / mental model | Domain được mô hình hóa ra sao? | Concept board |
| 5 | Invariant / decision rule | Điều kiện đúng/sai chính xác là gì? | Dark code/predicate panel |
| 6 | Code flow | Request đi qua những boundary nào? | Timeline/numbered flow |
| 7 | Implementation | Framework, schema, transaction và error mapping ra sao? | Code + implementation card |
| 8 | Trade-offs / limits | Thiết kế bảo vệ gì, tốn gì, chưa chứng minh gì? | Safe/cost/limit cards |
| 9 | Maturity levels | Tối thiểu, vận hành tốt và nâng cao khác nhau thế nào? | 3-level comparison |
| 10 | Threat / failure analysis | Request cạnh tranh hoặc không đáng tin thì sao? | Attack/defense panel |
| 11 | Pitfalls | Cách làm nào trông đúng nhưng nguy hiểm? | Warning cards |
| 12 | Review checklist | Điều kiện nào cần đạt trước khi gọi là hoàn thiện? | Checklist |
| 13 | Practice | User có thể chạy thử điều gì? | Divider + form/result/error UI |

Không bỏ qua section 4–8 chỉ vì lab có form đơn giản. Nếu một mục không áp dụng, phải ghi rõ `Không áp dụng` và lý do.

## 3. Content contract

### Hero và reading path

- Hero có mã BL, tên logic, độ khó, câu hỏi nghiệp vụ, scope và non-goal.
- Reading path tối đa 5 bước, có đường nối ngang trên desktop và dọc trên mobile.
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

Dùng component class chuẩn khi phù hợp:

- `.lab-page`, `.lab-card`, `.lab-card-copy`: canvas, card và text không tràn.
- `.lab-path`, `.lab-path-step`, `.lab-path-number`: stepper có line desktop/mobile.
- `.lab-theory-grid`, `.lab-theory-point`: concept board có đánh số.
- `.lab-code-block`: code/pseudo-code không làm vỡ layout.
- `.lab-tradeoff-card--safe`, `--cost`, `--limit`: guarantee/cost/limit.
- `.lab-review-card--accent`, `--risk`, `--dark`: maturity/risk/evidence.

Quy tắc style:

- Không dùng `truncate` cho kiến thức; heading và paragraph phải đủ line-height.
- Grid dùng `minmax(0, 1fr)` để text dài không đẩy vỡ card.
- Mobile là layout chính sách, không phải desktop thu nhỏ.
- Dark panel chỉ dành cho invariant, code hoặc threat analysis.
- Mỗi trạng thái có text label; màu chỉ là tín hiệu phụ.
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
- [ ] Có trade-off theo cấu trúc bảo vệ/chi phí/giới hạn.
- [ ] Có maturity levels: tối thiểu, vận hành, nâng cao.
- [ ] Có threat/failure analysis và pitfalls.
- [ ] Practice nằm sau toàn bộ phần giải thích.
- [ ] EN/VI parity không thiếu key; không có raw key trên UI.
- [ ] Responsive, keyboard, focus, empty/error/success/conflict states được rà.
- [ ] Test evidence và giới hạn claim xuất hiện trong docs/UI phù hợp.
- [ ] Formatter, test, build và `git diff --check` đều pass.

## 7. Definition of reuse

BL mới có thể thay đổi accent hoặc component practice theo domain, nhưng không tự ý đổi thứ tự learning flow. Nếu cần đổi thứ tự, technical record phải ghi lý do vì sao thứ tự đó giúp hiểu rule tốt hơn.
