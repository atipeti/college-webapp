<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

$host = "localhost";
$user = "root";
$password = "";

//kapcsolat létrehozása
$conn = mysqli_connect($host, $user, $password) or die ("Nem sikerült kapcsolódni a szerverhez: " . mysqli_connect_error());

//karakterkészlet beállítása
mysqli_query($conn, "SET NAMES utf8 COLLATE utf8_hungarian_ci");

//adatbázis kiválasztása
mysqli_select_db($conn, "kollegium") or die ("Nem lehet csatlakozni az adatbázishoz: " . mysqli_error($conn));

//fórum hozzáadása
if (isset($_POST["addForum"])) {
	$title = $_POST["title"];
	$creator = $_SESSION["felhasznalonev"];
	$date = date('Y-m-d H:i:s');
	
	$sql = "INSERT INTO forumok (cim, letrehozo, letrehozas, modositas) VALUES ('".$title."', '".$creator."', '".$date."', '".$date."')";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: forums.php');
}

//fórumok lekérése, elhelyezése tömbben
$sql = "SELECT * FROM forumok";
$result = mysqli_query($conn, $sql);
$forumList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//fórum törlése
if (isset($_POST["deleteForum"])) {
	if (isset($_POST["check"])) {
		$forumsToDelete = $_POST["check"];
		for ($i = 0; $i < count($forumsToDelete); $i++) {
			$sql = "DELETE FROM forumok WHERE cim = '".$forumsToDelete[$i]."'";
			mysqli_query($conn, $sql) or die (mysqli_error($conn));
		}
		
		header('Location: forums.php');
	}
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/forums.css">
	<style></style>
	<script>
		function checkTitle() {
			var forums = <?php echo json_encode($forumList) ?>;
			var forumTitle = document.getElementById('title').value;
			
			for (var f in forums) {
				if (forumTitle == forums[f]["cim"]) {
					alert('Már létezik fórum ezzel a címmel! Adj meg egy másik címet!');
					return false;
				}
			}
		}
		
		function confirmDelete() {
			if (window.confirm('Biztosan törlöd a kijelölt fórumo(ka)t?')) {
				return true;
			} else {
				return false;
			}
		}
	</script>
</head>
<body>
	<?php navbar("forums"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<h4>Új fórum indítása</h4>
				<form method="post" accept-charset="utf-8" onsubmit="return checkTitle()">
					<div class="form-group">
						<label for="title">Cím:</label>
						<input type="text" class="form-control" id="title" name="title" maxlength="80" tabindex="1" required />
					</div>
					<input type="submit" class="btn btn-primary" id="addForum" name="addForum" value="Hozzáad" tabindex="2" />
				</form>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<h4>Fórumok</h4>';
				
				if (count($forumList) > 0) {
					echo '<form method="post" onsubmit="return confirmDelete()">';
					echo '<table class="table table-bordered">';
					echo '<tr><th>Cím</th><th>Létrehozta</th><th>Létrehozva</th><th>Módosítva</th><th>Kijelölés</th>';
					foreach ($forumList as $f) {
						$pageId = str_replace(" ", "_", $f["cim"]); //amikor a forum.php oldalon lekérjük GET-tel, vissza kell alakítani (hogy azonosítható legyen vele az adatbázis egyed)!
						echo '<tr>';
						echo '<td><a href="forum.php?title='.$pageId.'">'.$f["cim"].'</a></td>';
						echo '<td><a href="userData.php?user='.$f["letrehozo"].'">'.$f["letrehozo"].'</a></td>';
						echo '<td>'.$f["letrehozas"].'</td>';
						echo '<td>'.$f["modositas"].'</td>';
						if ($f["letrehozo"] == $_SESSION["felhasznalonev"] || $_SESSION["jogosultsag"] == 1) {
							echo '<td><input type="checkbox" name="check[]" value="'.$f["cim"].'"/></td>';
						} else {
							echo '<td><input type="checkbox" name="check[]" value="'.$f["cim"].'" disabled /></td>';
						}
						echo '</tr>';
					}
					echo '</table>';
					echo '<input type="submit" class="btn button" id="deleteForum" name="deleteForum" value="Kijelölt fórum(ok) törlése" />';
					echo '</form>';
					
				}
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>