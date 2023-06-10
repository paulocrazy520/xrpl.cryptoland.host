<style>

.cs-card.cs-style4 .cs-card_btn_disabled {
  font-weight: 500;
  font-size: 12px;
  color: #fff;
  background-color: #3c4049;
  border-radius: 1.6em;
  line-height: 1.5em;
  padding: 4px 16px;
  position: relative;
  -webkit-transition: all 0.3s ease;
  transition: all 0.3s ease;
}

</style>

<?php     if(!$isPost){ ?>
<div class="cs-height_90 cs-height_lg_80"></div>
<!-- Start Page Head -->
<section class="cs-page_head cs-bg" data-src="assets/img/page_head_bg.svg">
  <div class="" style="padding-left:5%; padding-right:5%">
    <div class="text-center">
      <h1 class="cs-page_title">Claim Reveal </h1>
      <ol class="breadcrumb">
          <li class="breadcrumb-item">Issuer Address</li>
          <li class="breadcrumb-item active">
            <?php echo $default_issuer_address; ?>
          </li>
      </ol>
      <?php
            if(isset($current_user) && $current_user)
            {
             echo '<ol class="breadcrumb">
            <li class="breadcrumb-item">Signed Xumm Address</li>
            <li class="breadcrumb-item active">
              '.$current_user.'
            </li>
            </ol>';
            }
      ?>
    </div>
  </div>
</section>
<!-- End Page Head -->
<div class="cs-height_10 cs-height_lg_10"></div>
<div class="container" style="min-height:30vh; margin-bottom: 10vh;" >
    <?php require_once "filter.php"?>
    <div class="cs-sidebar_frame_right">
      <?php require_once "tab.php"?>
      <?php require_once "list.php"?>
    </div>
</div>

<div class="cs-height_10 cs-height_lg_10"></div>

<?php }else{ ?>
	<?php require_once "list.php"?>
<?php } ?>