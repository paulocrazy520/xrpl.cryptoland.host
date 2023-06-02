<?php if(!$isPost){  ?>
<div class="cs-sidebar_frame_right">

	<div class="cs-filter_head">
		<div class="cs-filter_head_left">
		<span class="cs-search_result cs-medium cs-ternary_color"> </span><span>Results</span>
		<!-- <div class="cs-form_field_wrap">
			<input type="text" class="cs-form_field cs-field_sm" placeholder="In Auction">
		</div> -->
		<a href="#" class="cs-clear_btn">Clear All</a>
		</div>
	</div>

    <div class="cs-height_30 cs-height_lg_30"></div>

    <div class="row" id="nft-list">
<?php
}


//Load nft infos from issuer and account
$totalArray = LoadNftInfosFromCurrentUser();

if(!$totalArray)
	return;

$filterArray = [];

//******************Filter Menu Begin*****************
	$menuCollection = isset($_POST['menuCollection']) ? $_POST['menuCollection'] : "";
	$menuRarity = isset($_POST['menuRarity']) ? $_POST['menuRarity'] : "";
	$menuColor = isset($_POST['menuColor']) ? $_POST['menuColor'] : "";
	$menuSale = isset($_POST['menuSale']) ? $_POST['menuSale'] : "";
	$menuBid = isset($_POST['menuBid']) ? $_POST['menuBid'] : "";
	$page = isset($_POST['page']) && is_numeric($_POST['page']) ? $_POST['page'] : 1;

	$index = 0 ;
	foreach($totalArray as $nft){

		$nft = json_decode(json_encode($nft));
        $url = $nft->URI;

		if (!isset($url) || !isset($nft)){
			return;
		} 
		
		$jsonString = file_get_contents($url);
		$json = json_decode($jsonString, true);
		
		$name = $json['name']; // Pull Name data from URI
		$imgPath = $json['image']; //Pull  Image data from URI
		$videoPath = $json['video']; //Pull Video data from URI
		$collectionName = $json['collection']['name']; //Pull Collection data from URI[name]
		$collectionFamily = $json['collection']['family']; //Pull Collection data from URI[family]
		$attributes = $json['attributes']; // Pull all Filter Data from URI
		$color = getHexColor();
		$colorName = "";
		
		foreach ($attributes as $attribute) {
			switch ($attribute["trait_type"]) {
				case 'Consumable Class':
					$collectionValue = $attribute['value']; //Pull Collection Class
					break;
				case 'Rarity':
					$rarity = $attribute['value']; // Pull Rarity data from URI
					break;
				case 'Liquid Color':
					// Pull lower back ground from CSS for collectionClass
					if (isset($attribute['value'])) {
						$color = getHexColor($attribute['value']);
						$colorName = $attribute['value'];
					}
					break;
				default:
					break;
			}
		}

		if(($menuCollection && ($menuCollection != $collectionFamily))  || ($menuRarity && strstr($rarity, $menuRarity) == false) ||  ($menuColor && ($menuColor != $colorName)))
			continue;
			
			$asset_owner = $nft->Owner;
			$asset_has_sell_offer = filter_var($nft->hasSellOffer, FILTER_VALIDATE_BOOLEAN);
			$current_user_has_sell = filter_var($nft->hasSellOfferByUser, FILTER_VALIDATE_BOOLEAN);
			$asset_has_bid = filter_var($nft->hasBuyOffer, FILTER_VALIDATE_BOOLEAN);
			$current_user_has_bid = filter_var($nft->hasBuyOfferByUser, FILTER_VALIDATE_BOOLEAN);
			

			if(($menuSale == "checked" && !$asset_has_sell_offer) || ($menuBid == "checked" && !$asset_has_bid))
				continue;

			if( ($page-1) * $num_results_on_page <= $index &&  $index < $page * $num_results_on_page)
			require "card.php";
			
			$index = $index + 1;
			array_push($filterArray, $nft);
	}
//***************Fiter Menu end*************
	$total_cards = count($filterArray);
	$total_pages = ceil($total_cards / $num_results_on_page) ;

	if(!$isPost){
		echo '<input type=hidden id="totalPages" value="'.$total_pages.'"/>';
		echo '<input type=hidden id="onPageCards" value="'.$num_results_on_page.'"/>';
		echo '<input type=hidden id="totalCards" value="'.$total_cards.'"/>';
		echo "</div></div>";
	}
?>
	