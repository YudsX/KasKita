```php
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
| AMBIL ID MAHASISWA
|--------------------------------------------------------------------------
*/

$id_mahasiswa = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_mahasiswa <= 0) {
    header('Location: mahasiswa.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA MAHASISWA
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id_mahasiswa,
        id_user,
        nim,
        nama_mahasiswa,
        no_hp,
        status
    FROM mahasiswa
    WHERE id_mahasiswa = ?
");

$stmt->bind_param("i", $id_mahasiswa);
$stmt->execute();

$result = $stmt->get_result();
$mahasiswa = $result->fetch_assoc();

if (!$mahasiswa) {
    header('Location: mahasiswa.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| PROSES UPDATE
|--------------------------------------------------------------------------
*/

$pesan = '';
$jenisPesan = '';

$nim = $mahasiswa['nim'];
$nama_mahasiswa = $mahasiswa['nama_mahasiswa'];
$no_hp = $mahasiswa['no_hp'];
$status = $mahasiswa['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nim = trim($_POST['nim'] ?? '');
    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $status = $_POST['status'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (
        $nim === '' ||
        $nama_mahasiswa === '' ||
        $no_hp === '' ||
        $status === ''
    ) {

        $pesan = 'Semua data wajib diisi.';
        $jenisPesan = 'error';

    } else {

        /*
        |--------------------------------------------------------------------------
        | CEK NIM
        |--------------------------------------------------------------------------
        | NIM tidak boleh sama dengan mahasiswa lain.
        */

        $stmtCek = $conn->prepare("
            SELECT id_mahasiswa
            FROM mahasiswa
            WHERE nim = ?
              AND id_mahasiswa != ?
        ");

        $stmtCek->bind_param(
            "si",
            $nim,
            $id_mahasiswa
        );

        $stmtCek->execute();

        $resultCek = $stmtCek->get_result();

        if ($resultCek->num_rows > 0) {

            $pesan = 'NIM sudah digunakan oleh mahasiswa lain.';
            $jenisPesan = 'error';

        } else {

            /*
            |--------------------------------------------------------------------------
            | UPDATE DATA MAHASISWA
            |--------------------------------------------------------------------------
            */

            $stmtUpdate = $conn->prepare("
                UPDATE mahasiswa
                SET
                    nim = ?,
                    nama_mahasiswa = ?,
                    no_hp = ?,
                    status = ?
                WHERE id_mahasiswa = ?
            ");

            $stmtUpdate->bind_param(
                "ssssi",
                $nim,
                $nama_mahasiswa,
                $no_hp,
                $status,
                $id_mahasiswa
            );

            if ($stmtUpdate->execute()) {

                /*
                |--------------------------------------------------------------------------
                | UPDATE DATA USER
                |--------------------------------------------------------------------------
                | Nama dan username pada tabel users ikut disesuaikan.
                */

                $stmtUser = $conn->prepare("
                    UPDATE users
                    SET
                        username = ?,
                        nama = ?
                    WHERE id_user = ?
                ");

                $stmtUser->bind_param(
                    "ssi",
                    $nim,
                    $nama_mahasiswa,
                    $mahasiswa['id_user']
                );

                $stmtUser->execute();


                /*
                |--------------------------------------------------------------------------
                | JIKA MAHASISWA DIUBAH MENJADI AKTIF
                |--------------------------------------------------------------------------
                | Berikan semua tagihan aktif yang belum dimiliki mahasiswa.
                */

                if ($status === 'Aktif') {

                    $stmtTagihan = $conn->prepare("
                        INSERT INTO tagihan_mahasiswa
                        (
                            id_mahasiswa,
                            id_tagihan,
                            status_tagihan
                        )
                        SELECT
                            ?,
                            tk.id_tagihan,
                            'Belum Dibayar'
                        FROM tagihan_kas tk
                        WHERE tk.status_tagihan = 'aktif'
                          AND NOT EXISTS (
                              SELECT 1
                              FROM tagihan_mahasiswa tm
                              WHERE tm.id_mahasiswa = ?
                                AND tm.id_tagihan = tk.id_tagihan
                          )
                    ");

                    $stmtTagihan->bind_param(
                        "ii",
                        $id_mahasiswa,
                        $id_mahasiswa
                    );

                    $stmtTagihan->execute();
                }


                header('Location: mahasiswa.php?status=diubah');
                exit;

            } else {

                $pesan = 'Data mahasiswa gagal diubah: ' . $stmtUpdate->error;
                $jenisPesan = 'error';
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

    <title>Edit Mahasiswa | KasKita</title>

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


                    <a href="mahasiswa.php" class="active">

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


                    <a href="transaksi.php">

                        <span class="menu-icon">Rp</span>

                        <span>Transaksi Kas</span>

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

                    <h1>Edit Mahasiswa</h1>

                    <p>
                        Mengubah data mahasiswa yang terdaftar.
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
                            Edit Data Mahasiswa
                        </h2>

                        <p>
                            Silakan ubah data mahasiswa di bawah ini.
                        </p>

                    </div>

                </div>


                <?php if ($pesan !== ''): ?>

                    <div style="
                    margin: 15px 0;
                    padding: 12px 15px;
                    background: #fdecec;
                    color: #b42318;
                    border-radius: 8px;
                ">
                        <?= htmlspecialchars($pesan) ?>
                    </div>

                <?php endif; ?>


                <form method="POST">


                    <!-- NIM -->
                    <div style="margin-bottom: 18px;">

                        <label for="nim">
                            NIM
                        </label>

                        <input type="text" id="nim" name="nim" value="<?= htmlspecialchars($nim) ?>" required>

                    </div>



                    <!-- NAMA -->
                    <div style="margin-bottom: 18px;">

                        <label for="nama_mahasiswa">
                            Nama Mahasiswa
                        </label>

                        <input type="text" id="nama_mahasiswa" name="nama_mahasiswa"
                            value="<?= htmlspecialchars($nama_mahasiswa) ?>" required>

                    </div>



                    <!-- NO HP -->
                    <div style="margin-bottom: 18px;">

                        <label for="no_hp">
                            No. HP
                        </label>

                        <input type="text" id="no_hp" name="no_hp" value="<?= htmlspecialchars($no_hp) ?>" required>

                    </div>



                    <!-- STATUS -->
                    <div style="margin-bottom: 18px;">

                        <label for="status">
                            Status
                        </label>

                        <select id="status" name="status" required>

                            <option value="Aktif" <?= $status === 'Aktif' ? 'selected' : '' ?>
                                >
                                Aktif
                            </option>

                            <option value="Nonaktif" <?= $status === 'Nonaktif' ? 'selected' : '' ?>
                                >
                                Nonaktif
                            </option>

                        </select>

                    </div>



                    <!-- BUTTON -->
                    <div style="
                    display: flex;
                    gap: 10px;
                    margin-top: 25px;
                ">

                        <button type="submit" class="btn-primary">
                            Simpan Perubahan
                        </button>


                        <a href="mahasiswa.php" class="btn-edit" style="
                            display: inline-flex;
                            align-items: center;
                            text-decoration: none;
                        ">
                            Batal
                        </a>

                    </div>

                </form>

            </section>


        </main>

    </div>

</body>

</html>
```