<?php if(!$isPost){  ?>
	<div class="cs-filter_head">
		<div class="cs-filter_head_left">
			<span>Results</span> <span class="cs-search_result cs-medium cs-ternary_color"> </span>		
		</div>
	</div>

	<div class="cs-height_30 cs-height_lg_30"></div>

	<div class="cs-isotop cs-style1 cs-isotop_col_4 cs-has_gutter_30"  id="nft-list">
		<div class="cs-grid_sizer"></div>
	<?php
}

	if(!isset($current_user) || !$current_user)
	{
		$account = $default_issuer_address;
	}
	else
		$account = $current_user;
		
	//*********************Only when page loading first, update db for owner*********************

	try{
		//  UpdateDatabaseByIssuersFromServer();
	}
	catch(Exception $e){
		print_r($e);
	}

	return;


	if(!$isPost){
		try{
			UpdateDBForOwner($account);	
			// CreateAssetFolder();
			//return;
		}
		catch(Exception $e)
		{
			echo '<h1 class="cs-hero_title cs-white_color cs-center" id="empty_result">
			The server is not connecting or is taking longer than expected...
			If it still appears after refreshing again, contact the team.
			</h1>';
			return;
		}
	}

	//*****************Test for updating database from xrpl server by issuer addresses***************** */
	// UpdateDatabaseByIssuersFromServer();
	// return;

	//*********************************************************************************************** */
	//******************Filter Menu Begin*****************
	$cardsCount = isset($_POST['cardsCount']) && is_numeric($_POST['cardsCount']) ? $_POST['cardsCount'] : 0;
	$tabType = isset($_POST['tabType']) ? $_POST['tabType'] : "*";

	$unclaimedCount = 0;
	$unrevealedCount = 0;
	$revealedCount = 0;

	$menuCollection = isset($_POST['menuCollection']) ? $_POST['menuCollection'] : "";
	$query_params = [
        'menuCollection' => $menuCollection,
		'account' => $account
    ];

	try{
		if($tabType == "*" || $tabType == ".unclaimed")
		{
			$issuedNfts = GetOwnedNftsByIssuersFromServer($query_params);
			if(!$issuedNfts)
				return;

			$unclaimedArray = GetOwnedNftArrayByIssuersFromDatabase($issuedNfts);

			if($unclaimedArray)
				$unclaimedCount = count($unclaimedArray);
		}

		if($tabType == "*" || $tabType == ".unrevealed" || $tabType == ".revealed" )
		{
			$ownedNfts = GetOwnedNftsFromServer($query_params);
			
			if(!$ownedNfts)
				return;

			$claimedWithRevealedArray = GetRevealNftArraysFromDatabase($ownedNfts);
			$revealedArray = isset($claimedWithRevealedArray["revealedArray"]) ? $claimedWithRevealedArray["revealedArray"] : array();
			$unrevealedArray = isset($claimedWithRevealedArray["unrevealedArray"]) ? $claimedWithRevealedArray["unrevealedArray"] : array();

			if($unrevealedArray)
				$unrevealedCount = count($unrevealedArray);

			if($revealedArray)
				$revealedCount = count($revealedArray);
		}
	}
	catch(Exception $e){
		echo '<h1 class="cs-hero_title cs-white_color cs-center" id="empty_result">
		The server is not connecting or is taking longer than expected...
		If it still appears after refreshing again, contact the team.
		</h1>';
		return;
	}

	$totalCount = $unclaimedCount + $unrevealedCount + $revealedCount;

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
				$info = $totalArray[$index];
				$nfTokenID = $info["nft_id"];
		
				$url = $info["base_uri"];
				$viewType =  str_replace(".", "", $subTabType);

				$jsonString = GetContentsFromValuableUrl($url);
				if(!$jsonString)
				{
					$_SESSION['user_id'] = "";
					header("Refresh:0");
					return;
				}

				$json = json_decode($jsonString, true);
			
				$name = $json['name']; // Pull Name data from URI
				$imgPath = $json['image']; //Pull  Image data from URI
				$videoPath = $json['video']; //Pull Video data from URI

				//For test 
				///var/www/htdocs/ingameassets.cryptoland.host/testNet/lbk/live-metadata/test.json
				if($subTabType == ".unrevealed")
				{
					//For test, use fixed json instead $info["base_uri"] of each nft
					// $test_url = "/var/www/htdocs/ingameassets.cryptoland.host/testNet/lbk/live-metadata/test.json";
					// $revealed_url = str_replace("/live-metadata/", "/revealed-metadata/", $test_url);
					$jsonString = GetContentsFromValuableUrl($revealed_url);

					if(!$jsonString)
						continue;

					$json = json_decode($jsonString, true);
					$revealedImgPath = $json['image']; //Pull  Image data from Revealed URI
					$revealedVideoPath = $json['video']; //Pull Video data from Revealed URI
				//////////////////////////////////////////////////////////////////////////////////////////
				}
				else
				{
					$revealedImgPath  = "";
					$revealedVideoPath = "";
				}

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

		if($totalArray)
		{
			for($index = $cardsCount ;  $index < min(count($totalArray), ($cardsCount + $num_results_on_page - ($cardsCount % $num_results_on_page))) ; $index++)
			{

				if(!isset($totalArray[$index]))
					break;

				$info = $totalArray[$index];
				$nfTokenID = $info["nft_id"];

				$url = $info["base_uri"];
				$viewType =  str_replace(".", "", $tabType);

				$jsonString = file_get_contents($url);
				$json = json_decode($jsonString, true);
			
				$name = $json['name']; // Pull Name data from URI
				$imgPath = $json['image']; //Pull  Image data from URI
				$videoPath = $json['video']; //Pull Video data from URI

				//For test 
				///var/www/htdocs/ingameassets.cryptoland.host/testNet/lbk/live-metadata/test.json
				if($tabType == ".unrevealed")
				{
					//For test, use fixed json instead $info["base_uri"] of each nft
					// $test_url = "/var/www/htdocs/ingameassets.cryptoland.host/testNet/lbk/live-metadata/test.json";
					// $revealed_url = str_replace("/live-metadata/", "/revealed-metadata/", $test_url);
					$jsonString = GetContentsFromValuableUrl($revealed_url);

					if(!$jsonString)
						continue;

					$json = json_decode($jsonString, true);
					$revealedImgPath = $json['image']; //Pull  Image data from Revealed URI
					$revealedVideoPath = $json['video']; //Pull Video data from Revealed URI
				//////////////////////////////////////////////////////////////////////////////////////////
				}
				else
				{
					$revealedImgPath  = "";
					$revealedVideoPath = "";
				}

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

	if(!$isPost || $menuCollection){

		if(!$isPost)
		echo '<h1 class="cs-hero_title cs-white_color cs-center" id="empty_result">Empty Result</h1>';
		echo '<input type=hidden id="totalCount" value="'.$totalCount.'"/>';
		echo '<input type=hidden id="unclaimedCount" value="'.$unclaimedCount.'"/>';
		echo '<input type=hidden id="unrevealedCount" value="'.$unrevealedCount.'"/>';
		echo '<input type=hidden id="revealedCount" value="'.$revealedCount.'"/>';
		echo "</div>";
	}
	
//***************Fiter Menu end*************
?>

	