<?php
	
	session_start();
	
	if (isset($_POST['log_out']))
	{
		unset($_SESSION['logged_in']);
		unset($_SESSION['my_username']);
		unset($_SESSION['my_id']);
	}
	
	header("Location: http://localhost/snooker/index.php");
	
?>