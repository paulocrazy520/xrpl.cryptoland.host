
<?php     if(!$isPost){ ?>
<div class="cs-height_90 cs-height_lg_80"></div>
<!-- Start Page Head -->
<section class="cs-page_head cs-bg" data-src="assets/img/page_head_bg.svg">
  <div class="" style="padding-left:5%; padding-right:5%">
    <div class="text-center">
      <h1 class="cs-page_title">Claim Reveal</h1>
    </div>
  </div>
</section>
<!-- End Page Head -->
<div class="cs-height_30 cs-height_lg_70"></div>
<div class="container" style="min-height:30vh;" >
    <?php require_once "tab.php"?>
    <?php require_once "list.php"?>
</div>

<div class="cs-height_30 cs-height_lg_30"></div>

<?php }else{ ?>
	<?php require_once "list.php"?>
<?php } ?>