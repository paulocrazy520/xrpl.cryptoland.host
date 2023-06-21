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
      <div class="cs-preloader_qr_layout">
        <div class="cs-modal_close cs-center" offerId="">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11.9649 2.54988C12.3554 2.15936 12.3554 1.52619 11.9649 1.13567C11.5744 0.745142 10.9412 0.745142 10.5507 1.13567L11.9649 2.54988ZM0.550706 11.1357C0.160181 11.5262 0.160181 12.1594 0.550706 12.5499C0.94123 12.9404 1.5744 12.9404 1.96492 12.5499L0.550706 11.1357ZM1.96492 1.13567C1.5744 0.745142 0.94123 0.745142 0.550706 1.13567C0.160181 1.52619 0.160181 2.15936 0.550706 2.54988L1.96492 1.13567ZM10.5507 12.5499C10.9412 12.9404 11.5744 12.9404 11.9649 12.5499C12.3554 12.1594 12.3554 11.5262 11.9649 11.1357L10.5507 12.5499ZM10.5507 1.13567L0.550706 11.1357L1.96492 12.5499L11.9649 2.54988L10.5507 1.13567ZM0.550706 2.54988L10.5507 12.5499L11.9649 11.1357L1.96492 1.13567L0.550706 2.54988Z" fill="currentColor"/>
            </svg>          
        </div>
        <img class="cs-preloader_qr" src="https://xumm.app/sign/d35c695f-f843-45dd-a99a-0b0dbe39250c_q.png"></img>
      </div>
      <div class="cs-height_20 cs-height_lg_20"></div>
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
    <script src="https://cdn.socket.io/3.1.3/socket.io.min.js" integrity="sha384-cPwlPLvBTa3sKAgddT6krw0cJat7egBga3DJepJyrLl4Q9/5WLra3rrnMcyTyOnh" crossorigin="anonymous"></script>
    
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

