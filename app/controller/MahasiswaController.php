<?php
class MahasiswaController extends Controller
{

    /* ======================================================
     *  GET DATA MAHASISWA AKTIF
     * ====================================================== */
    private function getMahasiswaAktif(): ?array
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }

        $userId = (int)$_SESSION['user']['id'];
        $conn   = db();

        $stmt = $conn->prepare("
            SELECT m.*
            FROM mahasiswa m
            WHERE m.user_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $mhs = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $mhs ?: null;
    }

    private function ensureMahasiswa(): void
    {
        $this->requireRole(['mahasiswa']);
    }

    /* ======================================================
     * FUNGSI BANTUAN (VALIDATOR URUTAN PENGAJUAN)
     * ====================================================== */
    private function cekSyaratUrutan(string $jenis_baru, int $id_mhs): bool|string
    {
        $conn = db();

        // 1. CEK PENGAJUAN PENDING (Sedang diajukan)
        // Mencegah mahasiswa mengajukan jika ada yang belum di-ACC/Revisi admin
        $stmt = $conn->prepare("SELECT jenis FROM pengajuan WHERE mahasiswa_id = ? AND status = 'diajukan' LIMIT 1");
        $stmt->bind_param('i', $id_mhs);
        $stmt->execute();
        $pending = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($pending) {
            return "Anda tidak bisa mengajukan. Masih ada pengajuan " . strtoupper(str_replace('_', ' ', $pending['jenis'])) . " yang sedang menunggu konfirmasi Admin.";
        }

        // 2. DEFINISI URUTAN ALUR
        $urutan = ['pkl', 'skripsi', 'seminar', 'sidang', 'laporan_akhir'];

        $idx_baru = array_search($jenis_baru, $urutan);

        // Jika bukan bagian dari urutan alur, izinkan lewat
        if ($idx_baru === false) {
            return true; 
        }

        // 3. CEK APAKAH TAHAP INI SUDAH PERNAH DI-ACC (Mencegah double pengajuan)
        if ($jenis_baru !== 'laporan_akhir') {
            $stmt = $conn->prepare("SELECT id FROM pengajuan WHERE mahasiswa_id = ? AND jenis = ? AND status = 'diterima' LIMIT 1");
            $stmt->bind_param('is', $id_mhs, $jenis_baru);
            $stmt->execute();
            $sudah_lulus = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($sudah_lulus) {
                return "Anda sudah menyelesaikan dan disetujui pada tahapan " . strtoupper(str_replace('_', ' ', $jenis_baru)) . ".";
            }
        }

        // 4. CEK TAHAP SEBELUMNYA (Harus sudah 'diterima' Admin)
        if ($idx_baru > 0) {
            $jenis_sebelumnya = $urutan[$idx_baru - 1];
            
            // Pengecualian logika Laporan Akhir (Cek kelulusan sidang skripsi)
            if ($jenis_baru === 'laporan_akhir') {
                 $stmt = $conn->prepare("SELECT id FROM pengajuan WHERE mahasiswa_id = ? AND jenis = 'sidang' AND status = 'diterima' LIMIT 1");
                 $stmt->bind_param('i', $id_mhs);
                 $stmt->execute();
                 $lulus_sebelumnya = $stmt->get_result()->fetch_assoc();
                 $stmt->close();
                 
                 if (!$lulus_sebelumnya) {
                     return "Akses ditolak. Anda harus lulus SIDANG SKRIPSI terlebih dahulu sebelum dapat mengupload Laporan Akhir.";
                 }
                 return true;
            }

            // Pengecekan umum tahap sebelumnya
            $stmt = $conn->prepare("SELECT id FROM pengajuan WHERE mahasiswa_id = ? AND jenis = ? AND status = 'diterima' LIMIT 1");
            $stmt->bind_param('is', $id_mhs, $jenis_sebelumnya);
            $stmt->execute();
            $lulus_sebelumnya = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$lulus_sebelumnya) {
                return "Akses ditolak. Anda harus menyelesaikan tahapan " . strtoupper(str_replace('_', ' ', $jenis_sebelumnya)) . " (dan dikonfirmasi Admin) sebelum dapat mengajukan " . strtoupper(str_replace('_', ' ', $jenis_baru)) . ".";
            }
        }

        return true;
    }

    public function pengajuanForm(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }
        
        $jenis = $_GET['jenis'] ?? 'pkl'; 
        if (!in_array($jenis, ['pkl', 'skripsi', 'seminar', 'sidang'], true)) {
            $jenis = 'pkl';
        }

        // ---> PANGGIL VALIDATOR DI SINI <---
        $cek = $this->cekSyaratUrutan($jenis, (int)$mhs['id']);
        if ($cek !== true) {
            // Jika tidak lolos validasi, kembalikan ke dashboard dengan pesan error
            $_SESSION['flash_error'] = $cek;
            header('Location: ' . BASE_URL . '/?r=mahasiswa/dashboard');
            exit;
        }

        $title = "Form Pengajuan " . strtoupper(str_replace('_', ' ', $jenis));
        $skripsiAktif = in_array($jenis, ['seminar', 'sidang'], true)
            ? $this->getSkripsiAktifForMahasiswa((int)$mhs['id'])
            : null;

        $this->view('mahasiswa/pengajuan/form', compact('title', 'mhs', 'jenis', 'skripsiAktif'));
    }

    // Terapkan juga di Laporan Akhir
    public function laporanAkhirCreateForm(): void
    {
        $this->ensureMahasiswa();
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        // ---> PANGGIL VALIDATOR DI SINI <---
        $cek = $this->cekSyaratUrutan('laporan_akhir', (int)$mhs['id']);
        if ($cek !== true) {
            $_SESSION['flash_error'] = $cek;
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirIndex');
            exit;
        }

        $skripsiAktif = $this->getSkripsiAktifForMahasiswa((int)$mhs['id']);
        if (!$skripsiAktif) {
            $_SESSION['flash_error'] = 'Data skripsi aktif tidak ditemukan.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirIndex');
            exit;
        }
        
        $title = "Upload Laporan Akhir";
        $this->view('mahasiswa/laporan/create', compact('title', 'mhs', 'skripsiAktif'));
    }

    // ... (Biarkan method getMahasiswaAktif, ensureMahasiswa, dan logBimbingan Anda yang lama tetap di sini) ...


    /* ======================================================
     *  DASHBOARD
     * ====================================================== */
    public function dashboard(): void
    {
        $this->requireRole(['mahasiswa']);

        if (!isset($_SESSION['user']['id'])) {
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        $conn   = db();
        $userId = (int)$_SESSION['user']['id'];

        /* ============================
     * 1. Data Mahasiswa Aktif
     * ============================ */
        $stmt = $conn->prepare("
        SELECT m.*, p.nama_prodi
        FROM mahasiswa m
        JOIN prodi p ON m.prodi_id = p.id
        WHERE m.user_id = ?
        LIMIT 1
    ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $resMhs    = $stmt->get_result();
        $mahasiswa = $resMhs->fetch_assoc();
        $stmt->close();

        if (!$mahasiswa) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin untuk melengkapi data.";
            exit;
        }

        $mahasiswaId = (int)$mahasiswa['id'];

        /* ============================
     * 1b. Dosen Pembimbing (terakhir yg diterima)
     * ============================ */
        $dosenPembimbing = null;

        $stmt = $conn->prepare("
        SELECT d.nama AS nama_pembimbing
        FROM pengajuan p
        JOIN dosen d ON p.pembimbing_id = d.id
        WHERE p.mahasiswa_id = ?
          AND p.status = 'diterima'
          AND p.pembimbing_id IS NOT NULL
          AND p.jenis IN ('pkl','skripsi')
        ORDER BY p.updated_at DESC, p.created_at DESC
        LIMIT 1
    ");
        $stmt->bind_param('i', $mahasiswaId);
        $stmt->execute();
        $rowPemb = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($rowPemb) {
            $dosenPembimbing = $rowPemb['nama_pembimbing'];
        }

        /* ============================
     * 2. Ringkasan Pengajuan
     * ============================ */
        $pengajuanRingkas = [];
        $stmt = $conn->prepare("
        SELECT jenis, judul, status, updated_at
        FROM pengajuan
        WHERE mahasiswa_id = ?
        ORDER BY updated_at DESC, created_at DESC
        LIMIT 5
    ");
        $stmt->bind_param('i', $mahasiswaId);
        $stmt->execute();
        $resPengajuan     = $stmt->get_result();
        $pengajuanRingkas = $resPengajuan->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        /* ============================
     * 3. Jadwal Terdekat Mahasiswa
     * ============================ */
        $jadwalTerdekat = [];
        $stmt = $conn->prepare("
        SELECT 
            j.*,
            p.judul,
            j.jenis
        FROM jadwal j
        JOIN pengajuan p ON j.pengajuan_id = p.id
        WHERE j.mahasiswa_id = ?
          AND j.tanggal >= CURDATE()
        ORDER BY j.tanggal ASC, j.jam_mulai ASC
        LIMIT 5
    ");
        $stmt->bind_param('i', $mahasiswaId);
        $stmt->execute();
        $resJadwal      = $stmt->get_result();
        $jadwalTerdekat = $resJadwal->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        /* ============================
     * 4. Log Terbaru (PKL + Bimbingan)
     * ============================ */
        $logPkl = [];
        $stmt = $conn->prepare("
        SELECT 
            'PKL' AS tipe,
            tanggal,
            kegiatan,
            created_at
        FROM log_pkl
        WHERE mahasiswa_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
        $stmt->bind_param('i', $mahasiswaId);
        $stmt->execute();
        $resLogPkl = $stmt->get_result();
        $logPkl    = $resLogPkl->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $logBimbingan = [];
        $stmt = $conn->prepare("
        SELECT 
            'Bimbingan' AS tipe,
            tanggal,
            topik AS kegiatan,
            created_at
        FROM log_bimbingan
        WHERE mahasiswa_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
        $stmt->bind_param('i', $mahasiswaId);
        $stmt->execute();
        $resLogBim   = $stmt->get_result();
        $logBimbingan = $resLogBim->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $logTerbaru = array_merge($logPkl, $logBimbingan);
        // sort gabungan log by created_at desc
        usort($logTerbaru, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        $logTerbaru = array_slice($logTerbaru, 0, 5);

        /* ============================
     * 5. Kirim ke View
     * ============================ */
        $title = 'Dashboard Mahasiswa';

        $this->view('mahasiswa/dashboard', compact(
            'title',
            'mahasiswa',
            'dosenPembimbing',
            'pengajuanRingkas',
            'jadwalTerdekat',
            'logTerbaru'
        ));
    }



    /* ======================================================
     *  INTERNAL FORM & STORE PENGAJUAN
     * ====================================================== */

    private function pengajuanStoreInternal(string $jenis, string $redirectTitle): void
    {
        $this->requireRole(['mahasiswa']);

        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa akun ini belum tersedia. Silakan minta admin melengkapi data.";
            exit;
        }

        $cek = $this->cekSyaratUrutan($jenis, (int)$mhs['id']);
        if ($cek !== true) {
            $_SESSION['flash_error'] = $cek;
            header('Location: ' . BASE_URL . '/?r=mahasiswa/dashboard');
            exit;
        }

        $conn = db();

        // =========================================================
        // 1) HANDLE LAMPIRAN (PDF SYARAT) – OPSIONAL
        // =========================================================
        $lampiranPathDb = null;

        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['lampiran'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_error'] = 'Gagal mengunggah file syarat.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/' . $redirectTitle);
                exit;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                $_SESSION['flash_error'] = 'Ukuran file maksimal 5 MB.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/' . $redirectTitle);
                exit;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
                $_SESSION['flash_error'] = 'File syarat wajib berformat PDF.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/' . $redirectTitle);
                exit;
            }

            $uploadDir = __DIR__ . '/../../public/uploads/pengajuan/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $safeJenis = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($jenis));
            $safeNim   = preg_replace('/[^a-zA-Z0-9]+/', '_', $mhs['nim']);
            // Tambahkan token acak agar nama file tidak bisa ditebak (cegah IDOR via enumerasi URL).
            $rand      = bin2hex(random_bytes(8));
            $fileName  = 'pengajuan_' . $safeJenis . '_' . $safeNim . '_' . time() . '_' . $rand . '.pdf';

            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $_SESSION['flash_error'] = 'Gagal menyimpan file syarat.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/' . $redirectTitle);
                exit;
            }

            $lampiranPathDb = 'public/uploads/pengajuan/' . $fileName;
        }

        // =========================================================
        // 2) HANDLE PER JENIS PENGAJUAN
        // =========================================================

        // ---------- PENGAJUAN PKL ----------
        if ($jenis === 'pkl') {

            $tempat         = trim($_POST['tempat_pkl'] ?? ($_POST['instansi_nama'] ?? ''));
            $alamat         = trim($_POST['instansi_alamat'] ?? '');
            $tanggalMulai   = trim($_POST['tanggal_mulai'] ?? '');
            $tanggalSelesai = trim($_POST['tanggal_selesai'] ?? '');
            $keterangan     = trim($_POST['keterangan'] ?? '');

            if ($tempat === '' || $tanggalMulai === '' || $tanggalSelesai === '') {
                $_SESSION['flash_error'] = 'Tempat PKL dan rentang tanggal wajib diisi.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/' . $redirectTitle);
                exit;
            }

            $judul = 'PKL di ' . $tempat;

            $deskripsi = "Tempat PKL : {$tempat}\n";
            if ($alamat !== '') {
                $deskripsi .= "Alamat Instansi : {$alamat}\n";
            }
            $deskripsi .= "Tanggal Mulai : {$tanggalMulai}\n";
            $deskripsi .= "Tanggal Selesai : {$tanggalSelesai}\n";
            if ($keterangan !== '') {
                $deskripsi .= "\nKeterangan : {$keterangan}";
            }
            if ($lampiranPathDb) {
                $deskripsi .= "\n\nLampiran syarat : {$lampiranPathDb}";
            }

            if ($lampiranPathDb) {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, lampiran, status)
                VALUES (?, 'pkl', ?, ?, ?, 'diajukan')
            ");
                $stmt->bind_param('isss', $mhs['id'], $judul, $deskripsi, $lampiranPathDb);
            } else {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, status)
                VALUES (?, 'pkl', ?, ?, 'diajukan')
            ");
                $stmt->bind_param('iss', $mhs['id'], $judul, $deskripsi);
            }
            $stmt->execute();
            $stmt->close();
        }

        // ---------- PENGAJUAN SKRIPSI ----------
        elseif ($jenis === 'skripsi') {

            $judulArr = $_POST['judul_skripsi'] ?? ($_POST['judul'] ?? []);
            if (!is_array($judulArr)) {
                $judulArr = [$judulArr];
            }

            $judulArr = array_map('trim', $judulArr);
            $judulArr = array_filter($judulArr, fn($v) => $v !== '');

            if (empty($judulArr)) {
                $_SESSION['flash_error'] = 'Minimal satu judul skripsi wajib diisi.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/' . $redirectTitle);
                exit;
            }

            $keterangan = trim($_POST['keterangan'] ?? '');

            $deskripsiBase = '';
            if ($keterangan !== '') {
                $deskripsiBase .= "Keterangan : {$keterangan}\n";
            }
            if ($lampiranPathDb) {
                $deskripsiBase .= "\nLampiran syarat : {$lampiranPathDb}";
            }

            if ($lampiranPathDb) {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, lampiran, status)
                VALUES (?, 'skripsi', ?, ?, ?, 'diajukan')
            ");
                foreach ($judulArr as $judul) {
                    $judul     = substr($judul, 0, 255);
                    $deskripsi = "Pengajuan judul skripsi.\n" . $deskripsiBase;
                    $stmt->bind_param('isss', $mhs['id'], $judul, $deskripsi, $lampiranPathDb);
                    $stmt->execute();
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, status)
                VALUES (?, 'skripsi', ?, ?, 'diajukan')
            ");
                foreach ($judulArr as $judul) {
                    $judul     = substr($judul, 0, 255);
                    $deskripsi = "Pengajuan judul skripsi.\n" . $deskripsiBase;
                    $stmt->bind_param('iss', $mhs['id'], $judul, $deskripsi);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }

        // ---------- PENGAJUAN SEMINAR ----------
        elseif ($jenis === 'seminar') {

            // 1) harus punya skripsi DITERIMA
            $skripsiAktif = $this->getSkripsiAktifForMahasiswa((int)$mhs['id']);
            if (!$skripsiAktif) {
                $_SESSION['flash_error'] = 'Seminar hanya bisa diajukan jika skripsi sudah DITERIMA oleh admin.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/pengajuanRiwayat');
                exit;
            }

            // 2) (opsional) syarat log bimbingan minimal
            // Bisa ditambah misal cek:
            //   SELECT COUNT(*) FROM log_bimbingan WHERE mahasiswa_id = ? AND status = 'disetujui'
            // Untuk sekarang kita lewati supaya tidak mengganggu alur.

            $catatan = trim($_POST['keterangan'] ?? '');

            $judul = $skripsiAktif['judul']; // sinkron dengan skripsi
            $deskripsi = "Pengajuan seminar untuk skripsi: {$judul}\n";
            $deskripsi .= "Status skripsi: DITERIMA\n";
            if ($catatan !== '') {
                $deskripsi .= "\nCatatan mahasiswa: {$catatan}";
            }
            if ($lampiranPathDb) {
                $deskripsi .= "\n\nLampiran syarat: {$lampiranPathDb}";
            }

            if ($lampiranPathDb) {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, lampiran, status)
                VALUES (?, 'seminar', ?, ?, ?, 'diajukan')
            ");
                $stmt->bind_param('isss', $mhs['id'], $judul, $deskripsi, $lampiranPathDb);
            } else {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, status)
                VALUES (?, 'seminar', ?, ?, 'diajukan')
            ");
                $stmt->bind_param('iss', $mhs['id'], $judul, $deskripsi);
            }
            $stmt->execute();
            $stmt->close();
        }

        // ---------- PENGAJUAN SIDANG ----------
        elseif ($jenis === 'sidang') {

            // 1) seminar harus sudah DITERIMA
            $seminarAktif = $this->getSeminarAktifForMahasiswa((int)$mhs['id']);
            if (!$seminarAktif) {
                $_SESSION['flash_error'] = 'Sidang hanya bisa diajukan jika seminar sudah DITERIMA dan revisi seminar dinyatakan selesai.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/pengajuanRiwayat');
                exit;
            }

            // 2) (opsional) cek revisi seminar melalui log / flag lain di DB kalau nanti kamu tambahkan.

            $catatan = trim($_POST['keterangan'] ?? '');

            $judul = $seminarAktif['judul'];
            $deskripsi = "Pengajuan sidang akhir untuk skripsi: {$judul}\n";
            $deskripsi .= "Status seminar: DITERIMA\n";
            if ($catatan !== '') {
                $deskripsi .= "\nCatatan mahasiswa: {$catatan}";
            }
            if ($lampiranPathDb) {
                $deskripsi .= "\n\nLampiran syarat: {$lampiranPathDb}";
            }

            if ($lampiranPathDb) {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, lampiran, status)
                VALUES (?, 'sidang', ?, ?, ?, 'diajukan')
            ");
                $stmt->bind_param('isss', $mhs['id'], $judul, $deskripsi, $lampiranPathDb);
            } else {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, status)
                VALUES (?, 'sidang', ?, ?, 'diajukan')
            ");
                $stmt->bind_param('iss', $mhs['id'], $judul, $deskripsi);
            }
            $stmt->execute();
            $stmt->close();
        }

        // ---------- FALLBACK GENERIC (kalau ada jenis lain) ----------
        else {
            $judul     = trim($_POST['judul'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if ($judul === '') {
                $_SESSION['flash_error'] = 'Judul wajib diisi.';
                header('Location: ' . BASE_URL . '/?r=mahasiswa/' . $redirectTitle);
                exit;
            }

            if ($lampiranPathDb) {
                $deskripsi .= ($deskripsi !== '' ? "\n\n" : '') . "Lampiran syarat: {$lampiranPathDb}";
            }

            if ($lampiranPathDb) {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, lampiran, status)
                VALUES (?, ?, ?, ?, ?, 'diajukan')
            ");
                $stmt->bind_param('issss', $mhs['id'], $jenis, $judul, $deskripsi, $lampiranPathDb);
            } else {
                $stmt = $conn->prepare("
                INSERT INTO pengajuan (mahasiswa_id, jenis, judul, deskripsi, status)
                VALUES (?, ?, ?, ?, 'diajukan')
            ");
                $stmt->bind_param('isss', $mhs['id'], $jenis, $judul, $deskripsi);
            }
            $stmt->execute();
            $stmt->close();
        }

        // =========================================================
        // 3) SELESAI → KEMBALI KE RIWAYAT
        // =========================================================
        $_SESSION['flash_success'] = 'Pengajuan berhasil dikirim. Menunggu verifikasi admin.';
        header('Location: ' . BASE_URL . '/?r=mahasiswa/pengajuanRiwayat');
        exit;
    }



    /* ======================================================
     *  PUBLIC FORM PENGAJUAN
     * ====================================================== */

    public function pengajuanPklForm(): void
    {
        $this->pengajuanFormInternal('pkl', 'Pengajuan PKL');
    }

    public function pengajuanSkripsiForm(): void
    {
        $this->pengajuanFormInternal('skripsi', 'Pengajuan Skripsi');
    }

    public function pengajuanSeminarForm(): void
    {
        $this->pengajuanFormInternal('seminar', 'Pengajuan Seminar');
    }

    public function pengajuanSidangForm(): void
    {
        $this->pengajuanFormInternal('sidang', 'Pengajuan Sidang');
    }


    /* ======================================================
     *  INTERNAL: FORM PENGAJUAN
     * ====================================================== */
    private function pengajuanFormInternal(string $jenis, string $pageTitle): void
    {
        $this->requireRole(['mahasiswa']);

        // data mahasiswa aktif
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $cek = $this->cekSyaratUrutan($jenis, (int)$mhs['id']);
        if ($cek !== true) {
            $_SESSION['flash_error'] = $cek;
            header('Location: ' . BASE_URL . '/?r=mahasiswa/dashboard');
            exit;
        }

        // Untuk seminar & sidang, coba ambil skripsi aktif (sudah diterima + ada pembimbing)
        $skripsiAktif = null;
        if (in_array($jenis, ['seminar', 'sidang'], true)) {
            // method ini sudah ada di controller-mu:
            // private function getSkripsiAktifForMahasiswa(int $mahasiswaId): ?array
            $skripsiAktif = $this->getSkripsiAktifForMahasiswa((int)$mhs['id']);
        }

        $title = $pageTitle;

        // View form pengajuan yang tadi sudah kita buat
        $this->view('mahasiswa/pengajuan/form', compact(
            'title',
            'jenis',
            'mhs',
            'skripsiAktif'
        ));
    }

    /* ======================================================
     *  PUBLIC STORE PENGAJUAN
     * ====================================================== */

    public function pengajuanPklStore(): void
    {
        $this->pengajuanStoreInternal('pkl', 'pengajuanPklForm');
    }

    public function pengajuanSkripsiStore(): void
    {
        $this->pengajuanStoreInternal('skripsi', 'pengajuanSkripsiForm');
    }

    public function pengajuanSeminarStore(): void
    {
        $this->pengajuanStoreInternal('seminar', 'pengajuanSeminarForm');
    }

    public function pengajuanSidangStore(): void
    {
        $this->pengajuanStoreInternal('sidang', 'pengajuanSidangForm');
    }

    /* ======================================================
     *  RIWAYAT PENGAJUAN
     * ====================================================== */

    public function pengajuanRiwayat(): void
    {
        $this->requireRole(['mahasiswa']);

        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa akun ini belum tersedia. Silakan minta admin melengkapi data mahasiswa.";
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
    SELECT p.*, d.nama AS nama_pembimbing
    FROM pengajuan p
    LEFT JOIN dosen d ON p.pembimbing_id = d.id
    WHERE p.mahasiswa_id = ?
    ORDER BY p.created_at DESC
");

        $stmt->bind_param('i', $mhs['id']);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $title = 'Riwayat Pengajuan';
        $this->view('mahasiswa/pengajuan/riwayat', compact('title', 'items', 'mhs'));
    }

    /* ===========================
     *  LOG PKL - MAHASISWA
     * =========================== */

    public function logPklIndex(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            SELECT *
            FROM log_pkl
            WHERE mahasiswa_id = ?
            ORDER BY tanggal DESC, created_at DESC
        ");
        $stmt->bind_param('i', $mhs['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $title = 'Log Harian PKL';
        $this->view('mahasiswa/log/pkl_index', compact('title', 'items', 'mhs'));
    }

    public function logPklCreateForm(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $title = 'Tambah Log PKL';
        $this->view('mahasiswa/log/pkl_create', compact('title', 'mhs'));
    }

    public function logPklStore(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $tanggal  = $_POST['tanggal'] ?? '';
        $kegiatan = trim($_POST['kegiatan'] ?? '');
        $lokasi   = trim($_POST['lokasi'] ?? '');
        $jam_mulai    = $_POST['jam_mulai'] ?? null;
        $jam_selesai  = $_POST['jam_selesai'] ?? null;
        $output   = trim($_POST['output'] ?? '');

        if ($tanggal === '' || $kegiatan === '') {
            $_SESSION['flash_error'] = 'Tanggal dan kegiatan wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklCreateForm');
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            INSERT INTO log_pkl (mahasiswa_id, tanggal, kegiatan, lokasi, jam_mulai, jam_selesai, output)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'issssss',
            $mhs['id'],
            $tanggal,
            $kegiatan,
            $lokasi,
            $jam_mulai,
            $jam_selesai,
            $output
        );
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Log PKL berhasil disimpan.';
        header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
        exit;
    }

    /* Cari pengajuan skripsi aktif (diterima & ada pembimbing) */
    private function getSkripsiAktifForMahasiswa(int $mahasiswaId): ?array
    {
        $conn = db();
        $stmt = $conn->prepare("
            SELECT p.*, d.nama AS nama_pembimbing
            FROM pengajuan p
            JOIN dosen d ON p.pembimbing_id = d.id
            WHERE p.mahasiswa_id = ?
              AND p.jenis = 'skripsi'
              AND p.status = 'diterima'
              AND p.pembimbing_id IS NOT NULL
            ORDER BY p.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param('i', $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $skripsi = $result->fetch_assoc();
        $stmt->close();

        return $skripsi ?: null;
    }

    private function getSeminarAktifForMahasiswa(int $mahasiswaId): ?array
    {
        $conn = db();
        $stmt = $conn->prepare("
            SELECT *
            FROM pengajuan
            WHERE mahasiswa_id = ?
              AND jenis = 'seminar'
              AND status = 'diterima'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param('i', $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $seminar = $result->fetch_assoc();
        $stmt->close();

        return $seminar ?: null;
    }

    /* ===========================
     *  LOG BIMBINGAN SKRIPSI - MAHASISWA
     * =========================== */

    public function logBimbinganIndex(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $skripsiAktif = $this->getSkripsiAktifForMahasiswa($mhs['id']);

        $conn = db();
        $stmt = $conn->prepare("
            SELECT lb.*, d.nama AS nama_dosen
            FROM log_bimbingan lb
            JOIN dosen d ON lb.dosen_id = d.id
            WHERE lb.mahasiswa_id = ?
            ORDER BY lb.tanggal DESC, lb.created_at DESC
        ");
        $stmt->bind_param('i', $mhs['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $title = 'Log Bimbingan Skripsi';
        $this->view('mahasiswa/log/bimbingan_index', compact('title', 'items', 'mhs', 'skripsiAktif'));
    }

    public function logBimbinganCreateForm(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $skripsiAktif = $this->getSkripsiAktifForMahasiswa($mhs['id']);
        if (!$skripsiAktif) {
            echo "Belum ada pengajuan skripsi yang diterima atau pembimbing belum ditetapkan. Hubungi admin/pembimbing.";
            exit;
        }

        $title = 'Tambah Log Bimbingan';
        $this->view('mahasiswa/log/bimbingan_create', compact('title', 'mhs', 'skripsiAktif'));
    }

    public function logBimbinganStore(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $skripsiAktif = $this->getSkripsiAktifForMahasiswa($mhs['id']);
        if (!$skripsiAktif) {
            echo "Belum ada pengajuan skripsi yang diterima atau pembimbing belum ditetapkan.";
            exit;
        }

        $tanggal  = $_POST['tanggal'] ?? '';
        $topik    = trim($_POST['topik'] ?? '');
        $catatan_mahasiswa = trim($_POST['catatan_mahasiswa'] ?? '');

        if ($tanggal === '' || $topik === '') {
            $_SESSION['flash_error'] = 'Tanggal dan topik bimbingan wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logBimbinganCreateForm');
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            INSERT INTO log_bimbingan (mahasiswa_id, dosen_id, tanggal, topik, catatan_mahasiswa, status)
            VALUES (?, ?, ?, ?, ?, 'diajukan')
        ");
        $stmt->bind_param(
            'iisss',
            $mhs['id'],
            $skripsiAktif['pembimbing_id'],
            $tanggal,
            $topik,
            $catatan_mahasiswa
        );
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Log bimbingan berhasil dikirim ke pembimbing.';
        header('Location: ' . BASE_URL . '/?r=mahasiswa/logBimbinganIndex');
        exit;
    }

    /* ===========================
     *  JADWAL SEMINAR & SIDANG - MAHASISWA
     * =========================== */

    public function jadwalIndex(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia.";
            exit;
        }

        $cek = $this->cekSyaratUrutan('laporan_akhir', (int)$mhs['id']);
        if ($cek !== true) {
            $_SESSION['flash_error'] = $cek;
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirIndex');
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            SELECT j.*
            FROM jadwal j
            WHERE j.mahasiswa_id = ?
            ORDER BY j.tanggal DESC, j.jam_mulai DESC
        ");
        $stmt->bind_param('i', $mhs['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $title = 'Jadwal Seminar & Sidang';
        $this->view('mahasiswa/jadwal/index', compact('title', 'items', 'mhs'));
    }

    /* ===========================
     *  LAPORAN AKHIR - MAHASISWA
     * =========================== */

    public function laporanAkhirIndex(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia. Minta admin mengisi data mahasiswa.";
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            SELECT la.*, p.judul AS judul_skripsi
            FROM laporan_akhir la
            JOIN pengajuan p ON la.pengajuan_id = p.id
            WHERE la.mahasiswa_id = ?
            ORDER BY la.created_at DESC
        ");
        $stmt->bind_param('i', $mhs['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $title = 'Laporan Akhir';
        $this->view('mahasiswa/laporan/index', compact('title', 'items', 'mhs'));
    }

    

    public function laporanAkhirStore(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa untuk akun ini belum tersedia.";
            exit;
        }

        $skripsiAktif = $this->getSkripsiAktifForMahasiswa($mhs['id']);
        if (!$skripsiAktif) {
            echo "Belum ada pengajuan skripsi yang diterima atau pembimbing belum ditetapkan.";
            exit;
        }

        $judul = trim($_POST['judul'] ?? '');

        // Validasi file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'File laporan wajib diunggah.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirCreateForm');
            exit;
        }

        if ($judul === '') {
            $_SESSION['flash_error'] = 'Judul laporan wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirCreateForm');
            exit;
        }

        $file = $_FILES['file'];

        // Batasan file
        $maxSize = 10 * 1024 * 1024; // 10 MB
        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = 'Ukuran file maksimal 10MB.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirCreateForm');
            exit;
        }

        // Hanya izinkan PDF
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            $_SESSION['flash_error'] = 'File laporan harus berformat PDF.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirCreateForm');
            exit;
        }

        // buat nama file aman: NIM_Tahun_laporan_akhir.pdf
        $tahun = date('Y');
        $safeJudul = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($judul));
        // Token acak agar nama file tidak bisa ditebak (cegah IDOR via enumerasi URL).
        $rand      = bin2hex(random_bytes(8));
        $fileName  = $mhs['nim'] . '_' . $tahun . '_LAPORAN_' . $safeJudul . '_' . $rand . '.pdf';

        $uploadDir = __DIR__ . '/../../public/uploads/laporan_akhir/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['flash_error'] = 'Gagal menyimpan file di server.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirCreateForm');
            exit;
        }

        // Path untuk disimpan di DB (relatif terhadap BASE_URL)
        $filePathDb = 'public/uploads/laporan_akhir/' . $fileName;

        $conn = db();

        // Opsional: jika kamu mau hanya 1 laporan per skripsi → hapus yang lama
        $stmtDel = $conn->prepare("
            DELETE FROM laporan_akhir
            WHERE pengajuan_id = ? AND mahasiswa_id = ?
        ");
        $stmtDel->bind_param('ii', $skripsiAktif['id'], $mhs['id']);
        $stmtDel->execute();
        $stmtDel->close();

        $stmt = $conn->prepare("
            INSERT INTO laporan_akhir (pengajuan_id, mahasiswa_id, judul, file_path, status)
            VALUES (?, ?, ?, ?, 'diajukan')
        ");
        $stmt->bind_param(
            'iiss',
            $skripsiAktif['id'],
            $mhs['id'],
            $judul,
            $filePathDb
        );
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Laporan akhir berhasil diunggah dan dikirim ke admin untuk verifikasi.';
        header('Location: ' . BASE_URL . '/?r=mahasiswa/laporanAkhirIndex');
        exit;
    }

    public function logPklPdf(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa tidak ditemukan.";
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            SELECT *
            FROM log_pkl
            WHERE mahasiswa_id = ?
            ORDER BY tanggal ASC, created_at ASC
        ");
        $stmt->bind_param('i', $mhs['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // load helper
        require_once __DIR__ . '/../../config/pdf.php';

        // load view pdf via output buffering
        $title = 'Log Harian PKL - ' . ($mhs['nim'] ?? '');
        ob_start();
        include __DIR__ . '/../view/pdf/log_pkl.php';
        $html = ob_get_clean();

        render_pdf($html, 'log_pkl_' . $mhs['nim'] . '.pdf', 'portrait', 'A4');
    }

    public function logBimbinganPdf(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            echo "Data mahasiswa tidak ditemukan.";
            exit;
        }

        $skripsiAktif = $this->getSkripsiAktifForMahasiswa($mhs['id']);
        if (!$skripsiAktif) {
            echo "Belum ada skripsi yang diterima atau pembimbing belum ditetapkan.";
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            SELECT lb.*, d.nama AS nama_dosen
            FROM log_bimbingan lb
            JOIN dosen d ON lb.dosen_id = d.id
            WHERE lb.mahasiswa_id = ?
            ORDER BY lb.tanggal ASC, lb.created_at ASC
        ");
        $stmt->bind_param('i', $mhs['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        require_once __DIR__ . '/../../config/pdf.php';

        $title = 'Log Bimbingan Skripsi - ' . ($mhs['nim'] ?? '');
        ob_start();
        include __DIR__ . '/../view/pdf/log_bimbingan.php';
        $html = ob_get_clean();

        render_pdf($html, 'log_bimbingan_' . $mhs['nim'] . '.pdf', 'portrait', 'A4');
    }



    public function logPklEdit(): void
    {
        $this->ensureMahasiswa();
        // FIX: $_SESSION['user_id'] tidak pernah di-set saat login.
        // Gunakan data mahasiswa aktif (mahasiswa.id) seperti method lainnya.
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            $_SESSION['flash_error'] = 'Data mahasiswa untuk akun ini belum tersedia.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
            exit;
        }
        $idMahasiswa = (int)$mhs['id'];
        $id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
        SELECT *
        FROM log_pkl
        WHERE id = ? AND mahasiswa_id = ?
        LIMIT 1
    ");
        $stmt->bind_param('ii', $id, $idMahasiswa);
        $stmt->execute();
        $result = $stmt->get_result();
        $log    = $result->fetch_assoc();
        $stmt->close();

        if (!$log) {
            $_SESSION['flash_error'] = 'Data log tidak ditemukan.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
            exit;
        }

        $title = 'Edit Log PKL';

        // gunakan form yang sama dengan tambah (pkl_create)
        $this->view('mahasiswa/log/pkl_create', compact('title', 'log'));
    }




    public function logPklUpdate(): void
    {
        $this->ensureMahasiswa();
        // FIX: $_SESSION['user_id'] tidak pernah di-set saat login.
        // Gunakan data mahasiswa aktif (mahasiswa.id) seperti method lainnya.
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            $_SESSION['flash_error'] = 'Data mahasiswa untuk akun ini belum tersedia.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
            exit;
        }
        $idMahasiswa = (int)$mhs['id'];

        if (empty($_POST['id']) || empty($_POST['tanggal']) || empty($_POST['kegiatan'])) {
            $_SESSION['flash_error'] = 'Data tidak lengkap.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
            exit;
        }

        $id       = (int)$_POST['id'];
        $tanggal  = $_POST['tanggal'];
        $kegiatan = trim($_POST['kegiatan'] ?? '');
        $lokasi   = trim($_POST['lokasi']   ?? '');
        $jamMulai = $_POST['jam_mulai']     ?? null;
        $jamSelesai = $_POST['jam_selesai'] ?? null;
        $output   = trim($_POST['output']   ?? '');

        $conn = db();
        $stmt = $conn->prepare("
        UPDATE log_pkl
        SET tanggal = ?, kegiatan = ?, lokasi = ?, jam_mulai = ?, jam_selesai = ?, output = ?
        WHERE id = ? AND mahasiswa_id = ?
    ");
        $stmt->bind_param(
            'ssssssii',
            $tanggal,
            $kegiatan,
            $lokasi !== '' ? $lokasi : null,
            $jamMulai !== '' ? $jamMulai : null,
            $jamSelesai !== '' ? $jamSelesai : null,
            $output !== '' ? $output : null,
            $id,
            $idMahasiswa
        );
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Log PKL berhasil diupdate.';
        header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
        exit;
    }



    public function logPklDelete(): void
    {
        $this->ensureMahasiswa();
        // FIX: $_SESSION['user_id'] tidak pernah di-set saat login.
        // Gunakan data mahasiswa aktif (mahasiswa.id) seperti method lainnya.
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            $_SESSION['flash_error'] = 'Data mahasiswa untuk akun ini belum tersedia.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
            exit;
        }
        $idMahasiswa = (int)$mhs['id'];
        $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id > 0) {
            $conn = db();
            $stmt = $conn->prepare("
            DELETE FROM log_pkl
            WHERE id = ? AND mahasiswa_id = ?
        ");
            $stmt->bind_param('ii', $id, $idMahasiswa);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash_success'] = 'Log PKL berhasil dihapus.';
        }

        header('Location: ' . BASE_URL . '/?r=mahasiswa/logPklIndex');
        exit;
    }


    public function bimbinganPklIndex(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            $_SESSION['flash_error'] = 'Data mahasiswa tidak ditemukan.';
            header('Location: ' . BASE_URL . '/?r=auth/login');
            exit;
        }

        $idMahasiswa = (int)$mhs['id'];

        $conn = db();
        $stmt = $conn->prepare("
        SELECT lb.*
        FROM log_bimbingan_pkl lb
        WHERE lb.id_mahasiswa = ?
        ORDER BY lb.tanggal DESC, lb.id DESC
    ");
        $stmt->bind_param('i', $idMahasiswa);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $title = 'Log Bimbingan PKL';
        $this->view('mahasiswa/bimbingan_pkl/index', compact('title', 'mhs', 'items'));
    }

    public function bimbinganPklCreateForm(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();

        $title = 'Tambah Log Bimbingan PKL';
        $this->view('mahasiswa/bimbingan_pkl/form', compact('title', 'mhs'));
    }
    public function bimbinganPklStore(): void
    {
        $this->requireRole(['mahasiswa']);
        $mhs = $this->getMahasiswaAktif();
        if (!$mhs) {
            $_SESSION['flash_error'] = 'Data mahasiswa tidak ditemukan.';
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        $idMahasiswa = (int)$mhs['id'];

        $conn = db();
        $stmtPembimbing = $conn->prepare("
            SELECT pembimbing_id
            FROM pengajuan
            WHERE mahasiswa_id = ?
              AND jenis = 'pkl'
              AND status = 'diterima'
              AND pembimbing_id IS NOT NULL
            ORDER BY COALESCE(updated_at, created_at) DESC
            LIMIT 1
        ");
        $stmtPembimbing->bind_param('i', $idMahasiswa);
        $stmtPembimbing->execute();
        $pklAktif = $stmtPembimbing->get_result()->fetch_assoc();
        $stmtPembimbing->close();

        $idDosen = (int)($pklAktif['pembimbing_id'] ?? 0);
        if ($idDosen <= 0) {
            $_SESSION['flash_error'] = 'Dosen pembimbing belum ditentukan.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/bimbinganPklIndex');
            exit;
        }

        $tanggal = $_POST['tanggal'] ?? '';
        $media   = $_POST['media'] ?? 'Tatap Muka';
        $topik   = trim($_POST['topik'] ?? '');
        $catMhs  = trim($_POST['catatan_mahasiswa'] ?? '');

        if ($tanggal === '' || $topik === '') {
            $_SESSION['flash_error'] = 'Tanggal dan topik wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=mahasiswa/bimbinganPklCreateForm');
            exit;
        }

        $stmt = $conn->prepare("
        INSERT INTO log_bimbingan_pkl
        (id_mahasiswa, id_dosen, tanggal, media, topik, catatan_mahasiswa, status_verifikasi)
        VALUES
        (?, ?, ?, ?, ?, ?, 'Menunggu')
    ");
        $stmt->bind_param('iissss', $idMahasiswa, $idDosen, $tanggal, $media, $topik, $catMhs);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Log bimbingan PKL berhasil ditambahkan.';
        header('Location: ' . BASE_URL . '/?r=mahasiswa/bimbinganPklIndex');
        exit;
    }
}
