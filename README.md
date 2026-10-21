# HKSpace Asset Register

Composer package `hki/asset-register` for the HKSpace asset-register
plugin. This package contains only the real Asset Register domain; starter
screens such as Hello World and the old Equipment CRUD are intentionally not
part of the plugin.

## Identity

| Item | Value |
| --- | --- |
| Composer package | `hki/asset-register` |
| Namespace | `Hki\AssetRegister` |
| Plugin code | `asset-register` |
| Provider | `Hki\AssetRegister\AssetRegisterServiceProvider` |
| Config | `config/asset-register.php` |

## Structure

```text
asset-register/
├── composer.json
├── config/asset-register.php
├── database/migrations/
├── lang/{vi,en}/
├── routes/{web.php,api.php}
├── src/
│   ├── Asset/Models/
│   │   ├── Asset.php
│   │   ├── AssetType.php
│   │   ├── AssetLocation.php
│   │   ├── AssetHandover.php
│   │   └── AssetHistory.php
│   ├── Console/Commands/
│   ├── Lifecycle/
│   ├── Rbac/
│   ├── AssetRegisterModule.php
│   └── AssetRegisterServiceProvider.php
└── tests/
```

## Database

The plugin owns exactly five tables:

- `asset_types`
- `asset_locations`
- `assets`
- `asset_handovers`
- `asset_histories`

Every account-owned table is scoped by `account_id`. The migration
`000006_remove_legacy_template_equipment_table.php` only removes the obsolete
local demo table from an existing installation; it is not part of the current
domain model.

## Permissions

The plugin registers exactly these account-scoped permissions:

- `asset-register.self.use`
- `asset-register.assets.view`
- `asset-register.assets.manage`

The permission provider is registered through
`AccountPermissionCatalogRegistry`, so these permissions are available to the
core role editor and full-access role synchronization.

## Lifecycle

The package provides installer, account grant, account revoke, account
uninstaller and full purge handlers:

- Grant enables the plugin for an account.
- Revoke removes access without deleting account data.
- Uninstall deletes the selected account's asset data and supports preview.
- Purge removes plugin tables in dependency-safe reverse order.

## Local integration

Add a path repository in the core project:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../asset-register",
      "options": { "symlink": true }
    }
  ]
}
```

Then run:

```bash
composer require hki/asset-register:@dev
php artisan package:discover
php artisan migrate
php artisan plugin:sync --prune
php artisan plugin:grant asset-register --account=1
```

The demo command creates account-scoped data for database verification:

```bash
php artisan asset-register:demo-data 1 --reset
```

It is disabled in production and never deletes data belonging to another
account.

## Development checklist

- [x] Composer package, manifest and provider use the Asset Register identity.
- [x] Five models and five owned database tables are defined.
- [x] Account-scoped permissions and role catalog provider are defined.
- [x] Grant, revoke, uninstall and purge lifecycle handlers are present.
- [x] Vietnamese and English package translation directories are present.
- [x] Demo data command is available for local database verification.
- [x] Legacy Hello and Equipment starter code has been removed.
- [ ] Add feature/API controllers and policies for each production Asset
  Register screen.
- [ ] Add account-isolation, authorization and end-to-end tests for each
  production screen.
