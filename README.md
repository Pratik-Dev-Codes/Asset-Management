<div align="center">
  <h1>📊 Asset Management System</h1>
  <p><strong>Modern, Efficient, and Scalable Solution for NEEPCO's Asset Management</strong></p>
  
  <p>
    <a href="https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/stargazers">
      <img src="https://img.shields.io/github/stars/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD?style=flat-square" alt="GitHub stars">
    </a>
    <a href="https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/network/members">
      <img src="https://img.shields.io/github/forks/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD?style=flat-square" alt="GitHub forks">
    </a>
    <a href="https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD/issues">
      <img src="https://img.shields.io/github/issues/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD?style=flat-square" alt="GitHub issues">
    </a>
    <a href="https://opensource.org/licenses/MIT">
      <img src="https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square" alt="License">
    </a>
  </p>
  
  <p>
    <a href="#key-features">Features</a> •
    <a href="#tech-stack">Tech Stack</a> •
    <a href="#quick-start">Quick Start</a> •
    <a href="#deployment">Deployment</a> •
    <a href="#api-documentation">API</a> •
    <a href="#contributing">Contributing</a>
  </p>
</div>

---

## 🚀 Overview

A comprehensive Asset Management System designed specifically for NEEPCO Ltd. to efficiently track, manage, and maintain organizational assets. Built with modern web technologies, this system provides a user-friendly interface and robust backend to handle all asset management needs.

<div align="center">
  <img src="https://via.placeholder.com/800x400.png?text=Asset+Management+Dashboard" alt="Dashboard Preview" style="border-radius: 8px; margin: 20px 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
</div>

## ✨ Key Features

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
  <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #4e73df;">
    <h3>📦 Asset Tracking</h3>
    <p>Real-time tracking of all organizational assets with detailed history and status updates.</p>
  </div>
  
  <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #1cc88a;">
    <h3>🔧 Maintenance Management</h3>
    <p>Schedule and track maintenance activities with automated notifications.</p>
  </div>
  
  <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #f6c23e;">
    <h3>📊 Advanced Reporting</h3>
    <p>Generate detailed reports on asset status, maintenance history, and more.</p>
  </div>
  
  <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #e74a3b;">
    <h3>👥 User Management</h3>
    <p>Role-based access control with customizable permissions.</p>
  </div>
  
  <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1;">
    <h3>📱 Responsive Design</h3>
    <p>Fully responsive interface that works on all devices.</p>
  </div>
  
  <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #20c9a6;">
    <h3>🔌 API First</h3>
    <p>RESTful API for seamless integration with other systems.</p>
  </div>
</div>

## 🛠️ Tech Stack

### Backend
- **PHP 8.1+** - Core programming language
- **Laravel 10** - PHP framework
- **MySQL** - Database
- **Redis** - Caching and queues

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Tailwind CSS** - Utility-first CSS framework
- **Inertia.js** - Server-side rendering
- **Alpine.js** - Minimal framework for JavaScript behavior

### DevOps
- **Docker** - Containerization
- **GitHub Actions** - CI/CD
- **Laravel Forge** - Server management

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- Composer
- Node.js 16+
- MySQL 8.0+
- Redis

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Pratik-Dev-Codes/Asset-Management-System-For-NEEPCO-LTD.git
   cd Asset-Management-System-For-NEEPCO-LTD
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate --seed
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   npm run dev
   ```

7. **Access the application**
   Open http://localhost:8000 in your browser

## 📚 API Documentation

Explore our comprehensive API documentation:

- **Swagger UI**: `/api/documentation` (Available in local environment)
- **Postman Collection**: [Download Postman Collection](docs/Asset-Management-API.postman_collection.json)

### Available Endpoints

#### Assets
- `GET /api/assets` - List all assets
- `POST /api/assets` - Create a new asset
- `GET /api/assets/{id}` - Get asset details
- `PUT /api/assets/{id}` - Update an asset
- `DELETE /api/assets/{id}` - Delete an asset

#### Maintenance
- `GET /api/maintenance` - List maintenance records
- `POST /api/maintenance` - Create maintenance record
- `GET /api/maintenance/{id}` - Get maintenance details

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Style
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Write meaningful commit messages
- Add tests for new features
- Update documentation as needed

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 About the Developer

<div align="center">
  <img src="https://github.com/Pratik-Dev-Codes.png" alt="Pratik Adhikary" width="150" style="border-radius: 50%; border: 4px solid #4e73df; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
  
  ### Pratik Adhikary
  *MCA Final Year Student | Full Stack Developer | Open Source Enthusiast*
  
  <p>
    <a href="https://github.com/Pratik-Dev-Codes" target="_blank" style="margin: 0 10px;">
      <img src="https://img.icons8.com/fluent/30/000000/github.png" alt="GitHub" title="GitHub">
    </a>
    <a href="https://linkedin.com/in/pratik-adhikary" target="_blank" style="margin: 0 10px;">
      <img src="https://img.icons8.com/color/30/000000/linkedin.png" alt="LinkedIn" title="LinkedIn">
    </a>
    <a href="mailto:pratik.dev.codes@gmail.com" style="margin: 0 10px;">
      <img src="https://img.icons8.com/color/30/000000/gmail.png" alt="Email" title="Email">
    </a>
  </p>
  
  <p style="max-width: 600px; margin: 20px auto; color: #6c757d;">
    Passionate about building scalable web applications and contributing to open source. 
    This project was developed as part of my final year thesis at Royal Global University, Assam.
  </p>
</div>

## 🚀 Deployment

This project includes a GitHub Actions workflow for automated deployment to staging and production environments.

### Prerequisites

- GitHub repository with the code
- Server with SSH access
- PHP 8.1+ with required extensions
- Node.js and npm
- MySQL/PostgreSQL database

### GitHub Secrets Setup

Before deploying, set up these secrets in your GitHub repository (Settings > Secrets > Actions):

| Secret Name           | Description                                      |
|-----------------------|--------------------------------------------------|
| `STAGING_HOST`       | Staging server IP or domain                     |
| `STAGING_USERNAME`   | SSH username for the server                     |
| `STAGING_SSH_KEY`    | Private SSH key for authentication              |
| `STAGING_SSH_PORT`   | (Optional) SSH port (default: 22)               |
| `SLACK_WEBHOOK_URL`  | (Optional) For deployment notifications         |
| `DB_*`               | Database connection variables if not in .env     |

### Deployment Workflow

The deployment process is automated and includes:

1. **Code Checkout**
2. **Environment Setup**
   - PHP 8.1 with required extensions
   - Node.js and npm
3. **Dependency Installation**
   - Composer packages
   - NPM packages (with caching)
4. **Build Process**
   - Asset compilation
   - Version detection
5. **Deployment**
   - Secure file transfer
   - Database migrations
   - Cache optimization
   - Queue restart
6. **Verification**
   - Health checks
   - Deployment status updates

### Manual Deployment

To deploy manually:

1. Push to the `develop` branch for staging deployment
2. Create a release for production deployment

```bash
git checkout develop
git add .
git commit -m "Your commit message"
git push origin develop
```

### Monitoring

- **GitHub Actions**: View deployment status in the "Actions" tab
- **Server Logs**: Check `/var/log/nginx/error.log` for web server errors
- **Application Logs**: Check `storage/logs/laravel.log`

### Rollback

If a deployment fails, the system will automatically roll back to the previous working version. You can also manually trigger a rollback by reverting the last commit and pushing again.

## 🙏 Acknowledgments

- Royal Global University, Assam for their guidance and support
- Laravel and Vue.js communities for amazing open-source tools
- NEEPCO for the opportunity to solve real-world challenges

---

<div align="center" style="margin: 40px 0 20px 0; color: #6c757d; font-size: 0.9em;">
  <p>Built with ❤️ using Laravel, Vue.js, and Tailwind CSS</p>
  <p>© 2025 Pratik Adhikary. All rights reserved.</p>
</div>
