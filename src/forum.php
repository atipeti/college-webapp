<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

$pageId = isset($_GET['title']) ? $_GET['title'] : NULL;

//oldal átirányítása, ha nincs GET paraméter
if (empty($pageId)) {
	header("Location: forums.php");
	exit();
}

$forumTitle = str_replace("_", " ", $pageId);

$_SESSION["megnyitottForumok"][] = $forumTitle;

$host = "localhost";
$user = "root";
$password = "";

//kapcsolat létrehozása
$conn = mysqli_connect($host, $user, $password) or die ("Nem sikerült kapcsolódni a szerverhez: " . mysqli_connect_error());

//karakterkészlet beállítása
mysqli_query($conn, "SET NAMES utf8 COLLATE utf8_hungarian_ci");

//adatbázis kiválasztása
mysqli_select_db($conn, "kollegium") or die ("Nem lehet csatlakozni az adatbázishoz: " . mysqli_error($conn));

//fórumbejegyzés elküldése
if (isset($_POST["send"])) {
	if ($_POST["topic"] !== "") {
		$topic = $_POST["topic"];
	} else {
		$topic = "(nincs tárgy)";
	}
	$text = $_POST["text"];
	$sender = $_SESSION["felhasznalonev"];
	$timestamp = date('Y-m-d H:i:s');
	
	$sql = "INSERT INTO bejegyzesek (forum, targy, szoveg, kuldo, idopont) VALUES ('".$forumTitle."', '".$topic ."', '".$text."', '".$sender."', '".$timestamp."')";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	$sql = "UPDATE forumok SET modositas = '".$timestamp."' WHERE cim = '".$forumTitle."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: forum.php?title='.$pageId);
}

//keresés a fórumban, a keresési feltételnek megfelelő bejegyzések lekérdezése
$query = "";
if (isset($_POST["search"])) {
	$keyword = $_POST["searchbox"];
	$searchBy = $_POST["searchBy"];
	
	if ($keyword === "") {
		$query = "SELECT * FROM bejegyzesek WHERE forum = '".$forumTitle."' ORDER BY idopont DESC";
	}
	elseif ($searchBy === "inTopic") {
		$query = "SELECT * FROM bejegyzesek WHERE forum = '".$forumTitle."' AND targy LIKE '%".$keyword."%' ORDER BY idopont DESC";
	}
	elseif ($searchBy === "inText") {
		$query = "SELECT * FROM bejegyzesek WHERE forum = '".$forumTitle."' AND szoveg LIKE '%".$keyword."%' ORDER BY idopont DESC";
	}
	else {
		$query = "SELECT * FROM bejegyzesek WHERE forum = '".$forumTitle."' AND kuldo LIKE '%".$keyword."%' ORDER BY idopont DESC";
	}
}
else {
	$query = "SELECT * FROM bejegyzesek WHERE forum = '".$forumTitle."' ORDER BY idopont DESC";
}
$postList = mysqli_query($conn, $query);
$sizeOfPostList = mysqli_num_rows($postList);

//bejegyzés törlése
if (isset($_POST["delete"])) {
	$deleteTime = date('Y-m-d H:i:s');
	$postId = $_POST["postId"];
	
	$sql = "DELETE FROM bejegyzesek WHERE azonosito = '".$postId."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	$sql = "UPDATE forumok SET modositas = '".$deleteTime."' WHERE cim = '".$forumTitle."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: forum.php?title='.$pageId);
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/forum.css">
	<style></style>
	<script></script>
</head>
<body>
	<?php navbar("forums"); ?>
	<div class="container">
		<div class="mainTitle">
			<h2><?php echo $forumTitle; ?></h2>
		</div>
		<div class="row">
			<div class="col-sm-3">
				<div id="searchComment">
					<h4>Keresés a fórumban</h4>
					<form id="searchForm" method="post" accept-charset="utf-8">
						<div class="form-group">
							<label for="searchbox">Kulcsszó: </label>
							<input type="text" class="form-control" id="searchbox" name="searchbox" tabindex="1" />
						</div>
						<div class="form-group">
							<select class="form-control" id="searchBy" name="searchBy" tabindex="2">
								<option value="inTopic" selected>tárgyban</option>
								<option value="inText">szövegben</option>
								<option value="forUser">felhasználóra</option>
							</select>
						</div>
						<input type="submit" class="btn btn-primary" id="search" name="search" value="Keres" tabindex="3" />
					</form>
				</div>
				<hr>
				<div id="addComment">
					<h4>Hozzászólás</h4>
					<form id="sendPostForm" method="post" accept-charset="utf-8">
						<div class="form-group">
							<label for="topic">Tárgy: </label>
							<input type="text" class="form-control" id="topic" name="topic" maxlength="20" tabindex="4" />
						</div>
						<div class="form-group">
							<label for="text">Szöveg: </label>
							<textarea class="form-control" id="text" name="text" tabindex="5" required ></textarea>
						</div>
						<input type="submit" class="btn btn-primary" id="send" name="send" value="Elküld" tabindex="6" />
					</form>
				</div>
			</div>
			<div class="col-sm-9">
				<div id="posts">
				<?php
				echo '<h4>Bejegyzések</h4>';
				
				while ($p = mysqli_fetch_array($postList, MYSQLI_ASSOC)) {
					echo '<div class="post">';
					echo '<h5>Tárgy: '.$p["targy"].'</h5>';
					echo '<span class="span1">Küldő: <a href="userData.php?user='.$p["kuldo"].'">'.$p["kuldo"].'</a></span>';
					echo '<span class="span2">Időpont: '.$p["idopont"].'</span>';
					echo '<div style="clear: both;"></div>';
					echo '<p style="margin-top: 10px;">'.$p["szoveg"].'</p>';
					
					if (($_SESSION["felhasznalonev"] === $p["kuldo"]) || ($_SESSION["jogosultsag"] == 1)) {
						echo '<form method="post">';
						echo '<input type="hidden" value="'.$p["azonosito"].'" name="postId" />';
						echo '<input type="submit" class="btn btn-primary" name="delete" value="Törlés" />';
						echo '</form>';
					}
					
					echo '</div>';
				}
				?>
				</div>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>