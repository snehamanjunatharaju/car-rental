<?php
include './header.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    echo 'Bad request';
    return;
}

require_once '../src/Database.php';
//$db = Database::getInstance();

$id = $db->real_escape_string($_GET['id']);

$sql = "SELECT * FROM cars WHERE id = '$id'";
$res = $db->query($sql);
$data = $res->fetch_object();
$fileName1 = explode('/', $data->image1)[4];
//print_r($fileName1);die;
$fileName2 = explode('/', $data->image2)[4];
$fileName3 = explode('/', $data->image3)[4];

$sql = "SELECT * FROM brands WHERE id = '$data->brand'";
$res = $db->query($sql);
$brand = $res->fetch_object()->brand_name;


?>
<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active"><a href="">Dashboard</a> / View Car </li>
    </ol>
    <div class="card">
        <div class="card-header">
            Car details
        </div>
        <div class="card-body">
            <table class="table tabled-bordered">
                <tr>
                    <td><strong>Car Name: </strong><?php echo htmlspecialchars($data->car_name) ?></td>
                    <td><strong>Brand: </strong><?php echo htmlspecialchars($brand) ?></td>

                </tr>
                <tr>
                    <td><strong>Transmission.: </strong><?php echo htmlspecialchars($data->transmission) ?></td>
                    <td><strong>Fuel Type: </strong><?php echo htmlspecialchars($data->fuel_type) ?></td>
                    <td><strong>Model: </strong><?php echo htmlspecialchars($data->model) ?></td>

                </tr>
                <tr>
                    <td colspan="3"><strong>Description: </strong> <?php echo htmlspecialchars($data->description) ?></td>
                </tr>
                <tr>
                    <td><strong>Km Driven.: </strong><?php echo htmlspecialchars($data->km_driven) ?></td>
                    <td><strong>Registration No: </strong><?php echo htmlspecialchars($data->reg_no) ?></td>

                </tr>
                <tr>
                    <td><strong>Car Image1 : </strong></td>
                    <td><a target="_blank" href="./uploaded-files/cars/<?php echo $fileName1 ?>"><i
                                class="fa fa-file-pdf text-danger"></i> car image 1 </a></td>
                </tr>
                <tr>
                    <td><strong>Car Image2: </strong></td>
                    <td><a target="_blank" href="./uploaded-files/cars/<?php echo $fileName2 ?>"><i
                                class="fa fa-file-pdf text-danger"></i> car image 2</a></td>
                </tr>
                <tr>
                    <td><strong>Car Image3.:
                        </strong><?php echo htmlspecialchars($fileName3) ?>
                    </td>
                    <td><a target="_blank" href="./uploaded-files/cars/<?php echo $fileName2 ?>"><i
                                class="fa fa-file-pdf text-danger"></i> car image 3</a></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php include './footer.php'?>