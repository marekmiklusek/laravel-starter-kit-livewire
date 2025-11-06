# 🚀 Laravel Livewire Starter Kit

**The Official Laravel Livewire Starter Kit** with enhanced development workflow and code quality tools.

Based on [Laravel's official Livewire starter kit](https://laravel.com/docs/12.x/starter-kits#livewire) with additional developer experience improvements from [laravel-starter-kit](https://github.com/marekmiklusek/laravel-starter-kit). ✨

## 📋 Requirements

- PHP >= 8.4.0
- Composer
- Node.js & NPM
- MySQL (or your preferred database)

## 🚀 Quick Start

### 📦 Installation

Create a new Laravel Livewire project:

```bash
composer create-project marekmiklusek/laravel-starter-kit-livewire --prefer-dist app-name
```

Run the automated setup script:

```bash
composer setup
```

This command will:
1. Install PHP dependencies via Composer
2. Create `.env` file from `.env.example` (if not exists)
3. Create `.env.production` file from `.env.example` (if not exists)
4. Generate application key
5. Run database migrations
6. Install NPM dependencies
7. Build frontend assets

### ⚙️ Additional Setup

#### 🔧 Environment Configuration

After running `composer setup`, configure your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 🌐 Browser Testing Setup (Optional)

If you plan to use Pest's browser testing capabilities, install Playwright:

```bash
npm install playwright
npx playwright install
```

This installs the necessary browser binaries for running browser tests.

#### 🚀 Production Environment

The setup script automatically creates a `.env.production` file. Configure it with production-specific settings:

```bash
# Edit .env.production with your production settings
```

Configure production environment variables:
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Configure production database credentials
- Set secure `APP_KEY` (generated during setup)
- Configure mail, cache, queue, and session drivers
- Set proper logging channels

## 💻 Development

### 🖥️ Running the Development Server

Start all development services concurrently:

```bash
composer dev
```

This starts:
- **Laravel development server** (port 8000) - Your Livewire application
- **Queue listener** - Background job processing
- **Log viewer (Pail)** - Real-time log monitoring
- **Vite dev server** - Hot Module Replacement for CSS/JS

Your Livewire application will be available at `http://localhost:8000` 🎉

## 🔍 Code Quality

### 🧹 Linting & Formatting

Fix code style issues:

```bash
composer lint
```

This runs:
- Rector (PHP refactoring)
- Laravel Pint (PHP formatting)
- Prettier (frontend formatting)

### 🧪 Testing

Run the full test suite:

```bash
composer test
```

This includes:
- Type coverage (100% minimum)
- Unit and feature tests (Pest)
- Code style validation
- Static analysis (PHPStan)

### 🌐 Browser Testing

This starter kit includes Pest 4 with browser testing capabilities. Create browser tests in `tests/Browser/`:

```php
it('displays the welcome page', function () {
    $page = visit('/');
    
    $page->assertSee('Laravel')
        ->assertNoJavascriptErrors();
});
```

### 🧪 Testing Livewire Components

Test your Livewire components with Pest's built-in Livewire testing:

```php
use Livewire\Livewire;

it('can update user profile', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(\App\Livewire\Settings\Profile::class)
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('profile-updated');
        
    expect($user->fresh()->name)->toBe('New Name');
});
```
## 📜 Available Scripts

### 🎼 Composer Scripts

- `composer setup` - Initial project setup
- `composer dev` - Run all development services
- `composer lint` - Fix code style issues
- `composer test` - Run full test suite
- `composer test:unit` - Run Pest tests only
- `composer test:types` - Run PHPStan analysis
- `composer test:type-coverage` - Check type coverage
- `composer test:lint` - Validate code style
- `composer update:requirements` - Update all dependencies

### 📦 NPM Scripts

- `npm run dev` - Start Vite dev server
- `npm run build` - Build for production
- `npm run lint` - Format frontend code
- `npm run test:lint` - Check frontend code style

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
