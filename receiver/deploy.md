# Deploy Receiver ke Server

Panduan singkat deploy **Receiver** (image `hajuenter/receiver_only:latest`) yang
terhubung ke MariaDB server yang sudah ada. Receiver tidak membundel database.

## Prasyarat

- Docker & Docker Compose di server
- MariaDB sudah terpasang di server
- File `setup-desa-induk.sql`
- Akses ke Docker Hub (`docker login` jika perlu)

## Alur Deploy

### 1. Siapkan Database

Import schema (langsung membuat database `desa_induk`):

```bash
mysql -h <host_mariadb> -u <user> -p < setup-desa-induk.sql
```

### 2. Cek MariaDB menerima koneksi dari luar

Receiver berjalan di container, jadi MariaDB harus menerima koneksi TCP dari luar.

**a. Cek `bind-address`:**

```bash
mysql -u root -p -e "SELECT @@bind_address;"
```

- `127.0.0.1` → hanya menerima koneksi lokal (container akan gagal).
- `0.0.0.0` → menerima dari semua interface (diperlukan).
- `NULL` / kosong → opsi tidak diset, artinya menerima dari semua interface
  (sudah siap, tidak perlu diubah).

Jika masih `127.0.0.1`, ubah di file config (biasanya
`/etc/mysql/mariadb.conf.d/50-server.cnf`):

```ini
bind-address = 0.0.0.0
```

lalu restart MariaDB (`systemctl restart mariadb`).

Cek juga port dan daftar user/akses yang sudah ada:

```sql
SELECT @@port;                 -- port yang didengarkan (default 3306)
SELECT user, host FROM mysql.user;   -- daftar user + host yang diizinkan
```

Artinya: kalau tidak ada baris `'user'@'%'` (host selain `localhost`/`127.0.0.1`),
maka container tidak akan bisa masuk.

**b. Beri akses untuk container** (pilih salah satu):

Opsi 1 — buat user khusus (disarankan):

```sql
CREATE USER 'receiver'@'%' IDENTIFIED BY 'passwordku';
GRANT ALL PRIVILEGES ON desa_induk.* TO 'receiver'@'%';
FLUSH PRIVILEGES;
```

Opsi 2 — pakai ulang user yang sudah ada (contoh `root`) agar bisa diakses dari host lain:

```sql
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '';
FLUSH PRIVILEGES;
```

Cek hasil grant:

```sql
SHOW GRANTS FOR 'receiver'@'%';   -- atau nama user/host yang dipakai
```

Catatan: koneksi dari Docker bisa terlihat sebagai `localhost` atau IP jaringan
Docker (mis. `172.17.0.x`) tergantung platform. Kalau `host.docker.internal`
ternyata muncul sebagai `localhost`, user `'user'@'localhost'` yang sudah ada pun
cukup.

**c. Firewall:** pastikan port 3306 (atau `@@port`) terbuka jika container di
server berbeda.

### 3. Siapkan Folder Deploy

Di server, cukup siapkan folder berisi:

```
receiver/
├── docker-compose.yml
└── .env
```

Buat `.env` dari contoh (`.env.example`) lalu isi:

```env
DB_HOST=host.docker.internal   # MariaDB di server yang sama
DB_PORT=3306
DB_USER=receiver
DB_PASSWORD=passwordku
DB_NAME=desa_induk

JWT_SECRET=ganti-dengan-secret-panjang
ADMIN_USERNAME=admin
ADMIN_PASSWORD=ganti-password-admin
ADMIN_EMAIL=email@contoh.com

FRONTEND_URL=http://<ip-server>:8090   # isi setelah monitoring_web deploy
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
```

- Jika MariaDB di **server yang sama** dengan Docker → `DB_HOST=host.docker.internal`.
- Jika MariaDB di **server/IP lain** → isi IP/hostname-nya.

Semua variabel di `.env` otomatis diteruskan ke container.

### 4. Pastikan Network `wh_shared` Ada

`docker-compose.yml` memakai network eksternal `wh_shared`. Buat jika belum ada:

```bash
docker network create wh_shared
```

### 5. Jalankan

```bash
docker-compose up -d
```

### 6. Verifikasi

Cek health (publik, tanpa JWT):

```bash
curl http://<ip-server>:3000/api/health
```

Berhasil:

```json
{"status":"ok","message":"Receiver berhasil berjalan",
 "database":{"connected":true,"host":"...","port":"3306","name":"desa_induk",...}}
```

Gagal konek DB (HTTP 503):

```json
{"status":"error","message":"Receiver berjalan, tetapi gagal terkoneksi ke database",
 "database":{"connected":false,...,"error":"SQLSTATE[HY000] [2002] Connection refused"}}
```

## Mengubah Konfigurasi Setelah Deploy

Ubah `.env`, lalu terapkan ulang:

```bash
docker-compose up -d
```

Contoh: setelah monitoring_web deploy, set `FRONTEND_URL=http://<ip-server>:8090`.

## Troubleshooting

| Gejala                          | Kemungkinan Penyebab                                       |
| ------------------------------- | ---------------------------------------------------------- |
| `Connection refused`            | `bind-address` masih `127.0.0.1`, atau MariaDB/port salah  |
| `Access denied for user`        | User/grant tidak cocok host container, atau password salah |
| `Unknown database 'desa_induk'` | SQL belum diimport / `DB_NAME` salah                       |
| Gagal start: network not found  | Network `wh_shared` belum dibuat                           |
