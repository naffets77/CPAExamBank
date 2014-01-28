<?php




class ReportBuilder {
    




    public function build_report($report) {

		echo "<h3>Building Report: $report</h3> <br /><hr />";

		$this->connect_db();
		
		//call_user_func($report);


		$this->$report();
    }


	public function rep_registered_users(){
		echo "Showing registered users";
	
	}


	public function connect_db(){

		mysql_connect("198.211.105.160", "root", "!Naffets77") or die(mysql_error());
		mysql_select_db("ciborium_prod") or die(mysql_error());
	
	}

}


?>