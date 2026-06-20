<?php
$current = $_GET['r'] ?? '';
$roles   = $_SESSION['roles'] ?? [$_SESSION['user']['role']];
$isKaprodi = in_array('kaprodi', $roles);

// helper active state
$isBimbinganActive = (
    (!$isKaprodi && $current === 'dosen/logbimbinganIndex') ||
    ($isKaprodi && $current === 'kaprodi/pklIndex')
);

$isJadwalActive = (
    (!$isKaprodi && $current === 'dosen/jadwalIndex') ||
    ($isKaprodi && $current === 'kaprodi/jadwalIndex')
);
?>

<!-- Bottom Nav Dosen -->
<div class="fixed inset-x-0 bottom-0 bg-white border-t shadow-lg z-50">
    <nav class="flex justify-between text-[11px]">

        <!-- Beranda -->
        <a href="<?= BASE_URL; ?>/?r=dosen/dashboard"
           class="flex-1 text-center py-2 <?= $current === 'dosen/dashboard' ? 'text-pink-600' : 'text-slate-500' ?>">
            <div class="flex flex-col items-center">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 mb-0.5 <?= $current === 'dosen/dashboard' ? 'stroke-pink-600' : 'stroke-slate-500' ?>"
                     fill="none" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H3.75A.75.75 0 013 21V9.75z" />
                </svg>
                <span class="<?= $current === 'dosen/dashboard' ? 'font-semibold' : '' ?>">Beranda</span>
            </div>
        </a>

        <!-- Bimbingan / Kaprodi -->
        <a href="<?= BASE_URL; ?>/?r=<?= $isKaprodi ? 'kaprodi/pklIndex' : 'dosen/logbimbinganIndex' ?>"
           class="flex-1 text-center py-2 <?= $isBimbinganActive ? 'text-pink-600' : 'text-slate-500' ?>">
            <div class="flex flex-col items-center">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 mb-0.5 <?= $isBimbinganActive ? 'stroke-pink-600' : 'stroke-slate-500' ?>"
                     fill="none" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-4-3c0 1.105 1.79 2 4 2s4-.895 4-2" />
                </svg>
                <span class="<?= $isBimbinganActive ? 'font-semibold' : '' ?>">Bimbingan</span>
            </div>
        </a>

        <!-- Jadwal -->
        <a href="<?= BASE_URL; ?>/?r=<?= $isKaprodi ? 'kaprodi/jadwalIndex' : 'dosen/jadwalIndex' ?>"
           class="flex-1 text-center py-2 <?= $isJadwalActive ? 'text-pink-600' : 'text-slate-500' ?>">
            <div class="flex flex-col items-center">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 mb-0.5 <?= $isJadwalActive ? 'stroke-pink-600' : 'stroke-slate-500' ?>"
                     fill="none" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6 2v4m12-4v4M3 10h18M3 10v10a2 2 0 002 2h14a2 2 0 002-2V10" />
                </svg>
                <span class="<?= $isJadwalActive ? 'font-semibold' : '' ?>">Jadwal</span>
            </div>
        </a>

        <!-- Profil -->
        <a href="<?= BASE_URL; ?>/?r=dosen/profilIndex"
           class="flex-1 text-center py-2 <?= $current === 'dosen/profilIndex' ? 'text-pink-600' : 'text-slate-500' ?>">
            <div class="flex flex-col items-center">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 mb-0.5 <?= $current === 'dosen/profilIndex' ? 'stroke-pink-600' : 'stroke-slate-500' ?>"
                     fill="none" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 1115 0v.75H4.5v-.75z" />
                </svg>
                <span class="<?= $current === 'dosen/profilIndex' ? 'font-semibold' : '' ?>">Profil</span>
            </div>
        </a>

    </nav>
</div>
