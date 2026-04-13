# CLAUDE.md

## Coding style

- Never use fully qualified class names (e.g. `\RuntimeException`) inline. Always add a `use` import at the top of the file and reference the short class name.
- Do not use private scoped functions or variables.
- Use specific exception classes for specific cases that are to be caught and handled appropriately.
- Always add a docblock for a class or function explaining what it does and what possible side-effects it may have.
- Add a single line comment before blocks of code (e.g. `// comment` in PHP, `# comment` in Python, `{{-- comment --}}` in Blade), using capital characters only in names and abbreviations.
- Use wherever possible alphanumerical order.
- Prefer constants over function calls over expression on LHS of comparison expression (e.g. `STATUS_ACTIVE === $status` rather than `$status === STATUS_ACTIVE`).
- Vertically align array assignments, chained function calls, etc.

## Documentation

- After finishing a task that has modified any files, update documentation if applicable.

## Environment

### Python

- The python command to use is: python3

## Input validation

- Ensure to always validate input.

## Language

- Prefer British English spelling and capitalisation.

## Makefile

- Always redirect standard output of `cd` command to /dev/null, e.g. `cd >/dev/null some-directory`.

## Plans

- Save plans in docs/plans prefixing the filename with the date (e.g. 2026-03-13-this-is-a-plan.md).

## Review

- After finishing a task that has modified any files, double check the changes.

## Tests

- Create/update tests for added/updated/removed code/logic.
