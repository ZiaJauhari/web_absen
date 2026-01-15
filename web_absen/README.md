# Sistem Absensi Karyawan

Sistem absensi berbasis web dengan fitur geolokasi untuk tracking kehadiran karyawan.

## Fitur Utama

- **Login System**: Autentikasi pengguna dengan email dan password
- **Dashboard Karyawan**: 
  - Check-in dan check-out dengan validasi lokasi GPS
  - Riwayat absensi terbaru
  - Statistik kehadiran
- **Admin Panel**:
  - Manajemen karyawan (tambah, lihat)
  - Laporan absensi semua karyawan
  - Pengaturan lokasi kerja
- **Laporan**: Statistik dan riwayat absensi lengkap per karyawan

## Teknologi

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: Bootstrap 5.1.3, JavaScript
- **Geolocation**: HTML5 Geolocation API

## Instalasi

### 1. Database Setup

```sql
-- Import database.sql ke MySQL
mysql -u root -p < database.sql
```

### 2. Konfigurasi Database

Edit `config.php` sesuai dengan konfigurasi database Anda:

```php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'web_absen';
```

### 3. Struktur Database

Database akan membuat 2 tabel:
- `employees`: Data karyawan (id, name, nim, email, password, role, location_lat, location_lng)
- `attendance`: Data absensi (id, employee_id, check_in, check_out, date, location_lat, location_lng)

### 4. Default Login

**Admin:**
- Email: admin@company.com
- Password: password

**Karyawan:**
- Email: john@company.com
- Password: password

## Cara Penggunaan

### Untuk Karyawan:

1. Login dengan kredensial yang diberikan admin
2. Izinkan akses lokasi pada browser
3. Klik tombol "Check In" saat mulai bekerja
4. Klik tombol "Check Out" saat selesai bekerja
5. Lihat riwayat absensi di menu "Laporan"

### Untuk Admin:

1. Login dengan kredensial admin
2. Tambah karyawan baru melalui form di Admin Panel
3. Atur lokasi kerja (latitude & longitude) untuk setiap karyawan
4. Monitor absensi semua karyawan di tabel laporan

## Fitur Keamanan

- Password di-hash menggunakan `password_hash()` PHP
- Session management untuk autentikasi
- Validasi lokasi GPS dengan radius tertentu
- Prepared statements untuk mencegah SQL injection

## Catatan Penting

- Pastikan browser mendukung HTML5 Geolocation API
- Karyawan harus mengizinkan akses lokasi pada browser
- Lokasi kerja harus diatur dengan koordinat yang akurat
- Radius default validasi lokasi: 1 meter (dapat diubah di `config.php`)

## Struktur File

```
web_absen/
├── admin.php          # Admin panel
├── config.php         # Konfigurasi database & fungsi helper
├── dashboard.php      # Dashboard karyawan
├── database.sql       # Schema database
├── index.php          # Halaman login
├── logout.php         # Logout handler
├── reports.php        # Laporan absensi
├── script.js          # JavaScript untuk geolocation
├── style.css          # Styling
└── README.md          # Dokumentasi
```

## Troubleshooting

**Geolocation tidak bekerja:**
- Pastikan menggunakan HTTPS atau localhost
- Periksa permission browser untuk akses lokasi
- Cek console browser untuk error

**Tidak bisa login:**
- Periksa koneksi database
- Pastikan tabel employees sudah terisi
- Cek kredensial login

**Absen ditolak karena lokasi:**
- Pastikan koordinat lokasi kerja sudah diatur
- Periksa akurasi GPS device
- Sesuaikan radius validasi jika perlu

## Lisensi

MIT License - Bebas digunakan untuk keperluan komersial maupun non-komersial.
