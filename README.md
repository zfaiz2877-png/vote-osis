# E-Voting OSIS OSKANER

Aplikasi PHP native dan MySQL untuk pemilihan Ketua OSIS SMKN 6 Jember. Antarmuka menggunakan CSS dan JavaScript lokal tanpa framework atau CDN.

## Menyiapkan database

1. Buat backup database `db_voting_osis` sebelum instalasi ulang.
2. Jalankan [`database.sql`](./database.sql) di MySQL. **Skrip ini menghapus tabel lama beserta semua data**, lalu membuat tabel baru dan akun admin awal.
3. Login melalui `admin.php` menggunakan `admin` / `admin123`. Ganti hash password awal sebelum aplikasi dibuka ke pengguna umum.

Skrip SQL menyiapkan database dan tabel; database `db_voting_osis` harus sudah dibuat. Sesuaikan nilai `DB_NAME`, `DB_USER`, dan `DB_PASSWORD` sebagai environment variable bila diperlukan. Tanpa environment variable, koneksi lokal menggunakan `db_voting_osis`, `root`, dan password kosong.
Untuk deployment publik, gunakan akun MySQL khusus dengan password kuat, ganti password admin awal, dan jangan gunakan kredensial root tanpa password.

## Menjalankan

- **XAMPP/localhost:** letakkan folder pada document root XAMPP, aktifkan Apache dan MySQL, lalu buka `http://localhost/vote-osis/`.
- **Docker:** jalankan `docker compose up --build`. Aplikasi tetap memakai MySQL pada host melalui `host.docker.internal`; pastikan MySQL host menerima koneksi dari Docker. Jika menggunakan Cloudflare Tunnel, isi `CLOUDFLARE_TOKEN` di environment Compose.
- **HTTPS di belakang proxy:** `config.php` mengenali `HTTP_X_FORWARDED_PROTO=https` untuk mengamankan cookie sesi.

## Alur aplikasi

Siswa memasukkan token enam karakter pada `index.php`, memilih kandidat pada halaman yang sama, lalu mengirim suara anonim. Dashboard `admin.php` mengelola kandidat, token, reset pemungutan suara, dan pencetakan token tersedia. `hasil.php` memuat pembaruan hasil melalui `api_hasil.php` setiap tiga detik.

Reset dari dashboard menghapus token dan suara, tetapi mempertahankan kandidat. Reset database menggunakan `database.sql` menghapus seluruh data tabel dan mengembalikan akun awal.
