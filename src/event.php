<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

$eventName = isset($_GET['id']) ? str_replace("_", " ", $_GET['id']) : NULL;

//oldal átirányítása, ha nincs GET paraméter
if (empty($eventName)) {
	header("Location: addEvent.php");
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

//az eseményhez tartozó visszajelzések lekérdezése
$sql = "SELECT felhasznalo, fontossag, reszvetel FROM visszajelzesek WHERE esemeny = '".$eventName."'";
$result = mysqli_query($conn, $sql);
$feedbacksForThisEvent = mysqli_fetch_all($result, MYSQLI_ASSOC);

//visszajelzés
if (isset($_POST["feedbackButton"])) {
	$whatToSet = "";
	$whatToSetFor = "";
	
	if (isset($_POST["importance"])) {
		$whatToSet = "fontossag";
		$whatToSetFor = $_POST["importance"];
	}
	if (isset($_POST["participation"])) {
		$whatToSet = "reszvetel";
		$whatToSetFor = $_POST["participation"];
	}
	
	$sql = "SELECT * FROM visszajelzesek WHERE felhasznalo = '".$_SESSION["felhasznalonev"]."' AND esemeny = '".$eventName."'";
	$result = mysqli_query($conn, $sql);
	
	if (mysqli_num_rows($result) == 0) {
		$sql = "INSERT INTO visszajelzesek (felhasznalo, esemeny, ".$whatToSet.") VALUES ('".$_SESSION["felhasznalonev"]."', '".$eventName."', ".$whatToSetFor.")";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	} else {
		$sql = "UPDATE visszajelzesek SET ".$whatToSet." = ".$whatToSetFor." WHERE felhasznalo = '".$_SESSION['felhasznalonev']."' AND esemeny = '".$eventName."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	}
	
	header('Location: event.php?id='.str_replace(" ", "_", $eventName));
}

//végleges időpont elmentése, időpont opciók törlése
if (isset($_POST["saveOption"])) {
	if (isset($_POST["chooseOption"])) {
		$optionToBeSaved = explode("|", $_POST["chooseOption"]);
		$optionStartAt = date('Y-m-d H:i:s', strtotime($optionToBeSaved[0]));
		$optionEndAt = date('Y-m-d H:i:s', strtotime($optionToBeSaved[1]));
		
		$sql = "UPDATE esemenyek SET idopont_kezd = '".$optionStartAt."', idopont_vege = '".$optionEndAt."', veglegesseg = 1 WHERE nev = '".$eventName."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
		
		$sql = "DELETE from esemeny_opciok WHERE esemeny_nev = '".$eventName."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
		
		header('Location: event.php?id='.str_replace(" ", "_", $eventName));
	}	
}

//esemény adatainak módosítása
if (isset($_COOKIE['newName']) && isset($_COOKIE['newDesc']) && (isset($_COOKIE['newDatetime']) || (isset($_COOKIE['newDatetimeOptions']) && isset($_COOKIE['oldDatetimeOptions']))) && isset($_COOKIE['newDuration']) && isset($_COOKIE['newOccasions']) && isset($_COOKIE['newRegularity']) && isset($_COOKIE['newMandatory']) && isset($_COOKIE['newExpDate'])) {
	$sql = "UPDATE esemenyek SET nev = '".$_COOKIE['newName']."' WHERE nev = '".$eventName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	$eventName = $_COOKIE['newName'];
	
	if ($_COOKIE['newDatetime'] != "") {
		$newStart = $_COOKIE['newDatetime'].':00';
		$newEnd = date('Y-m-d H:i:s', strtotime($newStart) + $_COOKIE['newDuration']*60*60);
		$sql = "UPDATE esemenyek SET idopont_kezd = '".$newStart."', idopont_vege = '".$newEnd."' WHERE nev = '".$eventName."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	} else if ($_COOKIE['newDatetimeOptions'] != "") {
		$newDatetimeOptions = explode(" v. ", $_COOKIE['newDatetimeOptions']);
		$oldDatetimeOptions = explode(" v. ", $_COOKIE['oldDatetimeOptions']);
		$sizeOfNew = count($newDatetimeOptions);
		$sizeOfOld = count($oldDatetimeOptions);
		
		if ($_COOKIE['newDatetimeOptions'] == $_COOKIE['oldDatetimeOptions']) {
			for ($i = 0; $i < $sizeOfOld; $i++) {
				$newEnd = date('Y-m-d H:i:s', strtotime($oldDatetimeOptions[$i].':00') + $_COOKIE['newDuration']*60*60);
				$sql = "UPDATE esemeny_opciok SET idopont_vege = '".$newEnd."' WHERE esemeny_nev = '".$eventName."' AND idopont_kezd = '".$oldDatetimeOptions[$i].":00'";
				mysqli_query($conn, $sql) or die (mysqli_error($conn));
			}
		} else {
			$difference = abs($sizeOfNew - $sizeOfOld);
			if ($difference > 0) { //tehát nem ugyanannyi
				if ($sizeOfNew == 1) { //ha újonnan csak egy választható opciót adott meg, azt fix időpontnak vesszük
					$newStart = $newDatetimeOptions[0].':00';
					$newEnd = date('Y-m-d H:i:s', strtotime($newStart) + $_COOKIE['newDuration']*60*60);
					$sql = "UPDATE esemenyek SET idopont_kezd = '".$newStart."', idopont_vege = '".$newEnd."', veglegesseg = 1 WHERE nev = '".$eventName."'";
					mysqli_query($conn, $sql) or die (mysqli_error($conn));
					$sql = "DELETE FROM esemeny_opciok WHERE esemeny_nev = '".$eventName."'";
					mysqli_query($conn, $sql) or die (mysqli_error($conn));
				} else {
					if ($sizeOfNew > $sizeOfOld) {
						for ($i = $sizeOfNew; $i > 0; $i--) {
							$newStart = $newDatetimeOptions[$i-1].':00';
							$newEnd = date('Y-m-d H:i:s', strtotime($newStart) + $_COOKIE['newDuration']*60*60);
							if ($i > $sizeOfOld) {
								$sql = "INSERT INTO esemeny_opciok (esemeny_nev, idopont_kezd, idopont_vege) VALUES ('".$eventName."', '".$newStart."', '".$newEnd."')";
								mysqli_query($conn, $sql) or die (mysqli_error($conn));
							} else {
								$sql = "UPDATE esemeny_opciok SET idopont_kezd = '".$newStart."', idopont_vege = '".$newEnd."' WHERE esemeny_nev = '".$eventName."' AND idopont_kezd = '".$oldDatetimeOptions[$i-1]."'";
								mysqli_query($conn, $sql) or die (mysqli_error($conn));
							}
						}
					} else { //ez csak kisebb lehet, mert az egyenlőséget már a legkülső if feltétellel kizártuk
						for ($j = $sizeOfOld; $j > 0; $j--) {
							if ($j > $sizeOfNew) {
								$sql = "DELETE FROM esemeny_opciok WHERE esemeny_nev = '".$eventName."' AND idopont_kezd = '".$oldDatetimeOptions[$j-1]."'";
								mysqli_query($conn, $sql) or die (mysqli_error($conn));
							} else {
								$newStart = $newDatetimeOptions[$j-1].':00';
								$newEnd = date('Y-m-d H:i:s', strtotime($newStart) + $_COOKIE['newDuration']*60*60);
								$sql = "UPDATE esemeny_opciok SET idopont_kezd = '".$newStart."', idopont_vege = '".$newEnd."' WHERE esemeny_nev = '".$eventName."' AND idopont_kezd = '".$oldDatetimeOptions[$j-1]."'";
								mysqli_query($conn, $sql) or die (mysqli_error($conn));
							}
						}
					}
				}
			}
		}
	}
	
	$sql = "UPDATE esemenyek SET leiras = '".$_COOKIE['newDesc']."', kotelezoseg = '".$_COOKIE['newMandatory']."', visszajelzesi_hatarido = '".$_COOKIE['newExpDate'].":00'";
	
	if ($_COOKIE['newOccasions'] != "") {
		$sql .= ", alkalmak = '".$_COOKIE['newOccasions']."', rendszeresseg = '".$_COOKIE['newRegularity']."'";
	}
	$sql .= " WHERE nev = '".$eventName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	unset($_COOKIE['newName']);
	unset($_COOKIE['newDesc']);
	unset($_COOKIE['newDatetime']);
	unset($_COOKIE['newDatetimeOptions']);
	unset($_COOKIE['newDuration']);
	unset($_COOKIE['newOccasions']);
	unset($_COOKIE['newRegularity']);
	unset($_COOKIE['newMandatory']);
	unset($_COOKIE['newExpDate']);
	setcookie('newName', '', time()-3600);
	setcookie('newDesc', '', time()-3600);
	setcookie('newDatetime', '', time()-3600);
	setcookie('newDatetimeOptions', '', time()-3600);
	setcookie('newDuration', '', time()-3600);
	setcookie('newOccasions', '', time()-3600);
	setcookie('newRegularity', '', time()-3600);
	setcookie('newMandatory', '', time()-3600);
	setcookie('newExpDate', '', time()-3600);
	
	header('Location: event.php?id='.str_replace(" ", "_", $eventName));	
}

//esemény törlése
if (isset($_POST["deleteEvent"])) {
	$sql = "DELETE FROM esemenyek WHERE nev = '".$eventName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	$sql = "DELETE FROM esemeny_opciok WHERE esemeny_nev = '".$eventName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: addEvent.php');
	exit();
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/event.css">
	<style></style>
	<script>
		var oldName = "";
		var oldDesc = "";
		var oldDatetime = "";
		var oldDuration = "";
		var oldOccasions = "";
		var oldRegularity = "";
		var oldMandatory = "";
		var oldExpDate = "";
		var oldDatetimeOptions = [];
		var oldMandatoryAsBinary = 0;
		var count = 1; //addOption() és removeOption() function-ökhöz kell
		var countMax = 10;
		
		var clicks = 0;
		function displayTable() {
			if (clicks % 2 == 0) {
				$('.feedbackTable').hide();
				$('#toBeHiddenOrShownAlongWithTheTable').hide();
				$('.ifNoFeedback').hide();
				$('.dropup').hide();
				$('.dropdown').show();
			} else {
				$('.feedbackTable').show();
				$('#toBeHiddenOrShownAlongWithTheTable').show();
				$('.ifNoFeedback').show();
				$('.dropup').show();
				$('.dropdown').hide();
			}
			clicks++;
		}
		
		var clicks2 = 0;
		function displayAlgorithmResult() {
			if (clicks2 % 2 == 0) {
				$('#algorithmDiv').hide();
				$('#dropupArrow').hide();
				$('#dropdownArrow').show();
			} else {
				$('#algorithmDiv').show();
				$('#dropupArrow').show();
				$('#dropdownArrow').hide();
			}
			clicks2++;
		}
		
		function displayHiddenCellsAndSubmit() {
			$('#pToBeHidden').hide();
			$('.cellToBeDisplayed').show();
			$('#saveOption').show();
			$('#cancelSavingOption').show();
		}
		
		function hideCellsAndButtons() {
			$('#pToBeHidden').show();
			$('.cellToBeDisplayed').hide();
			$('#saveOption').hide();
			$('#cancelSavingOption').hide();
		}
		
		function confirmSavingOption() {
			//https://stackoverflow.com/questions/1423777/how-can-i-check-whether-a-radio-button-is-selected-with-javascript
			if ($('input[name=chooseOption]:checked').length == 0) {
				alert('Nem jelöltél ki egy időpontot sem a táblázatban!');
				return false;
			}
			if (window.confirm('Biztosan elmented a kiválasztott opciót az esemény végleges időpontjaként?')) {
				return true;
			} else {
				return false;
			}
		}
		
		function editEvent() {
			$('#editButton').hide();
			$('#deleteEvent').hide();
			$('#algorithm').hide();
			$('#saveButton').show();
			$('#cancelButton').show();
			
			oldName = $('#tdName').text();
			oldDesc = $('#tdDesc').text();
			oldDatetime = $('#tdDatetime').text();
			oldDuration = $('#tdDuration').text();
			oldOccasions = $('#tdNumOfOccasions').text();
			oldRegularity = $('#tdRegularity').text();
			oldMandatory = $('#tdMandatory').text();
			oldExpDate = $('#tdExpDate').text();
			
			
			$('#tdName').text('').append($('<input type="text" id="newNameInput" class="form-control" />').val(oldName));
			$('#tdDesc').text('').append($('<textarea id="newDescInput" class="form-control" />').val(oldDesc));
			if (oldDatetime.length == 16) {
				$('#tdDatetime').text('').append($('<input type="date" id="newDateInput" class="form-control" />').val(oldDatetime.split(" ")[0]));
				$('#tdDatetime').append($('<input type="time" id="newTimeInput" class="form-control" min="07:00" max="23:00" />').val(oldDatetime.split(" ")[1]));
			} else {
				oldDatetimeOptions = oldDatetime.split(" v. ");
				count = oldDatetimeOptions.length;
				$('#tdDatetime').text('').append($('<a href="#" onclick="addOption()">Hozzáadás</a> <a href="#" onclick="removeOption()">Törlés</a>'));
				for (i = 0; i < oldDatetimeOptions.length; i++) {
					$('#tdDatetime').append($('<input type="date" id="newDateInput' + (i+1) + '" class="form-control" />').val(oldDatetimeOptions[i].split(" ")[0]));
					$('#tdDatetime').append($('<input type="time" id="newTimeInput' + (i+1) + '" class="form-control" />').val(oldDatetimeOptions[i].split(" ")[1]));
				}
			}
			$('#tdDuration').text('').append($('<input type="number" id="newDurationInput" class="form-control" min="0.5" max="6" step="0.5" />').val(oldDuration));
			$('#tdNumOfOccasions').text('').append($('<input type="number" id="newOccasionsInput" class="form-control" min="1" max="10" />').val(oldOccasions));
			
			//ownEvent.php
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
			
			if (oldMandatory == "kötelező") {
				oldMandatoryAsBinary = 1;
				$('#tdMandatory').text('').append($('<select class="form-control" id="newMandatorySelect"><option value="1" selected>kötelező</option><option value="0">nem kötelező</option></select>'));
			} else {
				$('#tdMandatory').text('').append($('<select class="form-control" id="newMandatorySelect"><option value="1">kötelező</option><option value="0" selected>nem kötelező</option></select>'));
			}
			$('#tdExpDate').text('').append($('<input type="date" id="newExpDateInput" class="form-control" />').val(oldExpDate.split(" ")[0]));
			$('#tdExpDate').append($('<input type="time" id="newExpTimeInput" class="form-control" min="07:00" max="23:00" />').val(oldExpDate.split(" ")[1]));
		}
		
		//addEvent.php
		function addOption() {
			if (count < countMax) {
				if (count < oldDatetimeOptions.length) { //ha olyat vettünk el, aminek volt értéke, azt értékkel együtt adjuk vissza
					$('#tdDatetime').append($('<input type="date" id="newDateInput' + (count+1) + '" class="form-control" />').val(oldDatetimeOptions[count].split(" ")[0]));
					$('#tdDatetime').append($('<input type="time" id="newTimeInput' + (count+1) + '" class="form-control" />').val(oldDatetimeOptions[count].split(" ")[1]));
				} else {
					$('#tdDatetime').append($('<input type="date" id="newDateInput' + (count+1) + '" class="form-control" />'));
					$('#tdDatetime').append($('<input type="time" id="newTimeInput' + (count+1) + '" class="form-control" />'));
				}
				count++;
			} else {
				alert('Több opciót nem tudsz hozzáadni!');
			}
		}
		function removeOption() {
			if (count > 1) {
				$('#tdDatetime').children().last().remove();
				$('#tdDatetime').children().last().remove();
				count--;
			}
		}
		
		function saveEvent() {
			var events = <?php echo json_encode($eventList); ?>;
			var newName = $('#newNameInput').val();
			var newDesc = $('#newDescInput').val();
			var newDatetime = "";
			var newDatetimeOptions = [];
			var joinedArray = "";
			if (oldDatetime.length == 16) {
				newDatetime = $('#newDateInput').val() + " " + $('#newTimeInput').val();
			} else {
				for (i = 0; i < count; i++) {
					newDatetimeOptions[i] = $('#newDateInput' + (i+1)).val() + " " + $('#newTimeInput' + (i+1)).val();
				}
				joinedArray = newDatetimeOptions.join(' v. '); //https://www.w3schools.com/jsref/jsref_join.asp
			}
			var newDuration = $('#newDurationInput').val();
			var newOccasions = "";
			if (oldOccasions != "") {
				newOccasions = $('#newOccasionsInput').val();
			}
			var newRegularity = "";
			if (oldRegularity != "") {
				newRegularity = $('#newRegularitySelect').val();
			}
			var newMandatory = $('#newMandatorySelect').val();
			var newExpDate = $('#newExpDateInput').val() + " " + $('#newExpTimeInput').val();
			
			if (newName == oldName && newDesc == oldDesc && (oldDatetime == newDatetime || oldDatetime == joinedArray) && newDuration == oldDuration && newOccasions == oldOccasions && newRegularity == oldRegularity && newMandatory == oldMandatoryAsBinary && newExpDate == oldExpDate) {
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					cancel();
				}
			} else {
				var nameChecker = false;
				for (var i in events) {
					if (newName != oldName && newName == events[i]["nev"]) {
						nameChecker = true;
						break;
					}
				}
				if (nameChecker == true) {
					alert('Már létezik ilyen nevű esemény! Adj meg egy másik nevet!');
					return;
				}
				if (!newName.replace(/\s/g, '').length) { //https://stackoverflow.com/questions/10261986/how-to-detect-string-which-contains-only-spaces
					alert('Nem hagyhatod üresen az esemény nevét!');
					return;
				}
				
				if (newDatetime != "") {
					if (newDatetime.length != 16) {
						alert('Az időpont nem (jól) lett megadva!');
						return;
					} else if (newDatetime <= getMinDate()) {
						alert('A megadott időpont már elmúlt!');
						//return;
					}
					
					if (newDatetime != " " && newExpDate != " " && newExpDate >= newDatetime) {
						alert('A visszajelzési határidőnek a program időpontja előtt kell lennie!');
						return;
					}
				}
				
				if (newExpDate.length != 16) {
					alert('Az visszajelzési határidő nem (jól) lett megadva!');
					return;
				} else if (newExpDate <= getMinDate()) {
					alert('A megadott határidő már elmúlt!');
					//return;
				}
				
				if (newDuration % 0.5 != 0 || newDuration < 0.5 || newDuration > 6) {
					alert('Az időtartam minimum fél óra, maximum 6 óra lehet, és feles közökkel növelhető!');
					return;
				}
				
				if (newOccasions != "" && (newOccasions < 1 || newOccasions > 10 || newOccasions % 1 != 0)) {
					alert('Az alkalmak száma minimum 1, maximum 10 lehet, és csak egész szám adható meg!');
					return;
				}
				
				if ((newOccasions == 1 && newRegularity != "") || (newOccasions > 1 && newRegularity == "")) {
					alert('Ha az esemény egyszeri, ne állíts be rendszerességet, ha többszöri, akkor viszont szükséges a rendszeresség beállítása!');
					return;
				}
				
				for (i = 0; i < newDatetimeOptions.length; i++) {
					if (newDatetimeOptions[i].length != 16) {
						alert('Valamelyik időpont opciót nem (jól) adtad meg!');
						return;
					} else {
						if (newDatetimeOptions[i] <= getMinDate()) {
							alert('Csak olyan időpont opciót adj meg, ami még nem múlt el!');
							return;
						}
						if (newExpDate >= newDatetimeOptions[i]) {
							alert('Az egyes időpont opciók nem előzhetik meg a visszajelzési határidőt!');
							return;
						}
					}
				}
				
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					document.cookie = "newName = " + newName;
					document.cookie = "newDesc = " + newDesc;
					document.cookie = "newDatetime = " + newDatetime;
					document.cookie = "newDuration = " + newDuration;
					document.cookie = "newOccasions = " + newOccasions;
					document.cookie = "newRegularity = " + newRegularity;
					document.cookie = "newMandatory = " + newMandatory;
					document.cookie = "newExpDate = " + newExpDate;
					document.cookie = "newDatetimeOptions = " + joinedArray;
					document.cookie = "oldDatetimeOptions = " + oldDatetimeOptions.join(' v. ');
					
					location.reload();
				}
			}
		}
		
		function cancel() {
			$('#tdName').text('').append(oldName);
			$('#tdDesc').text('').append(oldDesc);
			$('#tdDatetime').text('').append(oldDatetime);
			$('#tdDuration').text('').append(oldDuration);
			$('#tdNumOfOccasions').text('').append(oldOccasions);
			$('#tdRegularity').text('').append(oldRegularity);
			$('#tdMandatory').text('').append(oldMandatory);
			$('#tdExpDate').text('').append(oldExpDate);
			
			$('#editButton').show();
			$('#deleteEvent').show();
			$('#algorithm').show();
			$('#saveButton').hide();
			$('#cancelButton').hide();
		}
		
		function getMinDate() {
			var today = new Date();
			var day = today.getDate();
			var month = today.getMonth() + 1; //a január 0
			var year = today.getFullYear();
			var hours = today.getHours();
			var minutes = today.getMinutes();
			
			if (day < 10) {
				day = '0' + day;
			}
			if (month < 10) {
				month = '0' + month;
			}
			if (hours < 10) {
				hours = '0' + hours;
			}
			if (minutes < 10) {
				minutes = '0' + minutes;
			}
			
			today = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
			return today;
		}
		
		function confirmDelete() {
			if (window.confirm('Biztosan törölni szeretnéd az eseményt?')) {
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
			<h2><?php echo $eventName; ?></h2>
		</div>
		<div class="row">
			<div class="col-sm-3">
				<?php
				$optionsForThisEvent = [];
				$index = 0;
				foreach ($eventOptionsList as $option) {
					if ($option["esemeny_nev"] == $eventName) {
						$optionsForThisEvent[$index]["kezd"] = $option["idopont_kezd"];
						$optionsForThisEvent[$index]["vege"] = $option["idopont_vege"];
						$index++;
					}
				}
				
				$now = date('Y-m-d H:i');
				$expDateIsOver = false;
				$isOwnEvent = false;
				$isDefinite = false;
				$isMandatory = false;
				$announcer = "";
				$mandatory = "";
				$description = "";
				$numOfOccasions = 1;
				$eventRegularity = "";
				$eventDatetime = "";
				$eventDuration = "";
				$eventExpDate = "";
				foreach ($eventList as $event) {
					if ($event["nev"] == $eventName) {
						$announcer = $event["meghirdeto"];
						$description = $event["leiras"];
						$numOfOccasions = (int)$event["alkalmak"];
						$eventRegularity = $event["rendszeresseg"];
						$eventExpDate = date('Y-m-d H:i', strtotime($event["visszajelzesi_hatarido"]));
						if ($announcer == $_SESSION["felhasznalonev"]) {
							$isOwnEvent = true;
						}
						if ($eventExpDate <= $now) {
							$expDateIsOver = true;
						}
						if ($event["veglegesseg"]) {
							$isDefinite = true;
							$eventDatetime = date('Y-m-d H:i', strtotime($event["idopont_kezd"]));
							$eventDuration = (strtotime($event["idopont_vege"]) - strtotime($event["idopont_kezd"])) / 3600;
						} else {
							if (($isOwnEvent || $_SESSION["jogosultsag"] == 1) && count($optionsForThisEvent) > 0) {
								for ($i = 0; $i < count($optionsForThisEvent); $i++) {
									$eventDatetime .= date('Y-m-d H:i', strtotime($optionsForThisEvent[$i]["kezd"]));
									if ($i != count($optionsForThisEvent)-1) { $eventDatetime .= ' v. '; }
								}
							} else {
								$eventDatetime = "A visszajelzések függvényében!";
							}
							foreach ($eventOptionsList as $option) {
								if ($option["esemeny_nev"] == $event["nev"]) {
									$eventDuration = (strtotime($option["idopont_vege"]) - strtotime($option["idopont_kezd"])) / 3600;
									break;
								}
							}
						}
						if ($event["kotelezoseg"]) {
							$mandatory = "kötelező";
							$isMandatory = true;
						} else {
							$mandatory = "nem kötelező";
						}
						break;
					}
				}
				
				//a meghirdető és/vagy az admin számára elérhető
				if ($isOwnEvent || $_SESSION["jogosultsag"] == 1) {
					echo '<h4>Műveletek</h4>';
					echo '<div class="btn button" id="editButton" onclick="editEvent()">Adatok szerkesztése</div>';
					echo '<div class="btn button" id="saveButton" style="display: none;" onclick="saveEvent()">Mentés</div>';
					echo '<div class="btn button" id="cancelButton" style="display: none;" onclick="cancel()">Mégse</div>';
					
					echo '<form method="post" onsubmit="return confirmDelete()">';
					echo '<input type="submit" class="btn button" id="deleteEvent" name="deleteEvent" value="Esemény törlése" />';
					echo '</form>';
					
					if ($isOwnEvent && !($isDefinite)) {
						echo '<form method="post">';
						echo '<input type="submit" class="btn button" id="algorithm" name="algorithm" value="Időpont véglegesítése" />';
						echo '</form>';
					}
					
					echo '<hr>';
				}
				
				//visszajelezni minden felhasználó tud, a meghirdető kivételével
				if (!$isOwnEvent) {
					echo '<h4>Visszajelzés</h4>';
					echo '<form id="feedbackForm" method="post" accept-charset="utf-8">';
					if ($isDefinite) {
						echo '<div class="form-group">';
						echo '<label for "participation">Részvétel: </label>';
						echo '<select id="participation" name="participation" class="form-control">';
						echo '<option value="1">részt veszek</option><option value="0">nem veszek részt</option>';
						echo '</select>';
						echo '</div>';
					} else {
						echo '<div class="form-group">';
						echo '<label for="importance">Fontosság: </label>';
						echo '<select id="importance" name="importance" class="form-control" required>';
						if ($isMandatory) {
							echo '<option value="0" disabled>nem fontos</option><option value="1" disabled>kevésbé fontos</option><option value="2" disabled>fontos</option><option value="3" selected>nagyon fontos/kötelező</option>';
						} else {
							echo '<option value="" selected>&nbsp;</option><option value="0">nem fontos</option><option value="1">kevésbé fontos</option><option value="2">fontos</option><option value="3">nagyon fontos/kötelező</option>';
						}
						echo '</select>';
						echo '</div>';
					}
					
					echo '<input type="submit" class="btn btn-primary" id="feedbackButton" name="feedbackButton" value="Visszajelzés" ';
					if ($expDateIsOver) {
						echo 'disabled ';
					}
					echo '/>';
					echo '</form>';
				}
				
				//minden felhasználó számára látható
				$numOfFeedback = 0;
				foreach ($feedbackList as $feedback) {
					if ($feedback["esemeny"] == $eventName) { //minden felhasználó-esemény páros csak egyszer szerepel a táblában, ezért elég csak az esemény nevének előfordulásait összeszámolni
						$numOfFeedback++;
					}
				}
				$numOfUsers = count($usernameList) - 1; //az esemény meghirdetőjét nem számoljuk bele
				
				$percent = 0;
				if ($numOfUsers != 0) {
					$percent = ($numOfFeedback/$numOfUsers)*100;
				}
				echo '<hr>';
				echo '<h6>Visszajelzések aránya:</h6>';
				echo '<div class="progress">'; //https://www.w3schools.com/bootstrap/bootstrap_progressbars.asp
				echo '<div class="progress-bar" role="progressbar" aria-valuenow="'.$percent.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$percent.'%;">'.$numOfFeedback.'/'.$numOfUsers.'</div>';
				echo '</div>';
				
				?>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<h4>Esemény adatai</h4>';
				
				echo '<table class="table table-bordered">';
				echo '<tr><td class="firstCol">Név:</td><td id="tdName">'.$eventName.'</td></tr>';
				echo '<tr><td class="firstCol">Leírás:</td><td id="tdDesc">'.$description.'</td></tr>';
				echo '<tr><td class="firstCol">Meghirdető:</td><td><a href="userData.php?user='.$announcer.'" style="color: #6C6C6C;">'.$announcer.'</a></td></tr>';
				echo '<tr><td class="firstCol">Időpont:</td><td id="tdDatetime">'.$eventDatetime.'</td></tr>';
				echo '<tr><td class="firstCol">Időtartam (óra):</td><td id="tdDuration">'.$eventDuration.'</td></tr>';
				if ($numOfOccasions > 1) {
					echo '<tr><td class="firstCol">Alkalmak száma:</td><td id="tdNumOfOccasions">'.$numOfOccasions.'</td></tr>';
					echo '<tr><td class="firstCol">Rendszeresség:</td><td id="tdRegularity">'.$event["rendszeresseg"].'</td></tr>';
				}
				echo '<tr><td class="firstCol">Kötelezőség:</td><td id="tdMandatory">'.$mandatory.'</td></tr>';
				echo '<tr><td class="firstCol">Visszajelzés határideje:</td><td id="tdExpDate" style="color: red;">'.date('Y-m-d H:i', strtotime($event["visszajelzesi_hatarido"])).'</td></tr>';
				echo '</table>';
				echo '<hr>';
				
				function convertImportanceToString($importanceAsNumber) {
					switch ($importanceAsNumber) {
						case 3:
							$importanceString = "nagyon fontos/kötelező"; break;
						case 2:
							$importanceString = "fontos"; break;
						case 1:
							$importanceString = "kevésbé fontos"; break;
						case 0:
							$importanceString = "nem fontos"; break;
						default:
							$importanceString = "-";
					}
					return $importanceString;
				}
				
				echo '<div id="feedbackDiv">';
				if ($_SESSION["felhasznalonev"] == $announcer) {
					echo '<h5 onclick="displayTable()" style="cursor: pointer;">Visszajelzések <img src="images/drop-up.svg" class="dropup" height="10px" width="10px" alt="dropup"/>';
					echo '<img src="images/drop-down.svg" class="dropdown" height="10px" width="10px" alt="dropdown" style="display: none;"/></h5>';
					if (count($feedbacksForThisEvent) > 0) {
						echo '<table class="table table-bordered feedbackTable">';
						echo '<tr><th>Felhasználó</th>';
						if (!$isDefinite) { echo '<th>Fontosság</th>'; }
						if ($isDefinite) { echo '<th>Részvétel</th>'; }
						echo '</tr>';
						foreach ($feedbacksForThisEvent as $feedback) {
							$importanceString = "-";
							$participationString = "-";
							if ($feedback["fontossag"] != "") {
								$importanceString = convertImportanceToString($feedback["fontossag"]);
								$importanceString .= ' ('.$feedback["fontossag"].')';
							}
							if ($feedback["reszvetel"] == 1) {
								$participationString = "részt vesz";
							} else if ($feedback["reszvetel"] == 0) {
								$participationString = "nem vesz részt";
							}
							echo '<tr><td><a href="userData.php?user='.$feedback["felhasznalo"].'" style="color: #555555;">'.$feedback["felhasznalo"].'</a></td>';
							if (!$isDefinite) { echo '<td>'.$importanceString.'</td>'; }
							if ($isDefinite) { echo '<td>'.$participationString.'</td>'; }
							echo '</tr>';
						}
						echo '</table>';
					} else {
						echo '<p class="ifNoFeedback">Még nem érkezett visszajelzés!</p>';
					}
				} else {
					echo '<h5 onclick="displayTable()" style="cursor: pointer;">Visszajelzésed az eseményre <img src="images/drop-up.svg" class="dropup" height="10px" width="10px" alt="dropup"/>';
					echo '<img src="images/drop-down.svg" class="dropdown" height="10px" width="10px" alt="dropdown" style="display: none;"/></h5>';
					$matchFound = false;
					foreach ($feedbacksForThisEvent as $feedback) {
						if ($feedback["felhasznalo"] == $_SESSION["felhasznalonev"]) {
							$matchFound = true;
							$importanceString = convertImportanceToString($feedback["fontossag"]);
							$participationString = "-";
							$substring = "";
							if ($feedback["reszvetel"] == 1) {
								$participationString = "részt veszek";
							} else if ($feedback["reszvetel"] == 0) {
								$participationString = "nem veszek részt";
							}
							echo '<table class="table table-bordered feedbackTable">';
							if ($isDefinite) {
								echo '<tr><td class="firstCol">Részvétel:</td><td>'.$participationString.'</td><tr>';
								$substring = "részvételi szándékot";
							} else {
								echo '<tr><td class="firstCol">Fontosság:</td><td>'.$importanceString.'</td><tr>';
								$substring = "fontosságot";
							}
							echo '</table>';
							echo '<p id="toBeHiddenOrShownAlongWithTheTable">A megadott '.$substring.' egy újabb visszajelzés elküldésével tudod módosítani.</p>';
							break;
						}
					}
					if (!$matchFound) {
						echo '<p class="ifNoFeedback">Még nem jeleztél vissza erre az eseményre!</p>';
					}
				}
				echo '</div>';
				
				//calendar.php
				function defineAllOccasions($occasions, $regularity, $basicStart, $basicEnd) {
					$allOccasions = [];
					switch ($regularity) {
						case "hetente":
							$timeToAdd = "+1 week"; break;
						case "kéthetente":
							$timeToAdd = "+2 weeks"; break;
						case "három hetente":
							$timeToAdd = "+3 weeks"; break;
						case "havonta":
							$timeToAdd = "+1 month"; break;
						default:
							$timeToAdd = "+0 week";
					}
					$actualStart = $basicStart;
					$actualEnd = $basicEnd;
					for ($i = 0; $i < $occasions; $i++) {
						$allOccasions[$i]["kezd"] = $actualStart;
						$allOccasions[$i]["vege"] = $actualEnd;
						$actualStart = date('Y-m-d H:i:s', strtotime($timeToAdd, strtotime($actualStart)));
						$actualEnd = date('Y-m-d H:i:s', strtotime($timeToAdd, strtotime($actualEnd)));
					}
					
					return $allOccasions;
				}
				
				//ALGORITMUS
				if (isset($_POST["algorithm"])) {
					$optionScoresPerUser = [];
					$optionScores = [];
					$eventConflicts = [];
					$statistics = [];
					$mainEventOccasions = [];
					for ($i = 0; $i < count($optionsForThisEvent); $i++) {
						$mainEventOccasions[$i] = defineAllOccasions($numOfOccasions, $eventRegularity, $optionsForThisEvent[$i]["kezd"], $optionsForThisEvent[$i]["vege"]);
						$optionScores[$i] = 0;
						$statistics[$i]["opcio_kezd"] = date('Y-m-d H:i', strtotime($optionsForThisEvent[$i]["kezd"]));
						$statistics[$i]["opcio_vege"] = date('Y-m-d H:i', strtotime($optionsForThisEvent[$i]["vege"]));
						$statistics[$i]["program_utkozes"] = 0;
						$statistics[$i]["nincs_utkozes"] = 0;
						$statistics[$i]["fontossag3"] = 0;
						for ($j = 0; $j < count($usernameList); $j++) {
							$optionScoresPerUser[$j] = 0;
							
							if ($usernameList[$j]["felhasznalonev"] != $announcer) { //az eseményt meghirdető felhasználót nem vizsgáljuk
								$importance = 0;
								if ($isMandatory) { //ha kötelező az esemény (függetlenül attól, hogy visszajelzett-e) ($participation marad 1, hiszen kötelező a részvétel)
									$importance = 3;
								} else {
									foreach ($feedbacksForThisEvent as $array) {
										if (in_array($usernameList[$j]["felhasznalonev"], $array)) {
											$importance = $array["fontossag"];
											break;
										}
									}
								}
								
								$relevantEvents = 0;
								$noConflictWithEvent = 0;
								$otherEventOccasions = [];
								foreach ($eventList as $event) { //többi kollégiumi eseménnyel való összehasonlítás
									if ($event["nev"] != $eventName && $event["veglegesseg"]) {
										$conflictCount = 0;
										$otherEventOccasions[$event["nev"]] = defineAllOccasions($event["alkalmak"], $event["rendszeresseg"], $event["idopont_kezd"], $event["idopont_vege"]);
										for ($evOc1 = 0; $evOc1 < $event["alkalmak"]; $evOc1++) { //az eseménylistából aktuálisan vizsgált esemény alkalmai
											for ($evOc2 = 0; $evOc2 < $numOfOccasions; $evOc2++) { //a fő esemény - amelynek időpont opcióit vizsgáljuk - alkalmai
												$relevantEvents++;
												if (!($otherEventOccasions[$event["nev"]][$evOc1]["kezd"] >= $mainEventOccasions[$i][$evOc2]["vege"] || $otherEventOccasions[$event["nev"]][$evOc1]["vege"] <= $mainEventOccasions[$i][$evOc2]["kezd"])) { //ha ütköznek
													if ($isMandatory || $event["kotelezoseg"]) { //elvileg nem ütközhetnek (addEvent.php nem engedi elmenteni úgy), de jobb biztosra menni
														$optionScoresPerUser[$j] += 100;
														//stathoz:
														$eventConflicts[$i][$conflictCount]["opcio"] = date('Y-m-d H:i', strtotime($mainEventOccasions[$i][$evOc2]["kezd"]))."-".date('H:i', strtotime($mainEventOccasions[$i][$evOc2]["vege"]));
														$eventConflicts[$i][$conflictCount]["esemeny_nev"] = $event["nev"];
														$eventConflicts[$i][$conflictCount]["esemeny_ido"] = date('Y-m-d H:i', strtotime($otherEventOccasions[$event["nev"]][$evOc1]["kezd"]))."-".date('H:i', strtotime($otherEventOccasions[$event["nev"]][$evOc1]["vege"]));
														$conflictCount++;
													} else {
														$thisImportance = 0;
														$thisParticipation = 1;
														foreach ($feedbackList as $feedback) {
															if ($feedback["felhasznalo"] == $usernameList[$j]["felhasznalonev"] && $feedback["esemeny"] == $event["nev"]) {
																$thisImportance = $feedback["fontossag"];
																$thisParticipation = $feedback["reszvetel"];
																break;
															}
														}
														
														if ($thisParticipation == 0) {
															$noConflictWithEvent++; //ha egy nem kötelező eseményre azt jelezte vissza, hogy nem vesz részt, az olyan, mintha nem is ütközne
														} else {
															$optionScoresPerUser[$j] += $importance * $thisImportance;
														}
													}
												} else {
													$noConflictWithEvent++;
												}
											}
										}
									}
								}
								
								$relevantProgs = 0;
								$noConflictWithProg = 0;
								$progOccasions = [];
								foreach ($progList as $prog) { //saját programokkal való összehasonlítás
									if ($prog["felhasznalo"] == $usernameList[$j]["felhasznalonev"]) {
										$progOccasions[$prog["azonosito"]] = defineAllOccasions($prog["alkalmak"], $prog["rendszeresseg"], $prog["idopont_kezd"], $prog["idopont_vege"]);
										for ($prOc = 0; $prOc < $prog["alkalmak"]; $prOc++) { //a program alkalmai
											for ($evOc = 0; $evOc < $numOfOccasions; $evOc++) { //a fő esemény - amelynek időpont opcióit vizsgáljuk - alkalmai
												$relevantProgs++;
												if (!($progOccasions[$prog["azonosito"]][$prOc]["kezd"] >= $mainEventOccasions[$i][$evOc]["vege"] || $progOccasions[$prog["azonosito"]][$prOc]["vege"] <= $mainEventOccasions[$i][$evOc]["kezd"])) { //ha ütköznek
													$optionScoresPerUser[$j] += $importance * $prog["fontossag"];
													$statistics[$i]["program_utkozes"] += 1;
												}
												else {
													$noConflictWithProg++;
												}
											}
										}
									}
								}
								
								if ($noConflictWithEvent == $relevantEvents && $noConflictWithProg == $relevantProgs) {
									//nem ütközik semmivel, tehát a legjobb pontszámot kapja (mínusz)
									$optionScoresPerUser[$j] -= $importance * 3;
									$statistics[$i]["nincs_utkozes"] += 1;
									if ($importance == 3) {
										$statistics[$i]["fontossag3"] += 1;
									}
								}
							}
							$optionScores[$i] += $optionScoresPerUser[$j];
						}
					}
					
					$minScore = "";
					$indexOrIndexesOfMin = [];
					if (count($optionScores) > 0) {
						$minScore = min($optionScores);
						for ($i = 0; $i < count($optionScores); $i++) {
							if ($optionScores[$i] == $minScore) {
								array_push($indexOrIndexesOfMin, $i);
							}
						}
					}
					$bestOptions = [];
					for ($i = 0; $i < count($indexOrIndexesOfMin); $i++) {
						$bestOptions[$i]["kezd"] = $optionsForThisEvent[$indexOrIndexesOfMin[$i]]["kezd"];
						$bestOptions[$i]["vege"] = $optionsForThisEvent[$indexOrIndexesOfMin[$i]]["vege"];
					}
					
					$relevantUsers = count($optionScoresPerUser) - 1;
					echo '<hr>';
					echo '<h5 onclick="displayAlgorithmResult()" style="cursor: pointer;">Időpont véglegesítése <img src="images/drop-up.svg" id="dropupArrow" height="10px" width="10px" alt="dropup"/>';
					echo '<img src="images/drop-down.svg" id="dropdownArrow" height="10px" width="10px" alt="dropdown" style="display: none;"/></h5>';
					echo '<div id="algorithmDiv">';
					echo '<p>Vizsgált felhasználók száma: '.$relevantUsers.'</p>';
					
					
					echo '<form method="post" onsubmit="return confirmSavingOption()">';
					echo '<table class="table table-bordered" id="algoTable">';
					echo '<tr><th>Opció</th><th>Nincs ütközés (összes)</th>';
					if (!$isMandatory) { echo '<th>Nincs ütközés ÉS 3-as fontosság</th>'; }
					echo '<th>Ütközés egyéni programmal</th><th class="cellToBeDisplayed" style="display: none;">Kijelölés</th></tr>';
					foreach ($statistics as $stat) {
						$endOfOption = date('H:i', strtotime($stat["opcio_vege"]));
						echo '<tr><td>'.$stat["opcio_kezd"].'-'.$endOfOption.'</td><td>'.$stat["nincs_utkozes"].'</td>';
						if (!$isMandatory) { echo '<td>'.$stat["fontossag3"].'</td>'; }
						echo '<td>'.$stat["program_utkozes"].'</td><td class="cellToBeDisplayed" style="display: none;"><input type="radio" name="chooseOption" value="'.$stat["opcio_kezd"].'|'.$stat["opcio_vege"].'" /></td></tr>';
					}
					echo '</table>';
					
					if (count($eventConflicts) > 0) {
						echo '<h6 style="color: #BB0000;">Más kollégiumi eseménnyel való ütközés(ek) - <span style="font-style: italic;">ha legalább az egyik esemény kötelező</span>:</h6>';
						echo '<ul>';
						foreach ($eventConflicts as $conflict) {
							foreach ($conflict as $c) {
								echo '<li>'.$c["opcio"].' &gt;|&lt; ';
								echo '<a href="event.php?id='.str_replace(" ", "_", $c["esemeny_nev"]).'" style="color: #555555;">'.$c["esemeny_nev"].'</a> ('.$c["esemeny_ido"].')</li>';
							}
						}
						echo '</ul>';
					}
					
					echo '<h6>Legjobb opció(k):</h6>';
					echo '<ul>';
					foreach ($bestOptions as $option) {
						echo '<li><mark>'.date('Y-m-d H:i', strtotime($option["kezd"])).'-'.date('H:i', strtotime($option["vege"])).'</mark></li>';
					}
					echo '</ul>';
					
					echo '<p id="pToBeHidden">Az időpontot tudod véglegesíteni <a onclick="displayHiddenCellsAndSubmit()" style="cursor: pointer; font-weight: bold;">itt</a> a táblázatban, vagy fentebb, a Műveletek menü Adatok szerkesztése gombjára kattintva.</p>';
					
					echo '<input type="submit" class="btn button" id="saveOption" name="saveOption" value="Időpont véglegesítése" style="display: none;" />';
					echo '<div class="btn button" id="cancelSavingOption" style="display: none;" onclick="hideCellsAndButtons()">Mégse</div>';
					echo '</form>';
					echo '</div>';
				}
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>