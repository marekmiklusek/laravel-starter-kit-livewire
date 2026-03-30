### 1. Comments
- **No Redundant Comments:** Do not write explanatory comments (e.g., `// Comment...`) inside the code. The code should be self-documenting.

### 2. Error Handling
- **Variable Naming:** In `try/catch` blocks, **never** use `\Throwable $e`.
- **Requirement:** Always use `Throwable $throwable`.

### 3. Testing Strategy
- **No Application Code Modifications:** Do not modify application code to facilitate testing. Tests should interact with the application as it is.
- **Fakes over Mocks:** **Never** use Mocking (Mockery).
- **Requirement:** Always use **Fakes** (e.g., `Event::fake()`, `Bus::fake()`, `Storage::fake()`) or concrete implementations.

### 4. Action Classes
- **Pattern:** This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- **Location & Naming:** Actions live in `app/Actions`, and they are named based on what they do with the `Action` suffix (e.g., `CreateUserAction`).
- **Reuse Scope:** Actions may be called from many places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- **Method Contract:** Create dedicated Action classes for business logic with a single `execute()` method.
- **Dependencies:** Inject dependencies via the constructor using private properties.
- **Generation:** Create new actions with `php artisan make:action "{name}" --no-interaction`.
- **Transactions:** Wrap complex operations in `DB::transaction()` within actions when multiple models are involved.
- **No Dependencies Case:** Some actions do not require constructor dependencies and can use only the `execute()` method.

### 5. Configuration & Migrations

#### Configuration Access
- **Typed Helpers:** Never use `config()` directly. Always use typed accessors:
  - `config()->string('key')` for string values
  - `config()->integer('key')` for integer values
  - `config()->array('key')` for array values

#### Migration Defaults 
- **No Default Values in Migrations:** Never define default column values (e.g., `->default(0)`) in migration files.
- **Requirement:** Define all default values in application code (model casts, constructors, Action classes, etc.).