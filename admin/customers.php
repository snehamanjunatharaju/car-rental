<?php
include './header.php';
require_once '../src/Database.php';

//$db = Database::getInstance();

if (isset($_GET['delete'])) {
    $id = $db->real_escape_string($_GET['delete']);
    $sql = "SELECT * from orders WHERE id = '$id'";
    $res = $db->query($sql);

    if ($res->num_rows < 1) {
        header('Location:' . $_SERVER['PHP_SELF']);
        exit();
    }

    $sql = "DELETE FROM orders WHERE id = '$id'";
    if ($db->query($sql) === true) {
        $msg = "Order deleted";
    } else {
        $error = "Can not delete order";
    }
}

if (isset($_GET['delivered'])) {
    $id = $db->real_escape_string($_GET['delivered']);
    $sql = "SELECT * from orders WHERE id = '$id'";
    $res = $db->query($sql);

    if ($res->num_rows < 1) {
        header('Location:' . $_SERVER['PHP_SELF']);
        exit();
    }

    $sql = "UPDATE orders SET `status` = 'Delivered' WHERE id = '$id'";
    if ($db->query($sql) === true) {
        $msg = "Order status set to delivered";
    } else {
        $error = "Can not change order status";
    }
}


$sql = "SELECT * FROM customers ORDER BY id DESC";
$res = $db->query($sql);
$customers = [];
while ($row = $res->fetch_object()) {
    $customers[] = $row;
}

?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active"><a href="./dashboard.php">Dashboard</a> / Users</li>
    </ol>
    <?php if (isset($msg)) : ?>
        <div class="alert alert-success">
            <strong><i class="fa fa-check">Success! </i></strong> <?php echo $msg ?>
        </div>
    <?php endif ?>

    <?php if (isset($error)) : ?>
        <div class="alert alert-danger">
            <strong><i class="fa fa-check">Failed! </i></strong> <?php echo $error ?>
        </div>
    <?php endif ?>
    <!-- DataTables Example -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-table"></i>
            User Table</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Sl. No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th width="70px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer) : ?>
                            <tr>
                                <?php $d = new DateTime($customer->created_at); ?>
                                <td><?php echo htmlspecialchars($customer->id) ?></td>
                                <td><?php echo htmlspecialchars($customer->name) ?></td>
                                <td><?php echo htmlspecialchars($customer->email) ?></td>
                                <td><?php echo htmlspecialchars($customer->phone) ?></td>
                                <?php $customer->is_verified == 'Pending' ? $labelColor =  'danger' : $labelColor = 'success' ?>
                                <td><span class="badge badge-<?php echo $labelColor ?>"><?php echo $customer->is_verified ?></span></td>
                                <td>

                                    <a href="./view-customer.php?id=<?php echo $customer->id ?>" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                                    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?delete=<?php echo $customer->id ?>" onclick="return confirm('Are you sure want to delete this?')" href="" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>
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