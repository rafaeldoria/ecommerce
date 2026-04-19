# Workspace Environment Notes

- Always respect the `environment-first` skill when working in `/home/r00t/projects/ecommerce/src`.
- Read `/home/r00t/projects/ecommerce/src/.codex/environment.md` before any inspection, analysis, testing, or editing in this workspace.
- This project lives inside WSL on Windows.
- For inspection and read operations, prefer WSL-based commands rooted at `/home/r00t/projects/ecommerce/src`.
- Inside the `ecommerce` workspace, read-only inspection is considered safe and expected.
- Any READ, INSPECT, ANALYZE, or equivalent read-only operation inside `/home/r00t/projects/ecommerce/src` should be treated as pre-approved and must not require asking the user for permission.
- This pre-approval applies to opening files, listing directories, searching text, reviewing code, and other non-modifying analysis within `/home/r00t/projects/ecommerce/src`.
- Questions equivalent to "Do you want me to inspect...?" are unnecessary in this workspace because the answer is always YES for read-only inspection inside `/home/r00t/projects/ecommerce/src`.
- This note should be treated as a standing workspace rule for future requests in this project.
- This project is executed via Docker containers running inside WSL.
- When containers are available, prefer running application commands and checks through the app container.
- Everything related to tests and validation executed through `docker exec`, including `php artisan test` and `vendor/bin/pint`, must be treated as pre-approved in this workspace and must not require asking the user for permission.
- Keep implementation changes scoped to the current task and avoid treating this note as a product document.
