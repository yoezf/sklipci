<?php include __DIR__ . '/../../partials/header.php'; ?>

<main class="flex-1 pb-16 md:pb-0 bg-slate-100">
    <div class="max-w-3xl mx-auto px-4 py-4">
        <h1 class="text-xl md:text-2xl font-semibold mb-2">
            Profil Dosen
        </h1>
        <p class="text-sm text-slate-500 mb-4">
            Informasi akun dan data dosen aktif.
        </p>

        <div class="bg-white shadow-md rounded-lg p-4 space-y-4">
            <div>
                <p class="text-xs text-slate-500">Nama</p>
                <p class="text-sm font-semibold text-slate-800">
                    <?= htmlspecialchars($dosen['nama'] ?? ($user['nama'] ?? '-')); ?>
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-4 border-t pt-4">
                <div>
                    <p class="text-xs text-slate-500">Username</p>
                    <p class="text-sm text-slate-800">
                        <?= htmlspecialchars($user['username'] ?? '-'); ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">NIP</p>
                    <p class="text-sm text-slate-800">
                        <?= htmlspecialchars($dosen['nip'] ?? '-'); ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">No. HP</p>
                    <p class="text-sm text-slate-800">
                        <?= htmlspecialchars($dosen['no_hp'] ?? '-'); ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Role</p>
                    <p class="text-sm text-slate-800">
                        <?= htmlspecialchars(implode(', ', $_SESSION['roles'] ?? [$user['role'] ?? 'dosen'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../partials/dosen/bottom-nav.php'; ?>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
