<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

?>



<?php
if(!isset($_POST['submit'])){
?>
<html>
    <body>

    <form action="<?php echo $_SERVER;?>" method="post" enctype="multipart/form-data">
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
        
        $row = 1;
        if (($handle = fopen($_FILES["file"]["tmp_name"], "r")) !== FALSE) {
        
            $lineCount = 0;
        
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                //$num = count($data);
                //echo "<p> $num fields in line $row: <br /></p>\n";
                
                if($lineCount == 0){
                    echo "<h4> --- New Question ---- </h4>";
                }
                
                
                
                
                for ($c=0; $c < $num; $c++) {
                    echo $data[$c] . "<br />\n";
                }
                
                
                $row++;
            }
            fclose($handle);
        }
        else{
            echo " <br /><br />Error w/setting file handle";
        }
        
        
    } 
   

}
?>