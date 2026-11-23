# Submission protection

## Coverage

Both PDIMS (Laravel 12 / Livewire 3) and IHoMIS Pharmacy (Laravel 8 / Livewire 2) now register `ProtectSubmissions` for their web and API middleware groups. The protection covers create, update, delete, and other unsafe HTTP requests through these groups. Read requests, temporary Livewire uploads, and credential/session lifecycle endpoints are excluded. Direct SQL, console commands, and external applications are not automatically covered by HTTP middleware.

Livewire uses its signed snapshot plus action identity as the submission key. Every response rotates a signed nonce, including actions with no visible state changes. Repeated calls bundled in a single request execute once; return values are expanded/remapped to satisfy each client promise. Changed property data under an already-submitted snapshot conflicts instead of executing again.

Normal forms receive `_submission_key`; same-origin fetch and Axios writes receive `Idempotency-Key`. Buttons lock while submission actions are pending. Existing disabled states remain authoritative after server DOM updates. The client script is included by HTML response middleware so legacy layouts receive the same coverage without modifying Blade loops.

| Previously identified gap | Resolution |
| --- | --- |
| Delivery creation | Request receipts replay the original creation response. |
| Add/edit delivery items | Request receipts plus existing delivery row/status locks; inventory transactions coordinate with other pharmacy writers. |
| Stock create/update/pull-out/adjustment | Request receipts, inventory transactions, fresh stock locks, positive/available pull-out checks; identical absolute adjustments do not create another audit record. |
| IO issue/receive/cancel/return | Request receipts, shared inventory coordination, fresh transaction status checks; repeated completed receipt is a no-op. |
| Regular dispensing | Request receipts, locked fresh order checks, cumulative stock preflight, and transactional stock/log changes. Raw hospital queries and issuance logging use the hospital connection. |
| Ward RIS | Request receipts and inventory transactions; completed cancellations return before writing again. |
| UDDDS standard/fallback issuance | Shared inventory transaction and cumulative stock preflight, preserving existing schedule checks and fallback row locks. |
| Queue creation | A prescription's existing non-cancelled queue for the same location and day is returned. Creation locks its location/prefix sequence pool on SQL Server. |
| Queue status updates | Fresh row lock, same-status no-op, validation of known statuses, terminal-state guard; legacy API endpoint uses the same service. |
| Satellite logging/order jobs | Persisted job operation IDs, dispatch after commit, atomic job receipts and writes; successful retries do not log or increment again. |
| Other business forms/API writes | Shared HTTP receipts cover the remaining create/update entry points, including admin and portal management components. |

The shared SQL Server application lock serializes the coordinated legacy pharmacy mutations across both applications. This deliberately trades some write throughput for consistency while legacy stock summaries do not all have natural unique keys. Stock reservations additionally lock matching rows. The lock requires the applications to use the same hospital database to coordinate.

## Deployment

Apply only the new additive migration through the normal deployment process, before enabling the new application code:

```powershell
php artisan migrate --path=database/migrations/2026_10_29_000001_create_submission_receipts.php
```

It creates `pharm_submission_receipts` on the **hospital** connection. If both applications use the same database, the second migration detects the existing table. If they use different databases, migrate each. No existing clinical or inventory table is altered. The rollback intentionally retains the receipt table, because deleting safety records would allow old submissions to execute again. This change set does not run migrations against live databases.

Deploy `public/js/submissions.js` in both applications. Restart long-running satellite queue workers using the normal deployment procedure so they load the new handlers. Keep each application's encryption key stable; historical response bodies are encrypted using that key.

External API callers must generate a random UUID per intended operation and send it as `Idempotency-Key`. Retain the same key and payload for network retries; use a new key for a new intentional operation. Writes with no valid key return HTTP 428. Reuse with different data returns HTTP 409. A completed replay returns the original body/status and `Idempotency-Replayed: true`. Authentication/credential lifecycle requests are exempt and authentication responses are never recorded.

## Failure and recovery

Receipts reserve a submission before invoking the handler. A completed response is encrypted and retained for replay. A concurrent request receives 409 while the reservation is pending. Exceptions retain an uncertain receipt: a process might have already written through another database or sent an external effect. Do not automatically delete an uncertain receipt or generate a new key to retry it; reconcile the affected business records first. This is at-most-once execution for the same operation identity, not a distributed transaction across hospital, webapp, portal, queues, and external services.

Inventory jobs put their receipt and hospital writes in the same transaction. Failure rolls back both, so a normal queue retry can execute safely. Jobs queued before this deployment use the queue payload UUID as their stable fallback identity. An old job without any identity fails rather than performing an unsafe replay.

Do not automatically purge receipts: deleting them permits historical submissions to execute again. If retention is added later, retain a rejection tombstone for expired keys. Response receipts contain encrypted medical/business output and need the same access controls as the application databases. Session cookies and authentication headers are not stored or replayed.

## Verification

- `php vendor/bin/pest tests/Unit/SubmissionProtectionTest.php tests/Unit/SubmissionDomainTest.php --compact`
- `node --test tests/Unit/submissions-client.test.cjs`
- `php ihomis_pharmacy/tests/Standalone/SubmissionProtectionTest.php` (all database connections redirected to in-memory SQLite before framework boot)
- Existing `tests/Isolated/delivery_reversal.php` and `tests/Standalone/UdddsRecurrenceTest.php`, in each application.

SQL Server application-lock concurrency is not exercised by the SQLite tests. A staging test with parallel requests against SQL Server remains part of deployment validation. The broader pre-existing unit tests currently fail on drug-equivalence fixture schemas missing `drug.dmdrem` and related drug-description columns; these files are outside this change.

## Synchronization inventory

PDIMS changes:

- `.gitignore` (keep the new regression tests in version control).
- `bootstrap/app.php` and `app/Providers/AppServiceProvider.php` (HTTP middleware and Livewire 3 nonce registration).
- `app/Http/Middleware/ProtectSubmissions.php` and `SubmissionScripts.php`.
- `app/Http/Controllers/Api/QueueController.php`.
- `app/Support/SubmissionNonce.php`.
- `app/Services/SubmissionLedger.php`.
- `app/Services/Pharmacy/InventoryMutation.php`, `InventoryJob.php`, `PrescriptionQueueService.php`, and `UdddsService.php`.
- `app/Livewire/Pharmacy/Purchases/DeliveryView.php`.
- `app/Livewire/Pharmacy/Dispensing/DispensingEncounter.php`.
- `app/Livewire/Pharmacy/Drugs/StockList.php`, `IoTransactions.php`, `ViewIoTransRef.php`, `ViewIoTransDate.php`, `WardRisTrans.php`, `ViewWardRisRef.php`, `ViewWardRisDate.php`, and `Concerns/HandlesIoTransactionReturns.php`.
- `database/migrations/2026_10_29_000001_create_submission_receipts.php`.
- `public/js/submissions.js`.
- The two PHP regression files and the JavaScript test listed above; this document.

Satellite equivalents are adapted under `app/Http/Livewire`, with `app/Http/Kernel.php` registration and a Livewire 2 hydration-middleware nonce. Shared services, HTTP middleware, the migration, and the browser script are synchronized. The satellite regression test verifies the actual Laravel 8 / Livewire 2 runtime.

Satellite-only execution surfaces: `AddDrugOrder`, `LogDrugDelivery`, `LogDrugOrderIssue`, `LogDrugStockIssue`, `LogDrugTransaction`, `LogIoTransIssue`, and `LogIoTransReceive` under `ihomis_pharmacy/app/Jobs`. PDIMS performs their corresponding logging synchronously inside its inventory mutations and does not have corresponding queue job classes. Its prescription-queue service and legacy queue API have no satellite equivalent; its IO-return trait also has no matching satellite return surface.
