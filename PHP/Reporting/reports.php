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

		<title>Reports</title>

		<style>
			table.db-table 		{ border-right:1px solid #ccc; border-bottom:1px solid #ccc; }
			table.db-table td	{ padding:5px; border-left:1px solid #ccc; border-top:1px solid #ccc; }

			.visualize{font-size:62.5%;}
			.visTable{display:none;}
		</style>

        <link rel="stylesheet" type="text/css" href="../../Scripts/Plugins/jQuery.Vis/css/visualize.css">
        <link rel="stylesheet" type="text/css" href="../../Scripts/Plugins/jQuery.Vis/css/visualize-light.css">

	</head>
	<body>


		<form  method="post" action="">
			<table>
				<tr>
					<td>
						<select name='report'>
							<option value='rep_registered_users'>Registered Users</option>
							<option value='rep_question_history'>Question History Usage</option>
                            <option value='rep_raw_question_usage'>Question Aggregate Usage</option>
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


		<!--<table class='visTable'>
			<caption>2009 Employee Sales by Department</caption>
			<thead>
				<tr>
					<td></td>
					<th scope="col">food</th>
					<th scope="col">auto</th>
					<th scope="col">household</th>
					<th scope="col">furniture</th>
					<th scope="col">kitchen</th>
					<th scope="col">bath</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th scope="row">Mary</th>
					<td>190</td>
					<td>160</td>
					<td>40</td>
					<td>120</td>
					<td>30</td>
					<td>70</td>
				</tr>
				<tr>
					<th scope="row">Tom</th>
					<td>3</td>
					<td>40</td>
					<td>30</td>
					<td>45</td>
					<td>35</td>
					<td>49</td>
				</tr>
				<tr>
					<th scope="row">Brad</th>
					<td>10</td>
					<td>180</td>
					<td>10</td>
					<td>85</td>
					<td>25</td>
					<td>79</td>
				</tr>
				<tr>
					<th scope="row">Kate</th>
					<td>40</td>
					<td>80</td>
					<td>90</td>
					<td>25</td>
					<td>15</td>
					<td>119</td>
				</tr>		
			</tbody>
		</table>-->	



		<script src='../../Scripts/jquery-2.0.3.min.js' type='text/javascript'></script>
		<script src='../../Scripts/Plugins/jQuery.Vis/js/visualize.jQuery.js' type='text/javascript'></script>

		<script type='text/javascript'>
			$(document).on('ready', function(){



				//$('table').visualize({type: 'bar', width: '420px'});


			});
		</script>

	</body>
</html>