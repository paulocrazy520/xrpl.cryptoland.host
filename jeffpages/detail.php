<?php 
    $nftTokenId = isset($_GET['page']) ? $_GET['page'] : "";

    if(!$nftTokenId)
            return;

    $nft = GetDetailNftInfoFromBithomp("", $nftTokenId);

    if(!$nft)
        return;

    $json = json_decode(json_encode($nft->metadata), true);               

    $name = $json['name']; // Pull Name data from URI
    $description = $json['description']; //Pull description data from URI
    $imgPath = $json['image']; //Pull  Image data from URI
    $videoPath = $json['video']; //Pull Video data from URI
    $collectionName = $json['collection']['name']; //Pull Collection data from URI[name]
    $collectionFamily = $json['collection']['family']; //Pull Collection data from URI[family]
    $attributes = $json['attributes']; // Pull all Filter Data from URI

    if($nft->sellOffers)
    foreach($nft->sellOffers as $offer)
    {
        if(isset($current_user) && $offer->owner == $current_user)
        {
            $current_user_has_sell_offer = true;
        }

        $asset_has_sell_offer = true;
    }

    if($nft->buyOffers)
    foreach($nft->buyOffers as $offer)
    {
        if(isset($current_user) && $offer->owner == $current_user)
        {
            $current_user_has_buy_offer = true;
        }

        $asset_has_buy_offer = true;
    }
?>

<div class="cs-height_90 cs-height_lg_80"></div>
<!-- Start Page Head -->
<section class="cs-page_head cs-bg" data-src="assets/img/page_head_bg.svg">
    <div class="container">
        <div class="text-center">
            <h3 class="cs-page_title">Single Item page</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">NftTokenID</li>
                <li class="breadcrumb-item active">
                    <?php echo $nftTokenId; ?>
                </li>
            </ol>
        </div>
    </div>
</section>
<!-- End Page Head -->
<div class="cs-height_30 cs-height_lg_70"></div>
<div class="container">
    <div class="row">
        <div class="col-lg-6">
            <div class="slider-for">
                <div class="slider-item">
                    <div class="cs-slider_thumb_lg cs-video_open"  href=<?php echo $videoPath  ?>><img src=<?php echo $imgPath; ?> alt=""></div>
                </div>
            </div>
            <div class="cs-height_25 cs-height_lg_25"></div>
            <div class="cs-tabs cs-fade_tabs cs-style1">
                <div class="cs-medium">
                    <ul class="cs-tab_links cs-style1 cs-medium cs-primary_color cs-mp0 cs-primary_font">
                        <li class="active"><a href="#Description">Description</a></li>
                    </ul>
                </div>
                <div class="cs-height_20 cs-height_lg_20"></div>
                <div class="cs-tab_content">
                    <div id="Description" class="cs-tab active">
                        <div class="cs-white_bg cs-box_shadow cs-general_box_5">
                           <?php echo $description; ?>
                        </div>
                    </div><!-- .cs-tab -->
                </div>
            </div>
            <div class="cs-height_25 cs-height_lg_25"></div>
            <div class="cs-white_bg cs-box_shadow cs-general_box_5">
                <section class="cs-hero cs-style3 cs-left">
                    <div class="cs-hero_in">        
                        <div class="">
                            <h3 class="cs-hero_category_title text-left cs-white_color">Traits</h3>
                            <div class="cs-hero_categories">
                            <?php
                            foreach ($attributes as $attribute) {
                                echo ' <a href="#" class="cs-card cs-style1 cs-box_shadow text-center cs-white_bg">
                                    <div class="cs-card_thumb">
                                    <p class="cs-card_title">'.$attribute['trait_type'].'</p>
                                    </div>
                                    <p class="cs-card_title">'.$attribute['value'].'</p>
                                </a>';
                            }
                            ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
    
        </div>
        <div class="col-lg-6">
            <div class="cs-height_0 cs-height_lg_40"></div>
            <div class="cs-single_product_head">
            <?php    
                echo '<h2>'.$collectionName.' And '. $collectionFamily.'</h2>';
                
                echo '<p>'.$name.'</p>';
                // <p>On sale for <span class="cs-accent_color">'.'0'.'XRP</p>';
            ?>
            </div>
            <div class="cs-height_25 cs-height_lg_25"></div>
            <div class="row">
                <div class="col-xl-10">
                    <div class="cs-author_card cs-white_bg cs-box_shadow">
                        <div class="cs-author_img"><img src="assets/img/avatar/avatar_1.png" alt=""></div>
                        <div class="cs-author_right">
                            <h3>Owner</h3>
                            <p><?php echo $nft->owner; ?></p>
                        </div>
                    </div>
                    <div class="cs-height_25 cs-height_lg_25"></div>
                </div>
            </div>
            <div>
                <div class="col-xl-10">
                    <div class="cs-author_card cs-white_bg cs-box_shadow">
                        <div class="cs-author_img"><img src="assets/img/avatar/avatar_21.png" alt=""></div>
                        <div class="cs-author_right">
                            <h3>Issuer</h3>
                            <p><?php echo $nft->issuer; ?></p>
                        </div>
                    </div>
                    <div class="cs-height_25 cs-height_lg_25"></div>
                </div>
            </div>

            <div class="cs-height_25 cs-height_lg_25"></div>
            
            <div class="row">
            
                <?php
                if(isset($current_user) && !empty($current_user))
                {
                    if($current_user == $nft->owner)
                    {   
                         if(isset($current_user_has_sell_offer))
                        {
                            echo '<div class="col-6">
                                    <span class="cs-btn cs-style1 cs-btn_lg w-100 text-center" data-modal="#cancelList" nft-id="'.$nft->nftokenID.'"><span>Cancel Listing</span></span>
                                </div>';
                        }
                        else if(!isset($asset_has_sell_offer))
                            echo '<div class="col-6">
                                    <span  class="cs-btn cs-style1 cs-btn_lg w-100 text-center" data-modal="#listItem" nft-id="'.$nft->nftokenID.'"><span>List Item</span></span>
                                </div>';
                    }
                    else
                    {
                        if(isset($current_user_has_buy_offer))
                            echo '<div class="col-6">
                                    <span class="cs-btn cs-style1 cs-btn_lg w-100 text-center" data-modal="#cancelList" nft-id="'.$nft->nftokenID.'"><span>Cancel Bid</span></span>
                                 </div>';
                        else 
                             echo '<div class="col-6">
                                        <span class="cs-btn cs-style1 cs-btn_lg w-100 text-center" data-modal="#placeBid" nft-id="'.$nft->nftokenID.'" owner="'.$nft->owner.'"><span>Place Bid</span></span>
                                    </div>';

                        if(isset($asset_has_sell_offer))
                            echo '<div class="col-6">
                                    <span class="cs-btn cs-style1 cs-btn_lg w-100 text-center" data-modal="#buyItem" nft-id="'.$nft->nftokenID.'"><span>Buy Now</span></span>
                            </div>';
                    }
                }
                ?>
            </div>
            <div class="cs-height_25 cs-height_lg_25"></div>
            &nbsp;
            &nbsp;
            <div class="cs-tabs cs-fade_tabs cs-style1">
                <div class="cs-medium">
                    <ul class="cs-tab_links cs-style1 cs-medium cs-primary_color cs-mp0 cs-primary_font">
                        <li class="active"><a href="#tab_one">Listing</a></li>
                        <li><a href="#tab_two">Place Offers</a></li>
                    </ul>
                </div>
                <div class="cs-height_20 cs-height_lg_20"></div>
                <div class="cs-tab_content" style="max-height: 30vh;overflow-y: scroll;">
                    <div id="tab_one" class="cs-tab active">
                        <ul class="cs-activity_list cs-mp0">
                            <?php 
                            if($nft->sellOffers)
                            foreach($nft->sellOffers as $offer)
                            {
                                $amount = intval($offer->amount) / 1000000;
                                if(isset($current_user) && $offer->owner == $current_user)
                                {
                                    $current_user_has_sell_offer = true;
                                }

                                $asset_has_sell_offer = true;
                                $status = "Active";

                                $formatedCreatedData = date('m/d/Y, h:i:s A', $offer->createdAt); // Represent the date as "mm/dd/yyyy, hh:mm:ss AM/
                                $formatedActionData = "------";
                                if(isset($offer->acceptedAccount))
                                {
                                    $status = "Accepted";
                                    $formatedActionData =  date('m/d/Y, h:i:s A', $offer->acceptedAt);;
                                }
                                else if(isset($offer->canceledAt))
                                {
                                    $status = "Canceled";
                                    $formatedActionData =  date('m/d/Y, h:i:s A', $offer->canceledAt);;
                                }


                            ?>
                            <li>
                                <div class="cs-activity <?php echo ($status == "Active" ? "cs-white_bg": "cs-gray_bg"); ?> cs-type1">
                                    <div class="cs-activity_right">
                                        <p class="cs-activity_text" ><?php echo "Status " ?><span> <?php echo $status ?></p>
                                        <p class="cs-activity_text">Seller  <span><?php echo $offer->owner ?> </span> </p>
                                        <p class="cs-activity_text">Price <span><?php echo $amount; ?> XRP </span> </p>
                                        <p class="cs-activity_text">Created <span><?php echo $formatedCreatedData; ?> </span>  Performed <span><?php echo $formatedActionData; ?> </span</p>
                                        
                                    </div>
                                </div>
                            </li>
                            <?php } ?>
                        </ul>
                    </div><!-- .cs-tab -->
                    <div id="tab_two" class="cs-tab">
                        <ul class="cs-activity_list cs-mp0">
                        <?php 
                            if($nft->buyOffers)
                            {
                            foreach($nft->buyOffers as $offer)
                            {
                                $amount = intval($offer->amount) / 1000000;
                                if(isset($current_user) && $offer->owner == $current_user)
                                {
                                    $current_user_has_buy_offer = true;
                                }

                                $asset_has_buy_offer = true;
                                $status = "Active";

                                $formatedCreatedData = date('m/d/Y, h:i:s A', $offer->createdAt); // Represent the date as "mm/dd/yyyy, hh:mm:ss AM/
                                $formatedActionData = "------";
                                if(isset($offer->acceptedAccount))
                                {
                                    $status = "Accepted";
                                    $formatedActionData =  date('m/d/Y, h:i:s A', $offer->acceptedAt);;
                                }
                                else if(isset($offer->canceledAt))
                                {
                                    $status = "Canceled";
                                    $formatedActionData =  date('m/d/Y, h:i:s A', $offer->canceledAt);;
                                }

                            ?>
                            <li>
                            <div class="cs-activity <?php echo ($status == "Active" ? "cs-white_bg": "cs-gray_bg"); ?> cs-type1">
                                    <div class="cs-activity_right">
                                        <p class="cs-activity_text"><?php echo "Status " ?><span> <?php echo $status ?></p>
                                        <p class="cs-activity_text">Placer  <span><?php echo $offer->owner ?> </span> </p>
                                        <p class="cs-activity_text">Price <span><?php echo $amount; ?> XRP </span> </p>
                                        <p class="cs-activity_text">Created <span><?php echo $formatedCreatedData; ?> </span>  Performed <span><?php echo $formatedActionData; ?> </span</p>
                                        
                                    </div>
                                    <div class="cs-activity_icon cs-center cs-gray_bg cs-accent_color">
                                            <?php
                                                echo '<a href="#" class="cs-btn cs-style1 cs-btn_lg  text-center"  data-modal="#buyItem" nft-id="'.$nft->nftokenID.'" offer-id="'.$offer->offerIndex.'"><span>Accept</span></a>';
                                                ?>
                                            </div>
                                    </div>
                            </li>
                            <?php } }?>
                           
                        </ul>
                    </div><!-- .cs-tab -->
                </div>
            </div>
            &nbsp;
            <div class="cs-height_25 cs-height_lg_25"></div>
            <div class="cs-tabs cs-fade_tabs cs-style1">
                <div class="cs-medium">
                    <ul class="cs-tab_links cs-style1 cs-medium cs-primary_color cs-mp0 cs-primary_font">
                        <li class="active"><a href="#history">History</a></li>
                    </ul>
                </div>
                <div class="cs-height_20 cs-height_lg_20"></div>
                <div class="cs-tab_content">
                <div id="history" class="cs-tab active">
                        <ul class="cs-activity_list cs-mp0">
                            <?php 
                            foreach( $nft->history as $history)
                            {
                                if(isset($history->offerIndex) && $history->offerIndex)
                                {
                                    $offer = GetOfferInfoFromBithomp($history->offerIndex);
                                    $owner = $offer->owner;
                                    $amount = intval($offer->amount) / 1000000;
                                    $formatedCreatedData = date('m/d/Y, h:i:s A', intval($offer->createdAt)); //
                                    $flags = $offer->flags->sellToken ? "Sold" : "Buy";

                                    ?>
                                    <li>
                                        <div class="cs-activity cs-white_bg cs-type1">
                                            <div class="cs-activity_right">
                                                <p class="cs-activity_text"><?php echo $flags; ?> <span><?php echo $formatedCreatedData; ?> </span> </p>
                                                <p class="cs-activity_text">Price <span><?php echo $amount; ?> XRP </span> </p>
                                                <p class="cs-activity_text">From  <span><?php echo $offer->owner ?> </span> </p>
                                                <p class="cs-activity_text">To  <span><?php echo $offer->acceptedAccount ?> </span> </p>
                                            </div>
                                        </div>
                                    </li>
                                    <?php                                     
                                }
                                else{
                                    $flags = "Mint";
                                    $amount = "";
                                    $formatedCreatedData = date('m/d/Y, h:i:s A', intval($history->changedAt)); //
                                    ?>
                                    <li>
                                        <div class="cs-activity cs-white_bg cs-type1">
                                            <div class="cs-activity_right">
                                                <p class="cs-activity_text"><?php echo $flags; ?> <span><?php echo $formatedCreatedData; ?> </span> </p>
                                                <p class="cs-activity_text">From  <span><?php echo $nft->issuer ?> </span> </p>
                                                <p class="cs-activity_text">To  <span><?php echo $history->owner ?> </span> </p>
                                            </div>
        
                                        </div>
                                    </li>
                                    <?php   
                                }
                            }
                            ?>
                        </ul>
                    </div><!-- .cs-tab -->
            </div>
        </div>
</div>

<div class="cs-height_25 cs-height_lg_25"></div>
<div class="cs-height_25 cs-height_lg_25"></div>

