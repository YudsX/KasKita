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
require_once '../classes/Mahasiswa.php';

$db = new Database();
$conn = $db->connect();

$id_user = $_SESSION['id_user'];


/*
|--------------------------------------------------------------------------
| DATA MAHASISWA
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id_mahasiswa,
        nim,
        nama_mahasiswa,
        no_hp,
        status
    FROM mahasiswa
    WHERE id_user = ?
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$result = $stmt->get_result();
$mahasiswa = $result->fetch_assoc();

if (!$mahasiswa) {
    die("Data mahasiswa tidak ditemukan.");
}

$namaMahasiswa = $mahasiswa['nama_mahasiswa'];


/*
|--------------------------------------------------------------------------
| INSTANSIASI OBJECT MAHASISWA
|--------------------------------------------------------------------------
*/

$mahasiswaObj = new Mahasiswa(
    $id_user,
    $mahasiswa['nim'],
    null,
    'mahasiswa',
    $mahasiswa['nama_mahasiswa'],
    $mahasiswa['id_mahasiswa'],
    $mahasiswa['nim'],
    $mahasiswa['nama_mahasiswa'],
    $mahasiswa['no_hp'],
    $mahasiswa['status']
);


/*
|--------------------------------------------------------------------------
| DATA TAGIHAN MAHASISWA
|--------------------------------------------------------------------------
*/

$tagihan = [];

$queryTagihan = "
    SELECT
        tm.id_tagihan_mahasiswa,
        tm.id_tagihan,

        tk.nama_tagihan,
        tk.nominal_tagihan,
        tk.tanggal_tagihan,
        tk.tanggal_jatuh_tempo,

        (
            SELECT p.status_pembayaran
            FROM pembayaran p
            WHERE p.id_tagihan_mahasiswa = tm.id_tagihan_mahasiswa
            ORDER BY p.id_pembayaran DESC
            LIMIT 1
        ) AS status_pembayaran

    FROM tagihan_mahasiswa tm

    JOIN tagihan_kas tk
        ON tm.id_tagihan = tk.id_tagihan

    WHERE tm.id_mahasiswa = ?
      AND tk.status_tagihan = 'aktif'

    ORDER BY tk.tanggal_jatuh_tempo DESC
";

$stmtTagihan = $conn->prepare($queryTagihan);

$stmtTagihan->bind_param(
    "i",
    $mahasiswa['id_mahasiswa']
);

$stmtTagihan->execute();

$resultTagihan = $stmtTagihan->get_result();


while ($row = $resultTagihan->fetch_assoc()) {

    /*
    |--------------------------------------------------------------------------
    | TENTUKAN STATUS TAGIHAN
    |--------------------------------------------------------------------------
    */

    $status = 'Belum Dibayar';

    if ($row['status_pembayaran'] === 'disetujui') {

        $status = 'Sudah Dibayar';

    } elseif ($row['status_pembayaran'] === 'menunggu') {

        $status = 'Menunggu Verifikasi';

    } elseif ($row['status_pembayaran'] === 'ditolak') {

        $status = 'Ditolak';
    }


    $tagihan[] = [
        'id_tagihan_mahasiswa' => $row['id_tagihan_mahasiswa'],
        'id_tagihan' => $row['id_tagihan'],
        'nama' => $row['nama_tagihan'],
        'nominal' => $row['nominal_tagihan'],
        'tanggal_tagihan' => $row['tanggal_tagihan'],
        'jatuh_tempo' => $row['tanggal_jatuh_tempo'],
        'status' => $status
    ];
}


/*
|--------------------------------------------------------------------------
| HITUNG STATISTIK TAGIHAN
|--------------------------------------------------------------------------
*/

$totalTagihan = 0;
$totalTagihanAktif = 0;
$totalBelumDibayar = 0;

foreach ($tagihan as $item) {

    $totalTagihan += $item['nominal'];

    if (
        $item['status'] === 'Belum Dibayar' ||
        $item['status'] === 'Ditolak'
    ) {

        $totalBelumDibayar += $item['nominal'];
        $totalTagihanAktif++;
    }
}


/*
|--------------------------------------------------------------------------
| DATA PEMBAYARAN
|--------------------------------------------------------------------------
*/

$totalPembayaran = 0;
$totalDisetujui = 0;
$jumlahRiwayatPembayaran = 0;


$queryPembayaran = "
    SELECT
        p.id_pembayaran,
        p.id_tagihan_mahasiswa,
        p.nominal_pembayaran,
        p.tanggal_pembayaran,
        p.status,
        p.status_pembayaran,

        tk.nama_tagihan

    FROM pembayaran p

    JOIN tagihan_mahasiswa tm
        ON p.id_tagihan_mahasiswa = tm.id_tagihan_mahasiswa

    JOIN tagihan_kas tk
        ON tm.id_tagihan = tk.id_tagihan

    WHERE tm.id_mahasiswa = ?

    ORDER BY p.id_pembayaran DESC
";

$stmtPembayaran = $conn->prepare($queryPembayaran);

$stmtPembayaran->bind_param(
    "i",
    $mahasiswa['id_mahasiswa']
);

$stmtPembayaran->execute();

$resultPembayaran = $stmtPembayaran->get_result();

$jumlahRiwayatPembayaran = $resultPembayaran->num_rows;


while ($row = $resultPembayaran->fetch_assoc()) {

    if ($row['status_pembayaran'] === 'disetujui') {

        $totalPembayaran += $row['nominal_pembayaran'];
        $totalDisetujui++;
    }
}


/*
|--------------------------------------------------------------------------
| TAGIHAN TERBARU
|--------------------------------------------------------------------------
*/

if (count($tagihan) > 0) {

    $tagihanTerbaru = $tagihan[0];

} else {

    $tagihanTerbaru = null;
}


/*
|--------------------------------------------------------------------------
| DATA TAGIHAN TERBARU
|--------------------------------------------------------------------------
*/

if ($tagihanTerbaru) {

    $namaTagihanTerbaru =
        $tagihanTerbaru['nama'];

    $nominalTagihanTerbaru =
        $tagihanTerbaru['nominal'];

    $jatuhTempoTerbaru =
        $tagihanTerbaru['jatuh_tempo'];

    $statusTagihanTerbaru =
        $tagihanTerbaru['status'];

    $idTagihanMahasiswaTerbaru =
        $tagihanTerbaru['id_tagihan_mahasiswa'];

} else {

    $namaTagihanTerbaru = '-';
    $nominalTagihanTerbaru = 0;
    $jatuhTempoTerbaru = null;
    $statusTagihanTerbaru = '-';
    $idTagihanMahasiswaTerbaru = 0;
}


/*
|--------------------------------------------------------------------------
| STATUS PEMBAYARAN TERBARU
|--------------------------------------------------------------------------
*/

$statusPembayaranTerbaru = $statusTagihanTerbaru;

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard | KasKita
    </title>

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


            <!-- MENU UTAMA -->

            <div class="menu-section">

                <p class="menu-title">
                    MENU UTAMA
                </p>

                <nav class="menu">

                    <a href="dashboard_mahasiswa.php" class="active">

                        <span class="menu-icon">
                            ⌂
                        </span>

                        <span>
                            Dashboard
                        </span>

                    </a>


                    <a href="tagihan_mahasiswa.php">

                        <span class="menu-icon">
                            ▣
                        </span>

                        <span>
                            Tagihan Saya
                        </span>

                    </a>


                    <a href="pembayaran_mahasiswa.php">

                        <span class="menu-icon">
                            ▤
                        </span>

                        <span>
                            Pembayaran
                        </span>

                    </a>


                    <a href="riwayat_pembayaran.php">

                        <span class="menu-icon">
                            ▥
                        </span>

                        <span>
                            Riwayat Pembayaran
                        </span>

                    </a>

                </nav>

            </div>


            <!-- MENU LAINNYA -->

            <div class="menu-section">

                <p class="menu-title">
                    LAINNYA
                </p>

                <nav class="menu">

                    <a href="#">

                        <span class="menu-icon">
                            ♙
                        </span>

                        <span>
                            Profil
                        </span>

                    </a>

                </nav>

            </div>


            <!-- LOGOUT -->

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


        <!-- MAIN CONTENT -->

        <main class="main-content">


            <!-- TOPBAR -->

            <header class="topbar">

                <div class="page-title">

                    <h1>
                        Dashboard
                    </h1>

                    <p>
                        Ringkasan tagihan dan pembayaran kas kelas 3A
                    </p>

                </div>


                <div class="profile">

                    <div class="profile-icon">
                        M
                    </div>

                    <div class="profile-info">

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $mahasiswaObj->getNamaMahasiswa()
                            );

                            ?>

                        </strong>


                        <span>

                            <?php

                            echo htmlspecialchars(
                                $mahasiswaObj->getNim()
                            );

                            ?>

                        </span>

                    </div>

                </div>

            </header>


            <!-- WELCOME -->

            <section class="welcome">

                <div class="welcome-text">

                    <p class="welcome-small">
                        Selamat datang kembali 👋
                    </p>

                    <h2>
                        Pantau tagihan dan pembayaran kas kamu
                    </h2>

                    <p>
                        Lihat tagihan, lakukan pembayaran,
                        dan pantau status pembayaran melalui KasKita.
                    </p>

                </div>

            </section>


            <!-- STATISTIK -->

            <section class="stats">


                <!-- TOTAL TAGIHAN -->

                <div class="stat-card">

                    <div class="stat-icon blue">
                        Rp
                    </div>

                    <div class="stat-content">

                        <span>
                            Total Tagihan
                        </span>

                        <h3>

                            Rp
                            <?php

                            echo number_format(
                                $totalTagihan,
                                0,
                                ',',
                                '.'
                            );

                            ?>

                        </h3>

                        <small>
                            Total tagihan kamu
                        </small>

                    </div>

                </div>


                <!-- TAGIHAN AKTIF -->

                <div class="stat-card">

                    <div class="stat-icon orange">
                        !
                    </div>

                    <div class="stat-content">

                        <span>
                            Tagihan Aktif
                        </span>

                        <h3>
                            <?php echo $totalTagihanAktif; ?>
                        </h3>

                        <small>
                            Tagihan belum dibayar
                        </small>

                    </div>

                </div>


                <!-- STATUS PEMBAYARAN -->

                <div class="stat-card">

                    <div class="stat-icon green">
                        ✓
                    </div>

                    <div class="stat-content">

                        <span>
                            Status Pembayaran
                        </span>

                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $statusPembayaranTerbaru
                            );

                            ?>

                        </h3>

                        <small>
                            Status tagihan terbaru
                        </small>

                    </div>

                </div>


                <!-- RIWAYAT -->

                <div class="stat-card">

                    <div class="stat-icon purple">
                        ▤
                    </div>

                    <div class="stat-content">

                        <span>
                            Riwayat Pembayaran
                        </span>

                        <h3>
                            <?php echo $jumlahRiwayatPembayaran; ?>
                        </h3>

                        <small>
                            Pembayaran tercatat
                        </small>

                    </div>

                </div>

            </section>


            <!-- BAGIAN BAWAH -->

            <section class="dashboard-grid">


                <!-- TAGIHAN TERBARU -->

                <div class="dashboard-card payment-card student-bill-card">

                    <div class="card-header">

                        <div>

                            <h3>
                                Tagihan Terbaru
                            </h3>

                            <p>
                                Tagihan kas yang perlu kamu bayarkan
                            </p>

                        </div>

                        <a href="tagihan_mahasiswa.php">
                            Lihat semua
                        </a>

                    </div>


                    <div class="payment-table student-bill-table">


                        <!-- HEADER -->

                        <div class="table-head">

                            <span>
                                Tagihan
                            </span>

                            <span>
                                Nominal
                            </span>

                            <span>
                                Status
                            </span>

                        </div>


                        <!-- DATA -->

                        <?php if ($tagihanTerbaru): ?>

                            <div class="student-payment-row">

                                <div>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $namaTagihanTerbaru
                                        );

                                        ?>

                                    </strong>


                                    <small>

                                        Jatuh tempo

                                        <?php

                                        echo date(
                                            'd F Y',
                                            strtotime(
                                                $jatuhTempoTerbaru
                                            )
                                        );

                                        ?>

                                    </small>

                                </div>


                                <div>

                                    <strong>

                                        Rp

                                        <?php

                                        echo number_format(
                                            $nominalTagihanTerbaru,
                                            0,
                                            ',',
                                            '.'
                                        );

                                        ?>

                                    </strong>

                                </div>


                                <div>

                                    <?php if (
                                        $statusTagihanTerbaru === 'Sudah Dibayar'
                                    ): ?>

                                        <span class="status approved-status">
                                            Sudah Dibayar
                                        </span>


                                    <?php elseif (
                                        $statusTagihanTerbaru === 'Menunggu Verifikasi'
                                    ): ?>

                                        <span class="status waiting-status">
                                            Menunggu Verifikasi
                                        </span>


                                    <?php elseif (
                                        $statusTagihanTerbaru === 'Ditolak'
                                    ): ?>

                                        <span class="status rejected-status">
                                            Ditolak
                                        </span>


                                    <?php else: ?>

                                        <span class="status waiting-status">
                                            Belum Dibayar
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- BUTTON -->

                            <div class="card-footer">

                                <?php if (
                                    $statusTagihanTerbaru === 'Belum Dibayar' ||
                                    $statusTagihanTerbaru === 'Ditolak'
                                ): ?>

                                    <a href="pembayaran_mahasiswa.php?id_tagihan_mahasiswa=<?= $idTagihanMahasiswaTerbaru ?>"
                                        class="btn-primary">

                                        <?php

                                        echo $statusTagihanTerbaru === 'Ditolak'
                                            ? 'Bayar Ulang'
                                            : 'Bayar Sekarang';

                                        ?>

                                    </a>


                                <?php elseif (
                                    $statusTagihanTerbaru === 'Menunggu Verifikasi'
                                ): ?>

                                    <span style="color:#777;">
                                        Menunggu Verifikasi
                                    </span>


                                <?php else: ?>

                                    <span style="color:#777;">
                                        Selesai
                                    </span>

                                <?php endif; ?>

                            </div>


                        <?php else: ?>

                            <div class="student-payment-row">

                                <div style="
                                grid-column: 1 / -1;
                                text-align:center;
                            ">

                                    <span>
                                        Belum ada tagihan aktif.
                                    </span>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- INFORMASI PEMBAYARAN -->

                <div class="dashboard-card">

                    <div class="card-header">

                        <div>

                            <h3>
                                Informasi Pembayaran
                            </h3>

                            <p>
                                Ringkasan tagihan kamu
                            </p>

                        </div>

                    </div>


                    <div class="cash-info">


                        <!-- TOTAL TAGIHAN -->

                        <div class="cash-row">

                            <div>

                                <span class="cash-label">
                                    Total tagihan
                                </span>

                            </div>

                            <strong>

                                Rp

                                <?php

                                echo number_format(
                                    $totalTagihan,
                                    0,
                                    ',',
                                    '.'
                                );

                                ?>

                            </strong>

                        </div>


                        <!-- STATUS PEMBAYARAN -->

                        <div class="cash-row">

                            <div>

                                <span class="cash-label">
                                    Status pembayaran
                                </span>

                            </div>

                            <strong class="expense">

                                <?php

                                echo htmlspecialchars(
                                    $statusPembayaranTerbaru
                                );

                                ?>

                            </strong>

                        </div>


                        <!-- JATUH TEMPO -->

                        <div class="cash-row total">

                            <div>

                                <span class="cash-label">
                                    Jatuh tempo
                                </span>

                            </div>

                            <strong>

                                <?php if ($jatuhTempoTerbaru): ?>

                                    <?php

                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $jatuhTempoTerbaru
                                        )
                                    );

                                    ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </strong>

                        </div>

                    </div>

                </div>

            </section>


        </main>

    </div>

</body>

</html>