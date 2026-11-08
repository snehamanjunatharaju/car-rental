<?php
include './header.php';
require_once '../src/Database.php';

//$db = Database::getInstance();

if (isset($_GET['delete'])) {
    $id = $db->real_escape_string($_GET['delete']);
    $sql = "SELECT * from cars WHERE id = '$id'";
    $res = $db->query($sql);

    if ($res->num_rows < 1) {
        header('Location:' . $_SERVER['PHP_SELF']);
        exit();
    }

    $sql = "DELETE FROM cars WHERE id = '$id'";
    if ($db->query($sql) === true) {
        $msg = "Car deleted";
    } else {
        $error = "Can not delete car";
    }
}



$sql = "SELECT * FROM cars ORDER BY id DESC";
$res = $db->query($sql);
$cars = [];
while ($row = $res->fetch_object()) {
    $cars[] = $row;
}

?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active"><a href="">Dashboard</a> / Cars</li>
    </ol>
    <?php if (isset($error) && strlen($error) > 1): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php endif ?>
    <?php if (isset($msg) && strlen($msg) > 1): ?>
        <div class="alert alert-success" role="alert">
            <strong><i class="fas fa-check"></i> Success! </strong>
            <?php echo $msg; ?>
        </div>
    <?php endif ?>


    <div class="row">
        <div class="col-lg-12 text-right mb-2">
            <a class="btn btn-primary font-weight-bold" href="./add-car.php"><i class="fas fa-plus"></i> Add Car</a>
        </div>
    </div>
    <!-- DataTables Example -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-table"></i>
            Cars
        </div>
        <div class="card-body">


            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>sl.no</th>
                            <th width="20%">Name</th>
                            <th width="20%">Brand</th>
                            <th width="20%">Model</th>
                            <th width="20%">Transmission</th>
                            <th width="35%">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td></td>
                                <td><?php echo htmlspecialchars($car->car_name) ?></td>
                                <td>
                                    <?php
                                    // Assuming $db is your database connection
                                    $brandId = $car->brand; 
                                   
                                    $sql = "SELECT brand_name FROM brands WHERE id = '$brandId'";
                                    $result = $db->query($sql);
                                    if ($result->num_rows > 0) {
                                        $brandName = $result->fetch_assoc()['brand_name'];
                                        echo htmlspecialchars($brandName);
                                    } else {
                                        echo 'Brand not found';
                                    }
                                    ?>

                                </td>
                               
                                <td><?php echo htmlspecialchars($car->model) ?></td>
                                <td> <?php echo htmlspecialchars($car->transmission) ?></td>
                                <td>
                                    <a href="./view-car.php?id=<?php echo $car->id ?>" class="btn btn-info btn-sm"><i
                                            class="fa fa-eye"></i></a>
                                            <a href="" class="btn btn-success btn-sm"><i class="fa fa-edit"></i></a>

                                    <a onclick="return confirm('Are you sure to delete car data?')"
                                        href="<?php echo $_SERVER['PHP_SELF'] ?>?delete=<?php echo $car->id ?>"
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