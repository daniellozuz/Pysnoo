<?php
	// There shall only be match_creation shown, also form created

	session_start();
	
	if (isset($_SESSION['id_match']))
		header("Location: http://localhost/snooker/scoreboard.php");
	
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
			
					if (isset($_SESSION['logged_in']))
					{
						echo <<< EOT
						<form method="post" action="submit_match_creation.php">
							Pick your opponent
								<input list="players" name="player2">
								<datalist id="players">
EOT;
									// laczenie z baza i wyciaganie graczy
									require_once "connect.php";
									$conn = new mysqli($servername, $username, $password, $dbname);
									mysqli_set_charset($conn, "utf8mb4");
									
									if ($conn->connect_errno != 0)
										echo "Error: " . $conn->connect_errno;
									else
									{
										$sql = "SELECT * FROM users ORDER BY id DESC";
										$result = $conn->query($sql);
										 
										if ($result->num_rows > 0)
										{
											while ($row = $result->fetch_assoc())
											{
												if ($row["username"] != $_SESSION['my_username'])
													echo '<option value="'.$row["username"].'">';
											}
										}
									}
									// end laczenie
								echo <<< EOT
								</datalist>
							
							<br/>Pick the venue
								<input list="venues" name="venue">
								<datalist id="venues">
EOT;
									// laczenie z baza i wyciaganie klubow
									require_once "connect.php";
									$conn = new mysqli($servername, $username, $password, $dbname);
									mysqli_set_charset($conn, "utf8mb4");
									
									if ($conn->connect_errno != 0)
										echo "Error: " . $conn->connect_errno;
									else
									{
										$sql = "SELECT * FROM clubs ORDER BY id DESC";
										$result = $conn->query($sql);
										 
										if ($result->num_rows > 0)
										{
											while ($row = $result->fetch_assoc())
											{
												echo '<option value="'.$row["clubname"].'">';
											}
										}
									}
									// end laczenie
								echo <<< EOT
								</datalist>
							
							<br/>Player 1 opens.
							<br/>Choose best of
							<input type="number" name="bestof" id="bestof">
							<br/><input type="submit" value="GoToMatch" name="GoToMatch" id="GoToMatch">
						</form>
EOT;
					}
					else
					{
						echo <<< EOT
						<form method="post" action="submit_match_creation.php">
						
							Pick the players
								<input list="players" name="player1">
								<datalist id="players">
EOT;
									// laczenie z baza i wyciaganie graczy
									require_once "connect.php";
									$conn = new mysqli($servername, $username, $password, $dbname);
									mysqli_set_charset($conn, "utf8mb4");
									
									if ($conn->connect_errno != 0)
										echo "Error: " . $conn->connect_errno;
									else
									{
										$sql = "SELECT * FROM users ORDER BY id DESC";
										$result = $conn->query($sql);
										 
										if ($result->num_rows > 0)
										{
											while ($row = $result->fetch_assoc())
											{
												echo '<option value="'.$row["username"].'">';
											}
										}
									}
									// end laczenie
								
								echo <<< EOT
								</datalist>
								<input list="players" name="player2">
								<datalist id="players">
EOT;
									// laczenie z baza i wyciaganie graczy
									require_once "connect.php";
									$conn = new mysqli($servername, $username, $password, $dbname);
									mysqli_set_charset($conn, "utf8mb4");
									
									if ($conn->connect_errno != 0)
										echo "Error: " . $conn->connect_errno;
									else
									{
										$sql = "SELECT * FROM users ORDER BY id DESC";
										$result = $conn->query($sql);
										 
										if ($result->num_rows > 0)
										{
											while ($row = $result->fetch_assoc())
											{
												echo '<option value="'.$row["username"].'">';
											}
										}
									}
									// end laczenie
								
								echo <<< EOT
								</datalist>
							
							<br/>Pick the venue
								<input list="venues" name="venue">
								<datalist id="venues">
EOT;
									// laczenie z baza i wyciaganie klubow
									require_once "connect.php";
									$conn = new mysqli($servername, $username, $password, $dbname);
									mysqli_set_charset($conn, "utf8mb4");
									
									if ($conn->connect_errno != 0)
										echo "Error: " . $conn->connect_errno;
									else
									{
										$sql = "SELECT * FROM clubs ORDER BY id DESC";
										$result = $conn->query($sql);
										 
										if ($result->num_rows > 0)
										{
											while ($row = $result->fetch_assoc())
											{
												echo '<option value="'.$row["clubname"].'">';
											}
										}
									}
									// end laczenie
								echo <<< EOT
								</datalist>
							
							<br/>Player 1 opens.
							<br/>Choose best of
							<input type="number" name="bestof" id="bestof">
							<br/><input type="submit" value="GoToMatch" name="GoToMatch" id="GoToMatch">
						</form>
EOT;
					}
			?>
			
		</div>
		
		<div id="footer">
			Snoo by Daniel Zuziak
		</div>
	
	</div>

</body>
</html>