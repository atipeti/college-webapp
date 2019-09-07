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

//feladatok lekérése, elhelyezése tömbben
$sql = "SELECT * FROM feladatok";
$result = mysqli_query($conn, $sql);
$taskList = mysqli_fetch_all($result, MYSQLI_ASSOC);

//státusz attribútum magyar nyelvű megfelelője
function translateStatus($statusFromDb) {
	switch ($statusFromDb) {
		case "todo":
			$taskStatus = "elvégzendő"; break;
		case "doing":
			$taskStatus = "folyamatban"; break;
		case "done":
			$taskStatus = "kész"; break;
		default:
			$taskStatus = "";
	}
	
	return $taskStatus;
}

//feladat neve és státusza, ha a feladat szerepel az adatbázisban
$taskName = "";
$taskStatus = "";
if (isset($_GET['task'])) {
	$taskIdAsName = str_replace("_", " ", $_GET['task']);
	foreach ($taskList as $array) {
		if (in_array($taskIdAsName, $array)) {
			$taskName = $taskIdAsName;
			$taskStatus = translateStatus($array["statusz"]);
			break;
		}
	}
}

//feladat hozzáadása
$deadline = "";
if (isset($_POST["addTask"])) {
	$name = $_POST["taskName"];
	$text = $_POST["taskDescription"];
	$assignee = $_POST["assignee"];
	$deadline = $_POST["deadline"];
	$assigner = $_SESSION["felhasznalonev"];
	
	$sql = "INSERT INTO feladatok (feladat, letrehozo, felelos, leiras, hatarido, statusz) VALUES ('".$name."', '".$assigner."', '".$assignee."', '".$text."', '".$deadline."', 'todo')";
	if ($deadline == "") {
		$sql = "INSERT INTO feladatok (feladat, letrehozo, felelos, leiras, statusz) VALUES ('".$name."', '".$assigner."', '".$assignee."', '".$text."', 'todo')";
	}	
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	header('Location: tasks.php');
}

//feladat adatainak módosítása
if (isset($_COOKIE['newTaskName']) && isset($_COOKIE['oldTaskName']) && isset($_COOKIE['newAssignee']) && isset($_COOKIE['newDescription']) && isset($_COOKIE['newExpDate']) && isset($_COOKIE['newStatus'])) {
	$sql = "UPDATE feladatok SET feladat = '".$_COOKIE['newTaskName']."', felelos = '".$_COOKIE['newAssignee']."', leiras = '".$_COOKIE['newDescription']."', hatarido = '".$_COOKIE['newExpDate']."', statusz = '".$_COOKIE['newStatus']."' WHERE feladat = '".$_COOKIE['oldTaskName']."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	unset($_COOKIE['newTaskName']);
	unset($_COOKIE['newAssignee']);
	unset($_COOKIE['newDescription']);
	unset($_COOKIE['newExpDate']);
	unset($_COOKIE['newStatus']);
	setcookie('newTaskName', '', time()-3600);
	setcookie('newAssignee', '', time()-3600);
	setcookie('newDescription', '', time()-3600);
	setcookie('newExpDate', '', time()-3600);
	setcookie('newStatus', '', time()-3600);
	
	header('Location: tasks.php');
}

//feladat törlése
if (isset($_COOKIE['taskToDelete'])) {
	$sql = "DELETE FROM feladatok WHERE feladat = '".$_COOKIE['taskToDelete']."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	unset($_COOKIE['taskToDelete']);
	setcookie('taskToDelete', '', time()-3600);
	
	header('Location: tasks.php');
}

//státusz módosítása
if (isset($_COOKIE['statusUpdate'])) {
	$sql = "UPDATE feladatok SET statusz = '".$_COOKIE['statusUpdate']."' WHERE feladat = '".$taskName."'";
	mysqli_query($conn, $sql) or die (mysqli_error($conn));
	
	unset($_COOKIE['statusUpdate']);
	setcookie('statusUpdate', '', time()-3600);
	
	$taskNameAsId = str_replace(" ", "_", $taskName);
	header('Location: tasks.php?task='.$taskNameAsId);
}

//kapcsolat bezárása
mysqli_close($conn);

?><!doctype html>
<html lang="hu">
<head>
	<?php commonPartOfHead(); ?>
	<link rel="stylesheet" type="text/css" href="css/tasks.css">
	<style></style>
	<script>
		var oldTaskName = "";
		var oldAssignee = "";
		var oldDescription = "";
		var oldExpDate = "";
		var oldStatus = "";
		var oldStatusAsFromDb = "";
		
		function checkFormData() {
			var tasks = <?php echo json_encode($taskList); ?>;
			var taskToAdd = document.getElementById('taskName').value;
			
			for (var i in tasks) {
				if (taskToAdd == tasks[i]["feladat"]) {
					alert('Már létezik ilyen feladat! Adj meg egy másik nevet!');
					return false;
				}
			}
		}
		
		function editTask(clickedId) {
			$('.cellToHide').hide();
			$('#saveButton').show();
			$('#cancelButton').show();
			
			var rowToEdit = clickedId.replace('edit', '');
			var users = <?php echo json_encode($usernameList); ?>;
			
			oldTaskName = $('#tdTaskName' + rowToEdit).text();
			oldAssignee = $('#tdAssignee' + rowToEdit).text();
			oldDescription = $('#tdDescription' + rowToEdit).text();
			oldExpDate = $('#tdExpDate' + rowToEdit).text();
			oldStatus = $('#tdStatus' + rowToEdit).text();
			
			$('#tdTaskName' + rowToEdit).text('').append($('<input type="text" id="newTaskNameInput" class="form-control" />').val(oldTaskName));
			$('#tdAssignee' + rowToEdit).text('').append($('<select class="form-control" id="newAssigneeSelect">'));
			for (i = 0; i < users.length; i++) {
				if (users[i]["jogosultsag"] != 1) {
					if (users[i]["felhasznalonev"] == oldAssignee) {
						$('#newAssigneeSelect').append($('<option value="' + users[i]["felhasznalonev"] + '" selected>' + users[i]["felhasznalonev"] + '</option>'));
					} else {
						$('#newAssigneeSelect').append($('<option value="' + users[i]["felhasznalonev"] + '">' + users[i]["felhasznalonev"] + '</option>'));
					}
				}
			}
			$('#tdAssignee').append($('</select>'));
			
			$('#tdDescription' + rowToEdit).text('').append($('<textarea id="newDescriptionTextarea" class="form-control" />').val(oldDescription));
			$('#tdExpDate' + rowToEdit).text('').append($('<input type="date" id="newExpDateInput" class="form-control" />').val(oldExpDate));
			
			var statusOptions = ["elvégzendő", "folyamatban", "kész"];
			var statusOptionValues = ["todo", "doing", "done"];
			$('#tdStatus' + rowToEdit).text('').append($('<select class="form-control" id="newStatusSelect">'));
			for (i = 0; i < statusOptions.length; i++) {
				if (statusOptions[i] == oldStatus) {
					$('#newStatusSelect').append($('<option value="' + statusOptionValues[i] + '" selected>' + statusOptions[i] + '</option>'));
					oldStatusAsFromDb = statusOptionValues[i];
				} else {
					$('#newStatusSelect').append($('<option value="' + statusOptionValues[i] + '">' + statusOptions[i] + '</option>'));
				}
			}
			$('#tdStatus').append($('</select>'));
		}
		
		function saveTask() {
			var tasks = <?php echo json_encode($taskList); ?>;
			var newTaskName = $('#newTaskNameInput').val();
			var newAssignee = $('#newAssigneeSelect').val();
			var newDescription = $('#newDescriptionTextarea').val();
			var newExpDate = $('#newExpDateInput').val();
			var newStatus = $('#newStatusSelect').val();
			
			if (newTaskName == oldTaskName && newAssignee == oldAssignee && newDescription == oldDescription && newExpDate == oldExpDate && newStatus == oldStatusAsFromDb) {
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					cancel();
				}
			} else {
				for (var i in tasks) {
					if (newTaskName != oldTaskName && newTaskName == tasks[i]["feladat"]) {
						alert('Már létezik ilyen feladat! Adj meg egy másik nevet!');
						return;
					}
				}
				
				if (!newTaskName.replace(/\s/g, '').length) {
					alert('A feladat neve nem hagyható üresen!');
					return;
				}
				
				if (newExpDate != "" && newExpDate.length != 10) {
					alert('A határidő nem megfelelő formátumú!');
					return;
				}
				
				if (window.confirm('Biztosan elmented a változtatásokat?')) {
					document.cookie = "newTaskName = " + newTaskName;
					document.cookie = "oldTaskName = " + oldTaskName;
					document.cookie = "newAssignee = " + newAssignee;
					document.cookie = "newDescription = " + newDescription;
					document.cookie = "newExpDate = " + newExpDate;
					document.cookie = "newStatus = " + newStatus;
					
					location.reload();
				}
			}
			
		}
		
		function deleteTask(clickedId) {
			var rowToDelete = clickedId.replace('del', '');
			var taskToDelete = $('#tdTaskName' + rowToDelete).text();
			
			if (window.confirm('Biztosan törlöd a(z) ' + taskToDelete + ' feladatot?')) {
				document.cookie = "taskToDelete = " + taskToDelete;
				location.reload();
			} else {
				return false;
			}
		}
		
		function cancel() {
			location.reload();
		}
		
		//drag and drop: https://www.w3schools.com/htmL/html5_draganddrop.asp
		function allowDrop(ev) {
			ev.preventDefault();
		}
		
		function drag(ev) {
			ev.dataTransfer.setData("text", ev.target.id);
		}
		
		var statusUpdate = "";
		function drop(ev) {
			ev.preventDefault();
			var data = ev.dataTransfer.getData("text");
			ev.target.appendChild(document.getElementById(data));
			statusUpdate = ev.target.id;
		}
		
		function updateStatus() {
			if (window.confirm('Biztosan elmented?')) {
				document.cookie = "statusUpdate = " + statusUpdate;
				location.reload();
			}
		}
	</script>
</head>
<body>
	<?php navbar("tasks"); ?>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<?php
				if ($_SESSION["jogosultsag"] == 1 || $_SESSION["jogosultsag"] == 2) {
					echo '<h4>Műveletek</h4>';
					echo '<div id="addNewTask">';
					echo '<h5>Feladat hozzáadása</h5>';
					echo '<form method="post" accept-charset="utf-8" onsubmit="return checkFormData()">';
					echo '<div class="form-group">';
					echo '<label for="taskName">Megnevezés: </label>';
					echo '<input type="text" class="form-control" id="taskName" name="taskName" maxlength="40" tabindex="1" required />';
					echo '</div>';
					echo '<div class="form-group">';
					echo '<label for="taskDescription">Leírás: </label>';
					echo '<textarea class="form-control" id="taskDescription" name="taskDescription" tabindex="2"></textarea>';
					echo '</div>';
					echo '<div class="form-group">';
					echo '<label for="assignee">Felelős: </label>';
					echo '<select class="form-control" id="assignee" name="assignee" tabindex="3" required>';
					echo '<option value="" selected>&nbsp;</option>';
					for ($i = 0; $i < count($usernameList); $i++) {
						if ($usernameList[$i]["jogosultsag"] != 1) {
							echo '<option value="'.$usernameList[$i]["felhasznalonev"].'">'.$usernameList[$i]["felhasznalonev"].'</option>';
						}
					}
					echo '</select>';
					echo '</div>';
					echo '<div class="form-group">';
					echo '<label for="deadline">Határidő: </label>';
					echo '<input type="date" class="form-control" id="deadline" name="deadline" min="'.nowToDate().'" max="2030-12-31" tabindex="4" />';
					echo '</div>';
					echo '<input type="submit" class="btn btn-primary" id="addTask" name="addTask" value="Hozzáad" tabindex="5" />';
					echo '</form>';
					echo '</div>';
				}
				?>
			</div>
			<div class="col-sm-9">
				<?php
				echo '<div id="tasks">';
				echo '<h4>Feladatok</h4>';
				
				if (count($taskList) > 0) {
					echo '<table class="table table-bordered" id="firstTable">';
					echo '<tr><th>Feladat</th><th>Felelős</th><th>Leírás</th><th>Határidő</th><th>Státusz</th>';
					if ($_SESSION["jogosultsag"] == 1 || $_SESSION["jogosultsag"] == 2) {
						echo '<th class="cellToHide">Szerk.</th><th class="cellToHide">Törlés</th>';
					}
					echo '</tr>';
					$taskId = 1;
					foreach ($taskList as $task) {
						$taskNameAsId = str_replace(" ", "_", $task["feladat"]);
						if ($task["felelos"] == $_SESSION["felhasznalonev"] || $task["letrehozo"] == $_SESSION["felhasznalonev"] || $_SESSION["jogosultsag"] == 1) {
							echo '<tr><td id="tdTaskName'.$taskId.'"><a href="tasks.php?task='.$taskNameAsId.'">'.$task["feladat"].'</a></td>';
						} else {
							echo '<tr><td id="tdTaskName'.$taskId.'">'.$task["feladat"].'</td>';
						}
						echo '<td id="tdAssignee'.$taskId.'"><a href="userData.php?user='.$task["felelos"].'">'.$task["felelos"].'</a></td>';
						echo '<td id="tdDescription'.$taskId.'">'.$task["leiras"].'</td>';
						echo '<td id="tdExpDate'.$taskId.'">'.$task["hatarido"].'</td><td id="tdStatus'.$taskId.'">'.translateStatus($task["statusz"]).'</td>';
						if ($_SESSION["jogosultsag"] == 1 || $_SESSION["jogosultsag"] == 2) {
							echo '<td class="cellToHide" id="edit'.$taskId.'" onclick="editTask(this.id)"><img src="images/edit-icon1.png" alt="edit" height="20" width="20" style="cursor: pointer;" /></td>';
							echo '<td class="cellToHide" id="del'.$taskId.'" onclick="deleteTask(this.id)"><img src="images/delete-icon.png" alt="delete" height="30" width="30" style="cursor: pointer;" /></td>';
						}
						echo '</tr>';
						$taskId++;
					}
					echo '</table>';
					echo '<div class="btn" id="saveButton" style="display: none;" onclick="saveTask()">Mentés</div>';
					echo '<div class="btn" id="cancelButton" style="display: none;" onclick="cancel()">Mégse</div>';
				} else {
					echo '<p>Jelenleg nincsenek feladatok!</p>';
				}
				
				echo '</div>';
				
				if ($taskName != "") {
					$divToPlace = '<div id="taskDiv" src="img_logo.gif" draggable="true" ondragstart="drag(event)">'.$taskName.'</div>';
					
					echo '<hr>';
					echo '<div id="selectedTask">';
					echo '<h5>Feladat státusza</h5>';
					
					echo '<table class="table table-bordered" id="secondTable">';
					echo '<tr><th>Elvégzendő</th><th>Folyamatban</th><th>Kész</th></tr>';
					echo '<tr><td id="todo" class="droppable" ondrop="drop(event)" ondragover="allowDrop(event)">';
					if ($taskStatus == "elvégzendő") { echo $divToPlace; }
					echo '</td>';
					echo '<td id="doing" class="droppable" ondrop="drop(event)" ondragover="allowDrop(event)">';
					if ($taskStatus == "folyamatban") { echo $divToPlace; }
					echo '</td>';
					echo '<td id="done" class="droppable" ondrop="drop(event)" ondragover="allowDrop(event)">';
					if ($taskStatus == "kész") { echo $divToPlace; }
					echo '</td></tr>';
					echo '</table>';
					
					echo '<div class="btn" id="saveButton2" onclick="updateStatus()">Mentés</div>';
					echo '</div>';
				}
				
				?>
			</div>
		</div>
	</div>
	<?php scriptSource(); ?>
</body>
</html>