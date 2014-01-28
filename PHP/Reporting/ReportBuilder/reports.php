<?php




class ReportBuilder {
    




    public function build_report($report) {

		

		$this->connect_db();
		
		//call_user_func($report);


		$this->$report();
    }


	public function rep_registered_users(){
		$this->output_title("Registered Users");

		$res = $this->dbq("SELECT * FROM  `AccountUser`");
		$res_count = mysql_num_rows ( $res );

		echo "<br /> Num results: $res_count</br>";

	
	}


	
	// HTML Helpers

	public function output_title($report_name){
		echo "<h3>Building Report: $report_name</h3><hr />";
	}


	// DB Helper
	public function connect_db(){
		mysql_connect("198.211.105.160", "root", "!Naffets77") or die(mysql_error());
		mysql_select_db("ciborium_prod") or die(mysql_error());
	}

	public function dbq($query){
		return mysql_query($query);
	}

}


?>