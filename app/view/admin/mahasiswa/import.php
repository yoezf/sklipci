<?php
include __DIR__ . '/../../partials/header.php';
?>

<main class="flex-1 bg-slate-100 min-h-screen pb-16 md:pb-0">
  <div class="max-w-6xl mx-auto px-4 py-6 flex gap-6">

    <?php include __DIR__ . '/../../partials/admin/sidebar.php'; ?>

    <!-- Content -->
    <div class="flex-1">
      <h1 class="text-2xl font-semibold text-slate-800 mb-6">
        <?= $title ?? 'Import Mahasiswa (CSV)' ?>
      </h1>

      <!-- Flash Error -->
      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="mb-4 rounded-lg bg-red-100 border border-red-300 p-4 text-red-700">
          <?= htmlspecialchars($_SESSION['flash_error']); ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>

      <!-- Flash Success -->
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="mb-4 rounded-lg bg-green-100 border border-green-300 p-4 text-green-700">
          <?= htmlspecialchars($_SESSION['flash_success']); ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>

      <!-- Card -->
      <div class="bg-white rounded-xl shadow p-6 max-w-xl">
        <p class="mb-4 text-slate-600">
          <span class="font-medium">Format CSV (disarankan):</span><br>
          <code class="block mt-1 bg-slate-100 p-2 rounded text-sm">
            nim,nama,prodi_id,kelas,angkatan,semester,no_hp
          </code>
        </p>

        <form
          action="<?= BASE_URL ?>/?r=admin/mahasiswaImport"
          method="post"
          enctype="multipart/form-data"
          class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Upload File CSV
            </label>
            <input
              type="file"
              name="csv_file"
              accept=".csv"
              required
              class="block w-full text-sm text-slate-600
             file:mr-4 file:py-2 file:px-4
             file:rounded-lg file:border-0
             file:bg-slate-200 file:text-slate-700
             hover:file:bg-slate-300">
          </div>

          <button
            type="submit"
            class="h-[42px] bg-green-600 text-white px-6 rounded-lg
           hover:bg-green-700 transition whitespace-nowrap">
            Import CSV
          </button>
        </form>

      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>