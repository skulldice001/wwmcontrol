### Project Guidelines

#### Build & Configuration
This project is a hybrid Laravel (backend) and Next.js (frontend) application.

- **Backend (Laravel)**:
  - Standard Laravel structure.
  - Custom setup script available via composer: `composer setup`. This runs migrations, installs dependencies, and builds the frontend.
  - Environment variables are managed in `.env`.
  - Database: Primary database is PostgreSQL (configured in `phpunit.xml` for tests as well).

- **Frontend (Next.js)**:
  - Located in the `frontend/` directory.
  - To develop: `npm run dev` from the root (runs both backend and frontend via concurrently) or `cd frontend && npm run dev`.
  - Built using Next.js 16, React 19, and Tailwind CSS 4.
  - **Debugging**:
      - Backend: Set `APP_DEBUG=true` in `.env` for detailed Laravel error pages and logs.
      - Frontend: Set `NEXT_PUBLIC_DEBUG=true` in `.env` to enable API request/response tracing in the browser console (via `axios` interceptors).

#### Testing Information
- **Running Tests**:
  - Run all tests: `php artisan test`
  - Run specific test file: `php artisan test tests/Path/To/Test.php`
  - Note: Tests currently expect a PostgreSQL database named `wwm_test`. Ensure your local environment has this database set up or update `phpunit.xml` accordingly.
- **Adding New Tests**:
  - Unit tests: `php artisan make:test NameTest --unit`
  - Feature tests: `php artisan make:test NameTest`
- **Example Test**:
  ```php
  namespace Tests\Unit;
  use PHPUnit\Framework\TestCase;

  class GuidelinesCheckTest extends TestCase {
      public function test_guidelines_demo(): void {
          $this->assertTrue(true);
      }
  }
  ```

#### Additional Development Information
- **Discord Integration**: 
    - The project uses Laravel Socialite with the Discord provider for authentication.
    - **SSL Verification**: If you encounter `cURL error 60: SSL certificate problem` in your local environment (common on Windows), set `DISCORD_SSL_VERIFY=false` in your `.env` file. This is handled via the `guzzle` option in `config/services.php`.
- **Code Style**: Follow standard Laravel PSR-12 coding standards. Laravel Pint is included in `require-dev` for code style enforcement.
- **Environment**: Ensure you have PHP 8.2+, Node.js, and PostgreSQL installed.
