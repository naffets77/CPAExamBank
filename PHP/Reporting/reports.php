<?php

 // Do check to verify an admin user is logged in
 var_dump($_POST);


?>



<html>
	<head>
	</head>
	<body>


		<form  method="post" action="<?php echo $PHP_SELF;?>">
			<table>
				<tr>
					<td>
						<select name='report'>
							<option value='rep-registered_users'>Registered Users</option>
						</select>
					</td>
					<td>
						<input type='submit' />
					</td>
				</tr>
			</table>
		</form>



	</body>
</html>