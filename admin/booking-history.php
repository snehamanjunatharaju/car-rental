<?php
include './header.php';
require_once '../src/Database.php';



$sql = "SELECT * FROM booking WHERE booking_status IN ('Completed', 'Cancelled') ORDER BY id DESC";


$res = $db->query($sql);
$bookings = [];
while ($row = $res->fetch_object()) {
    $bookings[] = $row;
}

?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active"><a href="./dashboard.php">Dashboard</a> / Booking History</li>
    </ol>

    <!-- DataTables Example -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-table"></i>
            Booking History Table
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Sl. No.</th>
                            <th>Customer Name</th>
                            <th>Car </th>
                            <th>Start Trip Date</th>
                            <th>Status</th>
                            <th width="70px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking->id) ?></td>
                            <td>

                                <?php
                                 
                                    $customerId = $booking->customer; 
                                   
                                    $sql = "SELECT name FROM customers WHERE id = '$customerId'";
                                    $result = $db->query($sql);
                                    if ($result->num_rows > 0) {
                                        $customerName = $result->fetch_assoc()['name'];
                                        echo htmlspecialchars($customerName);
                                    } else {
                                        echo 'Customer not found';
                                    }
                                    ?>

                            </td>
                            <td>


                                <?php
                                 
                                 $carId = $booking->car; 
                                
                                 $sql = "SELECT car_name FROM cars WHERE id = '$carId'";
                                 $result = $db->query($sql);
                                 if ($result->num_rows > 0) {
                                     $carName = $result->fetch_assoc()['car_name'];
                                     echo htmlspecialchars($carName);
                                 } else {
                                     echo 'Car not found';
                                 }
                                 ?>



                            </td>
                            <td><?php echo htmlspecialchars($booking->start_date) ?></td>
                            <?php $booking->booking_status == 'Booked' ? $labelColor =  'success' : $labelColor = 'info' ?>
                            <td><span
                                    class="badge badge-<?php echo $labelColor ?>"><?php echo $booking->booking_status ?></span>
                            </td>
                            <td>

                                <a href="./view-booking.php?id=<?php echo $booking->id ?>"
                                    class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                                <a href="<?php echo $_SERVER['PHP_SELF'] ?>?delete=<?php echo $booking->id ?>"
                                    onclick="return confirm('Are you sure want to delete this?')" href=""
                                    class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->


<?php
include './footer.php';
?>