-- =============================================
-- DATABASE: lab_rpl 
-- =============================================

CREATE DATABASE IF NOT EXISTS lab_rpl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lab_rpl;

-- ── Tabel user ──
CREATE TABLE IF NOT EXISTS user (
    id_user    INT AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(100) NOT NULL,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Tabel laboratorium ──
CREATE TABLE IF NOT EXISTS laboratorium (
    id_lab    INT AUTO_INCREMENT PRIMARY KEY,
    nama_lab  VARCHAR(100) NOT NULL,
    lokasi    VARCHAR(150) NOT NULL,
    kapasitas INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Tabel alat_barang (REVISI 1: pisah stok kondisi) ──
CREATE TABLE IF NOT EXISTS alat_barang (
    id_alat          INT AUTO_INCREMENT PRIMARY KEY,
    nama_alat        VARCHAR(100) NOT NULL,
    jumlah_baik      INT NOT NULL DEFAULT 0,   -- stok kondisi baik
    jumlah_rusak_ringan INT NOT NULL DEFAULT 0, -- stok rusak ringan
    jumlah_rusak_berat  INT NOT NULL DEFAULT 0, -- stok rusak berat
    id_lab           INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lab) REFERENCES laboratorium(id_lab) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Tabel peminjaman ──
CREATE TABLE IF NOT EXISTS peminjaman (
    id_pinjam    INT AUTO_INCREMENT PRIMARY KEY,
    id_user      INT NOT NULL,
    id_alat      INT NOT NULL,
    tgl_pinjam   DATE NOT NULL,
    tgl_kembali_rencana DATE NOT NULL,         
    keperluan    VARCHAR(255) DEFAULT NULL,      
    status       ENUM('menunggu','disetujui','ditolak','selesai') DEFAULT 'menunggu',
    alasan_tolak VARCHAR(255) DEFAULT NULL,
    disetujui_oleh INT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user)  REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_alat)  REFERENCES alat_barang(id_alat) ON DELETE CASCADE,
    FOREIGN KEY (disetujui_oleh) REFERENCES user(id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Tabel detail_pinjam ──
CREATE TABLE IF NOT EXISTS detail_pinjam (
    id_detail        INT AUTO_INCREMENT PRIMARY KEY,
    id_pinjam        INT NOT NULL,
    id_alat          INT NOT NULL,
    tgl_pinjam       DATE NOT NULL,
    tgl_kembali_rencana DATE NOT NULL,
    tgl_kembali_aktual  DATE DEFAULT NULL,
    status           ENUM('dipinjam','menunggu_cek','selesai') DEFAULT 'dipinjam',
    kondisi_kembali  ENUM('baik','rusak_ringan','rusak_berat') DEFAULT NULL,
    catatan_kondisi  VARCHAR(255) DEFAULT NULL,
    -- Denda
    denda_terlambat  DECIMAL(10,2) DEFAULT 0,
    denda_kerusakan  DECIMAL(10,2) DEFAULT 0,
    total_denda      DECIMAL(10,2) DEFAULT 0,
    denda_lunas      TINYINT(1) DEFAULT 0,
    -- Admin yang cek
    dicek_oleh       INT DEFAULT NULL,
    FOREIGN KEY (id_pinjam) REFERENCES peminjaman(id_pinjam) ON DELETE CASCADE,
    FOREIGN KEY (id_alat)   REFERENCES alat_barang(id_alat)  ON DELETE CASCADE,
    FOREIGN KEY (dicek_oleh) REFERENCES user(id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Tabel konfigurasi denda ──
CREATE TABLE IF NOT EXISTS konfigurasi_denda (
    id_config   INT AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(50) NOT NULL UNIQUE,
    nama        VARCHAR(100) NOT NULL,
    nilai       DECIMAL(10,2) NOT NULL DEFAULT 0,
    satuan      VARCHAR(30) DEFAULT 'hari',
    keterangan  VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ── SEED DATA ──

-- Akun (password = 'password')
INSERT INTO user (nama, username, password, role) VALUES
('Administrator',  'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Budi Santoso',   'budi',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Siti Rahayu',    'siti',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Ahmad Fauzi',    'ahmad', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Laboratorium
INSERT INTO laboratorium (nama_lab, lokasi, kapasitas) VALUES
('Lab RPL 1',    'Gedung A Lantai 2', 30),
('Lab RPL 2',    'Gedung B Lantai 1', 25),
('Lab Jaringan', 'Gedung C Lantai 3', 20);

-- Alat (stok per kondisi)
INSERT INTO alat_barang (nama_alat, jumlah_baik, jumlah_rusak_ringan, jumlah_rusak_berat, id_lab) VALUES
('Laptop ASUS',       13, 2, 0, 1),
('Mouse Wireless',    18, 2, 0, 1),
('Keyboard USB',      15, 2, 1, 1),
('Kabel LAN Cat6',    28, 2, 0, 2),
('Switch Hub 8 Port',  4, 1, 0, 2),
('Raspberry Pi 4',     7, 1, 0, 2),
('Arduino Uno',        9, 2, 1, 3),
('Solder Set',         5, 1, 0, 3),
('Multimeter Digital', 8, 1, 1, 3),
('Crimping Tool',      7, 1, 0, 2);

-- Konfigurasi denda
INSERT INTO konfigurasi_denda (kode, nama, nilai, satuan, keterangan) VALUES
('denda_terlambat',   'Denda Keterlambatan',  2000, 'per hari',   'Denda per hari keterlambatan pengembalian'),
('denda_rusak_ringan','Denda Rusak Ringan',   25000, 'per item',  'Denda jika alat dikembalikan rusak ringan'),
('denda_rusak_berat', 'Denda Rusak Berat',   100000, 'per item',  'Denda jika alat dikembalikan rusak berat');
