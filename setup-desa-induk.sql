CREATE DATABASE IF NOT EXISTS desa_induk;
USE desa_induk;

-- 1. REGISTRY DESA
-- Daftar desa yang boleh mengirim data, beserta token autentikasinya
CREATE TABLE desa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_desa VARCHAR(10) NOT NULL UNIQUE,
  nama_desa VARCHAR(100) NOT NULL,
  api_token VARCHAR(64) NOT NULL UNIQUE,
  status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. AUDIT TRAIL TERPUSAT
-- Menyimpan seluruh event yang diterima dari semua desa, termasuk
-- payload old/new untuk keperluan monitoring perubahan data
CREATE TABLE inbound_queue (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_id VARCHAR(40),
  kode_desa VARCHAR(10) NOT NULL,
  table_name VARCHAR(100) NOT NULL,
  action VARCHAR(20) NOT NULL,           -- INSERT / UPDATE / DELETE
  record_id INT NULL,
  payload LONGTEXT,
  status ENUM('RECEIVED', 'PROCESSING','SUCCESS', 'FAILED') NOT NULL DEFAULT 'RECEIVED',
  error_message TEXT,
  received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at TIMESTAMP NULL,
  INDEX idx_desa_table (kode_desa, table_name),
  INDEX idx_event (event_id),
  INDEX idx_status (status)
);

-- 3. WAREHOUSE: dw_config
CREATE TABLE dw_config (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kode_desa VARCHAR(10) NOT NULL,
  source_id INT NOT NULL,
  app_key VARCHAR(100),
  nama_desa VARCHAR(100),
  kode_desa_bps VARCHAR(10),
  kode_pos INT,
  nama_kecamatan VARCHAR(100),
  kode_kecamatan VARCHAR(6),
  nama_kepala_camat VARCHAR(100),
  nip_kepala_camat VARCHAR(100),
  nama_kabupaten VARCHAR(100),
  kode_kabupaten VARCHAR(4),
  nama_propinsi VARCHAR(100),
  kode_propinsi VARCHAR(2),
  logo VARCHAR(100),
  lat VARCHAR(20),
  lng VARCHAR(20),
  alamat_kantor VARCHAR(200),
  telepon VARCHAR(50),
  kantor_desa VARCHAR(100),
  synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_source (kode_desa, source_id)
);

-- 4. WAREHOUSE: dw_user
-- Catatan: kolom password dan session TIDAK direplikasi ke induk
CREATE TABLE dw_user (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kode_desa VARCHAR(10) NOT NULL,
  source_id INT NOT NULL,
  username VARCHAR(100),
  id_grup INT,
  pamong_id INT,
  email VARCHAR(100),
  last_login DATETIME,
  active TINYINT,
  nama VARCHAR(50),
  phone VARCHAR(20),
  foto VARCHAR(100),
  synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_source (kode_desa, source_id)
);

-- 5. WAREHOUSE: dw_user_login_history
-- CREATE TABLE dw_user_login_history (
--   id BIGINT AUTO_INCREMENT PRIMARY KEY,
--   kode_desa VARCHAR(10) NOT NULL,
--   source_id BIGINT NOT NULL,
--   user_id INT,
--   login_at TIMESTAMP NULL,
--   ip_address VARCHAR(45),
--   synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--   UNIQUE KEY uniq_source (kode_desa, source_id)
-- );

-- 6. WAREHOUSE: dw_kategori
CREATE TABLE dw_kategori (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kode_desa VARCHAR(10) NOT NULL,
  source_id INT NOT NULL,
  kategori VARCHAR(100),
  tipe INT,
  urut TINYINT,
  enabled TINYINT,
  parent INT,
  slug VARCHAR(100),
  synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_source (kode_desa, source_id)
);

-- 7. WAREHOUSE: dw_artikel
CREATE TABLE dw_artikel (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kode_desa VARCHAR(10) NOT NULL,
  source_id INT NOT NULL,
  gambar VARCHAR(200),
  isi LONGTEXT,
  enabled INT,
  tgl_upload TIMESTAMP NULL,
  id_kategori INT,
  id_user INT,
  judul VARCHAR(200),
  gambar1 VARCHAR(200),
  gambar2 VARCHAR(200),
  gambar3 VARCHAR(200),
  dokumen VARCHAR(400),
  link_dokumen VARCHAR(200),
  boleh_komentar TINYINT,
  slug VARCHAR(200),
  hit INT,
  slider TINYINT,
  synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_source (kode_desa, source_id)
);

-- 8. WAREHOUSE: dw_komentar
CREATE TABLE dw_komentar (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kode_desa VARCHAR(10) NOT NULL,
  source_id INT NOT NULL,
  id_artikel INT,
  owner VARCHAR(50),
  email VARCHAR(50),
  subjek TINYTEXT,
  komentar TEXT,
  tgl_upload TIMESTAMP NULL,
  status TINYINT,
  tipe TINYINT,
  no_hp VARCHAR(15),
  is_archived TINYINT,
  synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_source (kode_desa, source_id)
);
