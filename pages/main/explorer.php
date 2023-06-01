<?php     if(!$isMenu){ ?>
<div class="cs-height_90 cs-height_lg_80"></div>
<!-- Start Page Head -->
<section class="cs-page_head cs-bg" data-src="assets/img/page_head_bg.svg">
  <div class="" style="padding-left:5%; padding-right:5%">
    <div class="text-center">
      <h1 class="cs-page_title">Xrpl MarketPlace</h1>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
        <li class="breadcrumb-item active">MarketPlace</li>
      </ol>
    </div>
  </div>
</section>
<!-- End Page Head -->
<div class="cs-height_100 cs-height_lg_70"></div>
<div class="" style="padding-left:2%; padding-right:2%">
    <?php require_once "left-menu.php"?>
    <?php require_once "main-section-test.php"?>
</div>

<?php } else { ?>
	<?php require_once "main-section-test.php"?>
<?php } ?>