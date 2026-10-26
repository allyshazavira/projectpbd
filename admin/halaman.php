<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Apotek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-3">
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark rounded px-3">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Apotek Apps</a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="offcanvas offcanvas-end text-dark" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu Apotek</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                            <li class="nav-item">
                                <a class="nav-link active text-dark" aria-current="page" href="index.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="barang.php">Data Barang</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="user.php">Data User</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="vendor.php">Data Vendor</a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </nav>
    </header>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html> 