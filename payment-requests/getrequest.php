<?php

        $reqId = $_GET["requestId"];

        try {
            $dbh = new PDO("mysql:host=localhost; dbname=omnidata_blob", "omnidata_blob", "Tango_32");
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT * FROM tbl_request WHERE id = ?";
            $query = $dbh->prepare($sql);
            $query->execute(array($reqId));
            $query->setFetchMode(PDO::FETCH_ASSOC);

            $array = $query->fetch();
            //header("Content-type: " . $array['imageType']);
            //print_r($array) ;
            echo "<br/>";
            echo "Id: " . $array["id"] . "<br/>";
            echo "Request Date: " . $array["reqDate"] . "<br/>";
            echo "Requestor: " . $array["reqFirstName"] . "&nbsp;" . $array["reqLastName"] . "<br/><hr/><br/>";
            echo  "Pay To: " . $array["payTo"] . "<br/>";
            echo  "Address: " . $array["payToAddress"] . "<br/>";
            echo  "City: " . $array["payToCity"] . " State: " . $array["payToState"] . " Zip: " . $array["payToZip"] . "<br/>";
            echo "Amount: " . $array["payToAmt"] . "<br/><br/>" ;

            if ($array["receipt"] != null) {
                echo "<a href='showreceipt.php?id=" . $reqId . "' target='_blank'>Click here to display receipt</a>";
            }
            else {
                echo "No receipts included";
            }





        }
        catch (Exception $ex) {
            echo $ex->getMessage();
        }



?>