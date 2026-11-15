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

$id_pembayaran = $_GET['id_pembayaran'] ?? 0;

if ($id_pembayaran == 0) {
    die("ID pembayaran tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| PROSES VERIFIKASI
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | SETUJUI PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    if ($aksi === 'setujui') {

        $conn->begin_transaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | AMBIL DATA PEMBAYARAN
            |--------------------------------------------------------------------------
            */

            $stmtData = $conn->prepare("
                SELECT
                    p.id_pembayaran,
                    p.nominal_pembayaran,
                    p.tanggal_pembayaran,
                    p.status,
                    p.status_pembayaran,
                    m.nama_mahasiswa,
                    tk.nama_tagihan

                FROM pembayaran p

                JOIN tagihan_mahasiswa tm
                    ON p.id_tagihan_mahasiswa = tm.id_tagihan_mahasiswa

                JOIN mahasiswa m
                    ON tm.id_mahasiswa = m.id_mahasiswa

                JOIN tagihan_kas tk
                    ON tm.id_tagihan = tk.id_tagihan

                WHERE p.id_pembayaran = ?
            ");

            $stmtData->bind_param("i", $id_pembayaran);
            $stmtData->execute();

            $resultData = $stmtData->get_result();
            $dataPembayaran = $resultData->fetch_assoc();

            if (!$dataPembayaran) {
                throw new Exception("Data pembayaran tidak ditemukan.");
            }


            /*
            |--------------------------------------------------------------------------
            | CEK STATUS
            |--------------------------------------------------------------------------
            */

            if (
                $dataPembayaran['status_pembayaran'] === 'disetujui' ||
                $dataPembayaran['status'] === 'disetujui'
            ) {
                throw new Exception(
                    "Pembayaran ini sudah disetujui sebelumnya."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS PEMBAYARAN
            |--------------------------------------------------------------------------
            */

            $stmtUpdate = $conn->prepare("
                UPDATE pembayaran
                SET
                    status = 'disetujui',
                    status_pembayaran = 'disetujui'
                WHERE id_pembayaran = ?
            ");

            $stmtUpdate->bind_param("i", $id_pembayaran);

            if (!$stmtUpdate->execute()) {
                throw new Exception(
                    "Gagal menyetujui pembayaran: "
                    . $stmtUpdate->error
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CEK TRANSAKSI KAS
            |--------------------------------------------------------------------------
            */

            $stmtCek = $conn->prepare("
                SELECT id_transaksi
                FROM transaksi_kas
                WHERE id_pembayaran = ?
                LIMIT 1
            ");

            $stmtCek->bind_param("i", $id_pembayaran);
            $stmtCek->execute();

            $resultCek = $stmtCek->get_result();


            /*
            |--------------------------------------------------------------------------
            | MASUKKAN KE TRANSAKSI KAS JIKA BELUM ADA
            |--------------------------------------------------------------------------
            */

            if ($resultCek->num_rows === 0) {

                $keterangan =
                    'Pembayaran ' .
                    $dataPembayaran['nama_tagihan'] .
                    ' - ' .
                    $dataPembayaran['nama_mahasiswa'];

                $stmtTransaksi = $conn->prepare("
                    INSERT INTO transaksi_kas
                    (
                        id_pembayaran,
                        nominal_transaksi,
                        tanggal_transaksi,
                        jenis_transaksi,
                        keterangan
                    )
                    VALUES (?, ?, ?, 'pemasukan', ?)
                ");

                $stmtTransaksi->bind_param(
                    "idss",
                    $id_pembayaran,
                    $dataPembayaran['nominal_pembayaran'],
                    $dataPembayaran['tanggal_pembayaran'],
                    $keterangan
                );

                if (!$stmtTransaksi->execute()) {
                    throw new Exception(
                        "Pembayaran disetujui, tetapi gagal masuk ke transaksi kas: "
                        . $stmtTransaksi->error
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SIMPAN PERUBAHAN
            |--------------------------------------------------------------------------
            */

            $conn->commit();

            header('Location: pembayaran.php?status=disetujui');
            exit;

        } catch (Exception $e) {

            $conn->rollback();

            die($e->getMessage());
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TOLAK PEMBAYARAN
    |--------------------------------------------------------------------------
    */ elseif ($aksi === 'tolak') {

        $stmt = $conn->prepare("
            UPDATE pembayaran
            SET
                status = 'ditolak',
                status_pembayaran = 'ditolak'
            WHERE id_pembayaran = ?
        ");

        $stmt->bind_param("i", $id_pembayaran);

        if ($stmt->execute()) {

            header('Location: pembayaran.php?status=ditolak');
            exit;

        } else {

            die(
                "Gagal menolak pembayaran: "
                . $stmt->error
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMBAYARAN
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        p.id_pembayaran,
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

    WHERE p.id_pembayaran = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_pembayaran);
$stmt->execute();

$result = $stmt->get_result();

$detail = $result->fetch_assoc();

if (!$detail) {
    die("Data pembayaran tidak ditemukan.");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Periksa Pembayaran | KasKita</title>

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

                    <a href="pembayaran.php" class="active">
                        <span class="menu-icon">▤</span>
                        <span>Pembayaran</span>
                    </a>

                    <a href="transaksi.php">
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


        <!-- MAIN -->

        <main class="main-content">


            <!-- TOPBAR -->

            <header class="topbar">

                <div class="page-title">

                    <h1>
                        Periksa Pembayaran
                    </h1>

                    <p>
                        Periksa bukti pembayaran sebelum melakukan verifikasi.
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
                            Informasi Pembayaran
                        </h2>

                        <p>
                            Periksa data pembayaran dari mahasiswa.
                        </p>

                    </div>


                    <a href="pembayaran.php" class="btn-cancel">
                        ← Kembali
                    </a>

                </div>


                <!-- DETAIL -->

                <div class="form-card">

                    <div class="form-header">

                        <h2>
                            Data Pembayaran
                        </h2>

                        <p>
                            Pembayaran dari
                            <?php
                            echo htmlspecialchars(
                                $detail['nama_mahasiswa']
                            );
                            ?>
                        </p>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label>
                                Nama Mahasiswa
                            </label>

                            <input type="text" value="<?php
                            echo htmlspecialchars(
                                $detail['nama_mahasiswa']
                            );
                            ?>" readonly>

                        </div>


                        <div class="form-group">

                            <label>
                                NIM
                            </label>

                            <input type="text" value="<?php
                            echo htmlspecialchars(
                                $detail['nim']
                            );
                            ?>" readonly>

                        </div>


                        <div class="form-group">

                            <label>
                                Tagihan
                            </label>

                            <input type="text" value="<?php
                            echo htmlspecialchars(
                                $detail['nama_tagihan']
                            );
                            ?>" readonly>

                        </div>


                        <div class="form-group">

                            <label>
                                Nominal
                            </label>

                            <input type="text" value="Rp <?php
                            echo number_format(
                                $detail['nominal_pembayaran'],
                                0,
                                ',',
                                '.'
                            );
                            ?>" readonly>

                        </div>


                        <div class="form-group">

                            <label>
                                Tanggal Pembayaran
                            </label>

                            <input type="text" value="<?php
                            echo date(
                                'd F Y',
                                strtotime(
                                    $detail['tanggal_pembayaran']
                                )
                            );
                            ?>" readonly>

                        </div>


                        <div class="form-group">

                            <label>
                                Metode Pembayaran
                            </label>

                            <input type="text" value="<?php
                            echo htmlspecialchars(
                                $detail['nama_metode']
                            );
                            ?>" readonly>

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Status Pembayaran
                        </label>

                        <input type="text" value="<?php
                        if ($detail['status_pembayaran'] === 'disetujui') {
                            echo 'Disetujui';
                        } elseif ($detail['status_pembayaran'] === 'ditolak') {
                            echo 'Ditolak';
                        } else {
                            echo 'Menunggu Verifikasi';
                        }
                        ?>" readonly>

                    </div>


                    <!-- BUKTI PEMBAYARAN -->

                    <div class="form-group">

                        <label>
                            Bukti Pembayaran
                        </label>

                        <div class="payment-proof">

                            <?php if (!empty($detail['bukti_pembayaran'])): ?>

                                <?php
                                $fileBukti = $detail['bukti_pembayaran'];
                                $pathBukti = '../uploads/' . $fileBukti;
                                ?>

                                <?php if (file_exists($pathBukti)): ?>

                                    <img src="<?php echo htmlspecialchars($pathBukti); ?>" alt="Bukti Pembayaran" style="
                                            max-width: 100%;
                                            max-height: 500px;
                                            border-radius: 8px;
                                            margin-top: 10px;
                                        ">

                                <?php else: ?>

                                    <p>
                                        File bukti pembayaran tidak ditemukan.
                                    </p>

                                <?php endif; ?>

                            <?php else: ?>

                                <p>
                                    <strong>
                                        Tidak ada bukti pembayaran.
                                    </strong>
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- AKSI -->

                    <?php if ($detail['status_pembayaran'] === 'menunggu'): ?>

                        <div class="form-actions">

                            <form method="POST" style="display:inline;">

                                <input type="hidden" name="aksi" value="tolak">

                                <button type="submit" class="btn-cancel"
                                    onclick="return confirm('Yakin ingin menolak pembayaran ini?');">
                                    Tolak
                                </button>

                            </form>


                            <form method="POST" style="display:inline;">

                                <input type="hidden" name="aksi" value="setujui">

                                <button type="submit" class="btn-primary"
                                    onclick="return confirm('Yakin ingin menyetujui pembayaran ini?');">
                                    Setujui Pembayaran
                                </button>

                            </form>

                        </div>

                    <?php else: ?>

                        <div class="form-actions">

                            <?php if ($detail['status_pembayaran'] === 'disetujui'): ?>

                                <p>
                                    Pembayaran ini sudah disetujui.
                                </p>

                            <?php elseif ($detail['status_pembayaran'] === 'ditolak'): ?>

                                <p>
                                    Pembayaran ini sudah ditolak.
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

</body>

</html>