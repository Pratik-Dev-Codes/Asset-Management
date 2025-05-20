# Create reports directory if it doesn't exist
$reportsDir = "$PSScriptRoot\\..\\reports"
New-Item -ItemType Directory -Force -Path $reportsDir | Out-Null

# Define report path
$reportPath = "$reportsDir\\Asset_Management_System_Report_$(Get-Date -Format 'yyyyMMdd').md"

# Start building the markdown content
$reportContent = @"
# Asset Management System
## Project Report

**Prepared for:** NEEPCO (North Eastern Electric Power Corporation Limited)  
**Date:** $(Get-Date -Format "MMMM dd, yyyy")

## Table of Contents
- [1. Executive Summary](#1-executive-summary)
- [2. System Overview](#2-system-overview)
  - [2.1 Core Features](#21-core-features)
  - [2.2 Technical Stack](#22-technical-stack)
- [3. System Architecture](#3-system-architecture)
  - [3.1 Database Schema](#31-database-schema)
  - [3.2 System Diagrams](#32-system-diagrams)
- [4. Technical Implementation](#4-technical-implementation)
- [5. Security Features](#5-security-features)
- [6. Documentation](#6-documentation)
- [7. Testing Strategy](#7-testing-strategy)
- [8. Deployment](#8-deployment)
- [9. Future Enhancements](#9-future-enhancements)

## 1. Executive Summary

The Asset Management System is a comprehensive solution developed for NEEPCO to efficiently track, manage, and maintain their physical and digital assets. Built on Laravel 10, the system provides a robust platform for asset lifecycle management with advanced tracking and reporting capabilities.

## 2. System Overview

### 2.1 Core Features

- Asset lifecycle management with unique identifiers
- Role-based access control
- Maintenance scheduling and tracking
- Document management
- Real-time reporting and analytics
- Barcode/QR code support
- Automated notifications

### 2.2 Technical Stack

- **Backend:** Laravel 10, PHP 8.1+
- **Frontend:** HTML5, CSS3, JavaScript, Vue.js
- **Database:** MySQL/PostgreSQL
- **Documentation:** PlantUML, Markdown
- **Testing:** PHPUnit, Pest

## 3. System Architecture

### 3.1 Database Schema

The system utilizes a relational database with the following key tables:

| Table Name | Description |
|------------|-------------|
| users | Manages system users and authentication |
| assets | Tracks all physical and digital assets |
| asset_models | Defines asset models and specifications |
| asset_statuses | Manages asset lifecycle states |
| maintenance_records | Tracks maintenance history |

### 3.2 System Diagrams

#### System Context Diagram
```
[System Context Diagram]
(To be generated from docs/diagrams/00-context.puml)
```

#### Main Process Flow
```
[Main Process Flow]
(To be generated from docs/diagrams/01-main-processes.puml)
```

## 4. Technical Implementation

### 4.1 Backend
- RESTful API architecture
- Repository pattern implementation
- Service layer for business logic
- Queue system for background jobs
- Scheduled tasks for maintenance

### 4.2 Frontend
- Responsive design
- Interactive dashboards
- Data visualization
- Form validation
- Real-time updates

## 5. Security Features

- Role-based access control
- CSRF protection
- XSS prevention
- Input validation
- Secure password hashing
- API authentication (JWT/OAuth2)

## 6. Documentation

### 6.1 System Documentation
- API documentation (OpenAPI/Swagger)
- Database schema documentation
- Installation and setup guides
- User manuals

### 6.2 Technical Diagrams
- System architecture
- Database schema
- Sequence diagrams
- Process flows

## 7. Testing Strategy

- Unit testing (PHPUnit)
- Feature testing
- Browser testing (Laravel Dusk)
- API testing
- Performance testing

## 8. Deployment

### 8.1 Requirements
- PHP 8.1+
- MySQL 5.7+ or PostgreSQL 10+
- Composer
- Node.js & NPM
- Web server (Apache/Nginx)

### 8.2 Installation
1. Clone the repository
2. Install dependencies (`composer install`, `npm install`)
3. Configure environment (`.env` file)
4. Run migrations (`php artisan migrate`)
5. Generate application key (`php artisan key:generate`)
6. Build assets (`npm run dev` or `npm run prod`)

## 9. Future Enhancements

- Mobile application integration
- IoT device integration for real-time monitoring
- AI-powered predictive maintenance
- Enhanced reporting with machine learning
- Multi-language support
- Advanced analytics dashboard

---
*Document generated on $(Get-Date -Format "MMMM dd, yyyy")*
"@

# Save the report
$reportContent | Out-File -FilePath $reportPath -Encoding utf8

Write-Host "Markdown report generated successfully at: $reportPath" -ForegroundColor Green
Write-Host "You can convert this to Word using any Markdown to Word converter." -ForegroundColor Cyan
