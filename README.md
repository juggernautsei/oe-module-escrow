# oe-module-escrow

OpenEMR custom module for patient **escrow pre-payment** reporting and refunds.

Migrated from the IVF client report at `interface/reports/escrow`. Built for **OpenEMR 8.x** multi-site deployments.

| | |
|---|---|
| Package | `juggernautsei/oe-module-escrow` |
| Namespace | `OpenEMR\Modules\Escrow\` |
| Module version | 1.0.1 (`version.php`) |
| License | GPL-3.0-only |
| PHP | >= 8.1 |

## Features

- Patient chart menu item: **Escrow**
- Main menu item under **Reports**
- Lists `ar_session` pre-payments (`adjustment_code = pre_payment`)
- Shows encounter allocations from `ar_activity` (`account_code = PP`)
- Records balance refunds (`adjustment_code = refund_balance`) with CSRF-protected form
- Twig UI (`templates/escrow/`) with print-friendly report layout

## Requirements

- OpenEMR **8.x** (developed and verified on 8.2)
- PHP >= 8.1
- ACL access:
  - Report: `acct/rep` **or** `patients/demo`
  - Refund: `acct/bill` **or** `acct/rep`
- Active patient in session (`pid`) for the report

## Install

### 1. Place the module

**Recommended (dev):** symlink from this repository so one checkout serves all installs:

```bash
ln -s "/path/to/oe-module-escrow" \
  "{openemr}/interface/modules/custom_modules/oe-module-escrow"
```

**Production:** copy or deploy the module directory to the same path. Folder name must remain `oe-module-escrow`.

Symlink installs are supported via `OpenEMR\Modules\Escrow\ModulePath` (do not rely on `dirname(__DIR__, N)` alone).

### 2. Register in Module Manager

1. Log in as an administrator
2. **Modules → Manage Modules**
3. **Unregistered** tab → **Register** on *Escrow Report*
4. **Install**
5. **Enable**
6. Log out and log back in so menu event listeners reload

No module-owned tables are created. `table.sql` is intentionally empty of `CREATE TABLE` statements; core AR tables are used.

### 3. Open the report

With a patient selected:

```text
{webroot}/interface/modules/custom_modules/oe-module-escrow/public/index.php
```

Or use the **Escrow** item on the patient horizontal menu.

Refund dialog:

```text
{webroot}/interface/modules/custom_modules/oe-module-escrow/public/refund.php
```

## Multi-site

- Install the module **once** per OpenEMR application tree under `custom_modules/`
- Data is always site-scoped through the active site database and session (`site_id`, `pid`)
- Do **not** add a `site_id` column to escrow rows; `ar_session` / `ar_activity` already live in the site DB
- Each site enables/registers modules in its own `modules` table as needed

## Data model

Uses core OpenEMR AR tables only:

| Table | Usage |
|-------|--------|
| `ar_session` | Pre-payments: `adjustment_code = pre_payment` |
| `ar_session` | Refunds: `adjustment_code = refund_balance` |
| `ar_activity` | Allocations to encounters: `account_code = PP`, `deleted IS NULL` |
| `form_encounter` | Encounter service date for allocation lines |

When module-owned schema is added later:

1. Define tables in `table.sql` with `#IfNotTable` / `#EndIf`
2. Bump `$v_database` in `version.php`
3. Add `sql/{from}-to-{to}_upgrade.sql`
4. Drop tables on unregister in `ModuleManagerListener`

## Architecture

```text
oe-module-escrow/
  openemr.bootstrap.php       # Registers namespace; starts Bootstrap
  ModuleManagerListener.php   # Install / enable / disable / unregister hooks
  moduleConfig.php            # Module Manager gear-icon page
  table.sql                   # Initial schema (none required)
  version.php                 # Module + SQL version metadata
  info.txt                    # UI display name
  composer.json
  public/
    index.php                 # Report entry
    refund.php                # Refund dialog entry
    assets/                   # Optional CSS/JS
  src/
    Bootstrap.php             # Menus + Twig path registration
    ModulePath.php            # Symlink-safe OpenEMR root resolution
    Controller/EscrowReportController.php
    Service/EscrowService.php # QueryUtils data access
    Exception/EscrowModuleException.php
  templates/escrow/
    base.html.twig
    index.html.twig
    refund.html.twig
  sql/                        # Versioned upgrades (when $v_database > 0)
  tests/Unit/
```

### OpenEMR 8.x conventions used

- **Session:** `SessionWrapperFactory` for `authUser`, `authUserID`, `pid`
- **CSRF:** `CsrfUtils::setupCsrfKey($session)`, `collectCsrfToken($session)`, `verifyCsrfToken($token, $session)`
- **Database:** `OpenEMR\Common\Database\QueryUtils` (not legacy `sqlQuery` / `sqlStatement` in services)
- **Exceptions:** module-scoped `EscrowModuleException`
- **UI:** Twig templates; `Header::setupHeader()` for assets
- **ACL:** explicit username passed into `AclMain::aclCheckCore()`
- **Autoload:** Module Manager / `ModulesClassLoader` namespace registration — **do not** run `composer dump-autoload` in the OpenEMR root for this module

## Configuration

`moduleConfig.php` is a placeholder. No encrypted secrets or globals are required for v1.0.0. Future site-specific options should stay on a module config page (not Admin → Globals) unless there is a strong reason otherwise.

## Development

```bash
cd "/path/to/oe-module-escrow"
composer install   # optional; only needed for local PHPUnit
./vendor/bin/phpunit
```

Primary checkout path used in development:

```text
/home/sherwin/OEMR Modules/oe-module-escrow
```

Verified install path (OpenEMR 8.2 sandbox):

```text
/home/sherwin/Trabal_Migration_OpenEMR/openemr/interface/modules/custom_modules/oe-module-escrow
```

### Manual verification checklist

1. Module appears in Manage Modules and is **Enabled**
2. After re-login, **Escrow** appears on the patient menu and under Reports
3. With a patient that has `pre_payment` sessions, the report lists session id, method, amount, PP allocations, and remaining balance
4. Refund dialog accepts check date / number / amount and inserts `refund_balance`
5. Second refund attempt is gated in UI when a refund row already exists for the patient

## Original source

Legacy report (OpenEMR 7 client tree):

```text
ivfandfertility2/interface/reports/escrow/
  Escrow.php
  index.php
  refund.php
```

## Uninstall

1. Modules → Manage Modules → **Disable** then **Unregister**
2. Remove the `custom_modules/oe-module-escrow` directory or symlink
3. No module tables to drop in v1.0.0; optional cleanup of test AR rows is manual if any were inserted during QA

## See also

- [CHANGELOG.md](CHANGELOG.md)
