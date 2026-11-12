# Architecture

Rules with an ID are enforced by PHPStan (`vendor/bin/phpstan`) or by the architecture tests in `tests/Unit`. The error message starts with the same ID.

These rules override conflicting advice in skills (laravel-best-practices, testing-best-practices): no mocks in tests, no code comments, Carbon expressions for cache TTL, no `Action` suffix, no service classes for business logic.

## Layers

- A1: Database writes only in actions (`app/Actions`). Controllers, Livewire components, commands, jobs and listeners call an action, never `save()`, `update()`, `create()`, `delete()` or `DB::transaction()` directly.
- R1: Database reads only in query classes (`app/Queries`). Controllers, Livewire components, commands, jobs and listeners call a query's `get()`, never `Model::query()`, `Model::find()`, `DB::table()` or relation queries directly. `Auth::user()` and route model binding are fine.
- Q1: Queries only read. No writes, transactions, dispatch, events, mail or HTTP calls.
- T1: No side effects (HTTP and API clients, dispatch, events, mail, notifications) inside `DB::transaction()`. Run them after the transaction or defer them with `afterCommit()`.

## Actions

- Business logic (several steps, several models, side effects, non-trivial decisions) always goes into an action, even with a single caller. A few lines of logic stay inline until a second caller appears.
- An action is a `final readonly` class with a single public `execute()` method and dependencies injected as private promoted properties. Create it with `php artisan make:action "{Name}" --no-interaction`.
- Wrap writes to more than one model in `DB::transaction()` inside the action.
- P1: When two or more actions need the same private method, extract it into its own action and inject it.

## Queries

- A query is a `final readonly` `{Name}Query` class in `app/Queries` with a single public `get()` method. Create it with `php artisan make:query "{Name}" --no-interaction`.

## DTOs

- D1: Actions and queries never take or return an associative array. Use a DTO from `app/Dtos`, a model, a collection or a `list<...>`. API clients in `app/Services` never return an associative array.
- A DTO is a `final readonly` `{Name}Dto` class with typed promoted constructor properties. Create it with `php artisan make:dto "{Name}" --no-interaction`.

## Naming

- A class name names a domain concept. Prefix an ambiguous name with its feature (`{Feature}Config`, `{Feature}Item`, `{Feature}Rules`). Generic reusable classes (API clients, base models) stay without a feature name.
- `app/Actions`, `app/Dtos` and `app/Enums` are flat. The feature lives in the class name, not in a subfolder.
- Variables use full words, never single letters or abbreviations.
- A variable, parameter or injected dependency holding an object is named after its class in camelCase (`CreateUser $createUser`). Use role names only when one scope holds several objects of the same class (`User $sender, User $recipient`).

## Tests

- Tests mirror `app/`: the test for `app/Http/Controllers/Settings/ProfileController.php` is `tests/Feature/Http/Controllers/Settings/ProfileControllerTest.php` (or the same path under `tests/Unit`). Every action, query, command, controller, middleware, Livewire component, job, listener, service and model has its own test file there.

## Code style

- C1: Cache, rate limiter and signed URL expirations are inline Carbon expressions (`now()->addMinutes(15)`), never an integer or a class constant.
- Validation rules for strings, numbers, arrays and files always have both `min:` and `max:`.
- Separate each logical step in a method with a blank line.
- Non-sensitive settings live in `config/`, never in `.env`. `.env` holds secrets only.

## Not in this repo

- No Horizon, Telescope, Pulse or Sentry. Errors go to Slack through `marekmiklusek/laravel-slack-errors`.
- Queues run through the scheduled `queue:work` in `routes/console.php`, not a long-running worker.
- Tests run on MySQL (`testing` database), not SQLite.
