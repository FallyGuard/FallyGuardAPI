# FallyGuard API

A Laravel API for a fall-detection and caregiver-support platform. Patients can record fall events and emergency contacts, while caregivers can follow patients, review safety information, communicate in real time, and receive broadcast alerts.

[Postman documentation](https://documenter.getpostman.com/view/23054100/2sA2rGtJj7) · [Repository branch](https://github.com/FallyGuard/FallyGuardAPI/tree/real-time)

> This README documents the `real-time` branch referenced by the project portfolio.

## Features

- Separate patient and caregiver registration/login flows
- Laravel Sanctum bearer-token authentication
- Email OTP verification, resend, forgot-password, and reset-password flows
- Google OAuth through Laravel Socialite
- Patient and caregiver profiles with Cloudinary image uploads
- Caregiver follow/unfollow relationships with patients
- Patient, caregiver, and shared profile views
- Emergency-contact CRUD
- Fall-event CRUD with latitude, longitude, severity, and generated static-map location
- Caregiver views of followed patients, their contacts, and their fall history
- Real-time one-to-one chat through private Pusher-compatible channels
- Broadcast fall and follow notifications
- Search across platform users
- Scheduled cleanup command for unverified accounts

## Tech Stack

- PHP 8.1+
- Laravel 10 and Eloquent
- Laravel Sanctum and Fortify
- MySQL or another Laravel-supported relational database
- Pusher-compatible broadcasting
- Laravel queues and notifications
- Cloudinary
- Laravel Socialite
- Google Static Maps integration
- Vite and Axios

## Domain Model

```text
Patient (User)
├── Emergency Contacts
├── Fall Events
├── Followed by Caregivers
└── Messages

Caregiver
├── Followed Patients
└── Messages
```

The main persistence models are `User`, `Caregiver`, `EmergencyContact`, `Fall`, and `Message`. Polymorphic sender/receiver fields allow chat messages to move between patient and caregiver accounts.

## Getting Started

### Prerequisites

- PHP 8.1+
- Composer
- A relational database supported by Laravel
- Node.js and npm for Vite assets
- SMTP, Pusher-compatible, Cloudinary, and Google OAuth credentials for the corresponding integrations

### Installation

```bash
git clone --branch real-time https://github.com/FallyGuard/FallyGuardAPI.git
cd FallyGuardAPI
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure `.env` for these integration groups:

| Group | Important variables |
| --- | --- |
| Application | `APP_URL`, `APP_ENV`, `APP_DEBUG` |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Queue | `QUEUE_CONNECTION` |
| Mail | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` |
| Broadcasting | `BROADCAST_DRIVER`, `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` |
| Frontend broadcast client | matching `VITE_PUSHER_*` variables |

Cloudinary and Google OAuth settings are read through their Laravel configuration files; provide the credentials required by those integrations without committing secrets.

Prepare the database:

```bash
php artisan migrate
# Optional development records
php artisan db:seed
```

Run the API, queue worker, and Vite client in separate terminals:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

The default Laravel API root is `http://127.0.0.1:8000/api`. Send `Accept: application/json` with API requests.

## API Overview

### Identity

```text
POST /api/patients/register
POST /api/patients/login
POST /api/patients/verify-email
POST /api/patients/forgot-password
POST /api/patients/reset-password

POST /api/caregivers/register
POST /api/caregivers/login
POST /api/caregivers/verify-email
POST /api/caregivers/forgot-password
POST /api/caregivers/reset-password

GET  /api/auth/google
GET  /api/auth/google/callback
```

### Authenticated Shared Operations

```text
GET    /api/me
GET    /api/me/chats
POST   /api/me/follow/{patientId}
POST   /api/me/unfollow/{patientId}
GET    /api/emergency-contacts
POST   /api/emergency-contacts
GET    /api/falls
POST   /api/falls
GET    /api/chat/{otherId}
POST   /api/chat/{receiverId}
```

### Patient and Caregiver Views

```text
GET /api/patients/{id}
GET /api/patients/{id}/contacts
GET /api/patients/{id}/falls
GET /api/patients/me

GET /api/caregivers/me
GET /api/caregivers/me/patients
GET /api/caregivers/me/patients/{id}/contacts
GET /api/caregivers/me/patients/{id}/falls
```

See [`routes/api.php`](routes/api.php) for the complete current route map and the [Postman documentation](https://documenter.getpostman.com/view/23054100/2sA2rGtJj7) for request examples.

## Real-Time Events

`SentMessage` broadcasts chat messages to a private channel shaped as:

```text
private-chat.{sender_id}.{receiver_id}
```

The event name is `message-sent`. Fall notifications use the `fall-channel` channel and `fall-event` event name. Configure Laravel's broadcast driver, queue worker, server-side Pusher credentials, and matching frontend `VITE_PUSHER_*` settings before testing real-time behavior.

## Project Structure

```text
app/
├── Events/                 # Chat and verification broadcasts
├── Http/Controllers/Api/   # Auth, users, caregivers, falls, contacts, chat
├── Http/Middleware/        # Accept, role, and token checks
├── Models/                 # Core Eloquent domain models
├── Notifications/          # Verification, reset, follow, and fall alerts
└── Services/               # Shared authentication workflows
database/
├── migrations/
├── factories/
└── seeders/
routes/
├── api.php
└── channels.php
```

## Verification

```bash
php artisan test
```

The repository currently contains Laravel's baseline example tests. Adding feature coverage for identity, ownership, fall alerts, and chat is recommended before production use.

## License

The Composer package metadata declares MIT. No standalone project license file is currently included.
