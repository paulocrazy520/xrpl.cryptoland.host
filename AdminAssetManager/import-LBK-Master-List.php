<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
require_once "import-LBK-Master-List-Function.php";

$doRun = 1;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>


    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" crossorigin="anonymous"></script>

</head>

<body>
    <div id="wrap">
        <div class="container" style="width: 100%;">
            <div class="row">
                <form class="form-horizontal" action="import-LBK-Master-List-Function.php" method="post" name="upload_excel" enctype="multipart/form-data">
                    <fieldset>
                        <!-- Form Name -->
                        <!-- <legend>CryptoLand userImport</legend>
                        <small>Example CSV format:<br>
                            `username`, `email`, `totalLand`, `totalAvatar`, `ref_community`
                            <br><br>
                            </small> -->
                        <!-- File Button -->
                        <?php if ($doRun == 1) { ?>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="filebutton">Select File</label>
                                <div class="col-md-4">
                                    <input type="file" name="file" id="file" class="input-large">
                                </div>
                            </div>
                            <!-- Button -->
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="singlebutton">Import data</label>
                                <div class="col-md-4">
                                    <button type="submit" id="submit" name="Import" class="btn btn-primary button-loading" data-loading-text="Loading...">Import</button>
                                </div>
                            </div>
                        <?php } ?>
                    </fieldset>
                </form>
            </div>
            <?php
            //get_all_records();
            ?>
        </div>
    </div>

</body>

</html>