# UniMind – Student Mental Health Platform

## Overview

This project was developed as part of the **PIDEV – 3rd Year Engineering Program** at **Esprit School of Engineering** (Academic Year 2025–2026).

UniMind is a full-stack web application designed to support student mental health by providing tools for psychological follow-up, meditation sessions, event management, and questionnaire-based assessments.

## Features

- 🧠 Psychological questionnaires and mental health assessments
- 🧘 Meditation session management with audio/video support
- 📅 Event and participation management
- 👤 User profile and role management (Student, Psychologist, Admin, Responsable)
- 📊 Admin dashboard 
- 🔐 Authentication, account verification and password reset
- 💬 Psychological treatment and follow-up tracking
- 📋 QR code generation for event participation

## Tech Stack

### Frontend
- Twig templating engine
- Bootstrap 5
- JavaScript / Chart.js

### Backend
- Symfony 6 (PHP 8.2)
- Doctrine ORM
- MySQL

## Architecture

UniMind follows the **MVC architecture** provided by the Symfony framework:

```
src/
├── Controller/       # Application controllers
├── Entity/           # Doctrine ORM entities
├── Repository/       # Database query repositories
├── Form/             # Symfony form types
├── Service/          # Business logic services
├── Enum/             # PHP 8.1+ enumerations
templates/            # Twig templates
public/               # Public assets
migrations/           # Database migrations
tests/                # Unit tests (PHPUnit)
```

## Contributors

| Name |
|------|
| Islem Louati | 
| Abdelkefi Nermine |
| Hraghui Ghofrane |
| Khenissi Souha | 
| Ghuider Oumayma |
| Eddouch Nadine |

## Academic Context

> Developed at **Esprit School of Engineering – Tunisia**  
> PIDEV – 3A42 | Academic Year 2025–2026

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Symfony CLI (optional)

### Installation

```bash
# Clone the repository
git clone https://github.com/Islem-louati/Esprit-PIDEV-3A42-2526-UniMind.git
cd Esprit-PIDEV-3A42-2526-UniMind

# Install dependencies
composer install

# Configure environment
cp .env .env.local
# Edit .env.local with your database credentials

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Start the server
symfony serve
# or
php -S localhost:8000 -t public/
```

## Deployment

🚧 Deployment in progress — Coming soon

## Acknowledgments

Special thanks to our professors and supervisors at **Esprit School of Engineering** for their guidance and support throughout this project.
