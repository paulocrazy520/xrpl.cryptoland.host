<?php if(!$isPost){  ?>
	<div class="cs-filter_head">
		<div class="cs-filter_head_left">
		<span class="cs-search_result cs-medium cs-ternary_color"> </span><span>Results</span>
		<a href="#" class="cs-clear_btn">Clear All</a>
		</div>
	</div>

	<div class="cs-height_30 cs-height_lg_30"></div>

	<div class="cs-isotop cs-style1 cs-isotop_col_5 cs-has_gutter_30" id="nft-list">
		<div class="cs-grid_sizer"></div>
	<?php
}

	//******************Filter Menu Begin*****************
	$page = isset($_POST['page']) && is_numeric($_POST['page']) ? $_POST['page'] : 1;

	$claimedArray = GetAccountNftsFromServer();
	$unclaimedArray = GetUnClaimedOffersFromServer();

	if(!$claimedArray || !$unclaimedArray)
		return;

	$totalArray = [$claimedArray, $unclaimedArray];
	
	$index = 0 ;

	for($i = 0 ; $i < 2; $i++)
	{
		foreach($totalArray[$i] as $nft){

			$info = GetNftInfoByNftIdFromDatabase($nft->NFTokenID);
			$url = $info["base_uri"];

	
			if($i==0) // claimed
			{
				$viewType = $info["revealed"] ?  "revealed": "unrevealed";
			}
			else if($i==1) // Unclaimed
			{
				$viewType = "unclaimed";
			}

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
				//print_r( $nft);

			if( ($page-1) * $num_results_on_page <= $index &&  $index < $page * $num_results_on_page)
			require "card.php";
			
			$index = $index + 1;
		}
	}


//***************Fiter Menu end*************
	$total_cards = $index;
	$total_pages = ceil($total_cards / $num_results_on_page) ;

	if(!$isPost){
		echo "</div>";
		echo '<input type=hidden id="totalPages" value="'.$total_pages.'"/>';
		echo '<input type=hidden id="onPageCards" value="'.$num_results_on_page.'"/>';
		echo '<input type=hidden id="totalCards" value="'.$total_cards.'"/>';
	}
?>

	