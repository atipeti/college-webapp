<?php
header('Content-Type: text/html; charset=utf-8');
include 'common.php';

//team neve és leírása, ha a team szerepel az adatbázisban
$teamName = "";
$description = "";
if (isset($_GET['team'])) {
	$teamIdAsName = str_replace("_", " ", $_GET['team']);
	foreach ($teamList as $array) {
		if (in_array($teamIdAsName, $array)) {
			$teamName = $teamIdAsName;
			$description = $array["leiras"];
			break;
		}
	}
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

//teamvezetők lekérdezése, elhelyezése tömbben
$sql = "SELECT felhasznalonev, team FROM felhasznalok WHERE team_szerepkor = 1";
$result = mysqli_query($conn, $sql);
$allTeamLeaders = mysqli_fetch_all($result, MYSQLI_ASSOC);

if ($teamName != "") {
	$sql = "SELECT felhasznalonev FROM felhasznalok WHERE team = '".$teamName."' AND team_szerepkor = 1";
	$result = mysqli_query($conn, $sql);
	$teamLeaders = mysqli_fetch_all($result, MYSQLI_ASSOC);
	
	$sql = "SELECT felhasznalonev FROM felhasznalok WHERE team = '".$teamName."' AND team_szerepkor = 2";
	$result = mysqli_query($conn, $sql);
	$teamMembers = mysqli_fetch_all($result, MYSQLI_ASSOC);
	
	//azon (nem admin jogosultságú) felhasználók, akik nem tagjai a teamnek
	$sql = "SELECT felhasznalonev FROM felhasznalok WHERE jogosultsag <> 1 AND (team IS NULL OR team <> '".$teamName."')";
	$result = mysqli_query($conn, $sql);
	$potentialTeamMembers = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

//team hozzáadása
if (isset($_POST["addTeam"])) {
	$name = $_POST["teamName"];
	$text = $_POST["teamDescription"];
	$teamLeaders = isset($_POST["teamLeaders"]) ? $_POST["teamLeaders"] : [];
	
	$sql = "INSERT INTO teamek (team_nev, leiras) VALUES ('".$name."', '".$text."')";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	for ($i = 0; $i < count($teamLeaders); $i++) {
		$sql = "UPDATE felhasznalok SET team = '".$name."', team_szerepkor = 1 WHERE felhasznalonev = '".$teamLeaders[$i]."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	}
	
	header('Location: teams.php');
}

//felhasználók hozzáadása teamhez
if (isset($_POST["addToTeam"]) && isset($_POST["usersToAdd"])) {
	$usersToAdd = $_POST["usersToAdd"];
	$addUsersAs = $_POST["addUsersAs"];
	
	for ($x = 0; $x < count($usersToAdd); $x++) {
		$sql = "UPDATE felhasznalok SET team = '".$teamName."', team_szerepkor = ".$addUsersAs." WHERE felhasznalonev = '".$usersToAdd[$x]."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	}
	
	header('Location: teams.php?team='.$teamName);
}

//felhasználók törlése teamből
if (isset($_POST["deleteFromTeam"]) && isset($_POST["usersToDelete"])) {
	$usersToDelete = $_POST["usersToDelete"];
	for ($x = 0; $x < count($usersToDelete); $x++) {
		$sql = "UPDATE felhasznalok SET team = NULL, team_szerepkor = NULL WHERE felhasznalonev = '".$usersToDelete[$x]."'";
		mysqli_query($conn, $sql) or die (mysqli_error($conn));
	}
	
	header('Location: teams.php?team='.$teamName);
}

//team adatainak módosítása
if (isset($_COOKIE['newTeamName']) && isset($_COOKIE['newDescription'])) {
	$sql = "UPDATE teamek SET team_nev = '".$_COOKIE['newTeamName']."', leiras = '".$_COOKIE['newDescription']."' WHERE team_nev = '".$teamName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	$teamName = $_COOKIE['newTeamName'];
	
	unset($_COOKIE['newTeamName']);
	unset($_COOKIE['newDescription']);
	setcookie('newTeamName', '', time()-3600);
	setcookie('newDescription', '', time()-3600);
	
	header('Location: teams.php?team='.$teamName);
}

//team törlése
if (isset($_POST["deleteTeam"])) {
	$sql = "UPDATE felhasznalok SET team_szerepkor = NULL WHERE team = '".$teamName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	$sql = "DELETE FROM teamek WHERE team_nev = '".$teamName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: teams.php');
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/teams.css">
	<style></style>
	<script>
		var oldTeamName = "";
		var oldDescription = "";
		var counterAdd = 0;
		var counterDel = 0;
		
		function checkFormData() {
			var teamNames = <?php echo json_encode($teamList); ?>;
			var teamToAdd = document.getElementById('teamName').value;
			
			for (var i in teamNames) {
				if (teamToAdd == teamNames[i]["team_nev"]) {
					alert('Már létezik ilyen nevű team! Adj meg egy másik nevet!');
					return false;
				}
			}
		}
		
		function addNewMember() {
			if (counterAdd % 2 == 0) {
				$('#addNewMemberForm').show();
			} else {
				$('#addNewMemberForm').hide();
			}
			counterAdd++;
		}
		
		function deleteMember() {
			if (counterDel % 2 == 0) {
				$('#deleteMemberForm').show();
			} else {
				$('#deleteMemberForm').hide();
			}
			counterDel++;
		}
		
		function editTeamData() {
			$('#editButton').hide();
			$('#addNewMemberButton').hide();
			$('#deleteMemberButton').hide();
			$('#deleteTeam').hide();
			$('#saveButton').show();
			$('#cancelButton').show();
			
			oldTeamName = $('#tdTeamName').text();
			oldDescription = $('#tdDescription').text();
			
			$('#tdTeamName').text('').append($('<input type="text" id="newTeamNameInput" class="form-control" />').val(oldTeamName));
			$('#tdDescription').text('').append($('<textarea id="newDescriptionTextarea" class="form-control" />').val(oldDescription));
		}
		
		function saveTeamData() {
			var teamNames = <?php echo json_encode($teamList); ?>;
			var newTeamName = $('#newTeamNameInput').val().trim();
			var newDescription = $('#newDescriptionTextarea').val().trim();
			
			if (newTeamName == oldTeamName && newDescription == oldDescription) { //ha egyiket se változtatta meg, és így ment, ne töltsön újra az oldal, és ne update-eljen a db-ben
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					cancelTeamEdit();
				}
			} else {
				for (var i in teamNames) {
					if (newTeamName != oldTeamName && newTeamName == teamNames[i]["team_nev"]) {
						alert('Már létezik ilyen nevű team! Adj meg egy másik nevet!');
						return;
					}
				}
				if (!newTeamName.replace(/\s/g, '').length) {
					alert('A teamnév formátuma nem megfelelő!');
					return;
				}
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					document.cookie = "newTeamName = " + newTeamName;
					document.cookie = "newDescription = " + newDescription;
					
					location.reload();
				}
			}
		}
		
		function cancelTeamEdit() {
			$('#tdTeamName').text('').append(oldTeamName);
			$('#tdDescription').text('').append(oldDescription);
			
			$('#editButton').show();
			$('#addNewMemberButton').show();
			$('#deleteMemberButton').show();
			$('#deleteTeam').show();
			$('#saveButton').hide();
			$('#cancelButton').hide();
		}
		
		function confirmDelete() {
			if (window.confirm('Biztosan törölni szeretnéd a teamet?')) {
				return true;
			} else {
				return false;
			}
		}
	</script>
</head>
<body>
	<?php navbar("teams"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<?php
				if ($_SESSION["jogosultsag"] == 1 || $_SESSION["jogosultsag"] == 2) {
					echo '<h4>Műveletek</h4>';
					if ($teamName == "") {
						echo '<div id="addNewTeam">';
						echo '<h5>Team hozzáadása</h5>';
						echo '<form method="post" accept-charset="utf-8" onsubmit="return checkFormData()">';
						echo '<div class="form-group">';
						echo '<label for="teamName">Név: </label>';
						echo '<input type="text" class="form-control" id="teamName" name="teamName" maxlength="40" tabindex="1" required />';
						echo '</div>';
						echo '<div class="form-group">';
						echo '<label for="teamDescription">Leírás: </label>';
						echo '<textarea class="form-control" id="teamDescription" name="teamDescription" tabindex="2"></textarea>';
						echo '</div>';
						echo '<div class="form-group">';
						echo '<label for="teamLeader">Teamvezető(k): </label>';
						echo '<select class="form-control" id="teamLeader" name="teamLeaders[]" multiple tabindex="3">';
						for ($i = 0; $i < count($usernameList); $i++) {
							if ($usernameList[$i]["jogosultsag"] != 1) {
								echo '<option value="'.$usernameList[$i]["felhasznalonev"].'">'.$usernameList[$i]["felhasznalonev"].'</option>';
							}
						}
						echo '</select>';
						echo '</div>';
						echo '<input type="submit" class="btn btn-primary" id="addTeam" name="addTeam" value="Hozzáad" tabindex="4" />';
						echo '</form>';
						echo '</div>';
					} else {
						echo '<div id="actions">';
						echo '<div class="btn" id="editButton" onclick="editTeamData()" tabindex="1">Adatok szerkesztése</div>';
						echo '<div class="btn" id="addNewMemberButton" onclick="addNewMember()" tabindex="2">Tagok hozzáadása</div>';
						echo '<div class="btn" id="deleteMemberButton" onclick="deleteMember()" tabindex="3">Tagok törlése</div>';
						
						echo '<form method="post" onsubmit="return confirmDelete()">';
						echo '<input type="submit" class="btn button" id="deleteTeam" name="deleteTeam" value="Team törlése" />';
						echo '</form>';
						
						echo '<div class="btn" id="saveButton" style="display: none;" onclick="saveTeamData()">Mentés</div>';
						echo '<div class="btn" id="cancelButton" style="display: none;" onclick="cancelTeamEdit()">Mégse</div>';
						echo '</div>';
						
						echo '<div id="addNewMemberForm" style="display: none;">';
						echo '<hr>';
						
						echo '<form method="post" accept-charset="utf-8">';
						echo '<div class="form-group">';
						echo '<label for="usersToAdd">Tagok hozzáadása: </label>';
						echo '<select class="form-control" id="usersToAdd" name="usersToAdd[]" multiple tabindex="5">';
						foreach ($potentialTeamMembers as $ptm) {
							echo '<option value="'.$ptm["felhasznalonev"].'">'.$ptm["felhasznalonev"].'</option>';
						}
						echo '</select>';
						echo '</div>';
						echo '<div class="form-group">';
						echo '<label for="addUsersAs">mint: </label>';
						echo '<select class="form-control" id="addUsersAs" name="addUsersAs" tabindex="6" required>';
						echo '<option value="" selected>&nbsp;</option>';
						echo '<option value="1">teamvezető</option>';
						echo '<option value="2">teamtag</option>';
						echo '</select>';
						echo '</div>';
						echo '<input type="submit" class="btn btn-primary" id="addToTeam" name="addToTeam" value="Hozzáad" tabindex="7" />';
						echo '</form>';
						echo '</div>';
						
						echo '<div id="deleteMemberForm" style="display: none;">';
						echo '<hr>';
						
						if (count($teamMembers)+count($teamLeaders) > 0) {
							echo '<form method="post" accept-charset="utf-8">';
							echo '<div class="form-group">';
							echo '<label for="usersToDelete">Tagok törlése: </label>';
							echo '<select class="form-control" id="usersToDelete" name="usersToDelete[]" multiple tabindex="8">';
							foreach ($teamMembers as $tm) {
								echo '<option value="'.$tm["felhasznalonev"].'">'.$tm["felhasznalonev"].'</option>';
							}
							foreach ($teamLeaders as $tl) {
								echo '<option value="'.$tl["felhasznalonev"].'">'.$tl["felhasznalonev"].'</option>';
							}
							echo '</select>';
							echo '</div>';
							echo '<input type="submit" class="btn btn-primary" id="deleteFromTeam" name="deleteFromTeam" value="Törlés" tabindex="9" />';
							echo '</form>';
						} else {
							echo '<p>Nincsenek tagok a teamben!</p>';
						}
						echo '</div>';
					}
				}
				?>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<div id="teams">';
				echo '<h4>Teamek</h4>';
				if (count($teamList) > 0) {
					echo '<table class="table table-bordered" id="tableOfAllTeams">';
					echo '<tr><th>Team</th><th>Leírás</th><th>Teamvezető(k)</th></tr>';
					foreach ($teamList as $team) {
					
						$teamNameAsId = str_replace(" ", "_", $team["team_nev"]);
						echo '<tr><td><a href="teams.php?team='.$teamNameAsId.'">'.$team["team_nev"].'</a></td><td>'.$team["leiras"].'</td><td>';
						$countTeamLeader = 1;
						foreach ($allTeamLeaders as $tl) {
							if ($tl["team"] == $team["team_nev"]) {
								if ($countTeamLeader > 1) { echo ', '; }
								echo '<a href="userData.php?user='.$tl["felhasznalonev"].'">'.$tl["felhasznalonev"].'</a>';
								$countTeamLeader++;
							}
						}
						echo '</td></tr>';
					}
					echo '</table>';
				} else {
					echo '<p>Jelenleg nincsenek teamek!</p>';
				}
				echo '</div>';
				
				
				if ($teamName != "") {
					echo '<hr>';
					echo '<div id="selectedTeam">';
					echo '<h5>'.ucfirst($teamName).'</h5>';
					
					echo '<table class="table table-bordered" id="tableOfSelectedTeam">';
					echo '<tr><td class="firstCol">Teamnév: </td><td id="tdTeamName">'.$teamName.'</td></tr>';
					echo '<tr><td class="firstCol">Leírás: </td><td id="tdDescription">'.$description.'</td></tr>';
					echo '<tr><td class="firstCol">Team vezető(k): </td><td id="tdTeamLeader">';
					for ($i = 0; $i < count($teamLeaders); $i++) {
						echo '<a href="userData.php?user='.$teamLeaders[$i]["felhasznalonev"].'">'.$teamLeaders[$i]["felhasznalonev"].'</a>';
						if ($i < count($teamLeaders)-1) {
							echo ', ';
						}
					}
					echo '</td></tr>';
					
					echo '<tr><td class="firstCol">Team tagok: </td><td id="tdTeamMembers">';
					for ($i = 0; $i < count($teamMembers); $i++) {
						echo '<a href="userData.php?user='.$teamMembers[$i]["felhasznalonev"].'">'.$teamMembers[$i]["felhasznalonev"].'</a>';
						if ($i < count($teamMembers)-1) {
							echo ', ';
						}
					}
					echo '</td></tr>';
					echo '</table>';
					
					echo '</div>';
				}
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>