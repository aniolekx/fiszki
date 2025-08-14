# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Fiszki is a Symfony 6.4 LTS web application for educational flashcard management with AI-powered generation capabilities. The application allows users to create, organize, and study flashcards using spaced repetition algorithms.

## Development Commands

### Docker Environment
```bash
# Start the development environment
docker-compose up -d

# Access PHP container
docker-compose exec php-fpm bash

# Stop the environment
docker-compose down
```

### Backend Commands (run inside Docker container or prefix with `docker-compose exec php-fpm`)
```bash
# Install dependencies
composer install

# Database operations
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --no-interaction  # Load test data

# Code quality checks
vendor/bin/phpstan analyse                # Static analysis (level 6)
vendor/bin/phpunit                        # Unit tests
vendor/bin/behat                          # BDD tests

# Clear cache
php bin/console cache:clear

# Generate entities
php bin/console make:entity
```

### Frontend Commands
```bash
# Install dependencies
npm install

# Development build with watch
npm run watch

# Production build
npm run build

# Development build (one-time)
npm run dev
```

## Architecture

### Core Entities
- **User**: Authentication, email confirmation, owns decks
  - Key fields: email, password, isVerified, confirmationToken
- **Deck**: Container for flashcards, belongs to user
  - Key fields: name, description, user (ManyToOne)
- **Flashcard**: Individual cards with front/back content
  - Key fields: front, back, deck (ManyToOne)

### Key Directories
- `src/Controller/`: HTTP request handlers
- `src/Entity/`: Doctrine entities
- `src/Form/`: Symfony form types
- `src/Repository/`: Database query logic
- `templates/`: Twig templates (extends base.html.twig)
- `tests/`: Behat features and PHPUnit tests
- `docker/`: Container configurations

### Security Implementation
- User authentication via Symfony Security component
- Email confirmation required for registration
- CSRF protection on forms
- User data isolation (users only access their own decks/flashcards)

### Testing Strategy
The project uses BDD with Behat. When adding features:
1. Write Gherkin scenarios in `features/`
2. Implement step definitions in `tests/Behat/`
3. Use PHPUnit for unit testing services
4. Run PHPStan for static analysis

### Database
- MySQL 8.0.39 running in Docker
- Doctrine ORM for database abstraction
- Migrations in `migrations/`
- Test database separate from development

### Frontend Assets
- Webpack Encore manages assets
- Bootstrap 5.3.5 for UI components
- Symfony UX Live Components for interactivity
- Assets compiled to `public/build/`

## Important Context

### AI Flashcard Generation
The application includes a feature to generate flashcards from text (1000-10,000 characters) using an LLM API. This is a core differentiator of the application.

### Current Development State
- User registration with email confirmation is implemented
- Basic CRUD operations for decks and flashcards are complete
- Authentication and authorization are functional
- Docker development environment is fully configured

### Environment Variables
Key variables in `.env`:
- `DATABASE_URL`: MySQL connection string
- `MAILER_DSN`: Email service configuration
- `APP_SECRET`: Symfony application secret

### Code Style
- Follow Symfony coding standards
- Use dependency injection via constructor
- Implement repository pattern for database queries
- Use form types for input validation
- Keep controllers thin, move logic to services