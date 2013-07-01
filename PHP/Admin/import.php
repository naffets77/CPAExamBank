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
        
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                //echo "<p> $num fields in line $row: <br /></p>\n";
                
                if($lineCount == 0){
                    echo "<h4> --- New Question (" + $questionCount + ") ---- </h4>";
                }
                
                switch($lineCount){
                
                    case 0:
                        echo "<br />Processing Question <br />";
                        break;
                        
                    case 1:
                    break;
                        echo "<br />Processing Answers<br />";
                        break;
                        
                    case 2:
                        echo "<br />Processing Explanation<br />";
                        break;
                    
                    case 3:
                        echo "<br />Processing Meta Data<br />";
                        $lineCount = 0;
                        $questionCount++;
                        break;
                
                }
                /*
                $num = count($data);
                for ($c=0; $c < $num; $c++) {
                    echo $data[$c] . "<br />\n";
                }
                */
                
                $lineCount ++;
                $row++;
            }
            
            // End While
            
            echo "<h3> Finished Processing File</h3>,<br /> <div>Found $questionCount questions on $lineCount  ... $lineCount / $questionCount is " + ($lineCount / $questionCount) + " ... should be 4!</div>";
            
            
            fclose($handle);
        }
        else{
            echo " <br /><br />Error w/setting file handle";
        }
        
        
    } 
   

}
?>