<?php

include_once("ReportBuilder/reports.php");

error_reporting(E_ALL);
ini_set('display_errors', '1');

if(isset($_POST['report'])){
	$report = $_POST['report'];
	$rb = new ReportBuilder();
 }

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

		<hr />

		<?php
			if(isset($report)){
				$rb->build_report($report);
			}
		?>



	</body>
</html>