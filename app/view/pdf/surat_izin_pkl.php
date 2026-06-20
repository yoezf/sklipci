<?php
// ======================
// GUARD / FALLBACK DATA
// ======================
$surat = $surat ?? [];
$pengajuan = $pengajuan ?? [];
$mahasiswaList = $mahasiswaList ?? [];

// Kalau mahasiswaList kosong tapi ada $pengajuan (single mahasiswa), bentuk jadi array
if (empty($mahasiswaList) && !empty($pengajuan)) {
  $mahasiswaList = [[
    'nim'   => $pengajuan['nim'] ?? '',
    'nama'  => $pengajuan['nama_mhs'] ?? '',
    'prodi' => $pengajuan['nama_prodi'] ?? ($surat['prodi_nama'] ?? ''),
  ]];
}

// Pastikan mahasiswaList selalu array
if (!is_array($mahasiswaList)) {
  $mahasiswaList = [];
}

// Helper aman: null -> ''
function e($val): string
{
  return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

// Optional: helper format no surat (3 digit)
function pad3($n): string
{
  $n = (int)($n ?? 0);
  return str_pad((string)$n, 3, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= e($title ?? 'Surat Pengantar PKL'); ?></title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; line-height: 1.5; color:#000; }
    .kop { text-align:center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
    .kop .uni { font-size: 16px; font-weight: 700; text-transform: uppercase; margin:0; }
    .kop .alamat { font-size: 9.5px; margin: 2px 0; }
    .meta { width:100%; }
    .meta td { padding: 1px 3px; vertical-align: top; }
    .label { width: 80px; }
    .tanggal-kanan { text-align:right; vertical-align: top; }
    .isi p { margin: 8px 0; text-align: justify; }
    table.mhs { width:100%; border-collapse: collapse; margin: 10px 0; }
    table.mhs th, table.mhs td { border:1px solid #000; padding: 5px; font-size:10px; }
    table.mhs th { text-align:center; }
    .ttd { margin-top: 40px; }
    .nama { margin-top: 60px; font-weight:700; text-decoration: underline; }
  </style>
</head>
<body>

  <!-- KOP -->
  <div class="kop">
    <p class="uni">UNIVERSITAS WANITA INTERNASIONAL</p>
    <p class="alamat">
      Kampus I: Jl. Pasir Kaliki No.179A Bandung 40163<br>
      Kampus II: Jl. Katapang Andir Km-4 Kab. Bandung 40921
    </p>
  </div>

  <!-- HEADER META + TANGGAL -->
  <table width="100%">
    <tr>
      <td width="60%">
        <table class="meta">
          <tr>
            <td class="label">No</td><td>:</td>
            <td>
              <?php
                // Format: 001/IF/FSTUI/XI/2026
                $noUrut      = $surat['no_urut'] ?? '001';
                $prodiKode   = $surat['prodi_kode'] ?? '';
                $bulanRomawi = $surat['bulan_romawi'] ?? '';
                $tahunSurat  = $surat['tahun'] ?? date('Y');

                // kalau no_urut angka, pad jadi 3 digit; kalau sudah string "001" biarkan
                $noUrutFix = is_numeric($noUrut) ? pad3($noUrut) : (string)$noUrut;

                echo e($noUrutFix) . '/' . e($prodiKode ?: 'IF') . '/FSTUI/' . e($bulanRomawi ?: 'I') . '/' . e($tahunSurat);
              ?>
            </td>
          </tr>
          <tr>
            <td class="label">Lampiran</td><td>:</td>
            <td><?= e($surat['lampiran'] ?? '-'); ?></td>
          </tr>
          <tr>
            <td class="label">Perihal</td><td>:</td>
            <td><strong><?= e($surat['perihal'] ?? 'Pengantar Praktek Kerja Lapangan (PKL)'); ?></strong></td>
          </tr>
        </table>
      </td>
      <td width="40%" class="tanggal-kanan">
        <?= e($surat['kota'] ?? 'Bandung'); ?>, <?= e($surat['tanggal_surat'] ?? date('d F Y')); ?>
      </td>
    </tr>
  </table>

  <!-- TUJUAN -->
  <p style="margin-top:10px;">
    Kepada Yth.<br>
    <strong><?= e($surat['instansi_nama'] ?? 'Pimpinan Instansi/Perusahaan'); ?></strong><br>
    <?php if (!empty($surat['instansi_alamat'])): ?>
      <?= nl2br(e($surat['instansi_alamat'])); ?><br>
    <?php endif; ?>
    <?php if (!empty($surat['instansi_kota'])): ?>
      <?= e($surat['instansi_kota']); ?><br>
    <?php endif; ?>
    di Tempat
  </p>

  <!-- ISI -->
  <div class="isi">
    <p>Dengan hormat,</p>

    <p>
      Sehubungan dengan pelaksanaan mata kuliah <strong>Praktek Kerja Lapangan (PKL)</strong>,
      bersama ini kami mengajukan permohonan kepada Instansi/Perusahaan yang Bapak/Ibu pimpin
      agar berkenan menerima mahasiswa berikut untuk melaksanakan PKL:
    </p>

    <!-- TABEL MAHASISWA (AMAN) -->
    <table class="mhs">
      <thead>
        <tr>
          <th width="5%">No</th>
          <th width="20%">NIM</th>
          <th>Nama</th>
          <th width="25%">Program Studi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($mahasiswaList)): ?>
          <tr>
            <td colspan="4" style="text-align:center;">(Data mahasiswa belum tersedia)</td>
          </tr>
        <?php else: ?>
          <?php $no = 1; foreach ($mahasiswaList as $m): ?>
            <tr>
              <td align="center"><?= $no++; ?></td>
              <td><?= e($m['nim'] ?? ''); ?></td>
              <td><?= e($m['nama'] ?? ''); ?></td>
              <td><?= e($m['prodi'] ?? ($surat['prodi_nama'] ?? '')); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <p>
      Pelaksanaan Praktek Kerja Lapangan tersebut akan dilaksanakan pada
      <strong>Semester Ganjil Tahun Akademik <?= e($surat['tahun_akademik'] ?? '2025/2026'); ?></strong>,
      yang berlangsung pada periode
      <strong><?= e($surat['tanggal_mulai'] ?? '1 Desember 2025'); ?></strong>
      s.d.
      <strong><?= e($surat['tanggal_selesai'] ?? '30 Januari 2026'); ?></strong>.
    </p>

    <p>
      Adapun waktu dan teknis pelaksanaan selanjutnya akan disesuaikan dengan kebijakan
      Instansi/Perusahaan yang bersangkutan.
    </p>

    <p>
      Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu,
      kami ucapkan terima kasih.
    </p>
  </div>

  <!-- TTD -->
  <table class="ttd" width="100%">
    <tr>
      <td width="50%">
        Ketua Program Studi <?= e($surat['prodi_nama'] ?? ''); ?><br>
        <div class="nama"><?= e($surat['kaprodi_nama'] ?? '................................'); ?></div>
        NIP. <?= e($surat['kaprodi_nip'] ?? '................................'); ?>
      </td>
      <td width="50%" align="center">
        Direktur Akademik, Administrasi Umum dan Kemahasiswaan<br>
        <div class="nama"><?= e($surat['direktur_nama'] ?? '................................'); ?></div>
        NIP. <?= e($surat['direktur_nip'] ?? '................................'); ?>
      </td>
    </tr>
  </table>

</body>
</html>
