<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Log PKL'); ?></title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
        }
        h1, h2, h3 {
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .info {
            margin-top: 4px;
            margin-bottom: 8px;
        }
        .info-table {
            font-size: 11px;
            width: 100%;
        }
        .info-table td {
            padding: 2px 4px;
        }
        table.log {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.log th,
        table.log td {
            border: 1px solid #9ca3af;
            padding: 4px 6px;
        }
        table.log th {
            background-color: #f3f4f6;
            font-size: 10px;
            text-align: center;
        }
        table.log td {
            font-size: 10px;
            vertical-align: top;
        }
        .text-center {
            text-align: center;
        }
        .mt-24 {
            margin-top: 24px;
        }
        table.signature {
            width: 100%;
            font-size: 11px;
        }
        table.signature td {
            vertical-align: top;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>LOG HARIAN PRAKTIK KERJA LAPANGAN (PKL)</h1>
    <p style="margin-top:4px;">Sistem Informasi Pengelolaan PKL & Skripsi (SIPKS)</p>
</div>

<div class="info">
    <table class="info-table">
        <tr>
            <td style="width:18%;">Nama</td>
            <td style="width:2%;">:</td>
            <td><?= htmlspecialchars($mhs['nama'] ?? ''); ?></td>
        </tr>
        <tr>
            <td>NIM</td>
            <td>:</td>
            <td><?= htmlspecialchars($mhs['nim'] ?? ''); ?></td>
        </tr>
        <?php if (!empty($mhs['nama_prodi'])): ?>
        <tr>
            <td>Program Studi</td>
            <td>:</td>
            <td><?= htmlspecialchars($mhs['nama_prodi']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($mhs['instansi_pkl'])): ?>
        <tr>
            <td>Instansi PKL</td>
            <td>:</td>
            <td><?= htmlspecialchars($mhs['instansi_pkl']); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<table class="log">
    <thead>
    <tr>
        <th style="width:5%;">No</th>
        <th style="width:12%;">Tanggal</th>
        <th style="width:18%;">Lokasi</th>
        <th>Kegiatan</th>
        <th style="width:15%;">Waktu</th>
        <th style="width:20%;">Output / Hasil</th>
        <th style="width:10%;">Paraf</th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
        <tr>
            <td colspan="7" class="text-center">Belum ada data log PKL.</td>
        </tr>
    <?php else: ?>
        <?php $no = 1; ?>
        <?php foreach ($items as $row): ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['tanggal']); ?></td>
                <td><?= htmlspecialchars($row['lokasi'] ?? '-'); ?></td>
                <td><?= nl2br(htmlspecialchars($row['kegiatan'])); ?></td>
                <td>
                    <?php
                    $jm = $row['jam_mulai'] ?? '';
                    $js = $row['jam_selesai'] ?? '';
                    echo ($jm || $js) ? htmlspecialchars($jm . ($js ? ' - ' . $js : '')) : '-';
                    ?>
                </td>
                <td><?= nl2br(htmlspecialchars($row['output'] ?? '-')); ?></td>
                <!-- Kolom paraf pembimbing (kosong, diisi manual setelah dicetak) -->
                <td style="height:35px;"></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<table class="signature mt-24">
    <tr>
        <td style="width:50%;">
            <div>
                <p>Pembimbing Lapangan,</p>
                <br><br><br>
                <p><u>................................</u></p>
                <p>NIP / No. Induk: ........................</p>
            </div>
        </td>
        <td style="width:50%;">
            <div>
                <p>.................., <?= date('d-m-Y'); ?></p>
                <p>Mahasiswa,</p>
                <br><br><br>
                <p><u><?= htmlspecialchars($mhs['nama'] ?? ''); ?></u></p>
                <p>NIM: <?= htmlspecialchars($mhs['nim'] ?? ''); ?></p>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
