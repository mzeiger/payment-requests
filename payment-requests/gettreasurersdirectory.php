<?php

    $myFile = fopen("treasurersdirectory.txt", "r") or die("Can't open file");

   // $table = "<table id='tblTreasurersDirectory'><tr><th>Title</th><th>Name</th><th>Email</th></tr>";
    $table = "<tr><th>Title</th><th>Name</th><th>Email</th></tr>";
    while (!feof($myFile)) {
        $line = fgets($myFile);
        $str = split(":", $line);
        if (count($str) == 3) {
            if (strtolower($str[0]) == "assistanttreasurer") {
                $str[0] ="Asst Treas";
            }
            $table .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>", $str[0], $str[1], $str[2]);
        }
    }
    fclose($myFile);
   // $table .= "</table>";
    echo $table;

?>