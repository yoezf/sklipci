<?php include __DIR__ . '/../../partials/header.php'; ?>

<main class="flex-1 pb-20 md:pb-0 bg-gradient-to-b from-slate-100 to-slate-200">
    <div class="max-w-3xl mx-auto px-4 py-6">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 flex items-center gap-2">
                <span class="inline-block w-2 h-6 bg-[#DB2777] rounded"></span>
                Upload Laporan Akhir
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                <?= htmlspecialchars($mhs['nim'] ?? ''); ?> – 
                <span class="font-medium text-slate-700"><?= htmlspecialchars($mhs['nama'] ?? ''); ?></span>
            </p>

            <p class="text-xs text-slate-500 mt-1">
                Skripsi: 
                <span class="font-semibold text-slate-800">
                    <?= htmlspecialchars($skripsiAktif['judul']); ?>
                </span>
            </p>
        </div>

        <!-- Flash Error -->
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3 shadow-sm">
                <?= htmlspecialchars($_SESSION['flash_error']); ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Card Form -->
        <div class="bg-white rounded-xl shadow-md p-6 border border-slate-200">
            <form action="<?= BASE_URL; ?>/?r=mahasiswa/laporanAkhirStore"
                method="post"
                enctype="multipart/form-data"
                class="space-y-5">
                                            <?= $this->csrfField() ?>

                <!-- Judul -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Judul Laporan Akhir
                    </label>
                    <input type="text" name="judul"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 
                               focus:outline-none focus:ring-2 focus:ring-[#DB2777] focus:border-[#DB2777]
                               transition shadow-sm"
                        placeholder="Mis: Sistem Informasi Pengelolaan PKL dan Skripsi pada ... "
                        required>
                </div>

                <!-- File -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        File Laporan (PDF)
                    </label>
                    <input type="file" name="file"
                        accept="application/pdf"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 
                               focus:outline-none focus:ring-2 focus:ring-[#DB2777] focus:border-[#DB2777]
                               transition shadow-sm
                               bg-white"
                        required>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Format: PDF, maksimal 10MB. Unggah file final yang telah disetujui pembimbing.
                    </p>
                </div>

                <!-- Buttons -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                        class="bg-[#DB2777] text-white text-sm font-semibold px-5 py-2.5 rounded-lg
                               hover:bg-pink-700 transition shadow">
                        Upload Laporan
                    </button>

                    <a href="<?= BASE_URL; ?>/?r=mahasiswa/laporanAkhirIndex"
                        class="text-sm text-slate-600 hover:text-slate-800 hover:underline transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>

    </div>

    <?php include __DIR__ . '/../../partials/mahasiswa/bottom-nav.php'; ?>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
