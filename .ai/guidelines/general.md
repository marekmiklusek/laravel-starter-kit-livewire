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
- **Always Both Bounds:** Whenever it makes any sense, define **both** `min:` and `max:` — never only one, never neither. This applies to strings (length), numerics (value), and arrays (size).

```php
// ❌ Wrong — no bounds
'name' => ['required', 'string'],

// ❌ Wrong — only max
'name' => ['required', 'string', 'max:255'],
'age' => ['required', 'integer', 'max:120'],
'tags' => ['required', 'array', 'max:10'],

// ✅ Correct — both bounds
'name' => ['required', 'string', 'min:1', 'max:255'],
'age' => ['required', 'integer', 'min:0', 'max:120'],
'tags' => ['required', 'array', 'min:1', 'max:10'],
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

### 9. Cache & TTL
- **No TTL Constants:** Never define TTL values as class constants at the top of a file (e.g. `private const TTL = 3600;`).
- **Requirement:** Always express TTL inline with a `Carbon` interval at the call site: `now()->addDay()`, `now()->addMinutes(15)`, `now()->addHour()`, etc.
- **Applies To:** `Cache::remember()`, `Cache::put()`, `RateLimiter`, and any other API taking a TTL.

```php
// ❌ Wrong
final class SomeService
{
    private const CACHE_TTL = 86400;

    public function execute(): mixed
    {
        return Cache::remember('key', self::CACHE_TTL, fn () => $this->compute());
    }
}

// ✅ Correct
final class SomeService
{
    public function execute(): mixed
    {
        return Cache::remember('key', now()->addDay(), fn () => $this->compute());
    }
}
```