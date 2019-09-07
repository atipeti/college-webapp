<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

$userId = isset($_GET['user']) ? $_GET['user'] : NULL;

//oldal átirányítása, ha nincs GET paraméter
if (empty($userId)) {
	header("Location: addUser.php");
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

//felhasználó adatainak lekérdezése
$sql = "SELECT * FROM felhasznalok WHERE felhasznalonev = '".$userId."'";
$result = mysqli_query($conn, $sql);
$userData = mysqli_fetch_assoc($result);

//teamek nevének lekérdezése, elhelyezése tömbben
$sql = "SELECT team_nev FROM teamek";
$result = mysqli_query($conn, $sql);
$teamNameList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//jelszó módosítása
if (isset($_COOKIE['newPassword'])) {
	$sql = "UPDATE felhasznalok SET jelszo = '".$_COOKIE['newPassword']."' WHERE felhasznalonev = '".$_SESSION['felhasznalonev']."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	//https://stackoverflow.com/questions/686155/remove-a-cookie
	unset($_COOKIE['newPassword']);
	setcookie('newPassword', '', time()-3600);
	
	header('Location: userData.php?user='.$userId);
}

//felhasználó adatainak módosítása
if (isset($_COOKIE['newPwd']) && isset($_COOKIE['newEmail']) && isset($_COOKIE['newTeam']) && isset($_COOKIE['newTeamRole']) && isset($_COOKIE['newPrivilege'])) {
	$sql = "UPDATE felhasznalok SET jelszo = '".$_COOKIE['newPwd']."', email = '".$_COOKIE['newEmail']."', jogosultsag = ".$_COOKIE['newPrivilege'];
	if ($_COOKIE['newTeam'] != "" && $_COOKIE['newTeamRole'] != 0) {
		$sql .= ", team = '".$_COOKIE['newTeam']."', team_szerepkor = '".$_COOKIE['newTeamRole']."'";
	} else {
		$sql .= ", team = NULL, team_szerepkor = NULL";
	}
	$sql .= " WHERE felhasznalonev = '".$userId."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	//MEGJEGYZÉS: ha valaki szerepköre "teamvezető"-re módosul, azért nem update-eljük a korábbi teamvezetőt, mert az új koncepció szerint egy teamhez több teamvezető is tartozhat
	
	unset($_COOKIE['newPwd']);
	unset($_COOKIE['newEmail']);
	unset($_COOKIE['newTeam']);
	unset($_COOKIE['newTeamRole']);
	unset($_COOKIE['newPrivilege']);
	setcookie('newPwd', '', time()-3600);
	setcookie('newEmail', '', time()-3600);
	setcookie('newTeam', '', time()-3600);
	setcookie('newTeamRole', '', time()-3600);
	setcookie('newPrivilege', '', time()-3600);
	
	header('Location: userData.php?user='.$userId);
}

//felhasználó törlése
if (isset($_POST["deleteUser"])) {
	$sql = "DELETE FROM felhasznalok WHERE felhasznalonev = '".$userId."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header("Location: addUser.php");
	exit();
}

//kapcsolat bezárása
mysqli_close($conn);

//átirányítás, ha a felhasználó adatait tartalmazó tömb üres
if (count($userData) == 0) {
	header("Location: addUser.php");
	exit();
}
?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/userData.css">
	<style></style>
	<script>
		var oldPass = "";
		var newPass = "";

		function changePassword() {
			oldPass = $('#tdPassword').text();
			$('#changePassButton').hide();
			$('#savePassButton').show();
			$('#cancelPassButton').show();
			$('#tdPassword').text('').append($('<input type="text" class="form-control" id="newPassInput" />').val(oldPass));
		}

		function savePassword() {
			newPass = $('#newPassInput').val();
			
			if (newPass == oldPass) {
				if (window.confirm('Biztosan elmented?')) {
					cancelPassChange();
				}
			} else {
				if (newPass.length < 6) {
					alert('A jelszónak legalább 6 karakter hosszúnak kell lennie!');
					return;
				}
				
				if (window.confirm('Biztosan elmented?')) {
					document.cookie = "newPassword = " + newPass;
					location.reload();
				}
			}
		}

		function cancelPassChange() {
			$('#tdPassword').text('').append(oldPass);
			$('#changePassButton').show();
			$('#savePassButton').hide();
			$('#cancelPassButton').hide();
		}
		
		var oldPwd = "";
		var oldEmail = "";
		var oldTeam = "";
		var oldTeamRole = "";
		var oldTeamRoleAsNumber = "";
		var oldPrivilege = "";
		var oldPrivilegeAsNumber = "";

		function editUserData() {
			var teams = <?php echo json_encode($teamNameList); ?>;
			$('#editButton').hide();
			$('#deleteButton').hide();
			$('#saveButton').show();
			$('#cancelButton').show();
			
			oldPwd = $('#tdPassword').text();
			oldEmail = $('#tdEmail').text();
			oldTeam = $('#tdTeam').text();
			oldTeamRole = $('#tdTeamRole').text();
			oldPrivilege = $('#tdPrivilege').text();
			
			$('#tdPassword').text('').append($('<input type="text" id="newPwdInput" class="form-control" />').val(oldPwd));
			$('#tdEmail').text('').append($('<input type="email" id="newEmailInput" class="form-control" />').val(oldEmail));
			$('#tdTeam').text('').append($('<select class="form-control" id="newTeamSelect"><option value="">&nbsp;</option>'));
			for (var i in teams) {
				if (teams[i]["team_nev"] == oldTeam) {
					$('#newTeamSelect').append($('<option value="' + teams[i]["team_nev"] + '" selected>' + teams[i]["team_nev"] + '</option>'));
				} else {
					$('#newTeamSelect').append($('<option value="' + teams[i]["team_nev"] + '">' + teams[i]["team_nev"] + '</option>'));
				}
			}
			$('#tdTeam').append($('</select>'));
			
			var teamRoles = ["", "teamvezető", "teamtag"];
			$('#tdTeamRole').text('').append($('<select class="form-control" id="newTeamRoleSelect">'));
			for (i = 0; i < teamRoles.length; i++) {
				if (teamRoles[i] == oldTeamRole) {
					$('#newTeamRoleSelect').append($('<option value="' + i + '" selected>' + teamRoles[i] + '</option>'));
					oldTeamRoleAsNumber = i;
				} else {
					$('#newTeamRoleSelect').append($('<option value="' + i + '">' + teamRoles[i] + '</option>'));
				}
			}
			$('#tdTeamRole').append($('</select>'));
			
			var privileges = ["admin", "koordinátor", "hallgató"];
			$('#tdPrivilege').text('').append($('<select class="form-control" id="newPrivilegeSelect">'));
			for (i = 1; i <= privileges.length; i++) {
				if (privileges[i-1] == oldPrivilege) {
					$('#newPrivilegeSelect').append($('<option value="' + i + '" selected>' + privileges[i-1] + '</option>'));
					oldPrivilegeAsNumber = i;
				} else {
					$('#newPrivilegeSelect').append($('<option value="' + i + '">' + privileges[i-1] + '</option>'));
				}
			}
			$('#tdPrivilege').append($('</select>'));
		}

		function saveUserData() {
			var newPwd = $('#newPwdInput').val();
			var newEmail = $('#newEmailInput').val();
			var newTeam = $('#newTeamSelect').val();
			var newTeamRole = $('#newTeamRoleSelect').val();
			var newPrivilege = $('#newPrivilegeSelect').val();
			
			if (newPwd == oldPwd && newEmail == oldEmail && newTeam == oldTeam && newTeamRole == oldTeamRoleAsNumber && newPrivilege == oldPrivilegeAsNumber) {
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					cancelUserEdit();
				}
			} else {
				//új email ellenőrzése: https://stackoverflow.com/questions/46155/how-to-validate-an-email-address-in-javascript + https://www.w3schools.com/jsref/jsref_regexp_test.asp
				var pattern = /\S+@\S+\.\S+/;
				if (!pattern.test(newEmail)) {
					alert('Nem jó email formátumot adtál meg!');
					return;
				}
				
				if (newPwd.length < 6) {
					alert('A jelszónak legalább 6 karakter hosszúnak kell lennie!');
					return;
				}
				
				if ((newTeam == "" && newTeamRole != 0) || (newTeam != "" && newTeamRole == 0)) {
					alert('A team és a team szerepkör megadása nem kötelező, de az egyik nem adható meg a másik nélkül!');
					return false;
				}
				
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					document.cookie = "newPwd = " + newPwd;
					document.cookie = "newEmail = " + newEmail;
					document.cookie = "newTeam = " + newTeam;
					document.cookie = "newTeamRole = " + newTeamRole;
					document.cookie = "newPrivilege = " + newPrivilege;
					
					location.reload();
				}
			}
		}
		
		function confirmDelete() {
			if (window.confirm('Biztosan törlöd a felhasználót?')) {
				return true;
			} else {
				return false;
			}
		}
		
		function cancelUserEdit() {
			$('#tdPassword').text('').append(oldPwd);
			$('#tdEmail').text('').append(oldEmail);
			$('#tdTeam').text('').append(oldTeam);
			$('#tdTeamRole').text('').append(oldTeamRole);
			$('#tdPrivilege').text('').append(oldPrivilege);
			$('#editButton').show();
			$('#deleteButton').show();
			$('#saveButton').hide();
			$('#cancelButton').hide();
		}
	</script>
</head>
<body>
	<?php if ($userId == $_SESSION["felhasznalonev"]) { navbar("profile"); } else { navbar(); } ?>
	<div class="container">
		<div class="mainTitle">
			<?php
			$fullname = $userData["vezeteknev"]." ".$userData["keresztnev"];
			if ($userData["masodik_nev"] != "") {
				$fullname = $fullname." ".$userData["masodik_nev"];
			}
			if ($fullname != " ") {
				echo '<h2>'.$fullname.'</h2>';
			} else {
				echo '<h2>'.$userData["felhasznalonev"].'</h2>';
			}
			?>
		</div>
		<div class="row">
			<div class="col-sm-3">
				<?php
				if ($_SESSION["jogosultsag"] == 1) {
					echo '<h4>Műveletek</h4>';
					echo '<div class="btn" id="editButton" onclick="editUserData()">Adatok szerkesztése</div>';
					echo '<div class="btn" id="saveButton" style="display: none;" onclick="saveUserData()">Mentés</div>';
					echo '<div class="btn" id="cancelButton" style="display: none;" onclick="cancelUserEdit()">Mégse</div>';
					if ($userId != $_SESSION["felhasznalonev"]) {
						echo '<form method="post" onsubmit="return confirmDelete()">';
						echo '<input type="submit" class="btn button" id="deleteUser" name="deleteUser" value="Felhasználó törlése" />';
						echo '</form>';
					}
				}
				if ($userId == $_SESSION["felhasznalonev"]) {
					if ($_SESSION["jogosultsag"] != 1) {
						echo '<h4>Műveletek</h4>';
						echo '<div class="btn" id="changePassButton" onclick="changePassword()">Jelszó megváltoztatása</div>';
						echo '<div class="btn" id="savePassButton" style="display: none;" onclick="savePassword()">Jelszó mentése</div>';
						echo '<div class="btn" id="cancelPassButton" style="display: none;" onclick="cancelPassChange()">Mégse</div>';
					}
					
				}
				
				?>
			</div>
			<div class="col-sm-9">
				<?php
				if ($userId == $_SESSION["felhasznalonev"]) {
					echo '<h4>Adataid</h4>';
				} else {
					echo '<h4>Felhasználó adatai</h4>';
				}
				
				echo '<table class="table table-bordered">';
				$fullname = $userData["vezeteknev"]." ".$userData["keresztnev"];
				if ($userData["masodik_nev"] != "") {
					$fullname = $fullname." ".$userData["masodik_nev"];
				}
				$privilege = "hallgató";
				if ($userData["jogosultsag"] == 1) {
					$privilege = "admin";
				} else if ($userData["jogosultsag"] == 2) {
					$privilege = "koordinátor";
				}
				$teamRole = "";
				if ($userData["team_szerepkor"] == 1) {
					$teamRole = "teamvezető";
				} else if ($userData["team_szerepkor"] == 2) {
					$teamRole = "teamtag";
				}
				
				echo '<tr><td class="firstCol">Név: </td><td id="tdName">'.$fullname.'</td></tr>';
				echo '<tr><td class="firstCol">Felhasználónév: </td><td id="tdUsername">'.$userData["felhasznalonev"].'</td></tr>';
				if ($userId == $_SESSION["felhasznalonev"] || $_SESSION["jogosultsag"] == 1) {
					echo '<tr><td class="firstCol">Jelszó: </td><td id="tdPassword">'.$userData["jelszo"].'</td></tr>';
					echo '<tr><td class="firstCol">E-mail cím: </td><td id="tdEmail">'.$userData["email"].'</td></tr>';
				}
				echo '<tr><td class="firstCol">Team: </td><td id="tdTeam">'.$userData["team"].'</td></tr>';
				echo '<tr><td class="firstCol">Team szerepkör: </td><td id="tdTeamRole">'.$teamRole.'</td></tr>';
				echo '<tr><td class="firstCol">Jogosultság: </td><td id="tdPrivilege">'.$privilege.'</td></tr>';
				echo '<tr><td class="firstCol">Utolsó belépés: </td><td id="tdLastLogin">'.$userData["utolso_belepes"].'</td></tr>';
				
				echo '</table>';
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>