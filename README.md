# WordPress CI/CD Quality Engineering Project

## Project Overview
Comprehensive Quality Engineering project implementing CI/CD pipeline for WordPress with automated testing (white-box and black-box).

**Project Deadline:** December 07, 2025  
**Team Size:** Maximum 3 members

## Quick Start

### Prerequisites
- Docker Desktop installed
- Git installed
- Node.js and npm installed
- PHP 7.4+ and Composer installed

### Local Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd wordpress-sqe-project
   ```

2. **Start WordPress with Docker:**
   ```bash
   docker-compose up -d
   ```

3. **Access WordPress:**
   - WordPress: http://localhost:8082
   - phpMyAdmin: http://localhost:8081

4. **Install dependencies:**
   ```bash
   # Backend testing
   composer install
   
   # Frontend testing
   npm install
   ```

## Project Structure

```
.
├── docker-compose.yml          # Docker setup for WordPress
├── .github/
│   └── workflows/              # CI/CD pipeline configurations
├── tests/
│   ├── unit/                   # PHPUnit unit tests
│   ├── integration/            # Integration tests
│   └── bootstrap.php
├── cypress/
│   ├── e2e/                    # Cypress E2E tests
│   ├── fixtures/
│   └── support/
├── docs/
│   └── test-plan.md            # Test plan document
└── README.md

```

## Running Tests

### Backend Tests (PHPUnit)
```bash
./vendor/bin/phpunit
```

### Frontend Tests (Cypress)
```bash
# Interactive mode
npx cypress open

# Headless mode
npx cypress run
```

## CI/CD Pipeline

The CI/CD pipeline is configured using GitHub Actions and includes:
- Source stage (Git triggers)
- Build stage (Docker build)
- Test stage (Automated testing)
- Staging deployment
- Production deployment

## Documentation

- [Project Analysis](./PROJECT_ANALYSIS.md)
- [Project Selection Analysis](./PROJECT_SELECTION_ANALYSIS.md)
- [WordPress Project Roadmap](./WORDPRESS_PROJECT_ROADMAP.md)

## Team Members
- [Add team member names]

## License
This project is for educational purposes.

