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

$namaMahasiswa = $_SESSION['username'] ?? 'Mahasiswa';

$id_user = $_SESSION['id_user'];


/*
|--------------------------------------------------------------------------
| DATA MAHASISWA
|--------------------------------------------------------------------------
*/

$stmtMahasiswa = $conn->prepare("
    SELECT id_mahasiswa, nama_mahasiswa
    FROM mahasiswa
    WHERE id_user = ?
");

$stmtMahasiswa->bind_param("i", $id_user);
$stmtMahasiswa->execute();

$resultMahasiswa = $stmtMahasiswa->get_result();
$mahasiswa = $resultMahasiswa->fetch_assoc();

if (!$mahasiswa) {
    die('Data mahasiswa tidak ditemukan.');
}

$id_mahasiswa = $mahasiswa['id_mahasiswa'];
$namaMahasiswa = $mahasiswa['nama_mahasiswa'];


/*
|--------------------------------------------------------------------------
| AMBIL RIWAYAT PEMBAYARAN
|--------------------------------------------------------------------------
*/

$riwayat = [];

$query = "
    SELECT
        p.id_pembayaran,
        p.nominal_pembayaran,
        p.tanggal_pembayaran,
        p.status_pembayaran,

        tk.nama_tagihan,
        mt.nama_metode

    FROM pembayaran p

    JOIN tagihan_mahasiswa tm
        ON p.id_tagihan_mahasiswa = tm.id_tagihan_mahasiswa

    JOIN tagihan_kas tk
        ON tm.id_tagihan = tk.id_tagihan

    JOIN metode mt
        ON p.id_metode = mt.id_metode

    WHERE tm.id_mahasiswa = ?

    ORDER BY p.id_pembayaran DESC
";

$stmtRiwayat = $conn->prepare($query);
$stmtRiwayat->bind_param("i", $id_mahasiswa);
$stmtRiwayat->execute();

$resultRiwayat = $stmtRiwayat->get_result();


while ($row = $resultRiwayat->fetch_assoc()) {

    $status = $row['status_pembayaran'];

    if ($status === 'disetujui') {

        $statusTampil = 'Disetujui';

    } elseif ($status === 'ditolak') {

        $statusTampil = 'Ditolak';

    } elseif ($status === 'menunggu') {

        $statusTampil = 'Menunggu Verifikasi';

    } else {

        $statusTampil = ucfirst($status);
    }

    $riwayat[] = [
        'id_pembayaran' => $row['id_pembayaran'],
        'tagihan' => $row['nama_tagihan'],
        'nominal' => $row['nominal_pembayaran'],
        'tanggal' => date(
            'd F Y',
            strtotime($row['tanggal_pembayaran'])
        ),
        'metode' => $row['nama_metode'],
        'status' => $statusTampil
    ];
}


/*
|--------------------------------------------------------------------------
| HITUNG STATISTIK
|--------------------------------------------------------------------------
*/

$totalPembayaran = 0;
$totalDisetujui = 0;
$totalDitolak = 0;
$totalMenunggu = 0;

foreach ($riwayat as $item) {

    if ($item['status'] === 'Disetujui') {

        $totalPembayaran += $item['nominal'];
        $totalDisetujui++;

    } elseif ($item['status'] === 'Ditolak') {

        $totalDitolak++;

    } elseif ($item['status'] === 'Menunggu Verifikasi') {

        $totalMenunggu++;
    }
}

$totalTransaksi = count($riwayat);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pembayaran | KasKita</title>

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

                    <a href="dashboard_mahasiswa.php">
                        <span class="menu-icon">⌂</span>
                        <span>Dashboard</span>
                    </a>

                    <a href="tagihan_mahasiswa.php">
                        <span class="menu-icon">▣</span>
                        <span>Tagihan Saya</span>
                    </a>

                    <a href="pembayaran_mahasiswa.php">
                        <span class="menu-icon">▤</span>
                        <span>Pembayaran</span>
                    </a>

                    <a href="riwayat_pembayaran.php" class="active">
                        <span class="menu-icon">▥</span>
                        <span>Riwayat Pembayaran</span>
                    </a>

                </nav>

            </div>


            <div class="menu-section">

                <p class="menu-title">
                    LAINNYA
                </p>

                <nav class="menu">

                    <a href="#">
                        <span class="menu-icon">♙</span>
                        <span>Profil</span>
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
                        Riwayat Pembayaran
                    </h1>

                    <p>
                        Daftar pembayaran kas yang pernah kamu lakukan
                    </p>

                </div>


                <div class="profile">

                    <div class="profile-icon">
                        M
                    </div>

                    <div class="profile-info">

                        <strong>
                            <?php echo htmlspecialchars($namaMahasiswa); ?>
                        </strong>

                        <span>
                            Mahasiswa
                        </span>

                    </div>

                </div>

            </header>


            <!-- STATISTIK -->
            <section class="stats">

                <div class="stat-card">

                    <div class="stat-icon blue">
                        Rp
                    </div>

                    <div class="stat-content">

                        <span>
                            Total Dibayar
                        </span>

                        <h3>
                            Rp
                            <?php echo number_format(
                                $totalPembayaran,
                                0,
                                ',',
                                '.'
                            ); ?>
                        </h3>

                        <small>
                            Pembayaran disetujui
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        ✓
                    </div>

                    <div class="stat-content">

                        <span>
                            Disetujui
                        </span>

                        <h3>
                            <?php echo $totalDisetujui; ?>
                        </h3>

                        <small>
                            Pembayaran berhasil
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        !
                    </div>

                    <div class="stat-content">

                        <span>
                            Ditolak
                        </span>

                        <h3>
                            <?php echo $totalDitolak; ?>
                        </h3>

                        <small>
                            Perlu diperiksa kembali
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        ▤
                    </div>

                    <div class="stat-content">

                        <span>
                            Total Transaksi
                        </span>

                        <h3>
                            <?php echo $totalTransaksi; ?>
                        </h3>

                        <small>
                            Riwayat pembayaran
                        </small>

                    </div>

                </div>

            </section>


            <!-- RIWAYAT -->
            <section class="dashboard-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Daftar Riwayat Pembayaran
                        </h3>

                        <p>
                            Semua pembayaran yang pernah kamu kirim
                        </p>

                    </div>

                </div>


                <div class="student-history-table">

                    <!-- HEADER -->
                    <div class="table-head">

                        <span>
                            Tagihan
                        </span>

                        <span>
                            Nominal
                        </span>

                        <span>
                            Tanggal
                        </span>

                        <span>
                            Metode
                        </span>

                        <span>
                            Status
                        </span>

                    </div>


                    <!-- DATA -->
                    <?php if (count($riwayat) > 0): ?>

                        <?php foreach ($riwayat as $item): ?>

                            <div class="student-history-row">

                                <div>

                                    <strong>
                                        <?php echo htmlspecialchars(
                                            $item['tagihan']
                                        ); ?>
                                    </strong>

                                </div>


                                <div>

                                    <strong>
                                        Rp
                                        <?php echo number_format(
                                            $item['nominal'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        <?php echo htmlspecialchars(
                                            $item['tanggal']
                                        ); ?>
                                    </span>

                                </div>


                                <div>

                                    <span>
                                        <?php echo htmlspecialchars(
                                            $item['metode']
                                        ); ?>
                                    </span>

                                </div>


                                <div>

                                    <?php if ($item['status'] === 'Disetujui'): ?>

                                        <span class="status approved-status">
                                            Disetujui
                                        </span>

                                    <?php elseif ($item['status'] === 'Ditolak'): ?>

                                        <span class="status rejected-status">
                                            Ditolak
                                        </span>

                                    <?php else: ?>

                                        <span class="status pending-status">
                                            Menunggu Verifikasi
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="student-history-row">

                            <div style="grid-column: 1 / -1; text-align: center;">

                                <span>
                                    Belum ada riwayat pembayaran.
                                </span>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

</body>

</html>