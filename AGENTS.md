# Repository Agent Instructions

These instructions apply to every AI or automated coding agent working in this repository.

## Database safety

- Do not execute any database-changing action.
- Database access is limited to operations that are conclusively read-only.
- Never run migrations, rollbacks, seeders, factories, data imports, write queries, database jobs, or application actions that may insert, update, delete, merge, truncate, create, alter, or drop database data or schema.
- Do not use a transaction or intended rollback as a workaround for a prohibited database action.
- Do not run tests, Artisan commands, HTTP requests, browser flows, queue workers, or scripts unless it is certain that they cannot write to a database.
- If an action's database behavior is uncertain, do not run it. Explain what a human operator must execute manually and what remains unverified.
- Any stricter database restriction inherited from a parent `AGENTS.md` remains in force. In particular, when a parent instruction prohibits read-only database access too, do not query the database.

## PDIMS application handoff

- After making any source change in this repository, explicitly ask the user whether the same or an adapted change should also be applied to the main PDIMS application at:

  `C:\laragon\www\emr2\pdims`

- Do not automatically copy or apply the change to the PDIMS application without the user's confirmation.
- When asking, briefly identify which changes would be carried over and whether adaptation is likely to be required.
