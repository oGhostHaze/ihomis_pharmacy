# Changelog

## 2026-08-26

### Improvements

- UDDDS (Wards) lists today's Basic (standing) unit-dose orders. Pharmacy can Ready to Bill a patient, selected items, or a whole ward; that charges, issues, and prints the charge slips together.
- Issuing Basic ward items asks for a UDDDS start and end date so later daily unit-dose rows can be generated. A scheduled `uddds:generate-daily` command creates those pending orders at 7:00 AM Asia/Manila.
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
