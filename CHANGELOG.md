# Changelog

## 2026-08-27

### Improvements

- UDDDS is optional and only available on inpatient (ADM) encounters. Add Item no longer requires unit-dose dates. OPD, ER, and other encounter types do not show UDDDS.

### Errors and fixes

- Issuing ward items no longer fails with `udddsStart is not defined`.

## 2026-08-26

### Improvements

- UDDDS (Wards) lists today's Basic (standing) unit-dose orders. Pharmacy can Ready to Bill a patient, selected items, or a whole ward; that charges, issues, and prints the charge slips together.
- Issuing a Basic ward item that already has UDDDS start and end dates (set when the item was added) turns UDDDS on so later daily unit-dose rows can be generated. A scheduled `uddds:generate-daily` command creates those pending orders at 7:00 AM Asia/Manila.
- Adding a stock item on an encounter now includes UDDDS order type and start/end dates on the same popup, instead of only asking at Issue.
- Issued ward items show a UDDDS switch. Turning it on later asks for order type and start/end dates again; turning it off stops future daily unit-dose orders.
- Encounter item rows have a UDDDS column next to the checkbox.
- When an item is tied to a prescription, issue remarks fill with quantity, frequency, and days from that Rx (for example `6 Every 8 hours for 2 days`). Enabling UDDDS still shows the drug name in the popup, but does not write it into remarks. Typed remarks are kept when adding an item.
- Rx/Orders includes a UDDDS tab next to Wards, OPD, and ER.

### Errors and fixes

- UDDDS (Wards) no longer crashes when `hrxo` is missing unit-dose columns. A migration adds those columns; until it is applied the page explains that UDDDS is not ready.

## 2026-07-28

### Improvements

- Enhanced User Management with collapsible combined filters, sortable columns, configurable page sizes, and clearer account details.
- Added a consolidated access editor for changing a user's role and pharmacy location.

### Errors and fixes

- Protected administrators from deactivating their own account or modifying protected Super Admin accounts.
- Corrected user searching so name, email, and employee ID conditions remain properly grouped with status, role, and location filters.
- Fixed the Filters accordion so its toggle remains visible and expands correctly with the application's DaisyUI version.
