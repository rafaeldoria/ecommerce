---
name: wave-docs
description: Create or update end-of-wave documentation for this ecommerce workspace after implementation work is finished. Use when a wave is completed and Codex should inspect modified files, compare the result with .codex/docs/tasks.md, summarize what was implemented, and save both an English version in .codex/docs/waves/wave-XX.md and a Portuguese version in .codex/docs/waves/pt-BR/wave-XX.md using the existing English wave files as the structural baseline.
---

# Wave Docs

Use this skill at the end of a delivery wave after code changes and validation are done.

The purpose is to leave behind a short, human-readable explanation of:

- what the wave implemented
- how the main modules interact
- what each module is responsible for
- what is expected from each module at this stage
- what is intentionally still out of scope

## Required workflow

1. Read `.codex/environment.md`.
2. Read the relevant wave section in `.codex/docs/tasks.md`.
3. Inspect the implemented code for the wave.
4. Inspect the modified files in git to understand the actual delivered scope.
5. Use the existing English wave docs in `.codex/docs/waves/` as the style baseline.
6. Generate or update:
   - English: `.codex/docs/waves/wave-XX.md`
   - Portuguese: `.codex/docs/waves/pt-BR/wave-XX.md`

Always write the English version first, then derive the Portuguese version from it.

## Output rules

- Keep the document short and practical.
- Prefer sections like:
  - `Wave Goal`
  - `Short Flow`
  - `Main Call Direction Between Modules`
  - `Central Idea Of Each Module`
  - `What This Wave Does Not Cover Yet`
  - `Practical Reading Of The Design`
- Include a compact Mermaid flowchart when it helps.
- The English file is the canonical template for the wave.
- The Portuguese file should match the English structure closely.
- Do not invent features that were not implemented.
- Do not describe future waves as if they already exist.
- Keep the text architecture-aware:
  - Actions are the main use-case boundary
  - controllers and Livewire are future entrypoints unless already implemented
  - avoid implying payment automation or overexpanded checkout behavior

## Source of truth

When there is a conflict, prefer:

1. implemented code
2. `.codex/docs/tasks.md`
3. existing English wave docs for structure and tone

## Git and storage rules

- Save the English document under `.codex/docs/waves/`.
- Save the Portuguese document under `.codex/docs/waves/pt-BR/`.
- Assume the Portuguese files may be local-only and ignored by git.
- Do not move or rename existing English wave files unless the user explicitly asks.

## Naming rules

- Use the format `wave-01.md`, `wave-02.md`, `wave-03.md`, and so on.
- Match the wave number being documented.
- Preserve zero padding for single-digit wave numbers.

## Content checklist

Before saving, confirm the document answers:

- What did this wave build?
- How do the main modules call each other?
- What is the central idea of each module?
- What is expected from each module now?
- What is still intentionally missing?

## Scope discipline

This skill documents completed work. It does not implement business features by itself unless the user also asked for implementation.
