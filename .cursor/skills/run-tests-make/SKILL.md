---
name: run-tests-make
description: Run project tests exclusively using "make test-unit" command. Use when the user asks to run tests, execute tests, check tests, verify code, or run the test suite. Never use direct test runner commands (phpunit, pytest, jest, etc.) — always use "make test-unit".
---

# Run Tests via Make

## Rule

Always run tests with:

```bash
make test-unit
```

Never use direct runner commands like `vendor/bin/phpunit`, `pytest`, `jest`, `npm test`, etc.

## When to Apply

- User asks to run / execute / check tests
- Verifying code after changes
- Any scenario that requires running the test suite
