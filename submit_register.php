<?php
	
	session_start();
	
	if (isset($_POST['reg_name']) and isset($_POST['reg_surname']) and isset($_POST['reg_password']) and isset($_POST['reg_dob'])) // obsluga wyslania danych w polu logowania
	{
		$reg_name = $_POST['reg_name'];
		$reg_surname = $_POST['reg_surname'];
		$reg_password = $_POST['reg_password'];
		$reg_dob = $_POST['reg_dob'];
		
		// check if data ok
		$all_OK = true;
		/// Name check
		if (!ctype_alpha($reg_name))
			$all_OK = false;
		/// Surname check
		if (!ctype_alpha($reg_surname))
			$all_OK = false;
		if ($reg_password == '')
			$all_OK = false;
		if ($reg_dob == '')
			$all_OK = false;
		
		if ($all_OK == true)
		{
			$reg_username = $reg_name . ' ' . $reg_surname;
			// sprawdz czy istnieje
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
					$result = $conn->query("SELECT username FROM users WHERE username='$reg_username'");
					if (!$result) throw new Exception($conn->error);
					
					if ($result->num_rows > 0)
					{
						$_SESSION['e_player_exists'] = 'Sorry man, such a player already exists.';
					}
					
					$conn->close();
				}
			}
			catch(Exception $e)
			{
				echo '<span class="error"> Server error, sorry for the inconvenience.</span>';
				echo '<br /> Developer info: '.$e;
			}
			// jesli nie to wstaw
			if (!isset($_SESSION['e_player_exists']))
			{
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
						$result = $conn->query("INSERT INTO users VALUES (NULL, '$reg_username', '$reg_password', '$reg_dob')");
						if (!$result) throw new Exception($conn->error);
						
						$conn->close();
					}
				}
				catch(Exception $e)
				{
					echo '<span class="error"> Server error, sorry for the inconvenience.</span>';
					echo '<br /> Developer info: '.$e;
				}
			}
		}
	}
	
	header("Location: http://localhost/snooker/index.php");
	
?>