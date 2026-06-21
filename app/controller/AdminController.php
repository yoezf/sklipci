<?php

class AdminController extends Controller
{
    /* ====================================================
     *  DASHBOARD
     * ==================================================== */
    public function dashboard(): void
    {
        $this->requireRole(['admin']);
        $conn = db();

        // Statistik umum
        $totalMahasiswa = $conn->query("SELECT COUNT(*) AS c FROM mahasiswa")->fetch_assoc()['c'] ?? 0;
        $totalDosen     = $conn->query("SELECT COUNT(*) AS c FROM dosen")->fetch_assoc()['c'] ?? 0;
        $totalProdi     = $conn->query("SELECT COUNT(*) AS c FROM prodi")->fetch_assoc()['c'] ?? 0;

        // Statistik pengajuan
        $qPengajuan = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'diajukan' THEN 1 ELSE 0 END) AS menunggu,
            SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) AS diterima,
            SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) AS ditolak
        FROM pengajuan
    ");
        $pengajuanStat = $qPengajuan ? $qPengajuan->fetch_assoc() : [
            'total' => 0,
            'menunggu' => 0,
            'diterima' => 0,
            'ditolak' => 0,
        ];

        // Statistik log
        $totalLogPkl       = $conn->query("SELECT COUNT(*) AS c FROM log_pkl")->fetch_assoc()['c'] ?? 0;
        $totalLogBimbingan = $conn->query("SELECT COUNT(*) AS c FROM log_bimbingan")->fetch_assoc()['c'] ?? 0;

        // Jadwal 7 hari ke depan
        $jadwalMingguIni = 0;
        $resJ = $conn->query("
        SELECT COUNT(*) AS c 
        FROM jadwal 
        WHERE tanggal >= CURDATE() 
          AND tanggal <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
        if ($resJ) {
            $jadwalMingguIni = $resJ->fetch_assoc()['c'] ?? 0;
        }

        // Pengajuan terbaru
        $pengajuanTerbaru = [];
        $resPT = $conn->query("
        SELECT p.*, m.nama AS nama_mhs, m.nim, pr.nama_prodi
        FROM pengajuan p
        JOIN mahasiswa m ON p.mahasiswa_id = m.id
        JOIN prodi pr ON m.prodi_id = pr.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
        if ($resPT) {
            $pengajuanTerbaru = $resPT->fetch_all(MYSQLI_ASSOC);
        }

        // Jadwal terdekat
        $jadwalTerdekat = [];
        $resJT = $conn->query("
    SELECT 
        j.*,
        p.judul,
        m.nama AS nama_mhs,
        m.nim,
        pr.nama_prodi,
        dpeng.nama AS nama_penguji
    FROM jadwal j
    JOIN pengajuan p ON j.pengajuan_id = p.id
    JOIN mahasiswa m ON p.mahasiswa_id = m.id
    JOIN prodi pr ON m.prodi_id = pr.id
    LEFT JOIN dosen dpeng ON j.dosen_penguji_id = dpeng.id
    WHERE j.tanggal >= CURDATE()
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
    LIMIT 5
");

        if ($resJT) {
            $jadwalTerdekat = $resJT->fetch_all(MYSQLI_ASSOC);
        }

        // Laporan akhir terbaru
        $laporanTerbaru = [];
        $resLA = $conn->query("
        SELECT la.*, m.nama AS nama_mhs, m.nim, pr.nama_prodi
        FROM laporan_akhir la
        JOIN mahasiswa m ON la.mahasiswa_id = m.id
        JOIN prodi pr ON m.prodi_id = pr.id
        ORDER BY la.created_at DESC
        LIMIT 5
    ");
        if ($resLA) {
            $laporanTerbaru = $resLA->fetch_all(MYSQLI_ASSOC);
        }

        $title = 'Dashboard Admin';

        $this->view('admin/dashboard', compact(
            'title',
            'totalMahasiswa',
            'totalDosen',
            'totalProdi',
            'pengajuanStat',
            'totalLogPkl',
            'totalLogBimbingan',
            'jadwalMingguIni',
            'pengajuanTerbaru',
            'jadwalTerdekat',
            'laporanTerbaru'
        ));
    }


    /* ====================================================
 *  PRODI CRUD (FIX + KAPRODI)
 * ==================================================== */

    public function prodiIndex(): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $sql  = "
        SELECT p.*, d.nama AS kaprodi
        FROM prodi p
        LEFT JOIN dosen d ON p.kaprodi_id = d.id
        ORDER BY p.nama_prodi ASC
    ";

        $result = $conn->query($sql);
        $items  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Data Prodi';
        $this->view('admin/prodi/index', compact('title', 'items'));
    }

    public function prodiCreateForm(): void
    {
        $this->requireRole(['admin']);

        $conn  = db();
        $dosen = $conn->query("SELECT id, nama FROM dosen ORDER BY nama ASC")
            ->fetch_all(MYSQLI_ASSOC);

        $title = 'Tambah Prodi';
        $this->view('admin/prodi/create', compact('title', 'dosen'));
    }

    public function prodiStore(): void
    {
        $this->requireRole(['admin']);

        $kode       = trim($_POST['kode_prodi'] ?? '');
        $nama       = trim($_POST['nama_prodi'] ?? '');
        $kaprodi_id = $_POST['kaprodi_id'] ?? null;
        if ($kaprodi_id === '') $kaprodi_id = null;
        else $kaprodi_id = (int)$kaprodi_id;

        if ($kode === '' || $nama === '') {
            $_SESSION['flash_error'] = 'Kode dan nama prodi wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=admin/prodiCreateForm');
            exit;
        }

        $conn = db();

        // validasi kode unik
        $cek = $conn->prepare("SELECT id FROM prodi WHERE kode_prodi = ? LIMIT 1");
        $cek->bind_param('s', $kode);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $_SESSION['flash_error'] = 'Kode prodi sudah digunakan.';
            header('Location: ' . BASE_URL . '/?r=admin/prodiCreateForm');
            exit;
        }
        $cek->close();

        // insert prodi
        if ($kaprodi_id === null) {
            $stmt = $conn->prepare("INSERT INTO prodi (kode_prodi, nama_prodi, kaprodi_id) VALUES (?, ?, NULL)");
            $stmt->bind_param('ss', $kode, $nama);
        } else {
            $stmt = $conn->prepare("INSERT INTO prodi (kode_prodi, nama_prodi, kaprodi_id) VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $kode, $nama, $kaprodi_id);
        }
        $stmt->execute();
        $stmt->close();

        header('Location: ' . BASE_URL . '/?r=admin/prodiIndex');
        exit;
    }

    public function prodiEditForm(): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/prodiIndex');
            exit;
        }

        $conn = db();

        // ambil data prodi
        $stmt = $conn->prepare("SELECT * FROM prodi WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $prodi = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prodi) {
            header('Location: ' . BASE_URL . '/?r=admin/prodiIndex');
            exit;
        }

        // ambil list dosen untuk dropdown kaprodi
        $dosen = $conn->query("SELECT id, nama FROM dosen ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

        $title = 'Edit Prodi';
        $this->view('admin/prodi/edit', compact('title', 'prodi', 'dosen'));
    }

    public function prodiUpdate(): void
    {
        $this->requireRole(['admin']);

        $id         = (int)($_POST['id'] ?? 0);
        $kode       = trim($_POST['kode_prodi'] ?? '');
        $nama       = trim($_POST['nama_prodi'] ?? '');
        $kaprodi_id = $_POST['kaprodi_id'] ?? null;
        if ($kaprodi_id === '') $kaprodi_id = null;
        else $kaprodi_id = (int)$kaprodi_id;

        if ($id <= 0 || $kode === '' || $nama === '') {
            $_SESSION['flash_error'] = 'Data tidak lengkap.';
            header('Location: ' . BASE_URL . '/?r=admin/prodiIndex');
            exit;
        }

        $conn = db();

        // validasi kode unik (kecuali dirinya sendiri)
        $cek = $conn->prepare("SELECT id FROM prodi WHERE kode_prodi = ? AND id != ? LIMIT 1");
        $cek->bind_param('si', $kode, $id);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $_SESSION['flash_error'] = 'Kode prodi sudah digunakan.';
            header('Location: ' . BASE_URL . '/?r=admin/prodiEditForm&id=' . $id);
            exit;
        }
        $cek->close();

        // update prodi
        if ($kaprodi_id === null) {
            $stmt = $conn->prepare("UPDATE prodi SET kode_prodi = ?, nama_prodi = ?, kaprodi_id = NULL WHERE id = ?");
            $stmt->bind_param('ssi', $kode, $nama, $id);
        } else {
            $stmt = $conn->prepare("UPDATE prodi SET kode_prodi = ?, nama_prodi = ?, kaprodi_id = ? WHERE id = ?");
            $stmt->bind_param('ssii', $kode, $nama, $kaprodi_id, $id);
        }
        $stmt->execute();
        $stmt->close();

        header('Location: ' . BASE_URL . '/?r=admin/prodiIndex');
        exit;
    }

    public function prodiDelete(): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/prodiIndex');
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("DELETE FROM prodi WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ' . BASE_URL . '/?r=admin/prodiIndex');
        exit;
    }



    /* ====================================================
     *  MAHASISWA CRUD
     * ==================================================== */

    public function mahasiswaIndex(): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $sql  = "
            SELECT m.*, p.nama_prodi, u.username
            FROM mahasiswa m
            JOIN prodi p ON m.prodi_id = p.id
            JOIN users u ON m.user_id = u.id
            ORDER BY m.nim ASC
        ";
        $result = $conn->query($sql);
        $items  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Data Mahasiswa';
        $this->view('admin/mahasiswa/index', compact('title', 'items'));
    }

    public function mahasiswaCreateForm(): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $result = $conn->query("SELECT id, nama_prodi FROM prodi ORDER BY nama_prodi ASC");
        $prodiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Tambah Mahasiswa';
        $this->view('admin/mahasiswa/create', compact('title', 'prodiList'));
    }

    public function mahasiswaStore(): void
    {
        $this->requireRole(['admin']);

        $nim      = trim($_POST['nim'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $prodi_id = (int)($_POST['prodi_id'] ?? 0);
        $no_hp    = trim($_POST['no_hp'] ?? '');
        $kelas    = trim($_POST['kelas'] ?? '');
        $angkatan = trim($_POST['angkatan'] ?? '');

        if (!preg_match('/^[0-9]{1,13}$/', $no_hp)) {
            $_SESSION['flash_error'] = "Nomor HP hanya boleh angka dan maksimal 13 digit.";
            // Cegah open redirect: hanya kembali ke URL internal, fallback ke form.
            safe_redirect($_SERVER['HTTP_REFERER'] ?? null, '/?r=admin/mahasiswaCreateForm');
        }

        if ($nim === '' || $nama === '' || $username === '' || $prodi_id <= 0) {
            $_SESSION['flash_error'] = 'NIM, nama, username, dan prodi wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaCreateForm');
            exit;
        }

        $conn = db();
        $conn->begin_transaction();

        try {
            // 1. Buat user baru
            $passwordHash = password_hash($username, PASSWORD_DEFAULT);
            $role         = 'mahasiswa';
            $status       = 'aktif';

            $stmtUser = $conn->prepare("
                INSERT INTO users (username, password, nama, role, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtUser->bind_param('sssss', $username, $passwordHash, $nama, $role, $status);
            $stmtUser->execute();
            $userId = $stmtUser->insert_id;
            $stmtUser->close();

            // 2. Buat record mahasiswa
            $stmtMhs = $conn->prepare("
                INSERT INTO mahasiswa (user_id, nim, nama, prodi_id, no_hp, kelas, angkatan)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtMhs->bind_param('ississs', $userId, $nim, $nama, $prodi_id, $no_hp, $kelas, $angkatan);
            $stmtMhs->execute();
            $stmtMhs->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = 'Gagal menyimpan data mahasiswa: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaCreateForm');
            exit;
        }

        header('Location: ' . BASE_URL . '/?r=admin/mahasiswaIndex');
        exit;
    }

    /* ====================================================
     *  MAHASISWA EDIT
     * ==================================================== */

    public function mahasiswaEditForm(): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaIndex');
            exit;
        }

        $conn = db();

        // Ambil data mahasiswa + user
        $stmt = $conn->prepare("
            SELECT m.*, u.username
            FROM mahasiswa m
            JOIN users u ON m.user_id = u.id
            WHERE m.id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $mhs  = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$mhs) {
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaIndex');
            exit;
        }

        // Ambil list prodi
        $resultProdi = $conn->query("SELECT id, nama_prodi FROM prodi ORDER BY nama_prodi ASC");
        $prodiList   = $resultProdi ? $resultProdi->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Edit Mahasiswa';
        $this->view('admin/mahasiswa/edit', compact('title', 'mhs', 'prodiList'));
    }

    public function mahasiswaUpdate(): void
    {
        $this->requireRole(['admin']);

        $id       = (int)($_POST['id'] ?? 0);
        $nim      = trim($_POST['nim'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $prodi_id = (int)($_POST['prodi_id'] ?? 0);
        $no_hp    = trim($_POST['no_hp'] ?? '');
        $kelas    = trim($_POST['kelas'] ?? '');
        $angkatan = trim($_POST['angkatan'] ?? '');

        if (!preg_match('/^[0-9]{1,13}$/', $no_hp)) {
            $_SESSION['flash_error'] = "Nomor HP hanya boleh angka dan maksimal 13 digit.";
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaEditForm&id=' . $id);
            exit;
        }


        if ($id <= 0 || $nim === '' || $nama === '' || $username === '' || $prodi_id <= 0) {
            $_SESSION['flash_error'] = 'Data wajib diisi lengkap.';
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaEditForm&id=' . $id);
            exit;
        }

        $conn = db();
        $conn->begin_transaction();

        try {
            // Ambil user_id mahasiswa
            $stmt = $conn->prepare("SELECT user_id FROM mahasiswa WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $rowMhs = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$rowMhs) {
                throw new Exception('Mahasiswa tidak ditemukan');
            }

            $userId = (int)$rowMhs['user_id'];

            // Update table users
            $stmtUser = $conn->prepare("
                UPDATE users
                SET username = ?, nama = ?
                WHERE id = ?
            ");
            $stmtUser->bind_param('ssi', $username, $nama, $userId);
            $stmtUser->execute();
            $stmtUser->close();

            // Update table mahasiswa
            $stmtMhs = $conn->prepare("
                UPDATE mahasiswa
                SET nim = ?, nama = ?, prodi_id = ?, no_hp = ?, kelas = ?, angkatan = ?
                WHERE id = ?
            ");
            $stmtMhs->bind_param('ssisssi', $nim, $nama, $prodi_id, $no_hp, $kelas, $angkatan, $id);
            $stmtMhs->execute();
            $stmtMhs->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = 'Gagal update mahasiswa: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaEditForm&id=' . $id);
            exit;
        }

        header('Location: ' . BASE_URL . '/?r=admin/mahasiswaIndex');
        exit;
    }

    /* ====================================================
     *  MAHASISWA DELETE
     * ==================================================== */

    public function mahasiswaDelete(): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaIndex');
            exit;
        }

        $conn = db();
        $conn->begin_transaction();

        try {
            // 1. Ambil user_id mahasiswa
            $stmt = $conn->prepare("SELECT user_id FROM mahasiswa WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                throw new Exception('Mahasiswa tidak ditemukan');
            }

            $userId = (int)$row['user_id'];

            // 2. BERSIHKAN SEMUA RELASI YANG PAKAI mahasiswa_id

            // 2a. Hapus jadwal seminar/sidang milik mahasiswa ini
            $stmtDel = $conn->prepare("DELETE FROM jadwal WHERE mahasiswa_id = ?");
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            $stmtDel->close();

            // 2b. Hapus laporan akhir milik mahasiswa ini
            $stmtDel = $conn->prepare("DELETE FROM laporan_akhir WHERE mahasiswa_id = ?");
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            $stmtDel->close();

            // 2c. Hapus log bimbingan skripsi mahasiswa ini
            $stmtDel = $conn->prepare("DELETE FROM log_bimbingan WHERE mahasiswa_id = ?");
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            $stmtDel->close();

            // 2d. Hapus log PKL mahasiswa ini
            $stmtDel = $conn->prepare("DELETE FROM log_pkl WHERE mahasiswa_id = ?");
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            $stmtDel->close();

            // 2e. Hapus semua pengajuan milik mahasiswa ini (PKL, skripsi, seminar, sidang)
            $stmtDel = $conn->prepare("DELETE FROM pengajuan WHERE mahasiswa_id = ?");
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            $stmtDel->close();

            // 3. Hapus user → baris mahasiswa ikut hilang via ON DELETE CASCADE
            $stmtDelUser = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmtDelUser->bind_param('i', $userId);
            $stmtDelUser->execute();
            $stmtDelUser->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = 'Gagal menghapus mahasiswa: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/?r=admin/mahasiswaIndex');
        exit;
    }

    /* ====================================================
 *  MAHASISWA IMPORT (CSV)
 * ==================================================== */

    public function mahasiswaImportForm(): void
    {
        $this->requireRole(['admin']);
        $title = 'Import Mahasiswa (CSV)';
        $this->view('admin/mahasiswa/import', compact('title'));
    }

    public function mahasiswaImport(): void
    {
        $this->requireRole(['admin']);

        // supaya error mysqli jadi exception (mudah debug)
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'File CSV tidak valid / gagal upload.';
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaImportForm');
            exit;
        }

        $conn = db();
        $tmp  = $_FILES['csv_file']['tmp_name'];

        $fh = fopen($tmp, 'r');
        if (!$fh) {
            $_SESSION['flash_error'] = 'File CSV gagal dibaca.';
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaImportForm');
            exit;
        }

        // deteksi delimiter dari baris pertama
        $firstLine = fgets($fh);
        rewind($fh);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $header = fgetcsv($fh, 0, $delimiter);
        if (!$header) {
            fclose($fh);
            $_SESSION['flash_error'] = 'Header CSV kosong.';
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaImportForm');
            exit;
        }

        // hapus BOM (Excel sering bikin)
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        // helper cari index kolom
        $col = function (string $name) use ($header) {
            $i = array_search($name, $header, true);
            return ($i === false) ? null : $i;
        };

        // WAJIB
        $iNim      = $col('nim');
        $iNama     = $col('nama');
        $iProdiId  = $col('prodi_id');
        $iAngkatan = $col('angkatan');

        // OPSIONAL
        $iKelas    = $col('kelas');
        $iNoHp     = $col('no_hp');
        $iUsername = $col('username');
        $iPassword = $col('password');

        if ($iNim === null || $iNama === null || $iProdiId === null || $iAngkatan === null) {
            fclose($fh);
            $_SESSION['flash_error'] = "Header wajib: nim,nama,prodi_id,angkatan";
            header('Location: ' . BASE_URL . '/?r=admin/mahasiswaImportForm');
            exit;
        }

        $success = 0;
        $skip    = 0;

        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
            if (count($row) === 1 && trim($row[0]) === '') continue;

            $nim      = trim($row[$iNim] ?? '');
            $nama     = trim($row[$iNama] ?? '');
            $prodi_id = (int) trim($row[$iProdiId] ?? '0');
            $angkatan = (int) trim($row[$iAngkatan] ?? '0');

            $kelas = ($iKelas !== null) ? trim($row[$iKelas] ?? '') : '';
            $no_hp = ($iNoHp  !== null) ? trim($row[$iNoHp]  ?? '') : '';

            $username = ($iUsername !== null) ? trim($row[$iUsername] ?? '') : '';
            $password = ($iPassword !== null) ? trim($row[$iPassword] ?? '') : '';

            if ($nim === '' || $nama === '' || $prodi_id <= 0 || $angkatan <= 0) {
                $skip++;
                continue;
            }

            if ($username === '') $username = $nim;
            if ($password === '') $password = $username;

            // semester dihitung otomatis dari angkatan
            $semester = $this->hitungSemester($angkatan);

            // validasi prodi_id harus ada
            $st = $conn->prepare("SELECT id FROM prodi WHERE id = ? LIMIT 1");
            $st->bind_param('i', $prodi_id);
            $st->execute();
            $okProdi = (bool)$st->get_result()->fetch_assoc();
            $st->close();
            if (!$okProdi) {
                $skip++;
                continue;
            }

            // cek duplikat username
            $st = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $st->bind_param('s', $username);
            $st->execute();
            if ($st->get_result()->fetch_assoc()) {
                $st->close();
                $skip++;
                continue;
            }
            $st->close();

            // cek duplikat nim
            $st = $conn->prepare("SELECT id FROM mahasiswa WHERE nim = ? LIMIT 1");
            $st->bind_param('s', $nim);
            $st->execute();
            if ($st->get_result()->fetch_assoc()) {
                $st->close();
                $skip++;
                continue;
            }
            $st->close();

            // transaksi insert
            $conn->begin_transaction();
            try {
                $hash   = password_hash($password, PASSWORD_DEFAULT);
                $role   = 'mahasiswa';
                $status = 'aktif';

                // 1) insert users
                $stU = $conn->prepare("INSERT INTO users (username,password,nama,role,status) VALUES (?,?,?,?,?)");
                $stU->bind_param('sssss', $username, $hash, $nama, $role, $status);
                $stU->execute();
                $userId = $stU->insert_id;
                $stU->close();

                // 2) insert mahasiswa (sudah termasuk semester)
                $stM = $conn->prepare("
                INSERT INTO mahasiswa (user_id, nim, nama, prodi_id, no_hp, kelas, angkatan, semester)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
                $stM->bind_param('ississii', $userId, $nim, $nama, $prodi_id, $no_hp, $kelas, $angkatan, $semester);
                $stM->execute();
                $stM->close();

                $conn->commit();
                $success++;
            } catch (\Throwable $e) {
                $conn->rollback();
                fclose($fh);
                $_SESSION['flash_error'] = "Gagal import NIM {$nim}: " . $e->getMessage();
                header('Location: ' . BASE_URL . '/?r=admin/mahasiswaImportForm');
                exit;
            }
        }

        fclose($fh);
        $_SESSION['flash_success'] = "Import selesai. Berhasil: {$success}, Skip: {$skip}.";
        header('Location: ' . BASE_URL . '/?r=admin/mahasiswaIndex');
        exit;
    }

    private function hitungSemester(int $angkatan): int
    {
        $year  = (int)date('Y');
        $month = (int)date('n');

        $diff = $year - $angkatan;   // 2026-2021=5
        $sem  = ($diff * 2) - 1;     // -> 9 (sesuai contohmu)

        // Feb–Aug dianggap semester genap
        if ($month >= 2 && $month <= 8) $sem += 1;

        return max(1, $sem);
    }




    /* ===========================
     *  DOSEN CRUD
     * =========================== */

    public function dosenIndex(): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $sql = "
            SELECT d.*, p.nama_prodi, u.username
            FROM dosen d
            JOIN prodi p ON d.prodi_id = p.id
            JOIN users u ON d.user_id = u.id
            ORDER BY d.nip ASC
        ";
        $result = $conn->query($sql);
        $items  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Data Dosen';
        $this->view('admin/dosen/index', compact('title', 'items'));
    }

    public function dosenCreateForm(): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $result = $conn->query("SELECT id, nama_prodi FROM prodi ORDER BY nama_prodi ASC");
        $prodiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Tambah Dosen';
        $this->view('admin/dosen/create', compact('title', 'prodiList'));
    }

    public function dosenStore(): void
    {
        $this->requireRole(['admin']);

        $nip      = trim($_POST['nip'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $prodi_id = (int)($_POST['prodi_id'] ?? 0);
        $no_hp    = trim($_POST['no_hp'] ?? '');

        if (!preg_match('/^[0-9]{1,13}$/', $no_hp)) {
            $_SESSION['flash_error'] = "Nomor HP hanya boleh angka dan maksimal 13 digit.";
            header('Location: ' . BASE_URL . '/?r=admin/dosenCreateForm');
            exit;
        }


        if ($nip === '' || $nama === '' || $username === '' || $prodi_id <= 0) {
            $_SESSION['flash_error'] = 'NIP, nama, username, dan prodi wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=admin/dosenCreateForm');
            exit;
        }

        $conn = db();
        $conn->begin_transaction();

        try {
            // 1. Buat user baru
            $passwordHash = password_hash($username, PASSWORD_DEFAULT);
            $role         = 'dosen';
            $status       = 'aktif';

            $stmtUser = $conn->prepare("INSERT INTO users (username, password, nama, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmtUser->bind_param('sssss', $username, $passwordHash, $nama, $role, $status);
            $stmtUser->execute();
            $userId = $stmtUser->insert_id;
            $stmtUser->close();

            // 2. Buat record di tabel dosen
            $stmtDosen = $conn->prepare("
                INSERT INTO dosen (user_id, nip, nama, prodi_id, no_hp)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtDosen->bind_param('issis', $userId, $nip, $nama, $prodi_id, $no_hp);
            $stmtDosen->execute();
            $stmtDosen->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = 'Gagal menyimpan data dosen: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/?r=admin/dosenCreateForm');
            exit;
        }

        header('Location: ' . BASE_URL . '/?r=admin/dosenIndex');
        exit;
    }

    public function dosenEditForm(): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/dosenIndex');
            exit;
        }

        $conn = db();

        $stmt = $conn->prepare("
            SELECT d.*, u.username
            FROM dosen d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dosen  = $result->fetch_assoc();
        $stmt->close();

        if (!$dosen) {
            header('Location: ' . BASE_URL . '/?r=admin/dosenIndex');
            exit;
        }

        $resultProdi = $conn->query("SELECT id, nama_prodi FROM prodi ORDER BY nama_prodi ASC");
        $prodiList   = $resultProdi ? $resultProdi->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Edit Dosen';
        $this->view('admin/dosen/edit', compact('title', 'dosen', 'prodiList'));
    }

    public function dosenUpdate(): void
    {
        $this->requireRole(['admin']);

        $id       = (int)($_POST['id'] ?? 0);
        $nip      = trim($_POST['nip'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $prodi_id = (int)($_POST['prodi_id'] ?? 0);
        $no_hp    = trim($_POST['no_hp'] ?? '');

        if (!preg_match('/^[0-9]{1,13}$/', $no_hp)) {
            $_SESSION['flash_error'] = "Nomor HP hanya boleh angka dan maksimal 13 digit.";
            header('Location: ' . BASE_URL . '/?r=admin/dosenEditForm&id=' . $id);
            exit;
        }


        if ($id <= 0 || $nip === '' || $nama === '' || $username === '' || $prodi_id <= 0) {
            $_SESSION['flash_error'] = 'Data wajib diisi lengkap.';
            header('Location: ' . BASE_URL . '/?r=admin/dosenEditForm&id=' . $id);
            exit;
        }

        $conn = db();
        $conn->begin_transaction();

        try {
            // Cari dosen untuk dapat user_id
            $stmt = $conn->prepare("SELECT user_id FROM dosen WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row    = $result->fetch_assoc();
            $stmt->close();

            if (!$row) {
                throw new Exception('Dosen tidak ditemukan');
            }

            $userId = (int)$row['user_id'];

            // Update users
            $stmtUser = $conn->prepare("UPDATE users SET username = ?, nama = ? WHERE id = ?");
            $stmtUser->bind_param('ssi', $username, $nama, $userId);
            $stmtUser->execute();
            $stmtUser->close();

            // Update dosen
            $stmtDosen = $conn->prepare("
                UPDATE dosen
                SET nip = ?, nama = ?, prodi_id = ?, no_hp = ?
                WHERE id = ?
            ");
            $stmtDosen->bind_param('ssisi', $nip, $nama, $prodi_id, $no_hp, $id);
            $stmtDosen->execute();
            $stmtDosen->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = 'Gagal update dosen: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/?r=admin/dosenEditForm&id=' . $id);
            exit;
        }

        header('Location: ' . BASE_URL . '/?r=admin/dosenIndex');
        exit;
    }

    public function dosenDelete(): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/dosenIndex');
            exit;
        }

        $conn = db();
        $conn->begin_transaction();

        try {
            // 1. Ambil user_id dari dosen
            $stmt = $conn->prepare("SELECT user_id FROM dosen WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row    = $result->fetch_assoc();
            $stmt->close();

            if (!$row) {
                throw new Exception('Dosen tidak ditemukan');
            }

            $userId = (int)$row['user_id'];

            // 2. Putuskan semua relasi yang menunjuk ke dosen ini

            // 2a. Sebagai pembimbing di tabel pengajuan
            $stmt = $conn->prepare("UPDATE pengajuan SET pembimbing_id = NULL WHERE pembimbing_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // 2b. Sebagai pembimbing di tabel jadwal
            $stmt = $conn->prepare("UPDATE jadwal SET dosen_pembimbing_id = NULL WHERE dosen_pembimbing_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // 2c. Sebagai penguji 1 di tabel jadwal
            $stmt = $conn->prepare("UPDATE jadwal SET dosen_penguji_id = NULL WHERE dosen_penguji_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // 2d. Sebagai penguji 2 di tabel jadwal
            $stmt = $conn->prepare("UPDATE jadwal SET dosen_penguji_2_id = NULL WHERE dosen_penguji_2_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // 2e. Log bimbingan – kita hapus semua log milik dosen ini
            $stmt = $conn->prepare("DELETE FROM log_bimbingan WHERE dosen_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // 3. Hapus user → baris di tabel dosen ikut terhapus via ON DELETE CASCADE
            $stmtDel = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmtDel->bind_param('i', $userId);
            $stmtDel->execute();
            $stmtDel->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = 'Gagal menghapus dosen: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/?r=admin/dosenIndex');
        exit;
    }


    /* ===========================
     *  PENGAJUAN (PKL/SKRIPSI/SEMINAR/SIDANG)
     * =========================== */

    private function pengajuanIndexInternal(string $jenis, string $title): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $stmt = $conn->prepare("
            SELECT p.*, m.nim, m.nama AS nama_mhs, pr.nama_prodi
            FROM pengajuan p
            JOIN mahasiswa m ON p.mahasiswa_id = m.id
            JOIN prodi pr ON m.prodi_id = pr.id
            WHERE p.jenis = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param('s', $jenis);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $this->view('admin/pengajuan/index', compact('title', 'items', 'jenis'));
    }

    private function pengajuanDetailInternal(string $jenis, string $title): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/pengajuan' . ucfirst($jenis) . 'Index');
            exit;
        }

        $conn = db();

        // Ambil detail pengajuan + data mahasiswa + prodi + pembimbing (jika ada)
        $stmt = $conn->prepare("
        SELECT p.*, 
               m.nim, 
               m.nama AS nama_mhs, 
               pr.nama_prodi,
               d.nama AS nama_pembimbing,
               d.nip  AS nip_pembimbing
        FROM pengajuan p
        JOIN mahasiswa m ON p.mahasiswa_id = m.id
        JOIN prodi pr ON m.prodi_id = pr.id
        LEFT JOIN dosen d ON p.pembimbing_id = d.id
        WHERE p.id = ? AND p.jenis = ?
        LIMIT 1
    ");
        $stmt->bind_param('is', $id, $jenis);
        $stmt->execute();
        $result    = $stmt->get_result();
        $pengajuan = $result->fetch_assoc();
        $stmt->close();

        if (!$pengajuan) {
            header('Location: ' . BASE_URL . '/?r=admin/pengajuan' . ucfirst($jenis) . 'Index');
            exit;
        }

        // Ambil daftar dosen untuk dropdown penetapan pembimbing
        $resultDosen = $conn->query("
        SELECT d.id, d.nip, d.nama, pr.nama_prodi
        FROM dosen d
        JOIN prodi pr ON d.prodi_id = pr.id
        ORDER BY pr.nama_prodi ASC, d.nama ASC
    ");
        $dosenList = $resultDosen ? $resultDosen->fetch_all(MYSQLI_ASSOC) : [];

        $title = $title;
        $this->view('admin/pengajuan/detail', compact('title', 'pengajuan', 'jenis', 'dosenList'));
    }


    public function pengajuanPklIndex(): void
    {
        $this->pengajuanIndexInternal('pkl', 'Pengajuan PKL');
    }

    public function pengajuanSkripsiIndex(): void
    {
        $this->pengajuanIndexInternal('skripsi', 'Pengajuan Skripsi');
    }

    public function pengajuanSeminarIndex(): void
    {
        $this->pengajuanIndexInternal('seminar', 'Pengajuan Seminar');
    }

    public function pengajuanSidangIndex(): void
    {
        $this->pengajuanIndexInternal('sidang', 'Pengajuan Sidang');
    }

    public function pengajuanPklDetail(): void
    {
        $this->pengajuanDetailInternal('pkl', 'Detail Pengajuan PKL');
    }

    public function pengajuanSkripsiDetail(): void
    {
        $this->pengajuanDetailInternal('skripsi', 'Detail Pengajuan Skripsi');
    }

    public function pengajuanSeminarDetail(): void
    {
        $this->pengajuanDetailInternal('seminar', 'Detail Pengajuan Seminar');
    }

    public function pengajuanSidangDetail(): void
    {
        $this->pengajuanDetailInternal('sidang', 'Detail Pengajuan Sidang');
    }

    public function pengajuanUpdateStatus(): void
    {
        $this->requireRole(['admin']);

        $id    = (int)($_POST['id'] ?? 0);
        $jenis = $_POST['jenis'] ?? '';
        $status = $_POST['status'] ?? 'diajukan';
        $catatan_admin = trim($_POST['catatan_admin'] ?? '');
        $pembimbing_id = (int)($_POST['pembimbing_id'] ?? 0);

        if ($id <= 0 || !in_array($jenis, ['pkl', 'skripsi', 'seminar', 'sidang'], true)) {
            header('Location: ' . BASE_URL . '/?r=admin/dashboard');
            exit;
        }

        if (!in_array($status, ['diajukan', 'diterima', 'ditolak'], true)) {
            $status = 'diajukan';
        }

        $conn = db();

        // Update status + catatan + pembimbing (NULL jika 0)
        $stmt = $conn->prepare("
        UPDATE pengajuan
        SET status = ?, 
            catatan_admin = ?, 
            pembimbing_id = NULLIF(?, 0),
            updated_at = NOW()
        WHERE id = ? AND jenis = ?
    ");
        $stmt->bind_param('ssiis', $status, $catatan_admin, $pembimbing_id, $id, $jenis);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Pengajuan berhasil diperbarui.';

        header('Location: ' . BASE_URL . '/?r=admin/pengajuan' . ucfirst($jenis) . 'Detail&id=' . $id);
        exit;
    }

    /* ===========================
     *  JADWAL SEMINAR & SIDANG
     * =========================== */

    private function jadwalIndexInternal(string $jenis, string $title): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $stmt = $conn->prepare("
    SELECT j.*,
           m.nim,
           m.nama AS nama_mhs,
           pr.nama_prodi,
           dp.nama   AS nama_pembimbing,
           dpg1.nama AS nama_penguji1,
           dpg2.nama AS nama_penguji2
    FROM jadwal j
    JOIN mahasiswa m ON j.mahasiswa_id = m.id
    JOIN prodi pr ON m.prodi_id = pr.id
    LEFT JOIN dosen dp   ON j.dosen_pembimbing_id = dp.id
    LEFT JOIN dosen dpg1 ON j.dosen_penguji_id    = dpg1.id
    LEFT JOIN dosen dpg2 ON j.dosen_penguji_2_id  = dpg2.id
    WHERE j.jenis = ?
    ORDER BY j.tanggal DESC, j.jam_mulai DESC
");
        $stmt->bind_param('s', $jenis);
        $stmt->execute();
        $result = $stmt->get_result();
        $items  = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $this->view('admin/jadwal/index', compact('title', 'items', 'jenis'));
    }

    private function jadwalCreateFormInternal(string $jenis, string $title): void
    {
        $this->requireRole(['admin']);

        $conn = db();

        // Ambil pengajuan yang sudah DITERIMA untuk jenis ini
        $stmt = $conn->prepare("
            SELECT p.id, p.judul, m.nim, m.nama AS nama_mhs, pr.nama_prodi
            FROM pengajuan p
            JOIN mahasiswa m ON p.mahasiswa_id = m.id
            JOIN prodi pr ON m.prodi_id = pr.id
            WHERE p.jenis = ?
              AND p.status = 'diterima'
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param('s', $jenis);
        $stmt->execute();
        $result           = $stmt->get_result();
        $pengajuanList    = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Ambil semua dosen untuk dipilih sebagai penguji (opsional)
        $resultDosen = $conn->query("
            SELECT d.id, d.nama, pr.nama_prodi
            FROM dosen d
            JOIN prodi pr ON d.prodi_id = pr.id
            ORDER BY pr.nama_prodi ASC, d.nama ASC
        ");
        $dosenList = $resultDosen ? $resultDosen->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('admin/jadwal/create', compact('title', 'jenis', 'pengajuanList', 'dosenList'));
    }

    private function jadwalStoreInternal(string $jenis): void
    {
        $this->requireRole(['admin']);

        $pengajuan_id      = (int)($_POST['pengajuan_id'] ?? 0);
        $tanggal           = $_POST['tanggal'] ?? '';
        $jam_mulai         = $_POST['jam_mulai'] ?? '';
        $jam_selesai       = $_POST['jam_selesai'] ?? null;
        $ruangan           = trim($_POST['ruangan'] ?? '');
        $dosen_penguji_id  = (int)($_POST['dosen_penguji_id'] ?? 0);
        $dosen_penguji_2_id = (int)($_POST['dosen_penguji_2_id'] ?? 0); // ⬅️ baru
        $catatan           = trim($_POST['catatan'] ?? '');

        if ($pengajuan_id <= 0 || $tanggal === '' || $jam_mulai === '' || $ruangan === '') {
            $_SESSION['flash_error'] = 'Pengajuan, tanggal, jam mulai, dan ruangan wajib diisi.';
            $route = $jenis === 'seminar' ? 'admin/jadwalSeminarCreateForm' : 'admin/jadwalSidangCreateForm';
            header('Location: ' . BASE_URL . '/?r=' . $route);
            exit;
        }

        $conn = db();

        // Ambil data pengajuan untuk dapat mahasiswa & pembimbing
        $stmt = $conn->prepare("
            SELECT p.mahasiswa_id, p.pembimbing_id
            FROM pengajuan p
            WHERE p.id = ? AND p.jenis = ?
            LIMIT 1
        ");
        $stmt->bind_param('is', $pengajuan_id, $jenis);
        $stmt->execute();
        $result = $stmt->get_result();
        $peng   = $result->fetch_assoc();
        $stmt->close();

        if (!$peng) {
            $_SESSION['flash_error'] = 'Pengajuan tidak valid.';
            $route = $jenis === 'seminar' ? 'admin/jadwalSeminarCreateForm' : 'admin/jadwalSidangCreateForm';
            header('Location: ' . BASE_URL . '/?r=' . $route);
            exit;
        }

        $mahasiswa_id        = (int)$peng['mahasiswa_id'];
        $dosen_pembimbing_id = $peng['pembimbing_id'] ? (int)$peng['pembimbing_id'] : null;

        // Insert jadwal
        $stmtIns = $conn->prepare("
    INSERT INTO jadwal (
        jenis, pengajuan_id, mahasiswa_id,
        dosen_pembimbing_id, dosen_penguji_id, dosen_penguji_2_id,
        tanggal, jam_mulai, jam_selesai, ruangan, status, catatan
    )
    VALUES (
        ?, ?, ?, 
        ?, NULLIF(?,0), NULLIF(?,0),
        ?, ?, ?, ?, 'dijadwalkan', ?
    )
");
        $stmtIns->bind_param(
            'siiiissssss',
            $jenis,
            $pengajuan_id,
            $mahasiswa_id,
            $dosen_pembimbing_id,
            $dosen_penguji_id,
            $dosen_penguji_2_id,
            $tanggal,
            $jam_mulai,
            $jam_selesai,
            $ruangan,
            $catatan
        );
        $stmtIns->execute();
        $stmtIns->close();


        $_SESSION['flash_success'] = 'Jadwal ' . $jenis . ' berhasil dibuat.';

        $route = $jenis === 'seminar' ? 'admin/jadwalSeminarIndex' : 'admin/jadwalSidangIndex';
        header('Location: ' . BASE_URL . '/?r=' . $route);
        exit;
    }

    public function jadwalSeminarIndex(): void
    {
        $this->jadwalIndexInternal('seminar', 'Jadwal Seminar');
    }

    public function jadwalSidangIndex(): void
    {
        $this->jadwalIndexInternal('sidang', 'Jadwal Sidang');
    }

    public function jadwalSeminarCreateForm(): void
    {
        $this->jadwalCreateFormInternal('seminar', 'Tambah Jadwal Seminar');
    }

    public function jadwalSidangCreateForm(): void
    {
        $this->jadwalCreateFormInternal('sidang', 'Tambah Jadwal Sidang');
    }

    public function jadwalSeminarStore(): void
    {
        $this->jadwalStoreInternal('seminar');
    }

    public function jadwalSidangStore(): void
    {
        $this->jadwalStoreInternal('sidang');
    }

    private function jadwalEditFormInternal(string $jenis): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $route = $jenis === 'seminar' ? 'admin/jadwalSeminarIndex' : 'admin/jadwalSidangIndex';
            header('Location: ' . BASE_URL . '/?r=' . $route);
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            SELECT j.*,
                   p.judul,
                   m.nim,
                   m.nama AS nama_mhs,
                   pr.nama_prodi,
                   dp.nama AS nama_pembimbing
            FROM jadwal j
            JOIN pengajuan p ON j.pengajuan_id = p.id
            JOIN mahasiswa m ON j.mahasiswa_id = m.id
            JOIN prodi pr ON m.prodi_id = pr.id
            LEFT JOIN dosen dp ON j.dosen_pembimbing_id = dp.id
            WHERE j.id = ? AND j.jenis = ?
            LIMIT 1
        ");
        $stmt->bind_param('is', $id, $jenis);
        $stmt->execute();
        $jadwal = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$jadwal) {
            $route = $jenis === 'seminar' ? 'admin/jadwalSeminarIndex' : 'admin/jadwalSidangIndex';
            header('Location: ' . BASE_URL . '/?r=' . $route);
            exit;
        }

        $resultDosen = $conn->query("
            SELECT d.id, d.nama, pr.nama_prodi
            FROM dosen d
            JOIN prodi pr ON d.prodi_id = pr.id
            ORDER BY pr.nama_prodi ASC, d.nama ASC
        ");
        $dosenList = $resultDosen ? $resultDosen->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Edit Jadwal ' . ucfirst($jenis);
        $this->view('admin/jadwal/edit', compact('title', 'jenis', 'jadwal', 'dosenList'));
    }

    private function jadwalUpdateInternal(string $jenis): void
    {
        $this->requireRole(['admin']);

        $id                = (int)($_POST['id'] ?? 0);
        $tanggal           = $_POST['tanggal'] ?? '';
        $jam_mulai         = $_POST['jam_mulai'] ?? '';
        $jam_selesai       = $_POST['jam_selesai'] ?? '';
        $ruangan           = trim($_POST['ruangan'] ?? '');
        $dosen_penguji_id  = (int)($_POST['dosen_penguji_id'] ?? 0);
        $dosen_penguji_2_id = (int)($_POST['dosen_penguji_2_id'] ?? 0);
        $status            = $_POST['status'] ?? 'dijadwalkan';
        $catatan           = trim($_POST['catatan'] ?? '');

        $routeIndex = $jenis === 'seminar' ? 'admin/jadwalSeminarIndex' : 'admin/jadwalSidangIndex';

        if (
            $id <= 0 ||
            $tanggal === '' ||
            $jam_mulai === '' ||
            $ruangan === '' ||
            !in_array($status, ['dijadwalkan', 'dikonfirmasi', 'ditolak'], true)
        ) {
            $_SESSION['flash_error'] = 'Data jadwal tidak lengkap atau status tidak valid.';
            header('Location: ' . BASE_URL . '/?r=' . $routeIndex);
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            UPDATE jadwal
            SET tanggal = ?,
                jam_mulai = ?,
                jam_selesai = NULLIF(?, ''),
                ruangan = ?,
                dosen_penguji_id = NULLIF(?, 0),
                dosen_penguji_2_id = NULLIF(?, 0),
                status = ?,
                catatan = ?,
                updated_at = NOW()
            WHERE id = ? AND jenis = ?
        ");
        $stmt->bind_param(
            'ssssiissis',
            $tanggal,
            $jam_mulai,
            $jam_selesai,
            $ruangan,
            $dosen_penguji_id,
            $dosen_penguji_2_id,
            $status,
            $catatan,
            $id,
            $jenis
        );
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Jadwal berhasil diperbarui.';
        header('Location: ' . BASE_URL . '/?r=' . $routeIndex);
        exit;
    }

    // ===========================
    //  JADWAL: EDIT / UPDATE / DELETE (WRAPPER)
    // ===========================

    public function jadwalSeminarEditForm(): void
    {
        $this->jadwalEditFormInternal('seminar');
    }

    public function jadwalSidangEditForm(): void
    {
        $this->jadwalEditFormInternal('sidang');
    }

    public function jadwalSeminarUpdate(): void
    {
        $this->jadwalUpdateInternal('seminar');
    }

    public function jadwalSidangUpdate(): void
    {
        $this->jadwalUpdateInternal('sidang');
    }

    public function jadwalSeminarDelete(): void
    {
        $this->jadwalDeleteInternal('seminar');
    }

    public function jadwalSidangDelete(): void
    {
        $this->jadwalDeleteInternal('sidang');
    }


    /* ===========================
     *  LAPORAN AKHIR - ADMIN
     * =========================== */

    public function laporanAkhirIndex(): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $result = $conn->query("
            SELECT la.*, 
                   m.nim, m.nama AS nama_mhs, pr.nama_prodi,
                   p.judul AS judul_skripsi
            FROM laporan_akhir la
            JOIN mahasiswa m ON la.mahasiswa_id = m.id
            JOIN prodi pr ON m.prodi_id = pr.id
            JOIN pengajuan p ON la.pengajuan_id = p.id
            ORDER BY la.created_at DESC
        ");
        $items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Laporan Akhir Mahasiswa';
        $this->view('admin/laporan/index', compact('title', 'items'));
    }

    public function laporanAkhirDetail(): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/?r=admin/laporanAkhirIndex');
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            SELECT la.*, 
                   m.nim, m.nama AS nama_mhs, pr.nama_prodi,
                   p.judul AS judul_skripsi
            FROM laporan_akhir la
            JOIN mahasiswa m ON la.mahasiswa_id = m.id
            JOIN prodi pr ON m.prodi_id = pr.id
            JOIN pengajuan p ON la.pengajuan_id = p.id
            WHERE la.id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result  = $stmt->get_result();
        $laporan = $result->fetch_assoc();
        $stmt->close();

        if (!$laporan) {
            header('Location: ' . BASE_URL . '/?r=admin/laporanAkhirIndex');
            exit;
        }

        $title = 'Detail Laporan Akhir';
        $this->view('admin/laporan/detail', compact('title', 'laporan'));
    }

    public function laporanAkhirUpdateStatus(): void
    {
        $this->requireRole(['admin']);

        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'diajukan';
        $catatan_admin = trim($_POST['catatan_admin'] ?? '');

        if ($id <= 0 || !in_array($status, ['diajukan', 'diterima', 'ditolak'], true)) {
            header('Location: ' . BASE_URL . '/?r=admin/laporanAkhirIndex');
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
            UPDATE laporan_akhir
            SET status = ?, catatan_admin = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('ssi', $status, $catatan_admin, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Status laporan akhir berhasil diperbarui.';
        header('Location: ' . BASE_URL . '/?r=admin/laporanAkhirDetail&id=' . $id);
        exit;
    }

    public function laporanAkhirArsip(): void
    {
        $this->requireRole(['admin']);

        $conn = db();
        $result = $conn->query("
            SELECT la.*, 
                   m.nim, m.nama AS nama_mhs, pr.nama_prodi,
                   p.judul AS judul_skripsi
            FROM laporan_akhir la
            JOIN mahasiswa m ON la.mahasiswa_id = m.id
            JOIN prodi pr ON m.prodi_id = pr.id
            JOIN pengajuan p ON la.pengajuan_id = p.id
            WHERE la.status = 'diterima'
            ORDER BY la.created_at DESC
        ");
        $items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $title = 'Arsip Laporan Akhir';
        $this->view('admin/laporan/arsip', compact('title', 'items'));
    }

    public function suratPenetapanPembimbingPdf(): void
    {
        $this->requireRole(['admin']);

        $pengajuan_id = (int)($_GET['id'] ?? 0);
        if ($pengajuan_id <= 0) {
            echo "Pengajuan tidak valid.";
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare("
    SELECT p.*, 
           m.nim, m.nama AS nama_mhs, pr.nama_prodi,
           d.nama AS nama_pembimbing
    FROM pengajuan p
    JOIN mahasiswa m ON p.mahasiswa_id = m.id
    JOIN prodi pr ON m.prodi_id = pr.id
    LEFT JOIN dosen d ON p.pembimbing_id = d.id
    WHERE p.id = ?
    LIMIT 1
");

        $stmt->bind_param('i', $pengajuan_id);
        $stmt->execute();
        $result    = $stmt->get_result();
        $pengajuan = $result->fetch_assoc();
        $stmt->close();

        if (!$pengajuan) {
            echo "Pengajuan tidak ditemukan.";
            exit;
        }

        // Kalau tetap mau jaga-jaga jenis:
        if (isset($pengajuan['jenis']) && $pengajuan['jenis'] !== 'skripsi') {
            echo "Pengajuan ini bukan pengajuan skripsi.";
            exit;
        }


        if (empty($pengajuan['pembimbing_id'])) {
            echo "Dosen pembimbing belum ditetapkan untuk pengajuan ini.";
            exit;
        }

        require_once __DIR__ . '/../../config/pdf.php';

        $title = 'Surat Penetapan Pembimbing';
        ob_start();
        include __DIR__ . '/../view/pdf/surat_penetapan_pembimbing.php';
        $html = ob_get_clean();

        $nim = $pengajuan['nim'] ?? 'mhs';
        render_pdf($html, 'surat_penetapan_pembimbing_' . $nim . '.pdf', 'portrait', 'A4');
    }

    public function suratIzinPklPdf(): void
    {
        $this->requireRole(['admin']);

        $pengajuan_id = intval($_GET['id'] ?? 0);
        if ($pengajuan_id <= 0) {
            echo "Pengajuan tidak valid.";
            exit;
        }

        $conn = db();

        $stmt = $conn->prepare("
        SELECT p.*, 
               m.nama AS nama_mhs, m.nim, pr.nama_prodi
        FROM pengajuan p
        JOIN mahasiswa m ON p.mahasiswa_id = m.id
        JOIN prodi pr ON m.prodi_id = pr.id
        WHERE p.id = ?
          AND p.jenis = 'pkl'
        LIMIT 1
    ");
        $stmt->bind_param("i", $pengajuan_id);
        $stmt->execute();
        $pengajuan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pengajuan) {
            echo "Pengajuan PKL tidak ditemukan.";
            exit;
        }

        require_once __DIR__ . '/../../config/pdf.php';
        $title = "Surat Izin PKL";

        // =========================
        // 1) Mahasiswa List (tabel)
        // =========================
        $mahasiswaList = [[
            'nim'   => $pengajuan['nim'] ?? '',
            'nama'  => $pengajuan['nama_mhs'] ?? '',
            'prodi' => $pengajuan['nama_prodi'] ?? '',
        ]];

        // =========================
        // 2) Helper: prodi kode 3 huruf
        // =========================
        $prodiNama = $pengajuan['nama_prodi'] ?? '';
        $prodi_kode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $prodiNama), 0, 3));
        if ($prodi_kode === '') $prodi_kode = 'IF'; // fallback

        // =========================
        // 3) Bulan romawi + tanggal surat
        // =========================
        $bulan = (int)date('n');
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $bulan_romawi = $romawi[$bulan] ?? 'I';

        // =========================
        // 4) Periode PKL (mulai - selesai)
        //    Jika di DB ada tanggal_mulai/tanggal_selesai, pakai itu.
        //    Kalau belum ada, fallback contoh yang kamu minta.
        // =========================
        $tanggal_mulai   = !empty($pengajuan['tanggal_mulai']) ? $pengajuan['tanggal_mulai'] : '1 Desember 2025';
        $tanggal_selesai = !empty($pengajuan['tanggal_selesai']) ? $pengajuan['tanggal_selesai'] : '30 Januari 2026';

        // =========================
        // 5) Nomor urut 3 digit (sementara)
        //    Idealnya diambil dari tabel surat + aturan reset per tahun.
        // =========================
        $no_urut = $pengajuan['no_urut_surat'] ?? '001';

        // =========================
        // 6) Data TTD (sementara hardcode / dari tabel setting)
        // =========================
        $surat = [
            'no_urut'        => $no_urut,
            'prodi_kode'     => $prodi_kode,
            'bulan_romawi'   => $bulan_romawi,
            'tahun'          => date('Y'),
            'tanggal_surat'  => date('d F Y'),
            'kota'           => 'Bandung',

            'instansi_nama'  => $pengajuan['instansi_nama'] ?? 'Pimpinan Instansi/Perusahaan',
            'instansi_alamat' => $pengajuan['instansi_alamat'] ?? '',
            'instansi_kota'  => $pengajuan['instansi_kota'] ?? '',

            'prodi_nama'     => $prodiNama,
            'tahun_akademik' => '2025/2026',
            'tanggal_mulai'  => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,

            // TTD (isi sesuai data kampus kamu)
            'kaprodi_nama'   => '................................',
            'kaprodi_nip'    => '................................',
            'direktur_nama'  => '................................',
            'direktur_nip'   => '................................',
        ];

        ob_start();
        include __DIR__ . '/../view/pdf/surat_izin_pkl.php';
        $html = ob_get_clean();

        $nim = $pengajuan['nim'];
        render_pdf($html, "surat_izin_pkl_{$nim}.pdf");
    }


    private function jadwalDeleteInternal(string $jenis): void
    {
        $this->requireRole(['admin']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo "Jadwal tidak valid.";
            exit;
        }

        $conn = db();

        $stmt = $conn->prepare("DELETE FROM jadwal WHERE id = ? AND jenis = ?");
        $stmt->bind_param('is', $id, $jenis);
        $stmt->execute();
        $stmt->close();

        if ($jenis === 'seminar') {
            header('Location: ' . BASE_URL . '/?r=admin/jadwalSeminarIndex');
        } else {
            header('Location: ' . BASE_URL . '/?r=admin/jadwalSidangIndex');
        }
        exit;
    }

    /* ====================================================
     *  PROFIL ADMIN (LIHAT & UPDATE DATA DIRI)
     * ==================================================== */

    public function profilAdmin(): void
    {
        $this->requireRole(['admin']);

        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $conn   = db();

        $stmt = $conn->prepare("SELECT id, username, nama, role, status FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin  = $result->fetch_assoc();
        $stmt->close();

        if (!$admin) {
            echo "Data admin tidak ditemukan.";
            exit;
        }

        $title = 'Profil Admin';
        $this->view('admin/profil/index', compact('title', 'admin'));
    }

    public function profilAdminUpdate(): void
    {
        $this->requireRole(['admin']);

        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        $userId   = (int)$_SESSION['user']['id'];
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');

        if ($username === '' || $nama === '') {
            $_SESSION['flash_error'] = 'Username dan nama wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=admin/profilAdmin');
            exit;
        }

        $conn = db();

        // Cek apakah username sudah dipakai user lain
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
        $stmt->bind_param('si', $username, $userId);
        $stmt->execute();
        $dup = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($dup) {
            $_SESSION['flash_error'] = 'Username sudah digunakan oleh pengguna lain.';
            header('Location: ' . BASE_URL . '/?r=admin/profilAdmin');
            exit;
        }

        // Update users
        $stmt = $conn->prepare("UPDATE users SET username = ?, nama = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('ssi', $username, $nama, $userId);
        $stmt->execute();
        $stmt->close();

        // Update juga session
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['nama']     = $nama;

        $_SESSION['flash_success'] = 'Profil admin berhasil diperbarui.';
        header('Location: ' . BASE_URL . '/?r=admin/profilAdmin');
        exit;
    }

    /* ====================================================
     *  GANTI PASSWORD ADMIN
     * ==================================================== */

    public function gantiPasswordAdminForm(): void
    {
        $this->requireRole(['admin']);

        $title = 'Ganti Password Admin';
        $this->view('admin/profil/password', compact('title'));
    }

    public function gantiPasswordAdminUpdate(): void
    {
        $this->requireRole(['admin']);

        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        $userId        = (int)$_SESSION['user']['id'];
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $password_konf = $_POST['password_konfirmasi'] ?? '';

        if ($password_lama === '' || $password_baru === '' || $password_konf === '') {
            $_SESSION['flash_error'] = 'Semua field password wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=admin/gantiPasswordAdminForm');
            exit;
        }

        if ($password_baru !== $password_konf) {
            $_SESSION['flash_error'] = 'Konfirmasi password baru tidak cocok.';
            header('Location: ' . BASE_URL . '/?r=admin/gantiPasswordAdminForm');
            exit;
        }

        if (strlen($password_baru) < 6) {
            $_SESSION['flash_error'] = 'Password baru minimal 6 karakter.';
            header('Location: ' . BASE_URL . '/?r=admin/gantiPasswordAdminForm');
            exit;
        }

        $conn = db();

        // Ambil password lama
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $_SESSION['flash_error'] = 'User admin tidak ditemukan.';
            header('Location: ' . BASE_URL . '/?r=admin/gantiPasswordAdminForm');
            exit;
        }

        if (!password_verify($password_lama, $row['password'])) {
            $_SESSION['flash_error'] = 'Password lama salah.';
            header('Location: ' . BASE_URL . '/?r=admin/gantiPasswordAdminForm');
            exit;
        }

        // Update password baru
        $hashBaru = password_hash($password_baru, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $hashBaru, $userId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Password berhasil diubah.';
        header('Location: ' . BASE_URL . '/?r=admin/gantiPasswordAdminForm');
        exit;
    }

    
}
