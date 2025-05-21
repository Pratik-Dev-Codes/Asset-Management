<div align="center">

# 🏗️ Asset Management System for NEEPCO

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)](https://php.net/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![GitHub stars](https://img.shields.io/github/stars/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD?style=social&logo=github)](https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD?style=social&logo=github)](https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/network/members)

A modern, comprehensive asset management solution built for **North Eastern Electric Power Corporation Limited (NEEPCO)** to efficiently track, manage, and maintain organizational assets.

<div align="center" style="margin: 1.5rem 0;">
  <a href="#-key-features" class="nav-link">✨ Features</a> •
  <a href="#-quick-start" class="nav-link">🚀 Quick Start</a> •
  <a href="#-tech-stack" class="nav-link">💻 Tech Stack</a> •
  <a href="#-documentation" class="nav-link">📚 Documentation</a> •
  <a href="#-contributing" class="nav-link">🤝 Contributing</a>
</div>

</div>

## 🌟 Introduction

Welcome to the **Asset Management System for NEEPCO**, a robust web application designed to streamline asset lifecycle management for one of India's premier power generation companies. This system provides a centralized platform for tracking assets, scheduling maintenance, generating reports, and ensuring compliance with organizational policies.

### 🎓 Academic Context
*Developed as a Final Year Project for Master's in Computer Application (MCA) at Royal Global University, Assam (2025)*

## 📋 Table of Contents
- [✨ Key Features](#-key-features)
- [🚀 Quick Start](#-quick-start)
- [💻 Tech Stack](#-tech-stack)
- [📚 Documentation](#-documentation)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)
- [👨‍💻 About the Developer](#-about-the-developer)

<style>
  .nav-link {
    margin: 0 10px;
    color: #0366d6;
    text-decoration: none;
    font-weight: 500;
  }
  .nav-link:hover {
    text-decoration: underline;
  }
  .feature-card {
    background: #f6f8fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1rem 0;
    border-left: 4px solid #0366d6;
  }
  .feature-card h3 {
    margin-top: 0;
    color: #24292e;
  }
</style>

## ✨ Key Features

<div class="feature-card">
  <h3>🏷️ Comprehensive Asset Management</h3>
  <ul>
    <li><strong>Asset Tracking</strong> - Monitor assets with unique identifiers, QR codes, and barcodes</li>
    <li><strong>Lifecycle Management</strong> - Track complete asset lifecycle from procurement to decommissioning</li>
    <li><strong>Bulk Operations</strong> - Import/export assets using Excel/CSV with validation</li>
    <li><strong>Asset Categorization</strong> - Organize assets with custom fields and hierarchies</li>
    <li><strong>Depreciation Tracking</strong> - Automate depreciation calculations and reporting</li>
  </ul>
</div>

<div class="feature-card">
  <h3>🛠️ Maintenance & Work Orders</h3>
  <ul>
    <li><strong>Preventive Maintenance</strong> - Schedule and track regular maintenance with automated reminders</li>
    <li><strong>Work Order System</strong> - Create, assign, and track work orders with priorities</li>
    <li><strong>Maintenance History</strong> - Complete audit trail of all activities and costs</li>
    <li><strong>Downtime Tracking</strong> - Monitor asset availability and performance metrics</li>
  </ul>
</div>

<div class="feature-card">
  <h3>📊 Advanced Reporting & Analytics</h3>
  <ul>
    <li><strong>Custom Reports</strong> - Generate detailed reports on assets and maintenance</li>
    <li><strong>Analytics Dashboard</strong> - Interactive dashboards with KPIs and trends</li>
    <li><strong>Scheduled Reports</strong> - Automate report generation and distribution</li>
    <li><strong>Performance Metrics</strong> - Track MTBF, MTTR, and other key indicators</li>
  </ul>
</div>

<div class="feature-card">
  <h3>🔐 Security & Access Control</h3>
  <ul>
    <li><strong>Role-Based Access</strong> - Granular permissions for different user roles</li>
    <li><strong>Multi-factor Authentication</strong> - Enhanced security with 2FA support</li>
    <li><strong>Audit Trails</strong> - Comprehensive logging of all system activities</li>
    <li><strong>Data Encryption</strong> - Industry-standard security for sensitive data</li>
  </ul>
</div>

## 🚀 Quick Start

Get started with the Asset Management System in minutes with these simple steps:

### Prerequisites
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 16+ & NPM 8+
- Composer 2.0+

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD.git
cd Asset-Management-System-For-NEEPCO-LTD

# 2. Install PHP dependencies
composer install

# 3. Install JavaScript dependencies
npm install

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Configure database (update .env with your credentials)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=asset_management_system
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# 6. Run migrations & seeders
php artisan migrate --seed

# 7. Compile assets
npm run dev
# or for production: npm run build

# 8. Start development server
php artisan serve
```

### Access the Application
- **URL**: http://localhost:8000
- **Admin Credentials**:
  - Email: admin@neepco.com
  - Password: password

> 💡 **Note**: For production deployment, make sure to set `APP_ENV=production` and `APP_DEBUG=false` in your `.env` file.

## 💻 Tech Stack

### 🖥️ Backend
- **PHP 8.1+** - Core programming language
- **Laravel 10** - PHP framework
- **MySQL 8.0+** - Relational database
- **Laravel Sanctum** - API authentication
- **Laravel Excel** - Data import/export
- **Laravel Telescope** - Debugging assistant

### 🎨 Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Inertia.js** - Server-side routing
- **Tailwind CSS** - Utility-first CSS framework
- **Alpine.js** - Minimal framework for JavaScript behavior
- **Chart.js** - Data visualization

### 🔧 Development Tools
- **Docker** - Containerization
- **PHPStan** - Static analysis
- **PHP_CodeSniffer** - Code style checking
- **Git** - Version control
- **GitHub Actions** - CI/CD Pipeline

## 📚 Documentation

### 📐 System Architecture

#### High-Level Overview
```mermaid
graph TD
    A[Client] -->|HTTPS| B[Web Server]
    B --> C[Laravel Application]
    C --> D[(MySQL Database)]
    C --> E[File Storage]
    C --> F[Cache]
    D --> C
```

#### 🧩 Core Components
1. **Asset Management** - Central module for asset CRUD operations
2. **Maintenance Module** - Scheduling and tracking maintenance
3. **Reporting Engine** - Custom report generation
4. **User Management** - Role-based access control
5. **API Layer** - RESTful endpoints for mobile/third-party integration

### 📖 API Documentation
Explore our comprehensive [API Documentation](docs/API.md) for detailed information about available endpoints, request/response formats, and authentication methods.

## 🚀 Future Enhancements

### 🛠️ Planned Features
- [ ] 📱 Mobile Application (React Native)
- [ ] 🌐 IoT Integration for real-time monitoring
- [ ] 🤖 Predictive Maintenance using ML
- [ ] 🔍 Barcode/QR Code scanning app
- [ ] 🌍 Multi-location support
- [ ] 📈 Advanced analytics with Power BI integration

### ⚙️ Technical Improvements
- [ ] 🔄 Implement API versioning
- [ ] ✅ Add comprehensive test coverage
- [ ] ⚡ Optimize database queries
- [ ] 🎯 Implement GraphQL API
- [�] 🌙 Add dark mode support

## 🤝 Contributing

We welcome contributions from the community! Please read our [Contributing Guidelines](CONTRIBUTING.md) to get started.

### 🛠️ How to Contribute

1. 🍴 Fork the repository
2. 🌿 Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. 💾 Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. 📤 Push to the branch (`git push origin feature/AmazingFeature`)
5. 🔄 Open a Pull Request

### 📝 Code Style
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Write meaningful commit messages
- Add tests for new features
- Update documentation as needed

### 🐛 Reporting Issues
Found a bug? Please [open an issue](https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/issues/new) with detailed information about the problem.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 About the Developer

<div align="center">
  <img src="https://github.com/Pratik-Dev-Codes.png" alt="Pratik Adhikary" width="150" style="border-radius: 50%;">
  
  ### Pratik Adhikary
  *MCA Final Year Student at Royal Global University, Assam*
  
  <div style="margin: 15px 0;">
    <a href="mailto:pratikadhikary.work@gmail.com" style="margin: 0 10px; text-decoration: none;">
      <img src="https://img.icons8.com/color/48/000000/gmail.png" width="32" alt="Email" title="Email">
    </a>
    <a href="https://github.com/Pratik-Dev-Codes" target="_blank" style="margin: 0 10px; text-decoration: none;">
      <img src="https://img.icons8.com/fluent/48/000000/github.png" width="32" alt="GitHub" title="GitHub">
    </a>
    <a href="https://linkedin.com/in/pratik-adhikary" target="_blank" style="margin: 0 10px; text-decoration: none;">
      <img src="https://img.icons8.com/color/48/000000/linkedin.png" width="32" alt="LinkedIn" title="LinkedIn">
    </a>
  </div>
  
  "*This project represents my journey in mastering modern web development technologies and best practices during my Master's program.*"
</div>

## 🙏 Acknowledgments

- Royal Global University, Assam for their guidance and support
- Laravel and Vue.js communities for amazing open-source tools
- NEEPCO for the opportunity to solve real-world challenges

---

<div align="center" style="margin-top: 40px; padding: 20px 0; border-top: 1px solid #eaecef;">
  <p style="margin: 0 0 10px 0; color: #6a737d; font-size: 14px;">
    Made with ❤️ by Pratik Adhikary
  </p>
  <div style="display: flex; justify-content: center; gap: 15px; margin-top: 10px;">
    <a href="https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/stargazers" target="_blank" style="display: inline-flex; align-items: center; text-decoration: none; color: #24292e;">
      <img src="https://img.icons8.com/fluent/24/000000/star.png" width="18" style="margin-right: 5px;" alt="Stars">
      <span id="github-stars">Stars</span>
    </a>
    <span style="color: #e1e4e8;">•</span>
    <a href="https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/network/members" target="_blank" style="display: inline-flex; align-items: center; text-decoration: none; color: #24292e;">
      <img src="https://img.icons8.com/fluent/24/000000/code-fork.png" width="18" style="margin-right: 5px;" alt="Forks">
      <span id="github-forks">Forks</span>
    </a>
  </div>
  <p style="margin: 15px 0 0 0; font-size: 12px; color: #6a737d;">
    <a href="#top" style="color: #0366d6; text-decoration: none;">⬆️ Back to Top</a>
  </p>
</div>

<!-- GitHub Stats API Script -->
<script>
  // Fetch GitHub repository stats
  fetch('https://api.github.com/repos/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD')
    .then(response => response.json())
    .then(data => {
      if (data.stargazers_count !== undefined) {
        document.getElementById('github-stars').textContent = data.stargazers_count.toLocaleString();
      }
      if (data.forks_count !== undefined) {
        document.getElementById('github-forks').textContent = data.forks_count.toLocaleString();
      }
    })
    .catch(error => console.error('Error fetching GitHub stats:', error));
</script>

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

## 🧪 Testing

Run the test suite with:

```bash
php artisan test
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🛡️ Code Quality

[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-silver)](https://phpstan.org/)
[![PHP_CodeSniffer](https://img.shields.io/badge/code%20style-PSR--12-6B46C1.svg)](https://www.php-fig.org/psr/psr-12/)
[![CI/CD](https://github.com/Pratik-Dev-Codes/Asset-Management/actions/workflows/laravel.yml/badge.svg)](https://github.com/Pratik-Dev-Codes/Asset-Management/actions/workflows/laravel.yml)

## 👨‍💻 About the Developer

<div align="center">
  <img src="https://github.com/Pratik-Dev-Codes.png" alt="Pratik Adhikary" width="150" style="border-radius: 50%;">
  
  ### Pratik Adhikary
  *MCA Final Year Student at Royal Global University, Assam (2025)*
  
  <div style="margin: 15px 0;">
    <a href="mailto:pratikadhikary.work@gmail.com" style="margin: 0 10px;">
      <img src="https://img.icons8.com/color/48/000000/gmail.png" width="32" alt="Email">
    </a>
    <a href="https://github.com/Pratik-Dev-Codes" target="_blank" style="margin: 0 10px;">
      <img src="https://img.icons8.com/color/48/000000/github--v1.png" width="32" alt="GitHub">
    </a>
    <a href="https://linkedin.com/in/pratik-adhikary" target="_blank" style="margin: 0 10px;">
      <img src="https://img.icons8.com/color/48/000000/linkedin.png" width="32" alt="LinkedIn">
    </a>
  </div>
  
  > *"This project represents my journey in mastering modern web development technologies and best practices during my Master's program."*
</div>

---

<div align="center">
  <p>Made with ❤️ by Pratik Adhikary</p>
  <div style="margin-top: 10px;">
    <a href="https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/stargazers" target="_blank" style="margin: 0 5px;">
      <img src="https://img.shields.io/github/stars/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD?style=social" alt="GitHub stars">
    </a>
    <a href="https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/network/members" target="_blank" style="margin: 0 5px;">
      <img src="https://img.shields.io/github/forks/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD?style=social" alt="GitHub forks">
    </a>
  </div>
  <p style="margin-top: 15px;">
    <a href="#top" style="text-decoration: none; color: #0366d6;">⬆️ Back to Top</a>
  </p>
</div>
