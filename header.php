<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
ini_set('display_errors', 0);
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$loggedInUser = isset($_SESSION['user']) && is_object($_SESSION['user'])
  ? $_SESSION['user']
  : null;
$loggedInUserName = isset($loggedInUser->name) && is_string($loggedInUser->name)
  ? trim($loggedInUser->name)
  : '';
$accountLabel = $loggedInUserName !== '' ? $loggedInUserName : 'Account';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Car Rental | Find your next drive</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description"
    content="Explore a curated local fleet, compare per-day rental estimates and find a car for your next trip.">

  <meta http-equiv="Content-Security-Policy" content="frame-src 'self' https://test.payu.in https://secure.payu.in https://maps.google.com https://www.google.com;">

  <!-- open graph meta data -->
  <meta property="og:site_name" content="Car Rental" />
  <meta property="og:title" content="Car Rental | Find your next drive" />
  <meta property="og:type" content="website" />
  <meta property="og:description"
    content="Explore a curated local fleet, compare per-day rental estimates and find a car for your next trip." />

  <meta name="twitter:title" content="Car Rental | Find your next drive">
  <meta name="twitter:description"
    content="Explore a curated local fleet, compare per-day rental estimates and find a car for your next trip.">
  <meta name="twitter:card" content="summary">

  <!-- Favicons -->
  <link href="img/faviconn.png" rel="icon">
  <link href="img/faviconn.png" rel="apple-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,700,700i|Montserrat:300,400,500,700"
    rel="stylesheet">

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.1.2/css/tempusdominus-bootstrap-4.min.css">

  <!-- Bootstrap CSS File -->
  <link href="lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">

  <!-- Libraries CSS Files -->
  <link href="lib/font-awesome/css/font-awesome.min.css" rel="stylesheet">
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">

  <!-- Main Stylesheet File -->
  <link href="css/style.css" rel="stylesheet">
  <link href="css/portfolio.css" rel="stylesheet">

  <!-- =======================================================
    Theme Name: NewBiz
    Theme URL: https://bootstrapmade.com/newbiz-bootstrap-business-template/
    Author: BootstrapMade.com
    License: https://bootstrapmade.com/license/
  ======================================================= -->
</head>

<body class="cr-site">
  <a class="cr-skip-link" href="#main">Skip to content</a>

  <!--==========================
  Header
  ============================-->
  <header id="header" class="fixed-top">
    <div class="container">

      <div class="logo float-left">
        <!-- Uncomment below if you prefer to use an image logo -->
        <!-- <h1 class="text-light"><a href="#header"><span>NewBiz</span></a></h1> -->
        <a href="./index.php" class="scrollto"><img src="img/logo-new.png" alt="Car Rental home" class="img-fluid"></a>
      </div>

      <nav class="main-nav float-right d-none d-lg-block" aria-label="Primary navigation">

        <?php

        if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'] == true):

          ?>
          <ul>
            <li class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>"><a href="./index.php">Home</a></li>
            <li class="<?php echo $currentPage === 'trip-match.php' ? 'active' : ''; ?>"><a href="./trip-match.php">Smart match</a></li>
            <li class="<?php echo $currentPage === 'cars.php' ? 'active' : ''; ?>"><a href="./cars.php">Explore cars</a></li>
            <li class="<?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>"><a href="./contact.php">Contact Us</a></li>

          </ul>
          <ul class="navbar-nav ml-auto">

            <li class="nav-item dropdown no-arrow">
              <a class="nav-link dropdown-toggle cr-account-toggle" href="#" id="userDropdown" role="button"
                aria-label="Account menu for <?php echo htmlspecialchars($accountLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-user-circle fa-fw" aria-hidden="true"></i>
                <span class="cr-account-name"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="./profile.php">Profile</a>
                <a class="dropdown-item" href="./my-bookings.php">My Bookings</a>
                <a class="dropdown-item" href="./logout.php">Logout</a>
              </div>
            </li>
          </ul>
        <?php else: ?>
          <ul>
            <li class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>"><a href="./index.php">Home</a></li>
            <li class="<?php echo $currentPage === 'trip-match.php' ? 'active' : ''; ?>"><a href="./trip-match.php">Smart match</a></li>
            <li class="<?php echo $currentPage === 'cars.php' ? 'active' : ''; ?>"><a href="./cars.php">Explore cars</a></li>
            <li class="<?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>"><a href="./contact.php">Contact Us</a></li>
            <li class="<?php echo $currentPage === 'login.php' ? 'active' : ''; ?>"><a href="./login.php">Login</a></li>
            <li class="<?php echo $currentPage === 'register.php' ? 'active' : ''; ?>"><a href="./register.php">Register</a></li>

          </ul>
        <?php endif ?>


      </nav><!-- .main-nav -->

    </div>
  </header><!-- #header -->