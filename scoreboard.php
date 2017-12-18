<?php
	// There shall only be a scoreboard shown, also forms created (buttons)
	
	session_start();
	
	if (!isset($_SESSION['id_match']))
		header("Location: http://localhost/snooker/match_creation.php");
	
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
	
	<script type="text/javascript">
	
	function timer()
	{
		var zawartosc = document.getElementById("tajmer").innerHTML;
		var czy_jest = zawartosc.indexOf("Shot");
		if (czy_jest != -1)
		{
			var shot_time = Number(zawartosc.match(/\d+/g)[0]) + 1;
			var frame_time = Number(zawartosc.match(/\d+/g)[1]) + 1;
			var zawartosc = "Shot time: " + shot_time + "<br>Frame time: " + frame_time;
			document.getElementById("tajmer").innerHTML = zawartosc;
		}
		setTimeout("timer()",1000);
	}
	
	</script>
	
</head>

<body onload="timer();">

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
					
					/// Zmienne pokazywane na wyświetlaczu.
					$points1 = 0;
					$points2 = 0;
					$break = 0;
					$frames1 = 0;
					$frames2 = 0;
					$p1_active = true;
					$p2_active = false;
					
					/// Zmienne odpowiadające za pokazywanie buttonów
					$show_normal = false;
					$show_miss = false;
					$show_begin = false;
					$show_resume = false;
					$show_start = false;
					
					/// Wczytanie logów meczu
					$id_match = $_SESSION['id_match'];
					require_once "connect.php";
					$conn = new mysqli($servername, $username, $password, $dbname);
					mysqli_set_charset($conn, "utf8mb4");
					$sqlx = "SELECT * FROM matches WHERE id='$id_match'";
					$resultx = $conn->query($sqlx);
					while ($row = $resultx->fetch_assoc())
					{
						$logs = $row['logs'];
						$bestof = $row['bestof'];
					}

					while ($logs != '') /// Pętla interpretująca logi. Każda iteracja bierze początkowy log, interpretuje go, po czym usuwa z początku zmiennej $logs.
					{
						if (substr_count($logs, 'Log') == 1) /// Jeśli został tylko jeden log, cała zmienna $logs jest logiem.
							$dlugosc = strlen($logs);
						else
							$dlugosc = strpos($logs, 'Log', 10); /// Pozycja na której znajduje się drugi log.
						$log = substr($logs, 0, $dlugosc); /// Tutaj przechowuję interpretowany log. [Weź z $logs od 0 do 0+długość]
						
						if (substr($log, -5) == 'begin')
						{
							/// Inicjalizacja. Begin pojawia się zawsze, tylko raz. Nie można go usunąć.
							/// Zmienne od punktów
							$p1_active = true;
							$p2_active = false;
							$points1 = 0;
							$points2 = 0;
							$frames1 = 0;
							$frames2 = 0;
							$break = 0;
							
							/// Zmienne odpowiadające za czas
							$log_time = 0;
							$shot_time = 0;
							$frame_time = 0;
							$shot_time_spent_paused = 0;
							$frame_time_spent_paused = 0;
							$frame_start_time = 0;
							
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_normal = false;
							$show_miss = false;
							$show_begin = true;
							$show_resume = false;
							$show_start = false;
						}
						if ((substr($log, -2) == 'p1') or (substr($log, -2) == 'p2') or (substr($log, -2) == 'p3') or (substr($log, -2) == 'p4') or (substr($log, -2) == 'p5') or (substr($log, -2) == 'p6') or (substr($log, -2) == 'p7'))
						{
							$shot_time_spent_paused = 0;
							$log_time = strtotime(substr($log, 5, 19));
							$x = substr($log, -1);
							$break += $x;
							if ($p1_active == true)
							{
								$points1 += $x;
							}
							else
							{
								$points2 += $x;
							}
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_miss = false;
						}
						if ((substr($log, -5) == 'foul4') or (substr($log, -5) == 'foul5') or (substr($log, -5) == 'foul6') or (substr($log, -5) == 'foul7'))
						{
							$shot_time_spent_paused = 0;
							$log_time = strtotime(substr($log, 5, 19));
							$break = 0;
							$x = substr($log, -1);
							if ($p1_active == true)
							{
								$points2 += $x;
								$p1_active = false;
								$p2_active = true;
							}
							else
							{
								$points1 += $x;
								$p1_active = true;
								$p2_active = false;
							}
							$show_miss = true;
						}
						if (substr($log, -4) == 'miss')
						{
							$shot_time_spent_paused = 0;
							$log_time = strtotime(substr($log, 5, 19));
							if ($p1_active == true)
							{
								$p1_active = false;
								$p2_active = true;
							}
							else
							{
								$p1_active = true;
								$p2_active = false;
							}
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_miss = false;
						}
						if (substr($log, -6) == 'change')
						{
							$shot_time_spent_paused = 0;
							$log_time = strtotime(substr($log, 5, 19));
							$break = 0;
							if ($p1_active == true)
							{
								$p1_active = false;
								$p2_active = true;
							}
							else
							{
								$p1_active = true;
								$p2_active = false;
							}
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_miss = false;
						}
						if (substr($log, -3) == 'win')
						{
							if ($p1_active == true)
							{
								$frames1 += 1;
							}
							else
							{
								$frames2 += 1;
							}
							
							$break = 0;
							$points1 = 0;
							$points2 = 0;
							
							/// Ustaw odpowiedniego gracza
							if (($frames1 + $frames2) % 2 == 0)
							{
								$p1_active = true;
								$p2_active = false;
							}
							else
							{
								$p1_active = false;
								$p2_active = true;
							}
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_normal = false;
							$show_miss = false;
							$show_start = true;
							$show_resume = false;
							$show_begin = false;
						}
						if (substr($log, -5) == 'pause')
						{
							$pause_time = strtotime(substr($log, 5, 19));
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_normal = false;
							$show_start = false;
							$show_resume = true;
							$show_begin = false;
						}
						if (substr($log, -6) == 'resume')
						{
							$resume_time = strtotime(substr($log, 5, 19));
							$shot_time_spent_paused += ($resume_time - $pause_time);
							$frame_time_spent_paused += ($resume_time - $pause_time);
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_normal = true;
							$show_start = false;
							$show_resume = false;
							$show_begin = false;
						}
						if (substr($log, -5) == 'start')
						{
							$log_time = strtotime(substr($log, 5, 19));
							$frame_start_time = $log_time;
							$shot_time_spent_paused = 0;
							$frame_time_spent_paused = 0;
							/// Zmienne odpowiadające za pokazywanie buttonów
							$show_normal = true;
							$show_start = false;
							$show_resume = false;
							$show_begin = false;
							$show_miss = false;
						}
						$shot_time = time() - $log_time - $shot_time_spent_paused;
						$frame_time = time() - $frame_start_time - $frame_time_spent_paused;
						$logs = substr($logs, $dlugosc); /// Obcięcie $logs o zinterpretowany log (usunięcie początku zmiennej)
					}
					
					if (($frames1 == floor(($bestof+1)/2)) or ($frames2 == floor(($bestof+1)/2)))
					{
						// dodac wstawianie koncowego wyniku do bazy (dla statsow)
						echo 'Zajebiscie, wygrales koles';
						unset($_SESSION['id_match']);
						// ustaw flage finished
						require_once "connect.php";
						$conn = new mysqli($servername, $username, $password, $dbname);
						mysqli_set_charset($conn, "utf8mb4");
						$sqlx = "UPDATE matches SET finished='true', p1_score='$frames1', p2_score='$frames2' WHERE id='$id_match'";
						$resultx = $conn->query($sqlx);
						/// Zmienne odpowiadające za pokazywanie buttonów
						$show_normal = false;
						$show_start = false;
						$show_resume = false;
						$show_begin = false;
						$show_miss = false;
					}
					
					echo <<< EOT
					<div id="wyswietlacz">
EOT;
						echo 'Venue:<br/>' . $_SESSION['venue_active'];
						echo '<div id="topka">';
							if ($p1_active == true)
								echo '<div id="gracz1_active">';
							else
								echo '<div id="gracz1">';
							echo $_SESSION['player1_active'];
							echo '<br/>' . $points1;
							echo '<br/>' . $frames1;
							echo '</div>';
							// zrobic div na break
							echo '<div id="srodek">';
							echo '<div id="brejkus">';
							if ($break != 0)
								echo 'Break<br/>' . $break;
							echo '</div>';
							
							echo '<div id="tajmer">';
							if ($show_resume == true or $show_begin == true or $show_start == true)
							{
								echo '<br/>Match paused';
							}
							else
							{
								echo 'Shot time: ' . $shot_time;
								echo '<br/>Frame time: ' . $frame_time;
							}
							echo '</div>';
							echo '</div>';
							if ($p2_active == true)
								echo '<div id="gracz2_active">';
							else
								echo '<div id="gracz2">';
							echo $_SESSION['player2_active'];
							echo '<br/>' . $points2;
							echo '<br/>' . $frames2;
							echo <<< EOT
							</div>
						</div>
						<div id="bestof">
EOT;
						echo 'Bestof<br/>' . $bestof;
						echo <<< EOT
						</div>
					</div>
EOT;
					echo '<div id="buttonsy">';
					
					if ($show_normal == true)
					{
						if ($show_miss == true)
						{
							echo <<< EOT
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Pause" name="pause" id="pause"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Change" name="change" id="change"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P1" name="p1" id="p1"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P2" name="p2" id="p2"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P3" name="p3" id="p3"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P4" name="p4" id="p4"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P5" name="p5" id="p5"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P6" name="p6" id="p6"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P7" name="p7" id="p7"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul4" name="foul4" id="foul4"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul5" name="foul5" id="foul5"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul6" name="foul6" id="foul6"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul7" name="foul7" id="foul7"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Miss" name="miss" id="miss"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Win" name="win" id="win"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Back" name="back" id="back"></form>
EOT;
						}
						else
						{
							echo <<< EOT
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Pause" name="pause" id="pause"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Change" name="change" id="change"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P1" name="p1" id="p1"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P2" name="p2" id="p2"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P3" name="p3" id="p3"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P4" name="p4" id="p4"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P5" name="p5" id="p5"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P6" name="p6" id="p6"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="P7" name="p7" id="p7"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul4" name="foul4" id="foul4"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul5" name="foul5" id="foul5"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul6" name="foul6" id="foul6"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Foul7" name="foul7" id="foul7"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Win" name="win" id="win"></form>
							<form method="post" action="submit_scoreboard.php"><input type="submit" value="Back" name="back" id="back"></form>
EOT;
						}
					}
					else
					{
						if ($show_begin == true)
						{
							echo '<form method="post" action="submit_scoreboard.php"><input type="submit" value="Start" name="start" id="start"></form>';
						}
						if ($show_resume == true)
						{
							echo '<form method="post" action="submit_scoreboard.php"><input type="submit" value="Resume" name="resume" id="resume"></form>';
							echo '<form method="post" action="submit_scoreboard.php"><input type="submit" value="Back" name="back" id="back"></form>';
						}
						if ($show_start == true)
						{
							echo '<form method="post" action="submit_scoreboard.php"><input type="submit" value="Start" name="start" id="start"></form>';
							echo '<form method="post" action="submit_scoreboard.php"><input type="submit" value="Back" name="back" id="back"></form>';
						}
					}
					
					echo '<form method="post" action="submit_goback.php">';
						echo '<input type="submit" value="GoToMatch_Back" name="GoToMatch_Back" id="GoToMatch_Back">';
					echo '</form>';

					echo '</div>';
				
			?>
			
		</div>
		
		<div id="footer">
			Snoo by Daniel Zuziak
		</div>
	
	</div>

</body>
</html>