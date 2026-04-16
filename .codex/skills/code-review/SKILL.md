---
name: code-review
description: Review code in /home/r00t/projects/ecommerce/src with a project-specific Laravel 13 checklist grounded in the Game Items E-commerce architecture. Use when the task asks for review, audit, findings, safe refactors, or pre-merge validation so the analysis favors modular domains, thin controllers/Livewire components, Actions as the home for business logic, and MVP scope discipline.
---

# Code Review

## Goal

Perform technical and corrective code review for this repository with a strong focus on correctness, maintainability, readability, and alignment with the project architecture described in `.codex/docs/project.md`.

This skill must:
- identify implementation and design problems;
- surface real risks, code smells, and architecture drift;
- keep findings grounded in the repository's modular monolith structure;
- suggest small and safe fixes when appropriate;
- only apply code changes when the task explicitly asks for them;
- respect Laravel 13, PHP 8.3, repository conventions, and MVP scope.

## When to use

Use this skill when the task involves one or more of the following:
- reviewing new or legacy PHP/Laravel code;
- reviewing a diff before merge or release;
- looking for code smells, excessive coupling, or duplication;
- checking whether controllers, Livewire components, models, or jobs are carrying business logic that belongs in Actions;
- validating alignment with Laravel 13, PHP 8.3, and project architecture;
- proposing safe, incremental refactors after a review;
- checking whether a change expands MVP scope by accident.

## When not to use

Do not use this skill for:
- large architecture redesigns with no review target;
- broad automated migrations with weak validation coverage;
- "rewrite everything" requests in a single pass;
- behavior-changing product decisions without domain confirmation.

In those cases, narrow the scope first and produce a short plan before proposing implementation changes.

## Project architecture baseline

Treat `.codex/docs/project.md` as the source of truth for architecture and MVP scope.

This repository is a:
- Laravel 13 backend with Livewire frontend;
- PostgreSQL application running through Docker inside WSL;
- modular monolith organized by domain;
- project that prefers Actions as use cases.

The core domains are:
- Catalog
- Cart
- Orders
- Payments
- Admin

Review with these architectural expectations:
- controllers are thin and coordinate requests only;
- Livewire components are thin and do not accumulate domain logic;
- business logic lives in Actions/use cases;
- models handle persistence, not orchestration-heavy rules;
- jobs and events are used only when needed;
- external integrations stay isolated behind clear boundaries or gateways;
- code should stay simple enough for MVP evolution without speculative layers.

## MVP scope guardrails

The reviewer must protect the current MVP boundaries described in `project.md`.

Flag changes that:
- introduce a separate business `category` dimension for catalog logic in the MVP;
- expand checkout beyond collecting `email` and `whatsapp` without explicit product direction;
- treat payment processing as already implemented instead of deferred work;
- add microservices, outbox, advanced stock automation, or full gateway orchestration prematurely;
- blur the current manual fulfillment flow;
- make stock handling less explicit around order creation;
- store or process product price in a format other than integer cents;
- add complexity justified only by possible future needs.

Prefer solutions that preserve:
- browsing by game and rarity;
- minimal checkout handoff;
- manual admin fulfillment;
- stock decrement on order creation;
- clean future evolution without overengineering now.

## Review principles

### 1. Findings over preferences
- Prioritize correctness, maintainability, security, clarity, performance, and architecture fit.
- Avoid vague comments such as "clean this up".
- Explain why the issue matters in this project, not only in abstract Laravel terms.

### 2. Preserve behavior unless the task says otherwise
- Do not change business rules during review unless explicitly requested.
- If a proposed fix changes behavior, state that clearly.
- Prefer small, verifiable corrections over broad rewrites.

### 3. Review for architecture drift
- Watch for business rules leaking out of Actions.
- Watch for duplicated domain rules across modules.
- Watch for customer flow and admin flow responsibilities becoming mixed.
- Watch for future-scope payment or fulfillment concerns leaking into MVP code paths.

### 4. Prefer project-aligned abstractions
- Prefer Actions over vague services, managers, helpers, or utility buckets.
- Only introduce a new abstraction when it clarifies a domain boundary or removes meaningful duplication.
- Treat generic repositories, managers, and "common" classes with skepticism unless they provide concrete local value.

### 5. Respect modern Laravel and PHP
- Keep Laravel usage idiomatic when that improves clarity.
- Favor clear typing, explicit returns, and readable APIs.
- Reduce ambiguous payloads, hidden state, and surprising side effects.

## Main review checklist

### A. Domain and architecture alignment
Check for:
- business logic in controllers or Livewire components;
- orchestration-heavy logic in models;
- Actions that are missing where a use case clearly exists;
- module boundaries that are bypassed by direct cross-domain shortcuts;
- customer and admin concerns mixed in the same flow;
- payment concepts coupled into orders or checkout too early;
- future-scope delivery automation leaking into MVP implementation.

### B. MVP scope discipline
Check for:
- checkout collecting more than the minimum buyer data required now;
- new catalog classification dimensions beyond game and rarity;
- stock behavior that becomes implicit, hidden, or hard to verify;
- fulfillment assumptions that contradict manual admin handling;
- complexity added for speculative future features rather than current business needs.

### C. Code smells and design
Check for:
- very long methods;
- very large classes;
- multiple responsibilities in the same class;
- methods mixing business logic, persistence, formatting, and error handling;
- deeply nested conditional flow;
- too many parameters;
- too many constructor dependencies;
- vague naming such as `Helper`, `Util`, `Manager`, `process`, or `handleData`;
- comments that exist only to explain confusing structure.

### D. Duplication and cohesion
Check for:
- repeated validation blocks;
- repeated payload transformations;
- repeated queries across modules;
- duplicated domain decisions across controller, Livewire, Action, job, or model;
- copy-paste code with small variations.

When duplication exists:
- prefer extraction only when the result is clearer than repetition;
- avoid generic shared layers that weaken domain ownership.

### E. Laravel and Livewire usage
Check for:
- excessive inline validation when a Form Request or dedicated validation structure would be clearer;
- improper facade use where dependency injection would improve clarity;
- N+1 queries or careless relationship loading;
- inconsistent response or serialization handling;
- weak transaction boundaries around order and stock behavior;
- jobs, events, listeners, or policies used without a clear need;
- Livewire components becoming a de facto service layer.

### F. PHP 8.3 and API clarity
Check for:
- missing types where intent is stable;
- mixed or unclear return contracts;
- magic arrays hiding meaning that should be explicit;
- loose comparisons where exact intent matters;
- generic exceptions with weak messages;
- method names and signatures that do not reveal behavior clearly.

### G. Testability, safety, and robustness
Check for:
- code that is hard to test because of coupling or hidden side effects;
- time, IO, network, or global state dependencies with no seam for tests;
- missing authorization or overly implicit access rules;
- excessive trust in incoming payloads;
- failure handling that is unclear around persistence or integrations;
- missing test coverage for critical changed behavior.

## Validation and tooling guidance

Use repository-native validation first. Do not assume every tool exists.

When checks are needed:
1. Read the target area and surrounding flow first.
2. Discover available project commands and config before selecting tools.
3. Prefer Docker/WSL/container-aware execution in this workspace when commands must run.
4. Run the narrowest relevant checks first.
5. Re-run impacted validation after any requested code changes.

Possible validation tools include:
- `vendor/bin/phpcs` when PHPCS is configured in the repo;
- `vendor/bin/pint --test` when Pint is available;
- `vendor/bin/phpstan` or `vendor/bin/phpstan analyse` when static analysis exists;
- `php artisan test` or a narrower test target when application tests are relevant.

If repository configuration exists, use it instead of forcing generic defaults.

Do not promise:
- PHPCS, Pint, or PHPStan execution when they are not available;
- automatic fixes unless the task explicitly allows code changes;
- full revalidation when commands were not actually run.

## Review output format

Deliver review results in this order:

```md
## Findings
### [Severity] Short title
- Where it is
- Why it is a problem here
- What the minimum safe fix is

## Change plan

## Validation
- Command executed
- Result

## Residual risks and assumptions
```

Rules for the response:
- Put findings first, ordered by severity.
- Tie each finding to concrete files, symbols, or flows.
- Keep the change plan brief and actionable.
- Include validation only for commands that actually ran.
- If no findings are found, state that explicitly and mention remaining risks or gaps.

## Execution procedure

1. Read the target files and enough surrounding context to understand the flow.
2. Compare the implementation against `project.md` expectations before discussing style.
3. Identify correctness, security, maintainability, and architecture issues.
4. Prioritize findings by severity and likelihood of real impact.
5. Propose the minimum safe fix path.
6. Apply code changes only if the task explicitly requests fixes.
7. Revalidate only with commands that are available and relevant.
8. Report residual risks, assumptions, and unvalidated areas.

## Rules for safe changes

It may propose or apply small code changes only when:
- the task explicitly asks for fixes or adjustments;
- the issue is local and low risk;
- the intended behavior is already clear;
- there is a reasonable way to validate the change.

It must stop and report before changing code when:
- the fix requires a product or business decision;
- expected behavior is ambiguous;
- the change would expand MVP scope;
- the fix triggers a broad chained refactor;
- there is no reasonable validation path for a risky change.

## Usage examples

### Example 1
> Review the current diff in the Orders domain, focus on whether stock decrement and order creation logic still belong in Actions, and list findings without changing behavior.

### Example 2
> Review the checkout flow and flag any code that expands the MVP beyond collecting email and WhatsApp. Apply only safe local fixes if needed.

### Example 3
> Review Catalog and Admin changes for architecture drift, especially business logic inside controllers or Livewire components, and validate with the narrowest repo-supported checks.

## Final notes

- This skill is for project-aware code review, not generic style policing.
- Correct behavior and explicit project conventions take priority over aesthetic preference.
- When architecture guidance and generic Laravel advice conflict, follow the repository architecture and MVP boundaries first.
