# Xibra API

**The core backend API for Xibra Platform.**

Xibra API is a RESTful backend service built with Laravel and PostgreSQL. It serves as the central backend for the Xibra ecosystem, providing authentication, portfolio management, personal productivity, financial management, and external integrations.

---

## Overview

Xibra Platform is an integrated personal digital ecosystem designed to combine professional portfolio management and personal management into one platform.

Xibra API acts as the central backend responsible for:

- Application business logic.
- Data storage and management.
- Authentication and authorization.
- API communication between applications.
- Personal productivity and financial operations.
- External service integrations.

The API is designed to support multiple clients through a centralized and versioned REST API architecture.

## Core Modules

The following modules define the planned scope of Xibra API. They will be implemented incrementally.

### 1. Authentication & User Management

- User accounts and profiles.
- API authentication.
- Authorization and access control.
- Account management.
- Session and token management.

### 2. Portfolio Management

- Personal profile.
- Professional experience.
- Education.
- Skills and certifications.
- Projects and project categories.
- Blog and articles.
- Resume management.
- Contact messages.

### 3. Personal Management

- Tasks and todos.
- Personal schedules.
- Reminders and deadlines.
- Personal projects.
- Productivity tracking.

### 4. Work Management

- Work schedules.
- Shift management.
- Work activity tracking.
- Work-related reminders.

### 5. Financial Management

- Income and expenses.
- Transactions.
- Budgets.
- Savings.
- Financial reports.

### 6. Notifications

- Application notifications.
- Reminder notifications.
- Scheduled notifications.
- External notification services.

### 7. External Integrations

- Telegram Bot.
- Hermes AI.
- Future AI services.
- External application integrations.

---

## Technology Stack

| Technology | Version / Usage |
|---|---|
| PHP | 8.5.11 (local development) |
| Laravel | 13.x |
| PostgreSQL | 18.6 (local development) |
| Composer | 2.10.3 (local development) |
| Laravel Sanctum | Planned API authentication |
| REST API | API architecture |
| Postman | API testing |

Technology versions may differ between development and production environments.

## Architecture

Xibra API follows a centralized backend architecture designed to support multiple applications.

### Connected Applications

| Application | Description |
|---|---|
| Xibra Web | Public portfolio website and private dashboard |
| Xibra Personal | Personal management interface |
| Xibra Bot | Telegram integration with Hermes AI |
| Future Applications | Android and other external clients |

### Architecture Overview

```text
                  XIBRA PLATFORM
                         |
                    XIBRA API
                 Laravel REST API
                         |
                   PostgreSQL
                         |
       +-----------------+-----------------+
       |                 |                 |
    Xibra Web        Xibra Bot       Future Apps
       |                 |                 |
  Portfolio &        Telegram          Android
  Dashboard          & Hermes          & Others
```

All connected applications communicate with the central backend through versioned API endpoints.

## API Structure

The API uses versioned endpoints to maintain compatibility and support future development.

**Base API Prefix**

```text
/api/v1
```

### Planned Endpoint Groups

```text
/api/v1/auth
/api/v1/users

/api/v1/portfolio
/api/v1/projects

/api/v1/tasks
/api/v1/todos
/api/v1/schedules
/api/v1/reminders

/api/v1/finance
/api/v1/notifications
```

Endpoints will be introduced as their respective modules are implemented.

### API Response Convention

The project intends to use a consistent JSON response structure.

Example:

```json
{
  "status": "success",
  "code": 200,
  "message": "Request successful",
  "data": {}
}
```

This response convention is a planned API standard and will be implemented as the API architecture develops.

## Project Structure

The Laravel project follows the standard Laravel application structure.

```text
xibra-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   └── Services/
│
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
└── README.md
```

Additional directories and modules will be introduced as the application grows.

## Development Environment

Xibra API is developed locally before deployment to the production VPS.

### Requirements

- PHP 8.3 or newer, compatible with Laravel 13.
- Composer.
- PostgreSQL.
- Required PHP extensions.
- Git.
- Postman (recommended for API testing).

### Local Development Environment

The initial development environment has been tested with:

- Windows.
- PHP 8.5.11.
- Composer 2.10.3.
- PostgreSQL 18.6.
- Git 2.55.0.

### Installation

Clone the repository:

```bash
git clone https://github.com/rizky66ibrahim/xibra-api.git
```

Navigate to the project directory:

```bash
cd xibra-api
```

Install PHP dependencies:

```bash
composer install
```

Create the environment configuration:

**Windows PowerShell:**

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### Environment Configuration

Configure the following variables in your `.env` file:

```env
APP_NAME="Xibra API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=xibra_db
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

Use your own local PostgreSQL credentials.

**Important:** Never commit `.env` or expose database credentials, application keys, or other secrets in the repository.

### Database Migration

After configuring the database connection, run:

```bash
php artisan migrate
```

To check the migration status:

```bash
php artisan migrate:status
```

### Start Development Server

Run the Laravel development server:

```bash
php artisan serve
```

The application will be available at:

```text
http://127.0.0.1:8000
```

---

## API Testing

Postman will be used to test and validate API functionality.

Testing will cover:

- HTTP status codes.
- Standardized JSON response structures.
- Authentication and authorization.
- Request validation.
- CRUD operations.
- Error handling.
- Database consistency.
- API security.

A Postman Collection and environment configuration will be maintained as the API grows.

## Security Principles

Security is an important part of Xibra API development.

The project follows these principles:

- Keep sensitive environment variables outside version control.
- Use secure authentication and authorization.
- Validate incoming requests.
- Apply appropriate access controls.
- Protect private user data.
- Avoid exposing internal application errors in production.
- Use HTTPS in production.
- Keep dependencies updated.

Laravel Sanctum is planned for API authentication.

## Development Principles

- Modular and maintainable architecture.
- Consistent API response structures.
- Secure authentication and authorization.
- Input validation and error handling.
- Versioned API endpoints.
- Database integrity and consistency.
- Incremental implementation and testing.
- Documentation maintained alongside development.
- Separation of development and production environments.

## Deployment

The project is developed and tested locally before being deployed to a production VPS.

Production deployment will include:

- Linux server environment.
- Nginx.
- PHP-FPM.
- PostgreSQL.
- Environment-specific configuration.
- HTTPS and SSL.
- Database backup strategy.
- Application monitoring.

Production infrastructure and deployment procedures will be documented separately.

## Project Roadmap

| Priority | Module | Status |
|---|---|---|
| P0 | Laravel foundation | In Progress |
| P0 | PostgreSQL integration | Initial setup completed |
| P0 | Core API architecture | Planned |
| P0 | Authentication | Planned |
| P1 | Portfolio management | Planned |
| P1 | Project management | Planned |
| P2 | Personal management | Planned |
| P2 | Work and shift management | Planned |
| P3 | Financial management | Planned |
| P4 | Telegram and Hermes integration | Planned |
| P4 | External application integrations | Planned |

## Project Status

**Early Development — Initial Setup**

The Laravel foundation has been installed and the PostgreSQL database connection has been established successfully.

The initial Laravel migrations have also been executed successfully.

Core API architecture, authentication, business modules, and external integrations will be implemented incrementally.

## License

License to be determined.

---

**Xibra Platform**  
Centralized backend for an integrated personal digital ecosystem.

Repository: https://github.com/rizky66ibrahim/xibra-api