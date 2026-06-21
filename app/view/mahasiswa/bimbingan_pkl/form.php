<div class="max-w-xl mx-auto px-4 py-6">
  <h1 class="text-xl font-semibold text-slate-800 mb-1"><?= htmlspecialchars($title) ?></h1>
  <p class="text-xs text-slate-500 mb-4">Isi tanggal, media, dan topik bimbingan.</p>

  <?php if (function_exists('flash')) flash(); ?>

  <form action="<?= BASE_URL ?>/?r=mahasiswa/bimbinganPklStore" method="POST"
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-4">
                                            <?= $this->csrfField() ?>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal</label>
      <input type="date" name="tanggal" required
             class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Media</label>
      <select name="media" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <option>Tatap Muka</option>
        <option>Online</option>
        <option>Chat</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Topik</label>
      <input type="text" name="topik" required
             class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
             placeholder="Contoh: Konsultasi laporan PKL minggu 1">
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Catatan Mahasiswa (opsional)</label>
      <textarea name="catatan_mahasiswa" rows="3"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                placeholder="Ringkasan diskusi / tugas yang diberikan"></textarea>
    </div>

    <div class="flex items-center justify-between pt-2">
      <a href="<?= BASE_URL ?>/?r=mahasiswa/bimbinganPklIndex"
         class="text-xs px-3 py-2 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50">
        Batal
      </a>
      <button class="text-xs px-4 py-2 rounded-full bg-[#DB2777] text-white hover:bg-pink-600">
        Simpan
      </button>
    </div>
  </form>
</div>
