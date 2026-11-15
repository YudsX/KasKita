
<?php
session_start();

require_once 'config/Database.php';

$db = new Database();
$conn = $db->connect();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '') {

        $error = 'Username wajib diisi.';

    } else {

        $stmt = $conn->prepare("
            SELECT id_user, username, password, role, nama
            FROM users
            WHERE username = ?
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            /*
            |--------------------------------------------------------------------------
            | MAHASISWA BELUM MEMILIKI PASSWORD
            |--------------------------------------------------------------------------
            */

            if (
                $user['role'] === 'mahasiswa' &&
                (
                    $user['password'] === null ||
                    $user['password'] === ''
                )
            ) {

                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['nama'];
                $_SESSION['role'] = 'mahasiswa';

                header('Location: pages/buat_password.php');
                exit;
            }

            /*
            |--------------------------------------------------------------------------
            | PASSWORD SUDAH TERDAFTAR
            |--------------------------------------------------------------------------
            */

            if ($password === '') {

                $error = 'Password wajib diisi.';

            } elseif ($password === $user['password']) {

                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                /*
                |--------------------------------------------------------------------------
                | ARAHKAN SESUAI ROLE
                |--------------------------------------------------------------------------
                */

                if ($user['role'] === 'bendahara') {

                    header('Location: pages/dashboard.php');
                    exit;

                } elseif ($user['role'] === 'mahasiswa') {

                    header('Location: pages/dashboard_mahasiswa.php');
                    exit;
                }

            } else {

                $error = 'Username atau password salah.';
            }

        } else {

            $error = 'Username atau password salah.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - KasKita</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            background:
                linear-gradient(135deg,
                    #e8f5e9 0%,
                    #f5f9f6 50%,
                    #dcefe1 100%);

            color: #333;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: white;

            border-radius: 18px;

            padding: 38px 35px;

            box-shadow:
                0 12px 35px rgba(0, 0, 0, 0.10);

            border: 1px solid #edf3ee;
        }

        .logo {
            width: 68px;
            height: 68px;

            margin: 0 auto 18px;

            border-radius: 18px;

            display: flex;
            justify-content: center;
            align-items: center;

            background: #2e7d32;
            color: white;

            font-size: 28px;
            font-weight: bold;

            box-shadow:
                0 8px 20px rgba(46, 125, 50, 0.25);
        }

        .title {
            text-align: center;

            color: #245b28;

            font-size: 27px;

            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;

            color: #777;

            font-size: 14px;

            margin-bottom: 28px;

            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: bold;

            color: #444;
        }

        input {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #d9d9d9;

            border-radius: 9px;

            font-size: 14px;

            outline: none;

            transition: 0.2s;
        }

        input:focus {
            border-color: #2e7d32;

            box-shadow:
                0 0 0 3px rgba(46, 125, 50, 0.10);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 75px;
        }

        .show-password {
            position: absolute;

            right: 12px;
            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #2e7d32;

            cursor: pointer;

            font-size: 12px;

            font-weight: bold;
        }

        .error {
            background: #fff1f1;

            border: 1px solid #f2caca;

            color: #c62828;

            padding: 11px 13px;

            border-radius: 8px;

            font-size: 13px;

            margin-bottom: 18px;
        }

        .btn-login {
            width: 100%;

            border: none;

            border-radius: 9px;

            padding: 13px;

            background: #2e7d32;

            color: white;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s;
        }

        .btn-login:hover {
            background: #256b29;

            transform: translateY(-1px);
        }

        .create-password {
            margin-top: 22px;

            padding-top: 20px;

            border-top: 1px solid #eeeeee;

            text-align: center;

            font-size: 13px;

            color: #777;
        }

        .create-password a {
            display: inline-block;

            margin-top: 7px;

            color: #2e7d32;

            text-decoration: none;

            font-weight: bold;
        }

        .create-password a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;

            margin-top: 20px;

            color: #999;

            font-size: 12px;
        }

        @media (max-width: 480px) {

            .login-container {
                padding: 15px;
            }

            .login-card {
                padding: 30px 24px;
            }

            .title {
                font-size: 24px;
            }
        }
    </style>

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                K
            </div>

            <h1 class="title">
                KasKita
            </h1>

            <p class="subtitle">
                Sistem Informasi Pengelolaan Kas Mahasiswa
            </p>

            <?php if ($error !== ''): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label for="username">
                        Username / NIM
                    </label>

                    <input type="text" id="username" name="username" placeholder="Masukkan username atau NIM"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username"
                        required>

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="password-wrapper">

                        <input type="password" id="password" name="password" placeholder="Masukkan password"
                            autocomplete="current-password">

                        <button type="button" class="show-password" onclick="togglePassword()">
                            Lihat
                        </button>

                    </div>

                </div>

                <button type="submit" class="btn-login">
                    Masuk
                </button>

            </form>

            <div class="create-password">

                <div>
                    Belum mempunyai password?
                </div>

                <a href="pages/buat_password.php">
                    Buat password terlebih dahulu
                </a>

            </div>

        </div>

        <div class="footer">
            &copy; 2026 KasKita
        </div>

    </div>

    <script>

        function togglePassword() {

            const password = document.getElementById('password');
            const button = document.querySelector('.show-password');

            if (password.type === 'password') {

                password.type = 'text';
                button.textContent = 'Sembunyikan';

            } else {

                password.type = 'password';
                button.textContent = 'Lihat';
            }
        }

    </script>

</body>

</html>