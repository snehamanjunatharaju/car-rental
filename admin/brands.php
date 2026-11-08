<?php
include './header.php';
require_once '../src/Database.php';

//$db = Database::getInstance();

if (isset($_GET['delete'])) {
    $id = $db->real_escape_string($_GET['delete']);
    $sql = "SELECT * from brands WHERE id = '$id'";
    $res = $db->query($sql);

    if ($res->num_rows < 1) {
        header('Location:' . $_SERVER['PHP_SELF']);
        exit();
    }

    $sql = "DELETE FROM brands WHERE id = '$id'";
    if ($db->query($sql) === true) {
        $msg = "Brand deleted";
    } else {
        $error = "Can not delete brand";
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


$sql = "SELECT * FROM brands ORDER BY id DESC";
$res = $db->query($sql);
$brands = [];
while ($row = $res->fetch_object()) {
    $brands[] = $row;
}

?>

<div class="container-fluid">
   <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active"><a href="">Dashboard</a>  /  Brands</li>
  </ol>
 <?php if (isset($error) && strlen($error) > 1): ?>
    <div class="alert alert-danger" role="alert">
           <?php echo $error; ?>
    </div>
<?php endif?>
 <?php if (isset($msg) && strlen($msg) > 1): ?>
  <div class="alert alert-success" role="alert">
  <strong><i class="fas fa-check"></i> Success! </strong>
     <?php echo $msg; ?>
  </div>
<?php endif?>


<div class="row">
        <div class="col-lg-12 text-right mb-2">
            <a class="btn btn-primary font-weight-bold" href="./add-brand.php"><i class="fas fa-plus"></i> Add brand</a>
        </div>
    </div>
 <!-- DataTables Example -->
 <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-table"></i>
            Brands</div>
        <div class="card-body">


            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>sl.no</th>
                            <th width="20%">Name</th>
                         
                            <th width="20%">Status</th>
                            <th width="35%">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php  $i = 0; foreach ($brands as $brand) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($i+1) ?></td>
                            <td><?php echo htmlspecialchars($brand->brand_name) ?></td>
                         
                            <td><?php echo htmlspecialchars($brand->status) ?></td>
                            <td>
                                <a href="" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                                <a href="" class="btn btn-success btn-sm"><i class="fa fa-edit"></i></a>

                                <a onclick = "return confirm('Are you sure to delete brand data?')" href="<?php echo $_SERVER['PHP_SELF'] ?>?delete=<?php echo $brand->id ?>" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php $i++;endforeach; ?>
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
