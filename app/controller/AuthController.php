<?php

class AuthController extends Controller
{
    public function loginForm(): void
    {
        // Kalau sudah login, arahkan ke dashboard utama
        if (isset($_SESSION['user'])) {
            $roles = $_SESSION['roles'] ?? [$_SESSION['user']['role']];

            if (in_array('admin', $roles)) {
                header('Location: ' . BASE_URL . '/?r=admin/dashboard');
            } elseif (in_array('mahasiswa', $roles)) {
                header('Location: ' . BASE_URL . '/?r=mahasiswa/dashboard');
            } elseif (in_array('dosen', $roles) || in_array('kaprodi', $roles)) {
                header('Location: ' . BASE_URL . '/?r=dosen/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/');
            }
            exit;
        }

        $title = 'Login - SIPKS';
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $this->view('auth/login', compact('title', 'error'));
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['flash_error'] = 'Username dan password wajib diisi.';
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        $conn = db();

        // === Ambil user (LOGIN LAMA TETAP) ===
        $stmt = $conn->prepare(
            'SELECT id, username, password, nama, role, status 
             FROM users 
             WHERE username = ? 
             LIMIT 1'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['flash_error'] = 'Username atau password salah.';
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        if ($user['status'] !== 'aktif') {
            $_SESSION['flash_error'] = 'Akun Anda tidak aktif. Silakan hubungi admin.';
            header('Location: ' . BASE_URL . '/?r=auth/loginForm');
            exit;
        }

        // === LOGIN BERHASIL ===
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'nama'     => $user['nama'],
            'role'     => $user['role'], // legacy (JANGAN DIHAPUS)
        ];

        // === AMBIL MULTI-ROLE (BARU) ===
        $roles = [];
        
        // Gunakan try-catch agar tidak crash jika tabel 'roles' dan 'user_roles' belum ada di database
        try {
            $stmt = $conn->prepare(
                'SELECT r.name 
                 FROM roles r
                 JOIN user_roles ur ON ur.role_id = r.id
                 WHERE ur.user_id = ?'
            );
            
            if ($stmt) {
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()) {
                    $roles[] = $row['name'];
                }
                $stmt->close();
            }
        } catch (\mysqli_sql_exception $e) {
            // Abaikan error database terkait tabel yang tidak ada.
            // Biarkan array $roles kosong agar otomatis menggunakan fallback di bawah.
        }

        // === FALLBACK KE ROLE LAMA (AMAN) ===
        if (empty($roles)) {
            $roles = [$user['role']];
        }

        // SIMPAN ROLE SEBAGAI ARRAY
        $_SESSION['roles'] = $roles;

        

        // === REDIRECT BERDASARKAN ROLE PRIORITAS ===
        if (in_array('admin', $roles)) {
            header('Location: ' . BASE_URL . '/?r=admin/dashboard');
        } elseif (in_array('mahasiswa', $roles)) {
            header('Location: ' . BASE_URL . '/?r=mahasiswa/dashboard');
        } elseif (in_array('kaprodi', $roles) || in_array('dosen', $roles)) {
            header('Location: ' . BASE_URL . '/?r=dosen/dashboard');
        } else {
            header('Location: ' . BASE_URL . '/');
        }
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: ' . BASE_URL . '/?r=auth/loginForm');
        exit;
    }
}