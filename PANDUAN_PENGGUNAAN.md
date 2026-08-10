# Panduan Penggunaan Sistem Surat Akademik ULM

Panduan ini berisi langkah-langkah lengkap dari tahap instalasi, pengaturan database, hingga cara menggunakan fitur utama termasuk verifikasi QR Code.

---

## 🚀 1. Persiapan & Instalasi Database

1. Pastikan komputer Anda sudah terinstal **Laragon** (atau XAMPP).
2. Pindahkan folder proyek ini (`TA_ULM`) ke dalam folder root server Anda:
   - Laragon: `C:\laragon\www\`
   - XAMPP: `C:\xampp\htdocs\`
3. Buka Laragon/XAMPP dan jalankan (Start) service **Apache/Nginx** dan **MySQL**.
4. Buka phpMyAdmin atau HeidiSQL, lalu buat database baru dengan nama: `db_surat_ulm`
5. Import file SQL:
   - Cari file `db_surat_ulm.sql` yang ada di dalam folder `database mysql` di dalam proyek ini.
   - Import file tersebut ke database `db_surat_ulm` yang baru saja Anda buat.

---

## 🌐 2. Mengakses Website

Setelah database terpasang, Anda bisa membuka website ini di browser dengan mengetikkan:

**Jika menggunakan Laragon:**
`http://ta_ulm.test` atau `http://localhost/TA_ULM` 
*(Catatan: Jika web server Anda berjalan di port tertentu seperti 8080, tambahkan portnya misal `http://localhost:8080/TA_ULM`)*

### 🔑 Akun Login Default
Berikut adalah daftar akun yang bisa digunakan untuk login:

| Role | Username | Password |
|---|---|---|
| **Admin** | `admin1234` | `admin1234` |
| **Dekan** | `dekan1234` | `dekan1234` |
| **Mahasiswa** | `adiza1234` | `adiza1234` |

---

## 📱 3. Simulasi Pengajuan Surat & Fitur QR Code

Untuk mencoba seluruh alur pengajuan surat dan fitur QR code, ikuti skenario ini:

### Langkah A: Mengajukan Surat (Mahasiswa)
1. Buka website dan login menggunakan akun **Mahasiswa** (`adiza1234`).
2. Di sidebar kiri, pilih jenis surat yang ingin diajukan (Contoh: `Surat Tugas Dosen` atau `Keterlambatan UKT`).
3. Isi form pengajuan dengan lengkap lalu klik **Ajukan Surat**.
4. Logout akun mahasiswa.

### Langkah B: Memverifikasi Surat (Admin)
1. Login menggunakan akun **Admin** (`admin1234`).
2. Pada tabel "Surat Masuk", cari surat yang baru diajukan tadi.
3. Klik tombol **Detail/Verifikasi** (Warna biru/hijau).
4. Ubah **Status** menjadi **"Selesai"**, lalu simpan perubahan.
5. Setelah status berubah jadi selesai, tombol **Cetak PDF** (Warna Merah/Orange) akan muncul. Klik tombol tersebut untuk membuka/mengunduh file PDF.

### Langkah C: Scan QR Code Verifikasi Asli (HP)
Di bagian bawah PDF yang telah dicetak, terdapat tanda tangan digital Dekan beserta sebuah **QR Code**. 

> ⚠️ **PENTING UNTUK FITUR SCAN QR:**
> Karena website dijalankan di *localhost* (offline di laptop), jika Anda men-scan QR code pakai HP, HP tidak bisa mengakses `localhost`. 
> 
> **Solusi agar bisa di-scan pakai HP (Untuk Presentasi/Demo):**
> 1. Pastikan HP dan Laptop terhubung ke jaringan **WiFi yang sama**.
> 2. Cek **IP Lokal Laptop** Anda (Contoh: `192.168.1.5`).
> 3. Buka web di browser laptop menggunakan IP tersebut (contoh: `http://192.168.1.5:8080/TA_ULM`).
> 4. Saat Anda mencetak PDF melalui alamat IP tersebut, QR Code akan otomatis menyesuaikan diri dan menghasilkan URL IP tersebut.
> 5. Sekarang, **Scan QR Code menggunakan kamera HP**.
> 6. HP akan langsung membuka halaman **Verifikasi Surat**, menampilkan badge hijau ("Dokumen Asli & Sah") beserta data detail surat dan info Dekan.
