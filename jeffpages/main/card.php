
 <!-- .col -->
 <div class="col-xl-3 col-lg-4 col-sm-6 nft-card"  style="min-width:350px; text-align:center">
              <div class="cs-card cs-style4 cs-box_shadow cs-white_bg">

                <!-- <a href="#" class="cs-card_thumb cs-zoom_effect"  onClick="window.open('<?php echo $videoPath ?>', 'newwindow', 'width=600,height=600,left='+((screen.width-600)/2)+',top='+((screen.height-600)/2));  return false;"> -->
                <a href="?page=<?php echo $nft->NFTokenID?>" class="cs-card_thumb cs-zoom_effect" >
                  <img
                    src="<?php echo $imgPath ?>"
                    alt="Image"
                    class="cs-zoom_item"
                  />
                </a>
                <div class="cs-card_info">
                  <!-- <a href="?page=<?php echo $nft->NFTokenID?>" class="cs-avatar cs-white_bg">
                    <img src="assets/img/avatar/avatar_13.png" alt="Avatar" />
                    <span >->Detail</span>
                  </a> -->
                  <div class="cs-height_20 cs-height_lg_20"></div>
                  <div style="background-color: <?php echo $color ?>;  border-radius:5%; padding:5%;">
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
<?php


                if(isset($current_user) && !empty($current_user))
                {
                if ($asset_owner == $current_user && $asset_has_sell_offer){
                    /* Display DeList Button */
                    echo '
                    <span class="cs-card_btn_3" data-modal="#cancelList" nft-id="'.$nft->NFTokenID.'"
                      ><span>Cancel Listing</span></span
                    >
                  ';
                    }


                    if ($asset_owner == $current_user && !$asset_has_sell_offer){
                    
                    /* Display List Button */
                    echo '
                    <span class="cs-card_btn_2" data-modal="#listItem" nft-id="'.$nft->NFTokenID.'"
                      ><span>List Item</span></span
                    >
                  ';
                    }

                    if ($asset_owner != $current_user && $asset_has_sell_offer && $current_user_has_bid ){
                      /* Display Buy Now Button from sell offer*/
                      /* Display Cancel Bid Button from current user's bid*/
                      echo '<span class="cs-card_btn_2" data-modal="#buyItem" nft-id="'.$nft->NFTokenID.'" 
                        ><span>Buy Now</span></span
                      >
                      <span class="cs-card_btn_5" data-modal="#cancelBid" nft-id="'.$nft->NFTokenID.'"
                        ><span>Cancel Bid</span></span
                      >
                    ';
                      }
                    
                      if ($asset_owner != $current_user && $asset_has_sell_offer && !$current_user_has_bid){
                      /* Display Buy Now Button from sell offer*/
                      echo '
                      <span class="cs-card_btn_2" data-modal="#buyItem" nft-id="'.$nft->NFTokenID.'" 
                        ><span>Buy Now</span> </span
                      >
                    ';
                      }
                      

                      if ($asset_owner != $current_user && !$asset_has_sell_offer && $current_user_has_bid){
                      /* Display Cancel Bid Button from current user's bid*/
                      echo '
                      <span class="cs-card_btn_5" data-modal="#cancelBid" nft-id="'.$nft->NFTokenID.'"
                        ><span>Cancel Bid</span></span
                      >
                    ';
                      }
                      
                      if ($asset_owner != $current_user && !$asset_has_sell_offer && !$current_user_has_bid){
                      
                      /* Display Place Bid Button */
                      echo '
                      <span class="cs-card_btn_4" data-modal="#placeBid" nft-id="'.$nft->NFTokenID.'" owner="'.$nft->Owner.'"
                        ><span>Place Bid</span></span
                      >
                    ';
                      }
                    }
?>
                      </div>
                   </div>
                </div>
              </div>
              <div class="cs-height_30 cs-height_lg_30"></div>
            </div>