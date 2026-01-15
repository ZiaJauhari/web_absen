# 🕐 Sistem Absen Karyawan

Sistem absensi karyawan berbasis web dengan fitur geolocation untuk memastikan karyawan melakukan absen dari lokasi yang ditentukan.

## ✨ Fitur Utama

### 👤 Untuk Karyawan
- ✅ Login dengan email dan password
- ✅ Check-in dan Check-out dengan validasi lokasi GPS
- ✅ Melihat status absen hari ini
- ✅ Melihat riwayat absen
- ✅ Laporan absen dengan filter bulan/tahun
- ✅ Statistik kehadiran personal
- ✅ Real-time clock display

### 👨‍💼 Untuk Admin
- ✅ Dashboard statistik (total karyawan, hadir hari ini, dll)
- ✅ Menambah karyawan baru
- ✅ Menghapus karyawan
- ✅ Melihat semua laporan absen karyawan
- ✅ Pagination untuk data besar
- ✅ Set lokasi kantor per karyawan

## 🎨 Tampilan Modern

- **Responsive Design**: Tampil sempurna di desktop, tablet, dan mobile
- **Modern UI**: Gradient backgrounds, smooth animations, dan icons
- **User Feedback**: Loading states, alerts, dan empty states
- **Dark Mode Ready**: Color scheme yang mudah disesuaikan
- **Print Friendly**: Laporan dapat dicetak dengan baik

## 🔒 Keamanan

- ✅ Password hashing dengan bcrypt
- ✅ Prepared statements untuk SQL queries
- ✅ Input sanitization dan validation
- ✅ XSS prevention
- ✅ Session management yang aman
- ✅ CSRF protection ready

## 📱 Teknologi

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Framework CSS**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0.0
- **Geolocation**: HTML5 Geolocation API

## 🚀 Instalasi

### 1. Requirements
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Browser dengan support Geolocation API

### 2. Setup Database
```sql
-- Import database.sql
mysql -u root -p < database.sql
```

### 3. Konfigurasi
Edit `config.php` untuk mengatur koneksi database:
```php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'web_absen';
```

### 4. Akses Aplikasi
Buka browser dan akses: `http://localhost/web_absen/`

## 👥 Demo Accounts

### Admin
- **Email**: admin@company.com
- **Password**: password

### Karyawan
- **Email**: john@company.com
- **Password**: password

## 📖 Cara Penggunaan

### Untuk Karyawan

1. **Login**
   - Masukkan email dan password
   - Klik tombol Login

2. **Check-in**
   - Pastikan GPS aktif
   - Pastikan berada di area kantor (radius 100 meter)
   - Klik tombol "Check In"
   - Izinkan akses lokasi di browser

3. **Check-out**
   - Klik tombol "Check Out" di akhir hari kerja
   - Sistem akan mencatat waktu check-out

4. **Melihat Laporan**
   - Klik menu "Laporan"
   - Pilih bulan dan tahun
   - Klik "Filter"
   - Lihat statistik dan riwayat absen

### Untuk Admin

1. **Tambah Karyawan**
   - Login sebagai admin
   - Isi form "Tambah Karyawan"
   - Set lokasi kantor (latitude & longitude) - opsional
   - Klik "Tambah Karyawan"

2. **Melihat Laporan**
   - Dashboard menampilkan statistik real-time
   - Scroll ke bawah untuk melihat semua absen karyawan
   - Gunakan pagination untuk navigasi data

3. **Hapus Karyawan**
   - Klik tombol delete (🗑️) di daftar karyawan
   - Konfirmasi penghapusan

## 🔧 Konfigurasi Lanjutan

### Mengubah Radius Lokasi
Edit `config.php`:
```php
function isLocationAllowed($lat, $lng, $allowedLat, $allowedLng, $radiusKm = 0.1) {
    // 0.1 km = 100 meter
    // Ubah sesuai kebutuhan
}
```

### Mengubah Timezone
Edit `config.php`:
```php
date_default_timezone_set('Asia/Jakarta');
// Ubah sesuai timezone Anda
```

## 📊 Database Schema

### Table: employees
- `id` - Primary key
- `name` - Nama karyawan
- `email` - Email (unique)
- `password` - Password (hashed)
- `role` - Role (employee/admin)
- `location_lat` - Latitude lokasi kantor
- `location_lng` - Longitude lokasi kantor
- `created_at` - Timestamp

### Table: attendance
- `id` - Primary key
- `employee_id` - Foreign key ke employees
- `check_in` - Waktu check in
- `check_out` - Waktu check out
- `date` - Tanggal absen
- `location_lat` - Latitude saat absen
- `location_lng` - Longitude saat absen

## 🐛 Troubleshooting

### GPS Tidak Berfungsi
1. Pastikan browser mendukung Geolocation API
2. Izinkan akses lokasi di browser
3. Pastikan GPS device aktif
4. Coba refresh halaman

### Tidak Bisa Check-in (Lokasi Terlalu Jauh)
1. Pastikan berada di area kantor
2. Tunggu GPS mendapat signal yang akurat
3. Hubungi admin untuk cek koordinat lokasi kantor

### Error Database Connection
1. Cek konfigurasi di `config.php`
2. Pastikan MySQL service berjalan
3. Pastikan database sudah di-import
4. Cek username dan password database

## 📝 Changelog

Lihat [CHANGELOG.md](CHANGELOG.md) untuk detail perubahan.

## 🎯 Roadmap

- [ ] Export laporan ke Excel/PDF
- [ ] Notifikasi email untuk lupa absen
- [ ] Dashboard analytics dengan charts
- [ ] Mobile app (PWA)
- [ ] Face recognition untuk absen
- [ ] Integration dengan payroll system
- [ ] Multi-language support
- [ ] Dark mode toggle

## 📄 License

MIT License - Bebas digunakan untuk keperluan komersial maupun personal.

## 👨‍💻 Developer

Dikembangkan dengan ❤️ untuk memudahkan manajemen absensi karyawan.

## 📞 Support

Jika ada pertanyaan atau masalah, silakan buat issue atau hubungi developer.

---

**Version**: 1.0  
**Last Updated**: 15 Januari 2026  
**Status**: ✅ Production Ready
