<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');


$command = "/usr/bin/git pull https://naffets77:!Naffets77@github.com/naffets77/VaheCPA 2>&$




//$command = "git pull hub";
// Change to the correct directory

if(chdir("/srv/www/tprep/dev/")){

    echo "We're working in dev: ".getcwd(). "</br>";
   echo "Trying Command :$command<br />";
     if(system($command)){
         echo "<br /><br />Git request success:<br /><br /> ";

        system("cp -r /srv/www/tprep/dev/Backend/Ciborium/Library/* /srv/lib/TPrepLib/dev/"$
        system("cp -r /srv/www/tprep/dev/Backend/Ciborium/Service/* /srv/lib/TPrepServices/$
        system("cp -r /srv/www/tprep/dev/Backend/Library/* /srv/lib/_master/dev/");
    }
    else{
     echo "<br /><br />Git Request Failed";
    }
}
else{
   echo "Failed Changing Directory";
}


?>


