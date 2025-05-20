# Asset Management System For NEEPCO LTD

[![GitHub repo](https://img.shields.io/badge/Repository-Asset--Management--System--For--NEEPCO--LTD-blue?logo=github)](https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD)

A comprehensive asset management system built with Laravel 10, specifically designed for NEEPCO (North Eastern Electric Power Corporation Limited) to track and manage their physical and digital assets efficiently.

## About NEEPCO

North Eastern Electric Power Corporation Limited (NEEPCO) is a Miniratna Category-I Central Public Sector Enterprise under the Ministry of Power, Government of India. It was established to plan, promote, investigate, survey, design, construct, generate, operate, and maintain power stations in the North Eastern Region of India.

## Features

- 🏷️ Asset tracking with unique identifiers
- 📊 Dashboard with asset statistics and reports
- 🔍 Advanced search and filtering
- 📱 Responsive design for all devices
- 🔒 Role-based access control
- 📅 Maintenance scheduling and tracking
- 📄 Document attachment support
- 📈 Reporting and analytics

## System Architecture

The system architecture is documented through a series of diagrams that illustrate the different layers and components of the application. These diagrams are automatically generated and kept in sync with your codebase.

### Architecture Diagrams

1. **Context Diagram**
   - Overview of system boundaries and external actors
   - Location: `docs/diagrams/00-context.puml`
   - Auto-generated: `00-context-auto.puml`

2. **Main Processes**
   - High-level view of system processes
   - Location: `docs/diagrams/01-main-processes.puml`

3. **Asset Management**
   - Detailed asset lifecycle management
   - Location: `docs/diagrams/02-asset-management.puml`

4. **Asset Registration**
   - Step-by-step registration workflow
   - Location: `docs/diagrams/03-asset-registration.puml`

5. **Auto-generated Class Diagram**
   - Generated from your models
   - Location: `docs/diagrams/04-class-diagram-auto.puml`

6. **Auto-generated Sequence Diagrams**
   - Key workflows and processes
   - Location: `docs/diagrams/05-sequence-*.puml`

## Automated Diagram Generation

### 1. Using Artisan Command

Generate all documentation and diagrams with a single command:

```bash
php artisan docs:generate
```

This will:
- Generate context diagram
- Create class diagrams from your models
- Generate sequence diagrams for key workflows
- Save all diagrams in both PNG and SVG formats

### 2. Pre-commit Hook

A Git pre-commit hook automatically generates diagrams when you commit changes to `.puml` files.

### 3. CI/CD Integration

GitHub Actions automatically updates diagrams when changes are pushed to `main` or `develop` branches.

### 4. Development Setup

For new developers, run the setup script:

```powershell
# Windows (PowerShell as Administrator)
Set-ExecutionPolicy Bypass -Scope Process -Force
.\scripts\setup-dev.ps1
```

```bash
# Linux/macOS
chmod +x ./scripts/setup-dev.sh
./scripts/setup-dev.sh
```

This will install all required dependencies:
- Java Runtime Environment (JRE)
- Graphviz
- PlantUML
- Node.js and Husky for Git hooks

## Manual Generation (if needed)

```bash
# Generate all diagrams
php artisan docs:generate

# Or use the provided scripts
./docs/generate_diagrams.ps1  # Windows PowerShell
./docs/generate_diagrams.bat  # Windows CMD
```

> **Note**: Requires [PlantUML](https://plantuml.com/) and [Graphviz](https://graphviz.org/) to be installed.

## Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & NPM

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Pratik-Dev-Codes/Asset-Management.git
   cd Asset-Management
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install NPM dependencies:
   ```bash
   npm install
   ```

4. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

5. Generate application key:
   ```bash
   php artisan key:generate
   ```

6. Configure your `.env` file with database credentials and other settings.

7. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

9. Visit `http://localhost:8000` in your browser.

## Testing

Run the tests with:

```bash
php artisan test
```

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## CI/CD Status

[![CI/CD](https://github.com/Pratik-Dev-Codes/Asset-Management/actions/workflows/laravel.yml/badge.svg)](https://github.com/Pratik-Dev-Codes/Asset-Management/actions/workflows/laravel.yml)

## Code Quality

[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-silver)](https://phpstan.org/)
[![PHP_CodeSniffer](https://img.shields.io/badge/code%20style-PSR--12-6B46C1.svg)](https://www.php-fig.org/psr/psr-12/)
