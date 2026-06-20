<?php
// ======================
// GUARD / FALLBACK DATA
// ======================
$surat = $surat ?? [];
$pengajuan = $pengajuan ?? [];
$mahasiswaList = $mahasiswaList ?? [];

// fallback: 1 mahasiswa dari pengajuan skripsi
if (empty($mahasiswaList) && !empty($pengajuan)) {
  $mahasiswaList = [[
    'nim'   => $pengajuan['nim'] ?? '',
    'nama'  => $pengajuan['nama_mhs'] ?? '',
    'prodi' => $pengajuan['nama_prodi'] ?? '',
  ]];
}

if (!is_array($mahasiswaList)) {
  $mahasiswaList = [];
}

function e($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function pad3($n): string {
  return str_pad((string)(int)$n, 3, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= e($title ?? 'Surat Izin Penelitian Skripsi'); ?></title>
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:11px; line-height:1.5; }
.kop { text-align:center; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:12px; }
.kop .uni { font-size:16px; font-weight:700; text-transform:uppercase; margin:0; }
.kop .alamat { font-size:9.5px; margin:2px 0; }
.meta td { padding:1px 3px; vertical-align:top; }
.tanggal { text-align:right; }
.isi p { margin:8px 0; text-align:justify; }
table.mhs { width:100%; border-collapse:collapse; margin:10px 0; }
table.mhs th, table.mhs td { border:1px solid #000; padding:5px; font-size:10px; }
.ttd { margin-top:40px; }
.nama { margin-top:60px; font-weight:700; text-decoration:underline; }
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

<!-- META -->
<table width="100%">
<tr>
<td width="60%">
  <table class="meta">
    <tr>
      <td>No</td><td>:</td>
      <td>
        <?php
          $no = $surat['no_urut'] ?? 1;
          $kode = $surat['prodi_kode'] ?? 'IF';
          $bulan = $surat['bulan_romawi'] ?? 'I';
          $tahun = $surat['tahun'] ?? date('Y');
          echo e(pad3($no)."/$kode/FSTUI/$bulan/$tahun");
        ?>
      </td>
    </tr>
    <tr>
      <td>Lampiran</td><td>:</td>
      <td><?= e($surat['lampiran'] ?? '-'); ?></td>
    </tr>
    <tr>
      <td>Perihal</td><td>:</td>
      <td><strong><?= e($surat['perihal'] ?? 'Izin Penelitian Skripsi'); ?></strong></td>
    </tr>
  </table>
</td>
<td width="40%" class="tanggal">
  <?= e($surat['kota'] ?? 'Bandung'); ?>, <?= e($surat['tanggal_surat'] ?? date('d F Y')); ?>
</td>
</tr>
</table>

<!-- TUJUAN -->
<p>
Kepada Yth.<br>
<strong><?= e($surat['instansi_nama'] ?? 'Pimpinan Instansi/Perusahaan'); ?></strong><br>
<?= nl2br(e($surat['instansi_alamat'] ?? '')); ?><br>
<?= e($surat['instansi_kota'] ?? ''); ?><br>
di Tempat
</p>

<!-- ISI -->
<div class="isi">
<p>Dengan hormat,</p>

<p>
Sehubungan dengan pelaksanaan penelitian <strong>Skripsi</strong> pada
Universitas Wanita Internasional, dengan ini kami mengajukan permohonan
kepada Instansi/Perusahaan yang Bapak/Ibu pimpin agar dapat memberikan izin
kepada mahasiswa berikut untuk melaksanakan penelitian:
</p>

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
<tr><td colspan="4" align="center">(Data mahasiswa belum tersedia)</td></tr>
<?php else: ?>
<?php $i=1; foreach ($mahasiswaList as $m): ?>
<tr>
  <td align="center"><?= $i++; ?></td>
  <td><?= e($m['nim']); ?></td>
  <td><?= e($m['nama']); ?></td>
  <td><?= e($m['prodi']); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

<p>
Penelitian skripsi tersebut akan dilaksanakan pada periode
<strong><?= e($surat['tanggal_mulai'] ?? '-'); ?></strong>
s.d.
<strong><?= e($surat['tanggal_selesai'] ?? '-'); ?></strong>,
dengan judul penelitian:
</p>

<p><strong><?= e($pengajuan['judul'] ?? '-'); ?></strong></p>

<p>
Adapun waktu dan teknis pelaksanaan penelitian sepenuhnya disesuaikan
dengan kebijakan Instansi/Perusahaan yang bersangkutan.
</p>

<p>
Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerja sama
Bapak/Ibu, kami ucapkan terima kasih.
</p>
</div>

<!-- TTD -->
<table class="ttd" width="100%">
<tr>
<td width="50%">
Ketua Program Studi <?= e($surat['prodi_nama'] ?? ''); ?>
<div class="nama"><?= e($surat['kaprodi_nama'] ?? '........................'); ?></div>
NIP. <?= e($surat['kaprodi_nip'] ?? '........................'); ?>
</td>
<td width="50%" align="center">
Direktur Akademik, Administrasi Umum dan Kemahasiswaan
<div class="nama"><?= e($surat['direktur_nama'] ?? '........................'); ?></div>
NIP. <?= e($surat['direktur_nip'] ?? '........................'); ?>
</td>
</tr>
</table>

</body>
</html>
