# Git Workflow — feature branch và main

> Quy ước nhánh gọn cho repo học tập: mỗi thay đổi được làm trên nhánh riêng, review/kiểm tra trước khi hợp nhất vào `main`. Không cần nhánh `develop` trong giai đoạn repo nhỏ.

## 1. Mục tiêu

- Giữ `main` ở trạng thái có thể đọc, demo và clone được.
- Mỗi feature/lab hoặc thay đổi có một nhánh ngắn hạn, phạm vi rõ.
- Hợp nhất có review và bằng chứng phù hợp; tránh sửa trực tiếp `main`.
- Lịch sử dễ hiểu, dễ quay lại nếu thay đổi gây lỗi.

## 2. Vai trò của các nhánh

### `main`

- Nhánh chuẩn và ổn định; đây là phiên bản mặc định của repo.
- Chỉ nhận thay đổi đã qua Pull Request (PR) hoặc quy trình review tương đương.
- Không commit/push trực tiếp, kể cả thay đổi nhỏ, trừ khôi phục khẩn cấp khi PR workflow không khả dụng; trường hợp đó phải tạo PR ghi nhận lại ngay sau.
- Luôn giữ README, tài liệu và liên kết nhất quán sau merge.

### `feature/*`

Dùng cho mọi công việc có phạm vi cụ thể: thêm lab, bổ sung phân tích, UI, docs, sửa nội dung hoặc cải thiện test.

Quy ước tên:

```text
feature/<scope>-<short-description>
```

Ví dụ:

```text
feature/bl-00-feature-toggle
feature/bl-01-ticket-state
feature/ui-lab-catalog
feature/docs-git-workflow
```

Một nhánh nên làm một mục tiêu review được. Nếu thay đổi độc lập và có thể merge riêng, tách nhánh; nếu các phần phụ thuộc chặt để lab hoàn chỉnh, giữ cùng nhánh và ghi rõ trong PR.

Không tạo nhánh `develop`, `release/*` hoặc `hotfix/*` khi chưa có nhu cầu deploy/release nhiều phiên bản. Tái đánh giá khi dự án có nhiều người phát triển hoặc lịch release riêng.

## 3. Luồng làm việc chuẩn

```text
main mới nhất
  → tạo feature branch
  → làm thay đổi nhỏ, commit
  → push branch và mở PR vào main
  → review + kiểm tra
  → squash merge
  → xóa branch đã merge
  → cập nhật local main
```

### Bước 1: cập nhật `main` và tạo nhánh

```bash
git switch main
git pull --ff-only origin main
git switch -c feature/bl-01-ticket-state
```

Tạo branch sau khi biết rõ phạm vi. Không bắt đầu từ một `main` cũ nếu có thể tránh.

### Bước 2: commit thay đổi theo mục tiêu

```bash
git status --short
git add docs/labs/01-state-transition.md
git commit -m "docs(bl-01): clarify transition rules"
```

Commit message nên nói hành động và phạm vi. Có thể dùng tiền tố `feat`, `fix`, `docs`, `refactor`, `test`, `chore`; đây là quy ước đọc lịch sử, không bắt buộc tuân chuẩn Conventional Commits đầy đủ.

Không gom secret, file build/cache, dữ liệu production hoặc thay đổi ngoài phạm vi vào commit. Trước mỗi commit, đọc `git diff --staged`.

### Bước 3: push và mở PR

```bash
git push -u origin feature/bl-01-ticket-state
```

PR target là `main`. Mô tả tối thiểu:

- **Mục tiêu:** vấn đề/lab nào được giải quyết?
- **Thay đổi:** file/logic/schema/UI nào đổi?
- **Rule/decision:** business rule hoặc trade-off nào được chốt?
- **Kiểm tra:** command và kết quả thực tế; ghi rõ nếu chưa chạy.
- **Rủi ro/giới hạn:** migration, compatibility, security hoặc phần chưa xác minh.
- **Ảnh/flow demo:** chỉ khi UI thay đổi.

### Bước 4: review và kiểm tra

Trước merge:

- PR chỉ chứa phạm vi đã mô tả; không có file ngoài ý muốn.
- Markdown links, heading, thuật ngữ và index/README đã cập nhật.
- Nếu có code: lint/static analysis/test/build phù hợp đã chạy; concurrency/DB claims dùng engine phù hợp.
- Migration có constraint/rollback/rollout reasoning; dữ liệu cũ được xem xét.
- Không có secrets, credentials, personal data hoặc logs thật.
- Người review (hoặc self-review khi làm một mình) đọc diff từ đầu đến cuối và kiểm tra acceptance criteria.

Không ghi “tests pass” nếu chưa chạy. Với docs-only thay đổi, review links/nội dung và cấu trúc thường là đủ; không cần dựng app chỉ để hợp thức hóa PR.

### Bước 5: squash merge

Mặc định dùng **Squash and merge** để mỗi feature thành một commit gọn trên `main`, nhất là khi branch có nhiều commit sửa nhỏ. Dùng commit message mô tả feature/lab, ví dụ:

```text
feat(bl-01): document ticket state transitions
```

Dùng merge commit khi cần giữ đầy đủ chuỗi commit như một đơn vị lịch sử; dùng rebase merge khi repo thống nhất giữ từng commit tuyến tính. Chọn một phương án mặc định và không đổi tùy hứng giữa các PR. Repo này mặc định squash.

Sau merge, xóa remote branch. Nhánh local có thể xóa khi chắc chắn đã merge:

```bash
git switch main
git pull --ff-only origin main
git branch -d feature/bl-01-ticket-state
git fetch --prune
```

## 4. Đồng bộ nhánh feature khi `main` thay đổi

Trước PR, nếu branch đã tụt sau `main`:

```bash
git fetch origin
git switch feature/bl-01-ticket-state
git rebase origin/main
```

Nếu branch chỉ do bạn sử dụng, push lại bằng:

```bash
git push --force-with-lease
```

Không dùng `--force` mù quáng. Nếu branch có người khác cùng dùng hoặc đã tích hợp vào nhánh khác, tránh rewrite history; phối hợp với cộng tác viên và dùng merge `origin/main` vào feature branch khi cần.

Rebase/merge cập nhật code; nó không thay thế việc chạy lại kiểm tra sau khi conflict được giải quyết.

## 5. Xử lý merge conflict

Khi rebase báo conflict:

```bash
git status
git diff --name-only --diff-filter=U
```

1. Đọc cả hai thay đổi và hiểu mục đích, không chọn “ours/theirs” hàng loạt.
2. Hợp nhất nội dung; giữ rule có giá trị từ cả hai hoặc hỏi reviewer nếu mâu thuẫn về nghiệp vụ.
3. Kiểm tra file sau khi sửa, tìm marker `<<<<<<<`, `=======`, `>>>>>>>`.
4. Stage file đã giải quyết rồi tiếp tục:

```bash
git add <resolved-files>
git rebase --continue
```

5. Nếu chưa hiểu cách hợp nhất hoặc thấy mất dữ liệu, dừng an toàn:

```bash
git rebase --abort
```

6. Sau rebase, đọc toàn bộ diff so với `origin/main`, chạy lại kiểm tra và push với `--force-with-lease` chỉ khi branch là của riêng bạn.

Conflict trong cùng một business rule không chỉ là conflict văn bản. So sánh rule IDs, assumptions, schema/API contract và test trước khi chọn nội dung.

## 6. Bảo vệ `main`

Nếu host Git hỗ trợ branch protection/rulesets, cấu hình:

- Require Pull Request trước khi merge.
- Require CI checks phù hợp với repo; tránh bật check chưa có hoặc không ổn định.
- Block force push và branch deletion cho `main`.
- Require conversation resolution.
- Một approval là phù hợp khi có reviewer; với repo cá nhân có thể self-review thay vì tạo approval giả.
- Không bắt buộc signed commits, linear history hoặc nhiều approval nếu chúng không giải quyết rủi ro thật.

Nếu repo không có CI, PR vẫn phải có checklist review; chưa cần thêm pipeline phức tạp chỉ để có tick xanh.

## 7. Merge sai hoặc cần hoàn tác

- Nếu merge commit chưa được chia sẻ và chưa push, có thể sửa lịch sử cẩn trọng.
- Nếu đã vào `main`/đã được người khác kéo, ưu tiên tạo revert commit/PR thay vì reset/force push.
- Với squash merge, revert commit squash bằng PR/commit mới; xác nhận dữ liệu migration và side effects có thể đảo ngược hay cần forward-fix.
- Revert code không tự hoàn tác thay đổi dữ liệu production. Migration/destructive operations cần kế hoạch riêng.

## 8. PR checklist

```text
[ ] PR target là main; branch bắt đầu từ main mới nhất
[ ] Tên branch phản ánh scope; thay đổi không trộn mục tiêu độc lập
[ ] Diff không có file/secret/dữ liệu ngoài ý muốn
[ ] Rule, docs, index và README đồng bộ
[ ] Test/check được ghi trung thực (command + kết quả hoặc chưa chạy)
[ ] Migration/compatibility/security/failure modes được review nếu có
[ ] Conflict đã giải quyết theo nghiệp vụ, không chỉ theo text
[ ] Chọn squash merge; branch xóa sau merge
```

## 9. Quyết định có thể xem lại

Quy trình này cố ý đơn giản cho repo học tập và portfolio cá nhân. Chuyển sang GitFlow, release branches, code owners hoặc nhiều approval khi có release cadence, nhiều contributor, compliance hoặc production deployment yêu cầu. Chưa cần áp dụng trước khi có vấn đề thực tế.
