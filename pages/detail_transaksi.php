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


/*
|--------------------------------------------------------------------------
| AMBIL ID TRANSAKSI
|--------------------------------------------------------------------------
*/

$id_transaksi = $_GET['id_transaksi'] ?? 0;

if ($id_transaksi == 0) {
    die("ID transaksi tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA TRANSAKSI
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id_transaksi,
        id_pembayaran,
        nominal_transaksi,
        tanggal_transaksi,
        jenis_transaksi,
        keterangan
    FROM transaksi_kas
    WHERE id_transaksi = ?
");

$stmt->bind_param("i", $id_transaksi);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data transaksi tidak ditemukan.");
}

$transaksi = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Transaksi | KasKita</title>

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

            <!-- TOPBAR -->
            <header class="topbar">

                <div class="page-title">

                    <h1>
                        Detail Transaksi
                    </h1>

                    <p>
                        Informasi lengkap transaksi kas.
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


            <!-- CONTENT -->
            <section class="content-card">

                <div class="content-header">

                    <div>

                        <h2>
                            Detail Transaksi Kas
                        </h2>

                        <p>
                            Berikut informasi transaksi yang dipilih.
                        </p>

                    </div>

                    <a href="transaksi.php" class="btn-primary">
                        ← Kembali
                    </a>

                </div>


                <div class="detail-card">

                    <div class="detail-item">

                        <span>
                            ID Transaksi
                        </span>

                        <strong>
                        <?php echo $transaksi['id_transaksi']; ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Tanggal Transaksi
                        </span>

                        <strong>
                            <?php
                            echo date(
                                'd M Y',
                                strtotime($transaksi['tanggal_transaksi'])
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Jenis Transaksi
                        </span>

                        <strong>

                            <?php if ($transaksi['jenis_transaksi'] === 'pemasukan'): ?>

                                <span class="transaction-income">
                                    Pemasukan
                                </span>

                            <?php else: ?>

                                <span class="transaction-expense">
                                    Pengeluaran
                                </span>

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Nominal
                        </span>

                        <strong>

                            <?php if ($transaksi['jenis_transaksi'] === 'pemasukan'): ?>

                                <span class="text-success">
                                    + Rp
                                    <?php
                                    echo number_format(
                                        $transaksi['nominal_transaksi'],
                                        0,
                                        ',',
                                        '.'
                                    );
                                    ?>
                                </span>

                            <?php else: ?>

                                <span class="text-danger">
                                    - Rp
                                    <?php
                                    echo number_format(
                                        $transaksi['nominal_transaksi'],
                                        0,
                                        ',',
                                        '.'
                                    );
                                    ?>
                                </span>

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Keterangan
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $transaksi['keterangan'] ?? '-'
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Sumber Transaksi
                        </span>

                        <strong>

                            <?php if (!empty($transaksi['id_pembayaran'])): ?>

                                Pembayaran Mahasiswa

                            <?php else: ?>

                                Bendahara

                            <?php endif; ?>

                        </strong>

                    </div>


                    <?php if (!empty($transaksi['id_pembayaran'])): ?>

                        <div class="detail-item">

                            <span>
                                ID Pembayaran
                            </span>

                            <strong>
                                <?php echo $transaksi['id_pembayaran']; ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

</body>

</html>
