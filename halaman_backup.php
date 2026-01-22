<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Data User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="btn btn-secondary btn-sm rounded-circle" href="index.php"><i class="fas fa-arrow-left"></i></a>
            <span class="navbar-brand mb-0 h1 fs-6 fw-bold mx-auto">Backup & Restore</span>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body text-center p-5">
                        
                        <div class="mb-4">
                            <i class="fas fa-cloud-download-alt text-success" style="font-size: 80px;"></i>
                        </div>

                        <h4 class="fw-bold mb-3">Backup Data Pelanggan</h4>
                        <p class="text-muted mb-4">
                            Fitur ini akan mengunduh semua username dan password 
                            <strong>PPPoE</strong> dan <strong>Hotspot</strong> dari MikroTik 
                            ke dalam format Excel (CSV).
                        </p>

                        <div class="alert alert-warning text-start small">
                            <i class="fas fa-exclamation-triangle me-1"></i> <strong>Penting:</strong>
                            <ul class="mb-0 ps-3">
                                <li>File ini berisi password sensitif. Simpan di tempat aman.</li>
                                <li>Gunakan file ini untuk restore jika Router di-reset.</li>
                            </ul>
                        </div>

                        <div class="d-grid mt-4">
                            <a href="proses_backup.php" class="btn btn-success btn-lg fw-bold shadow-sm">
                                <i class="fas fa-file-csv me-2"></i> DOWNLOAD BACKUP (.CSV)
                            </a>
                        </div>
                        
                        <div class="mt-3">
                            <a href="index.php" class="text-decoration-none text-secondary small">Kembali ke Dashboard</a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>