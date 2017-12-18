<?php
	
	session_start();
	
	if (isset($_POST['GoToMatch_Back']))
	{
		unset($_SESSION['id_match']);
	}
	
	header("Location: http://localhost/snooker/match_creation.php");
	
?>