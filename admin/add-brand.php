<?php
include './header.php';
if (!isset($_SESSION['admin_brand_csrf_token']) || !is_string($_SESSION['admin_brand_csrf_token'])) {
    $_SESSION['admin_brand_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_brand_csrf_token'];

?>

<div id="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="./dashboard.php">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="./brands.php">Brands</a>
            </li>
            <li class="breadcrumb-item active">Add brand</li>
        </ol>
        <?php if (isset($msg)): ?>
        <div class="alert alert-success" role="alert"><?php echo $msg ?></div>
        <?php endif?>
        <!-- Icon Cards-->
        <div class="card">
            <div class="card-header text-uppercase font-weight-bold">
                <i class="fas fa-plus-circle"></i>
                Add Brand
            </div>
            <div class="card-body">
                <form id="formBrand" method="post" action="./src/brand.php"
                                enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <div class="form-row">
                        <div class="form-group col-lg-">
                            <label for="name">Brand Name</label>
                            <input type="text" id="name" class="form-control" name="name"
                                placeholder="Enter brand name">
                                <small id="nameError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="name">Image</label>
                            <input type="file" id="image" class="form-control" name="photo"
                                placeholder="Enter class name">
                                <small id="brandImageError" class="form-text text-danger"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-lg-4">
                            <button type="reset" class="btn btn-danger"><i class="fas fa-eraser"></i> Clear</button>
                            <button type="submit" name="submit" class="btn btn-success"><i class="fas fa-check"></i>
                                Submit</button>
                        </div>
                    </div>
                </form>
                <div id="msg"></div>
            </div>
        </div>
    </div>

    <?php
include './footer.php';
?>

<script>


//validation
const nameError = document.querySelector('#nameError');
const brandImageError = document.querySelector('#brandImageError');

const formBrand = document.querySelector('#formBrand');



formBrand.addEventListener('submit', function(event) {
    event.preventDefault();
    let data = new FormData(this);
    //const fieldCorrectionError = document.querySelector('#fieldCorrectionError');
    //const gurnameError = document.querySelector('#gurnameError');
    clearErrorMessage();
    msg.innerHTML = '';
    fetch('../src/brand-add.php', {
            method: 'POST',
            body: data
        })
        .then(res => {
            if (res.status == 200) {
                res.json().then(json => {
                    msg.innerHTML =
                        '<div class="alert alert-success"><strong><i class="fa fa-check"></i> Success! </strong>' +
                        json.msg + '</div>'
                })
            } else {
                res.json().then(json => {
                    if (json.errors) displayErrors(json.errors);
                    msg.innerHTML =
                        '<div class="alert alert-danger"><strong><i class="fa fa-times"></i> Failed! </strong>' +
                        json.msg + '</div>'
                })
            }
        })
        .catch(error => {
            console.error(error);
        })
})

function displayErrors(error) {
    //const fieldCorrectionError = document.querySelector('#fieldCorrectionError');
   

    nameError.innerHTML = error.name;
    brandImageError.innerHTML = error.brandImage;

}

function clearErrorMessage() {


    nameError.innerHTML = '';
    brandImageError.innerHTML = '';

}
</script>