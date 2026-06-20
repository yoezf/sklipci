<div class="max-w-5xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-xl font-semibold text-slate-800"><?= htmlspecialchars($title) ?></h1>
      <p class="text-xs text-slate-500">Catat bimbingan PKL dan tunggu verifikasi dosen.</p>
    </div>
    <a href="<?= BASE_URL ?>/?r=mahasiswa/bimbinganPklCreateForm"
       class="text-xs px-3 py-2 rounded-full bg-[#DB2777] text-white hover:bg-pink-600">
      + Tambah Log
    </a>
  </div>

  <?php if (function_exists('flash')) flash(); ?>

  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Tanggal</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Media</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Topik</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Status</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Catatan Dosen</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="5" class="px-4 py-6 text-center text-slate-500 italic">Belum ada log bimbingan PKL.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $row): ?>
            <?php
              $status = $row['status_verifikasi'] ?? 'Menunggu';
              $badge = [
                'Menunggu'  => 'bg-yellow-100 text-yellow-700',
                'Disetujui' => 'bg-green-100 text-green-700',
                'Ditolak'   => 'bg-red-100 text-red-700',
              ][$status] ?? 'bg-slate-100 text-slate-600';
            ?>
            <tr class="border-t border-slate-100">
              <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($row['tanggal']) ?></td>
              <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($row['media']) ?></td>
              <td class="px-4 py-3 text-slate-800 font-medium">
                <?= htmlspecialchars($row['topik']) ?>
                <?php if (!empty($row['catatan_mahasiswa'])): ?>
                  <div class="mt-1 text-xs text-slate-500">
                    <?= nl2br(htmlspecialchars($row['catatan_mahasiswa'])) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <span class="text-xs px-2 py-1 rounded-full <?= $badge ?>"><?= htmlspecialchars($status) ?></span>
              </td>
              <td class="px-4 py-3 text-xs text-slate-600">
                <?= !empty($row['catatan_dosen']) ? nl2br(htmlspecialchars($row['catatan_dosen'])) : '-' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
