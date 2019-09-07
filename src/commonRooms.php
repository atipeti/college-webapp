<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

$roomName = isset($_GET['room']) ? $_GET['room'] : NULL;
$reservationId = isset($_GET['reservation']) ? $_GET['reservation'] : NULL;

$host = "localhost";
$user = "root";
$password = "";

//kapcsolat létrehozása
$conn = mysqli_connect($host, $user, $password) or die ("Nem sikerült kapcsolódni a szerverhez: " . mysqli_connect_error());

//karakterkészlet beállítása
mysqli_query($conn, "SET NAMES utf8 COLLATE utf8_hungarian_ci");

//adatbázis kiválasztása
mysqli_select_db($conn, "kollegium") or die ("Nem lehet csatlakozni az adatbázishoz: " . mysqli_error($conn));

//közös helyiségek lekérdezése
$sql = "SELECT * FROM kozos_helyisegek";
$result = mysqli_query($conn, $sql);
$commonRoomList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//helyiség leírása, férőhelyek száma, ha a helyiség szerepel az adatbázisban
$isInArray = false;
$roomDescription = "";
$roomCapacity = "";
if ($roomName != null) {
	foreach ($commonRoomList as $array) {
		if (in_array($roomName, $array)) {
			$isInArray = true;
			$roomDescription = $array["leiras"];
			$roomCapacity = $array["ferohely"];
			break;
		}
	}
}

//ha a helyiség szerepel az adatbázisban, akkor a hozzá tartozó foglalások lekérdezése
if ($isInArray) {
	$sql = "SELECT * FROM kozos_helyiseg_hasznalat WHERE helyiseg = '".$roomName."'";
	$result = mysqli_query($conn, $sql);
	$commonRoomUseList = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

//helyiség hozzáadása
if (isset($_POST["addRoom"])) {
	$nameOfRoom = $_POST["nameOfRoom"];
	$description = $_POST["description"];
	$capacity = $_POST["capacity"];
	
	$sql = "INSERT INTO kozos_helyisegek (helyisegnev, leiras, ferohely) VALUES ('".$nameOfRoom."', '".$description."', ".$capacity.")";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	if (isset($roomName)) {
		header('Location: commonRooms.php?room='.$roomName);
	} else {
		header('Location: commonRooms.php');
	}
}

//foglalás elmentése
if (isset($_POST["reserveRoom"])) {
	$date = $_POST["reserveDate"];
	$time = $_POST["reserveTime"];
	$duration = $_POST["reserveDuration"];
	$when = date('Y-m-d H:i:s', strtotime($date." ".$time));
	$until = date('Y-m-d H:i:s', strtotime($when) + $duration*60*60);
	$exclusiveUse = isset($_POST["exclusiveUse"]) ? 1 : 0;
	$username = $_SESSION["felhasznalonev"];
	$updateRowWhereId = "";
	
	foreach ($commonRoomUseList as $c) {
		if ($c["helyiseg"] == $roomName && $c["felhasznalo"] == $_SESSION["felhasznalonev"]) {
			if (!($when >= $c["befejezes"] || $until <= $c["kezdes"])) { //ha ütköznek
				$updateRowWhereId = $c["azonosito"];
				break;
			}
			if ($when == $c["befejezes"] || $until == $c["kezdes"]) {
				if ($when == $c["befejezes"]) {
					$when = $c["kezdes"];
				}
				if ($until == $c["kezdes"]) {
					$until = $c["befejezes"];
				}
				$updateRowWhereId = $c["azonosito"];
				break;
			}
		}
	}
	
	if ($updateRowWhereId == "") {
		$sql = "INSERT INTO kozos_helyiseg_hasznalat (felhasznalo, helyiseg, kezdes, befejezes, kizarolagossag) VALUES ('".$username."', '".$roomName."', '".$when."', '".$until."', ".$exclusiveUse.")";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	} else {
		$sql = "UPDATE kozos_helyiseg_hasznalat SET kezdes = '".$when."', befejezes = '".$until."', kizarolagossag = '".$exclusiveUse."' WHERE azonosito = '".$updateRowWhereId."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	}
	
	header('Location: commonRooms.php?room='.$roomName);
}

//helyiség adatainak módosítása
if (isset($_COOKIE['newName']) || isset($_COOKIE['newDesc']) || isset($_COOKIE['newCapacity'])) {
	$sql = "UPDATE kozos_helyisegek SET helyisegnev = '".$_COOKIE['newName']."', leiras = '".$_COOKIE['newDesc']."', ferohely = '".$_COOKIE['newCapacity']."' WHERE helyisegnev = '".$roomName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	$roomName = $_COOKIE['newName'];
	
	unset($_COOKIE['newName']);
	unset($_COOKIE['newDesc']);
	unset($_COOKIE['newCapacity']);
	setcookie('newName', '', time()-3600);
	setcookie('newDesc', '', time()-3600);
	setcookie('newCapacity', '', time()-3600);
	
	header('Location: commonRooms.php?room='.$roomName);
}

//helyiség törlése
if (isset($_POST["deleteRoom"])) {
	$sql = "DELETE FROM kozos_helyisegek WHERE helyisegnev = '".$roomName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: commonRooms.php');
}

//foglalás adatainak módosítása
if (isset($_COOKIE['newStart']) && isset($_COOKIE['newEnd']) && isset($_COOKIE['newExclusive'])) {
	$sql = "UPDATE kozos_helyiseg_hasznalat SET kezdes = '".$_COOKIE['newStart'].":00', befejezes = '".$_COOKIE['newEnd'].":00', kizarolagossag = '".$_COOKIE['newExclusive']."' WHERE azonosito = '".$reservationId."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	unset($_COOKIE['newStart']);
	unset($_COOKIE['newEnd']);
	unset($_COOKIE['newExclusive']);
	setcookie('newStart', '', time()-3600);
	setcookie('newEnd', '', time()-3600);
	setcookie('newExclusive', '', time()-3600);
	
	header('Location: commonRooms.php?room='.$roomName.'&reservation='.$reservationId);
}

//foglalás törlése
if (isset($_POST["deleteRes"])) {
	$sql = "DELETE FROM kozos_helyiseg_hasznalat WHERE azonosito = '".$reservationId."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: commonRooms.php?room='.$roomName);
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/commonRooms.css">
	<style>
		#coomonRoomsDiv {
			margin-top: 10px;
		}
	</style>
	<script>
		var oldName = "";
		var oldDesc = "";
		var oldCapacity = 0;
		
		var oldStart = "";
		var oldEnd = "";
		var oldExclusive = "";
		
		var clicks = 0;
		function displaySchedule() {
			if (clicks % 2 == 0) {
				$('#schedule').hide();
				$('#dropupArrow').hide();
				$('#dropdownArrow').show();
			} else {
				$('#schedule').show();
				$('#dropupArrow').show();
				$('#dropdownArrow').hide();
			}
			clicks++;
		}
		
		function editRoom() {
			$('#editRoom').hide();
			$('#deleteRoom').hide();
			$('#saveRoom').show();
			$('#cancelRoom').show();
			
			oldName = $('#roomNameAsTitle').text();
			oldDesc = $('#tdDescription').text();
			oldCapacity = $('#tdCapacity').text();
			
			$('#roomNameAsTitle').text('').append($('<input type="text" id="newNameH4" class="form-control" />').val(oldName));
			$('#tdDescription').text('').append($('<textarea id="newDescInput" class="form-control"></textarea>').val(oldDesc));
			$('#tdCapacity').text('').append($('<input type="number" id="newCapacityInput" class="form-control" />').val(oldCapacity));
		}
		
		function saveRoom() {
			var commonRooms = <?php echo json_encode($commonRoomList); ?>;
			var newName = $('#newNameH4').val();
			var newDesc = $('#newDescInput').val();
			var newCapacity = $('#newCapacityInput').val();
			
			if (newName == oldName && newDesc == oldDesc && newCapacity == oldCapacity) {
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					cancelRoom();
				}
			} else {
				for (var c in commonRooms) {
					if (newName != oldName && newName == commonRooms[c]["helyisegnev"]) {
						alert('Már van ilyen nevű helyiség! Adj meg egy másik nevet!');
						return;
					}
				}
				if (!newName.replace(/\s/g, '').length) {
					alert('Nem hagyhatod üresen a helyiség nevét!');
					return;
				}
				
				if (newCapacity != "" && newCapacity % 1 != 0) {
					alert('A férőhelyek száma csak egész szám lehet!');
					return;
				}
				
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					document.cookie = "newName = " + newName;
					document.cookie = "newDesc = " + newDesc;
					document.cookie = "newCapacity = " + newCapacity;
					
					location.reload();
				}
			}
		}
		
		function cancelRoom() {
			$('#roomNameAsTitle').text('').append(oldName);
			$('#tdDescription').text('').append(oldDesc);
			$('#tdCapacity').text('').append(oldCapacity);
			
			$('#editRoom').show();
			$('#deleteRoom').show();
			$('#saveRoom').hide();
			$('#cancelRoom').hide();
		}
		
		function confirmDeleteRoom() {
			if (window.confirm('Biztosan törölni szeretnéd ezt a helyiséget?')) {
				return true;
			} else {
				return false;
			}
		}
		
		function editRes() {
			$('#editRes').hide();
			$('#deleteRes').hide();
			$('#saveRes').show();
			$('#cancelRes').show();
			
			oldStart = $('#tdStartTime').text();
			oldEnd = $('#tdEndTime').text();
			oldExclusive = $('#tdExclusive').text();
			oldExclusiveasBinary = 0;
			
			$('#tdStartTime').text('').append($('<input type="date" id="newStartDate" class="form-control" />').val(oldStart.split(" ")[0]));
			$('#tdStartTime').append($('<input type="time" id="newStartTime" class="form-control" />').val(oldStart.split(" ")[1]));
			$('#tdEndTime').text('').append($('<input type="date" id="newEndDate" class="form-control" />').val(oldEnd.split(" ")[0]));
			$('#tdEndTime').append($('<input type="time" id="newEndTime" class="form-control" />').val(oldEnd.split(" ")[1]));
			$('#tdExclusive').text('').append($('<select id="newExclusiveSelect" class="form-control">'));
			if (oldExclusive == "kizárólagos") {
				$('#newExclusiveSelect').append($('<option value="0">nem kizárólagos</option><option value="1" selected>kizárólagos</option>'));
				oldExclusiveasBinary = 1;
			} else {
				$('#newExclusiveSelect').append($('<option value="0" selected>nem kizárólagos</option><option value="1">kizárólagos</option>'));
			}
			$('#tdExclusive').append($('</select>'));
		}
		
		function saveRes() {
			var newStart = $('#newStartDate').val() + " " + $('#newStartTime').val();
			var newEnd = $('#newEndDate').val() + " " + $('#newEndTime').val();
			var newExclusive = $('#newExclusiveSelect').val();
			
			if (newStart == oldStart && newEnd == oldEnd && newExclusive == oldExclusiveasBinary) {
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					cancelRes();
				}
			} else {
				if (newStart.length != 16 || newEnd.length != 16) {
					alert('Nem (jól) adtad meg a kezdés és/vagy befejezés időpontját!');
					return;
				} else {
					if ($('#newStartTime').val() < "07:00" || $('#newStartTime').val() > "22:30" || $('#newEndTime').val() < "07:30" || $('#newEndTime').val() > "23:00") {
						alert('A kezdő időpont min. 07:00, max. 22:30, a befejező min. 07:30, max. 23:00 lehet!');
						return;
					}
					if (!(["00", "30"].includes($('#newStartTime').val().split(":")[1]) && ["00", "30"].includes($('#newEndTime').val().split(":")[1]))) {
						alert('Csak egész vagy fél órás kezdő, illetve befejező időpont adható meg!');
						return;
					}
					if (newEnd < newStart) {
						alert('A kezdő időpontnak meg kell előznie a befejezőt!');
						return;
					}
				}
				
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					document.cookie = "newStart = " + newStart;
					document.cookie = "newEnd = " + newEnd;
					document.cookie = "newExclusive = " + newExclusive;
					
					location.reload();
				}
			}
		}
		
		function cancelRes() {
			$('#tdStartTime').text('').append(oldStart);
			$('#tdEndTime').text('').append(oldEnd);
			$('#tdExclusive').text('').append(oldExclusive);
			
			$('#editRes').show();
			$('#deleteRes').show();
			$('#saveRes').hide();
			$('#cancelRes').hide();
		}
		
		function confirmDeleteRes() {
			if (window.confirm('Biztosan törölni szeretnéd ezt a foglalást?')) {
				return true;
			} else {
				return false;
			}
		}
		
		function checkAdditionForm() {
			var rooms = <?php echo json_encode($commonRoomList); ?>;
			var room = document.getElementById('nameOfRoom').value;
			
			for (var i in rooms) {
				if (room == rooms[i]["helyisegnev"]) {
					alert('Már létezik ilyen helyiség! Adj meg egy másik nevet!');
					return false;
				}
			}
		}
		
		//csak akkor engedi lefoglalni a helyiséget adott időpontban, ha az nem foglalt (kizárólagosság) és nem telt be (férőhely)
		function checkReservationForm() {
			var reservations = <?php echo json_encode($commonRoomUseList); ?>;
			var thisRoom = <?php echo json_encode($roomName); ?>; //https://stackoverflow.com/questions/168214/pass-a-php-string-to-a-javascript-variable-and-escape-newlines
			var thisCapacity = <?php echo json_encode($roomCapacity); ?>;
			var dateOfRes = document.getElementById('reserveDate').value;
			var startTime = document.getElementById('reserveTime').value + ':00';
			var isExclusive = document.getElementById('exclusiveUse').checked ? 1 : 0; //https://stackoverflow.com/questions/9887360/how-can-i-check-if-a-checkbox-is-checked
			var thisUser = <?php echo json_encode($_SESSION["felhasznalonev"]); ?>;
			
			var splitTime = startTime.split(":");
			var someDate = new Date();
			someDate.setHours(splitTime[0], splitTime[1]);
			someDate.setMinutes(someDate.getMinutes() + document.getElementById('reserveDuration').value*60);
			var endTime = (someDate.getHours() + ':' + (someDate.getMinutes()<10?'0':'') + someDate.getMinutes()) + ':00';
			
			var numOfReservationsForThisRoom = 0;
			for (var r in reservations) {
				var splitDatetime = reservations[r]["kezdes"].split(" ");
				if (thisRoom == reservations[r]["helyiseg"] && dateOfRes == splitDatetime[0]) {
					if (!(startTime >= reservations[r]["befejezes"].split(" ")[1] || endTime <= splitDatetime[1])) {
						if (reservations[r]["felhasznalo"] != thisUser) {
							numOfReservationsForThisRoom++;
						}
						//feltétel, hogy ugyanaz a felhasználó ne tudja többször ugyanabban az időintervallumban lefoglalni a helyiséget
						if (reservations[r]["felhasznalo"] == thisUser) {
							if (!window.confirm('Már lefoglaltad a helyiséget ebben az időpontban! Felülírod a korábbi foglalást?')) {
								return false;
							}
						}
						if (reservations[r]["kizarolagossag"] == 1) {
							alert('Már lefoglalták a helyiséget ebben az időpontban!');
							return false;
						}
					}
					if (reservations[r]["felhasznalo"] == thisUser && (startTime == reservations[r]["befejezes"].split(" ")[1] || endTime == splitDatetime[1])) {
						if (!window.confirm('Már lefoglaltad a helyiséget közvetlenül ez előtt/után. Szeretnéd összevonni a két időpontot?')) {
							return false;
						}
					}
				}
			}
			
			if (numOfReservationsForThisRoom == thisCapacity) {
				alert('Betelt a helyiség ebben az intervallumban!');
				return false;
			}
			
			//ha kizárólagosra akarja lefoglalni, mikor már van (nem kizárólagos) foglalás erre az időpontra/intervallumra
			if (numOfReservationsForThisRoom > 0 && isExclusive) {
				alert('Nem foglalhatod le kizárólagosra a helyiséget, mert már van (nem kizárólagos) foglalás az intervallumon belül!');
				return false;
			}
		}
	</script>
</head>
<body>
	<?php navbar("commonRooms"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<h4>Közös helyiségek</h4>
				<div id="coomonRoomsDiv">
					<?php
					$tabIndex = 1;
					echo '<ul>';
					foreach ($commonRoomList as $room) {
						echo '<li><a href="commonRooms.php?room='.$room["helyisegnev"].'" tabindex="'.$tabIndex.'">'.$room["helyisegnev"].'</a></li>';
						$tabIndex++;
					}
					echo '</ul>';
					?>
				</div>
				<?php if ($isInArray) : ?>
				<hr>
				<div id="reserveRoomDiv">
					<h5><?php echo $roomName.' használat'; ?></h5>
					<form method="post" accept-charset="utf-8" onsubmit="return checkReservationForm()">
						<div class="form-group">
							<label for="reserveDate">Dátum: </label>
							<input type="date" class="form-control" id="reserveDate" name="reserveDate" min="<?php echo nowToDate(); ?>" max="2030-12-31" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" required />
						</div>
						<div class="form-group">
							<label for="reserveTime">Időpont: </label>
							<input type="time" class="form-control" id="reserveTime" name="reserveTime" min="07:00" max="23:00" step="1800" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" required />
						</div>
						<div class="form-group">
							<label for="reserveDuration">Időtartam (óra): </label>
							<input type="number" class="form-control" id="reserveDuration" name="reserveDuration" min="0" max="6" step="0.5" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" required />
						</div>
						<div class="form-group">
							<label for="exclusiveUse"><input type="checkbox" id="exclusiveUse" name="exclusiveUse" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" /> Kizárólagos használat</label>
						</div>
						<input type="submit" class="btn btn-primary" id="reserveRoom" name="reserveRoom" value="Lefoglal" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" />
					</form>
				</div>
				<?php endif; ?>
				<?php if ($_SESSION["jogosultsag"] == 1) : ?>
				<hr>
				<div id="addNewRoomDiv">
					<h5>Új helyiség hozzáadása</h5>
					<form method="post" accept-charset="utf-8" onsubmit="return checkAdditionForm()">
						<div class="form-group">
							<label for="nameOfRoom">Helyiség neve: </label>
							<input type="text" class="form-control" id="nameOfRoom" name="nameOfRoom" maxlength="80" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" required />
						</div>
						<div class="form-group">
							<label for="description">Leírás: </label>
							<textarea class="form-control" id="description" name="description" tabindex="<?php $tabIndex++; echo $tabIndex; ?>"></textarea>
						</div>
						<div class="form-group">
							<label for="capacity">Férőhely: </label>
							<input type="number" class="form-control" id="capacity" name="capacity" min="1" max="1000" step="1" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" required />
						</div>
						<input type="submit" class="btn btn-primary" id="addRoom" name="addRoom" value="Hozzáad" tabindex="<?php $tabIndex++; echo $tabIndex; ?>" />
					</form>
				</div>
				<?php endif; ?>
			</div>
			<div class="col-sm-9">
				<?php
				if ($isInArray && !isset($reservationId)) {
					echo '<h4 id="roomNameAsTitle">'.$roomName.'</h4>';
					if ($_SESSION["jogosultsag"] == 1) {
						echo '<form method="post" onsubmit="return confirmDeleteRoom()">';
						echo '<input type="submit" class="btn button" id="deleteRoom" name="deleteRoom" value="Helyiség törlése" />';
						echo '</form>';
						echo '<div class="btn button" id="editRoom" onclick="editRoom()">Adatok szerkesztése</div>';
						echo '<div class="btn button" id="cancelRoom" style="display: none;" onclick="cancelRoom()">Mégse</div>';
						echo '<div class="btn button" id="saveRoom" style="display: none;" onclick="saveRoom()">Mentés</div>';
						
					}
					echo '<div style="clear: both; margin-bottom: 10px;"></div>';
					echo '<table class="table table-bordered">';
					echo '<tr><td class="firstCol">Leírás</td><td id="tdDescription">'.$roomDescription.'</td></tr><tr><td class="firstCol">Férőhelyek</td><td id="tdCapacity">'.$roomCapacity.'</td></tr>';
					echo '</table>';
					
					$week = date('o-W');
					if (isset($_GET['week'])) {
						//ha a felhasználó rossz formátumot ír az URL-be
						$pieces = explode("-", $_GET['week']);
						$digits = ["01", "02", "03", "04", "05", "06", "07", "08", "09"];
						if (count($pieces) == 2 && ($pieces[0] > 2001 && $pieces[0] < 2030) && (in_array($pieces[1], $digits) || ($pieces[1] >= 10 && $pieces[1] <= 52))) {
							$week = $_GET['week'];
						}
					}
					
					$today = date('Y-m-d');
					$firstDayOfGivenWeek = date('Y-m-d', strtotime(str_replace("-", "W", $week)));
					
					//előző és következő hét
					$prev = date('o-W', strtotime('-1 week', strtotime($firstDayOfGivenWeek)));
					$next = date('o-W', strtotime('+1 week', strtotime($firstDayOfGivenWeek))); //Y helyett o: https://www.php.net/manual/en/function.date.php
					
					
					echo '<h5 onclick="displaySchedule()" style="cursor: pointer;">'.$roomName.' beosztás <img src="images/drop-up.svg" id="dropupArrow" height="10px" width="10px" alt="dropup"/>';
					echo '<img src="images/drop-down.svg" id="dropdownArrow" height="10px" width="10px" alt="dropdown" style="display: none;"/></h5>';
					echo '<div id="schedule">';
					echo '<h5><a href="commonRooms.php?room='.$roomName.'&week='.$prev.'">Előző hét</a> / E hét / <a href="commonRooms.php?room='.$roomName.'&week='.$next.'">Következő hét</a></h5>';
					
					$days = array("H", "K", "Sze", "Cs", "P", "Szo", "V");
					//$daysNotAbbreviated = array("Hétfő", "Kedd", "Szerda", "Csütörtök", "Péntek", "Szombat", "Vasárnap");
					
					echo '<table class="table table-bordered">';
					$hour = 7;
					$minute = '00';
					for ($i = 0; $i < 18; $i++) {
						$date = date('m/d', strtotime($firstDayOfGivenWeek));
						echo '<tr>';
						if ($i == 0) {
							for ($j = 0; $j <= count($days); $j++) {
								if ($j == 0) {
									echo '<th>Beosztás</th>';
								} else {
									echo '<th';
									if (date('Y-m-d', strtotime($date)) == $today) {
										echo ' class="today"';
									}
									echo '>'.$days[$j-1].' | '.$date.'</th>';
									$date = date('m/d', strtotime('+1 day', strtotime($date)));
								}
								
							}
						} else {
							for ($k = 0; $k <= count($days); $k++) {
								$time = $hour.':'.$minute;
								if ($hour < 10) {
									$time = '0'.$time;
								}
								$date = date('Y-m-d', strtotime($date));
								$cellId = date('Y-m-d H:i:s', strtotime($date.' '.$time));
								if ($k == 0) {
									echo '<td>'.$time.'</td>';
								} else {
									echo '<td';
									if ($date == $today) {
										echo ' class="today"';
									}
									echo ' id="'.$cellId.'">';
									
									foreach ($commonRoomUseList as $c) {
										$nextHour = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($cellId)));
										if ($c["kezdes"] == $cellId || ($c["kezdes"] < $cellId && $c["befejezes"] > $cellId) || ($c["kezdes"] > $cellId && $c["kezdes"] < $nextHour)) {
											echo '<div class="reservation';
											if ($c["kizarolagossag"] == 1) {
												echo ' exclusive';
											}
											echo '">';
											echo '<a href="commonRooms.php?room='.$roomName.'&reservation='.$c["azonosito"].'">'.$c["felhasznalo"].'</a></div>';
										}
									}
									echo '</td>';
									$date = date('m/d', strtotime('+1 day', strtotime($date)));
								}
							}
							$hour += 1;
						}
						echo '</tr>';
					}
					echo '</table>';
					echo '</div>';
				}
				if (!($isInArray)) {
					echo 'Nincs kiválasztva közös helyiség!';
				}
				if ($isInArray && isset($reservationId)) {
					echo '<h4>Helyiség használat/foglalás adatai</h4>';
					foreach ($commonRoomUseList as $c) {
						if ($reservationId == $c["azonosito"]) {
							if ($_SESSION["felhasznalonev"] == $c["felhasznalo"] || $_SESSION["jogosultsag"] == 1) {
								echo '<form method="post" onsubmit="return confirmDeleteRes()">';
								echo '<input type="submit" class="btn button" id="deleteRes" name="deleteRes" value="Foglalás törlése" />';
								echo '</form>';
								echo '<div class="btn button" id="editRes" onclick="editRes()">Foglalás szerkesztése</div>';
								echo '<div class="btn button" id="cancelRes" style="display: none;" onclick="cancelRes()">Mégse</div>';
								echo '<div class="btn button" id="saveRes" style="display: none;" onclick="saveRes()">Mentés</div>';
							}
							
							echo '<div style="clear: both; margin-bottom: 10px;"></div>';
							echo '<table class="table table-bordered">';
							echo '<tr><td class="firstCol">Helyiség</td><td id="tdRoomName">'.$c["helyiseg"].'</td></tr>';
							echo '<tr><td class="firstCol">Kezdés</td><td id="tdStartTime">'.date('Y-m-d H:i', strtotime($c["kezdes"])).'</td></tr>';
							echo '<tr><td class="firstCol">Befejezés</td><td id="tdEndTime">'.date('Y-m-d H:i', strtotime($c["befejezes"])).'</td></tr>';
							$isExclusive = "nem kizárólagos";
							if ($c["kizarolagossag"]) { $isExclusive = "kizárólagos"; }
							echo '<tr><td class="firstCol">Kizárólagosság</td><td id="tdExclusive">'.$isExclusive.'</td></tr>';
							echo '</table>';
							break;
						}
					}
				}
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>