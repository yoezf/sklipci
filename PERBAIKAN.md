# Ringkasan Perbaikan (Production Hardening)

Dokumen ini merangkum perbaikan yang diterapkan agar aplikasi siap produksi.

## Keamanan
1. **CSRF terpusat** — Semua request `POST` divalidasi token CSRF di router (`index.php`).
   Token disisipkan otomatis ke 27 form via `$this->csrfField()`.
2. **Aksi destruktif via GET → POST** — `dosenDelete`, `mahasiswaDelete`, `prodiDelete`,
   `jadwalSeminar/SidangDelete`, `logPklDelete` kini memakai form POST + CSRF.
3. **Open Redirect** — `header('Location: '.$_SERVER['HTTP_REFERER'])` diganti `safe_redirect()`
   yang hanya mengizinkan URL internal.
4. **Kredensial DB** — dipindah ke environment / `.env` (lihat `.env.example`).
   Tidak ada lagi kredensial root hardcoded di kode.
5. **Error handling produksi** — `display_errors` dimatikan saat `APP_ENV=production`;
   error dicatat ke log, pesan generik ke pengguna; koneksi DB gagal → HTTP 503 tanpa bocor detail.
6. **Session hardening** — cookie `HttpOnly`, `SameSite=Lax`, `Secure` saat HTTPS,
   `session.use_strict_mode`.
7. **Security headers** — `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, HSTS (HTTPS).
8. **Validasi input router** — nama controller/method dibatasi regex (cegah path traversal),
   method warisan/non-publik diblokir via Reflection (HTTP 403).
9. **Upload aman** — nama file diberi token acak (`random_bytes`) untuk mencegah IDOR via enumerasi URL;
   `public/uploads/.htaccess` mematikan eksekusi skrip.
10. **Brute-force login** — lockout 60 detik setelah 5 percobaan gagal.
11. **Kebocoran rahasia** — `hash.php` tidak lagi mencetak password default & hanya jalan via CLI.
    `.htaccess` root memblokir akses ke `app/`, `config/`, `vendor/`, dump `.sql`, `.env`.
12. **Data PII** — file PDF mahasiswa contoh dihapus dari repo; `.gitignore` mencegah commit upload.

## Bug Fungsional
- **PKL log edit/update/delete rusak** — memakai `$_SESSION['user_id']` yang tidak pernah di-set
  (selalu 0). Diperbaiki memakai `getMahasiswaAktif()` (mahasiswa.id) konsisten dengan method lain.

## Kebersihan Kode
- Hapus dead code `app/view/pdf/Untitled-1.php`.
- Tambah helper terpusat: `csrf_token/csrf_field/csrf_verify/e/safe_redirect` + method `Controller`.

## Catatan / Rekomendasi Lanjutan
- Untuk IDOR file sepenuhnya tertutup, sajikan unduhan via controller berotorisasi
  (cek kepemilikan/role) alih-alih link statis ke `public/uploads`.
- Tambah autoload PSR-4 & pisahkan lapisan Model/Repository dari controller.
- Tambah logging terstruktur, health check, dan strategi backup DB.
