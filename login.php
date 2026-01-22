<?php
session_start();
include 'koneksi.php';

// 1. CEK APAKAH SUDAH LOGIN? (Kalau sudah, lempar ke dashboard)
if(isset($_SESSION['status']) && $_SESSION['status'] == "login"){
    header("location:index.php");
    exit();
}

$pesan_error = "";

// 2. PROSES LOGIN SAAT TOMBOL DITEKAN
if(isset($_POST['btn_login'])){
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Cek Database Admin
    $cek = mysqli_query($koneksi, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
    
    if(mysqli_num_rows($cek) > 0){
        $data = mysqli_fetch_assoc($cek);
        
        // Set Session
        $_SESSION['username'] = $username;
        $_SESSION['nama'] = $data['nama_lengkap'];
        $_SESSION['status'] = "login";
        
        header("location:index.php");
    } else {
        $pesan_error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-login {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px; /* Lebar maksimal di PC */
        }
        .login-header {
            background: #fff;
            padding: 30px 20px 10px;
            text-align: center;
        }
        .logo-icon {
            width: 70px;
            height: 70px;
            background: #e7f1ff;
            color: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 15px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .form-control:focus {
            background: #fff;
            box-shadow: none;
            border-color: #0d6efd;
        }
        .btn-login {
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
            transition: 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>

    <div class="container p-3">
        <div class="card card-login mx-auto">
            
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-wifi"></i>
                </div>
                <h4 class="fw-bold text-dark">AwraNet Admin</h4>
                <p class="text-muted small">Silakan login untuk mengelola sistem</p>
            </div>

            <div class="card-body p-4 pt-2">
                
                <?php if($pesan_error != ""){ ?>
                    <div class="alert alert-danger text-center p-2 small mb-3">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $pesan_error; ?>
                    </div>
                <?php } ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" name="username" class="form-control border-start-0" placeholder="Masukan username" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="Masukan password" required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="btn_login" class="btn btn-primary btn-login">
                            MASUK <i class="fas fa-sign-in-alt ms-2"></i>
                        </button>
                    </div>
                </form>

            </div>
            <div class="card-footer bg-light text-center py-3 border-0">
                <small class="text-muted">&copy; <?php echo date('Y'); ?> AwraNet</small>
            </div>
        </div>
    </div>

</body>
</html>