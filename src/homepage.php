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

//felhasználó legutóbbi belépése óta elmentett fórumbejegyzések lekérdezése
$sql = "SELECT COUNT(azonosito) AS uj_bejegyzesek, forum FROM bejegyzesek WHERE idopont > '".$_SESSION["utolso_belepes"]."' AND kuldo <> '".$_SESSION["felhasznalonev"]."' GROUP BY forum";
$result = mysqli_query($conn, $sql);
$numOfNewPosts = mysqli_fetch_all($result, MYSQLI_ASSOC);

//felhasználó feladatainak lekérdezése
$sql = "SELECT * FROM feladatok WHERE felelos = '".$_SESSION["felhasznalonev"]."'";
$result = mysqli_query($conn, $sql);
$tasksOfUser = mysqli_fetch_all($result, MYSQLI_ASSOC);

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/homepage.css">
	<style></style>
	<script></script>
</head>
<body>
	<?php navbar("homepage"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<?php
				$now = date('Y-m-d H:i:s');
				$eventsToRespond = [];
				$soonEvents = [];
				$count1 = 0;
				$count2 = 0;
				foreach ($eventList as $event) {
					if ($event["visszajelzesi_hatarido"] > $now && $event["meghirdeto"] != $_SESSION["felhasznalonev"]) { //feltétel volt a $event["veglegesseg"] == 0 is, de a fix időpontú eseményekre is kell/lehet részvételi szándékot visszajelezni
						$countdown = count($feedbackList);
						$importance = 0;
						$participation = "";
						foreach ($feedbackList as $feedback) {
							if ($feedback["esemeny"] == $event["nev"] && $feedback["felhasznalo"] == $_SESSION["felhasznalonev"]) {
								$importance = $feedback["fontossag"];
								$participation = $feedback["reszvetel"];
								break;
							}
							$countdown--;
						}
						if (count($feedbackList) == 0 || $countdown == 0 || (!$event["veglegesseg"] && $importance == "") || ($event["veglegesseg"] && $participation = "")) {
							$eventsToRespond[$count1]["nev"] = $event["nev"];
							$eventsToRespond[$count1]["id"] = str_replace(" ", "_", $event["nev"]);
							$eventsToRespond[$count1]["visszajelzes"] = date('Y-m-d H:i', strtotime($event["visszajelzesi_hatarido"]));
							$count1++;
						}
					}
					if ($event["idopont_kezd"] != "" && $event["idopont_kezd"] > $now && $event["idopont_kezd"] <= date('Y-m-d H:i:s', strtotime('+1 week', strtotime($now)))) {
						$soonEvents[$count2]["nev"] = $event["nev"];
						$soonEvents[$count2]["id"] = str_replace(" ", "_", $event["nev"]);
						$count2++;
					}
				}
				
				
				$days = ["hétfő", "kedd", "szerda", "csütörtök", "péntek", "szombat", "vasárnap"];
				$today = date('Y. m. d.');
				$whichDay = date('N');
				echo '<mark>'.$today.', '.$days[$whichDay-1].'</mark>';
				echo '<hr>';
				
				$relevantProgs = 0;
				echo '<h4>Mai programok</h4>';
				echo '<ul>';
				foreach ($progList as $prog) {
					if ($prog["felhasznalo"] == $_SESSION["felhasznalonev"] && date('Y-m-d', strtotime($prog["idopont_kezd"])) == date('Y-m-d')) {
						$from = date('H:i', strtotime($prog["idopont_kezd"]));
						$until = date('H:i', strtotime($prog["idopont_vege"]));
						echo '<li><a href="ownEvent.php?id='.$prog["azonosito"].'">'.$prog["nev"].'</a> '.$from.'-'.$until.'</li>';
						$relevantProgs++;
					}
				}
				echo '</ul>';
				if ($relevantProgs == 0) {
					echo '<p>A naptárban nem szerepel program a mai napra.</p>';
				}
				echo '<hr>';
				
				echo '<h4>Közelgő események</h4>';
				if (count($soonEvents) > 0) {
					echo '<ul>';
					foreach ($soonEvents as $event) {
						echo '<li><a href="event.php?id='.$event["id"].'">'.$event["nev"].'</a></li>';
					}
					echo '</ul>';
				} else {
					echo '<p>Nincs meghirdetett esemény egy héten belül.</p>';
				}
				
				if ($_SESSION["jogosultsag"] != 1) {
					echo '<hr>';
					echo '<h4>Feladataid</h4>';
					if (count($tasksOfUser) > 0) {
						echo '<ul>';
						foreach ($tasksOfUser as $task) {
							echo '<li>'.$task["feladat"];
							if ($task["hatarido"] != "") { echo ' [határidő: '.$task["hatarido"].']'; }
							echo '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>Jelenleg nincsenek feladataid.</p>';
					}
				}
				?>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<h4>Visszajelzésre váró események</h4>';
				
				if (count($eventsToRespond) > 0) {
					foreach ($eventsToRespond as $event) {
						echo '<div class="eventDiv"><a href="event.php?id='.$event["id"].'">'.$event["nev"].'</a> | Visszajelzési határidő: '.$event["visszajelzes"].'</div>';
					}
				} else {
					echo '<p>Jelenleg nincsenek visszajelzésre váró események.</p>';
				}
				echo '<hr>';
				
				echo '<h4>Új fórum bejegyzések legutóbbi belépésed óta</h4>';
				
				//$_SESSION["megnyitottForumok"] --> login.php/61. sor + forum.php/15. sor
				foreach ($numOfNewPosts as $np) {
					if (!in_array($np["forum"], $_SESSION["megnyitottForumok"])) {
						echo '<div class="newPostsDiv"><a href="forum.php?title='.$np["forum"].'">'.$np["forum"].'</a>: '.$np["uj_bejegyzesek"].' olvasatlan bejegyzés</div>';
					}
				}
				if (count($numOfNewPosts) == 0 || count($numOfNewPosts) == count($_SESSION["megnyitottForumok"])) {
					echo '<p>Nincsenek olvasatlan fórum bejegyzések!</p>';
				}
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>