<?php

	session_start();	
	
	if (!isset($_SESSION['my_username']))
		header("Location: http://localhost/snooker/register.php");

?>

<!DOCTYPE HTML>
<html lang="pl">
<head>
	<meta charset="UTF-8" />
	<title>Snoo</title>
	<meta name="description" content="Snoo" />
	<meta name="keywords" content="snoo, snooker" />
	<meta http-equiv="X-UA_Compatible" content="IE=edge,chrome=1" />
	<link rel="stylesheet" href="style.css" type="text/css" />
	<link href='https://fonts.googleapis.com/css?family=Lato:400,700&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
</head>

<body>

	<div id="container">
	
		<div id="login_bar">
			<div id="photo">
			<?php
				if (isset($_SESSION['logged_in']))
					echo 'Zalogowany<br/>' . $_SESSION['my_username'] . '<br/>fota';
				else
					echo 'Wylog';
			?>
			</div>
			<div id="peekaboo">
				<?php
					if (isset($_SESSION['logged_in']))
					{
						echo <<< EOT
						<form method="post" action="submit_logout.php">
							<div id="div3">
								<input type="submit" value="Log-out" name="log_out" id="log_out">
							</div>
						</form>
EOT;
					}
					else
					{
						echo <<< EOT
						<form method="post" action="submit_login.php">
							<div id="div1">
								<input type="text" name="username" id="username" placeholder="username">
								<input type="text" name="password" id="password" placeholder="password">
							</div>
							<div id="div2">
								<input type="submit" value="Log-in">
							</div>
						</form>
EOT;
					}
				?>
			</div>
			<div style="clear:both;"></div>
		</div>
		
		<div id="header">
			Snoo
		</div>
		
		<div id="menu">
			<a href="index.php"><div class="menu_opt">Main menu</div></a>
			<a href="match_creation.php"><div class="menu_opt">Match</div></a>
			<a href="stats.php"><div class="menu_opt">Stats</div></a>
			<a href="profile.php"><div class="menu_opt">Profile</div></a>
			<div style="clear: both;"></div>
		</div>
		
		<div id="content">
		
		<?php
		
			require_once "connect.php";
			mysqli_report(MYSQLI_REPORT_STRICT);
			
			try
			{
				$conn = new mysqli($servername, $username, $password, $dbname);
				mysqli_set_charset($conn, 'utf8mb4');
				if($conn->connect_errno != 0)
					throw new Exception(mysqli_connect_errno());
				else
				{
					$my_id = $_SESSION['my_id'];
					$result = $conn->query("SELECT date_of_birth, password FROM users WHERE id=$my_id");
					if (!$result) throw new Exception($conn->error);
					
					// Jesli jest co
					while ($row = $result->fetch_assoc())
					{
						$date_of_birth = $row['date_of_birth'];
						$password = $row['password'];
						$conn->close();
					}
				}
			}
			catch(Exception $e)
			{
				echo '<span class="error"> Server error, sorry for the inconvenience.</span>';
				echo '<br /> Developer info: '.$e;
			}
		
			echo '<br/>Profile';
			echo '<br/>Zmiana hasla';
			echo '<br/>Ustawienie zdjecia profilowego';
			echo '<br/>Dane osobowe wiek itp';
			echo '<br/>Date of birth: ' . $date_of_birth;
			echo '<br/>Password: ' . $password;
			
			?>
			
		</div>
		
		<div id="footer">
			Snoo by Daniel Zuziak
		</div>
	
	</div>

</body>
</html>