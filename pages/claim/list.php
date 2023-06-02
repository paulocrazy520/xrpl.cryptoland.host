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
	$page = isset($_POST['page']) && is_numeric($_POST['page']) ? $_POST['page'] : 1;
	$typeType = isset($_POST['typeType']) ? $_POST['typeType'] : "*";

	$claimedArray = GetAccountNftsFromServer();
	$unclaimedArray = GetUnClaimedOffersFromServer();

	if(!$claimedArray || !$unclaimedArray)
		return;

	switch($typeType){
		case "*":
			$totalArray = [$claimedArray, $unclaimedArray];
			break;
		case ".unclaimed":
			$totalArray =  $unclaimedArray;
			break;
		case ".unrevealed":
		case ".revealed":
			$totalArray =  $claimedArray;
			break;
		default:
			$totalArray = [$claimedArray, $unclaimedArray];
			break;
	}
	
	$totalCount = 0 ;

	$unclaimed_index = 0;
	$unrevealed_index = 0;
	$revealed_index = 0;

	for($i = 0 ; $i < 2; $i++)
	{
		foreach($totalArray[$i] as $nfTokenID){
		
			if( ( ($page-1) * $num_results_on_page <= $totalCount &&  $totalCount < $page * $num_results_on_page))
			{
				$info = GetNftInfoByNftIdFromDatabase($nfTokenID);
				$url = $info["base_uri"];

				if($i==0) // claimed
				{
					if($info["revealed"])
					{
						$viewType = "revealed";
						$revealed_index ++;
					}
					else
					{
						$viewType = "unrevealed";
						$unrevealed_index ++;
					}
				}
				else if($i==1) // Unclaimed
				{
					$viewType = "unclaimed";
					$unclaimed_index = 0;
				}

				if (!isset($url)){
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

				if( ($page-1) * $num_results_on_page <= $totalCount &&  $totalCount < $page * $num_results_on_page)
					require "card.php";
			}
			$totalCount = $totalCount + 1;
		}
	}


//***************Fiter Menu end*************
	if(!$isPost){

		echo "</div>";
		echo '<input type=hidden id="totalPages" value="'.(ceil($totalCount / $num_results_on_page)).'"/>';
		echo '<input type=hidden id="unclaimedTotalPages" value="'.ceil($unclaimed_index / $num_results_on_page).'"/>';
		echo '<input type=hidden id="unrevealedTotalPages" value="'.ceil($unrevealed_index / $num_results_on_page).'"/>';
		echo '<input type=hidden id="revealedTotalPages" value="'.ceil($revealed_index / $num_results_on_page).'"/>';


		echo '<input type=hidden id="numTotalCards" value="'.$totalCount.'"/>';
		
	}
?>

	