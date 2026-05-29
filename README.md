# TokoKu API

Backend API untuk platform e-commerce **TokoKu**, dibangun dengan **Laravel** dan autentikasi **Laravel Sanctum**.

---

## Daftar Isi

- [Informasi Umum](#informasi-umum)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Instalasi](#instalasi)
- [Format Respons](#format-respons)
- [Autentikasi](#autentikasi)
- [Endpoint API](#endpoint-api)
  - [Auth](#auth)
  - [Kategori](#kategori)
  - [Produk](#produk)
  - [Pesanan (Orders)](#pesanan-orders)
- [Fitur Bonus](#fitur-bonus)

---

## Informasi Umum

| Item | Detail |
|---|---|
| Base URL | `http://localhost:8000/api` |
| Format | JSON |
| Header wajib | `Accept: application/json` |
| Autentikasi | Bearer Token (Laravel Sanctum) |

---

## Tech Stack

- **Framework**: Laravel (latest)
- **Autentikasi**: Laravel Sanctum
- **Database**: MySQL
- **Testing**: Postman

---

## Database Schema

### `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | Primary key |
| name | string | Nama pengguna |
| email | string | Email (unique) |
| password | string (hashed) | Password terenkripsi |
| created_at | timestamp | |

### `categories`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | Primary key |
| name | string | Nama kategori |
| slug | string | Slug URL-friendly |
| description | text | Deskripsi kategori |
| created_at | timestamp | |

### `products`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | Primary key |
| category_id | FK | Relasi ke `categories` |
| name | string | Nama produk |
| slug | string | Slug URL-friendly |
| description | text | Deskripsi produk |
| price | decimal | Harga produk |
| stock | integer | Jumlah stok |
| is_active | boolean | Status aktif produk |
| created_at | timestamp | |

### `orders`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | Primary key |
| user_id | FK | Relasi ke `users` |
| total_price | decimal | Total harga pesanan |
| status | enum | `pending` / `processing` / `done` / `cancelled` |
| notes | text | Catatan pesanan |
| created_at | timestamp | |

### `order_items`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | Primary key |
| order_id | FK | Relasi ke `orders` |
| product_id | FK | Relasi ke `products` |
| quantity | integer | Jumlah item |
| unit_price | decimal | Harga satuan saat order |

---

## Instalasi

```bash
# 1. Buat proyek Laravel
composer create-project laravel/laravel tokoku-api
cd tokoku-api

# 2. Install Laravel Sanctum
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# 3. Konfigurasi .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tokoku_db
DB_USERNAME=root
DB_PASSWORD=
```

Tambahkan `HasApiTokens` pada model `User`:

```php
// app/Models/User.php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

---

## Format Respons

Semua respons API menggunakan format JSON standar berikut:

**Sukses**
```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": { }
}
```

**Gagal / Error**
```json
{
  "success": false,
  "message": "Produk tidak ditemukan",
  "errors": { }
}
```

---

## Autentikasi

API ini menggunakan **Laravel Sanctum** dengan mekanisme **Bearer Token**.

Untuk endpoint yang membutuhkan autentikasi, sertakan header berikut:

```
Authorization: Bearer <token>
```

Token didapatkan setelah melakukan request login yang berhasil.

---

## Endpoint API

### Auth

#### `POST /auth/register`

Registrasi pengguna baru.

- **Auth**: Tidak diperlukan

**Request Body**
```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response `201 Created`**
```json
{
  "success": true,
  "message": "Registrasi berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@example.com"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
  }
}
```

**Response `422 Unprocessable Entity`** — validasi gagal (misal email sudah terdaftar)

---

#### `POST /auth/login`

Login dan mendapatkan Bearer Token Sanctum.

- **Auth**: Tidak diperlukan

**Request Body**
```json
{
  "email": "budi@example.com",
  "password": "password123"
}
```

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@example.com"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
  }
}
```

**Response `401 Unauthorized`** — kredensial salah

---

#### `POST /auth/logout`

Logout dan mencabut token aktif.

- **Auth**: ✅ Diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Logout berhasil",
  "data": null
}
```

**Response `401 Unauthorized`** — token tidak valid atau sudah dicabut

---

#### `GET /auth/profile`

Melihat data profil pengguna yang sedang login.

- **Auth**: ✅ Diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Data profil berhasil diambil",
  "data": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "created_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

**Response `401 Unauthorized`** — tanpa token

---

### Kategori

#### `GET /categories`

Menampilkan semua kategori.

- **Auth**: Tidak diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Data kategori berhasil diambil",
  "data": [
    {
      "id": 1,
      "name": "Elektronik",
      "slug": "elektronik",
      "description": "Produk elektronik dan gadget"
    }
  ]
}
```

---

#### `POST /categories`

Membuat kategori baru.

- **Auth**: ✅ Diperlukan

**Request Body**
```json
{
  "name": "Elektronik",
  "description": "Produk elektronik dan gadget"
}
```

**Response `201 Created`**
```json
{
  "success": true,
  "message": "Kategori berhasil dibuat",
  "data": {
    "id": 1,
    "name": "Elektronik",
    "slug": "elektronik",
    "description": "Produk elektronik dan gadget"
  }
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `422 Unprocessable Entity`** — validasi gagal

---

#### `GET /categories/{id}`

Menampilkan detail kategori beserta daftar produknya.

- **Auth**: Tidak diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Detail kategori berhasil diambil",
  "data": {
    "id": 1,
    "name": "Elektronik",
    "slug": "elektronik",
    "description": "Produk elektronik dan gadget",
    "products": [
      {
        "id": 1,
        "name": "Laptop Gaming",
        "price": "15000000.00",
        "stock": 10,
        "is_active": true
      }
    ]
  }
}
```

**Response `404 Not Found`** — ID tidak ditemukan

---

#### `PUT /categories/{id}`

Memperbarui data kategori.

- **Auth**: ✅ Diperlukan

**Request Body**
```json
{
  "name": "Elektronik & Gadget",
  "description": "Produk elektronik, gadget, dan aksesoris"
}
```

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Kategori berhasil diperbarui",
  "data": {
    "id": 1,
    "name": "Elektronik & Gadget",
    "slug": "elektronik-gadget",
    "description": "Produk elektronik, gadget, dan aksesoris"
  }
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `422 Unprocessable Entity`** — data kosong / tidak valid  
**Response `404 Not Found`** — ID tidak ditemukan

---

#### `DELETE /categories/{id}`

Menghapus kategori. Gagal jika kategori masih memiliki produk.

- **Auth**: ✅ Diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Kategori berhasil dihapus",
  "data": null
}
```

**Response `400 Bad Request`** — kategori masih memiliki produk  
**Response `401 Unauthorized`** — tanpa token  
**Response `404 Not Found`** — ID tidak ditemukan

---

### Produk

#### `GET /products`

Menampilkan semua produk aktif dengan pagination.

- **Auth**: Tidak diperlukan
- **Query Params (Bonus)**:
  - `search` — filter berdasarkan nama produk (contoh: `?search=laptop`)
  - `category_id` — filter berdasarkan kategori (contoh: `?category_id=1`)

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Data produk berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Laptop Gaming",
        "slug": "laptop-gaming",
        "description": "Laptop untuk gaming performa tinggi",
        "price": "15000000.00",
        "stock": 10,
        "is_active": true,
        "category": {
          "id": 1,
          "name": "Elektronik"
        }
      }
    ],
    "per_page": 15,
    "total": 50
  }
}
```

---

#### `POST /products`

Membuat produk baru.

- **Auth**: ✅ Diperlukan

**Request Body**
```json
{
  "category_id": 1,
  "name": "Laptop Gaming",
  "description": "Laptop untuk gaming performa tinggi",
  "price": 15000000,
  "stock": 10
}
```

**Response `201 Created`**
```json
{
  "success": true,
  "message": "Produk berhasil dibuat",
  "data": {
    "id": 1,
    "category_id": 1,
    "name": "Laptop Gaming",
    "slug": "laptop-gaming",
    "description": "Laptop untuk gaming performa tinggi",
    "price": "15000000.00",
    "stock": 10,
    "is_active": true
  }
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `422 Unprocessable Entity`** — validasi gagal

---

#### `GET /products/{id}`

Menampilkan detail produk beserta informasi kategori.

- **Auth**: Tidak diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Detail produk berhasil diambil",
  "data": {
    "id": 1,
    "name": "Laptop Gaming",
    "slug": "laptop-gaming",
    "description": "Laptop untuk gaming performa tinggi",
    "price": "15000000.00",
    "stock": 10,
    "is_active": true,
    "category": {
      "id": 1,
      "name": "Elektronik",
      "slug": "elektronik"
    }
  }
}
```

**Response `404 Not Found`** — ID tidak ditemukan

---

#### `PUT /products/{id}`

Memperbarui data produk.

- **Auth**: ✅ Diperlukan

**Request Body**
```json
{
  "category_id": 1,
  "name": "Laptop Gaming Pro",
  "description": "Laptop gaming edisi terbaru",
  "price": 18000000,
  "stock": 5
}
```

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Produk berhasil diperbarui",
  "data": { }
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `404 Not Found`** — ID tidak ditemukan

---

#### `PATCH /products/{id}/toggle`

Mengaktifkan atau menonaktifkan produk (toggle `is_active`).

- **Auth**: ✅ Diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Status produk berhasil diubah",
  "data": {
    "id": 1,
    "is_active": false
  }
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `404 Not Found`** — ID tidak ditemukan

---

#### `DELETE /products/{id}`

Menghapus produk.

- **Auth**: ✅ Diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Produk berhasil dihapus",
  "data": null
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `404 Not Found`** — ID tidak ditemukan

---

### Pesanan (Orders)

#### `GET /orders`

Menampilkan semua pesanan milik user yang sedang login.

- **Auth**: ✅ Diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Data pesanan berhasil diambil",
  "data": [
    {
      "id": 1,
      "total_price": "350000.00",
      "status": "pending",
      "notes": "Tolong kirim cepat",
      "created_at": "2025-01-01T10:00:00.000000Z"
    }
  ]
}
```

**Response `401 Unauthorized`** — tanpa token

---

#### `POST /orders`

Membuat pesanan baru. `total_price` dikalkulasi otomatis dari `unit_price × quantity` setiap item.

- **Auth**: ✅ Diperlukan

**Request Body**
```json
{
  "notes": "Tolong kirim cepat",
  "items": [
    { "product_id": 3, "quantity": 2 },
    { "product_id": 7, "quantity": 1 }
  ]
}
```

**Response `201 Created`**
```json
{
  "success": true,
  "message": "Pesanan berhasil dibuat",
  "data": {
    "id": 12,
    "user_id": 1,
    "total_price": "350000.00",
    "status": "pending",
    "notes": "Tolong kirim cepat",
    "items": [
      {
        "product_id": 3,
        "quantity": 2,
        "unit_price": "150000.00"
      },
      {
        "product_id": 7,
        "quantity": 1,
        "unit_price": "50000.00"
      }
    ]
  }
}
```

**Response `400 Bad Request`** — stok produk tidak mencukupi  
**Response `401 Unauthorized`** — tanpa token  
**Response `422 Unprocessable Entity`** — validasi gagal

---

#### `GET /orders/{id}`

Menampilkan detail pesanan beserta seluruh item-nya. Hanya bisa diakses oleh pemilik pesanan.

- **Auth**: ✅ Diperlukan

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Detail pesanan berhasil diambil",
  "data": {
    "id": 12,
    "user_id": 1,
    "total_price": "350000.00",
    "status": "pending",
    "notes": "Tolong kirim cepat",
    "created_at": "2025-01-01T10:00:00.000000Z",
    "items": [
      {
        "id": 1,
        "product_id": 3,
        "quantity": 2,
        "unit_price": "150000.00",
        "product": {
          "id": 3,
          "name": "Nama Produk"
        }
      }
    ]
  }
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `403 Forbidden`** — pesanan milik user lain  
**Response `404 Not Found`** — ID tidak ditemukan

---

#### `PATCH /orders/{id}/status`

Memperbarui status pesanan.

- **Auth**: ✅ Diperlukan

**Request Body**
```json
{
  "status": "processing"
}
```

> Nilai `status` yang valid: `pending`, `processing`, `done`, `cancelled`

**Response `200 OK`**
```json
{
  "success": true,
  "message": "Status pesanan berhasil diperbarui",
  "data": {
    "id": 12,
    "status": "processing"
  }
}
```

**Response `401 Unauthorized`** — tanpa token  
**Response `422 Unprocessable Entity`** — nilai status tidak valid  
**Response `404 Not Found`** — ID tidak ditemukan

---

## Ringkasan Endpoint

| Method | Endpoint | Auth | Deskripsi |
|---|---|:---:|---|
| POST | `/auth/register` | — | Registrasi pengguna baru |
| POST | `/auth/login` | — | Login, mendapatkan token |
| POST | `/auth/logout` | ✅ | Logout, cabut token |
| GET | `/auth/profile` | ✅ | Lihat profil pengguna login |
| GET | `/categories` | — | Daftar semua kategori |
| POST | `/categories` | ✅ | Buat kategori baru |
| GET | `/categories/{id}` | — | Detail kategori + produknya |
| PUT | `/categories/{id}` | ✅ | Update kategori |
| DELETE | `/categories/{id}` | ✅ | Hapus kategori |
| GET | `/products` | — | Daftar produk aktif (pagination) |
| POST | `/products` | ✅ | Buat produk baru |
| GET | `/products/{id}` | — | Detail produk + kategori |
| PUT | `/products/{id}` | ✅ | Update produk |
| PATCH | `/products/{id}/toggle` | ✅ | Toggle status aktif produk |
| DELETE | `/products/{id}` | ✅ | Hapus produk |
| GET | `/orders` | ✅ | Daftar pesanan milik user login |
| POST | `/orders` | ✅ | Buat pesanan baru |
| GET | `/orders/{id}` | ✅ | Detail pesanan + item-itemnya |
| PATCH | `/orders/{id}/status` | ✅ | Update status pesanan |

---

## Fitur Bonus

| Fitur | Endpoint |
|---|---|
| Pencarian produk | `GET /products?search=nama` |
| Filter berdasarkan kategori | `GET /products?category_id=1` |
| Upload foto produk | Laravel Storage |
| Data dummy (Seeder) | Min. 3 kategori, 10 produk |
