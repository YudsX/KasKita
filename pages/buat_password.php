<?php
session_start();

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] !== 'mahasiswa'
) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/Database.php';

$db = new Database();
$conn = $db->connect();

$error = '';

$id_user = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = trim($_POST['password'] ?? '');
    $konfirmasi = trim($_POST['konfirmasi_password'] ?? '');

    if ($password === '' || $konfirmasi === '') {

        $error = 'Password dan konfirmasi password wajib diisi.';

    } elseif (strlen($password) < 5) {

        $error = 'Password minimal 5 karakter.';

    } elseif ($password !== $konfirmasi) {

        $error = 'Konfirmasi password tidak sesuai.';

    } else {

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id_user = ?
        ");

        $stmt->bind_param("si", $password, $id_user);

        if ($stmt->execute()) {

            header('Location: dashboard_mahasiswa.php');
            exit;

        } else {

            $error = 'Password gagal disimpan.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Buat Password - KasKita</title>

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

        .password-container {
            width: 100%;
            max-width: 430px;

            padding: 20px;
        }

        .password-card {
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

            font-size: 26px;

            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;

            color: #777;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 26px;
        }

        .info-box {
            background: #f1f8f2;

            border: 1px solid #d9eadb;

            border-radius: 10px;

            padding: 12px 14px;

            margin-bottom: 22px;

            color: #4d654f;

            font-size: 13px;

            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;

            margin-bottom: 7px;

            color: #444;

            font-size: 14px;

            font-weight: bold;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;

            padding: 13px 75px 13px 14px;

            border: 1px solid #d9d9d9;

            border-radius: 9px;

            font-size: 14px;

            outline: none;

            transition: 0.2s;
        }

        .password-wrapper input:focus {
            border-color: #2e7d32;

            box-shadow:
                0 0 0 3px rgba(46, 125, 50, 0.10);
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

            line-height: 1.4;
        }

        .password-info {
            font-size: 12px;

            color: #888;

            margin-top: -8px;

            margin-bottom: 20px;
        }

        .btn-submit {
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

        .btn-submit:hover {
            background: #256b29;

            transform: translateY(-1px);
        }

        .back-login {
            text-align: center;

            margin-top: 20px;

            padding-top: 18px;

            border-top: 1px solid #eeeeee;

            font-size: 13px;

            color: #777;
        }

        .back-login a {
            display: inline-block;

            margin-top: 6px;

            color: #2e7d32;

            text-decoration: none;

            font-weight: bold;
        }

        .back-login a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;

            margin-top: 20px;

            color: #999;

            font-size: 12px;
        }

        @media (max-width: 480px) {

            .password-container {
                padding: 15px;
            }

            .password-card {
                padding: 30px 24px;
            }

            .title {
                font-size: 23px;
            }
        }
    </style>

</head>

<body>

    <div class="password-container">

        <div class="password-card">

            <div class="logo">
                K
            </div>

            <h1 class="title">
                Buat Password
            </h1>

            <p class="subtitle">
                Buat password untuk akun KasKita kamu
            </p>

            <div class="info-box">
                Silakan buat password baru untuk melanjutkan
                ke halaman dashboard mahasiswa.
            </div>

            <?php if ($error !== ''): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label for="password">
                        Password Baru
                    </label>

                    <div class="password-wrapper">

                        <input type="password" id="password" name="password" placeholder="Masukkan password baru"
                            autocomplete="new-password" required>

                        <button type="button" class="show-password" onclick="togglePassword('password', this)">
                            Lihat
                        </button>

                    </div>

                </div>

                <div class="password-info">
                    Password minimal 5 karakter.
                </div>

                <div class="form-group">

                    <label for="konfirmasi_password">
                        Konfirmasi Password
                    </label>

                    <div class="password-wrapper">

                        <input type="password" id="konfirmasi_password" name="konfirmasi_password"
                            placeholder="Masukkan ulang password" autocomplete="new-password" required>

                        <button type="button" class="show-password"
                            onclick="togglePassword('konfirmasi_password', this)">
                            Lihat
                        </button>

                    </div>

                </div>

                <button type="submit" class="btn-submit">
                    Simpan Password
                </button>

            </form>

            <div class="back-login">

                <div>
                    Ingin kembali ke halaman login?
                </div>

                <a href="../index.php">
                    Kembali ke Login
                </a>

            </div>

        </div>

        <div class="footer">
            &copy; 2026 KasKita
        </div>

    </div>

    <script>

        function togglePassword(id, button) {

            const input = document.getElementById(id);

            if (input.type === 'password') {

                input.type = 'text';
                button.textContent = 'Sembunyikan';

            } else {

                input.type = 'password';
                button.textContent = 'Lihat';
            }
        }

    </script>

</body>

</html>