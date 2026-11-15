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
| CARI ID USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id_user
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

$id_user = $mahasiswa['id_user'];


/*
|--------------------------------------------------------------------------
| HAPUS DATA
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | HAPUS TAGIHAN MAHASISWA
    |--------------------------------------------------------------------------
    */

    $stmtTagihan = $conn->prepare("
        DELETE FROM tagihan_mahasiswa
        WHERE id_mahasiswa = ?
    ");

    $stmtTagihan->bind_param("i", $id_mahasiswa);
    $stmtTagihan->execute();


    /*
    |--------------------------------------------------------------------------
    | HAPUS DATA MAHASISWA
    |--------------------------------------------------------------------------
    */

    $stmtMahasiswa = $conn->prepare("
        DELETE FROM mahasiswa
        WHERE id_mahasiswa = ?
    ");

    $stmtMahasiswa->bind_param("i", $id_mahasiswa);
    $stmtMahasiswa->execute();


    /*
    |--------------------------------------------------------------------------
    | HAPUS USER
    |--------------------------------------------------------------------------
    */

    $stmtUser = $conn->prepare("
        DELETE FROM users
        WHERE id_user = ?
    ");

    $stmtUser->bind_param("i", $id_user);
    $stmtUser->execute();


    /*
    |--------------------------------------------------------------------------
    | SIMPAN PERUBAHAN
    |--------------------------------------------------------------------------
    */

    $conn->commit();

    header('Location: mahasiswa.php?status=dihapus');
    exit;

} catch (Exception $e) {

    $conn->rollback();

    die('Data mahasiswa gagal dihapus: ' . $e->getMessage());
}
