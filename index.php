<?php
/**
 * SISTEM INFORMASI TARIF PELAYARAN LENGKAP
 * Dibuat dengan PHP Native + MySQL + Bootstrap 5
 * 
 * Pastikan XAMPP/WAMP sudah aktif
 * Buat database dengan nama: db_pelayaran
 */

// Konfigurasi Database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_pelayaran';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Koneksi Database Gagal: " . $e->getMessage());
}

// Start Session
if(session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ======================
// HELPER FUNCTIONS
// ======================
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    return date('d-m-Y H:i', strtotime($tanggal));
}

function generateResi() {
    return 'SHP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['level']) && $_SESSION['level'] == 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// Get Page Parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$msg_type = '';

// ======================
// HANDLE POST ACTIONS
// ======================
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // LOGIN
    if($action == 'login') {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['level'] = $user['level'];
            $msg = "Login berhasil!";
            $msg_type = "success";
            redirect('index.php?page=home');
        } else {
            $msg = "Username atau password salah!";
            $msg_type = "danger";
        }
    }
    
    // REGISTER
    if($action == 'register') {
        $username = sanitize($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $nama = sanitize($_POST['nama']);
        $email = sanitize($_POST['email']);
        $no_telp = sanitize($_POST['no_telp']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, no_telp, level) VALUES (?, ?, ?, ?, ?, 'user')");
            $stmt->execute([$username, $password, $nama, $email, $no_telp]);
            $msg = "Registrasi berhasil! Silakan login.";
            $msg_type = "success";
        } catch(PDOException $e) {
            $msg = "Username sudah terdaftar!";
            $msg_type = "danger";
        }
    }
    
    // TAMBAH TARIF (ADMIN)
    if($action == 'add_rate' && isAdmin()) {
        $id_rute = (int)$_POST['id_rute'];
        $jenis_muatan = sanitize($_POST['jenis_muatan']);
        $harga_per_kg = (float)$_POST['harga_per_kg'];
        $harga_minimum = (float)$_POST['harga_minimum'];
        
        $stmt = $pdo->prepare("INSERT INTO rates (id_rute, jenis_muatan, harga_per_kg, harga_minimum) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_rute, $jenis_muatan, $harga_per_kg, $harga_minimum]);
        $msg = "Tarif berhasil ditambahkan!";
        $msg_type = "success";
    }
    
    // TAMBAH PELABUHAN (ADMIN)
    if($action == 'add_port' && isAdmin()) {
        $nama = sanitize($_POST['nama']);
        $lokasi = sanitize($_POST['lokasi']);
        $kode = sanitize($_POST['kode']);
        
        $stmt = $pdo->prepare("INSERT INTO ports (nama_pelbrecht, lokasi, kode_port) VALUES (?, ?, ?)");
        $stmt->execute([$nama, $lokasi, $kode]);
        $msg = "Pelabuhan berhasil ditambahkan!";
        $msg_type = "success";
    }
    
    // TAMBAH RUTE (ADMIN)
    if($action == 'add_route' && isAdmin()) {
        $asal = (int)$_POST['asal'];
        $tujuan = (int)$_POST['tujuan'];
        $jarak = (int)$_POST['jarak'];
        $lama = (int)$_POST['lama'];
        
        $stmt = $pdo->prepare("INSERT INTO routes (id_pelabuuhan_asal, id_pelanjutan_tujuan, jarak_km, lama_perjalanan_jam) VALUES (?, ?, ?, ?)");
        $stmt->execute([$asal, $tujuan, $jarak, $lama]);
        $msg = "Rute berhasil ditambahkan!";
        $msg_type = "success";
    }
    
    // TAMBAH JADWAL (ADMIN)
    if($action == 'add_schedule' && isAdmin()) {
        $id_kapal = (int)$_POST['id_kapal'];
        $id_rute = (int)$_POST['id_rute'];
        $berangkat = sanitize($_POST['berangkat']);
        $datang = sanitize($_POST['datang']);
        $harga = (float)$_POST['harga'];
        $kapasitas = (int)$_POST['kapasitas'];
        
        $stmt = $pdo->prepare("INSERT INTO schedules (id_kapal, id_rute, tgl_keberangkatan, tgl_kedatangan, harga_tiket, kapasitas_kursi) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_kapal, $id_rute, $berangkat, $datang, $harga, $kapasitas]);
        $msg = "Jadwal berhasil ditambahkan!";
        $msg_type = "success";
    }
    
    // TAMBAH USER (ADMIN)
    if($action == 'add_user' && isAdmin()) {
        $username = sanitize($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $nama = sanitize($_POST['nama']);
        $email = sanitize($_POST['email']);
        $level = sanitize($_POST['level']);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, level) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $nama, $email, $level]);
        $msg = "User berhasil ditambahkan!";
        $msg_type = "success";
    }
    
    // DELETE DATA (ADMIN)
    if($action == 'delete' && isAdmin()) {
        $table = sanitize($_POST['table']);
        $delete_id = (int)$_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$delete_id]);
        $msg = "Data berhasil dihapus!";
        $msg_type = "success";
    }
    
    // UPDATE TARIF (ADMIN)
    if($action == 'update_rate' && isAdmin()) {
        $id_rate = (int)$_POST['id'];
        $jenis_muatan = sanitize($_POST['jenis_muatan']);
        $harga_per_kg = (float)$_POST['harga_per_kg'];
        $harga_minimum = (float)$_POST['harga_minimum'];
        
        $stmt = $pdo->prepare("UPDATE rates SET jenis_muatan=?, harga_per_kg=?, harga_minimum=? WHERE id=?");
        $stmt->execute([$jenis_muatan, $harga_per_kg, $harga_minimum, $id_rate]);
        $msg = "Tarif berhasil diupdate!";
        $msg_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Tarif Pelayaran Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --accent: #198754;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            padding: 100px 0;
            color: white;
            margin-bottom: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            border-radius: 25px;
            padding: 10px 30px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 40px 0;
            margin-top: 50px;
        }
        .sidebar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .sidebar a {
            display: block;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: var(--primary);
            color: white;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #0d6efd; color: #fff; }
        .status-in_transit { background: #6c757d; color: #fff; }
        .status-delivered { background: #198754; color: #fff; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php">
            <i class="fas fa-ship"></i> ShippingIndo
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=home">🏠 Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=search-rate">💰 Cari Tarif</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=schedules">🚢 Jadwal Kapal</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=tracking">📦 Tracking</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=simulation">🧮 Simulasi</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=contact">📞 Kontak</a></li>
                
                <?php if(isLoggedIn()): ?>
                    <?php if(isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="index.php?page=admin">⚙️ Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown">
                            👤 <?= $_SESSION['nama']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=logout">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-warning btn-sm" href="index.php?page=login">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Alert Message -->
<?php if($msg): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show">
        <i class="fas fa-<?= $msg_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
        <?= $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- MAIN CONTENT AREA -->
<main class="container my-4">

<?php
// =====================
// ROUTING PAGES
// =====================

// ===== HOME =====
if($page == 'home'):
?>
<div class="hero text-center rounded-3">
    <div class="container">
        <h1 class="display-3 fw-bold">Layanan Ekspor & Impor</h1>
        <p class="lead fs-4">Terbaik, Terpercaya & Terjamin Seluruh Indonesia</p>
        <a href="index.php?page=search-rate" class="btn btn-warning btn-lg mt-3">
            <i class="fas fa-search"></i> Cek Tarif Sekarang
        </a>
    </div>
</div>

<div class="row text-center">
    <div class="col-md-3 mb-4">
        <div class="card h-100 p-4">
            <i class="fas fa-search-dollar feature-icon"></i>
            <h5>Pencarian Tarif</h5>
            <p class="text-muted">Cari tarif berdasarkan pelabuhan asal, tujuan & jenis muatan</p>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 p-4">
            <i class="fas fa-ship feature-icon"></i>
            <h5>Jadwal Kapal</h5>
            <p class="text-muted">Lihat jadwal keberangkatan & kedatangan kapal</p>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 p-4">
            <i class="fas fa-map-marker-alt feature-icon"></i>
            <h5>Tracking Pengiriman</h5>
            <p class="text-muted">Lacak posisi paket dengan nomor resi</p>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 p-4">
            <i class="fas fa-calculator feature-icon"></i>
            <h5>Simulasi Biaya</h5>
            <p class="text-muted">Hitung estimasi biaya pengiriman</p>
        </div>
    </div>
</div>

<div class="
