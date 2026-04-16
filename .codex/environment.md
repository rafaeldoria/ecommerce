# Workspace Environment Notes

- This project lives inside WSL on Windows.
- For inspection and read operations, prefer WSL-based commands rooted at `/home/r00t/projects/ecommerce/src`.
- Inside the `ecommerce` workspace, read-only inspection is considered safe and expected.
- This note should be treated as a standing workspace rule for future requests in this project.
- This project is executed via Docker containers running inside WSL.
- When containers are available, prefer running application commands and checks through the app container.
- Keep implementation changes scoped to the current task and avoid treating this note as a product document.
