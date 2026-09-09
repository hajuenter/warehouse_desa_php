# Monitoring Web

Dashboard monitoring untuk memantau riwayat sinkronisasi data desa secara real-time.

## Jalankan Secara Lokal (Dev)

```bash
npm install
npm run dev
```

Buka http://localhost:5173

## Build untuk Production

```bash
npm run build
```

Hasil build ada di folder `dist/`.

## Deploy dengan Docker

### Build Image

```bash
docker build -t monitoring-web .
```

### Jalankan Container

```bash
docker run -d --name monitoring-app -p 8080:80 monitoring-web
```

- `-d` = running di background (tidak nempel di terminal)
- `--name monitoring-app` = nama container
- `-p 8080:80` = port 8080 di komputer → port 80 di container

Buka http://localhost:8080

### Dengan Environment Variable

```bash
docker build --build-arg VITE_API_URL=http://localhost:3000/api -t monitoring-web .
docker run -d --name monitoring-app -p 8080:80 monitoring-web
```

## Update Code

Setelah ada perubahan code, rebuild image dan jalankan ulang container:

```bash
docker stop monitoring-app
docker rm monitoring-app
docker build -t monitoring-web .
docker run -d --name monitoring-app -p 8080:80 monitoring-web
```

## Command Penting

| Kegunaan | Command |
|---|---|
| Lihat container yang sedang jalan | `docker ps` |
| Lihat semua container | `docker ps -a` |
| Stop container | `docker stop monitoring-app` |
| Hapus container | `docker rm monitoring-app` |
| Lihat logs | `docker logs monitoring-app` |
| Lihat logs secara real-time | `docker logs -f monitoring-app` |
| Lihat semua image | `docker images` |
| Hapus image | `docker rmi monitoring-web` |

## Troubleshooting

**Port sudah dipakai**

Ganti port lain:
```bash
docker run -d --name monitoring-app -p 8081:80 monitoring-web
```

**Container tidak bisa start**

Cek logs:
```bash
docker logs monitoring-app
```

**Ingin mulai dari awal (clean slate)**

```bash
docker stop monitoring-app
docker rm monitoring-app
docker rmi monitoring-web
docker build -t monitoring-web .
docker run -d --name monitoring-app -p 8080:80 monitoring-web
```
