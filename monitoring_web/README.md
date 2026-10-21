# Monitoring Web Docker

Dashboard monitoring untuk memantau riwayat sinkronisasi data desa secara real-time.

## Prasyarat

Pastikan sudah menginstall:

- [Docker](https://www.docker.com/)

## Cara Install

### 1. Download file yang diperlukan

Download file berikut:

- `docker-compose.yml`

### 2. Jalankan Docker

Buka terminal pada folder tersebut, kemudian jalankan:

```bash
docker-compose up -d
```

Tunggu beberapa saat hingga container berhasil berjalan.

### 3. Buka Monitoring Web

Buka browser dan akses:

**http://localhost:8080**

---

## Install Ulang

Jika ingin melakukan instalasi ulang dari awal, jalankan:

```bash
docker-compose down
```

Setelah itu jalankan kembali:

```bash
docker-compose up -d
```

---

## Akses Layanan

| Layanan       | URL                   |
| ------------- | --------------------- |
| Monitoring Web | http://localhost:8080 |

---

## Perintah Docker

| Perintah                 | Fungsi                           |
| ------------------------ | -------------------------------- |
| `docker-compose up -d`   | Menjalankan container di background |
| `docker-compose down`    | Menghentikan dan menghapus container |
| `docker-compose restart` | Restart container                |

---

## Jalankan Secara Lokal (Dev)

Jika ingin tanpa Docker:

```bash
npm install
npm run dev
```

Buka http://localhost:5173

---

## Troubleshooting

**Port sudah dipakai**

Ganti port di `docker-compose.yml`:
```yaml
ports:
  - "8081:80"
```

**Container tidak bisa start**

Cek logs:
```bash
docker-compose logs
```
