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

//esemény elmentése
if (isset($_POST["save"])) {
	$name = $_POST["eventName"];
	$description = $_POST["eventDesc"];
	$duration = $_POST["duration"];
	$occasions = $_POST["occasions"];
	$regularity = "";
	if ($occasions > 1) {
		$regularity = $_POST["regularity"];
	}
	$isMandatory = $_POST["isMandatory"];
	$isDefinite = 0; //alapértelmezetten nem véglegesnek tekintjük a programot
	$arranger = $_SESSION["felhasznalonev"];
	$expDate = "";
	$dates = [];
	$times = [];
	$options = [];
	
	$dates = $_POST["dates"];
	$times = $_POST["times"];
	for ($x = 0; $x < count($dates); $x++) {
		$options[$x] = date('Y-m-d H:i:s', strtotime($dates[$x]." ".$times[$x]));
	}
	
	if ($_POST["expirationDate"] != "" && $_POST["expirationTime"] != "") {
		$expDate = date('Y-m-d H:i:s', strtotime($_POST["expirationDate"]." ".$_POST["expirationTime"]));
	} else {
		$minDate = "";
		for ($x = 0; $x < count($options); $x++) {
			if ($x == 0) {
				$minDate = $options[$x];
			} else {
				if ($options[$x] < $minDate) {
					$minDate = $options[$x];
				}
			}
		}
		$expDate = date('Y-m-d H:i:s', strtotime('-2 days', strtotime($minDate))); //ha nem adott meg visszajelzési határidőt, akkor a legkorábbi időpont (opció) előtt 2 nappal lesz
		
		$now = date('Y-m-d H:i:s');
		if ($expDate <= $now) {
			$then = strtotime($minDate);
			$expDate = date('Y-m-d H:i:s', strtotime($now) + ($then - strtotime($now))/2); //ha a leghamarabbi időpont (opció) a jelentől számítva 2 napon belül van
		}
	}
	
	
	//ADATBÁZISBA MENTÉS aszerint, hogy egy fix időpont van megadva, vagy több választható:
	if (count($options) == 1) {
		$eventStartAt = $options[0];
		$eventEndAt = date('Y-m-d H:i:s', strtotime($eventStartAt) + $duration*60*60);
		$isDefinite = 1;
		$sql = "INSERT INTO esemenyek (nev, leiras, idopont_kezd, idopont_vege, alkalmak, rendszeresseg, kotelezoseg, meghirdeto, visszajelzesi_hatarido, veglegesseg) VALUES ('".$name."', '".$description."', '".$eventStartAt."', '".$eventEndAt."', '".$occasions."', '".$regularity."', '".$isMandatory."', '".$arranger."', '".$expDate."', '".$isDefinite."')";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	} else if (count($options) > 1) {
		$sql = "INSERT INTO esemenyek (nev, leiras, alkalmak, rendszeresseg, kotelezoseg, meghirdeto, visszajelzesi_hatarido, veglegesseg) VALUES ('".$name."', '".$description."', '".$occasions."', '".$regularity."', '".$isMandatory."', '".$arranger."', '".$expDate."', '".$isDefinite."')";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
		
		for ($x = 0; $x < count($options); $x++) {
			$eventStartAt = $options[$x];
			$eventEndAt = date('Y-m-d H:i:s', strtotime($eventStartAt) + $duration*60*60);
			$sql = "INSERT INTO esemeny_opciok (esemeny_nev, idopont_kezd, idopont_vege) VALUES ('".$name."', '".$eventStartAt."', '".$eventEndAt."')";
			mysqli_query($conn, $sql) or die (mysqli_error($conn));
		}
	}
	
	header('Location: addEvent.php');
}

//nem múltbeli események lekérése
$now = date('Y-m-d H:i:s');
$sql = "SELECT * FROM esemenyek WHERE (veglegesseg = 1 AND idopont_vege >= '".$now."') OR (veglegesseg = 0 AND visszajelzesi_hatarido >= '".$now."')";
$result = mysqli_query($conn, $sql);
$notPastEvents = mysqli_fetch_all($result, MYSQLI_ASSOC);

//keresett események
$searchedEvents = [];
if (isset($_POST["search"])) {
	$keyword = $_POST["searchbox"];
	$sql = "SELECT * FROM esemenyek WHERE nev LIKE '%".$keyword."%' OR leiras LIKE '%".$keyword."%' OR meghirdeto LIKE '%".$keyword."%' OR idopont_kezd LIKE '%".$keyword."%' OR visszajelzesi_hatarido LIKE '%".$keyword."%'";
	$result = mysqli_query($conn, $sql);
	$searchedEvents = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

//rendezett események
$orderedEvents = [];
if (isset($_POST["order"])) {
	//az összes eseményt kérjük le rendezve, ezen majd lehet további műveleteket végezni
	$orderType = $_POST["orderType"]; //ASC vagy DESC
	if ($_POST["orderBy"] == "name") {
		$orderBy = "nev ";
	} else if ($_POST["orderBy"] == "time") {
		$orderBy = "idopont_kezd ";
	} else if ($_POST["orderBy"] == "expiration") {
		$orderBy = "visszajelzesi_hatarido ";
	}
	$sql = "SELECT * FROM esemenyek ORDER BY ".$orderBy.$orderType;
	$result = mysqli_query($conn, $sql);
	$orderedEvents = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

//szűrt események
$filteredEvents = [];
if (isset($_POST["filter"])) {
	$thisUser = $_SESSION["felhasznalonev"];
	$now = date('Y-m-d H:i:s');
	
	$sql = "SELECT * FROM esemenyek WHERE ";
	if ($_POST["filterBy"] == "mandatory") {
		$sql .= "kotelezoseg = 1";
	} else if ($_POST["filterBy"] == "fixed") {
		$sql .= "veglegesseg = 1";
	} else if ($_POST["filterBy"] == "notFixed") {
		$sql .= "veglegesseg = 0";
	} else if ($_POST["filterBy"] == "notResponded") {
		$sql .= "nev NOT IN (SELECT esemeny FROM visszajelzesek WHERE felhasznalo = '".$thisUser."')";
	} else if ($_POST["filterBy"] == "responded") {
		$sql .= "nev IN (SELECT esemeny FROM visszajelzesek WHERE felhasznalo = '".$thisUser."')";
	} else if ($_POST["filterBy"] == "past") {
		$sql .= "(veglegesseg = 1 AND idopont_vege < '".$now."') OR (veglegesseg = 0 AND visszajelzesi_hatarido < '".$now."')";
	} else if ($_POST["filterBy"] == "own") {
		$sql .= "meghirdeto = '".$thisUser."'";
	}
	
	$result = mysqli_query($conn, $sql);
	$filteredEvents = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/addEvent.css">
	<style></style>
	<script>
		var count = 1;
		var countMax = 10;
		function addOption() {
			if (count < countMax) {
				count++;
				$('#optionsDiv').append('<div><label for="date' + count + '" class="sr-only">Dátum' + count + '</label><input type="date" class="form-control options" id="date' + count + '" name="dates[]" min="<?php echo nowToDate(); ?>" max="2030-12-31" required /><label for="time' + count + '" class="sr-only">Időpont' + count + '</label><input type="time" class="form-control options" id="time' + count + '" name="times[]" min="07:00" max="23:00" required /></div>');
			} else {
				alert('Több opciót nem tudsz hozzáadni!');
			}
		}
		function removeOption() {
			if (count > 1) {
				$('#optionsDiv').children().last().remove();
				count--;
			}
		}
		
		function checkFormData() {
			var events = <?php echo json_encode($eventList) ?>;
			var eventName = document.getElementById('eventName').value;
			var duration = document.getElementById('duration').value;
			var occasions = document.getElementById('occasions').value;
			var regularity = document.getElementById('regularity').value;
			var mandatory = document.getElementById('isMandatory').value;
			var expDate = document.getElementById('expirationDate').value + " " + document.getElementById('expirationTime').value;
			
			var dates = [];
			var times = [];
			var options = [];
			var concurrentEvent = "";
			var concurrentOption = "";
			
			$('input[name="dates[]"]').each(function() {
				dates.push($(this).val());
			});
			$('input[name="times[]"]').each(function() {
				times.push($(this).val());
			});
			for (i = 0; i < dates.length; i++) {
				options[i] = dates[i] + " " + times[i] + "|" + addTimeToDatetimeString(dates[i] + " " + times[i], duration);
			}
			
			for (var e in events) {
				if (eventName == events[e]["nev"]) { //hogy ne dobjon hibát a rendszer, ha már szerepel a megadott esemény név az adatbázisban
					alert('Már létezik ilyen nevű esemény! Adj meg egy másik nevet!');
					return false;
				}
				for (i = 0; i < options.length; i++) { //hogy ne lehessen olyan időpontot megadni, amely ütközik egy fix időpontú kötelező eseményével (vagy nem kötelezőével úgy, hogy a meghirdetendő esemény kötelező)
					if (events[e]["veglegesseg"] == 1 && (events[e]["kotelezoseg"] == 1 || mandatory == 1)) {
						if (!(events[e]["idopont_kezd"] >= options[i].split("|")[1] || events[e]["idopont_vege"] <= options[i].split("|")[0])) {
							concurrentEvent = events[e]["nev"];
							concurrentOption = options[i].split("|")[0];
						}
					}
				}
			}
			
			if (concurrentEvent != "" && concurrentOption != "") {
				alert('A(z) ' + concurrentOption + ' időpont ütközik a(z) ' + concurrentEvent + ' nevű eseménnyel. Adj meg egy másik időpontot vagy időtartamot!');
				return false;
			}
			
			//a visszajelzési határidő csak teljes dátummal, időponttal együtt elfogadható; nem lehet a jelen időnél hamarabb; nem lehet a leghamarabbi időpont opció után...
			if (expDate != " ") {
				if (expDate.length != 16) {
					alert('A megadott határidő dátum vagy időpont részét nem (jól) adtad meg!');
					return false;
				} else if (expDate <= getMinDate()) {
					alert('A megadott határidő már elmúlt!');
					return false;
				}
			}
			
			for (i = 0; i < options.length; i++) {
				if (options[i].split("|")[0] <= getMinDate()) {
					alert('A megadott időpont(ok valamelyike) már elmúlt!');
					return false;
				}
				if (expDate >= options[i].split("|")[0]) {
					alert('A visszajelzési határidőnek meg kell előznie az esemény (valamennyi lehetséges) időpontját!');
					return false;
				}
			}
			
			if ((occasions == 1 && regularity != "") || (occasions > 1 && regularity == "")) {
				alert('Ha az esemény egyszeri, ne állíts be rendszerességet, ha többszöri, akkor viszont szükséges a rendszeresség beállítása!');
				return false;
			}
			
			if (!window.confirm('Biztosan elmented így?')) {
				return false;
			}
		}
		
		function addTimeToDatetimeString(oldDatetime, duration) {
			var datetime = new Date(oldDatetime);
			datetime.setHours(datetime.getHours() + parseInt(duration));
			datetime.setMinutes(datetime.getMinutes() + (duration%1)*60);
			
			var day = datetime.getDate();
			var month = datetime.getMonth() + 1; //a január 0
			var year = datetime.getFullYear();
			var hours = datetime.getHours();
			var minutes = datetime.getMinutes();
			
			if (day < 10) { day = '0' + day; }
			if (month < 10) { month = '0' + month; }
			if (hours < 10) { hours = '0' + hours; }
			if (minutes < 10) { minutes = '0' + minutes; }
			
			newDatetime = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
			return newDatetime;
		}
		
		//event.php
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
	</script>
</head>
<body>
	<?php navbar("addEvent"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<h4>Új esemény</h4>
				<form method="post" accept-charset="utf-8" onsubmit="return checkFormData()">
					<div class="form-group">
						<label for="eventName">Megnevezés: </label>
						<input type="text" class="form-control" id="eventName" name="eventName" maxlength="80" tabindex="1" required />
					</div>
					<div class="form-group">
						<label for="eventDesc">Leírás: </label>
						<textarea class="form-control" id="eventDesc" name="eventDesc" tabindex="2"></textarea>
					</div>
					<div class="form-group">
						<label for="duration">Időtartam (óra): </label> <!-- az időtartamot nem mentjük el, csak ahhoz kell, hogy a kezdő időpontból kiszámítsa a befejezőt -->
						<input type="number" class="form-control" id="duration" name="duration" min="0.5" max="6" step="0.5" tabindex="3" required />
					</div>
					<div class="form-group">
						<label for="occasions">Alkalmak száma: </label>
						<input type="number" class="form-control" id="occasions" name="occasions" min="1" max="10" tabindex="4" required />
					</div>
					<div class="form-group">
						<label for="regularity">Rendszeresség: </label>
						<select class="form-control" id="regularity" name="regularity" tabindex="5">
							<option value="" selected>&nbsp;</option>
							<option value="hetente">hetente</option>
							<option value="kéthetente">kéthetente</option>
							<option value="három hetente">három hetente</option>
							<option value="havonta">havonta</option>
						</select>
					</div>
					<div class="form-group">
						<label for="isMandatory">Kötelezőség: </label>
						<select class="form-control" id="isMandatory" name="isMandatory" tabindex="6">
							<option value="1">kötelező</option>
							<option value="0" selected>nem kötelező</option>
						</select>
					</div>
					<div class="form-group">
						<label for="date1">Dátum: </label>
						<input type="date" class="form-control" id="date1" name="dates[]" min="<?php echo nowToDate(); ?>" max="2030-12-31" tabindex="7" required />
					</div>
					<div class="form-group">
						<label for="time1">Időpont: </label>
						<input type="time" class="form-control" id="time1" name="times[]" min="07:00" max="23:00" tabindex="8" required />
					</div>
					<div class="form-group" id="optionsDiv">
						<label>További opciók: </label>
						<a onclick="addOption()" tabindex="9"><img src="images/plus-sign.png" id="plus" height="25px" width="25px" alt="add option" /></a>
						<a onclick="removeOption()" tabindex="10"><img src="images/minus-sign.png" id="minus" height="25px" width="25px" alt="remove option" /></a>
					</div>
					<div class="form-group">
						<label for="expirationDate">Visszajelzési határidő: </label>
						<input type="date" class="form-control" id="expirationDate" name="expirationDate" min="<?php echo nowToDate(); ?>" max="2030-12-31" tabindex="11" />
						<label for="expirationTime" class="sr-only">Visszajelzési határidő (időpont): </label>
						<input type="time" class="form-control" id="expirationTime" name="expirationTime" tabindex="12" />
					</div>
					<input type="submit" class="btn btn-primary" id="save" name="save" value="Mentés" tabindex="13" />
				</form>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<h4>Kollégiumi események</h4>';
				
				echo '<form class="form-inline" method="post" accept-charset="utf-8">';
				echo '<div class="form-group">';
				echo '<label for="searchbox" class="sr-only">Keresés: </label>';
				echo '<input type="text" class="form-control" id="searchbox" name="searchbox" maxlength="80" tabindex="14" />';
				echo '<input type="submit" class="btn btn-primary" id="search" name="search" value="Keresés" tabindex="15" />';
				echo '</div>';
				
				echo '<div class="form-group">';
				echo '<label for="filterBy" class="sr-only">Szűrés: </label>';
				echo '<select class="form-control" id="filterBy" name="filterBy" tabindex="16">';
				echo '<option value="mandatory">kötelező</option>';
				echo '<option value="fixed">fix időpontú</option>';
				echo '<option value="notFixed">nem fix időpontú</option>';
				echo '<option value="notResponded" selected>visszajelzésre váró</option>';
				echo '<option value="responded">visszajelzett</option>';
				echo '<option value="past">múltbeli</option>';
				echo '<option value="own">saját</option>';
				echo '</select>';
				echo '<input type="submit" class="btn btn-primary" id="filter" name="filter" value="Szűrés" tabindex="17" />';
				echo '</div>';
				
				echo '<div class="form-group">';
				echo '<label for="orderBy" class="sr-only">Rendezés: </label>';
				echo '<select class="form-control" id="orderBy" name="orderBy" tabindex="18">';
				echo '<option value="name">név</option>';
				echo '<option value="time">időpont</option>';
				echo '<option value="expiration">visszajelzés határideje</option>';
				echo '</select>';
				echo '<label for="orderType" class="sr-only">Sorrend: </label>';
				echo '<select class="form-control" id="orderType" name="orderType" tabindex="19">';
				echo '<option value="ASC">növekvő</option>';
				echo '<option value="DESC">csökkenő</option>';
				echo '</select>';
				echo '<input type="submit" class="btn btn-primary" id="order" name="order" value="Rendezés" tabindex="20" />';
				echo '</div>';
				echo '</form>';
				echo '<hr>';
				
				$eventsToBeListed = [];
				if (isset($_POST["search"])) { //alternatív módszer: azt nézzük meg, hogy melyik tömb nem üres
					$eventsToBeListed = $searchedEvents;
				} else if (isset($_POST["order"])) {
					$eventsToBeListed = $orderedEvents;
				} else if (isset($_POST["filter"])) {
					$eventsToBeListed = $filteredEvents;
				} else {
					$eventsToBeListed = $notPastEvents;
				}
				
				if (count($eventsToBeListed) > 0) {
					echo '<table class="table table-bordered">';
					echo '<tr><th>Esemény</th><th>Meghirdető</th><th>Időpont</th><th>Visszajelzési határidő</th></tr>';
					foreach ($eventsToBeListed as $event) {
						$nameAsId = str_replace(" ", "_", $event["nev"]);
						echo '<tr';
						if (($event["veglegesseg"] == 1 && $event["idopont_vege"] < date('Y-m-d H:i:s')) || ($event["veglegesseg"] == 0 && $event["visszajelzesi_hatarido"] < date('Y-m-d H:i:s'))) {
							echo ' class="pastEvent"';
						}
						echo '><td><a href="event.php?id='.$nameAsId.'">'.$event["nev"].'</a></td>';
						echo '<td><a href="userData.php?user='.$event["meghirdeto"].'">'.$event["meghirdeto"].'</a></td><td>';
						if ($event["veglegesseg"] == 1) {
							echo date('Y-m-d H:i', strtotime($event["idopont_kezd"]));
						} else {
							echo 'még nem fix';
						}
						
						echo '</td><td>'.date('Y-m-d H:i', strtotime($event["visszajelzesi_hatarido"])).'</td></tr>';
					}
					echo '</table>';
				} else {
					if (isset($_POST["search"]) || isset($_POST["order"]) || isset($_POST["filter"])) {
						echo '<p style="color: red;">Nincs ilyen esemény!</p>';
					} else {
						echo '<p>Jelenleg nincsenek meghirdetve kollégiumi események. Azokat, amik már elmúltak, a keresés/szűrés funkciót használva tudod listázni.</p>';
					}
				}
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>