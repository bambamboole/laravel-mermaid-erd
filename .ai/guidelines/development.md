# Local Development

- This package is developed with Orchestra Testbench, not a full Laravel app. `artisan` at the repo
  root is a symlink to `vendor/bin/testbench`, so `php artisan <command>` boots the Testbench
  skeleton with this package's service provider and the `workbench/` app.
- Run the test suite with `composer test`. Run the full CI-equivalent gate with `composer check`
  (Pint, PHPStan level 5, Rector dry-run, Pest). Never push on red.
- Tests run against in-memory SQLite by default. CI additionally runs the whole suite against
  MySQL 8 and Postgres 16; locally, start a container and set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`,
  `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (see `tests/TestCase.php`).
- **Assertions on generated diagrams must stay driver-agnostic.** Column type names differ per
  driver (`integer` vs `bigint` vs `int8`) and defaults render differently (Postgres emits
  `nextval(...)`). Match constraint markers (`\w+ id PK`), never concrete type names.
- The example ERD in `README.md` is generated from the workbench schema. Regenerate it whenever the
  workbench migrations or the diagram output format change:
  `vendor/bin/testbench workbench:build && vendor/bin/testbench generate:mermaid-erd --output=file --path=$PWD/README.md`
- Regenerate `CLAUDE.md` after editing `.ai/guidelines/`, `.ai/skills/`, or `boost.json` with
  `composer boost:refresh`.
- The Boost path overrides for the Testbench context live in `workbench/app/Support/` and are wired
  in `Workbench\App\Providers\WorkbenchServiceProvider`. They point Boost at the package root
  instead of the Testbench skeleton.
