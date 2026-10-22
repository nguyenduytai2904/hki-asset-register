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
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/AssetRegisterDevelopmentSeeder.php
├── lang/{vi,en}/
├── routes/{web.php,api.php}
├── src/
│   ├── Asset/
│   │   ├── Contracts/{Repositories,Services}/
│   │   ├── DTOs/
│   │   ├── Factories/
│   │   ├── Http/{Controllers,Requests}/
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Repositories/
│   │   └── Services/
│   ├── Console/Commands/
│   ├── Lifecycle/
│   ├── Rbac/
│   ├── Support/
│   ├── AssetRegisterModule.php
│   └── AssetRegisterServiceProvider.php
└── tests/
```

Controllers are HTTP adapters: they accept FormRequests, use factories to
create DTOs, call services through interfaces, then return views or redirects.
Policies own permission decisions and are invoked by FormRequests before
validation; services retain defensive policy checks and implement use cases.
Persistence queries and transactions are behind repository interfaces. The
plugin service provider binds the repository and registers the domain policy.

## Wave 1: UC-001 to UC-003

The first web slice provides the permission-aware Asset Register entry point
and the “My assets”, “Asset register” and “Settings” tabs. Settings reads and
mutates the active account's asset-type and location catalogs. Catalog names
are unique per account, case-insensitive and accent-sensitive; a type prefix
cannot change after its first asset, and a catalog item in use cannot be
deleted.

The page routes and catalog mutations are registered in `routes/web.php`.
The “My assets” page remains a static development preview until its later
self-service use case.

## Wave 2: UC-004 to UC-007

The asset register now reads account-scoped records from the database and
supports search, status/type/location/warranty filters, sort orders and
pagination. Managers can add assets with an account-specific generated or
manual code; automatic sequences are stored on each type and skip occupied
codes without advancing for manually entered codes. Creation writes the
initial history entry. Registry filters update asynchronously as values change.
Asset and catalog creation forms open in dialogs, and plugin screens scroll
within the content area below the core's fixed header and sidebar.

Asset details and editing enforce account and permission boundaries. Current
or pending keepers without register access can open only their own limited
detail view. Editing never changes the asset code, retains the current
disabled type/location when unchanged, rejects selecting other disabled
catalog entries, and records changed values in immutable history. Disposed
records are read-only; disposal actions themselves are not implemented in
this wave.

UC-008, handover actions, status transitions and disposal actions remain
outside this release. The `AssetRegisterPreviewData` fixture may still be
used by development tooling, but it is no longer the data source for the live
registry or settings screens.

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
account. The demo account must contain the active fixture members used by the
sample data (`ha`, `khoa`, `bao`, `an`, `tam` and `huy` at
`@sotaisan.test`); records are not assigned to users outside that account or
to suspended members.

## Local and test fixtures

In `local` and `testing`, the plugin contributes
`AssetRegisterDevelopmentSeeder` through `AssetRegisterModule::developmentSeeders()`.
The core `DatabaseSeeder` and PHPUnit `RefreshDatabase` flow use it to create
the isolated `Asset Register Test Workspace`, grant the plugin, add the six
active demo members, and reset/rebuild the sample catalogs, assets and handovers.
The seed is skipped outside local/testing and does not include the core's large
task dataset.

The test database name is derived from the core `.env` `DB_DATABASE` plus
`_testing`; do not hard-code it in `phpunit.xml`. Run tests through the core's
standard test command so its isolation guard selects the correct database.

## Development checklist

- [x] Composer package, manifest and provider use the Asset Register identity.
- [x] Five models and five owned database tables are defined.
- [x] Account-scoped permissions and role catalog provider are defined.
- [x] Grant, revoke, uninstall and purge lifecycle handlers are present.
- [x] Vietnamese and English package translation directories are present.
- [x] Demo data command is available for local database verification.
- [x] A repeatable local/testing fixture creates a granted Asset Register
  workspace and seeds the plugin's demo catalogs, assets and handovers.
- [x] Legacy Hello and Equipment starter code has been removed.
- [x] UC-001 access/navigation and UC-002/UC-003 catalog web screens and
  account-scoped mutations are implemented.
- [x] UC-004 registry search, filters, sorting and pagination use account data.
- [x] UC-005 create and UC-006 detail / UC-007 edit use live account data,
  with code sequencing and immutable history.
- [x] Feature tests cover permission gating, catalog behavior, uniqueness,
  reference constraints and demo fixture integrity.
- [x] Focused MySQL feature tests cover live registry, asset creation, detail,
  edits, access control and account isolation.
