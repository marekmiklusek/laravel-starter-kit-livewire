<laravel-boost-guidelines>
=== .ai/general rules ===

### 1. Comments

- **No Redundant Comments:** Do not write explanatory comments (e.g., `// Comment...`) inside the code. The code should be self-documenting.

### 2. Error Handling

- **Variable Naming:** In `try/catch` blocks, **never** use `\Throwable $e`.
- **Requirement:** Always use `Throwable $throwable`.

```php
// ❌ Wrong
try {
    // ...
} catch (\Throwable $e) {
    // ...
}

// ✅ Correct
try {
    // ...
} catch (Throwable $throwable) {
    // ...
}
```

### 3. Testing Strategy

- **No Application Code Modifications:** Do not modify application code to facilitate testing. Tests should interact with the application as it is.
- **Fakes over Mocks:** **Never** use Mocking (Mockery).
- **Requirement:** Always use **Fakes** (e.g., `Event::fake()`, `Bus::fake()`, `Storage::fake()`) or concrete implementations.

```php
// ❌ Wrong
$mock = Mockery::mock(SomeService::class);

// ✅ Correct
Event::fake();
Bus::fake();
Storage::fake();
```

### 4. Action Classes

- **Pattern:** This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- **Location & Naming:** Actions live in `app/Actions`, and they are named based on what they do **without** any suffix (e.g., `CreateUser`, not `CreateUserAction`).
- **Reuse Scope:** Actions may be called from many places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- **Extraction Trigger:** Move logic into an Action class as soon as it is used in more than one place. A single call site can stay inline; the second call site is the signal to extract — duplication is the threshold, not speculation.
- **Method Contract:** Create dedicated Action classes for business logic with a single `execute()` method.
- **Dependencies:** Inject dependencies via the constructor using private properties.
- **Generation:** Create new actions with `php artisan make:action "{name}" --no-interaction`.
- **Transactions:** Wrap complex operations in `DB::transaction()` within actions when multiple models are involved.
- **No Dependencies Case:** Some actions do not require constructor dependencies and can use only the `execute()` method.

```php
final readonly class CreateUser
{
    public function execute(CreateUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            // ...
        });
    }
}
```

### 5. Configuration & Migrations

#### Configuration Access

- **Typed Helpers:** Never use `config()` directly. Always use typed accessors:
    - `config()->string('key')` for string values
    - `config()->integer('key')` for integer values
    - `config()->array('key')` for array values

```php
// ❌ Wrong
$value = config('app.name');

// ✅ Correct
$value = config()->string('app.name');
$count = config()->integer('app.count');
$items = config()->array('app.items');
```

#### Migration Defaults

- **No Default Values in Migrations:** Never define default column values (e.g., `->default(0)`) in migration files.
- **Requirement:** Define all default values in application code (Action classes, Controller classes, etc.).

```php
// ❌ Wrong (in migration)
$table->integer('count')->default(0);

// ✅ Correct (in migration — no default)
$table->integer('count');
```

### 6. Validation

- **String Minimum Length:** When validating a `string` field, always include the `min:` rule.

```php
// ❌ Wrong
'name' => ['required', 'string'],

// ✅ Correct
'name' => ['required', 'string', 'min:1'],
```

### 7. Code Spacing

- **Breathing Room:** Use blank lines to visually separate logical units inside methods. Code should read as distinct "thoughts", not as one dense block.
- **Before Control Structures:** Always put a blank line before `foreach`, `if`, `for`, `while`, `switch`, `try`, and `return` when they follow other statements.
- **Between Logical Steps:** Separate variable preparation, conditional checks, transformations, and assignments with blank lines.

```php
// ❌ Wrong — dense, hard to scan
/** @var Collection<int, array<int, string>> $rows */
$rows = Collection::make();
foreach ($parameters as $parameter) {
    // ...
}

// ✅ Correct — blank line before the loop
/** @var Collection<int, array<int, string>> $rows */
$rows = Collection::make();

foreach ($parameters as $parameter) {
    // ...
}
```

```php
// ❌ Wrong — multiple logical steps glued together
$entry = trim($entry);
if ($entry === '') {
    continue;
}
[$filterName, $valueName] = array_pad(explode('=', $entry, 2), 2, '');
if ($filterName === '' || $valueName === '') {
    continue;
}
$out[$filterName] = $valueName;

// ✅ Correct — each logical step separated
$entry = trim($entry);

if ($entry === '') {
    continue;
}

[$filterName, $valueName] = array_pad(explode('=', $entry, 2), 2, '');

if ($filterName === '' || $valueName === '') {
    continue;
}

$out[$filterName] = $valueName;
```

### 8. Block Body Style

- **No Empty Braces:** Never use empty inline braces `{}`.
- **Always Open Body:** The opening `{` and closing `}` always go on separate lines, with `// ...` (or actual code) inside — even when the body would otherwise be empty.
- **Applies Everywhere:** Constructors, methods, classes, closures — all blocks follow this style.
```php
// ❌ Wrong
final readonly class SomeController
{
    public function __construct(private CreateUser $createUser) {}
}
 
// ✅ Correct
final readonly class SomeController
{
    public function __construct(private CreateUser $createUser)
    {
        // ...
    }
}
```

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

</laravel-boost-guidelines>
