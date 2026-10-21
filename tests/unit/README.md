# Unit Tests

Unit tests cover isolated PHP logic that does not require a WordPress runtime or a database. They are the fastest feedback lane for helpers, normalization, formatting, and other deterministic behaviour.

For common commands, safety rules, and test-lane selection, see the [test guide](../README.md).

## Run the lane

```bash
composer test:unit
```

The repository pre-commit hook runs this command for staged changes. You can also run it directly while developing.

## Scope and placement

Add tests under `tests/unit/` when production code can be exercised without bootstrapping WordPress or connecting to MySQL. Typical examples include:

- CSV normalization and formula-injection protection;
- pagination and input normalization;
- email-template inventories and formatting helpers;
- settings behaviour that can be tested in memory.

If the behaviour needs WordPress hooks, options, post data, capabilities, or a database, use the [integration lane](../integration/README.md) instead.

## Conventions

- Name test files after the unit or behaviour they cover.
- Keep test data local to the test and use synthetic values.
- Assert observable behaviour, including edge cases and error results.
- Avoid reflection for new tests when a narrow testable API can be exposed without broadening production behaviour.
