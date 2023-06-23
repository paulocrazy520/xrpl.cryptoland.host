<?php if(!$isPost){  ?>
<div class="cs-sidebar_frame_right">
	<div class="cs-filter_head">
		<div class="cs-filter_head_left">
		<span class="cs-search_result cs-medium cs-ternary_color"> </span><span>Results</span>
		<a href="#" onclick="location.reload();" class="cs-clear_btn">Clear All</a>
		</div>
	</div>

    <div class="cs-height_30 cs-height_lg_30"></div>
    <div class="row" id="nft-list">
<?php
}

$menuCollection = isset($_POST['menuCollection']) ? $_POST['menuCollection'] : "";
$menuRarity = isset($_POST['menuRarity']) ? $_POST['menuRarity'] : "";
$menuColor = isset($_POST['menuColor']) ? $_POST['menuColor'] : "";
$menuSale = isset($_POST['menuSale']) ? $_POST['menuSale'] : "";
$menuBid = isset($_POST['menuBid']) ? $_POST['menuBid'] : "";
$cardsCount = isset($_POST['cardsCount']) && is_numeric($_POST['cardsCount']) ? $_POST['cardsCount'] : 0;

//Load nft infos from issuer and account
$totalArray = GetNftArrayForMarketplaceFromServer($menuCollection, $menuRarity, $menuColor, $menuSale, $menuBid, $cardsCount);

if(!$totalArray)
	return;

//******************Filter Menu Begin*****************
$totalCount = 0;
foreach($totalArray as $nft){
	$nft = json_decode(json_encode($nft));

	if (empty($nft))
		continue;

	if(isset($nft->totalCount)){
		$totalCount = $nft->totalCount;
		continue;
	}
	
	$url = $nft->URI;

	$jsonString = file_get_contents($url);
	$json = json_decode($jsonString, true);
	
	$name = $json['name']; // Pull Name data from URI
	$imgPath = $json['image']; //Pull  Image data from URI
	$videoPath = $json['video']; //Pull Video data from URI
	$collectionName = $json['collection']['name']; //Pull Collection data from URI[name]
	$collectionFamily = $json['collection']['family']; //Pull Collection data from URI[family]
	$attributes = $json['attributes']; // Pull all Filter Data from URI
	$color = GetTestHexColorFromColorString();
	$colorName = "";
	$rarity = "";
	
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
					$color = GetTestHexColorFromColorString($attribute['value']);
					$colorName = $attribute['value'];
				}
				break;
			case 'Subclass':
				$subclass = $attribute['value']; // Pull Rarity data from URI
				break;
			default:
				break;
		}
	}

	$asset_owner = $nft->Owner;
	$asset_has_sell_offer = filter_var($nft->hasSellOffer, FILTER_VALIDATE_BOOLEAN);
	$current_user_has_sell = filter_var($nft->hasSellOfferByUser, FILTER_VALIDATE_BOOLEAN);
	$asset_has_bid = filter_var($nft->hasBuyOffer, FILTER_VALIDATE_BOOLEAN);
	$current_user_has_bid = filter_var($nft->hasBuyOfferByUser, FILTER_VALIDATE_BOOLEAN);
	
	require "card.php";
}


//***************Fiter Menu end*************
if(!$cardsCount)
{
	echo '<input type=hidden id="totalCount" value="'.$totalCount.'"/>';
}

if(!$isPost){
	echo "</div></div>";
}
?>
	