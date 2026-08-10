ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','dekan','wakil_dekan','dosen') NOT NULL DEFAULT 'user';

CREATE TABLE kegiatan_akademik (
  id int(11) NOT NULL AUTO_INCREMENT,
  dosen_id int(11) NOT NULL,
  nomor_surat varchar(50) DEFAULT NULL,
  nama_kegiatan varchar(255) NOT NULL,
  jenis_kegiatan varchar(100) NOT NULL,
  deskripsi text NOT NULL,
  tanggal_kegiatan date NOT NULL,
  lokasi varchar(255) NOT NULL,
  lampiran varchar(255) DEFAULT NULL,
  status enum('Pending','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Pending',
  catatan_admin text DEFAULT NULL,
  tanggal_pengajuan datetime NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (dosen_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE kegiatan_mahasiswa (
  id int(11) NOT NULL AUTO_INCREMENT,
  kegiatan_id int(11) NOT NULL,
  mahasiswa_id int(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (kegiatan_id) REFERENCES kegiatan_akademik(id) ON DELETE CASCADE,
  FOREIGN KEY (mahasiswa_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert dummy Dosen user
INSERT INTO users (nama_lengkap, username, password, role) VALUES ('Dr. Dosen Penguji', 'dosen1234', 'dosen1234', 'dosen');
