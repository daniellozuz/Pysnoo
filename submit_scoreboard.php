<?php
	// There shall be only php which checks the data provided from scoreboard forms
	
	session_start();
	
	$info = 'Log: ';
	$now = date("Y-m-d H:i:s : ");
	
	if ((isset($_POST['pause'])) or (isset($_POST['resume'])) or (isset($_POST['begin'])) or (isset($_POST['start'])) or (isset($_POST['change'])) or (isset($_POST['p1'])) or (isset($_POST['p2'])) or (isset($_POST['p3'])) or (isset($_POST['p4'])) or (isset($_POST['p5'])) or (isset($_POST['p6'])) or (isset($_POST['p7'])) or (isset($_POST['foul4'])) or (isset($_POST['foul5'])) or (isset($_POST['foul6'])) or (isset($_POST['foul7'])) or (isset($_POST['win'])) or (isset($_POST['back'])) or (isset($_POST['miss']))) // Stworzenie odpowiednich logow do wyslania do bazy danych
	{
		if (isset($_POST['pause'])) //// zmienic na switch case czy cos
			$log = $info . $now . 'pause';
		if (isset($_POST['resume']))
			$log = $info . $now . 'resume';
		if (isset($_POST['begin']))
			$log = $info . $now . 'begin';
		if (isset($_POST['start']))
			$log = $info . $now . 'start';
		if (isset($_POST['change']))
			$log = $info . $now . 'change';
		if (isset($_POST['p1']))
			$log = $info . $now . 'p1';
		if (isset($_POST['p2']))
			$log = $info . $now . 'p2';
		if (isset($_POST['p3']))
			$log = $info . $now . 'p3';
		if (isset($_POST['p4']))
			$log = $info . $now . 'p4';
		if (isset($_POST['p5']))
			$log = $info . $now . 'p5';
		if (isset($_POST['p6']))
			$log = $info . $now . 'p6';
		if (isset($_POST['p7']))
			$log = $info . $now . 'p7';
		if (isset($_POST['foul4']))
			$log = $info . $now . 'foul4';
		if (isset($_POST['foul5']))
			$log = $info . $now . 'foul5';
		if (isset($_POST['foul6']))
			$log = $info . $now . 'foul6';
		if (isset($_POST['foul7']))
			$log = $info . $now . 'foul7';
		if (isset($_POST['miss']))
			$log = $info . $now . 'miss';
		if (isset($_POST['win']))
			$log = $info . $now . 'win';
		if (isset($_POST['back'])) // back jest tylko flaga, nie jest on zapisywany do bazy, po prostu usuwamy ostatni(e) niepoprawne logi
			$log = 'back';
		
		//echo $log;
		
		//// polacz z baza danych, wyciagnij loga meczu, dodaj kolejnego loga, zapisz do bazy
		require_once "connect.php";
		$conn = new mysqli($servername, $username, $password, $dbname);
		mysqli_set_charset($conn, "utf8mb4");

		if ($conn->connect_errno != 0)
			echo "Error: " . $conn->connect_errno;
		else
		{
			if ($log != 'back') // wstaw log na koniec
			{
				$id_match = $_SESSION['id_match'];
				$sqlx = "SELECT * FROM matches WHERE id='$id_match'";
				$resultx = $conn->query($sqlx);
				while ($row = $resultx->fetch_assoc())
				{
					$logs = $row['logs'];
				}
				$logs = $logs . $log;
				//echo $logs;
				
				$sql = "UPDATE matches SET logs='$logs' WHERE id='$id_match'";
				$conn->query($sql);
			}
			else // usun ostatni log
			{
				$id_match = $_SESSION['id_match'];
				$sqlx = "SELECT * FROM matches WHERE id='$id_match'";
				$resultx = $conn->query($sqlx);
				while ($row = $resultx->fetch_assoc())
				{
					$logs = $row['logs'];
				}
				if ($logs == '') // sprawdz czy pusto, jesli tak to nic nie rob (nie ma czego usuwac)
				{
					
				}
				else // usun ostatni log
				{
					$numerek = strrpos($logs, 'Log'); // znajdz odpowiednie miejsce
					echo $numerek;
					$proba = substr($logs, 0, $numerek); // $proba opisuje mi juz obciety $logs
					echo $proba;
					// wsadz $proba za $logs
					$sql = "UPDATE matches SET logs='$proba' WHERE id='$id_match'";
					$conn->query($sql);
				}
			}
			// sprawdz czy jest tylko 1 log, jesli tak to nie pozwol na wyswietlanie back i wysylanie kolejnych usuniec.
		}
	}
	
	if (isset($log))
		unset($log);
	
	header("Location: http://localhost/snooker/scoreboard.php");
	
?>