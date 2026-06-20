<?php include __DIR__ . '/../../partials/header.php'; ?>

<?php
$labelJenis = [
    'pkl'     => 'PKL',
    'skripsi' => 'Skripsi / Tugas Akhir',
    'seminar' => 'Seminar',
    'sidang'  => 'Sidang',
];
$jenisLabel = $labelJenis[$jenis] ?? strtoupper($jenis);

$storeRouteMap = [
    'pkl'     => 'mahasiswa/pengajuanPklStore',
    'skripsi' => 'mahasiswa/pengajuanSkripsiStore',
    'seminar' => 'mahasiswa/pengajuanSeminarStore',
    'sidang'  => 'mahasiswa/pengajuanSidangStore',
];
$storeRoute = $storeRouteMap[$jenis] ?? 'mahasiswa/pengajuanPklStore';

/**
 * Label lampiran sesuai revisi demo:
 * - Skripsi/TA: minimal KRS aktif (SS KRS sem 7/8)
 * - PKL: opsional (kalau kampus tidak mewajibkan surat/keterangan)
 * - Seminar/Sidang: biasanya butuh bukti/berkas (biar fleksibel)
 */
$lampiranLabelMap = [
    'skripsi' => 'Upload KRS Aktif (Semester 7/8) - PDF',
    'pkl'     => 'Lampiran (opsional) - PDF',
    'seminar' => 'Lampiran Persyaratan Seminar - PDF',
    'sidang'  => 'Lampiran Persyaratan Sidang - PDF',
];
$lampiranLabel = $lampiranLabelMap[$jenis] ?? 'Lampiran (PDF)';

$lampiranHintMap = [
    'skripsi' => 'Unggah KRS aktif (misal: SS KRS Semester 7/8) dalam format PDF. Maks 5 MB.',
    'pkl'     => 'Jika ada berkas pendukung (opsional) unggah PDF. Maks 5 MB.',
    'seminar' => 'Unggah berkas persyaratan seminar (PDF). Maks 5 MB.',
    'sidang'  => 'Unggah berkas persyaratan sidang (PDF). Maks 5 MB.',
];
$lampiranHint = $lampiranHintMap[$jenis] ?? 'Format PDF, maks 5 MB.';
?>

<main class="flex-1 pb-16 md:pb-0 bg-gradient-to-b from-slate-100 to-white min-h-screen">
    <div class="max-w-md mx-auto px-4 py-6 md:py-10">

        <!-- Header -->
        <div class="mb-5 text-center">
            <h1 class="text-lg md:text-xl font-semibold text-slate-800 flex items-center justify-center gap-2">
                Pengajuan <?= htmlspecialchars($jenisLabel); ?>
            </h1>

            <p class="text-xs md:text-sm text-slate-500">
                Lengkapi form di bawah, lalu unggah lampiran sesuai kebutuhan.
            </p>

            <?php if ($jenis === 'pkl'): ?>
                <div class="mt-3 text-[11px] md:text-xs text-amber-700 bg-amber-50 border border-amber-100 px-3 py-2 rounded-lg">
                    Catatan: Periode pengajuan PKL dibuka pada <strong>Agustus–Oktober</strong> (Semester Ganjil).
                </div>
            <?php endif; ?>
        </div>

        <!-- Flash message -->
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="mb-4 text-xs md:text-sm text-emerald-700 bg-emerald-50 border border-emerald-100 px-3 py-2 rounded-lg">
                <?= htmlspecialchars($_SESSION['flash_success']); ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="mb-4 text-xs md:text-sm text-red-700 bg-red-50 border border-red-100 px-3 py-2 rounded-lg">
                <?= htmlspecialchars($_SESSION['flash_error']); ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- FORM UTAMA -->
        <form
            action="<?= BASE_URL; ?>/?r=<?= htmlspecialchars($storeRoute); ?>"
            method="post"
            enctype="multipart/form-data"
            class="bg-white shadow-md rounded-2xl p-4 md:p-6 space-y-4">

            <?php if ($jenis === 'pkl'): ?>
                <!-- ================= PKL ================= -->
                <h2 class="text-sm font-semibold text-fuchsia-600 text-center mb-3">
                    Pengajuan Praktik Kerja Lapangan (PKL)
                </h2>

                <div>
                    <label class="block text-xs font-semibold mb-1">Nama / Tempat Instansi</label>
                    <input type="text" name="instansi_nama" required
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Alamat Instansi</label>
                    <textarea name="instansi_alamat" rows="2" required
                        class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" required
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" required
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

            <?php elseif ($jenis === 'skripsi'): ?>
                <!-- ================= SKRIPSI ================= -->


                <div>
                    <label class="block text-xs font-semibold mb-1">Judul Skripsi (Final)</label>
                    <input type="text" name="judul" required
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Nama / Tempat Instansi</label>
                    <input type="text" name="instansi_nama" required
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Alamat Instansi</label>
                    <textarea name="instansi_alamat" rows="2" required
                        class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                </div>


            <?php elseif ($jenis === 'seminar'): ?>
                <!-- ================= SEMINAR ================= -->
                <h2 class="text-sm font-semibold text-fuchsia-600 text-center mb-3">
                    Pengajuan Seminar
                </h2>

                <div class="text-xs bg-slate-50 border rounded p-3">
                    <strong>Persyaratan Seminar:</strong>
                    <ul class="list-disc ml-4 mt-1">
                        <li>Scan KTP</li>
                        <li>Kartu Bimbingan (min. 6x)</li>
                        <li>Sertifikat keikutsertaan</li>
                        <li>Lembar Pengesahan (TTD Pembimbing)</li>
                        <li>Draft Skripsi (link Drive sesuai prodi)</li>
                        <li>Scan KRS Aktif</li>
                        <li>Bukti Lunas Perkuliahan</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">
                        Upload Berkas Persyaratan (PDF)
                    </label>
                    <input type="file" name="lampiran" accept="application/pdf" required
                        class="w-full text-sm">
                </div>

            <?php elseif ($jenis === 'sidang'): ?>
                <!-- ================= SIDANG ================= -->
                <h2 class="text-sm font-semibold text-fuchsia-600 text-center mb-3">
                    Pengajuan Sidang Skripsi
                </h2>

                <div class="text-xs bg-slate-50 border rounded p-3">
                    <strong>Persyaratan Sidang:</strong>
                    <ul class="list-disc ml-4 mt-1">
                        <li>Scan KTP</li>
                        <li>Kartu Bimbingan</li>
                        <li>Draft Skripsi BAB I–V</li>
                        <li>Matriks Seminar</li>
                        <li>Form Permohonan Sidang</li>
                        <li>Lembar Persetujuan Pembimbing</li>
                        <li>Transkrip Nilai</li>
                        <li>Bukti Lunas Perkuliahan & Wisuda</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">
                        Upload Berkas Persyaratan (PDF)
                    </label>
                    <input type="file" name="lampiran" accept="application/pdf" required
                        class="w-full text-sm">
                </div>
            <?php endif; ?>

            <!-- BUTTON -->
            <div class="pt-4 flex gap-2">
                <button type="submit"
                    class="flex-1 bg-fuchsia-600 text-white py-2 rounded-lg font-semibold">
                    Ajukan
                </button>
                <a href="<?= BASE_URL; ?>/?r=mahasiswa/dashboard"
                    class="flex-1 bg-gray-700 text-white py-2 rounded-lg text-center">
                    Kembali
                </a>
            </div>
        </form>


    </div>

    <?php include __DIR__ . '/../../partials/mahasiswa/bottom-nav.php'; ?>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>