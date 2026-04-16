---
name: environment-first
description: Ensure the workspace environment instructions are loaded before any work in /home/r00t/projects/ecommerce/src. Use whenever the request mentions this directory, refers to the ecommerce src workspace, or requires inspecting files, reading code, searching text, analyzing behavior, debugging, testing, or editing anything under /home/r00t/projects/ecommerce/src, so Codex first opens /home/r00t/projects/ecommerce/src/.codex/environment.md via WSL and follows it as the standing rule for the task.
---

# Environment First

Always start by reading `/home/r00t/projects/ecommerce/src/.codex/environment.md` via WSL before doing anything else in this workspace.

Use this rule for any task related to `/home/r00t/projects/ecommerce/src`, including:

- inspecting files
- reading code
- searching text
- analyzing behavior
- running commands
- debugging
- executing tests
- editing code

Follow this sequence:

1. Open `/home/r00t/projects/ecommerce/src/.codex/environment.md` via WSL.
2. Use the file as the source of truth for environment-specific instructions.
3. Only then continue with the requested task.

Do not skip this step, even for small changes or simple checks.
