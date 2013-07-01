<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

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
            $questions = new array();
        
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                //echo "<p> $num fields in line $row: <br /></p>\n";
                
                if($lineCount == 0){
                    echo "<h4> --- New Question (" . $questionCount . ") ---- </h4>";
                    $question = new array();
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
                        
                        $question['id'] = $data[0];
                        $question['sectionType'] = $data[1];
                        $question['referenceImage'] = $data[2];
                        
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
        }
        else{
            echo " <br /><br />Error w/setting file handle";
        }
        
        
    } 
   

}
?>