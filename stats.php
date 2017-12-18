<?php

	session_start();	
	
	if (!isset($_SESSION['my_username'])) // Obsluga przekierowania do rejestracji w przypadku nie bycia zalogowanym
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
	
	<script type="text/javascript">
	
		function prepare(pts_tot1, pts_tot2, b_potted_tot1, b_potted_tot2, timee, time_at_tab1, time_at_tab2, time_per_shot1, time_per_shot2, breaks1, breaks2)
		{
			var zawartosc = "/>Punkty zdobyte przez P1: " + pts_tot1 + "<br/>Punkty zdobyte przez P2: " + pts_tot2 + "<br/>Bile wbite przez P1: " + b_potted_tot1 + "<br/>Bile wbite przez P2: " + b_potted_tot2 + "<br/>Czas frejma: " + timee + "<br/>Czas przy stole P1: " + time_at_tab1 + "<br/>Czas przy stole P2: " + time_at_tab2 + "<br/>Czas na zagranie P1: " + time_per_shot1 + "<br/>Czas na zagranie P2: " + time_per_shot2 + "<br/>Brejki P1: " + breaks1 + "<br/>Brejki P2: " + breaks2;
					
			document.getElementById("gownienko").innerHTML = zawartosc;
		}
		
	</script>
	
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
			
				if (isset($_POST['show_match']))
				{
					$_SESSION['show_match'] = true;
					$_SESSION['shown_match_id'] = $_POST['show_match'];
				}
				
				if (isset($_POST['hide_match']))
				{
					unset($_SESSION['show_match']);
				}
				
				if (isset($_SESSION['show_match']))
				{
					// Wyswietlanie wybranego meczu
					//echo 'Wysw mecz i stworz przycisk unsetujacy show_match';
					// hide match
					echo '<div class="hide_match">';
						echo '<form method="post">';
							echo '<input type="hidden" name="hide_match" value="XXXX"></input>';
							echo '<input type="submit" id="hide_match_button" value="Hide match"></input>';
						echo '</form>';
					echo '</div>';
					
					// Stworz tabele: ilosc wierszy = ilosc frejmow
					// Numer frejma; punkty p1; punkty p2; czas frejma;
					// Po kliknieciu row pokazuje sie szczegolowa statystyka danego frejma na boku. Poczatkowo na boku jest szczegolowa statystyka calego meczu.
					
					// Tworzenie tabeli
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
							$shown_match_id = $_SESSION['shown_match_id'];
							$result = $conn->query("SELECT IFNULL(B.username,'') player1, IFNULL(C.username,'') player2, IFNULL(D.clubname,'') clubname, A.logs, A.bestof, A.date, A.p1_score, A.p2_score FROM matches A LEFT JOIN users B ON A.player1 = B.id LEFT JOIN users C ON A.player2 = C.id LEFT JOIN clubs D ON A.club = D.id WHERE A.id='$shown_match_id'");
							if (!$result) throw new Exception($conn->error);
							
							// Jesli jest co
							if ($result->num_rows > 0)
							{
								// Policz ile frejmow
								$no_frames = 0;
								while ($row = $result->fetch_assoc())
								{
									$p1_score = $row['p1_score'];
									$p2_score = $row['p2_score'];
									$bestof = $row['bestof'];
									$no_frames = $p1_score + $p2_score;
									$logs = $row['logs'];
									$time_begin = $row['date'];
									$player1 = $row['player1'];
									$player2 = $row['player2'];
									$venue = $row['clubname'];
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
										$frame_number = 1;
										$p1_active = true;
										$p2_active = false;
										$points_scored1 = 0;
										$points_scored2 = 0;
										$points_given_away1 = 0;
										$points_given_away2 = 0;
										$balls_potted1 = 0;
										$balls_potted2 = 0;
										$number_of_shots1 = 0;
										$number_of_shots2 = 0;
										$number_of_fouls1 = 0;
										$number_of_fouls2 = 0;
										$break = 0;
										/// Zmienne typu array, przechowywanie frejmow i meczu.
										$f_points_scored1 = array();
										$f_points_scored2 = array();
										$f_points_given_away1 = array();
										$f_points_given_away2 = array();
										$f_balls_potted1 = array();
										$f_balls_potted2 = array();
										$f_number_of_shots1 = array();
										$f_number_of_shots2 = array();
										$f_number_of_fouls1 = array();
										$f_number_of_fouls2 = array();
										$f_breaks1 = array();
										$f_breaks2 = array();
										$m_breaks1 = array();
										$m_breaks2 = array();
										/// Zmienne odpowiadające za czas
										$frame_times = array();
										$frame_times_at_table1 = array();
										$frame_times_at_table2 = array();
									}
									if ((substr($log, -2) == 'p1') or (substr($log, -2) == 'p2') or (substr($log, -2) == 'p3') or (substr($log, -2) == 'p4') or (substr($log, -2) == 'p5') or (substr($log, -2) == 'p6') or (substr($log, -2) == 'p7'))
									{
										$x = substr($log, -1);
										
										$break += $x;
										$time_now = strtotime(substr($log, 5, 19));
										if ($p1_active == true)
										{
											$balls_potted1 += 1;
											$points_scored1 += $x;
											$number_of_shots1 += 1;
											$frame_time_at_table1 += ($time_now - $time_last - $shot_time_spent_paused);
										}
										else
										{
											$balls_potted2 += 1;
											$points_scored2 += $x;
											$number_of_shots2 += 1;
											$frame_time_at_table2 += ($time_now - $time_last - $shot_time_spent_paused);
										}
										$time_last = $time_now;
										$shot_time_spent_paused = 0;
									}
									if ((substr($log, -5) == 'foul4') or (substr($log, -5) == 'foul5') or (substr($log, -5) == 'foul6') or (substr($log, -5) == 'foul7'))
									{
										$x = substr($log, -1);
										$time_now = strtotime(substr($log, 5, 19));
										if ($p1_active == true)
										{
											$frame_time_at_table1 += ($time_now - $time_last - $shot_time_spent_paused);
											if ($break != 0)
												array_push($f_breaks1, "$break");
											$break = 0;
											$number_of_shots1 += 1;
											$number_of_fouls1 += 1;
											$points_given_away1 += $x;
											$p1_active = false;
											$p2_active = true;
										}
										else
										{
											$frame_time_at_table2 += ($time_now - $time_last - $shot_time_spent_paused);
											if ($break != 0)
												array_push($f_breaks2, "$break");
											$break = 0;
											$number_of_shots2 += 1;
											$number_of_fouls2 += 1;
											$points_given_away2 += $x;
											$p1_active = true;
											$p2_active = false;
										}
										$time_last = $time_now;
										$shot_time_spent_paused = 0;
									}
									if (substr($log, -4) == 'miss')
									{
										$time_now = strtotime(substr($log, 5, 19));
										if ($p1_active == true)
										{
											$frame_time_at_table1 += ($time_now - $time_last - $shot_time_spent_paused);
											$p1_active = false;
											$p2_active = true;
										}
										else
										{
											$frame_time_at_table2 += ($time_now - $time_last - $shot_time_spent_paused);
											$p1_active = true;
											$p2_active = false;
										}
										$time_last = $time_now;
										$shot_time_spent_paused = 0;
									}
									if (substr($log, -6) == 'change')
									{
										$time_now = strtotime(substr($log, 5, 19));
										if ($p1_active == true)
										{
											$frame_time_at_table1 += ($time_now - $time_last - $shot_time_spent_paused);
											if ($break != 0)
												array_push($f_breaks1, "$break");
											$break = 0;
											$number_of_shots1 += 1;
											$p1_active = false;
											$p2_active = true;
										}
										else
										{
											$frame_time_at_table2 += ($time_now - $time_last - $shot_time_spent_paused);
											if ($break != 0)
												array_push($f_breaks2, "$break");
											$break = 0;
											$number_of_shots2 += 1;
											$p1_active = true;
											$p2_active = false;
										}
										$time_last = $time_now;
										$shot_time_spent_paused = 0;
									}
									if (substr($log, -3) == 'win')
									{
										$time_now = strtotime(substr($log, 5, 19));
										if ($p1_active == true)
										{
											if ($break != 0)
												array_push($f_breaks1, "$break");
											$frame_time_at_table1 += ($time_now - $time_last - $shot_time_spent_paused);
										}
										else
										{
											if ($break != 0)
												array_push($f_breaks2, "$break");
											$frame_time_at_table2 += ($time_now - $time_last - $shot_time_spent_paused);
										}
										
										array_push($m_breaks1, $f_breaks1);
										$f_breaks1 = array();
										array_push($m_breaks2, $f_breaks2);
										$f_breaks2 = array();
										
										$frame_number += 1;
										$break = 0;
										
										array_push($f_points_scored1, "$points_scored1");
										array_push($f_points_scored2, "$points_scored2");
										$points_scored1 = 0;
										$points_scored2 = 0;
										array_push($f_balls_potted1, "$balls_potted1");
										array_push($f_balls_potted2, "$balls_potted2");
										$balls_potted1 = 0;
										$balls_potted2 = 0;
										array_push($f_number_of_shots1, "$number_of_shots1");
										array_push($f_number_of_shots2, "$number_of_shots2");
										$number_of_shots1 = 0;
										$number_of_shots2 = 0;
										array_push($frame_times_at_table1, $frame_time_at_table1);
										array_push($frame_times_at_table2, $frame_time_at_table2);
										array_push($f_number_of_fouls1, "$number_of_fouls1");
										array_push($f_number_of_fouls2, "$number_of_fouls2");
										$number_of_fouls1 = 0;
										$number_of_fouls2 = 0;
										array_push($f_points_given_away1, "$points_given_away1");
										array_push($f_points_given_away2, "$points_given_away2");
										$points_given_away1 = 0;
										$points_given_away2 = 0;
										
										/// Ustaw odpowiedniego gracza
										if ($frame_number % 2 != 0)
										{
											$p1_active = true;
											$p2_active = false;
										}
										else
										{
											$p1_active = false;
											$p2_active = true;
										}
										
										$frame_ending = strtotime(substr($log, 5, 19));
										
										$frame_time = $frame_ending - $frame_beginning - $frame_time_spent_paused;
										array_push($frame_times, $frame_time);
									}
									if (substr($log, -5) == 'pause')
									{
										$pause_time = strtotime(substr($log, 5, 19));
									}
									if (substr($log, -6) == 'resume')
									{
										$resume_time = strtotime(substr($log, 5, 19));
										$frame_time_spent_paused += ($resume_time - $pause_time);
										$shot_time_spent_paused += ($resume_time - $pause_time);
									}
									if (substr($log, -5) == 'start')
									{
										$frame_beginning = strtotime(substr($log, 5, 19));
										$frame_time_spent_paused = 0;
										$shot_time_spent_paused = 0;
										$frame_time_at_table1 = 0;
										$frame_time_at_table2 = 0;
										$time_last = strtotime(substr($log, 5, 19));
									}
									
									$logs = substr($logs, $dlugosc); /// Obcięcie $logs o zinterpretowany log (usunięcie początku zmiennej)
								} /// Koniec interpretacji logów.
								
								// Budowa tabelki
								$match_time = array_sum($frame_times);
								echo '<div id="matc_table">';
									echo '<div class="matc_table_row">';
										echo '<div class="entry" style="width:610px;">' . $venue . '</div>';
									echo '</div>';
									echo '<div class="matc_table_row">';
										echo '<div class="entry" style="width:100px;">Total</div><div class="entry" style="width:130px;">' . $p1_score . '</div><div class="entry" style="width:70px;">(' . $bestof . ')</div><div class="entry" style="width:130px;">' . $p2_score . '</div><div class="entry" style="width:100px;">' . gmdate("H:i:s", $match_time) . '</div>';
									echo '</div>';
									echo '<div class="matc_table_row">';
										echo '<div class="entry" style="width:100px;"></div><div class="entry" style="width:175px;">' . $player1 . '</div><div class="entry" style="width:175px;">' . $player2 . '</div>';
									echo '</div>';
									
									$match_points_scored1 = 0;
									$match_points_scored2 = 0;
									$match_balls_potted1 = 0;
									$match_balls_potted2 = 0;
									$match_number_of_shots1 = 0;
									$match_number_of_shots2 = 0;
									$match_time_at_table1 = 0;
									$match_time_at_table2 = 0;
									$match_number_of_fouls1 = 0;
									$match_number_of_fouls2 = 0;
									$match_points_given_away1 = 0;
									$match_points_given_away2 = 0;
									
									for ($i = 0; $i < $no_frames; $i++)
									{										
										$j = $i + 1;
										$k = $i - 1;
										echo '<div class="matc_table_row" onclick="prepare('."'".$f_points_scored1[$i]."'".','."'".$f_points_scored2[$i]."'".','."'".$f_balls_potted1[$i]."'".','."'".$f_balls_potted2[$i]."'".','."'".gmdate("H:i:s", $frame_times[$i])."'".','."'".gmdate("H:i:s", $frame_times_at_table1[$i])."'".','."'".gmdate("H:i:s", $frame_times_at_table2[$i])."'".','."'".gmdate("H:i:s", $frame_times_at_table1[$i]/$f_number_of_shots1[$i])."'".','."'".gmdate("H:i:s", $frame_times_at_table2[$i]/$f_number_of_shots2[$i])."'".',[';
										// tutaj wsadz cala tablice ktora chcesz przeslac
										//echo '1,2,3,4';
										for ($k = 0; $k < count($m_breaks1[$i]); $k++)
										{
											echo $m_breaks1[$i][$k];
											echo ',';
										}
										
										echo '],[';
										for ($k = 0; $k < count($m_breaks2[$i]); $k++)
										{
											echo $m_breaks2[$i][$k];
											echo ',';
										}
										
										echo '])">';
										echo '<div class="entry" style="width:100px;">Frame: ' . $j . '</div><div class="entry" style="width:175px;">' . ($f_points_scored1[$i] + $f_points_given_away2[$i]) . '</div><div class="entry" style="width:175px;">' . ($f_points_scored2[$i] + $f_points_given_away1[$i]) . '</div><div class="entry" style="width:100px;">' . gmdate("H:i:s", $frame_times[$i]) . '</div>';
										echo '</div>';
										$match_points_scored1 += $f_points_scored1[$i];
										$match_points_scored2 += $f_points_scored2[$i];
										$match_number_of_fouls1 += $f_number_of_fouls1[$i];
										$match_number_of_fouls2 += $f_number_of_fouls2[$i];
										$match_points_given_away1 += $f_points_given_away1[$i];
										$match_points_given_away2 += $f_points_given_away2[$i];
										$match_balls_potted1 += $f_balls_potted1[$i];
										$match_balls_potted2 += $f_balls_potted2[$i];
										$match_number_of_shots1 += $f_number_of_shots1[$i];
										$match_number_of_shots2 += $f_number_of_shots2[$i];
										$match_time_at_table1 += $frame_times_at_table1[$i];
										$match_time_at_table2 += $frame_times_at_table2[$i];
									}
									
									echo '<div style="clear: both;"></div>';
								echo '</div>';
								
								// Po tabelce walimy statsy obok niej
								echo '<div id="gownienko">'; // onclick bedzie ustawial zawartosc diva gownienko
								//echo 'Tu beda glowne statsy. Preferably jakis graf ze stosunkiem wygranych do rpzegranych meczy<br/>Wygrane/przegrane mecze/frejmy.';
								echo '<br/>Punkty zdobyte przez P1: ' . $match_points_scored1;
								echo '<br/>Punkty zdobyte przez P2: ' . $match_points_scored2;
								echo '<br/>Ilosc fauli P1: ' . $match_number_of_fouls1;
								echo '<br/>Ilosc fauli P2: ' . $match_number_of_fouls2;
								echo '<br/>Punkty oddane przez P1: ' . $match_points_given_away1;
								echo '<br/>Punkty oddane przez P2: ' . $match_points_given_away2;
								echo '<br/>Bile wbite przez P1: ' . $match_balls_potted1;
								echo '<br/>Bile wbite przez P2: ' . $match_balls_potted2;
								$avg_frame_time = $match_time / $no_frames;
								echo '<br/>Sredni czas frejma: ' . floor($avg_frame_time/60) . 'm ' . $avg_frame_time%60 . 's';
								echo '<br/>Czas przy stole P1: ' . floor($match_time_at_table1/60) . 'm ' . $match_time_at_table1%60 . 's';
								echo '<br/>Czas przy stole P2: ' . floor($match_time_at_table2/60) . 'm ' . $match_time_at_table2%60 . 's';
								$avg_shot_time1 = $match_time_at_table1 / $match_number_of_shots1;
								echo '<br/>Sredni czas na zagranie P1: ' . floor($avg_shot_time1) . 's';
								$avg_shot_time2 = $match_time_at_table2 / $match_number_of_shots2;
								echo '<br/>Sredni czas na zagranie P2: ' . floor($avg_shot_time2) . 's';
								$wynik = 0;
								$suma = 0;
								$ilosc = 0;
								for ($i = 0; $i < count($m_breaks1); $i++)
								{
									if (count($m_breaks1[$i]) > 0)
									{
										$suma = $suma + array_sum($m_breaks1[$i]);
										$ilosc = $ilosc + count($m_breaks1[$i]);
										$wynik_try = max($m_breaks1[$i]);
										if ($wynik_try > $wynik)
											$wynik = $wynik_try;
									}
								}
								echo '<br/>Najwyższy brejk P1: ' . $wynik;
								echo '<br/>Sredni brejk P1: ' . round($suma/$ilosc,2);
								$wynik = 0;
								$suma = 0;
								$ilosc = 0;
								for ($i = 0; $i < count($m_breaks2); $i++)
								{
									if (count($m_breaks2[$i]) > 0)
									{
										$suma = $suma + array_sum($m_breaks2[$i]);
										$ilosc = $ilosc + count($m_breaks2[$i]);
										$wynik_try = max($m_breaks2[$i]);
										if ($wynik_try > $wynik)
											$wynik = $wynik_try;
									}
								}
								echo '<br/>Najwyższy brejk P2: ' . $wynik;
								echo '<br/>Sredni brejk P2: ' . round($suma/$ilosc,2);
								echo '</div>';
								//print_r($f_pts_1);
								//print_r($f_pts_2);
								//print_r($f_time);
							}
							$conn->close();
						}
					}
					catch(Exception $e)
					{
						echo '<span class="error"> Server error, sorry for the inconvenience.</span>';
						echo '<br /> Developer info: '.$e;
					}
				}
				else
				{
					// Wyswietlanie tabeli z meczami
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
							$result = $conn->query("SELECT IFNULL(B.username,'') player1, IFNULL(C.username,'') player2, IFNULL(D.clubname,'') clubname, A.id, A.bestof, A.date, A.p1_score, A.p2_score FROM matches A LEFT JOIN users B ON A.player1  = B.id LEFT JOIN users C ON A.player2 = C.id LEFT JOIN clubs D ON A.club = D.id WHERE (player1 = '$my_id' OR player2 = '$my_id') AND finished='true'");
							if (!$result) throw new Exception($conn->error);
							
							// Jesli jest co
							if ($result->num_rows > 0)
							{
								// Tworz tabele
								echo '<div id="matc_table">';
									echo '<div class="matc_table_row">';
										echo '<div class="entry" style="width:50px;">id</div><div class="entry" style="width:150px;">p1</div><div class="entry" style="width:40px;">Score</div><div class="entry" style="width:40px;">Score</div><div class="entry" style="width:150px;">p2</div><div class="entry" style="width:150px;">club</div><div class="entry" style="width:90px;">bestof</div><div class="entry" style="width:250px;">date</div>';
									echo '</div>';
									echo '<div style="clear: both;"></div>';
								while ($row = $result->fetch_assoc())
								{
									echo '<div class="matc_table_row">'; // dodac onclick(zmiana wyswietlania statystyk z general -> domyslnego; na konkretny mecz)
										echo '<div class="entry" style="width:50px;">' . $row['id'] . '</div><div class="entry" style="width:150px;">' . $row['player1'] . '</div><div class="entry" style="width:40px;">' . $row['p1_score'] . '</div><div class="entry" style="width:40px;">' . $row['p2_score'] . '</div><div class="entry" style="width:150px;">' . $row['player2'] . '</div><div class="entry" style="width:150px;">' . $row['clubname'] . '</div><div class="entry" style="width:90px;">' . $row['bestof'] . '</div><div class="entry" style="width:250px;">' . $row['date'] . '</div>';
										// submit -> idz do meczu
										echo '<div class="show_match">';
											echo '<form method="post">';
												echo '<input type="hidden" name="show_match" value="'.$row['id'].'"></input>';
												echo '<input type="submit" id="show_match_button" value="Show Match"></input>';
											echo '</form>';
										echo '</div>';
										
									echo '</div>';
									echo '<div style="clear: both;"></div>';
								}
								echo '</div>';
							}
							$conn->close();
						}
					}
					catch(Exception $e)
					{
						echo '<span class="error"> Server error, sorry for the inconvenience.</span>';
						echo '<br /> Developer info: '.$e;
					}
					
				echo '<div id="gownienko">';
				//echo 'Tu beda glowne statsy. Preferably jakis graf ze stosunkiem wygranych do rpzegranych meczy<br/>Wygrane/przegrane mecze/frejmy.';
					
				// Zlicz zakonczone mecze, wygrane/przegrane zakonczone mecze
				$sum_f_you = 0;
				$sum_m_you = 0;
				$sum_f_opp = 0;
				$sum_m_opp = 0;
				
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
						$result = $conn->query("SELECT p1_score, p2_score FROM matches WHERE player1='$my_id' AND finished='true'");
						if (!$result) throw new Exception($conn->error);
						
						while ($row = $result->fetch_assoc())
						{
							$sum_f_you = $sum_f_you + $row['p1_score'];
							$sum_f_opp = $sum_f_opp + $row['p2_score'];
							if ($row['p1_score'] > $row['p2_score'])
								$sum_m_you = $sum_m_you + 1;
							else
								$sum_m_opp = $sum_m_opp + 1;
						}
						$conn->close();
					}
				}
				catch(Exception $e)
				{
					echo '<span class="error"> Server error, sorry for the inconvenience.</span>';
					echo '<br /> Developer info: '.$e;
				}
				
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
						$result = $conn->query("SELECT p1_score, p2_score FROM matches WHERE player2='$my_id' AND finished='true'");
						if (!$result) throw new Exception($conn->error);
						
						while ($row = $result->fetch_assoc())
						{
							$sum_f_you = $sum_f_you + $row['p2_score'];
							$sum_f_opp = $sum_f_opp + $row['p1_score'];
							if ($row['p2_score'] > $row['p1_score'])
								$sum_m_you = $sum_m_you + 1;
							else
								$sum_m_opp = $sum_m_opp + 1;
						}
						$conn->close();
					}
				}
				catch(Exception $e)
				{
					echo '<span class="error"> Server error, sorry for the inconvenience.</span>';
					echo '<br /> Developer info: '.$e;
				}
				
				echo '<br/>Wygrane mecze:' . $sum_m_you;
				echo '<br/>Przegrane mecze:' . $sum_m_opp;
				echo '<br/>Wygrane frejmy:' . $sum_f_you;
				echo '<br/>Przegrane frejmy:' . $sum_f_opp;
				
				echo '</div>';
				}
			?>
		</div>
		
		<div id="footer">
			Snoo by Daniel Zuziak
		</div>
	
	</div>

</body>
</html>