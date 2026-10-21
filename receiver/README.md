# Receiver Docker

Docker setup untuk menjalankan **Receiver** (Warehouse Desa) menggunakan Docker Compose.

## Prasyarat

Pastikan sudah menginstall:

- [Docker](https://www.docker.com/)
- MariaDB yang sudah berjalan

## Cara Install

### 1. Siapkan database MariaDB

Receiver tidak membuat database secara otomatis. Buat database dan tabel secara manual
dengan mengimpor file `setup-desa-induk.sql` ke MariaDB Anda:

```bash
mysql -h <host_mariadb> -u <user> -p < setup-desa-induk.sql
```

### 2. Download file yang diperlukan

Download file berikut:

- `docker-compose.yml`
- `.env.example`

Letakkan file tersebut dalam **satu folder yang sama**.

### 3. Buat file `.env`

Rename:

```text
.env.example
```

menjadi:

```text
.env
```

### 4. Isi konfigurasi `.env`

Buka file `.env` dan isi dengan konfigurasi yang sesuai:

| Variable         | Keterangan                                                                  |
| ---------------- | --------------------------------------------------------------------------- |
| `DB_HOST`        | Host MariaDB. Gunakan `host.docker.internal` jika MariaDB di host yang sama |
| `DB_PORT`        | Port MariaDB (default `3306`)                                               |
| `DB_USER`        | User MariaDB                                                                |
| `DB_PASSWORD`    | Password MariaDB                                                            |
| `DB_NAME`        | Nama database (default `desa_induk`)                                        |
| `JWT_SECRET`     | Secret key untuk JWT token                                                  |
| `ADMIN_PASSWORD` | Password admin                                                              |
| `ADMIN_EMAIL`    | Email admin                                                                 |
| `SMTP_USER`      | Username SMTP                                                               |
| `SMTP_PASS`      | Password SMTP                                                               |
| `FRONTEND_URL`   | URL frontend (monitoring_web), dipakai untuk link reset password            |

> Semua variabel di `.env` otomatis diteruskan ke container. Setelah mengubah
> `.env`, jalankan `docker-compose up -d` untuk menerapkan.

### 5. Jalankan Docker

Buka terminal pada folder tersebut, kemudian jalankan:

```bash
docker-compose up -d
```

Tunggu beberapa saat hingga container berhasil berjalan.

### 6. Buka Receiver

Buka browser dan akses:

**http://localhost:3000**

### 7. Login

Gunakan akun administrator:

| Konfigurasi | Nilai       |
| ----------- | ----------- |
| Username    | `admin`     |
| Password    | `admin123@` |

---

## Install Ulang

Jika ingin melakukan instalasi ulang, jalankan:

```bash
docker-compose down
```

Setelah itu jalankan kembali:

```bash
docker-compose up -d
```

> **Perhatian:** Data tersimpan di MariaDB Anda, bukan di container. Hapus container
> tidak akan menghapus database.

---

## Akses Layanan

| Layanan  | URL                   |
| -------- | --------------------- |
| Receiver | http://localhost:3000 |

---

## Perintah Docker

| Perintah                 | Fungsi                               |
| ------------------------ | ------------------------------------ |
| `docker-compose up -d`   | Menjalankan container di background  |
| `docker-compose down`    | Menghentikan dan menghapus container |
| `docker-compose restart` | Restart container                    |

---

## Environment Variables

| Variable         | Default                | Keterangan                 |
| ---------------- | ---------------------- | -------------------------- |
| `DB_HOST`        | `host.docker.internal` | Host database MariaDB      |
| `DB_PORT`        | `3306`                 | Port database MariaDB      |
| `DB_USER`        | `root`                 | User database MariaDB      |
| `DB_PASSWORD`    | `rahasia123`           | Password database MariaDB  |
| `DB_NAME`        | `desa_induk`           | Nama database              |
| `JWT_SECRET`     | `-`                    | Secret key untuk JWT token |
| `ADMIN_PASSWORD` | `admin123@`            | Password admin             |
| `ADMIN_EMAIL`    | `-`                    | Email admin                |
| `SMTP_USER`      | `-`                    | Username SMTP              |
| `SMTP_PASS`      | `-`                    | Password SMTP              |

---

## Database

Database `desa_induk` dibuat manual dengan mengimpor `setup-desa-induk.sql`.
Tabel yang dibuat:

| Tabel           | Keterangan                  |
| --------------- | --------------------------- |
| `desa`          | Registry desa               |
| `inbound_queue` | Audit trail event masuk     |
| `dw_config`     | Warehouse: konfigurasi desa |
| `dw_user`       | Warehouse: data user        |
| `dw_kategori`   | Warehouse: kategori artikel |
| `dw_artikel`    | Warehouse: artikel          |
| `dw_komentar`   | Warehouse: komentar artikel |
