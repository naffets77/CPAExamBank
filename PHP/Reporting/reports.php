﻿<?php

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

		<style>
			table.db-table 		{ border-right:1px solid #ccc; border-bottom:1px solid #ccc; }
			table.db-table td	{ padding:5px; border-left:1px solid #ccc; border-top:1px solid #ccc; }
		</style>

	</head>
	<body>


		<form  method="post" action="">
			<table>
				<tr>
					<td>
						<select name='report'>
							<option value='rep_registered_users'>Registered Users</option>
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