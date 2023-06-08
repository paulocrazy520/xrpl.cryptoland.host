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
<?php 
  switch($viewType){
    case "unclaimed":
      $btnStr =  "Claim";

      if(!$info["transfer_status"])
        $btnStyle = "cs-card_btn_4";
      else
        $btnStyle = "cs-card_btn_disabled";

      $modalId= "#claimItem";
      break;
    case "unrevealed":
      $btnStr = "Reveal";
      $btnStyle = "cs-card_btn_2";
      $modalId= "#revealItem";
      break;
    case "revealed":
      $btnStr = "Revealed";
      $btnStyle = "cs-card_btn_1";
      break;
    default:
      $btnStyle = "cs-card_btn_disabled";
      break;
  }

  $disabled = "cs-gray_bg";

  $offerID = isset($offerID) ? $offerID : "";
?>
 <!-- .col -->
 <div class="cs-isotop_item <?php echo $viewType; ?> nft-card"  nft-id='<?php echo $nfTokenID; ?>'  style="text-align:center">
    <div class="cs-card cs-style4 cs-box_shadow cs-white_bg" style="background-color: <?php echo $color ?>;">
      <!-- <a href="#" class="cs-card_thumb cs-zoom_effect"  onClick="window.open('<?php echo $videoPath ?>', 'newwindow', 'width=600,height=600,left='+((screen.width-600)/2)+',top='+((screen.height-600)/2));  return false;"> -->
      <a href="?page=<?php echo $nfTokenID?>" class="cs-card_thumb cs-zoom_effect" >
        <img
          src="<?php echo $imgPath ?>"
          alt="Image"
          class="cs-zoom_item"
        />
      </a>
      <div class="cs-card_info" style="padding:0px;">
        <div>
          <div class="cs-height_10 cs-height_lg_10"></div>
            <h3 class="cs-card_title">
              <a href="?page=<?php echo $nfTokenID?>" ><?php echo $collectionName . ' And ' . $collectionFamily ?></a>
            </h3>
            <div class="cs-card_price" style="display: flex; justify-content: space-between;">
              Name: <b><?php echo $name ?></b>
            </div>
            <div class="cs-card_price" style="display: flex; justify-content: space-between;">
              Rarity: <b><?php echo $rarity ?></b>
            </div>
            <hr />
            <?php if($current_user){ ?>
            <div class="cs-card_footer" style="padding:5px 10% 5px 10%;">
            <?php              
                  echo '
                  <span class="cs-action_item '.$btnStyle.' w-100" data-modal="'.$modalId.'" nft-id="'.$nfTokenID.'" offer-id="'.$offerID.'"
                    ><span>'.$btnStr.'</span></span
                  >';
               ?>
            </div>
            <?php } ?>
          </div>
      </div>
  </div>
</div>