<?php

	session_start();	

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
		
		Sorry, if you want to have an access to this feature, please sign in.
		<br/><br/>If you do not have an account, please sign up below.
		
		<form method="post" action="submit_register.php">
			<input type="text" placeholder="name" name="reg_name" id="reg_name">
			<input type="text" placeholder="surname" name="reg_surname" id="reg_surname">
			<input type="text" placeholder="password" name="reg_password" id="reg_password">
			<input type="text" placeholder="date of birth [yyyy-mm-dd]" name="reg_dob" id="reg_dob">
			<input type="submit" value="Sign in" name="go_register" id="go_register">
		</form>
		
		</div>
		
		<div id="footer">
			Snoo by Daniel Zuziak
		</div>
	
	</div>

</body>
</html>