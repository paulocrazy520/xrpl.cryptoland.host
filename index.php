<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$forceDebug = true;
if ($forceDebug == true) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once 'assets/init.php';


// Pass the environment variables to your view
echo "<script>window.env = {";
  foreach ($_ENV as $key => $value) {
      echo '"' . $key . '":"' . $value . '",';
  }
echo "}</script>";

$DEFAULT_PAGE_TYPE = isset($_GET['DEFAULT_PAGE_TYPE']) ? $_GET['DEFAULT_PAGE_TYPE'] : (isset($_ENV['DEFAULT_PAGE_TYPE']) ? $_ENV['DEFAULT_PAGE_TYPE'] : 'claim');
$isDetail = isset($_GET['page']) ? $_GET['page'] : "";
$isPost = isset($_POST['pageType']) ? $_POST['pageType'] : "";

if($isPost)
{
  require_once "jeffpages/$isPost/index.php";
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
  if(!$isPost)
    require_once "jeffpages/header.php";

  if($isDetail)
    require_once "jeffpages/detail.php";
  else
  {
    require_once "jeffpages/$DEFAULT_PAGE_TYPE/index.php";
  }
  
  if(!$isPost){
    // require_once "jeffpages/footer.php";
    require_once "jeffpages/dialog.php";
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
    <script src="./jeffjs/functions.js"></script>
    <script src="./jeffjs/action.js"></script>
    <script src="./jeffjs/filter.js"></script>
    
    <script type="module">
      import env from './env.js';
    </script>
    <!-------------->
  </body>
</html>

