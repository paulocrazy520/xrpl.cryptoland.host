<?php

$forceDebug = true;
if ($forceDebug == true) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
require_once 'assets/init.php';
$isMenu = isset($_POST['menuCollection']) ? true : false;
if($isMenu)
{
  require_once "pages/main/explorer.php";
  return;
}
?>

<!DOCTYPE html>
<html class="no-js" lang="en">
  <head>
    <!-- Meta Tags -->
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="author" content="ThemeMarch" />
    <!-- Site Title -->
    <title>MarketPlace</title>
    <link rel="stylesheet" href="assets/css/plugins/fontawesome.min.css" />
    <link rel="stylesheet" href="assets/css/plugins/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins/slick.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
  </head>

  <body class="cs-dark">
    <div class="cs-preloader cs-center">
      <div class="cs-preloader_in"></div>
      <span>Loading</span>
    </div>

<?php
  $isDetail = isset($_GET['page']) ? $_GET['page'] : "";

  if(!$isMenu)
    require_once "pages/header.php";

  if($isDetail)
    require_once "pages/main/detail.php";
  else
    require_once "pages/main/explorer.php";
  
  if(!$isMenu){
    require_once "pages/footer.php";
    require_once "pages/dialog.php";
  }

?>
    <!-- Script -->
    <script src="assets/js/plugins/jquery-3.6.0.min.js"></script>
    <script src="assets/js/plugins/isotope.pkg.min.js"></script>
    <script src="assets/js/plugins/jquery.slick.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://xumm.app/assets/cdn/xumm-oauth2-pkce.min.js?v=2.7.1"></script>
    <script src="https://unpkg.com/xrpl@2.2.3"></script>
    
    <!-- ToMarcus -->
    <script src="./global/config.js"></script>
    <script src="./global/functions.js"></script>
    <script src="./global/sign.js"></script>
    <script src="./global/dialog.js"></script>
    <script src="./global/filter.js"></script>
    <!-------------->
  </body>
</html>

