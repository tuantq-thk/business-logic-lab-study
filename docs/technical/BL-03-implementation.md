# BL-03 Technical Implementation Record

> Cập nhật: 2026-11-14
> Phạm vi: `laravel/`
> Trạng thái: implemented locally; SQLite và isolated MySQL functional evidence đã pass; concurrency evidence đang pending.

## 1. Mục tiêu và boundary

BL-03 chứng minh cách giữ capacity đúng khi nhiều request cùng reserve một SKU, đồng thời làm rõ lifecycle của reservation và việc thay đổi tồn kho có lịch sử.

MVP có:

- một inventory item/SKU với `on_hand` và `reserved_quantity`;
- reserve với `order_ref`, quantity và TTL;
- consume, release và expire reservation;
- admin stock adjustment có reason và movement ledger;
- idempotent retry theo `(owner, order_ref, inventory_item)`;
- UI demo server-rendered và artisan expiry/reconciliation flow;
- SQLite functional tests và MySQL concurrency/constraint evidence.

MVP không có order/payment provider, multi-warehouse, backorder, partial fulfillment, external webhook hay distributed reservation service.

## 2. Quyết định kiến trúc

### 2.1 Counter là projection nóng, reservation là lifecycle source

`inventory_items.reserved_quantity` được dùng để quyết định nhanh:

```text
available = on_hand - reserved_quantity
```

Reservation giữ identity, owner, quantity, expiry và trạng thái. `reserved_quantity` là counter được bảo vệ bởi row lock và transaction; không tính `SUM(active reservations)` trên đường reserve/consume/release.

Một reconciliation query sẽ so sánh counter với `SUM(quantity WHERE status = active)` để phát hiện drift. Reconciliation không tự sửa dữ liệu trong MVP.

### 2.2 Lock protocol

Mọi mutation của reservation đều tuân thủ thứ tự:

```text
transaction
  → lock inventory_items theo primary key
  → lock reservation theo primary key nếu đã tồn tại
  → kiểm tra trạng thái/expiry/quantity
  → update counter + reservation + movement nếu cần
  → commit
```

Reserve không tồn tại reservation thì chỉ cần lock item; unique business key vẫn là lớp bảo vệ cuối nếu có entry point khác bypass protocol. Nếu sau này một request thao tác nhiều SKU, phải sort item IDs tăng dần trước khi lock.

Laravel `DB::transaction(..., attempts: 3)` được dùng cho deadlock retry. Retry chỉ bao quanh toàn bộ transaction, không retry riêng một statement sau khi transaction đã bị rollback.

## 3. Data contract

### `inventory_items`

| Field | Contract |
|---|---|
| `sku` | unique, stable, normalized uppercase input |
| `name_key` | translation key, không lưu presentation text phụ thuộc locale |
| `on_hand` | unsigned BIGINT; tổng tồn vật lý được hệ thống biết |
| `reserved_quantity` | unsigned BIGINT; không vượt `on_hand` |
| `version` | unsigned integer tăng sau mutation |

### `inventory_reservations`

| Field | Contract |
|---|---|
| `inventory_item_id` | FK tới item |
| `owner_id` | FK user tạo reservation; dùng resource scope |
| `order_ref` | normalized bounded reference |
| `quantity` | unsigned BIGINT > 0 |
| `status` | `active`, `consumed`, `released`, `expired` |
| `expires_at` | UTC timestamp; active chỉ hợp lệ khi `expires_at > now` |
| timestamps | trạng thái cuối giữ `consumed_at` hoặc `released_at` |

Unique key là `(owner_id, order_ref, inventory_item_id)`. Retry cùng key và cùng quantity trả lại reservation hiện tại; khác quantity trả conflict. Reservation terminal không được tạo lại bằng cùng business key.

### `inventory_movements`

Ledger chỉ ghi thay đổi `on_hand`, không ghi reserve/release vì hai thao tác đó chỉ thay đổi capacity tạm thời.

- `delta`: signed BIGINT, khác 0;
- `reason`: bắt buộc với manual adjustment, giới hạn kích thước;
- `reference`: nullable bounded business reference;
- `actor_id`: nullable cho system operation, restrict/null policy phải được test;
- index `(inventory_item_id, created_at, id)`.

## 4. Use cases và invariants

### ReserveStock

1. Normalize `sku/order_ref`, validate positive quantity và TTL bounds.
2. Mở transaction retryable.
3. Lock item bằng primary key.
4. Tìm reservation theo unique business key.
5. Nếu đã có: kiểm tra quantity; trả existing hoặc conflict.
6. Kiểm tra `on_hand - reserved_quantity >= quantity`.
7. Tăng counter, version và insert active reservation.
8. Commit; không gọi network/provider trong transaction.

### ConsumeReservation

1. Authorize owner/admin.
2. Lock item trước, sau đó lock reservation.
3. Nếu không active: trả idempotent result cho trạng thái terminal hoặc conflict theo API contract.
4. Nếu `expires_at <= now`: chuyển expired và release counter; không consume.
5. Với reservation active: giảm `reserved_quantity`, giảm `on_hand`, tăng version, ghi movement âm và chuyển consumed.

### Release/Expire

Lock theo cùng protocol. Chỉ active mới giảm counter. Release do owner/admin; expiry do trusted command/job. Repeated release/expire không giảm counter lần hai.

### AdjustStock

Chỉ admin. Lock item, validate reason và delta, bảo đảm `on_hand + delta >= reserved_quantity`, cập nhật `on_hand`/version và ghi movement. Không được ghi đè số dư hoặc làm available âm để “sửa nhanh”.

## 5. Database, index và performance contract

- Tất cả bảng dùng InnoDB khi chạy MySQL.
- `inventory_items.sku` unique để lookup catalog không scan.
- Reservation expiry dùng composite index `(status, expires_at, id)`; worker xử lý bounded batch theo thứ tự ổn định.
- Business idempotency dùng unique `(owner_id, order_ref, inventory_item_id)`.
- Counter update nằm trên một row item, nên hot SKU sẽ serialize có chủ ý; đây là trade-off rõ ràng để giữ invariant.
- Không thêm index cho mọi cột. Mỗi index phải gắn với query route/worker/reconciliation và được kiểm tra bằng `EXPLAIN`/`EXPLAIN ANALYZE` trên MySQL.
- UI list phải scope theo owner trước pagination; tránh N+1 bằng eager load/aggregate có chủ đích.

MySQL `CHECK` constraints sẽ bảo vệ các invariant cục bộ như `reserved_quantity <= on_hand`, `quantity > 0` và `delta <> 0`; application/action vẫn kiểm tra để error mapping rõ trên SQLite.

## 6. Error and security contract

- `INSUFFICIENT_STOCK`: available không đủ; không leak dữ liệu của owner khác.
- `RESERVATION_KEY_CONFLICT`: cùng business key nhưng quantity khác.
- `RESERVATION_EXPIRED`: không thể consume reservation hết hạn.
- `RESERVATION_TERMINAL`: lifecycle đã kết thúc; retry không mutate lần hai.
- `STOCK_ADJUSTMENT_FORBIDDEN`: non-admin không được điều chỉnh.
- `STALE_OR_CONCURRENT_CONFLICT`: conflict sau lock/unique/deadlock policy nếu client cần retry.

Không tin `on_hand`, `reserved_quantity`, status hoặc actor từ request. UI hiding không phải authorization. Resource query phải scope owner trước khi render hoặc mutate. Logs không ghi secret; `order_ref` và actor identifiers chỉ log khi cần correlation/operation audit.

## 7. Verification plan

### Functional

- reserve vừa đủ/vượt tồn;
- same-key retry cùng quantity không tạo row/counter thứ hai;
- same-key khác quantity bị reject;
- consume/release/expire transition matrix;
- repeated terminal operation không đổi counter;
- adjustment reason, authorization và không làm available âm;
- transaction rollback khi child insert/movement insert fail.

### MySQL-specific

- hai connection cùng tranh item cuối: đúng một reserve thành công;
- expiry và consume race: đúng một terminal outcome;
- deadlock retry thực thi lại toàn transaction nếu tạo được deadlock có chủ ý;
- CHECK, unique/FK và index tồn tại đúng;
- `EXPLAIN` cho reserve lookup, owner list, expiry worker và reconciliation.

### Performance/operations

- đo query count và cumulative query duration của các route chính;
- bounded expiry batch, không giữ transaction qua batch/network;
- ghi lock wait/deadlock outcome và correlation id ở mức an toàn;
- reconciliation report counter vs active-sum, không silent repair.

## 8. References

- [Laravel 13 database transactions and deadlock attempts](https://laravel.com/framework/docs/13.x/database#handling-deadlocks)
- [Laravel 13 query builder locking](https://laravel.com/framework/docs/13.x/queries#pessimistic-locking)
- [MySQL 8.4 locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html)
- [MySQL 8.4 transaction isolation](https://dev.mysql.com/doc/refman/8.4/en/innodb-transaction-isolation-levels.html)
- [MySQL 8.4 deadlocks](https://dev.mysql.com/doc/refman/8.4/en/innodb-deadlocks.html)
- [MySQL 8.4 CHECK constraints](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html)
- [MySQL 8.4 indexes and EXPLAIN](https://dev.mysql.com/doc/refman/8.4/en/mysql-indexes.html)

## 9. Open decisions intentionally deferred

- multi-item reservation atomicity;
- auto-retry policy exposed to HTTP clients;
- movement retention/archive;
- physical stock count workflow;
- reservation notifications and outbox integration.

## 10. Current implementation map

- `app/Features/InventoryCapacity/Models`: item, reservation và append-only movement.
- `app/Features/InventoryCapacity/Actions`: reserve, consume, release, expiry và adjustment.
- `app/Features/InventoryCapacity/Policies`: owner/admin boundary.
- `database/migrations/2026_11_14_090000..090004`: schema, indexes, reconciliation covering index và MySQL checks.
- `app/Console/Commands/ExpireInventoryReservations.php`: bounded expiry worker.
- `app/Console/Commands/ReconcileInventory.php`: report counter drift, không silent repair.
- `resources/views/labs/inventory-capacity/index.blade.php`: demo reserve/lifecycle/admin adjustment.
- `tests/Feature/InventoryCapacity/InventoryCapacityTest.php`: translation-key resolution, functional lifecycle, idempotency, authorization và route flow.
- Inventory mutation routes dùng named rate limiter theo authenticated user/IP; reservation forms có namespaced input, labels và duplicate-submit guard.

## 11. Evidence hiện tại và giới hạn

| Check | Kết quả |
|---|---|
| Pest functional suite | 72 tests, 387 assertions passed trên SQLite |
| Pest MySQL profile | 72 tests, 387 assertions passed trên MySQL 9.5.0 (`laravel_test`) |
| Pint | Passed |
| Vite production build | Passed; optional `fontaine` warning |
| `git diff --check` | Passed |
| MySQL migrations/check constraints | Passed trên MySQL 9.5.0; InnoDB, FK và CHECK đã xác nhận bằng `SHOW CREATE TABLE` |
| MySQL two-connection concurrency harness | Chưa chạy |
| MySQL `EXPLAIN`/`EXPLAIN ANALYZE` | `EXPLAIN` reserve/expiry/reconciliation đã chạy; reconciliation dùng covering index mới |
| Browser visual/accessibility QA | Chưa chạy |

`.env` hiện trỏ tới `127.0.0.1:3306`; MySQL đã kết nối được trong lần verification này. Vì chưa có two-connection race harness nên chưa claim concurrency outcome, deadlock retry thực tế hoặc isolation behavior. SQLite tests vẫn là functional suite chính; MySQL verification hiện bao phủ migration, constraint, reconciliation và query plan.
