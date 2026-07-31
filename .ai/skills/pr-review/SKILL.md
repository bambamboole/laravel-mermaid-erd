---
name: pr-review
description: Use when reviewing a laravel-mermaid-erd pull request, branch diff, or set of staged/working changes for quality — reuse, simplification, efficiency, and altitude cleanups, plus adherence to the project guidelines. Review-only — it surfaces findings and never commits the changes it proposes.
---

# PR Review

Review changed code for **quality**. The mandate: is the change as simple, reused, efficient, and
well-placed as it should be, and does it follow the project guidelines?

This is **not** a broad bug hunt — for a full correctness sweep use `/code-review`; the two are
complementary. But "not a bug hunt" is not a licence to ignore correctness you stumble into. When a
hunk you are already reading raises a *concrete* correctness doubt, **trace it to ground before
deciding** — follow the data path, read the caller, confirm whether the defect is real. Report a
verified defect as a finding, or raise a genuinely ambiguous one as an explicit question. Never
defer an un-investigated suspicion.

**Core principle:** every finding must be behavior-preserving and earn its place. Suggest the change
a senior engineer would actually make — not a restyle, not a nitpick a linter already catches.

## Operating Rules (non-negotiable)

- **Review-only. Never commit, push, or leave the proposed change as the deliverable.** Your output
  is findings, not edits to merge. If the main checkout holds in-progress work, review in a separate
  worktree.
- You **may** edit code locally to *verify a path* — confirm a refactor passes
  `vendor/bin/pest --filter=...`, or that an existing helper really covers the case. After
  verifying, revert it. The deliverable is the finding, never the scratch edit.
- **Scope to the diff.** Only review lines the PR added or touched, plus the immediate context
  needed to judge them. Do not flag pre-existing issues on unmodified lines.
- **Preserve behavior.** If a suggestion changes outputs, side effects, or edge-case handling, it is
  out of scope — note it as a question, don't present it as a cleanup.
- **Confidence gate.** Only surface findings you are confident improve the code. When unsure, drop
  it. A short list of real improvements beats a long list of maybes.

## The Four Dimensions

Read every changed hunk through these lenses:

1. **Reuse** — *is this reinventing something we already have?* Duplicated blocks that should be one
   function; a hand-rolled helper mirroring an existing util, Collection method, or Laravel helper
   (`str()`, `collect()`, `Arr::`, `Str::`); re-deriving a value already computed elsewhere in the
   generator/service. Before claiming "this already exists", grep for it and confirm.
2. **Simplification** — *is this more complex than the job needs?* Dead code, redundant
   conditionals, one-caller indirection, needless nesting, flags that are always the same value,
   defensive checks that cannot trigger. Balance: explicit beats clever — prefer early returns and
   guard clauses over dense one-liners or nested ternaries.
3. **Efficiency** — *does this do needless work?* Repeated schema introspection for the same table
   (`getColumns`/`getForeignKeys`/`getIndexes` are live DB queries — hoist or reuse results),
   recomputation inside loops, work done eagerly that is rarely needed. Do not micro-optimize cold
   paths at the cost of readability.
4. **Altitude** — *is the code at the right level of abstraction?* Schema introspection belongs in
   `DatabaseInformationService`, diagram assembly in `MermaidErdGenerator`, file handling in
   `FileWriter`, I/O and prompts in the command/controller. Flag logic that leaks across those
   layers.

## Guideline Adherence (highest-signal checks)

The full rules live in `CLAUDE.md` / `.ai/guidelines/`. Weight these during review:

- **Driver-agnostic tests:** any assertion on generated diagrams that matches concrete column type
  names (`integer`, `bigint`, `int8`) or driver-specific defaults will break the MySQL/Postgres CI
  jobs. Match constraint markers instead (`\w+ id PK`).
- **Comments:** no "what" comments. Comments may only explain *why*.
- **PHP:** constructor property promotion; explicit return types and parameter type hints;
  `declare(strict_types=1)` in every file.
- **Testing:** behavior is tested through the artisan command, the web route, or the
  container-resolved services — not by asserting private internals. New diagram features need a
  workbench migration exercising them.
- **Git:** one logical change per commit; no agent attribution; concise PR description.

## What NOT to Flag

- Anything Pint, PHPStan, or Rector catches — CI runs `composer check` separately.
- Pre-existing issues on lines the PR didn't touch.
- Pedantic nitpicks a senior engineer would wave through.
- Intentional changes clearly tied to the PR's purpose.
- General "add more tests / docs" wishes unless a guideline requires it for this change.

## Workflow

1. Establish the diff. Branch/working tree: `git diff main...HEAD` (or `git diff` for uncommitted).
   GitHub PR: `gh pr diff <n>` and `gh pr view <n>`.
2. Read each changed hunk through the four dimensions, then the guideline checks.
3. For any non-obvious finding, **verify the path**: grep for the existing helper, or apply the
   refactor locally and run the relevant tests — then revert.
4. Apply the confidence gate; drop the maybes.
5. Report findings only. Do not commit.

## Findings Output Format

Group by dimension or by file. For each finding give: location, dimension/rule, why, and a concrete
suggested change. Cite `file_path:line`.

```
### PR Review — <branch or #PR>

Found N findings.

1. [Reuse] src/MermaidErdGenerator.php:42
   Reimplements column-name extraction already done in generateTableDiagram(). Pass the names in.

2. [Guideline: driver-agnostic tests] tests/LaravelMermaidErdCommandTest.php:88
   Asserts `integer id PK` — breaks on MySQL/Postgres. Match `\w+ id PK` instead.
```

If nothing meets the gate:

```
### PR Review — <branch or #PR>

No findings. Checked reuse, simplification, efficiency, altitude, and guideline adherence.
```
