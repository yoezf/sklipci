<?php

class Controller
{
    /** Token CSRF tersembunyi untuk disisipkan di dalam <form>. */
    protected function csrfField(): string
    {
        return csrf_field();
    }

    /** Escape nilai untuk output HTML (cegah XSS). */
    protected function e($value): string
    {
        return e($value);
    }

    /** Redirect aman ke route internal. */
    protected function redirect(string $route): void
    {
        header('Location: ' . BASE_URL . $route);
        exit;
    }

    /** Kembali ke halaman sebelumnya hanya jika internal (cegah open redirect). */
    protected function back(string $fallback = '/'): void
    {
        safe_redirect($_SERVER['HTTP_REFERER'] ?? null, $fallback);
    }

    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = BASE_PATH . '/app/view/' . $view . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            http_response_code(500);
            echo "View tidak ditemukan: " . htmlspecialchars($viewFile);
        }
    }

    protected function requireRole(array $roles): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        $userRoles = $_SESSION['roles'] ?? [$_SESSION['user']['role'] ?? ''];

        if (empty(array_intersect($userRoles, $roles))) {
            // Kalau role tidak sesuai, lempar ke dashboard sesuai role
            $role = $_SESSION['user']['role'] ?? ($userRoles[0] ?? '');
            if ($role === 'admin') {
                header('Location: ' . BASE_URL . '/?r=admin/dashboard');
            } elseif ($role === 'mahasiswa') {
                header('Location: ' . BASE_URL . '/?r=mahasiswa/dashboard');
            } elseif ($role === 'dosen') {
                header('Location: ' . BASE_URL . '/?r=dosen/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            }
            exit;
        }
    }
}
