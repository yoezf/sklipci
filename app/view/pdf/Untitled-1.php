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
$storeRoute = $storeRouteMap[$jenis];
?>

<main class="flex-1 bg-slate-100 min-h-screen py-8">
    <div class="max-w-md mx-auto px-4">

        <form action="<?= BASE_URL ?>/?r=<?= $storeRoute ?>"
            method="post"
            enctype="multipart/form-data"
            class="bg-white shadow rounded-2xl p-5 space-y-4">

            <h1 class="text-center text-lg font-semibold text-pink-600">
                Pengajuan <?= htmlspecialchars($jenisLabel) ?>
            </h1>

            <!-- ================= PKL ================= -->
            <?php if ($jenis === 'pkl'): ?>

                <input type="hidden" name="jenis" value="pkl">

                <div>
                    <label class="text-xs font-semibold">Nama Instansi</label>
                    <input type="text" name="instansi_nama" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-xs font-semibold">Alamat Instansi</label>
                    <textarea name="instansi_alamat" rows="3" required class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" required class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="text-xs font-semibold">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" required class="w-full border rounded px-3 py-2">
                    </div>
                </div>

            <?php endif; ?>

            <!-- ================= SKRIPSI ================= -->
            <?php if ($jenis === 'skripsi'): ?>

                <input type="hidden" name="jenis" value="skripsi">

                <div>
                    <label class="text-xs font-semibold">Judul Skripsi (Final)</label>
                    <input type="text" name="judul" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-xs font-semibold">Instansi Penelitian</label>
                    <input type="text" name="instansi_nama" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-xs font-semibold">Alamat Instansi</label>
                    <textarea name="instansi_alamat" rows="3" required class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" required class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="text-xs font-semibold">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" required class="w-full border rounded px-3 py-2">
                    </div>
                </div>

            <?php endif; ?>

            <!-- ================= SEMINAR ================= -->
            <?php if ($jenis === 'seminar'): ?>

                <input type="hidden" name="jenis" value="seminar">

                <div class="text-xs bg-slate-50 border rounded p-3">
                    <strong>Persyaratan Seminar:</strong>

                    <ul class="list-disc ml-4 mt-1">
                        <li>Scan KTP</li>
                        <li>Kartu Bimbingan (min. 6x)</li>
                        <li>Sertifikat keikutsertaan</li>
                        <li>Lembar Pengesahan (TTD Pembimbing)</li>

                        <li>
                            Draft Skripsi (berupa link Drive )
                            <ul class="list-disc ml-5 mt-1">
                                <li>BAB 1–3 MIPA & Desain Interior</li>
                                <li>BAB 1–4 DKV</li>
                                <li>BAB 1–3 Informatika</li>
                            </ul>
                        </li>

                        <li>Scan KRS Aktif</li>
                        <li>Bukti Lunas Selama Perkuliahan</li>
                    </ul>
                </div>


                <div>
                    <label class="text-xs font-semibold">Upload Berkas (PDF)</label>
                    <input type="file" name="lampiran" accept="application/pdf" required
                        class="w-full text-xs">
                </div>

            <?php endif; ?>

            <!-- ================= SIDANG ================= -->
            <?php if ($jenis === 'sidang'): ?>

                <input type="hidden" name="jenis" value="sidang">

                <div class="text-xs bg-slate-50 border rounded p-3">
                    <strong>Persyaratan Sidang:</strong>

                    <ul class="list-disc ml-4 mt-1">
                        <li>Scan KTP</li>
                        <li>Kartu Bimbingan</li>
                        <li>Sertifikat keikutsertaan</li>
                        <li>Draft BAB 1-5 ( berupa link drive )</li>
                        <li>Scan matriks seminar</li>
                        <li>Scan Formulir permohonan Sidang</li>
                        <li>Scan lembar persetujuan Pembimbing</li>
                        <li>Scan Transkrip pembimbing</li>
                        <li>Lembar Pengesahan (TTD Pembimbing)</li>
                        <li>Scan KRS Aktif</li>
                        <li>Bukti Lunas Selama Perkuliahan & wisuda</li>
                    </ul>
                </div>


                <div>
                    <label class="text-xs font-semibold">Upload Berkas (PDF)</label>
                    <input type="file" name="lampiran" accept="application/pdf" required
                        class="w-full text-xs">
                </div>

            <?php endif; ?>

            <!-- ================= BUTTON ================= -->
            <div class="pt-3 flex gap-2">
                <button type="submit"
                    class="flex-1 bg-pink-600 text-white py-2 rounded font-semibold">
                    Ajukan
                </button>
                <a href="<?= BASE_URL ?>/?r=mahasiswa/dashboard"
                    class="flex-1 bg-gray-700 text-white py-2 rounded text-center font-semibold">
                    Kembali
                </a>
            </div>

        </form>
    </div>
    <?php include __DIR__ . '/../../partials/mahasiswa/bottom-nav.php'; ?>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>