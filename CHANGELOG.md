# Changelog

All notable changes to `laravel-mermaid-erd` will be documented in this file.

## [0.6.0](https://github.com/bambamboole/laravel-mermaid-erd/compare/0.5.0...0.6.0) (2026-08-17)


### ⚠ BREAKING CHANGES

* requires PHP 8.4+ and Laravel 13. Support for PHP 8.3 and Laravel 12 is dropped.

### Features

* add ELK layout switch, search highlighting, and table detail modal to ERD viewer ([de09cc9](https://github.com/bambamboole/laravel-mermaid-erd/commit/de09cc9310d41ae8e28c8639e7a8ec6dd2d6a98f))
* add focus mode, schema health panel, legend, and pinned CDN versions ([8eb5fc1](https://github.com/bambamboole/laravel-mermaid-erd/commit/8eb5fc130394629e2766476e28bdaebf0416b9f2))
* annotate relations whose columns lack a supporting index ([1f73717](https://github.com/bambamboole/laravel-mermaid-erd/commit/1f73717f9fbfa283cf9ece30a0a988183c1a90f0))
* annotate relations whose columns lack a supporting index ([83b5797](https://github.com/bambamboole/laravel-mermaid-erd/commit/83b5797cf6d6a7c3cef33b3ec0a8f47424f0acfd))
* expose structured column and relation details in the graph payload ([a57db70](https://github.com/bambamboole/laravel-mermaid-erd/commit/a57db705fd4b685ad5a5cf26354ad0e2d8fa237b))
* make elk the default layout engine, configurable via web.layout ([cf175a7](https://github.com/bambamboole/laravel-mermaid-erd/commit/cf175a79d8c48c445b5471c5e6f0d5669af345e4))
* replace the table detail modal with an info sidebar ([0069838](https://github.com/bambamboole/laravel-mermaid-erd/commit/00698384c374afe0dbfe2e4dd8fe8886ac9bdbaa))
* require PHP 8.4 and Laravel 13, adopting the extended-testbench scaffold ([db60a90](https://github.com/bambamboole/laravel-mermaid-erd/commit/db60a90064be616a2eb91ab79c255af8d0b7aa62))
* show columns and relations in the ERD table detail modal ([b0de073](https://github.com/bambamboole/laravel-mermaid-erd/commit/b0de0733bddd0ec4f1a48d9b3601ba6027ff7a83))


### Bug Fixes

* detect pivot tables without a real foreign key constraint ([4286a7c](https://github.com/bambamboole/laravel-mermaid-erd/commit/4286a7ca295d2d9054c4f7222f4646e08406d933))


### Refactoring

* derive relation facts from Column objects and dedupe wiring ([d5199dd](https://github.com/bambamboole/laravel-mermaid-erd/commit/d5199ddc47cd5aba975685ddf19b8a8eb0db11e5))
* introduce a mermaid document object model behind MermaidErdRenderer ([8daf143](https://github.com/bambamboole/laravel-mermaid-erd/commit/8daf14368cb7fa89e878e5fa576ea8eba13174be))
* replace spatie/laravel-package-tools with a plain service provider ([582206c](https://github.com/bambamboole/laravel-mermaid-erd/commit/582206c90f51d4bf2f76fc1d347c99e374da0e4b))
* strip over-engineering found by audit ([f7f5e3b](https://github.com/bambamboole/laravel-mermaid-erd/commit/f7f5e3b1e586669e908cb18a3fb652cc329c8e98))


### Documentation

* regenerate the README ERD from the current workbench schema ([e7472ad](https://github.com/bambamboole/laravel-mermaid-erd/commit/e7472ad2bbbd8e2c729e44d258175180b311671a))
* trim the guidelines to project-specific rules ([5529336](https://github.com/bambamboole/laravel-mermaid-erd/commit/55293369fda6aaa3803516af1408ef2a6365466f))

## [0.5.0](https://github.com/bambamboole/laravel-mermaid-erd/compare/0.4.0...0.5.0) (2026-07-31)


### Features

* --exclude-tables option ([ac8d320](https://github.com/bambamboole/laravel-mermaid-erd/commit/ac8d320af51ec1739591c81cce2f87aea597cc42))
* discover hasMany/hasOne/belongsTo relations from models ([8ecd4f3](https://github.com/bambamboole/laravel-mermaid-erd/commit/8ecd4f3044b39466b26f0b04c356a5acd0aeb08d))
* enrich the diagram with Eloquent model metadata ([de5edc5](https://github.com/bambamboole/laravel-mermaid-erd/commit/de5edc55f457a5cda9ca7dfe58857a29e202d974))
* raw mermaid source endpoint via ?raw=1 ([d460daf](https://github.com/bambamboole/laravel-mermaid-erd/commit/d460daf5bc25ed3a3f817a8b8b950018fcca5f25))
* scan all of app_path() for models by default ([2654f25](https://github.com/bambamboole/laravel-mermaid-erd/commit/2654f252001facb19aad6c1e4a218b8c1f6ecc22))
* write plain mermaid source for .mmd paths ([a8d3647](https://github.com/bambamboole/laravel-mermaid-erd/commit/a8d3647deb99b7887cf91bb1989382526bf4f61b))
* zoom toward the cursor in the web view ([76488f6](https://github.com/bambamboole/laravel-mermaid-erd/commit/76488f6fa53bf2f4c2582cdf46cc5682acff8b98))


### Refactoring

* discover model classes with spatie/php-structure-discoverer ([6ea8504](https://github.com/bambamboole/laravel-mermaid-erd/commit/6ea8504448f2702ead16002d00b4bd5c889a991e))


### Documentation

* document raw endpoint, --exclude-tables and .mmd output ([0a06654](https://github.com/bambamboole/laravel-mermaid-erd/commit/0a0665438cb996555c09c5962c81a54597d0c5a2))

## [0.4.0](https://github.com/bambamboole/laravel-mermaid-erd/compare/0.3.5...0.4.0) (2026-07-31)


### Features

* live table/column filter in the web view + large workbench schema ([d4857d1](https://github.com/bambamboole/laravel-mermaid-erd/commit/d4857d1a3bcbb51acd23be9313299c8446d2afb7))
* resolve many-to-many relations through pivots when filtering ([c3bd920](https://github.com/bambamboole/laravel-mermaid-erd/commit/c3bd9208219fa7030997bbf9ce1e81998c0736c4))


### Bug Fixes

* narrow console option types for level 8 ([06372d1](https://github.com/bambamboole/laravel-mermaid-erd/commit/06372d11669400caebe0c62da2afbbb9c491dfe5))
* zoom proportionally to wheel delta ([5a35510](https://github.com/bambamboole/laravel-mermaid-erd/commit/5a35510381e6e94bcc457cbf009fb0725426c2a6))


### Refactoring

* extract schema model, make mermaid a renderer over it ([7c01d87](https://github.com/bambamboole/laravel-mermaid-erd/commit/7c01d876e94a87e600f3680af96a2afc6762db41))

## 0.3.5 - 2026-05-09

### What's Changed

* Fix MySQL schema scoping for table discovery by @Keyliananda in https://github.com/bambamboole/laravel-mermaid-erd/pull/15
* Bump dependabot/fetch-metadata from 2.5.0 to 3.1.0 by @dependabot[bot] in https://github.com/bambamboole/laravel-mermaid-erd/pull/14

### New Contributors

* @Keyliananda made their first contribution in https://github.com/bambamboole/laravel-mermaid-erd/pull/15

**Full Changelog**: https://github.com/bambamboole/laravel-mermaid-erd/compare/0.3.4...0.3.5

## 0.3.4 - 2026-04-08

### What's Changed

* Bump ramsey/composer-install from 3 to 4 by @dependabot[bot] in https://github.com/bambamboole/laravel-mermaid-erd/pull/11

**Full Changelog**: https://github.com/bambamboole/laravel-mermaid-erd/compare/0.3.3...0.3.4

## 0.3.3 - 2026-02-24

**Full Changelog**: https://github.com/bambamboole/laravel-mermaid-erd/compare/0.3.2...0.3.3

## 0.3.2 - 2026-02-24

**Full Changelog**: https://github.com/bambamboole/laravel-mermaid-erd/compare/0.3.1...0.3.2

## 0.3.1 - 2026-02-23

**Full Changelog**: https://github.com/bambamboole/laravel-mermaid-erd/compare/0.3.0...0.3.1

## 0.3.0 - 2026-02-23

**Full Changelog**: https://github.com/bambamboole/laravel-mermaid-erd/compare/0.2.0...0.3.0

## 0.2.0 - 2026-02-23

### What's Changed

* Bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 by @dependabot[bot] in https://github.com/bambamboole/laravel-mermaid-erd/pull/1
* Bump aglipanci/laravel-pint-action from 2.4 to 2.5 by @dependabot[bot] in https://github.com/bambamboole/laravel-mermaid-erd/pull/2
* Bump dependabot/fetch-metadata from 2.3.0 to 2.4.0 by @dependabot[bot] in https://github.com/bambamboole/laravel-mermaid-erd/pull/3
* Bump aglipanci/laravel-pint-action from 2.5 to 2.6 by @dependabot[bot] in https://github.com/bambamboole/laravel-mermaid-erd/pull/5
* Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/bambamboole/laravel-mermaid-erd/pull/9
* Add Laravel 12 support, multi-database compatibility (SQLite/MySQL/PostgreSQL), Pest v4 by @bambamboole in https://github.com/bambamboole/laravel-mermaid-erd/pull/10

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/bambamboole/laravel-mermaid-erd/pull/1
* @bambamboole made their first contribution in https://github.com/bambamboole/laravel-mermaid-erd/pull/10

**Full Changelog**: https://github.com/bambamboole/laravel-mermaid-erd/compare/0.1.1...0.2.0
