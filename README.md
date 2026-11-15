# KasKita

**KasKita** adalah aplikasi web Sistem Informasi Kas Mahasiswa yang dibuat menggunakan PHP dan MySQL/MariaDB. Aplikasi ini menyediakan alur pengelolaan kas dari sisi bendahara serta akses informasi tagihan dan pembayaran dari sisi mahasiswa.

## Fitur aplikasi

Berdasarkan halaman dan kode yang tersedia di dalam project:

### Bendahara
- Login sebagai bendahara.
- Dashboard bendahara.
- Pengelolaan data mahasiswa.
- Penambahan data mahasiswa.
- Pengelolaan tagihan kas.
- Penambahan tagihan.
- Melihat detail tagihan.
- Melihat transaksi dan riwayat pembayaran.
- Memeriksa pembayaran mahasiswa.

### Mahasiswa
- Login sebagai mahasiswa.
- Membuat password saat akun mahasiswa belum memiliki password.
- Melihat dashboard mahasiswa.
- Melihat tagihan kas.
- Melihat dan melakukan proses pembayaran.
- Melihat riwayat pembayaran.

> Ketersediaan fitur dan alur akhirnya mengikuti implementasi kode serta struktur database yang digunakan.

## Teknologi

- **PHP** — sisi server.
- **MySQL/MariaDB** — penyimpanan data.
- **HTML dan CSS** — struktur dan tampilan antarmuka.
- **Laragon** — lingkungan server lokal yang dapat digunakan untuk menjalankan project.
- **phpMyAdmin** — pengelolaan database melalui browser.

## Struktur folder

```text
Kaskita/
├── assets/
│   └── css/
│       └── style.css
├── classes/
│   ├── mahasiswa.php
│   └── user.php
├── config/
│   └── database.php
├── pages/
│   ├── buat_password.php
│   ├── dashboard.php
│   ├── dashboard_mahasiswa.php
│   ├── detail_tagihan.php
│   ├── mahasiswa.php
│   ├── pembayaran.php
│   ├── pembayaran_mahasiswa.php
│   ├── periksa_pembayaran.php
│   ├── riwayat_pembayaran.php
│   ├── tagihan.php
│   ├── tagihan_mahasiswa.php
│   ├── tambah_mahasiswa.php
│   ├── tambah_tagihan.php
│   └── transaksi.php
└── index.php
```

## Persyaratan

Pastikan perangkat sudah memiliki:
- Laragon atau web server lokal sejenis.
- PHP yang kompatibel dengan kode project.
- MySQL atau MariaDB.
- Browser web.

## Cara menjalankan menggunakan Laragon

1. Ekstrak folder project.
2. Pindahkan folder `Kaskita` ke direktori `C:\laragon\www\`.
3. Jalankan Laragon, kemudian klik **Start All**.
4. Buka phpMyAdmin melalui menu Laragon atau alamat phpMyAdmin yang tersedia pada instalasi Laragon.
5. Buat database dengan nama:

   ```sql
   CREATE DATABASE db_kaskita;
   ```

6. Siapkan dan impor file SQL database ke `db_kaskita`.

   **Catatan:** Arsip project ini tidak menyertakan file ekspor database (`.sql`). Struktur tabel dan data awal perlu disediakan secara terpisah sebelum aplikasi dapat digunakan. Kode project merujuk antara lain pada tabel `users`, `mahasiswa`, `tagihan`, `tagihan_kas`, `tagihan_mahasiswa`, dan `pembayaran`.

7. Periksa konfigurasi koneksi pada `config/database.php`:

   ```php
   private $host = "localhost";
   private $username = "root";
   private $password = "";
   private $database = "db_kaskita";
   ```

   Sesuaikan host, username, password, dan nama database dengan konfigurasi MySQL/MariaDB lokal.

8. Pastikan pemanggilan file konfigurasi di `index.php` sesuai dengan nama file sebenarnya. Pada arsip ini, file yang tersedia bernama `config/database.php`, sedangkan `index.php` memanggil `config/Database.php`. Samakan penamaan/path tersebut agar file dapat ditemukan, terutama pada server yang membedakan huruf besar dan kecil.
9. Buka aplikasi melalui browser:

   ```text
   http://localhost/Kaskita/
   ```

## Konfigurasi penting

### Koneksi database

Konfigurasi database berada di:

```text
config/database.php
```

Nama database default yang digunakan oleh kode adalah `db_kaskita`.

### Lokasi CSS

File CSS utama berada di:

```text
assets/css/style.css
```

Beberapa halaman menggunakan path asset absolut `/kaskita/assets/css/style.css`. Jika nama folder atau alamat instalasi berbeda, sesuaikan path asset tersebut agar stylesheet dapat dimuat.

## Akun login

Akun pengguna dibaca dari tabel `users`. Project tidak menyertakan kredensial akun demo atau file SQL untuk membuat akun awal. Buat data akun sesuai struktur tabel dan aturan autentikasi yang diterapkan pada database project.

## Catatan pengembangan

- Pastikan database dan seluruh tabel yang dibutuhkan sudah tersedia sebelum membuka aplikasi.
- Periksa kembali konsistensi huruf besar/kecil pada nama file dan `require_once`.
- Untuk deployment, jangan gunakan konfigurasi database default tanpa menyesuaikan kredensial server.
- Sebelum digunakan pada lingkungan produksi, tinjau kembali keamanan autentikasi, pengelolaan session, validasi input, dan penyimpanan password.

## Lisensi

Belum ada informasi lisensi yang disertakan di dalam arsip project ini.
