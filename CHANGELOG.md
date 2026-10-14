# Changelog

All notable changes to `oe-module-escrow` are documented in this file.

## 1.0.1 — 2026-10-14

### Fixed

- Patient menu item now includes `pid` and `on_click` required by `PatientMenuRole::displayHorizNavBarMenu()` (clears undefined-property warnings)
- Normalize sibling patient-menu entries missing `pid`/`on_click` when Escrow menu listener runs
- Prevent duplicate Escrow entries when menu events fire more than once

## 1.0.0 — 2026-10-14

### Added

- Initial OpenEMR 8.x custom module packaging for the IVF escrow report
- Patient and Reports menu registration via `Bootstrap`
- `EscrowService` using core `ar_session` / `ar_activity` through `QueryUtils`
- Twig report and refund views under `templates/escrow/`
- CSRF-protected refund POST flow (OpenEMR 8.2 session CSRF API)
- Symlink-safe OpenEMR root resolution (`ModulePath`)
- Module Manager listener, `table.sql` placeholder, `version.php` 1.0.0
- Unit smoke test for `EscrowService` defaults

### Notes

- No module-owned database tables in this release
- Multi-site support is via per-site database + session context only
