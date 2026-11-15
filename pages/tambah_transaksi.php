<?php

session_start();

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] !== 'bendahara'
) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/Database.php';

$db = new Database();
$conn = $db->connect();

$pesan = '';
$jenisPesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jenis_transaksi = $_POST['jenis_transaksi'] ?? '';
    $nominal = $_POST['nominal_transaksi'] ?? '';
    $tanggal = $_POST['tanggal_transaksi'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (
        $jenis_transaksi === '' ||
        $nominal === '' ||
        $tanggal === '' ||
        $keterangan === ''
    ) {

        $pesan = 'Semua data transaksi wajib diisi.';
        $jenisPesan = 'error';

    } elseif (!in_array($jenis_transaksi, ['pemasukan', 'pengeluaran'])) {

        $pesan = 'Jenis transaksi tidak valid.';
        $jenisPesan = 'error';

    } elseif (!is_numeric($nominal) || $nominal <= 0) {

        $pesan = 'Nominal transaksi harus berupa angka lebih dari 0.';
        $jenisPesan = 'error';

    } else {

        $stmt = $conn->prepare("
            INSERT INTO transaksi_kas
            (
                id_pembayaran,
                nominal_transaksi,
                tanggal_transaksi,
                jenis_transaksi,
                keterangan
            )
            VALUES
            (
                NULL,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $stmt->bind_param(
            "dsss",
            $nominal,
            $tanggal,
            $jenis_transaksi,
            $keterangan
        );

        if ($stmt->execute()) {

            header('Location: transaksi.php');
            exit;

        } else {

            $pesan = 'Transaksi gagal disimpan.';
            $jenisPesan = 'error';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Transaksi | KasKita</title>

    <link rel="stylesheet" href="/kaskita/assets/css/style.css">

</head>

<body>

    <div class="app">

        <!-- SIDEBAR -->
        <aside class="sidebar">

            <div class="brand">

                <div class="brand-icon">
                    K
                </div>

                <div class="brand-name">
                    <span>Kas</span>Kita
                </div>

            </div>

            <div class="menu-section">

                <p class="menu-title">
                    MENU UTAMA
                </p>

                <nav class="menu">

                    <a href="dashboard.php">
                        <span class="menu-icon">⌂</span>
                        <span>Dashboard</span>
                    </a>

                    <a href="mahasiswa.php">
                        <span class="menu-icon">♙</span>
                        <span>Mahasiswa</span>
                    </a>

                    <a href="tagihan.php">
                        <span class="menu-icon">▣</span>
                        <span>Tagihan Kas</span>
                    </a>

                    <a href="pembayaran.php">
                        <span class="menu-icon">▤</span>
                        <span>Pembayaran</span>
                    </a>

                    <a href="transaksi.php" class="active">
                        <span class="menu-icon">Rp</span>
                        <span>Transaksi Kas</span>
                    </a>

                </nav>

            </div>

            <div class="menu-section">

                <p class="menu-title">
                    LAINNYA
                </p>

                <nav class="menu">

                    <a href="#">
                        <span class="menu-icon">▥</span>
                        <span>Laporan</span>
                    </a>

                </nav>

            </div>

            <div class="sidebar-bottom">

                <a href="../index.php" class="logout">
                    <span class="menu-icon">↪</span>
                    <span>Keluar</span>
                </a>

            </div>

        </aside>


        <!-- MAIN CONTENT -->
        <main class="main-content">

            <header class="topbar">

                <div class="page-title">

                    <h1>
                        Tambah Transaksi
                    </h1>

                    <p>
                        Tambahkan transaksi pemasukan atau pengeluaran kas.
                    </p>

                </div>

                <div class="profile">

                    <div class="profile-icon">
                        B
                    </div>

                    <div class="profile-info">

                        <strong>
                            Bendahara
                        </strong>

                        <span>
                            Kelas 3A
                        </span>

                    </div>

                </div>

            </header>


            <section class="content-card">

                <div class="content-header">

                    <div>

                        <h2>
                            Form Transaksi
                        </h2>

                        <p>
                            Isi data transaksi dengan benar.
                        </p>

                    </div>

                </div>


                <?php if ($pesan !== ''): ?>

                    <div style="
                    padding: 12px 15px;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    background: #f8d7da;
                    color: #842029;
                ">
                        <?php echo htmlspecialchars($pesan); ?>
                    </div>

                <?php endif; ?>


                <form method="POST">

                    <div class="form-group">

                        <label>
                            Jenis Transaksi
                        </label>

                        <select name="jenis_transaksi" required>

                            <option value="">
                                -- Pilih Jenis Transaksi --
                            </option>

                            <option value="pemasukan">
                                Pemasukan
                            </option>

                            <option value="pengeluaran">
                                Pengeluaran
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Nominal
                        </label>

                        <input type="number" name="nominal_transaksi" min="1" placeholder="Contoh: 50000" required>

                    </div>


                    <div class="form-group">

                        <label>
                            Tanggal Transaksi
                        </label>

                        <input type="date" name="tanggal_transaksi" required>

                    </div>


                    <div class="form-group">

                        <label>
                            Keterangan
                        </label>

                        <textarea name="keterangan" rows="4" placeholder="Contoh: Pembelian perlengkapan kelas"
                            required></textarea>

                    </div>


                    <div class="form-actions">

                        <a href="transaksi.php" class="btn-cancel">
                            Batal
                        </a>

                        <button type="submit" class="btn-primary">
                            Simpan Transaksi
                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>

</body>

</html>