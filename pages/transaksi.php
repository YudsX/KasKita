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
| FILTER
|--------------------------------------------------------------------------
*/

$jenisFilter = $_GET['jenis'] ?? '';
$cari = trim($_GET['cari'] ?? '');


/*
|--------------------------------------------------------------------------
| TOTAL PEMASUKAN
|--------------------------------------------------------------------------
*/

$queryPemasukan = "
    SELECT COALESCE(SUM(nominal_transaksi), 0) AS total
    FROM transaksi_kas
    WHERE jenis_transaksi = 'pemasukan'
";

$resultPemasukan = $conn->query($queryPemasukan);
$totalPemasukan = $resultPemasukan->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| TOTAL PENGELUARAN
|--------------------------------------------------------------------------
*/

$queryPengeluaran = "
    SELECT COALESCE(SUM(nominal_transaksi), 0) AS total
    FROM transaksi_kas
    WHERE jenis_transaksi = 'pengeluaran'
";

$resultPengeluaran = $conn->query($queryPengeluaran);
$totalPengeluaran = $resultPengeluaran->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| SALDO KAS
|--------------------------------------------------------------------------
*/

$saldoKas = $totalPemasukan - $totalPengeluaran;


/*
|--------------------------------------------------------------------------
| DATA TRANSAKSI
|--------------------------------------------------------------------------
*/

$queryTransaksi = "
    SELECT
        tk.id_transaksi,
        tk.id_pembayaran,
        tk.nominal_transaksi,
        tk.tanggal_transaksi,
        tk.jenis_transaksi,
        tk.keterangan
    FROM transaksi_kas tk
";


/*
|--------------------------------------------------------------------------
| FILTER TRANSAKSI
|--------------------------------------------------------------------------
*/

$conditions = [];
$params = [];
$types = '';


if (
    $jenisFilter === 'pemasukan' ||
    $jenisFilter === 'pengeluaran'
) {

    $conditions[] = "jenis_transaksi = ?";
    $params[] = $jenisFilter;
    $types .= 's';

}


if ($cari !== '') {

    $conditions[] = "keterangan LIKE ?";
    $params[] = '%' . $cari . '%';
    $types .= 's';

}


/*
|--------------------------------------------------------------------------
| BUNGKUS QUERY JIKA ADA FILTER
|--------------------------------------------------------------------------
*/

if (count($conditions) > 0) {

    $queryTransaksi = "
        SELECT *
        FROM (
            $queryTransaksi
        ) AS semua_transaksi
        WHERE " . implode(' AND ', $conditions);

}


$queryTransaksi .= "
    ORDER BY tanggal_transaksi DESC
";


/*
|--------------------------------------------------------------------------
| EKSEKUSI QUERY
|--------------------------------------------------------------------------
*/

$stmtTransaksi = $conn->prepare($queryTransaksi);

if (!$stmtTransaksi) {
    die("Query transaksi gagal: " . $conn->error);
}


if (count($params) > 0) {

    $stmtTransaksi->bind_param(
        $types,
        ...$params
    );

}


$stmtTransaksi->execute();

$resultTransaksi = $stmtTransaksi->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Transaksi Kas | KasKita</title>

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
                        Transaksi Kas
                    </h1>

                    <p>
                        Catatan pemasukan dan pengeluaran kas kelas 3A.
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
                            Riwayat Transaksi Kas
                        </h2>

                        <p>
                            Catatan seluruh transaksi keuangan kelas.
                        </p>

                    </div>


                    <a href="tambah_transaksi.php" class="btn-primary">
                        + Tambah Transaksi
                    </a>

                </div>


                <!-- SUMMARY -->
                <div class="mini-summary">

                    <div class="mini-card">

                        <span>
                            Total Pemasukan
                        </span>

                        <strong class="text-success">
                            Rp <?php echo number_format($totalPemasukan, 0, ',', '.'); ?>
                        </strong>

                    </div>


                    <div class="mini-card">

                        <span>
                            Total Pengeluaran
                        </span>

                        <strong class="text-danger">
                            Rp <?php echo number_format($totalPengeluaran, 0, ',', '.'); ?>
                        </strong>

                    </div>


                    <div class="mini-card">

                        <span>
                            Saldo Kas
                        </span>

                        <strong class="text-primary">
                            Rp <?php echo number_format($saldoKas, 0, ',', '.'); ?>
                        </strong>

                    </div>

                </div>


                <!-- FILTER -->
                <form method="GET" class="filter-area">

                    <div class="filter-item">

                        <label>
                            Jenis Transaksi
                        </label>

                        <select name="jenis">

                            <option value="">
                                Semua Transaksi
                            </option>

                            <option value="pemasukan" <?php echo $jenisFilter === 'pemasukan' ? 'selected' : ''; ?>>
                                Pemasukan
                            </option>

                            <option value="pengeluaran" <?php echo $jenisFilter === 'pengeluaran' ? 'selected' : ''; ?>>
                                Pengeluaran
                            </option>

                        </select>

                    </div>


                    <div class="filter-item">

                        <label>
                            Cari Transaksi
                        </label>

                        <input type="text" name="cari" value="<?php echo htmlspecialchars($cari); ?>"
                            placeholder="Cari keterangan transaksi...">

                    </div>


                    <button type="submit" class="btn-search">
                        Cari
                    </button>

                </form>


                <!-- TABLE -->
                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    No
                                </th>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Jenis
                                </th>

                                <th>
                                    Keterangan
                                </th>

                                <th>
                                    Nominal
                                </th>

                                <th>
                                    Sumber
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if ($resultTransaksi->num_rows > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($transaksi = $resultTransaksi->fetch_assoc()): ?>

                                    <tr>

                                        <td>
                                            <?php echo $no++; ?>
                                        </td>


                                        <td>

                                            <?php
                                            echo date(
                                                'd M Y',
                                                strtotime($transaksi['tanggal_transaksi'])
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php if ($transaksi['jenis_transaksi'] === 'pemasukan'): ?>

                                                <span class="transaction-income">
                                                    Pemasukan
                                                </span>

                                            <?php else: ?>

                                                <span class="transaction-expense">
                                                    Pengeluaran
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>
                                            <?php echo htmlspecialchars(
                                                $transaksi['keterangan'] ?? '-'
                                            ); ?>
                                        </td>


                                        <td>

                                            <?php if ($transaksi['jenis_transaksi'] === 'pemasukan'): ?>

                                                <strong class="text-success">

                                                    + Rp

                                                    <?php
                                                    echo number_format(
                                                        $transaksi['nominal_transaksi'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    );
                                                    ?>

                                                </strong>

                                            <?php else: ?>

                                                <strong class="text-danger">

                                                    - Rp

                                                    <?php
                                                    echo number_format(
                                                        $transaksi['nominal_transaksi'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    );
                                                    ?>

                                                </strong>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?php if (!empty($transaksi['id_pembayaran'])): ?>

                                                Pembayaran

                                            <?php else: ?>

                                                Bendahara

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <div class="action-buttons">
                                                <a href="detail_transaksi.php?id_transaksi=<?php echo $transaksi['id_transaksi']; ?>" class="btn-edit">
                                                    Detail
                                                </a>
                                            </div>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="7" style="text-align:center;">
                                        Belum ada transaksi kas.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


            </section>


        </main>

    </div>

</body>

</html>