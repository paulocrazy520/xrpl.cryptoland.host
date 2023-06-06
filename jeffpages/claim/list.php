<?php if(!$isPost){  ?>
	<div class="cs-filter_head">
		<div class="cs-filter_head_left">
			<span>Results</span> <span class="cs-search_result cs-medium cs-ternary_color"> </span>		
		</div>
	</div>

	<div class="cs-height_30 cs-height_lg_30"></div>

	<div class="cs-isotop cs-style1 cs-isotop_col_5 cs-has_gutter_30" id="nft-list">
		<div class="cs-grid_sizer"></div>
	<?php
}

	//******************Filter Menu Begin*****************
	$cardsCount = isset($_POST['cardsCount']) && is_numeric($_POST['cardsCount']) ? $_POST['cardsCount'] : 0;
	$tabType = isset($_POST['tabType']) ? $_POST['tabType'] : "*";

	$unclaimedArray = GetUnClaimedNftsFromServer();
	$claimedArray = GetClaimedNftsFromServer();
	$claimedWithRevealedArray = GetRevealNftArraysFromDatabase($claimedArray);
	$revealedArray = isset($claimedWithRevealedArray["revealedArray"]) ? $claimedWithRevealedArray["revealedArray"] : array();
	$unrevealedArray = isset($claimedWithRevealedArray["unrevealedArray"]) ? $claimedWithRevealedArray["unrevealedArray"] : array();
	 if(!$claimedArray && !$unclaimedArray)
	 	return;

	switch($tabType){
		case "*":
			$arrayList = [$unclaimedArray, $unrevealedArray, $revealedArray];
			$tabTypeList = [".unclaimed", ".unrevealed", ".revealed"];
			break;
		default:
		case ".unclaimed":
			$totalArray =  $unclaimedArray;
			break;
		case ".unrevealed":
			$totalArray =  $unrevealedArray;			
			break;
		case ".revealed":
			$totalArray =  $revealedArray;		
			break;
	}

	if($tabType == "*")
	{
		$tab_index = 0;
		foreach($arrayList as $totalArray)
		{

			$subTabType = $tabTypeList[$tab_index];
			$tab_index ++;

			if(!$totalArray)
			continue;

			for($index = $cardsCount ;  $index < min(count($totalArray), ($cardsCount + $num_results_on_page - ($cardsCount % $num_results_on_page))) ; $index++)
			{
				if($subTabType == ".unclaimed")
				{
					$nfTokenID = $totalArray[$index]->NFTokenID;
					$offerID = $totalArray[$index]->OfferID;
					$info = GetNftInfoByNftIdFromDatabase($nfTokenID);
				}else
				{
					$info = $totalArray[$index];
					$nfTokenID = $info["nft_id"];
				}

		
				$url = $info["base_uri"];

				$viewType =  str_replace(".", "", $subTabType);

				if (!isset($url)){
					continue;
				} 
	
				$jsonString = file_get_contents($url);
				$json = json_decode($jsonString, true);
			
				$name = $json['name']; // Pull Name data from URI
				$imgPath = $json['image']; //Pull  Image data from URI
				$videoPath = $json['video']; //Pull Video data from URI
				$collectionName = $json['collection']['name']; //Pull Collection data from URI[name]
				$collectionFamily = $json['collection']['family']; //Pull Collection data from URI[family]
				$attributes = $json['attributes']; // Pull all Filter Data from URI
				$color = GetTestHexColorFromColorString();
			
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
							}
							break;
						default:
							break;
					}
				}

				require "card.php";
			}
		}
	}
	else{

		if(!$totalArray)
		for($index = $cardsCount ;  $index < min(count($totalArray), ($cardsCount + $num_results_on_page - ($cardsCount % $num_results_on_page))) ; $index++)
		{
			if($tabType == ".unclaimed")
			{
				$nfTokenID = $totalArray[$index]->NFTokenID;
				$offerID = $totalArray[$index]->OfferID;
				$info = GetNftInfoByNftIdFromDatabase($nfTokenID);
			}else
			{
				$info = $totalArray[$index];
				$nfTokenID = $info["nft_id"];
			}
			$url = $info["base_uri"];
		
			$viewType =  str_replace(".", "", $tabType);

			if (!isset($url)){
				continue;
			} 

			$jsonString = file_get_contents($url);
			$json = json_decode($jsonString, true);
		
			$name = $json['name']; // Pull Name data from URI
			$imgPath = $json['image']; //Pull  Image data from URI
			$videoPath = $json['video']; //Pull Video data from URI
			$collectionName = $json['collection']['name']; //Pull Collection data from URI[name]
			$collectionFamily = $json['collection']['family']; //Pull Collection data from URI[family]
			$attributes = $json['attributes']; // Pull all Filter Data from URI
			$color = GetTestHexColorFromColorString();
		
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
						}
						break;
					default:
						break;
				}
			}

			require "card.php";
		}
	}

	if(!$isPost){
		$unclaimedCount = count($unclaimedArray);
		$unrevealedCount = count($unrevealedArray);
		$revealedCount = count($revealedArray);
		$totalCount = $unclaimedCount + $unrevealedCount + $revealedCount;

		echo '<h1 class="cs-hero_title cs-white_color cs-center" id="empty_result">Empty Result</h1>';
		echo '<input type=hidden id="totalCount" value="'.$totalCount.'"/>';
		echo '<input type=hidden id="unclaimedCount" value="'.$unclaimedCount.'"/>';
		echo '<input type=hidden id="unrevealedCount" value="'.$unrevealedCount.'"/>';
		echo '<input type=hidden id="revealedCount" value="'.$revealedCount.'"/>';
		echo "</div>";
	}
	
//***************Fiter Menu end*************
?>

	