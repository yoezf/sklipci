<?php include __DIR__ . '/../../partials/header.php'; ?>

<main class="flex-1 pb-16 md:pb-0 bg-slate-100">
    <div class="max-w-3xl mx-auto px-4 py-6">

        <!-- Judul Halaman -->
        <div class="mb-4">
            <h1 class="text-2xl font-semibold text-slate-800">Tambah Log PKL</h1>
            <p class="text-sm text-slate-500 mt-1">
                <?= htmlspecialchars($mhs['nim'] ?? ''); ?> – <?= htmlspecialchars($mhs['nama'] ?? ''); ?>
            </p>
        </div>

        <!-- Flash Error -->
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                <?= htmlspecialchars($_SESSION['flash_error']); ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Card Form -->
        <div class="bg-white shadow-sm rounded-lg p-5 border border-slate-200">
            <a href="<?= BASE_URL; ?>/?r=mahasiswa/logPklIndex"
                class="inline-flex items-center text-sm text-[#DB2777] bg-pink-50 hover:bg-pink-100 px-3 py-1.5 rounded-md transition mb-4">
                ← Kembali ke Log PKL
            </a>



            <form action="<?= BASE_URL; ?>/?r=mahasiswa/logPklStore" method="post" class="space-y-5">

                <!-- Tanggal -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                    <input
                        type="date"
                        name="tanggal"
                        class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400"
                        required>
                </div>

                <!-- Kegiatan -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kegiatan</label>
                    <textarea
                        name="kegiatan"
                        rows="4"
                        class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400"
                        placeholder="Jelaskan kegiatan yang dilakukan hari ini..."
                        required></textarea>
                </div>

                <!-- Lokasi + Jam -->
                <div class="grid md:grid-cols-2 gap-4">

                    <!-- Lokasi -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Lokasi</label>
                        <input
                            type="text"
                            name="lokasi"
                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400"
                            placeholder="Nama instansi / divisi / lokasi kerja">
                    </div>

                    <!-- Jam Mulai & Selesai -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jam Mulai - Jam Selesai</label>
                        <div class="flex gap-2">
                            <input
                                type="time"
                                name="jam_mulai"
                                class="w-1/2 border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
                            <input
                                type="time"
                                name="jam_selesai"
                                class="w-1/2 border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
                        </div>
                    </div>
                </div>

                <!-- Output -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Output / Hasil</label>
                    <textarea
                        name="output"
                        rows="3"
                        class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400"
                        placeholder="Mis: laporan harian, modul yang dikerjakan, dsb."></textarea>
                </div>

                <!-- Tombol -->
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="bg-pink-600 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow hover:bg-pink-700 transition">
                        Simpan Log
                    </button>
                    <a href="<?= BASE_URL; ?>/?r=mahasiswa/logPklIndex"
                        class="text-sm text-slate-600 hover:underline">
                        Batal
                    </a>
                </div>

            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../../partials/mahasiswa/bottom-nav.php'; ?>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>