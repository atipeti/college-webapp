<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

//ha nincs bejelentkezett felhasználó, átirányítás a bejelentkező űrlapra
if (!isset($_SESSION["felhasznalonev"])) {
    header('Location: login.php');
    exit;
}

//mai dátum, amit pl. az esemény hozzáadó űrlap dátum input mezőjének adunk meg min értékként, hogy ne tudjon múltbeli eseményt létrehozni a felhasználó
function nowToDate() {
	$now = date('Y-m-d');
	return $now;
}

//ADATBÁZIS
$host = "localhost";
$user = "root";
$password = "";

//kapcsolat létrehozása
$conn = mysqli_connect($host, $user, $password) or die ("Nem sikerült kapcsolódni a szerverhez: " . mysqli_connect_error());

//karakterkészlet beállítása
mysqli_query($conn, "SET NAMES utf8 COLLATE utf8_hungarian_ci");

//adatbázis kiválasztása
mysqli_select_db($conn, "kollegium") or die ("Nem lehet csatlakozni az adatbázishoz: " . mysqli_error($conn));

//kollégiumi események
$sql = "SELECT * FROM esemenyek";
$result = mysqli_query($conn, $sql);
$eventList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//kollégiumi események időpont opciói
$sql = "SELECT * FROM esemeny_opciok";
$result = mysqli_query($conn, $sql);
$eventOptionsList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//visszajelzések
$sql = "SELECT * FROM visszajelzesek";
$result = mysqli_query($conn, $sql);
$feedbackList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//programok
$sql = "SELECT * FROM egyeni_programok";
$result = mysqli_query($conn, $sql);
$progList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//felhasználók + jogosultságuk
$sql = "SELECT felhasznalonev, jogosultsag FROM felhasznalok";
$result = mysqli_query($conn, $sql);
$usernameList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//teamek
$sql = "SELECT * FROM teamek";
$result = mysqli_query($conn, $sql);
$teamList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//kapcsolat bezárása
mysqli_close($conn);

?>
<?php function commonPartOfHead() { ?>
	<title>XY Kollégium</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<?php
}

function navbar($active = "") { ?>
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<a class="navbar-brand" href="#">XY Kollégium</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbar">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item <?php if ($active == "homepage") { echo 'active'; } ?>">
					<a class="nav-link" href="homepage.php">Kezdőlap <?php if ($active == "homepage") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
				<li class="nav-item <?php if ($active == "forums") { echo 'active'; } ?>">
					<a class="nav-link" href="forums.php">Fórum <?php if ($active == "forums") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
				<li class="nav-item <?php if ($active == "calendar") { echo 'active'; } ?>">
					<a class="nav-link" href="calendar.php">Naptár, programok <?php if ($active == "calendar") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
				<li class="nav-item <?php if ($active == "addEvent") { echo 'active'; } ?>">
					<a class="nav-link" href="addEvent.php">Kollégiumi események <?php if ($active == "addEvent") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
				<li class="nav-item <?php if ($active == "commonRooms") { echo 'active'; } ?>">
					<a class="nav-link" href="commonRooms.php">Közös helyiségek <?php if ($active == "commonRooms") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
				<li class="nav-item <?php if ($active == "teams") { echo 'active'; } ?>">
					<a class="nav-link" href="teams.php">Teamek <?php if ($active == "teams") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
				<li class="nav-item <?php if ($active == "tasks") { echo 'active'; } ?>">
					<a class="nav-link" href="tasks.php">Feladatok <?php if ($active == "tasks") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
				<li class="nav-item <?php if ($active == "addUser") { echo 'active'; } ?>">
					<a class="nav-link" href="addUser.php">Felhasználók <?php if ($active == "addUser") { echo '<span class="sr-only">(current)</span>'; } ?></a>
				</li>
			</ul>
			<ul class="navbar-nav ml-auto">
				<li class="nav-item <?php if ($active == "profile") { echo 'active'; } ?>">
					<a class="nav-link" href="userData.php?user=<?php echo $_SESSION["felhasznalonev"]; ?>">Profil</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="logout.php">Kilépés</a>
				</li>
			</ul>
		</div>
	</nav>
<?php
}

function scriptSource() {
?>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<?php
}
?>