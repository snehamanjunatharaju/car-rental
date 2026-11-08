<?php
http_response_code(404);
include './header.php';
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Appointment application</li>
    </ol>
    <div class="alert alert-info" role="status">
        Appointment applications are not part of this car-rental demo.
    </div>
    <a class="btn btn-primary" href="./dashboard.php">Back to dashboard</a>
</div>

<?php include './footer.php'; ?>
