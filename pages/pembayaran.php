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

$statusFilter = $_GET['status'] ?? '';
$cari = trim($_GET['cari'] ?? '');


/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMBAYARAN
|--------------------------------------------------------------------------
*/

$dataPembayaran = [];

$query = "
    SELECT
        p.id_pembayaran,
        p.id_tagihan_mahasiswa,
        p.nominal_pembayaran,
        p.tanggal_pembayaran,
        p.status,
        p.status_pembayaran,
        p.bukti_pembayaran,

        m.nim,
        m.nama_mahasiswa,

        tk.nama_tagihan,

        mt.nama_metode

    FROM pembayaran p

    JOIN tagihan_mahasiswa tm
        ON p.id_tagihan_mahasiswa = tm.id_tagihan_mahasiswa

    JOIN mahasiswa m
        ON tm.id_mahasiswa = m.id_mahasiswa

    JOIN tagihan_kas tk
        ON tm.id_tagihan = tk.id_tagihan

    JOIN metode mt
        ON p.id_metode = mt.id_metode
";


/*
|--------------------------------------------------------------------------
| KONDISI FILTER
|--------------------------------------------------------------------------
*/

$conditions = [];
$params = [];
$types = '';


if (
    $statusFilter === 'menunggu' ||
    $statusFilter === 'disetujui' ||
    $statusFilter === 'ditolak'
) {
    $conditions[] = "p.status_pembayaran = ?";
    $params[] = $statusFilter;
    $types .= 's';
}


if ($cari !== '') {

    $conditions[] = "
        (
            m.nama_mahasiswa LIKE ?
            OR m.nim LIKE ?
        )
    ";

    $params[] = '%' . $cari . '%';
    $params[] = '%' . $cari . '%';

    $types .= 'ss';
}


if (count($conditions) > 0) {

    $query .= "
        WHERE " . implode(' AND ', $conditions);
}


$query .= "
    ORDER BY p.id_pembayaran DESC
";


/*
|--------------------------------------------------------------------------
| JALANKAN QUERY
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Query gagal: " . $conn->error);
}


if (count($params) > 0) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


$stmt->execute();

$result = $stmt->get_result();


while ($row = $result->fetch_assoc()) {
    $dataPembayaran[] = $row;
}


/*
|--------------------------------------------------------------------------
| HITUNG STATUS
|--------------------------------------------------------------------------
*/

$totalPembayaran = count($dataPembayaran);

$totalMenunggu = 0;
$totalDisetujui = 0;
$totalDitolak = 0;


foreach ($dataPembayaran as $item) {

    $status = $item['status_pembayaran'];

    if ($status === 'menunggu') {

        $totalMenunggu++;

    } elseif ($status === 'disetujui') {

        $totalDisetujui++;

    } elseif ($status === 'ditolak') {

        $totalDitolak++;
    }
}


/*
|--------------------------------------------------------------------------
| FUNGSI STATUS
|--------------------------------------------------------------------------
*/

function tampilStatus($status)
{
    if ($status === 'disetujui') {

        return '
            <span class="status approved-status">
                Disetujui
            </span>
        ';

    } elseif ($status === 'ditolak') {

        return '
            <span class="status rejected-status">
                Ditolak
            </span>
        ';
    }

    return '
        <span class="status waiting-status">
            Menunggu
        </span>
    ';
}


/*
|--------------------------------------------------------------------------
| FUNGSI AKSI
|--------------------------------------------------------------------------
*/

function tampilAksi($status, $id_pembayaran)
{
    if ($status === 'menunggu') {

        return '
            <a
                href="periksa_pembayaran.php?id_pembayaran='
            . $id_pembayaran .
            '"
                class="btn-edit">
                Periksa
            </a>
        ';

    } elseif ($status === 'disetujui') {

        return '
            <a
                href="periksa_pembayaran.php?id_pembayaran='
            . $id_pembayaran .
            '"
                class="btn-edit">
                Detail
            </a>
        ';

    } else {

        return '
            <a
                href="periksa_pembayaran.php?id_pembayaran='
            . $id_pembayaran .
            '"
                class="btn-edit">
                Periksa Ulang
            </a>
        ';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pembayaran | KasKita</title>

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

                        <span class="menu-icon">
                            ⌂
                        </span>

                        <span>
                            Dashboard
                        </span>

                    </a>


                    <a href="mahasiswa.php">

                        <span class="menu-icon">
                            ♙
                        </span>

                        <span>
                            Mahasiswa
                        </span>

                    </a>


                    <a href="tagihan.php">

                        <span class="menu-icon">
                            ▣
                        </span>

                        <span>
                            Tagihan Kas
                        </span>

                    </a>


                    <a href="pembayaran.php" class="active">

                        <span class="menu-icon">
                            ▤
                        </span>

                        <span>
                            Pembayaran
                        </span>

                    </a>


                    <a href="transaksi.php">

                        <span class="menu-icon">
                            Rp
                        </span>

                        <span>
                            Transaksi Kas
                        </span>

                    </a>

                </nav>

            </div>


            <div class="menu-section">

                <p class="menu-title">
                    LAINNYA
                </p>

                <nav class="menu">

                    <a href="#">

                        <span class="menu-icon">
                            ▥
                        </span>

                        <span>
                            Laporan
                        </span>

                    </a>

                </nav>

            </div>


            <div class="sidebar-bottom">

                <a href="../index.php" class="logout">

                    <span class="menu-icon">
                        ↪
                    </span>

                    <span>
                        Keluar
                    </span>

                </a>

            </div>

        </aside>


        <!-- MAIN -->

        <main class="main-content">


            <!-- TOPBAR -->

            <header class="topbar">

                <div class="page-title">

                    <h1>
                        Pembayaran
                    </h1>

                    <p>
                        Kelola dan verifikasi pembayaran kas mahasiswa.
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


                <!-- NOTIFIKASI -->

                <?php if (isset($_GET['status'])): ?>

                    <?php if ($_GET['status'] === 'disetujui'): ?>

                        <div class="success-message">
                            Pembayaran berhasil disetujui.
                        </div>

                    <?php elseif ($_GET['status'] === 'ditolak'): ?>

                        <div class="error-message">
                            Pembayaran ditolak.
                        </div>

                    <?php endif; ?>

                <?php endif; ?>


                <!-- HEADER -->

                <div class="content-header">

                    <div>

                        <h2>
                            Daftar Pembayaran
                        </h2>

                        <p>
                            Periksa pembayaran yang masuk dari mahasiswa.
                        </p>

                    </div>

                </div>


                <!-- SUMMARY -->

                <div class="mini-summary">

                    <div class="mini-card">

                        <span>
                            Total Pembayaran
                        </span>

                        <strong>
                            <?php echo $totalPembayaran; ?>
                        </strong>

                    </div>


                    <div class="mini-card">

                        <span>
                            Menunggu Verifikasi
                        </span>

                        <strong class="text-warning">
                            <?php echo $totalMenunggu; ?>
                        </strong>

                    </div>


                    <div class="mini-card">

                        <span>
                            Disetujui
                        </span>

                        <strong class="text-success">
                            <?php echo $totalDisetujui; ?>
                        </strong>

                    </div>

                </div>


                <!-- FILTER -->

                <form method="GET" class="filter-area">

                    <div class="filter-item">

                        <label>
                            Status
                        </label>

                        <select name="status">

                            <option value="" <?php echo ($statusFilter === '') ? 'selected' : ''; ?>>
                                Semua Status
                            </option>

                            <option value="menunggu" <?php echo ($statusFilter === 'menunggu') ? 'selected' : ''; ?>>
                                Menunggu Verifikasi
                            </option>

                            <option value="disetujui" <?php echo ($statusFilter === 'disetujui') ? 'selected' : ''; ?>>
                                Disetujui
                            </option>

                            <option value="ditolak" <?php echo ($statusFilter === 'ditolak') ? 'selected' : ''; ?>>
                                Ditolak
                            </option>

                        </select>

                    </div>


                    <div class="filter-item">

                        <label>
                            Cari Mahasiswa
                        </label>

                        <input type="text" name="cari" value="<?php echo htmlspecialchars($cari); ?>"
                            placeholder="Masukkan nama atau NIM...">

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
                                    Mahasiswa
                                </th>

                                <th>
                                    Tagihan
                                </th>

                                <th>
                                    Nominal
                                </th>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Metode
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (count($dataPembayaran) > 0): ?>

                                <?php $no = 1; ?>

                                <?php foreach ($dataPembayaran as $item): ?>

                                    <tr>

                                        <td>
                                            <?php echo $no++; ?>
                                        </td>


                                        <td>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $item['nama_mahasiswa']
                                                );
                                                ?>
                                            </strong>

                                            <br>

                                            <small>
                                                <?php
                                                echo htmlspecialchars(
                                                    $item['nim']
                                                );
                                                ?>
                                            </small>

                                        </td>


                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $item['nama_tagihan']
                                            );
                                            ?>
                                        </td>


                                        <td>

                                            Rp <?php

                                            echo number_format(
                                                $item['nominal_pembayaran'],
                                                0,
                                                ',',
                                                '.'
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo date(
                                                'd M Y',
                                                strtotime(
                                                    $item['tanggal_pembayaran']
                                                )
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $item['nama_metode']
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo tampilStatus(
                                                $item['status_pembayaran']
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <div class="action-buttons">

                                                <?php

                                                echo tampilAksi(
                                                    $item['status_pembayaran'],
                                                    $item['id_pembayaran']
                                                );

                                                ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="8" style="text-align:center;">

                                        Belum ada pembayaran masuk.

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