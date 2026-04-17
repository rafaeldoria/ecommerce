---
name: wave-git-close
description: Finalize a completed wave in git for this ecommerce workspace. Use when implementation and validation are done and Codex must safely finish the branch workflow: confirm the current branch is wave-related and not master, review modified files, group changes into the best ordered commits, verify nothing important was left out, and push only after rechecking that the branch is not master.
---

# Wave Git Close

Use this skill only at the end of a wave, after the implementation and validations are already complete.

This skill exists to perform exactly this git closing flow:

1. confirm the current branch is related to the wave
2. confirm it is **not** `master`
3. inspect the modified files
4. group and create the commits in the best order
5. verify nothing important was left out
6. push the branch
7. confirm again before push that the branch is **not** `master`

## Required workflow

1. Read `.codex/environment.md`.
2. Run `git branch --show-current`.
3. Stop immediately if the branch is `master`.
4. Stop if the branch does not look related to the current wave or task.
5. Inspect:
   - `git status --short`
   - `git diff --stat`
   - any important touched files needed to understand the grouping
6. Create the commits in the best reviewable order.
7. Re-run `git status --short` and confirm no intended tracked changes were forgotten.
8. Re-check the branch with `git branch --show-current`.
9. Push with upstream tracking when needed.

## Hard safety rules

- Never commit or push from `master`.
- Re-check the branch immediately before push.
- If the branch is `master`, stop and report it.
- If the branch naming does not look wave-related, stop and report it.
- Do not use destructive git commands.
- Do not amend commits unless the user explicitly asks.
- Do not include ignored local-only files by accident.

## Commit grouping rules

- Prefer grouping by responsibility, not by raw file order.
- A good default order is:
  - domain or module commits first
  - cross-cutting docs/config/convention commits last
- Keep each commit reviewable and internally coherent.
- If one shared file supports a module-specific change, include it in the most appropriate commit.
- If the wave produced docs or conventions that describe all modules, prefer a final docs/conventions commit.

## Required checks before push

Before pushing, explicitly verify:

- the branch is not `master`
- the commit sequence is ordered and readable
- there is no forgotten tracked file in `git status --short`
- ignored local-only files remain ignored when appropriate

## Output expectations

When reporting the result, include:

- the branch name
- the commit list in order
- confirmation that push completed

## Scope discipline

This skill is for the final git workflow only.
It should not implement product code, rewrite history broadly, or perform unrelated cleanup.
