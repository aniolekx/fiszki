# Fiszki - AI-Powered Flashcard Learning Platform

<div align="center">

![Symfony](https://img.shields.io/badge/Symfony-6.4_LTS-000000?style=for-the-badge&logo=symfony&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-20.10%2B-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)

**An intelligent flashcard application that leverages AI to generate educational content and optimize learning through spaced repetition algorithms.**

[Features](#features) • [Installation](#installation) • [Usage](#usage) • [Development](#development) • [Testing](#testing) • [Contributing](#contributing)

</div>

## 📚 About

Fiszki is a modern web application designed to revolutionize the way people learn through flashcards. By combining traditional learning methods with AI-powered content generation and scientifically-proven spaced repetition algorithms, Fiszki makes memorization more efficient and engaging.

## ✨ Features

### Core Functionality
- 🎯 **User Management** - Secure registration with email confirmation, authentication, and profile management
- 📦 **Deck Organization** - Create and manage collections of flashcards organized by topic
- 🃏 **Flashcard CRUD** - Full create, read, update, and delete operations for flashcards
- 📧 **Email Notifications** - Automated email confirmations and notifications

### AI-Powered Features
- 🤖 **Smart Generation** - Generate flashcards automatically from text (1000-10,000 characters)
- 🎨 **Multiple Formats** - Support for various flashcard types and content formats
- 💳 **Credit System** - Token-based system for AI generation with usage tracking
- 📊 **Usage Analytics** - Track AI generation history and patterns

### Learning Enhancement
- 🧠 **Spaced Repetition** - Scientifically-proven algorithm for optimal memorization
- 📈 **Progress Tracking** - Monitor learning progress with detailed statistics
- 🎮 **Study Sessions** - Interactive study mode with immediate feedback
- 📝 **Performance History** - Review past performance and identify areas for improvement

### Administration
- 👨‍💼 **Admin Dashboard** - Comprehensive admin panel for user and system management
- ⚙️ **System Settings** - Configure AI models, API keys, and system parameters
- 📊 **User Analytics** - Monitor user activity and system usage

## 🚀 Installation

### Prerequisites
- Docker >= 20.10
- Docker Compose >= 2.0
- Git

### Quick Start

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/fiszki.git
cd fiszki
```

2. **Copy environment configuration**
```bash
cp .env.example .env
# Edit .env with your configuration (database, mailer, OpenAI API key)
```

3. **Start Docker containers**
```bash
docker-compose up -d
```

4. **Install dependencies**
```bash
docker-compose exec php-fpm composer install
docker-compose exec php-fpm npm install
```

5. **Set up the database**
```bash
docker-compose exec php-fpm php bin/console doctrine:migrations:migrate
docker-compose exec php-fpm php bin/console doctrine:fixtures:load --no-interaction  # Optional: Load sample data
```

6. **Build frontend assets**
```bash
docker-compose exec php-fpm npm run build
```

7. **Access the application**
```
http://localhost:8080
```

## 💻 Development

### Available Commands

#### Docker Commands
```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Access PHP container
docker-compose exec php-fpm bash

# View logs
docker-compose logs -f
```

#### Backend Commands
```bash
# Clear cache
php bin/console cache:clear

# Create new entity
php bin/console make:entity

# Create new migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Load fixtures
php bin/console doctrine:fixtures:load --no-interaction
```

#### Frontend Commands
```bash
# Development build with watch
npm run watch

# Development build (one-time)
npm run dev

# Production build
npm run build
```

#### Code Quality
```bash
# Run PHPStan (static analysis)
vendor/bin/phpstan analyse

# Run PHP CS Fixer
vendor/bin/php-cs-fixer fix

# Run all tests
vendor/bin/phpunit
vendor/bin/behat
```

## 🧪 Testing

### Unit Tests
```bash
vendor/bin/phpunit
```

### BDD Tests
```bash
vendor/bin/behat
```

### Static Analysis
```bash
vendor/bin/phpstan analyse --level=6
```

### Test Coverage
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## 📁 Project Structure

```
fiszki/
├── assets/            # Frontend assets (JS, CSS)
├── bin/              # Symfony console
├── config/           # Application configuration
├── docker/           # Docker configuration
├── migrations/       # Database migrations
├── public/           # Public directory (web root)
├── src/              # Application source code
│   ├── Controller/   # HTTP controllers
│   ├── Entity/       # Doctrine entities
│   ├── Form/         # Form types
│   ├── Repository/   # Database repositories
│   ├── Service/      # Business logic services
│   └── Security/     # Security components
├── templates/        # Twig templates
├── tests/           # Test suites
│   ├── Behat/       # BDD tests
│   ├── Functional/  # Functional tests
│   └── Unit/        # Unit tests
├── translations/    # Internationalization
└── var/            # Cache and logs
```

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env`:

```bash
# Application
APP_ENV=dev
APP_SECRET=your_secret_key

# Database
DATABASE_URL="mysql://root:password@mysql:3306/fiszki_db?serverVersion=8.0.39"

# Mailer
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# OpenAI API
OPEN_AI_KEY=your_openai_api_key
AI_MODEL=gpt-3.5-turbo
AI_TEMPERATURE=0.7
AI_MAX_TOKENS=1000
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow Symfony coding standards
- Use dependency injection via constructor
- Write tests for new features
- Keep controllers thin, move logic to services
- Run PHPStan and fix any issues before committing

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with [Symfony 6.4 LTS](https://symfony.com)
- UI powered by [Bootstrap 5](https://getbootstrap.com)
- AI integration via [OpenAI API](https://openai.com)
- Icons from [Font Awesome](https://fontawesome.com)

## 📞 Support

For support, please open an issue in the GitHub repository or contact the maintainers.

---

<div align="center">
Made with ❤️ by the Fiszki Team
</div>