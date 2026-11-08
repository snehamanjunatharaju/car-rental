<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !isset($_SESSION['name'])) {
    $_SESSION['error'] = "You must login first";
    header('Location: ./index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin - Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="./resources/vendor/fontawesome-free/css/all.css" rel="stylesheet" type="text/css">

    <!-- Page level plugin CSS-->
    <link href="./resources/vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="./resources/css/sb-admin.css" rel="stylesheet">
    <link href="./resources/css/summernote.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

</head>

<body id="page-top">

    <nav class="navbar navbar-expand navbar-dark bg-dark static-top">

        <a class="navbar-brand mr-1" href="./dashboard.php">Admin Dashboard</a>

        <button class="btn btn-link btn-sm text-white order-1 order-sm-0" id="sidebarToggle" href="#">
            <i class="fas fa-bars"></i>
        </button>



        <!-- Navbar -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user-circle fa-fw"></i>
                </a>
                
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="./logout.php" data-toggle="modal"
                        data-target="#logoutModal">Logout</a>
                </div>
            </li>
        </ul>

    </nav>

    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="sidebar navbar-nav">
            <li class="nav-item active">
                <a class="nav-link" href="./dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item font-weight-bold dash-hover">
                <a class="nav-link" href="./bookings.php">
                    <i class="fas fa-fw fa-clock"></i>
                    <span>Bookings</span></a>
            </li>
            <li class="nav-item font-weight-bold dash-hover">
                <a class="nav-link" href="./booking-history.php">
                    <i class="fas fa-fw fa-history"></i>
                    <span>Booking History</span></a>
            </li>
            <li class="nav-item font-weight-bold dash-hover">
                <a class="nav-link" href="./brands.php">
                    <i class="fas fa-fw fa-print"></i>
                    <span>Brands</span></a>
            </li>
            <li class="nav-item font-weight-bold dash-hover">
                <a class="nav-link" href="./cars.php">
                    <i class="fas fa-fw fa-car"></i>
                    <span>Cars</span></a>
            </li>

            <li class="nav-item font-weight-bold dash-hover">
                <a class="nav-link" href="./customers.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Users</span></a>
            </li>

            <li class="nav-item font-weight-bold dash-hover">
                <a class="nav-link" href="./change-password.php">
                    <i class="fas fa-fw fa-edit"></i>
                    <span>Change Password</span></a>
            </li>

        </ul>

        <div id="content-wrapper">