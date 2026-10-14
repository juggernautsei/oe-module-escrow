# oe-module-escrow

OpenEMR custom module for patient **escrow pre-payment** reporting and refunds.

Migrated from `interface/reports/escrow` on IVF client installs. Targets **OpenEMR 8.x** multi-site deployments.

## Features

- Patient menu item: **Escrow**
- Reports menu item under Reports
- Lists `ar_session` pre-payments (`adjustment_code = pre_payment`)
- Shows encounter allocations from `ar_activity` (`account_code = PP`)
- Records balance refunds (`adjustment_code = refund_balance`)

## Requirements

- OpenEMR 8.x
- PHP >= 8.1

## Install

1. Place or symlink this directory at:

   ```text
   {openemr}/interface/modules/custom_modules/oe-module-escrow
   ```

2. In OpenEMR: **Modules → Manage Modules**
   - Unregistered → **Register**
   - **Install**
   - **Enable**

3. Log out and back in so menu listeners reload.

## Layout

```text
oe-module-escrow/
  openemr.bootstrap.php      # Module loader entry
  ModuleManagerListener.php  # Install/enable/unregister hooks
  moduleConfig.php           # Gear-icon config stub
  table.sql                  # Install schema (none required yet)
  version.php
  info.txt
  composer.json
  public/                    # Web entry points
  src/                       # PSR-4 OpenEMR\Modules\Escrow\
  templates/escrow/          # Twig views
  sql/                       # Versioned upgrades when $v_database bumps
  tests/
```

## Development notes

- Namespace: `OpenEMR\Modules\Escrow\`
- DB access via `OpenEMR\Common\Database\QueryUtils`
- Exceptions via `OpenEMR\Modules\Escrow\Exception\EscrowModuleException`
- No module-owned tables; multi-site isolation is per-site database + session patient context
- Do not run `composer dump-autoload` in the OpenEMR root for this module; use the module class loader / Module Manager namespace registration

## Source of original report

`ivfandfertility2/interface/reports/escrow/`
