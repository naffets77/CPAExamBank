                                                                     
                                                                     
                                                                     
                                             
<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');
	include_once("../config.php");
	//openDBConnection(); 
	//include_once(library_configuration::$environment_librarypath."/database.php");
?>



<?php
if(!isset($_POST['submit'])){
?>
<html>
    <body>

    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data">
        <label for="file">Filename:</label>
        <input type="file" name="file" id="file"><br>
        <input type="submit" name="submit" value="Submit">
    </form>

    </body>
</html>

<?php

}
else{

    // We'll be doing some importing now

    if ($_FILES["file"]["error"] > 0)
    {
        echo "Error: " . $_FILES["file"]["error"] . "<br>";
    }
    else
    {
        echo "<h3>File Data </h3>";
        echo "Upload: " . $_FILES["file"]["name"] . "<br>";
        echo "Type: " . $_FILES["file"]["type"] . "<br>";
        echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
        echo "Stored in: " . $_FILES["file"]["tmp_name"];
        
        echo "<h3> Reading File </h3>";
        
        
        if (($handle = fopen($_FILES["file"]["tmp_name"], "r")) !== FALSE) {
            
            $row = 1;
            $lineCount = 0;
            $questionCount = 1;
            
            $question = null;
            $questions = array();
        
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                //echo "<p> $num fields in line $row: <br /></p>\n";
                
                if($lineCount == 0){
                    echo "<h4> --- New Question (" . $questionCount . ") ---- </h4>";
                    $question = array();
                }
                
                switch($lineCount){
                
                    case 0:
                    
                        echo "<br />Row $lineCount - Processing Question <br />";
                        
                        $questionText = "";
                        
                        $num = count($data);
                        
                        for ($c=0; $c < $num; $c++) {
                            $questionText .= "<p>{$data[$c]}</p>";
                        }
                        
                        $question['text'] = $questionText;
                        
                        $lineCount++;
                        break;
                        
                    case 1:
                    
                        echo "<br />Row $lineCount - Processing Answers<br />";
                        
                        $answers = array();
                        
                        for ($c=0; $c < $num; $c++) {
                            array_push($answers,$data[$c]);
                        }                        
                        
                        $question ['answers'] = $answers;
                        
                        $lineCount++;
                        break;    
                        
                    case 2:
                    
                        echo "<br />Row $lineCount - Processing Explanation<br />";
                        
                        $explanationText = "";
                        
                        $num = count($data);
                        
                        for ($c=0; $c < $num; $c++) {
                            $explanationText .= "<p>{$data[$c]}</p>";
                        }
                        
                        $question['explanation'] = $explanationText;
                        
                        $lineCount++;
                        break;
                    
                    case 3:
                    
                        echo "<br />Row $lineCount - Processing Meta Data<br />";
                        
                        $question['referenceID'] = $data[0];
                        $question['sectionType'] = $data[1];
						$question['answerIndex'] = (string)((int)$data[2] - 1);
                        $question['referenceImage'] = $data[3];
                        
                        array_push($questions, $question);
                        
                        $lineCount = 0;
                        $questionCount++;
                        break;
                
                }

                               
                $row++;
            }
            
            // End While
            
            echo "<h3> Finished Processing File</h3><br /> <div>Found $questionCount questions on $row  ... $row / $questionCount is " . ($lineCount / $questionCount) . " ... should be 4!</div>";
            
            echo "<h3> Dumping Questions...</h3>";
            
            echo "<pre style='font-size:10px;'>";
            
            var_dump($questions);
            
            echo "</pre>";
            
           
            fclose($handle);
            
            insertQuestions($questions);
        }
        else{
            echo " <br /><br />Error w/setting file handle";
        }
        
        
    } 
   

}




function insertQuestions($questions){
	global $GLOBAL_database;
	
    $num = count($questions);
    $mysqli = new mysqli("198.211.105.160","root","!Naffets77", $GLOBAL_database);
    //get SectionType enum; TODO: use enum_ class
	$SectionTypeResult = $mysqli->query("SELECT SectionType, SectionTypeId FROM SectionType");
	$SectionTypes = array();
	while($row = mysqli_fetch_assoc($SectionTypeResult)){
		$SectionTypes[$row["SectionType"]] = $row["SectionTypeId"];
	}
	echo "Current Modules and their ids: <br />";
	print_r($SectionTypes);

    for($i = 0; $i < $num ; $i++){
    
        $question = $questions[$i];
        $question['referenceID'] = isset($question['referenceID']) ? trim($question['referenceID']) : "";
		$question['sectionType'] = isset($question['sectionType']) ? strtoupper(trim($question['sectionType'])) : "";
		
        echo "<div> Inserting: {$question['referenceID']} </div>"; // This is 
		if(!empty($question['referenceID']) ){
			//check that QuestionClientId doesn't exist already in Question table
			$escapedQuestionClientId = $mysqli->real_escape_string($question['referenceID']);
			$QuestionClientIdResult = $mysqli->query("SELECT QuestionClientId FROM Question WHERE QuestionClientId = '".$escapedQuestionClientId."';");
			if(mysqli_num_rows($QuestionClientIdResult) == 0){
				
				//Check that the section type is valid
				if(array_key_exists($question['sectionType'], $SectionTypes)){
					$SectionTypeId = $SectionTypes[$question['sectionType']];
					
					//escape string the insert fields
					$escapedDisplayText = $mysqli->real_escape_string($question['text']);
					$escapedExplanation = $mysqli->real_escape_string($question['explanation']);
					$escapedQuestionClientImage = $mysqli->real_escape_string($question['referenceImage']);
					
					//perform the question insert
					$query = "INSERT INTO Question (QuestionId, QuestionClientId, QuestionTypeId, DisplayName, DisplayText, Explanation, SectionTypeId, Tags, QuestionClientImage, HasHTMLInName, HasHTMLInText, IsSamplable, IsApprovedForUse, IsActive, IsDeprecated, DateLastModified, LastModifiedBy, DateCreated, CreatedBy) 
								VALUES(DEFAULT, '".$escapedQuestionClientId."', 1, '', '".$escapedDisplayText."', '".$escapedExplanation."', ".$SectionTypeId.", '', '".$escapedQuestionClientImage."', 0, 1, 0, 0, 0, 0, CURRENT_TIMESTAMP, 'import.php', NOW(), 'import.php');";
					$mysqli->query($query);
					$QuestionId = $mysqli->insert_id;
					
					if($QuestionId > 0){
						//insert the answers
						$AnswerSortIndex = 1;
						foreach($question['answers'] as $key => $value){
							$IsCorrectAnswer = (string)$key == $question['answerIndex'] ? 1 : 0;
							$escapedDisplayAnswerText = $mysqli->real_escape_string(trim($value));
							if(!empty($escapedDisplayAnswerText)){
								$query = "INSERT INTO QuestionToAnswers VALUES(DEFAULT, '".$QuestionId."', ".$AnswerSortIndex.", '', '".$escapedDisplayAnswerText."', ".$IsCorrectAnswer.", 0, 1, CURRENT_TIMESTAMP, 'import.php', NOW(), 'import.php');";
								$mysqli->query($query);
							}
							$AnswerSortIndex += 1;
						}
					}
					else{
						echo " Question failed to insert.";
					}
					
					
				}
				else{
					echo "<p>Question's SectionType (trim/to upper) ".$question['sectionType']." was not valid</p>";
				}
			}
			else{
				echo "<p>Question's RefrenceId is already in the database</p>";
			}
			
		}
		else{
			echo "<p>Question's RefrenceId was not set or empty.</p>";
		}
    
    
    }
	//mysql::close();

}

// function openDBConnection(){

	// $mysqli = new mysqli("198.211.105.160","root","!Naffets77", $GLOBAL_database);  

    // if ($mysqli->connect_error) {
        // die('Connect Error (' . $mysqli->connect_errno . ') '
                // . $mysqli->connect_error);
    // }

	// return $mysql;
// }

// function closeDBConnection(){
	// //mysqli::close();
// }

?>