<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# 🔐 Laravel Sanctum Authentication System

A secure, production-ready REST API authentication system built with **Laravel 12** and **Sanctum** — featuring device-based token management, refresh token rotation, reuse detection, and HttpOnly cookies.

---

## 🚀 Technologies

| Technology      | Version |
| --------------- | ------- |
| PHP             | ^8.2    |
| Laravel         | 12.x    |
| Laravel Sanctum | 4.x     |
| MySQL           | 8.x     |

---

## ✨ Features

- **Device-Based Tokens** — Each device gets its own independent access & refresh token pair. Logging out from one device does not affect others.
- **Refresh Token Rotation** — Every refresh request invalidates the old token and issues a brand-new pair.
- **Reuse Detection** — If a refresh token is used more than once, all tokens for that user are immediately revoked.
- **HttpOnly Cookies** — Tokens are stored in HttpOnly cookies, inaccessible to JavaScript, protecting against XSS attacks.
- **Token Abilities** — Access tokens and refresh tokens have separate abilities, preventing misuse across endpoints.
- **UUID Primary Keys** — User IDs are UUIDs instead of auto-increment integers, improving security and obscurity.
- **Role-Based Users** — Users have an `admin` or `user` role stored directly on the model.
- **Global Error Handling** — Consistent JSON error responses for authentication, not-found, and server errors.
- **API Helper Functions** — Global `successResponse()` and `errorResponse()` helpers for clean, consistent API responses.

---

## 📁 Project Structure

```
app/
├── Helpers/
│   └── api_helper.php          # Global response helpers
├── Http/
│   ├── Controllers/
│   │   ├── ApiController.php
│   │   └── Auth/
│   │       └── AuthController.php
│   └── Resources/
│       └── UserResource.php
├── Models/
│   └── User.php
routes/
└── api.php
bootstrap/
└── app.php                     # Exception handling & middleware
```

---

## ⚙️ Installation

### 1. Clone the repository

```bash
git clone https://github.com/ebrahimnezhadali8-gif/Auth_System_Laravel.git
cd Auth_System_Laravel
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auth_system
DB_USERNAME=root
DB_PASSWORD=

ACCESS_TOKEN_EXPIRY=15
REFRESH_TOKEN_EXPIRY=10080
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Start the server

```bash
php artisan serve
```

---

## 📡 API Endpoints

| Method | Endpoint             | Auth Required | Description              |
| ------ | -------------------- | ------------- | ------------------------ |
| POST   | `/api/auth/register` | ❌            | Register a new user      |
| POST   | `/api/auth/login`    | ❌            | Login and receive tokens |
| POST   | `/api/auth/refresh`  | ❌            | Refresh access token     |
| POST   | `/api/auth/logout`   | ✅            | Logout current device    |

---

## 🔒 Security Architecture

```
Login
 ├── Creates unique deviceId (UUID)
 ├── Issues Access Token  → expires in 15 min  → stored in HttpOnly Cookie
 ├── Issues Refresh Token → expires in 7 days  → stored in HttpOnly Cookie
 └── Stores deviceId      → stored in HttpOnly Cookie

Refresh
 ├── Reads refresh token from cookie
 ├── Checks token exists in DB
 ├── Checks last_used_at → if used before = REUSE DETECTED → revoke all tokens
 ├── Checks token not expired
 ├── Checks ability = 'refresh'
 ├── Deletes old token pair for this device
 └── Issues new token pair

Logout
 ├── Reads deviceId from cookie
 ├── Deletes all tokens for this device from DB
 └── Expires all cookies on client
```

---

## 🧪 Testing with Postman

1. Send `POST /api/auth/login` with phone & password in JSON body
2. Postman will automatically store the cookies
3. Send `POST /api/auth/logout` — cookies are sent automatically

> Make sure **"Send cookies"** is enabled in Postman settings.

---

## 📝 Request & Response Examples

### Register

```json
POST /api/auth/register
{
    "name": "Ali Ahmadi",
    "phone": "09123456789",
    "password": "password123"
}
```

### Login

```json
POST /api/auth/login
{
    "phone": "09123456789",
    "password": "password123"
}
```

### Response format

```json
{
    "status": "success",
    "message": "User login successfully",
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Ali Ahmadi",
        "phone": "09123456789"
    },
    "token": "27|token-access"
}
```
