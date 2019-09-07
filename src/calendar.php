<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

if (isset($_GET["date"]) && sizeof(explode("-", $_GET["date"])) == 3) {
	$parts = explode("-", date('Y-m-j', strtotime($_GET["date"])));
} else {
	$parts = explode("-", date('Y-m-j'));
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

//program elmentése
if (isset($_POST["saveProg"])) {
	$name = $_POST["progName"];
	$duration = $_POST["progDuration"];
	$start = date('Y-m-d H:i:s', strtotime($_POST["progDate"]." ".$_POST["progStartAt"]));
	$end = date('Y-m-d H:i:s', strtotime($start) + $duration*60*60);
	$occasions = $_POST["occasions"];
	$regularity = $_POST["regularity"];
	$importance = $_POST["importance"];
	$owner = $_SESSION["felhasznalonev"];
	
	$sql = "INSERT INTO egyeni_programok (nev, felhasznalo, idopont_kezd, idopont_vege, alkalmak, rendszeresseg, fontossag) VALUES ('".$name."', '".$owner."', '".$start."', '".$end."', ".$occasions.", '".$regularity."', ".$importance.")";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	if (isset($_GET["date"])) {
		header('Location: calendar.php?date='.$_GET["date"]);
	} else {
		header('Location: calendar.php');
	}
}

//kapcsolat bezárása
mysqli_close($conn);

//NAPTÁRHOZ
function defineOccasions($regularity) { //a további alkalmak meghatározásához
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
	return $timeToAdd;
}

$allProgDates = array();
$progCounter = 0;
foreach ($progList as $prog) {
	if ($prog["felhasznalo"] == $_SESSION["felhasznalonev"]) {
		$progDate = date('Y-m-d', strtotime($prog["idopont_kezd"]));
		$timeToAdd = defineOccasions($prog["rendszeresseg"]);
		
		for ($i = 0; $i < $prog["alkalmak"]; $i++) {
			$allProgDates[$progCounter]["nev"] = $prog["nev"];
			$allProgDates[$progCounter]["azonosito"] = $prog["azonosito"];
			$allProgDates[$progCounter]["datum"] = $progDate;
			$allProgDates[$progCounter]["idopont_kezd"] = date('H:i', strtotime($prog["idopont_kezd"]));
			$allProgDates[$progCounter]["idopont_vege"] = date('H:i', strtotime($prog["idopont_vege"]));
			$progDate = date('Y-m-d', strtotime($timeToAdd, strtotime($progDate)));
			$progCounter++;
		}
	}
}

$allEventDates = array();
$eventCounter = 0;
foreach ($eventList as $event) {
	$eventDate = date('Y-m-d', strtotime($event["idopont_kezd"]));
	$timeToAdd = defineOccasions($event["rendszeresseg"]);
	
	for ($i = 0; $i < $event["alkalmak"]; $i++) {
		$allEventDates[$eventCounter]["nev"] = $event["nev"];
		$allEventDates[$eventCounter]["datum"] = $eventDate;
		$allEventDates[$eventCounter]["idopont_kezd"] = date('H:i', strtotime($event["idopont_kezd"]));
		$allEventDates[$eventCounter]["idopont_vege"] = date('H:i', strtotime($event["idopont_vege"]));
		$eventDate = date('Y-m-d', strtotime($timeToAdd, strtotime($eventDate)));
		$eventCounter++;
	}
}

//az összes olyan dátum, amikor van valamilyen program vagy esemény
$allBusyDates = [];
foreach ($allProgDates as $prog) {
	array_push($allBusyDates, $prog["datum"]);
}
foreach ($allEventDates as $event) {
	array_push($allBusyDates, $event["datum"]);
}

//NAPTÁR
//https://stackoverflow.com/questions/45894184/calendar-php-data-from-mysql/45894353#45894353
if (isset($_GET['ym'])) {
	$pieces = explode("-", substr($_GET['ym'], 0, 7));
	if (sizeof($pieces) >= 2 && ((int)$pieces[0] >= 1970 && (int)$pieces[0] <= 2030) && ((int)$pieces[1] >= 1 && (int)$pieces[1] <= 12)) {
		$ym = $_GET['ym'];
	} else {
		$ym = date('Y-m'); //ha a felhasználó rossz formátumot ír be a böngésző címsorába
	}
} else {
	$ym = date('Y-m');
}

$timestamp = strtotime($ym.'-01');
$today = date('Y-m-d', time());
$year = date('Y', $timestamp);
$month = date('n', $timestamp);

//előző és következő hónap
$prev = date('Y-m', strtotime('-1 month', $timestamp));
$next = date('Y-m', strtotime('+1 month', $timestamp));

$numOfDays = date('t', $timestamp); //napok száma az adott hónapban
$dayOfWeek = date('w', $timestamp); //adott hónap elseje milyen napra esik
if ($dayOfWeek == 0) { $dayOfWeek = 7; }

$weeks = array();
$week = "";

for ($x = 1; $x < $dayOfWeek; $x++) {
	$week .= '<td></td>';
}

$date = "";
$queryString = $_SERVER["QUERY_STRING"];
$urlParts = explode("&", $queryString);
$queryStringYm = "";
$queryStringDate = "";
if ($_SERVER["QUERY_STRING"] != "" && sizeof($urlParts) == 1) {
	if (strpos($urlParts[0], "ym") !== false) {
		$queryStringYm = $urlParts[0]."&";
		$queryStringDate = "";
	} else if (strpos($urlParts[0], "date") !== false) {
		$queryStringYm = "";
		$queryStringDate = "&".$urlParts[0];
	}
} else if (sizeof($urlParts) == 2) {
	$queryStringYm = $urlParts[0]."&";
	$queryStringDate = "&".$urlParts[1];
}

for ($day = 1; $day <= $numOfDays; $day++, $dayOfWeek++) {
	if ($dayOfWeek > 7) {
		$dayOfWeek = 1;
	}
	if ($day < 10) {
		$date = $ym.'-0'.$day;
	} else {
		$date = $ym.'-'.$day;
	}
	
	$week .= '<td class="';
	if ($date == $today) {
		$week .= 'today';
	}
	if (isset($_GET["date"]) && $date == $_GET["date"]) {
		$week .= ' selected';
	}
	if (in_array($date, $allBusyDates)) {
		$week .= ' busy';
	}
	$week .= '">';
	
	$week .= '<a href="calendar.php?'.$queryStringYm.'date='.$date.'">'.$day.'</a></td>';
	
	//hét vége vagy hónap vége
	if ($dayOfWeek % 7 == 0 || $day == $numOfDays) {
		if ($day == $numOfDays) {
			$week .= str_repeat('<td></td>', (7 - $dayOfWeek));
		}
		$weeks[] = '<tr>'.$week.'</tr>';
		$week = ''; //új hét
	}
}

$months = array("január", "február", "március", "április", "május", "június", "július", "augusztus", "szeptember", "október", "november", "december");

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/calendar.css">
	<style></style>
	<script>
		function checkFormData() {
			var startTime = document.getElementById('progStartAt').value;
			var endTime = document.getElementById('progEndAt').value;
			var occasions = document.getElementById('occasions').value;
			var regularity = document.getElementById('regularity').value;
			
			if (endTime < startTime) {
				alert('A befejezés nem előzheti meg a kezdést!');
				return false;
			}
			
			if ((occasions == 1 && regularity != "") || (occasions > 1 && regularity == "")) {
				alert('Ha a program egyszeri, ne adj meg rendszerességet, ha többszöri, akkor viszont szükséges a rendszeresség megadása!');
				return false;
			}
			
			return true;
		}
	</script>
</head>
<body>
	<?php navbar("calendar"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<h4>Új program</h4>
				<form id="form" method="post" accept-charset="utf-8" onsubmit="return checkFormData()">
					<div class="form-group">
						<label for="progName">Megnevezés: </label>
						<input type="text" class="form-control" id="progName" name="progName" maxlength="80" tabindex="1" required />
					</div>
					<div class="form-group">
						<label for="progDate">Dátum: </label>
						<input type="date" class="form-control" id="progDate" name="progDate" min="2018-01-01" max="2030-12-31" tabindex="2" required />
					</div>
					<div class="form-group">
						<label for="progStartAt">Időpont: </label>
						<input type="time" class="form-control" id="progStartAt" name="progStartAt" tabindex="3" required />
					</div>
					<div class="form-group">
						<label for="progDuration">Időtartam (óra): </label> <!-- nem mentjük el, csak ahhoz kell, hogy a kezdő időpontból kiszámítsa a befejezőt -->
						<input type="number" class="form-control" id="progDuration" name="progDuration" min="0" max="23" step="0.5" tabindex="4" required />
					</div>
					<div class="form-group">
						<label for="occasions">Alkalmak száma: </label>
						<input type="number" class="form-control" id="occasions" name="occasions" min="1" max="50" tabindex="5" required />
					</div>
					<div class="form-group">
						<label for="regularity">Rendszeresség: </label>
						<select class="form-control" id="regularity" name="regularity" tabindex="6">
							<option value="" selected>&nbsp;</option>
							<option value="hetente">hetente</option>
							<option value="kéthetente">kéthetente</option>
							<option value="három hetente">három hetente</option>
							<option value="havonta">havonta</option>
						</select>
					</div>
					<div class="form-group">
						<label for="importance">Fontosság: </label>
						<select class="form-control" id="importance" name="importance" tabindex="7" required>
							<option value="" selected>&nbsp;</option>
							<option value="0">nem fontos</option>
							<option value="1">kevésbé fontos</option>
							<option value="2">fontos</option>
							<option value="3">nagyon fontos/kötelező</option>
						</select>
					</div>
					<input type="submit" class="btn btn-primary" id="saveProg" name="saveProg" value="Mentés" tabindex="8" />
				</form>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<div id="calendar">';
				echo '<h4 style="float: left;">Naptár</h4>';
				echo '<span style="float: right;">'.date('Y. m. d.').'</span>';
				echo '<div style="clear: both;"></div>';
				echo '<h5 style="text-align: center;"><a href="calendar.php?ym='.$prev.$queryStringDate.'">&lt;</a> '.$year.'. '.$months[$month-1].' <a href="calendar.php?ym='.$next.$queryStringDate.'">&gt;</a></h5>';
				echo '<table class="table table-bordered">';
				echo '<tr><th>H</th><th>K</th><th>Sze</th><th>Cs</th><th>P</th><th>Szo</th><th>V</th></tr>';
				
				foreach ($weeks as $week) {
					echo $week;
				}
						
				echo '</table>';
				echo '</div>';
				
				echo '<hr>';
				echo '<div id="dailySchedule">';
				echo '<h5>'.$parts[0].'. '.$months[$parts[1]-1].' '.$parts[2].'.</h5>';
				
				foreach ($allProgDates as $prog) {
					if (((isset($_GET["date"]) && $prog["datum"] == $_GET["date"]) || (!isset($_GET["date"]) && $prog["datum"] == date('Y-m-d')))) {
						echo '<div class="progDiv">'.$prog["idopont_kezd"].'-'.$prog["idopont_vege"].': <a href="ownEvent.php?id='.$prog["azonosito"].'">'.$prog["nev"].'</a></div>';
					}
				}
				
				foreach ($allEventDates as $event) {
					$nameAsId = str_replace(" ", "_", $event["nev"]);
					if ((isset($_GET["date"]) && $event["datum"] == $_GET["date"]) || (!isset($_GET["date"]) && $event["datum"] == date('Y-m-d'))) {
						echo '<div class="eventDiv">'.$event["idopont_kezd"].'-'.$event["idopont_vege"].': <a href="event.php?id='.$nameAsId.'">'.$event["nev"].'</a> <span>(kollégiumi)</span></div>';
					}
				}
				
				echo '</div>';
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>