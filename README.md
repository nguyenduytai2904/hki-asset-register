# HKSpace Asset Register

Composer package `hki/asset-register` adds an account-scoped asset register to
HKSpace. It is intended for account administrators and asset managers who
maintain equipment catalogs and asset records, and for members who need to
view assets assigned to them.

| Item | Value |
| --- | --- |
| Composer package | `hki/asset-register` |
| Plugin code | `asset-register` |
| Provider | `Hki\AssetRegister\AssetRegisterServiceProvider` |
| Module class | `Hki\AssetRegister\AssetRegisterModule` |
| Required plugin API contract | `hki/hkspace-plugin-api ^1.2` |
| Runtime | PHP `^8.2`, Laravel `^12.0` |

## Permissions

The plugin registers these account-scoped permission keys in the **Tài sản**
group. The account must also have the plugin granted and active.

| Key | Label | Allowed actions |
| --- | --- | --- |
| `asset-register.self.use` | Tự phục vụ | Open the “Của tôi” tab and view the member's assigned-asset area. |
| `asset-register.assets.view` | Xem sổ tài sản | Open and read the account's asset register. |
| `asset-register.assets.manage` | Quản lý sổ tài sản | Manage asset-type and location catalogs. Asset-record mutations require the register-view permission as well. |

The core account `view` permission also grants the self-service entry point;
the core account `manage` permission grants register viewing and management.
Having only `asset-register.assets.manage` opens Settings but does not grant
access to the register. Suspended account members and accounts without the
plugin grant are blocked by the core access middleware.

## Configuration

The plugin-specific PHP configuration is in `config/asset-register.php`.

| Environment variable | Default | Meaning | When to set |
| --- | --- | --- | --- |
| `ASSET_REGISTER_ENABLED` | `true` | Declared as `asset-register.enabled`. No runtime code currently reads this value; it is not an activation or security switch. | Do not rely on it to enable or disable the plugin. Use the core plugin catalog and account grant instead. |
| `ASSET_REGISTER_DEMO_COMMAND_ALLOWED` | `true` | Declared as `asset-register.demo_command_allowed`. The demo command currently does not read this value. | Do not rely on it to authorize the demo command; the command itself rejects `APP_ENV=production`. |
| `ASSET_REGISTER_BASE_URL` | `http://localhost` | Playwright's E2E base URL; this is a test-runner variable, not Laravel runtime configuration. | Set it to the non-production HKSpace URL under test. |
| `APP_ENV` | Defined by HKSpace | The development seeder runs only in `local` and `testing`; the demo-data command refuses `production`. | Keep the core environment accurate. Never set a production host to `local` or `testing` to bypass the command guard. |

The plugin defines no database connection variables. Database driver,
credentials and test-database isolation are inherited from the host HKSpace
configuration. The demo seeder runs only in `local` and `testing`; the console
demo-data command refuses to run in `production`.

## Background work and integrations

| Capability | Status |
| --- | --- |
| Queued jobs / queues | None registered by this plugin. |
| Scheduled tasks | None registered by this plugin. |
| Events emitted / listeners | None registered by this plugin. |
| Realtime channels / broadcasts | None registered by this plugin. |
| Plugin API routes | None currently defined in `routes/api.php`; plugin screens use authenticated web routes. |

## Console commands

| Signature | Effect | `--dry-run` |
| --- | --- | --- |
| `asset-register:demo-data {accountId} {--reset}` | Creates sample types, locations and assets for one existing account. Requires the six active sample members (`ha`, `khoa`, `bao`, `an`, `tam`, `huy` at `@sotaisan.test`). With `--reset`, deletes this plugin's history, handover, asset, type and location rows for that account before rebuilding them. The operation is transactional and refuses `APP_ENV=production`. | Not supported. `--reset` is destructive for the selected account; use only in an isolated development/test account. |

Plugin-owned tables use the `asset_register_` prefix and are declared in
`AssetRegisterSchema`. Both migrations and the plugin installer use this schema
owner. A compatibility migration renames earlier unprefixed development tables
and adds account ownership to existing handover and history records.

## System dependencies

| Dependency | Minimum | Use |
| --- | --- | --- |
| PHP | `8.2` | Plugin runtime. |
| Composer | Composer 2 / runtime API `^2.0` | Install the package and resolve its dependencies. |
| HKSpace core | Laravel `12.x`; plugin API contract `^1.2` | Host application, plugin lifecycle, account context, RBAC and database services. |
| Node.js and npm | Needed only for E2E test tooling | Install and run Playwright; not needed on the production application server. |
| Playwright browser | Chromium installed with Playwright | Needed only when E2E scenarios are present and run. |

The plugin requires no additional server executable, native extension,
external storage service or realtime server beyond what the host HKSpace
installation itself requires. Use the database engine configured by the
host; migrations are run by Laravel.

## Code layout

```text
config/                         Plugin configuration
database/factories/             Plugin model factories
database/migrations/            Plugin-owned schema
database/seeders/               Development/test sample workspace
lang/{en,vi}/                   Translations
resources/views/asset-register/ Plugin pages and styles
routes/{web.php,api.php}        Authenticated plugin routes
src/Asset/
  Contracts/{Repositories,Services}/ Interfaces consumed across layers
  DTOs/                          Typed use-case data
  Factories/                     Validated input to DTO mapping
  Http/{Controllers,Requests}/   Thin web adapters and request validation
  Models/                        Eloquent domain records
  Policies/                      Asset and catalog authorization
  Repositories/                  Account-scoped persistence
  Services/                      Use-case orchestration
src/Console/Commands/             Plugin Artisan commands
src/Lifecycle/                    Install, grant, revoke, uninstall and purge
src/Rbac/                         Permission keys and core permission provider
src/Support/                      Cross-domain preview and schema helpers
tests/                            Feature, architecture and E2E tests
```

Controllers authorize and validate through FormRequests, map input through
factories, and call service interfaces. Services call repository interfaces;
repositories own database queries and transactions. Policies centralize
permission decisions.

## Development and testing

For local development, add the package as a Composer path repository in the
core project, then install/discover it and migrate using the host core:

```bash
composer require hki/asset-register:@dev
php artisan package:discover
php artisan migrate
php artisan plugin:sync --prune
php artisan plugin:grant asset-register --account=1
```

Run plugin feature tests from the HKSpace core so the core test bootstrap,
database isolation guard and plugin test suite are used:

The Asset feature suite keeps all asset-related tests under
`tests/Feature/Asset`. The current suite covers permissions/navigation and the
development fixture.

```bash
cd /path/to/hk-space-be
php artisan test --testsuite=Plugins --filter=AssetPermissionTest
php artisan test --testsuite=Plugins --filter=AssetDevelopmentSeederTest
```

The development fixture asserts exactly **9 asset types, 5 locations and 18
assets** for its account. The core test database is derived from the core
`.env` `DB_DATABASE` plus `_testing`; do not point tests at a development or
production database.

## UC-004 to UC-007: Asset register operations

The `feature/asset-register-uc4-uc7` review branch adds the live,
account-scoped registry and asset-record workflows:

- UC-004: search, multi-value status/type/location/warranty filters, sorting,
  pagination, empty states and former-member keeper labels.
- UC-005: create an asset with a generated or manually entered account-unique
  code, an initial history record, purchase/warranty details and catalog
  validation. Generated codes use a per-type sequence and skip occupied codes.
- UC-006: view account-scoped asset details and history. A keeper without
  register access is limited to their own detail view and related history.
- UC-007: edit asset metadata without changing its code, preserve unchanged
  disabled catalog selections, and record field changes in history.

Asset-record routes require the relevant account permission and an active
plugin grant. Disposed records are read-only; lifecycle actions such as
handover, status transition and disposal are outside UC-004 to UC-007.

Playwright is configured to use `ASSET_REGISTER_BASE_URL`. Start an isolated
HKSpace test instance with a seeded test account, then run:

```bash
cd /path/to/asset-register
npm install
npx playwright install chromium
ASSET_REGISTER_BASE_URL=http://localhost:8000 npm run test:e2e
```

There are currently no Playwright spec files under `tests/E2E`; the command
will have no plugin scenarios to run until E2E cases are added. Never point
E2E tests at production.

## Business limits and known gaps

### Catalogs

- All catalog records are scoped to the current account.
- Type name: required, up to 100 characters. Prefix: required, 2–6 uppercase
  ASCII letters/digits; it is normalized to uppercase.
- Location name: required, up to 150 characters.
- Type icon and type/location descriptions are optional, up to 50 and 500
  characters respectively.
- Type names, type prefixes and location names are unique per account;
  comparisons ignore case and distinguish accents.
- A type prefix cannot be changed after the type is used by an asset. A type
  or location referenced by an asset cannot be deleted; it can be disabled.

### Asset records

- Asset codes are account-unique, 2–30 characters, normalized to uppercase,
  and limited to ASCII letters, digits and hyphens. Generated codes use the
  type prefix and per-type sequence.
- Name is required (1–150 characters); brand, model and serial are up to 100
  characters; configuration and note are up to 2,000; supplier is up to 150;
  receipt number is up to 100.
- Condition is one of `new`, `good`, `fair` or `poor`. Warranty end date cannot
  precede purchase date. New records require an active type and, if selected,
  an active location.
- Account scoping is enforced on reads and writes. A disabled current catalog
  value may be retained while editing, but a different disabled value cannot
  be selected.

### Release scope

- The “Của tôi” page is currently a static development preview; self-service
  actions are not implemented.
- Handover actions, status transitions and disposal actions are not
  implemented. A disposed record, if present, is read-only.
- The plugin exposes web routes only; no public plugin API, import/export,
  file upload, notifications, queue processing or realtime updates are
  provided.
- `ASSET_REGISTER_ENABLED` and `ASSET_REGISTER_DEMO_COMMAND_ALLOWED` are
  present in config but are not wired to runtime behavior; plugin activation
  and access must be controlled through the core plugin registry and account
  grants.
