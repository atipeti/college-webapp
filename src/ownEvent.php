<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

$progId = isset($_GET['id']) ? str_replace("_", " ", $_GET['id']) : NULL;

//oldal átirányítása, ha nincs GET paraméter
if (empty($progId)) {
	header("Location: calendar.php");
	exit();
}

$host = "localhost";
$user = "root";
$password = "";

//kapcsolat létrehozása
$conn = mysqli_connect($host, $user, $password) or die ("Nem sikerült kapcsolódni a szerverhez: " . mysqli_connect_error());

//karakterkészlet beállítása
mysqli_query($conn, "SET NAMES utf8 COLLATE utf8_hungarian_ci");

//adatbázis kiválasztása
mysqli_select_db($conn, "kollegium") or die ("Nem lehet csatlakozni az adatbázishoz: " . mysqli_error($conn));

//program adatainak lekérdezése
$sql = "SELECT * FROM egyeni_programok WHERE azonosito = '".$progId."'";
$result = mysqli_query($conn, $sql);
$progData = mysqli_fetch_assoc($result);

//program adatainak módosítása
if (isset($_COOKIE['newName']) && isset($_COOKIE['newDatetime']) && isset($_COOKIE['newDuration']) && isset($_COOKIE['newOccasions']) && isset($_COOKIE['newRegularity']) && isset($_COOKIE['newImportance'])) {
	$newStart = date('Y-m-d H:i:s', strtotime($_COOKIE['newDatetime']));
	$newEnd = date('Y-m-d H:i:s', strtotime($newStart) + $_COOKIE['newDuration']*60*60);
	
	$sql = "UPDATE egyeni_programok SET nev = '".$_COOKIE['newName']."', idopont_kezd = '".$newStart."', idopont_vege = '".$newEnd."', alkalmak = '".$_COOKIE['newOccasions']."', rendszeresseg = '".$_COOKIE['newRegularity']."', fontossag = ".$_COOKIE['newImportance']." WHERE azonosito = '".$progId."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	unset($_COOKIE['newName']);
	unset($_COOKIE['newDatetime']);
	unset($_COOKIE['newDuration']);
	unset($_COOKIE['newOccasions']);
	unset($_COOKIE['newRegularity']);
	unset($_COOKIE['newImportance']);
	setcookie('newName', '', time()-3600);
	setcookie('newDatetime', '', time()-3600);
	setcookie('newDuration', '', time()-3600);
	setcookie('newOccasions', '', time()-3600);
	setcookie('newRegularity', '', time()-3600);
	setcookie('newImportance', '', time()-3600);
	
	header('Location: ownEvent.php?id='.$progId);
}

//program törlése
if (isset($_POST["deleteButton"])) {
	$sql = "DELETE FROM egyeni_programok WHERE azonosito = '".$progId."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: calendar.php');
	exit();
}

//kapcsolat bezárása
mysqli_close($conn);

//átirányítás, ha a program adatait tartalmazó tömb üres (pl. azért, mert az URL nem létező programra hivatkozik) VAGY nem a hozzá tartozó felhasználó nyitotta meg pl. a címsorból
if (count($progData) == 0 || $_SESSION["felhasznalonev"] != $progData["felhasznalo"]) {
	header("Location: calendar.php");
	exit();
}
?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/ownEvent.css">
	<style></style>
	<script>
		var oldName = "";
		var oldDatetime = "";
		var oldDuration = "";
		var oldOccasions = "";
		var oldRegularity = "";
		var oldImportance = "";
		var oldImportanceAsNumber = "";
		
		function editProg() {
			$('#editButton').hide();
			$('#deleteButton').hide();
			$('#saveButton').show();
			$('#cancelButton').show();
			
			oldName = $('#tdName').text();
			oldDatetime = $('#tdDatetime').text();
			oldDuration = $('#tdDuration').text();
			oldOccasions = $('#tdOccasions').text();
			oldRegularity = $('#tdRegularity').text();
			oldImportance = $('#tdImportance').text();
			
			$('#tdName').text('').append($('<input type="text" id="newNameInput" class="form-control" />').val(oldName));
			$('#tdDatetime').text('').append($('<input type="date" id="newDateInput" class="form-control" min="2018-01-01" max="2030-12-31" />').val(oldDatetime.split(" ")[0]));
			$('#tdDatetime').append($('<input type="time" id="newTimeInput" class="form-control" />').val(oldDatetime.split(" ")[1]));
			$('#tdDuration').text('').append($('<input type="number" id="newDurationInput" class="form-control" min="0" max="23" step="0.5" required />').val(oldDuration));
			$('#tdOccasions').text('').append($('<input type="number" id="newOccasionsInput" class="form-control" min="1" max="50" />').val(oldOccasions));
			
			var regularityOptions = ["", "hetente", "kéthetente", "három hetente", "havonta"];
			$('#tdRegularity').text('').append($('<select class="form-control" id="newRegularitySelect">'));
			for (i = 0; i < regularityOptions.length; i++) {
				if (regularityOptions[i] == oldRegularity) {
					$('#newRegularitySelect').append($('<option value="' + regularityOptions[i] + '" selected>' + regularityOptions[i] + '</option>'));
				} else {
					$('#newRegularitySelect').append($('<option value="' + regularityOptions[i] + '">' + regularityOptions[i] + '</option>'));
				}
			}
			$('#tdRegularity').append($('</select>'));
			
			var importanceOptions = ["nem fontos", "kevésbé fontos", "fontos", "nagyon fontos/kötelező"];
			$('#tdImportance').text('').append($('<select class="form-control" id="newImportanceSelect">'));
			for (i = 0; i < importanceOptions.length; i++) {
				if (importanceOptions[i] == oldImportance) {
					$('#newImportanceSelect').append($('<option value="' + i + '" selected>' + importanceOptions[i] + '</option>'));
					oldImportanceAsNumber = i;
				} else {
					$('#newImportanceSelect').append($('<option value="' + i + '">' + importanceOptions[i] + '</option>'));
				}
			}
			$('#tdImportance').append($('</select>'));
			
		}
		
		function saveProg() {
			var newName = $('#newNameInput').val();
			var newDatetime = $('#newDateInput').val() + " " + $('#newTimeInput').val();
			var newDuration = $('#newDurationInput').val();
			var newOccasions = $('#newOccasionsInput').val();
			var newRegularity = $('#newRegularitySelect').val();
			var newImportance = $('#newImportanceSelect').val();
			
			if (newName == oldName && newDatetime == oldDatetime && newDuration == oldDuration && newOccasions == oldOccasions && newRegularity == oldRegularity && newImportance === oldImportanceAsNumber) { //ha egyiket se változtatta meg, és így ment, ne töltsön újra az oldal, és ne update-eljen a db-ben
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					cancel();
				}
			} else {
				if (!newName.replace(/\s/g, '').length) {
					alert('Nem hagyhatod üresen a program nevét!');
					return;
				}
				if (newDatetime.length != 16) {
					alert('Nem jó dátum/időpont formátum!');
					return;
				}
				if (newDatetime < '2018-01-01 07:00' || newDatetime > '2030-12-31 23:59') {
					alert('Az időpontnak 2018-01-01 07:00 és 2030-12-31 23:59 között kell lennie!');
					return;
				}
				if (newDuration == "") {
					alert('Nem hagyhatod üresen az időtartam mezőt!');
					return;
				}
				if (newDuration < 0.5 || newDuration > 23 || newDuration%(1/2) != 0) {
					alert('Az időtartam értéke nem megfelelő!');
					return;
				}
				if (newOccasions < 1 || newOccasions > 50) {
					alert('Az alkalmak száma nem lehet kevesebb 1-nél és nem lehet több 50-nél!');
					return;
				}
				if ((newOccasions == 1 && newRegularity != "") || (newOccasions > 1 && newRegularity == "")) {
					alert('Ha a program egyszeri, ne adj meg rendszerességet, ha többszöri, akkor viszont szükséges a rendszeresség megadása!');
					return;
				}
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					document.cookie = "newName = " + newName;
					document.cookie = "newDatetime = " + newDatetime;
					document.cookie = "newDuration = " + newDuration;
					document.cookie = "newOccasions = " + newOccasions;
					document.cookie = "newRegularity = " + newRegularity;
					document.cookie = "newImportance = " + newImportance;
					
					location.reload();
				}
			}
			
		}
		
		function cancel() {
			$('#tdName').text('').append(oldName);
			$('#tdDatetime').text('').append(oldDatetime);
			$('#tdDuration').text('').append(oldDuration);
			$('#tdOccasions').text('').append(oldOccasions);
			$('#tdRegularity').text('').append(oldRegularity);
			$('#tdImportance').text('').append(oldImportance);
			
			$('#editButton').show();
			$('#deleteButton').show();
			$('#saveButton').hide();
			$('#cancelButton').hide();
		}
		
		function confirmDelete() {
			if (window.confirm('Biztosan törölni szeretnéd a programot?')) {
				return true;
			} else {
				return false;
			}
		}
	</script>
</head>
<body>
	<?php navbar(""); ?>
	<div class="container">
		<div class="mainTitle">
			<h2><?php echo $progData["nev"]; ?></h2>
		</div>
		<div class="row">
			<div class="col-sm-3">
				<?php
				if ($progData["felhasznalo"] == $_SESSION["felhasznalonev"]) {
					echo '<h4>Műveletek</h4>';
					echo '<div class="btn button" id="editButton" onclick="editProg()">Adatok szerkesztése</div>';
					echo '<div class="btn button" id="saveButton" style="display: none;" onclick="saveProg()">Mentés</div>';
					echo '<div class="btn button" id="cancelButton" style="display: none;" onclick="cancel()">Mégse</div>';
					
					echo '<form method="post" onsubmit="return confirmDelete()">';
					echo '<input type="submit" class="btn button" id="deleteButton" name="deleteButton" value="Program törlése" />';
					echo '</form>';
				}
				?>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<h4>Program adatai</h4>';
				
				$progDuration = (strtotime($progData["idopont_vege"]) - strtotime($progData["idopont_kezd"])) / 3600;
				$progDatetime = date('Y-m-d H:i', strtotime($progData["idopont_kezd"]));
				
				switch ($progData["fontossag"]) {
					case 3:
						$importance = "nagyon fontos/kötelező"; break;
					case 2:
						$importance = "fontos"; break;
					case 1:
						$importance = "kevésbé fontos"; break;
					case 0:
						$importance = "nem fontos"; break;
					default:
						$importance = "";
				}
				
				echo '<table class="table table-bordered">';
				echo '<tr><td class="firstCol">Név:</td><td id="tdName">'.$progData["nev"].'</td></tr>';
				echo '<tr><td class="firstCol">Időpont:</td><td id="tdDatetime">'.$progDatetime.'</td></tr>';
				echo '<tr><td class="firstCol">Időtartam (óra):</td><td id="tdDuration">'.$progDuration.'</td></tr>';
				echo '<tr><td class="firstCol">Alkalmak száma:</td><td id="tdOccasions">'.$progData["alkalmak"].'</td></tr>';
				echo '<tr><td class="firstCol">Rendszeresség:</td><td id="tdRegularity">'.$progData["rendszeresseg"].'</td></tr>';
				echo '<tr><td class="firstCol">Fontosság:</td><td id="tdImportance">'.$importance.'</td></tr>';
				echo '</table>';
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>