
 <!-- .col -->
 <div class="cs-isotop_item unclaimed"  style="text-align:center">
    <div class="cs-card cs-style4 cs-box_shadow cs-white_bg" style="background-color: <?php echo $color ?>;">
      <!-- <a href="#" class="cs-card_thumb cs-zoom_effect"  onClick="window.open('<?php echo $videoPath ?>', 'newwindow', 'width=600,height=600,left='+((screen.width-600)/2)+',top='+((screen.height-600)/2));  return false;"> -->
      <a href="?page=<?php echo $nft->NFTokenID?>" class="cs-card_thumb cs-zoom_effect" >
        <img
          src="<?php echo $imgPath ?>"
          alt="Image"
          class="cs-zoom_item"
        />
      </a>
      <div class="cs-card_info" style="padding:0px;">
        <a href="?page=<?php echo $nft->NFTokenID?>" class="cs-avatar cs-white_bg">
          <img src="assets/img/avatar/avatar_13.png" alt="Avatar" />
          <span >->Detail</span>
        </a>

        <div>
            <h3 class="cs-card_title">
              <a href="?page=<?php echo $nft->NFTokenID?>" ><?php echo $collectionName . ' And ' . $collectionFamily ?></a>
            </h3>
            <div class="cs-card_price" style="display: flex; justify-content: space-between;">
              Name: <b><?php echo $name ?></b>
            </div>
            <div class="cs-card_price" style="display: flex; justify-content: space-between;">
              Rarity: <b><?php echo $rarity ?></b>
            </div>
            <hr />
            <div class="cs-card_footer" style="padding:5px 10% 5px 10%;">

            </div>
          </div>
      </div>
  </div>
</div>