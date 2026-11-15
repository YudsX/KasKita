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

$pesan = '';
$jenisPesan = '';


/*
|--------------------------------------------------------------------------
| DATA MAHASISWA
|--------------------------------------------------------------------------
*/

$id_user = $_SESSION['id_user'];

$stmtMahasiswa = $conn->prepare("
    SELECT
        id_mahasiswa,
        nim,
        nama_mahasiswa
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
$nimMahasiswa = $mahasiswa['nim'];
$namaMahasiswa = $mahasiswa['nama_mahasiswa'];


/*
|--------------------------------------------------------------------------
| AMBIL ID TAGIHAN MAHASISWA
|--------------------------------------------------------------------------
*/

$id_tagihan_mahasiswa = (int)($_GET['id_tagihan_mahasiswa'] ?? 0);


/*
|--------------------------------------------------------------------------
| JIKA ID TIDAK ADA
|--------------------------------------------------------------------------
|
| Form pembayaran harus dibuka melalui tombol Bayar
| atau Bayar Ulang dari halaman Tagihan Saya.
|
*/

if ($id_tagihan_mahasiswa === 0) {

    header('Location: tagihan_mahasiswa.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL TAGIHAN YANG DIPILIH
|--------------------------------------------------------------------------
*/

$stmtTagihan = $conn->prepare("
    SELECT
        tm.id_tagihan_mahasiswa,
        tm.id_tagihan,
        tm.status_tagihan,

        tk.nama_tagihan,
        tk.nominal_tagihan,
        tk.tanggal_tagihan,
        tk.tanggal_jatuh_tempo,
        tk.status_tagihan AS status_tagihan_master

    FROM tagihan_mahasiswa tm

    JOIN tagihan_kas tk
        ON tm.id_tagihan = tk.id_tagihan

    WHERE tm.id_tagihan_mahasiswa = ?
      AND tm.id_mahasiswa = ?
      AND tk.status_tagihan = 'aktif'
");

$stmtTagihan->bind_param(
    "ii",
    $id_tagihan_mahasiswa,
    $id_mahasiswa
);

$stmtTagihan->execute();

$resultTagihan = $stmtTagihan->get_result();
$tagihan = $resultTagihan->fetch_assoc();

if (!$tagihan) {
    die('Tagihan tidak ditemukan atau bukan milik mahasiswa ini.');
}


/*
|--------------------------------------------------------------------------
| CEK PEMBAYARAN TERBARU
|--------------------------------------------------------------------------
*/

$stmtCek = $conn->prepare("
    SELECT
        id_pembayaran,
        status_pembayaran
    FROM pembayaran
    WHERE id_tagihan_mahasiswa = ?
    ORDER BY id_pembayaran DESC
    LIMIT 1
");

$stmtCek->bind_param(
    "i",
    $id_tagihan_mahasiswa
);

$stmtCek->execute();

$resultCek = $stmtCek->get_result();
$pembayaranSebelumnya = $resultCek->fetch_assoc();


/*
|--------------------------------------------------------------------------
| PROSES KIRIM PEMBAYARAN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $metode = $_POST['metode'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';

    if ($metode === '' || $tanggal === '') {

        $pesan = 'Silakan lengkapi metode dan tanggal pembayaran.';
        $jenisPesan = 'error';

    } elseif (
        $pembayaranSebelumnya &&
        $pembayaranSebelumnya['status_pembayaran'] === 'menunggu'
    ) {

        $pesan = 'Pembayaran sebelumnya masih menunggu verifikasi bendahara.';
        $jenisPesan = 'error';

    } elseif (
        $pembayaranSebelumnya &&
        $pembayaranSebelumnya['status_pembayaran'] === 'disetujui'
    ) {

        $pesan = 'Tagihan ini sudah dibayar.';
        $jenisPesan = 'error';

    } else {

        /*
        |--------------------------------------------------------------------------
        | UPLOAD BUKTI PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        $bukti = null;

        if (
            isset($_FILES['bukti']) &&
            $_FILES['bukti']['error'] === UPLOAD_ERR_OK
        ) {

            $namaFile = $_FILES['bukti']['name'];
            $tmpFile = $_FILES['bukti']['tmp_name'];

            $ekstensi = strtolower(
                pathinfo($namaFile, PATHINFO_EXTENSION)
            );

            $ekstensiDiizinkan = [
                'jpg',
                'jpeg',
                'png',
                'pdf'
            ];

            if (!in_array($ekstensi, $ekstensiDiizinkan)) {

                $pesan = 'Format bukti pembayaran tidak diperbolehkan.';
                $jenisPesan = 'error';

            } else {

                $namaBaru = uniqid('bukti_') . '.' . $ekstensi;

                $folderUpload = '../uploads/bukti_pembayaran/';

                if (!is_dir($folderUpload)) {
                    mkdir($folderUpload, 0777, true);
                }

                if (
                    move_uploaded_file(
                        $tmpFile,
                        $folderUpload . $namaBaru
                    )
                ) {

                    $bukti = $namaBaru;

                } else {

                    $pesan = 'Bukti pembayaran gagal diupload.';
                    $jenisPesan = 'error';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SIMPAN PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        if ($pesan === '') {

            $id_metode = 0;

            if ($metode === 'Transfer') {

                $id_metode = 1;

            } elseif ($metode === 'QRIS') {

                $id_metode = 2;

            } elseif ($metode === 'Tunai') {

                $id_metode = 3;
            }


            if ($id_metode === 0) {

                $pesan = 'Metode pembayaran tidak valid.';
                $jenisPesan = 'error';

            } else {

                $stmtPembayaran = $conn->prepare("
                    INSERT INTO pembayaran
                    (
                        id_tagihan_mahasiswa,
                        id_metode,
                        nominal_pembayaran,
                        tanggal_pembayaran,
                        status_pembayaran,
                        bukti_pembayaran,
                        status
                    )
                    VALUES (?, ?, ?, ?, 'menunggu', ?, 'menunggu')
                ");

                $stmtPembayaran->bind_param(
                    "iidss",
                    $id_tagihan_mahasiswa,
                    $id_metode,
                    $tagihan['nominal_tagihan'],
                    $tanggal,
                    $bukti
                );


                if ($stmtPembayaran->execute()) {

                    $pesan = 'Pembayaran berhasil dikirim dan sedang menunggu verifikasi bendahara.';
                    $jenisPesan = 'success';

                    /*
                    | Status terbaru langsung menjadi menunggu.
                    */

                    $pembayaranSebelumnya = [
                        'status_pembayaran' => 'menunggu'
                    ];

                } else {

                    $pesan = 'Pembayaran gagal disimpan: ' . $stmtPembayaran->error;
                    $jenisPesan = 'error';
                }
            }
        }
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

                <a href="dashboard_mahasiswa.php">

                    <span class="menu-icon">⌂</span>

                    <span>
                        Dashboard
                    </span>

                </a>


                <a href="tagihan_mahasiswa.php">

                    <span class="menu-icon">▣</span>

                    <span>
                        Tagihan Saya
                    </span>

                </a>


                <!--
                    Form pembayaran harus dibuka
                    melalui tagihan yang dipilih.
                -->

                <a href="tagihan_mahasiswa.php">

                    <span class="menu-icon">▤</span>

                    <span>
                        Pembayaran
                    </span>

                </a>


                <a href="riwayat_pembayaran.php">

                    <span class="menu-icon">▥</span>

                    <span>
                        Riwayat Pembayaran
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

                    <span class="menu-icon">♙</span>

                    <span>
                        Profil
                    </span>

                </a>

            </nav>

        </div>


        <div class="sidebar-bottom">

            <a href="../index.php" class="logout">

                <span class="menu-icon">↪</span>

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
                    Lakukan pembayaran tagihan kas kamu
                </p>

            </div>


            <div class="profile">

                <div class="profile-icon">
                    M
                </div>

                <div class="profile-info">

                    <strong>
                        <?php
                        echo htmlspecialchars($namaMahasiswa);
                        ?>
                    </strong>

                    <span>
                        Mahasiswa
                    </span>

                </div>

            </div>

        </header>


        <!-- PESAN -->

        <?php if ($pesan !== ''): ?>

            <?php if ($jenisPesan === 'success'): ?>

                <div class="success-message">
                    <?php
                    echo htmlspecialchars($pesan);
                    ?>
                </div>

            <?php else: ?>

                <div class="error-message">
                    <?php
                    echo htmlspecialchars($pesan);
                    ?>
                </div>

            <?php endif; ?>

        <?php endif; ?>


        <!-- CONTENT -->

        <section class="dashboard-grid payment-form-grid">


            <!-- DETAIL TAGIHAN -->

            <div class="dashboard-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Detail Tagihan
                        </h3>

                        <p>
                            Tagihan yang akan dibayar
                        </p>

                    </div>

                </div>


                <div class="cash-info">


                    <div class="cash-row">

                        <div>

                            <span class="cash-label">
                                Nama Tagihan
                            </span>

                        </div>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $tagihan['nama_tagihan']
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="cash-row">

                        <div>

                            <span class="cash-label">
                                Nominal
                            </span>

                        </div>

                        <strong>
                            Rp
                            <?php
                            echo number_format(
                                $tagihan['nominal_tagihan'],
                                0,
                                ',',
                                '.'
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="cash-row">

                        <div>

                            <span class="cash-label">
                                Jatuh Tempo
                            </span>

                        </div>

                        <strong>
                            <?php
                            echo date(
                                'd F Y',
                                strtotime(
                                    $tagihan['tanggal_jatuh_tempo']
                                )
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="cash-row total">

                        <div>

                            <span class="cash-label">
                                Status
                            </span>

                        </div>


                        <strong class="expense">

                            <?php

                            if ($pembayaranSebelumnya) {

                                if (
                                    $pembayaranSebelumnya['status_pembayaran']
                                    === 'menunggu'
                                ) {

                                    echo 'Menunggu Verifikasi';

                                } elseif (
                                    $pembayaranSebelumnya['status_pembayaran']
                                    === 'disetujui'
                                ) {

                                    echo 'Sudah Dibayar';

                                } elseif (
                                    $pembayaranSebelumnya['status_pembayaran']
                                    === 'ditolak'
                                ) {

                                    echo 'Ditolak';

                                } else {

                                    echo 'Belum Dibayar';
                                }

                            } else {

                                echo 'Belum Dibayar';

                            }

                            ?>

                        </strong>

                    </div>

                </div>

            </div>


            <!-- FORM PEMBAYARAN -->

            <div class="dashboard-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Form Pembayaran
                        </h3>

                        <p>
                            Isi data pembayaran dengan benar
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="payment-form"
                >


                    <div class="form-group">

                        <label>
                            Metode Pembayaran
                        </label>

                        <select
                            name="metode"
                            required
                        >

                            <option value="">
                                Pilih metode pembayaran
                            </option>

                            <option value="Transfer">
                                Transfer
                            </option>

                            <option value="QRIS">
                                QRIS
                            </option>

                            <option value="Tunai">
                                Tunai
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Nominal Pembayaran
                        </label>

                        <input
                            type="text"
                            value="Rp <?php
                            echo number_format(
                                $tagihan['nominal_tagihan'],
                                0,
                                ',',
                                '.'
                            );
                            ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Tanggal Pembayaran
                        </label>

                        <input
                            type="date"
                            name="tanggal"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Bukti Pembayaran
                        </label>

                        <input
                            type="file"
                            name="bukti"
                            accept=".jpg,.jpeg,.png,.pdf"
                        >

                        <small>
                            Format JPG, PNG, atau PDF.
                        </small>

                    </div>


                    <div class="form-actions">

                        <a
                            href="tagihan_mahasiswa.php"
                            class="btn-cancel"
                        >
                            Batal
                        </a>


                        <?php if (
                            !$pembayaranSebelumnya ||
                            $pembayaranSebelumnya['status_pembayaran'] === 'ditolak'
                        ): ?>

                            <button
                                type="submit"
                                class="btn-primary"
                            >
                                Kirim Pembayaran
                            </button>

                        <?php else: ?>

                            <span style="color:#777;">
                                Pembayaran tidak dapat dikirim saat ini.
                            </span>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

</body>

</html>