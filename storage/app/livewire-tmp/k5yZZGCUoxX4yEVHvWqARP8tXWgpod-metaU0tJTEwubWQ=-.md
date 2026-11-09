---
name: gamemodes-pawn
description: Work on the Greenside Open.mp Pawn gamemode and its included feature modules.
---

# Gamemodes Skill

Use this skill for changes under `gamemodes/`, especially `som.pwn` and `modules/`. This is an Open.mp/SAMP-compatible Pawn roleplay gamemode.

## Project Shape

- `som.pwn` is the main production gamemode. It defines compiler and feature macros, imports shared includes, declares shared state, and textually includes modules. Treat its include order and configuration as part of the runtime contract.
- `modules/<feature>/` groups feature implementation. A feature may span several `.pwn` files, and each required file must be included by `som.pwn`.
- The main `public OnGameModeInit()` explicitly calls module initialization functions. When adding lifecycle work, add the module function and wire it into the central callback in the appropriate order. Check the corresponding player connect, disconnect, and exit paths as well.
- `test.pwn` is a separate source file; do not assume it is the production entry point.

## Editing Conventions

- Follow the nearest module's naming, indentation, and layout. The repository contains both tabs and spaces; avoid formatting unrelated code.
- Use the project's established Pawn constructs and tags, including `stock`, `function:`, `Task:`, `String:`, `Pool:`, enums, and tagged handles where appropriate. Keep public APIs and shared enum/array layouts compatible with their callers.
- Keep feature state and helpers in the owning module. Before adding shared globals, constants, forwards, or callbacks, search `som.pwn` and nearby modules for an existing definition or integration pattern.
- Module files are textually included, so avoid duplicate global/function definitions and account for declaration/include order. Do not add another public callback with a name already provided by the gamemode or a plugin; inspect existing hook/callback patterns first.
- Use existing database helpers and the established MySQL APIs (`mysql_tquery`, `mysql_aquery`, and their string variants) consistently with the operation. Keep queries asynchronous where the surrounding flow expects it, and use the project's existing escaping/query-building conventions for user-controlled values.
- Do not copy database credentials or other secrets into new code, examples, or documentation. Avoid changing database schema or shared gameplay configuration unless the task requires it.

## Validation

- For production changes, compile `gamemodes/som.pwn` using the workspace's **build** task with that file active. Compiling an included module by itself is not a valid integration check.
- Resolve compiler errors at their source and re-run the build. For changes that affect runtime behavior or database contracts, also inspect the relevant call paths and schema/migration code; compilation alone does not validate those behaviors.
- Keep generated `.amx` files and other build artifacts out of source changes unless explicitly requested.
