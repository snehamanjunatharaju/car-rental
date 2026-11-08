<?php
include './header.php';

require_once '../src/Database.php';
//$db = Database::getInstance();


$sql = "SELECT count(id) as total_brands FROM brands";
$res = $db->query($sql);
$total_brands = $res->fetch_object()->total_brands;


$sql = "SELECT count(id) as total_booking FROM booking";
$res = $db->query($sql);
$total_booking = $res->fetch_object()->total_booking;

$sql = "SELECT count(id) as total_car FROM cars";
$res = $db->query($sql);
$total_car = $res->fetch_object()->total_car;

$sql = "SELECT count(id) as total_customer FROM customers";
$res = $db->query($sql);
$total_customer = $res->fetch_object()->total_customer;




?>
<!-- Breadcrumbs-->

<div class="container-fluid">
	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item active"><a href="">Dashboard</a> / overview</li>
	</ol>
	<div class="row">
		<div class="col-xl-3 col-md-6">
			<div class="card bg-primary text-white mb-4">
				<div class="card-body">Total Booking</div>
				<span style="margin-left:40px"><?php echo $total_booking ?></span><br>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="./bookings.php">View Details</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
		<div class="col-xl-3 col-md-6">
			<div class="card bg-warning text-white mb-4">
				<div class="card-body">Total Cars</div>
				<span style="margin-left:40px"><?php echo $total_car ?></span><br>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="./cars.php">View Details</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
		<div class="col-xl-3 col-md-6">
			<div class="card bg-success text-white mb-4">
				<div class="card-body">Customers</div>
				<span style="margin-left:40px"><?php echo $total_customer ?></span><br>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="./customers.php">View Details</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
		<div class="col-xl-3 col-md-6">
			<div class="card bg-danger text-white mb-4">
				<div class="card-body">Brands</div>
				<span style="margin-left:40px"><?php echo $total_brands ?></span><br>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="./brands.php">View Details</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>

	</div>
	<!-- /.container-fluid -->

	<?php
	include './footer.php';
	?>