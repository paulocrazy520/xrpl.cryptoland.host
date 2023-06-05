<?php


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


$sql_db_host = "localhost";
$sql_db_user = "root";
$sql_db_pass = "==Z9C2ZQ@MfqebQ";
$sql_db_name = "sample_CL_data_v1";
$site_url = "https://sb236.cryptoland.io";

?>

<!DOCTYPE html>
<html>

<head>
	<title>User NFT Collection</title>
	<meta charset="utf-8">
	<style>
		html {
			font-family: Tahoma, Geneva, sans-serif;
			padding: 20px;
			background-color: #F8F9F9;
		}

		table {
			border-collapse: collapse;
			/* width: 500px; */
		}

		td,
		th {
			padding: 10px;
		}

		th {
			background-color: #54585d;
			color: #ffffff;
			font-weight: bold;
			font-size: 13px;
			border: 1px solid #54585d;
		}

		td {
			color: #636363;
			border: 1px solid #dddfe1;
		}

		tr {
			background-color: #f9fafb;
		}

		tr:nth-child(odd) {
			background-color: #ffffff;
		}

		.pagination {
			list-style-type: none;
			padding: 10px 0;
			display: inline-flex;
			justify-content: space-between;
			box-sizing: border-box;
		}

		.pagination li {
			box-sizing: border-box;
			padding-right: 10px;
		}

		.pagination li a {
			box-sizing: border-box;
			background-color: #e2e6e6;
			padding: 8px;
			text-decoration: none;
			font-size: 12px;
			font-weight: bold;
			color: #616872;
			border-radius: 4px;
		}

		.pagination li a:hover {
			background-color: #d4dada;
		}

		.pagination .next a,
		.pagination .prev a {
			text-transform: uppercase;
			font-size: 12px;
		}

		.pagination .currentpage a {
			background-color: #518acb;
			color: #fff;
		}

		.pagination .currentpage a:hover {
			background-color: #518acb;
		}
	</style>
</head>
<?php


// Below is optional, remove if you have already connected to your database.
$mysqli = mysqli_connect($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
$where = '';//'WHERE user_id != "0"';
// Get the total number of records from our table "students".
$total_pages = $mysqli->query('SELECT * FROM user_nft ' . $where . '')->num_rows;

// Check if the page number is specified and check if it's a number, if not return the default page number which is 1.
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;

// Number of results to show on each page.
$num_results_on_page = 100;


if ($stmt = $mysqli->prepare('SELECT * FROM user_nft ' . $where . ' ORDER BY user_id LIMIT ?,?')) {
	// Calculate the page to get the results we need from our table.
	$calc_page = ($page - 1) * $num_results_on_page;
	$stmt->bind_param('ii', $calc_page, $num_results_on_page);
	$stmt->execute();
	// Get the results...
	$result = $stmt->get_result();
?>

	<body>
		<h2 align=center>User NFT Collection</h2>

		<?php if (ceil($total_pages / $num_results_on_page) > 0) : ?>
			<ul class="pagination">
				<?php if ($page > 1) : ?>
					<li class="prev"><a href="user_collection.php?page=<?php echo $page - 1 ?>">Prev</a></li>
				<?php endif; ?>

				<?php if ($page > 3) : ?>
					<li class="start"><a href="user_collection.php?page=1">1</a></li>
					<li class="dots">...</li>
				<?php endif; ?>

				<?php if ($page - 2 > 0) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page - 2 ?>"><?php echo $page - 2 ?></a></li><?php endif; ?>
				<?php if ($page - 1 > 0) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page - 1 ?>"><?php echo $page - 1 ?></a></li><?php endif; ?>

				<li class="currentpage"><a href="user_collection.php?page=<?php echo $page ?>"><?php echo $page ?></a></li>

				<?php if ($page + 1 < ceil($total_pages / $num_results_on_page) + 1) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page + 1 ?>"><?php echo $page + 1 ?></a></li><?php endif; ?>
				<?php if ($page + 2 < ceil($total_pages / $num_results_on_page) + 1) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page + 2 ?>"><?php echo $page + 2 ?></a></li><?php endif; ?>

				<?php if ($page < ceil($total_pages / $num_results_on_page) - 2) : ?>
					<li class="dots">...</li>
					<li class="end"><a href="user_collection.php?page=<?php echo ceil($total_pages / $num_results_on_page) ?>"><?php echo ceil($total_pages / $num_results_on_page) ?></a></li>
				<?php endif; ?>

				<?php if ($page < ceil($total_pages / $num_results_on_page)) : ?>
					<li class="next"><a href="user_collection.php?page=<?php echo $page + 1 ?>">Next</a></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<table>
			<tr>
				<th>id</th>
				<th>nft_uuid</th>
				<th>nft_id</th>
				<th>user_id</th>

				<th>date_created</th>
				<th>last_update</th>
				<th>owner_wallet</th>
			</tr>
			<?php while ($row = $result->fetch_assoc()) : ?>
				<tr>
					<td><?php echo $row['id']; ?></td>
					<td><?php echo $row['nft_uuid']; ?></td>
					<td><a href="https://test.bithomp.com/nft/<?php echo $row['nft_id']; ?>" target="_blank"><?php echo $row['nft_id']; ?></a></td>
					<td><a href="https://fast.dev.srv1.sandboxit.dev/0307202305/?user_id=<?php echo $row['user_id']; ?>" target="_blank"><?php echo $row['user_id']; ?></a></td>
					<td><?php echo $row['date_created']; ?></td>
					<td><?php echo $row['last_update']; ?></td>
					<td><a href="https://test.bithomp.com/explorer/<?php echo $row['owner_wallet']; ?>" target="_blank"><?php echo $row['owner_wallet']; ?></a></td>
				</tr>
			<?php endwhile; ?>
		</table>
		<?php if (ceil($total_pages / $num_results_on_page) > 0) : ?>
			<ul class="pagination">
				<?php if ($page > 1) : ?>
					<li class="prev"><a href="user_collection.php?page=<?php echo $page - 1 ?>">Prev</a></li>
				<?php endif; ?>

				<?php if ($page > 3) : ?>
					<li class="start"><a href="user_collection.php?page=1">1</a></li>
					<li class="dots">...</li>
				<?php endif; ?>

				<?php if ($page - 2 > 0) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page - 2 ?>"><?php echo $page - 2 ?></a></li><?php endif; ?>
				<?php if ($page - 1 > 0) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page - 1 ?>"><?php echo $page - 1 ?></a></li><?php endif; ?>

				<li class="currentpage"><a href="user_collection.php?page=<?php echo $page ?>"><?php echo $page ?></a></li>

				<?php if ($page + 1 < ceil($total_pages / $num_results_on_page) + 1) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page + 1 ?>"><?php echo $page + 1 ?></a></li><?php endif; ?>
				<?php if ($page + 2 < ceil($total_pages / $num_results_on_page) + 1) : ?><li class="page"><a href="user_collection.php?page=<?php echo $page + 2 ?>"><?php echo $page + 2 ?></a></li><?php endif; ?>

				<?php if ($page < ceil($total_pages / $num_results_on_page) - 2) : ?>
					<li class="dots">...</li>
					<li class="end"><a href="user_collection.php?page=<?php echo ceil($total_pages / $num_results_on_page) ?>"><?php echo ceil($total_pages / $num_results_on_page) ?></a></li>
				<?php endif; ?>

				<?php if ($page < ceil($total_pages / $num_results_on_page)) : ?>
					<li class="next"><a href="user_collection.php?page=<?php echo $page + 1 ?>">Next</a></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>
	</body>

</html>
<?php
	$stmt->close();
}
?>