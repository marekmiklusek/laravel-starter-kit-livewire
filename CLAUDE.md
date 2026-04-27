# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the official Laravel starter kit using Livewire v3, Volt, and Flux UI components. It provides a complete authentication system with Laravel Fortify including registration, login, password reset, email verification, and two-factor authentication.

**Key Technologies:**
- Laravel 12 (latest streamlined structure)
- Livewire 3 with Volt (single-file components)
- Flux UI (Livewire component library)
- Tailwind CSS v4
- Pest v4 (testing with browser support)
- Laravel Fortify (authentication)

## Development Commands

### Running the Application
```bash
# Start all services concurrently (server, queue, vite)
composer run dev

# Alternative: Run services individually
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

### Testing
```bash
# Run all tests (includes type coverage, unit, lint, types)
composer test

# Run only unit/feature/browser tests
composer run test:unit
# Or: php artisan test

# Run specific test file
php artisan test tests/Browser/WelcomeTest.php

# Run tests matching a filter
php artisan test --filter=testName

# Check type coverage (must be 100%)
composer run test:type-coverage
# Or: pest --type-coverage --min=100

# Run type checking with PHPStan
composer run test:types
# Or: vendor/bin/phpstan
```

### Code Quality
```bash
# Format all code (Rector + Pint + Prettier)
composer run lint

# Run linting tests (checks formatting without fixing)
composer run test:lint

# Format PHP only with Pint
vendor/bin/pint

# Format specific files with Pint
vendor/bin/pint --dirty

# Check Pint formatting
vendor/bin/pint --test

# Format frontend code with Prettier
npm run lint

# Run Rector refactoring
vendor/bin/rector
```

### Setup
```bash
# Initial setup (install dependencies, create .env, generate key, migrate, build assets)
composer run setup

# Update dependencies
composer run update:requirements
```

## Architecture & Patterns

### Laravel 12 Streamlined Structure
This project uses Laravel 12's simplified structure:
- **No `app/Console/Kernel.php`** - Commands auto-register from `app/Console/Commands/`
- **No `app/Http/Kernel.php`** - Middleware configured in `bootstrap/app.php`
- **`bootstrap/app.php`** - Central configuration for middleware, exceptions, routing
- **`bootstrap/providers.php`** - Application-specific service providers

### Authentication Flow
- **Laravel Fortify** handles all authentication logic (no controllers needed)
- Fortify configured in `config/fortify.php`
- Fortify actions in `app/Actions/Fortify/` (CreateNewUser, ResetUserPassword, etc.)
- Routes automatically registered by Fortify (login, register, password reset, etc.)
- Fortify views disabled in favor of Livewire components in `resources/views/livewire/auth/`

### Livewire Component Organization
```
app/Livewire/
  Actions/         # Reusable actions (e.g., Logout)
  Settings/        # Settings page components
    Profile.php
    Password.php
    TwoFactor.php
    Appearance.php
    DeleteUserForm.php
    TwoFactor/     # Sub-components
      RecoveryCodes.php

resources/views/livewire/
  auth/            # Authentication views (login, register, etc.)
  settings/        # Settings views matching app/Livewire/Settings
```

**Livewire Pattern:**
- Class-based Livewire components (not using Volt for complex components)
- Each component has matching Blade view in `resources/views/livewire/`
- Components handle their own validation inline (not using Form Requests for Livewire)
- Use `$this->validate()` directly in component methods
- Components dispatch events for cross-component communication (e.g., `$this->dispatch('profile-updated')`)

### View Layouts
```
resources/views/components/layouts/
  app.blade.php       # Main authenticated layout
  auth.blade.php      # Base auth layout
  app/
    sidebar.blade.php # Sidebar navigation
    header.blade.php  # App header
  auth/
    card.blade.php    # Card-style auth layout
    simple.blade.php  # Simple auth layout
    split.blade.php   # Split-screen auth layout
```

### Flux UI Components
- Custom Flux components in `resources/views/flux/`
- Flux navlist customization in `resources/views/flux/navlist/group.blade.php`
- Custom Flux icons in `resources/views/flux/icon/`
- Follow Flux conventions when creating new components

### Routing
- Web routes in `routes/web.php`
- Console routes in `routes/console.php`
- Most routes point to Livewire components directly using `ComponentName::class`
- Settings routes redirect `/settings` to `/settings/profile`
- Auth routes auto-registered by Fortify

## Code Standards

### PHP Strict Mode & Typing
- **All PHP files must use `declare(strict_types=1);`** (enforced by Pint)
- All methods must have explicit return type declarations
- Use PHP 8.4 constructor property promotion
- Type hint all parameters
- Follow array validation rules (check sibling files for string vs array format)

### Code Style Rules (Pint Configuration)
- Files must declare final classes where appropriate (`final_class: true`)
- Import all classes, constants, and functions (`global_namespace_import`)
- Strict comparisons required (`strict_comparison: true`)
- Use array_push() instead of $array[] syntax
- DateTimeImmutable preferred over DateTime
- Specific class element ordering (traits, constants, properties, constructor, methods)
- Import sorting by length

### Rector Configuration
- Automatically refactors to Laravel best practices
- Uses bleeding-edge Laravel patterns
- Converts to modern Laravel collection/query builder methods
- Skips `AddOverrideAttributeToOverriddenMethodsRector`

### PHPStan Configuration
- **Level: max** (strictest analysis)
- Uses Larastan for Laravel-specific rules
- Analyzes: app, bootstrap/app.php, config, database, public, routes

### Testing Standards (Pest v4)
- All tests use Pest syntax (not PHPUnit)
- Browser tests in `tests/Browser/` using Pest v4's browser testing
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- All tests extend `TestCase` and use `RefreshDatabase`
- Global test setup in `tests/Pest.php`:
  - Random strings/UUIDs normalized
  - HTTP/Process stray requests prevented
  - Sleep faked
  - Time frozen for each test

**Browser Testing:**
- Use `visit()` function for browser tests
- Can use Laravel features like `Event::fake()`, model factories, `RefreshDatabase`
- Example: `visit('/')->assertSee('Laravel')->assertNoJavascriptErrors()`
- Browser screenshots stored in `tests/Browser/Screenshots/` (uploaded as CI artifacts)

**Test Assertions:**
- Use specific assertion methods: `assertSuccessful()`, `assertForbidden()`, `assertNotFound()`
- Never use generic `assertStatus(403)` when specific method exists
- Custom expect extensions defined in `tests/Pest.php` (e.g., `expect()->toBeOne()`)

## Tailwind CSS v4 Specific Notes

**Key Differences from v3:**
- Import using `@import "tailwindcss";` (not `@tailwind` directives)
- Opacity utilities changed: `bg-opacity-*` ’ `bg-black/*`
- Flex utilities: `flex-shrink-*` ’ `shrink-*`, `flex-grow-*` ’ `grow-*`
- Text utilities: `overflow-ellipsis` ’ `text-ellipsis`
- No `corePlugins` support in v4

**Styling Patterns:**
- Use `gap-*` utilities for spacing between items (not margins)
- Dark mode support using `dark:` prefix on all components
- Prettier configured with `prettier-plugin-tailwindcss` for class sorting

## Important Conventions

### When Creating New Features
1. Use `php artisan make:` commands to scaffold files
2. Pass `--no-interaction` flag to Artisan commands
3. For Livewire components, create both class and view
4. For models, create factory and seeder together
5. Check sibling files for existing patterns before creating new structures
6. Follow existing validation patterns (inline for Livewire, Form Requests for controllers)

### Validation
- Livewire components validate inline using `$this->validate()`
- Standard controllers use Form Request classes
- Check existing validation rules to match array vs string format

### Authentication & Authorization
- User model in `app/Models/User.php`
- Two-factor authentication fully implemented
- Email verification enabled
- Password confirmation for sensitive actions

### Configuration
- Never use `env()` outside config files
- Always use `config('key')` in application code
- Database: SQLite by default

### Testing Workflow
1. Write or update tests for every change
2. Run minimal tests with `php artisan test --filter=testName`
3. Ensure passing before finalizing
4. Run full suite with `composer test` before committing

## CI/CD
- GitHub Actions workflow in `.github/workflows/tests.yml`
- Runs on PHP 8.4 with Node 22
- Full test suite including Playwright browser tests
- Caches Rector, PHPStan, and Playwright browsers
- Uploads browser screenshots as artifacts

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- livewire/volt (VOLT) - v1
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domainâ€”don't wait until you're stuck.

- `fortify-development` â€” ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` â€” Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `fluxui-development` â€” Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
- `volt-development` â€” Develops single-file Livewire components with Volt. Activates when creating Volt components, converting Livewire to Volt, working with @volt directive, functional or class-based Volt APIs; or when the user mentions Volt, single-file components, functional Livewire, or inline component logic in Blade files.
- `pest-testing` â€” Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored â€” including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.

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

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== volt/core rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
