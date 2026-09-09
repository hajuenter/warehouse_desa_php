DELIMITER $$

-- 1. CONFIG
DROP TRIGGER IF EXISTS trg_config_insert$$
CREATE TRIGGER trg_config_insert
AFTER INSERT ON config
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.id, 'INSERT', 'config', NEW.id,
    JSON_OBJECT(
      'app_key', NEW.app_key, 'nama_desa', NEW.nama_desa, 'kode_desa', NEW.kode_desa,
      'kode_desa_bps', NEW.kode_desa_bps, 'kode_pos', NEW.kode_pos,
      'nama_kecamatan', NEW.nama_kecamatan, 'kode_kecamatan', NEW.kode_kecamatan,
      'nama_kepala_camat', NEW.nama_kepala_camat, 'nip_kepala_camat', NEW.nip_kepala_camat,
      'nama_kabupaten', NEW.nama_kabupaten, 'kode_kabupaten', NEW.kode_kabupaten,
      'nama_propinsi', NEW.nama_propinsi, 'kode_propinsi', NEW.kode_propinsi,
      'logo', NEW.logo, 'lat', NEW.lat, 'lng', NEW.lng,
      'alamat_kantor', NEW.alamat_kantor, 'telepon', NEW.telepon, 'kantor_desa', NEW.kantor_desa
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_config_update$$
CREATE TRIGGER trg_config_update
AFTER UPDATE ON config
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.id, 'UPDATE', 'config', NEW.id,
    JSON_OBJECT(
      'old', JSON_OBJECT(
        'app_key', OLD.app_key, 'nama_desa', OLD.nama_desa, 'kode_desa', OLD.kode_desa,
        'kode_desa_bps', OLD.kode_desa_bps, 'kode_pos', OLD.kode_pos,
        'nama_kecamatan', OLD.nama_kecamatan, 'kode_kecamatan', OLD.kode_kecamatan,
        'nama_kepala_camat', OLD.nama_kepala_camat, 'nip_kepala_camat', OLD.nip_kepala_camat,
        'nama_kabupaten', OLD.nama_kabupaten, 'kode_kabupaten', OLD.kode_kabupaten,
        'nama_propinsi', OLD.nama_propinsi, 'kode_propinsi', OLD.kode_propinsi,
        'logo', OLD.logo, 'lat', OLD.lat, 'lng', OLD.lng,
        'alamat_kantor', OLD.alamat_kantor, 'telepon', OLD.telepon, 'kantor_desa', OLD.kantor_desa
      ),
      'new', JSON_OBJECT(
        'app_key', NEW.app_key, 'nama_desa', NEW.nama_desa, 'kode_desa', NEW.kode_desa,
        'kode_desa_bps', NEW.kode_desa_bps, 'kode_pos', NEW.kode_pos,
        'nama_kecamatan', NEW.nama_kecamatan, 'kode_kecamatan', NEW.kode_kecamatan,
        'nama_kepala_camat', NEW.nama_kepala_camat, 'nip_kepala_camat', NEW.nip_kepala_camat,
        'nama_kabupaten', NEW.nama_kabupaten, 'kode_kabupaten', NEW.kode_kabupaten,
        'nama_propinsi', NEW.nama_propinsi, 'kode_propinsi', NEW.kode_propinsi,
        'logo', NEW.logo, 'lat', NEW.lat, 'lng', NEW.lng,
        'alamat_kantor', NEW.alamat_kantor, 'telepon', NEW.telepon, 'kantor_desa', NEW.kantor_desa
      )
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_config_delete$$
CREATE TRIGGER trg_config_delete
AFTER DELETE ON config
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (UUID(), OLD.id, 'DELETE', 'config', OLD.id, JSON_OBJECT('id', OLD.id), 'pending', 0, NOW());
END$$


-- 2. USER
-- Catatan: kolom password dan session tidak diikutkan di payload
DROP TRIGGER IF EXISTS trg_user_insert$$
CREATE TRIGGER trg_user_insert
AFTER INSERT ON user
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'INSERT', 'user', NEW.id,
    JSON_OBJECT(
      'username', NEW.username, 'id_grup', NEW.id_grup, 'pamong_id', NEW.pamong_id,
      'email', NEW.email, 'last_login', NEW.last_login, 'active', NEW.active,
      'nama', NEW.nama, 'phone', NEW.phone, 'foto', NEW.foto
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_user_update$$
CREATE TRIGGER trg_user_update
AFTER UPDATE ON user
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'UPDATE', 'user', NEW.id,
    JSON_OBJECT(
      'old', JSON_OBJECT(
        'username', OLD.username, 'id_grup', OLD.id_grup, 'pamong_id', OLD.pamong_id,
        'email', OLD.email, 'last_login', OLD.last_login, 'active', OLD.active,
        'nama', OLD.nama, 'phone', OLD.phone, 'foto', OLD.foto
      ),
      'new', JSON_OBJECT(
        'username', NEW.username, 'id_grup', NEW.id_grup, 'pamong_id', NEW.pamong_id,
        'email', NEW.email, 'last_login', NEW.last_login, 'active', NEW.active,
        'nama', NEW.nama, 'phone', NEW.phone, 'foto', NEW.foto
      )
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_user_delete$$
CREATE TRIGGER trg_user_delete
AFTER DELETE ON user
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (UUID(), OLD.config_id, 'DELETE', 'user', OLD.id, JSON_OBJECT('id', OLD.id), 'pending', 0, NOW());
END$$


-- 3. USER_LOGIN_HISTORY
-- DROP TRIGGER IF EXISTS trg_user_login_history_insert$$
-- CREATE TRIGGER trg_user_login_history_insert
-- AFTER INSERT ON user_login_history
-- FOR EACH ROW
-- BEGIN
--   INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
--   VALUES (
--     UUID(), NEW.config_id, 'INSERT', 'user_login_history', NEW.id,
--     JSON_OBJECT('user_id', NEW.user_id, 'login_at', NEW.login_at, 'ip_address', NEW.ip_address),
--     'pending', 0, NOW()
--   );
-- END$$

-- DROP TRIGGER IF EXISTS trg_user_login_history_update$$
-- CREATE TRIGGER trg_user_login_history_update
-- AFTER UPDATE ON user_login_history
-- FOR EACH ROW
-- BEGIN
--   INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
--   VALUES (
--     UUID(), NEW.config_id, 'UPDATE', 'user_login_history', NEW.id,
--     JSON_OBJECT(
--       'old', JSON_OBJECT('user_id', OLD.user_id, 'login_at', OLD.login_at, 'ip_address', OLD.ip_address),
--       'new', JSON_OBJECT('user_id', NEW.user_id, 'login_at', NEW.login_at, 'ip_address', NEW.ip_address)
--     ),
--     'pending', 0, NOW()
--   );
-- END$$

-- DROP TRIGGER IF EXISTS trg_user_login_history_delete$$
-- CREATE TRIGGER trg_user_login_history_delete
-- AFTER DELETE ON user_login_history
-- FOR EACH ROW
-- BEGIN
--   INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
--   VALUES (UUID(), OLD.config_id, 'DELETE', 'user_login_history', OLD.id, JSON_OBJECT('id', OLD.id), 'pending', 0, NOW());
-- END$$


-- 4. KATEGORI
DROP TRIGGER IF EXISTS trg_kategori_insert$$
CREATE TRIGGER trg_kategori_insert
AFTER INSERT ON kategori
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'INSERT', 'kategori', NEW.id,
    JSON_OBJECT(
      'kategori', NEW.kategori,
      'tipe', NEW.tipe,
      'urut', NEW.urut,
      'enabled', NEW.enabled,
      'parent', NEW.parrent,
      'slug', NEW.slug
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_kategori_update$$
CREATE TRIGGER trg_kategori_update
AFTER UPDATE ON kategori
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'UPDATE', 'kategori', NEW.id,
    JSON_OBJECT(
      'old', JSON_OBJECT(
        'kategori', OLD.kategori, 'tipe', OLD.tipe, 'urut', OLD.urut,
        'enabled', OLD.enabled, 'parent', OLD.parrent, 'slug', OLD.slug
      ),
      'new', JSON_OBJECT(
        'kategori', NEW.kategori, 'tipe', NEW.tipe, 'urut', NEW.urut,
        'enabled', NEW.enabled, 'parent', NEW.parrent, 'slug', NEW.slug
      )
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_kategori_delete$$
CREATE TRIGGER trg_kategori_delete
AFTER DELETE ON kategori
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (UUID(), OLD.config_id, 'DELETE', 'kategori', OLD.id, JSON_OBJECT('id', OLD.id), 'pending', 0, NOW());
END$$


-- 5. ARTIKEL
DROP TRIGGER IF EXISTS trg_artikel_insert$$
CREATE TRIGGER trg_artikel_insert
AFTER INSERT ON artikel
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'INSERT', 'artikel', NEW.id,
    JSON_OBJECT(
      'gambar', NEW.gambar, 'isi', NEW.isi, 'enabled', NEW.enabled, 'tgl_upload', NEW.tgl_upload,
      'id_kategori', NEW.id_kategori, 'id_user', NEW.id_user, 'judul', NEW.judul,
      'gambar1', NEW.gambar1, 'gambar2', NEW.gambar2, 'gambar3', NEW.gambar3,
      'dokumen', NEW.dokumen, 'link_dokumen', NEW.link_dokumen, 'boleh_komentar', NEW.boleh_komentar,
      'slug', NEW.slug, 'hit', NEW.hit, 'slider', NEW.slider
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_artikel_update$$
CREATE TRIGGER trg_artikel_update
AFTER UPDATE ON artikel
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'UPDATE', 'artikel', NEW.id,
    JSON_OBJECT(
      'old', JSON_OBJECT(
        'gambar', OLD.gambar, 'isi', OLD.isi, 'enabled', OLD.enabled, 'tgl_upload', OLD.tgl_upload,
        'id_kategori', OLD.id_kategori, 'id_user', OLD.id_user, 'judul', OLD.judul,
        'gambar1', OLD.gambar1, 'gambar2', OLD.gambar2, 'gambar3', OLD.gambar3,
        'dokumen', OLD.dokumen, 'link_dokumen', OLD.link_dokumen, 'boleh_komentar', OLD.boleh_komentar,
        'slug', OLD.slug, 'hit', OLD.hit, 'slider', OLD.slider
      ),
      'new', JSON_OBJECT(
        'gambar', NEW.gambar, 'isi', NEW.isi, 'enabled', NEW.enabled, 'tgl_upload', NEW.tgl_upload,
        'id_kategori', NEW.id_kategori, 'id_user', NEW.id_user, 'judul', NEW.judul,
        'gambar1', NEW.gambar1, 'gambar2', NEW.gambar2, 'gambar3', NEW.gambar3,
        'dokumen', NEW.dokumen, 'link_dokumen', NEW.link_dokumen, 'boleh_komentar', NEW.boleh_komentar,
        'slug', NEW.slug, 'hit', NEW.hit, 'slider', NEW.slider
      )
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_artikel_delete$$
CREATE TRIGGER trg_artikel_delete
AFTER DELETE ON artikel
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (UUID(), OLD.config_id, 'DELETE', 'artikel', OLD.id, JSON_OBJECT('id', OLD.id), 'pending', 0, NOW());
END$$


-- 6. KOMENTAR
DROP TRIGGER IF EXISTS trg_komentar_insert$$
CREATE TRIGGER trg_komentar_insert
AFTER INSERT ON komentar
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'INSERT', 'komentar', NEW.id,
    JSON_OBJECT(
      'id_artikel', NEW.id_artikel, 'owner', NEW.owner, 'email', NEW.email, 'subjek', NEW.subjek,
      'komentar', NEW.komentar, 'tgl_upload', NEW.tgl_upload, 'status', NEW.status,
      'tipe', NEW.tipe, 'no_hp', NEW.no_hp, 'is_archived', NEW.is_archived
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_komentar_update$$
CREATE TRIGGER trg_komentar_update
AFTER UPDATE ON komentar
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (
    UUID(), NEW.config_id, 'UPDATE', 'komentar', NEW.id,
    JSON_OBJECT(
      'old', JSON_OBJECT(
        'id_artikel', OLD.id_artikel, 'owner', OLD.owner, 'email', OLD.email, 'subjek', OLD.subjek,
        'komentar', OLD.komentar, 'tgl_upload', OLD.tgl_upload, 'status', OLD.status,
        'tipe', OLD.tipe, 'no_hp', OLD.no_hp, 'is_archived', OLD.is_archived
      ),
      'new', JSON_OBJECT(
        'id_artikel', NEW.id_artikel, 'owner', NEW.owner, 'email', NEW.email, 'subjek', NEW.subjek,
        'komentar', NEW.komentar, 'tgl_upload', NEW.tgl_upload, 'status', NEW.status,
        'tipe', NEW.tipe, 'no_hp', NEW.no_hp, 'is_archived', NEW.is_archived
      )
    ),
    'pending', 0, NOW()
  );
END$$

DROP TRIGGER IF EXISTS trg_komentar_delete$$
CREATE TRIGGER trg_komentar_delete
AFTER DELETE ON komentar
FOR EACH ROW
BEGIN
  INSERT INTO webhook_queue (event_id, config_id, event_type, table_name, record_id, payload, status, retry_count, received_at)
  VALUES (UUID(), OLD.config_id, 'DELETE', 'komentar', OLD.id, JSON_OBJECT('id', OLD.id), 'pending', 0, NOW());
END$$

DELIMITER ;
