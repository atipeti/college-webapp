<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

//https://stackoverflow.com/questions/6101956/generating-a-random-password-in-php/6101969#6101969
function randomPassword() {
	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass = array();
	$alphaLength = strlen($alphabet) - 1;
	for ($i = 0; $i < 6; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass);
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

//felhasználók lekérdezése, elhelyezése tömbben
$sql = "SELECT * FROM felhasznalok";
$result = mysqli_query($conn, $sql);
$userList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//felhasználó hozzáadása
if (isset($_POST["addUser"])) {
	$lastname = $_POST["lastname"];
	$firstname = $_POST["firstname"];
	$secondname = $_POST["secondFirstname"];
	$email = $_POST["emailAddress"];
	$team = $_POST["team"];
	$teamRole = $_POST["teamRole"];
	$privilege = $_POST["privilege"];
	$userPassword = randomPassword();
	
	//https://stackoverflow.com/questions/10152894/php-replacing-special-characters-like-%C3%A0-a-%C3%A8-e/24572118#24572118
	$replace = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o', 'ú' => 'u', 'ü' => 'u', 'ű' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ö' => 'O', 'Ő' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ű' => 'U'];
	$lastnameMod = strtolower(str_replace(array_keys($replace), $replace, $lastname));
	$firstnameMod = strtolower(str_replace(array_keys($replace), $replace, $firstname));
	$secondnameMod = strtolower(str_replace(array_keys($replace), $replace, $secondname));
	$username = $lastnameMod.".".$firstnameMod;
	
	//ha van már ilyen nevű felhasználó az adatbázisban:
	$count = 0;
	foreach ($userList as $row) {
		$nameParts = explode(".", $row["felhasznalonev"]);
		if ($row["masodik_nev"] == "" && $lastnameMod == $nameParts[0] && $firstnameMod == $nameParts[1]) {
			$count = $count + 1;
		}
		if ($row["masodik_nev"] != "" && $lastnameMod == $nameParts[0] && $firstnameMod == $nameParts[1] && $secondnameMod == $nameParts[2]) {
			$count = $count + 1;
		}
	}
	
	if ($secondname !== "") {
		$username .= ".".$secondnameMod;
	}
	if ($count > 0) {
		$username .= ".".$count;
	}
	
	if ($team != "" && $teamRole != "") {
		$sql = "INSERT INTO felhasznalok (felhasznalonev, jelszo, vezeteknev, keresztnev, masodik_nev, email, team, team_szerepkor, jogosultsag) VALUES ('".$username."', '".$userPassword."', '".$lastname."', '".$firstname."', '".$secondname."', '".$email."', '".$team."', ".$teamRole.", ".$privilege.")";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	} else {
		$sql = "INSERT INTO felhasznalok (felhasznalonev, jelszo, vezeteknev, keresztnev, masodik_nev, email, jogosultsag) VALUES ('".$username."', '".$userPassword."', '".$lastname."', '".$firstname."', '".$secondname."', '".$email."', ".$privilege.")";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	}
	
	header('Location: addUser.php');
}

//teamek nevének lekérdezése
$sql = "SELECT team_nev FROM teamek";
$result = mysqli_query($conn, $sql);
$teamNameList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//felhasználó(k) törlése
if (isset($_POST["deleteUser"])) {
	if (isset($_POST["check"])) {
		$checkbox = $_POST["check"];
		for ($i = 0; $i < count($checkbox); $i++) {
			$userToBeDeleted = $checkbox[$i];
			$sql = "DELETE FROM felhasznalok WHERE felhasznalonev = '".$userToBeDeleted."'";
			$result = mysqli_query($conn, $sql);
		}
		if ($result) {
			header('Location: addUser.php');
		}
	}
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/addUser.css">
	<style></style>
	<script>
		function checkFormData() {
			var team = document.getElementById('team').value;
			var teamRole = document.getElementById('teamRole').value;
			var privilege = document.getElementById('privilege').value;
			
			if ((team == "" && teamRole != "") || (team != "" && teamRole == "")) {
				alert('A team és a team szerepkör megadása nem kötelező, de az egyik nem adható meg a másik nélkül!');
				return false;
			}
			
			if (team != "" && teamRole != "" && privilege == 1) {
				alert('Admin jogosultságú felhasználó nem adható hozzá teamhez!');
				return false;
			}
		}
		
		function confirmDelete() {
			if (window.confirm('Biztosan törölni szeretnéd a kijelölt felhasználó(ka)t?')) {
				return true;
			} else {
				return false;
			}
		}
	</script>
</head>
<body>
	<?php navbar("addUser"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<?php if ($_SESSION["jogosultsag"] == 1) : ?>
				<h4>Új felhasználó</h4>
				<form id="addUserForm" method="post" accept-charset="utf-8" onsubmit="return checkFormData()">
					<div class="form-group">
						<label for="lastname">Vezetéknév: </label>
						<input type="text" class="form-control" id="lastname" name="lastname" maxlength="20" tabindex="1" required />
					</div>
					<div class="form-group">
						<label for="firstname">Keresztnév: </label>
						<input type="text" class="form-control" id="firstname" name="firstname" maxlength="20" tabindex="2" required />
					</div>
					<div class="form-group">
						<label for="secondFirstname">Második név: </label>
						<input type="text" class="form-control" id="secondFirstname" name="secondFirstname" maxlength="20" tabindex="3" />
					</div>
					<div class="form-group">
						<label for="emailAddress">E-mail cím: </label>
						<input type="email" class="form-control" id="emailAddress" name="emailAddress" maxlength="40" tabindex="4" />
					</div>
					<div class="form-group">
						<label for="team">Team: </label>
						<select class="form-control" id="team" name="team" tabindex="5">
							<option value="" selected>&nbsp;</option>
							<?php
							foreach ($teamNameList as $tn) {
								echo '<option value="'.$tn["team_nev"].'">'.$tn["team_nev"].'</option>';
							}
							?>
						</select>
					</div>
					<div class="form-group">
						<label for="teamRole">Team szerepkör: </label>
						<select class="form-control" id="teamRole" name="teamRole" tabindex="6">
							<option value="" selected>&nbsp;</option>
							<option value="1">teamvezető</option>
							<option value="2">teamtag</option>
						</select>
					</div>
					<div class="form-group">
						<label for="privilege">Jogosultság: </label>
						<select class="form-control" id="privilege" name="privilege" tabindex="7" required>
							<option value="" selected>&nbsp;</option>
							<option value="1">admin</option>
							<option value="2">koordinátor</option>
							<option value="3">hallgató</option>
						</select>
					</div>
					<input type="submit" class="btn btn-primary" id="addUser" name="addUser" value="Hozzáad" tabindex="8" />
				</form>
				<?php endif; ?>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<h4>Felhasználók</h4>';
				
				if (count($userList) > 0) {
					if ($_SESSION["jogosultsag"] == 1) { echo '<form method="post" onsubmit="return confirmDelete()">'; }
					echo '<table class="table table-bordered">';
					echo '<tr><th>Név</th><th>Felhasználónév</th><th>Utolsó belépés</th><th>Adatlap</th>';
					if ($_SESSION["jogosultsag"] == 1) { echo '<th>Kijelölés</th>'; }
					echo '</tr>';
					$fullname = "";
					foreach ($userList as $row) {
						if ($row["masodik_nev"] != "") {
							$fullname = $row["vezeteknev"].' '.$row["keresztnev"].' '.$row["masodik_nev"];
						} else {
							$fullname = $row["vezeteknev"].' '.$row["keresztnev"];
						}
						if ($_SESSION["felhasznalonev"] !== $row["felhasznalonev"]) {
							echo '<tr>';
							echo '<td>'.$fullname.'</td>';
							echo '<td>'.$row["felhasznalonev"].'</td>';
							echo '<td>'.$row["utolso_belepes"].'</td>';
							echo '<td><a href="userData.php?user='.$row["felhasznalonev"].'"><img src="images/datasheet.png" alt="Felhasználó adatlapja" style="height: 25px;"/></a></td>';
							if ($_SESSION["jogosultsag"] == 1) { echo '<td><input type="checkbox" name="check[]" value="'.$row["felhasznalonev"].'"/></td>'; }
							echo '</tr>';
						}
					}
					echo '</table>';
					if ($_SESSION["jogosultsag"] == 1) {
						echo '<input type="submit" class="btn button" name="deleteUser" value="Kijelöltek törlése" />';
						echo '</form>';
					}
				}
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>