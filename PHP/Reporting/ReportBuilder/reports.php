<?php




class ReportBuilder {
    




    public function build_report($report) {

		echo "Building Report: ". $report;

		$this->connect_db();


    }

	public function connect_db(){

		mysql_connect("198.211.105.160", "root", "Naffets77") or die(mysql_error());
		mysql_select_db("ciborium_prod") or die(mysql_error());
	
	}

}


?>