<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

//ha be van jelentkezve a felhasználó, átirányítás a kezdőlapra
if (isset($_SESSION["felhasznalonev"])) {
    header('Location: homepage.php');
    exit;
}

$host = "localhost";
$user = "root";
$password = "";

//kapcsolat létrehozása
$conn = mysqli_connect($host, $user, $password) or die ("Nem sikerült kapcsolódni a szerverhez: " . mysqli_connect_error());

//karakterkészlet beállítása
mysqli_query($conn, "SET NAMES utf8 COLLATE utf8_hungarian_ci");

//adatbázis létrehozása, https://dev.mysql.com/doc/refman/8.0/en/charset-database.html
$sql = "CREATE DATABASE IF NOT EXISTS kollegium CHARACTER SET utf8 COLLATE utf8_hungarian_ci";
mysqli_query($conn, $sql) or die ("Nem sikerült létrehozni az adatbázist: " . mysqli_error($conn));

//adatbázis kiválasztása
mysqli_select_db($conn, "kollegium") or die ("Nem lehet csatlakozni az adatbázishoz: " . mysqli_error($conn));

//táblák létrehozása
$fileContent = file_get_contents("kollegium.sql");
$sql = utf8_encode($fileContent);
mysqli_multi_query($conn, $sql) or die ("Hiba a táblák létrehozása közben: " . mysqli_error($conn));

//kapcsolat bezárása
mysqli_close($conn);

//újracsatlakozás (ha nem csatlakozunk újra, nem hajtja végre a SELECT-et jól)
$connect = mysqli_connect($host, $user, $password) or die ("Nem sikerült kapcsolódni a szerverhez: " . mysqli_connect_error());
mysqli_query($connect, "SET NAMES utf8 COLLATE utf8_hungarian_ci");

mysqli_select_db($connect, "kollegium") or die ("Nem lehet csatlakozni az adatbázishoz: " . mysqli_error($connect));

$message = "";
if (isset($_POST["login"])) {
	$username = $_POST["username"];
	$userPassword = $_POST["password"];
	
	$sql = "SELECT * FROM felhasznalok WHERE felhasznalonev='".$username."' AND jelszo='".$userPassword."'";
	
	$result = mysqli_query($connect, $sql) or die ("Hiba: " . mysqli_error($connect));
	
	//ha volt felhasználónév és jelszó egyezés, akkor 1 sornak kell lennie
	if (mysqli_num_rows($result) == 1) {
		$row = mysqli_fetch_assoc($result);
		$_SESSION["felhasznalonev"] = $row['felhasznalonev'];
		$_SESSION["jelszo"] = $row["jelszo"];
		$_SESSION["vezeteknev"] = $row["vezeteknev"];
		$_SESSION["keresztnev"] = $row["keresztnev"];
		$_SESSION["masodik_nev"] = $row["masodik_nev"];
		$_SESSION["jogosultsag"] = $row["jogosultsag"];
		$_SESSION["utolso_belepes"] = $row["utolso_belepes"];
		$_SESSION["megnyitottForumok"] = [];
		
		$timestamp = date('Y-m-d H:i:s');
		$sql = "UPDATE felhasznalok SET utolso_belepes = '".$timestamp."' WHERE felhasznalonev = '".$row['felhasznalonev']."'";
		mysqli_query($connect, $sql) or die (mysqli_error($connect));
		
		header('Location: homepage.php');
		exit();
	}
	else {
		$message = "Hibás felhasználónév vagy jelszó!";
	}	
}

mysqli_close($connect);

?><!doctype html>
<html lang="hu">
<head>
	<title>XY Kollégium</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	<link rel="stylesheet" type="text/css" href="css/login.css">
	<style></style>
	<script></script>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<a class="navbar-brand" href="#">XY Kollégium</a>
	</nav>
	<div class="container text-center">
		<form id="loginForm" action="#" method="post" accept-charset="utf-8">
			<h2 class="">Belépés</h2>
			<label for="username" class="sr-only">Felhasználónév</label>
			<input type="text" class="form-control" id="username" name="username" placeholder="Felhasználónév" maxlength="62" autofocus tabindex="1" required />
			<label for="password" class="sr-only">Jelszó</label>
			<input type="password" class="form-control" id="password" name="password" placeholder="Jelszó" maxlength="15" tabindex="2" required />
			<input type="submit" class="btn btn-default btn-block" id="login" name="login" value="Belépés" tabindex="3" />
		</form>
		<?php
		if (!isset($_SESSION["felhasznalonev"])) {
			echo '<p>'.$message.'</p>';
			$message = "";
		}
		?>
	</div>
</body>
</html>