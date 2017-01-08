<?php

    $reqId = $_GET["requestId"];

        $dbh = new PDO("mysql:host=localhost; dbname=omnidata_blob", "omnidata_blob", "Tango_32");
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "select imageType, receipt from receipts where id = ?";
        $query = $dbh->prepare(($sql));
        $query->execute(array($reqId));
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $array = $query->fetch();

        header("Content-type: " . $array['imageType']);
        echo $array['receipt'];

?>