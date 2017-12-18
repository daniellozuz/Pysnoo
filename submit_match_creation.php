<?php
	// There shall be only php which checks the data provided from match_creation form
	
	session_start();
	
	if (isset($_POST['GoToMatch'])) // obsluga wyslania danych w celu przejscia do meczu
	{
		if (isset($_SESSION['my_username'])) // jesli jestes zalogowany -> player1 to Ty
			$player1 = $_SESSION['my_username'];
		else // jesli nie jestes zalogowany -> player1 przyjdzie postem
			$player1 = $_POST['player1'];
		
		$player2 = $_POST['player2'];
		$venue = $_POST['venue'];
		$bestof = (int)$_POST['bestof'];
		
		require_once "connect.php";
		$conn = new mysqli($servername, $username, $password, $dbname);
		mysqli_set_charset($conn, "utf8mb4");

		if ($conn->connect_errno != 0)
			echo "Error: " . $conn->connect_errno;
		else // brak bledu przy laczeniu sie z baza danych
		{
			$all_OK = true; // flaga poprawnosci danych
			$sql_player1 = "SELECT * FROM users WHERE username='$player1' LIMIT 1";
			$result_player1 = $conn->query($sql_player1);
			if ($result_player1->num_rows > 0) // sprawdzenie czy player1 istnieje i pobranie jego id
			{
				while ($row = $result_player1->fetch_assoc())
				{
					$id_player1 = $row['id'];
					$_SESSION['player1_active'] = $row['username'];
				}
				$all_OK = true;
			}
			else // nie istnieje taki player1
				$all_OK = false; 
			
			$sql_player2 = "SELECT * FROM users WHERE username='$player2' LIMIT 1";
			$result_player2 = $conn->query($sql_player2);
			if ($result_player2->num_rows > 0) // sprawdzenie czy player2 istnieje i pobranie jego id
			{
				while ($row = $result_player2->fetch_assoc())
				{
					$id_player2 = $row['id'];
					$_SESSION['player2_active'] = $row['username'];
				}
				$all_OK = true;
			}
			else // nie istnieje taki player2
				$all_OK = false;
			
			$sql_venue = "SELECT * FROM clubs WHERE clubname='$venue' LIMIT 1";
			$result_venue = $conn->query($sql_venue);
			if ($result_venue->num_rows > 0) // sprawdzenie czy venue istnieje i pobranie jego id
			{
				while ($row = $result_venue->fetch_assoc())
				{
					$id_venue = $row['id'];
					$_SESSION['venue_active'] = $row['clubname'];
				}
				$all_OK = true;
			}
			else // nie istnieje takie venue
				$all_OK = false;
			
			if (!is_int($bestof) or ($bestof <= 0)) // sprawdzenie poprawnosci best of
				$all_OK = false;
		}
		//// na koniec ustaw aktownosc meczu jesli zostal stworzony, jak nie daj error i nie ustawiaj flagi
		if ($all_OK == true) // przejscie do meczu jesli dane sa ok (stworzenie nowego meczu lub wczytanie starego)
		{
			//// stworz mecz w db, sprawdz czy juz taki nie istnieje (ukonczonosc) pozniej uzyj go aby wypelnic wartosci w sekcji Klikniete
			require_once "connect.php";
			$conn = new mysqli($servername, $username, $password, $dbname);
			mysqli_set_charset($conn, "utf8mb4");

			if ($conn->connect_errno != 0)
				echo "Error: " . $conn->connect_errno;
			else // brak bledu w laczeniu sie z baza danych
			{
				$sqlx = "SELECT * FROM matches WHERE player1='$id_player1' AND player2='$id_player2' AND club='$id_venue' AND finished='false'";
				$resultx = $conn->query($sqlx);
				if ($resultx->num_rows > 0) // taki mecz juz istnieje, zapisz jego id
				{
					while ($row = $resultx->fetch_assoc())
					{
						$id_match = $row['id'];
						echo 'Existing match loaded.' . $id_match;
					}
				}
				else // to nowy mecz, stworz go i zapisz jego id
				{
					$now = date("Y-m-d H:i:s : ");
					$log_begin = 'Log: ' . $now . 'begin'; // nie dziala jak probuje to wsadzic do logsow
					$sql = "INSERT INTO matches VALUES (NULL, '$id_player1', '$id_player2', '$id_venue', '$bestof', 'Log: 2016-11-26 17:20:51 : begin', 'false', 'false', 'false', 'false', '$now', 0, 0)";
					$conn->query($sql);
					
					$sqlx = "SELECT * FROM matches WHERE player1='$id_player1' AND player2='$id_player2' AND club='$id_venue' AND finished='false'";
					$resultx = $conn->query($sqlx);
					while ($row = $resultx->fetch_assoc())
					{
						$id_match = $row['id'];
						echo 'A new match created.';
					}
				}
				$_SESSION['id_match'] = $id_match;
			}
		}
		else
		{
			$_SESSION['e_create_match'] = 'Sorry, but error match create.';
		}
	}
	
	header("Location: http://localhost/snooker/match_creation.php");
	
?>