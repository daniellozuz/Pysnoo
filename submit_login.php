<?php
	
	session_start();
	
	if (isset($_POST['username']) and isset($_POST['password'])) // obsluga wyslania danych w polu logowania
	{
		$my_username = $_POST['username'];
		$my_password = $_POST['password'];
		
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
				$result = $conn->query("SELECT id, username, password FROM users WHERE username='$my_username'");
				if (!$result) throw new Exception($conn->error);
				
				while ($row = $result->fetch_assoc())
				{
					if ($my_password == $row['password'])
					{
						$_SESSION['logged_in'] = true;
						$_SESSION['my_username'] = $my_username;
						$_SESSION['my_id'] = $row['id'];
					}
					else
						$_SESSION['e_login'] = 'Sorry, but error login.';
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
	
	header("Location: http://localhost/snooker/index.php");
	
?>