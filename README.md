# Mini Transaction Service

![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![Laravel](https://img.shields.io/badge/Laravel-11-red)
![JWT](https://img.shields.io/badge/JWT-auth-orange)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14-blue)
![License](https://img.shields.io/badge/License-MIT-green)

Mini Transaction Service adalah **RESTful API** berbasis Laravel 11 untuk manajemen user dan transaksi (DEBIT / CREDIT) dengan autentikasi JWT.

---

## ⚡ Setup Project

1. **Clone repository:**

```bash
git clone https://github.com/username/mini-transaction-service.git
cd mini-transaction-service
```

2. **Install dependencies:**

```bash
composer install
```

3. **Copy environment file:**

```bash
cp .env.example .env
```

4. **Sesuaikan konfigurasi database di `.env` (PostgreSQL):**

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mini_transaction_db
DB_USERNAME=postgres
DB_PASSWORD=secret
```

5. **Setup JWT Authentication (`tymon/jwt-auth`):**

```bash
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

* Ini akan generate `JWT_SECRET` di `.env`.

6. **Generate application key:**

```bash
php artisan key:generate
```

7. **Migrasi database:**

```bash
php artisan migrate
```

8. **Generate dokumentasi Swagger:**

```bash
php artisan l5-swagger:generate
```

9. **Jalankan server:**

```bash
php artisan serve
```

Server berjalan di `http://127.0.0.1:8000`.

---

## 🏗️ Arsitektur Sistem

```
Mini Transaction Service (Laravel 11)
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php        # Registrasi, login, logout, refresh, me
│   │   │   └── TransactionController.php # CRUD transaksi
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   └── Transaction.php
│   ├── Events/
│   │   └── TransactionCreated.php
├── database/
│   ├── migrations/
│   └── seeders/ (opsional)
├── routes/
│   └── api.php                          # Semua route API
├── storage/
│   └── api-docs/
│       └── api-docs.json                # Dokumentasi Swagger JSON
├── config/
│   └── jwt.php                          # Konfigurasi JWT
├── composer.json
└── README.md
```

---

## 🔑 Fitur Utama

* **User Management**: Registrasi, login, logout, refresh token, lihat data user.
* **Transaction Management**: DEBIT / CREDIT dengan status `PENDING` / `COMPLETED`.
* **JWT Authentication**: Token-based authentication untuk semua endpoint.
* **Event-driven**: Event `TransactionCreated` untuk integrasi dengan Wallet Service / notifikasi.
* **Swagger Documentation**: Auto-generated OpenAPI 3.0 di `storage/api-docs/api-docs.json`.

---

## 🧰 API Endpoints

| Endpoint                  | Method | Auth | Description                       |
| ------------------------- | ------ | ---- | --------------------------------- |
| `/api/auth/register`      | POST   | ❌    | Registrasi user baru              |
| `/api/auth/login`         | POST   | ❌    | Login dan dapatkan token JWT      |
| `/api/auth/refresh`       | POST   | ✅    | Refresh token JWT                 |
| `/api/auth/logout`        | POST   | ✅    | Logout / invalidate token         |
| `/api/auth/me`            | GET    | ✅    | Ambil data user yang sedang login |
| `/api/transactions`       | POST   | ✅    | Buat transaksi DEBIT / CREDIT     |
| `/api/transactions/{id}`  | GET    | ✅    | Detail transaksi                  |
| `/api/users/{id}/balance` | GET    | ✅    | Ambil saldo user                  |

> **Catatan:** Semua endpoint yang membutuhkan auth harus menyertakan header:
>
> ```http
> Authorization: Bearer <token>
> ```

---

## 📄 Dokumentasi API

* File dokumentasi Swagger JSON:

```
storage/api-docs/api-docs.json
```

* Bisa dibuka di [Swagger Editor](https://editor.swagger.io/) atau di-import ke Postman.

---

## 🔧 Testing

* **Unit & Integration Test Coverage:** Minimal 70%.
* **Testing Tools:** PHPUnit dengan mocking service eksternal.
* **Skenario Tes:**

  * Saldo tidak cukup → transaksi gagal.
  * Double request transaksi → pastikan idempotent handling.
  * Retry mechanism berjalan untuk transaksi gagal.

Jalankan testing:

```bash
php artisan test --coverage
```

* Hasil testing menampilkan persentase coverage.

---

## ⚙️ Konfigurasi JWT

* File konfigurasi: `config/jwt.php`
* Secret disimpan di `.env` sebagai `JWT_SECRET`.
* Token default berlaku 60 menit (TTL), bisa diubah di config.

---

## 📄 License

MIT License © 2025

```
```
