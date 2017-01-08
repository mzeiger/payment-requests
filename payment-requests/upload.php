<?php

    require 'PHPMailer/PHPMailerAutoload.php';

    $firstName = $_POST["firstname"];
    $lastName = $_POST["lastname"];
    $reqEmail = isset($_POST["reqEmail"]) ? $_POST["reqEmail"] : "";
    $vendorOrReimburse = isset($_POST["vendorOrReimburse"]) ? $_POST["vendorOrReimburse"] : "";
    //$vendorOrReimburse =$_POST["vendorOrReimburse"];
    if ($vendorOrReimburse == "vendor")
        $checkOrCredit = "check";
    else
        $checkOrCredit = isset($_POST["checkOrCredit"]) ? $_POST["checkOrCredit"] : "";
    $payToName = $_POST["payto"];
    if ($vendorOrReimburse == "reimburse")
        $reoccurringVendor = "";
    else
        $reoccurringVendor = isset($_POST["reoccurringVendor"]) ? $_POST["reoccurringVendor"] : "";
    $payToAddress = $_POST["paytoAddress"];
    $payToCity = $_POST["paytoCity"];
    $payToState = $_POST['paytoState'];
    $payToZip = $_POST["paytoZip"];
    $payToEmail =  isset($_POST["paytoEmail"]) ?  $_POST["paytoEmail"] : "" ;
    $pmtAmount = $_POST["pmtAmount"];
    $projectName = isset($_POST["projectName"]) ? $_POST["projectName"] : "" ;
    $glNumber = isset($_POST["glNumber"]) ? $_POST["glNumber"] : "";
    $reqComment = isset($_POST["reqComment"]) ? $_POST["reqComment"] : "" ;
    $pmName = isset($_POST["pmName"]) ? $_POST["pmName"] : "";  // pmName = project manager name (called "Approver" on web page)
    $pmEmail = isset($_POST["pmEmail"]) ? $_POST["pmEmail"] : "";


    if ($_FILES["receipts"]["error"] == 4) {
        echo "No file uploaded<br/>";
        $file_uploaded = false;
        $receipt = null;
        $fileType = "";
        $fileSize = 0;
        $fileName = "";
    }

    try {
        $dbh = new PDO("mysql:host=localhost; dbname=monumen8_pmtrqst", "monumen8_pmtrqst", "Tango_32");
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO tbl_request (reqDate, reqFirstName, reqLastName, reqEmail, vendor_reimburse, checkOrCredit,
                                         payTo, reoccurringVendor, payToAddress, payToCity, payToState, payToZip, payToEmail, payToAmt,
                                         projectName, glNumber, reqComment)
                                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $query = $dbh->prepare($sql);

        $dt = date("Y-m-d", strtotime("now"));

        $arrayForInsert = array($dt, $firstName, $lastName, $reqEmail, $vendorOrReimburse, $checkOrCredit,
                              $payToName, $reoccurringVendor, $payToAddress, $payToCity, $payToState, $payToZip, $payToEmail,
                              $pmtAmount, $projectName, $glNumber, $reqComment);
        $query->execute($arrayForInsert );
        unset($query);

        $sql = "SELECT LAST_INSERT_ID() AS reqId";
        $query = $dbh->prepare($sql);
        $query->execute();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $array = $query->fetch();
        $reqId = $array['reqId'] ;

        $file_uploaded = false;
        if ((strlen($_FILES["receipts"]["name"][0]) > 0) || (strlen($_FILES["receipts"]["name"][1]) > 0) || (strlen($_FILES["receipts"]["name"][2]) > 0)) {
            $file_uploaded = true;
            $sql = "INSERT INTO receipts (id, fileName, imageSize, imageType, receipt) VALUES (?, ?, ?, ?, ?)";
            $query = $dbh->prepare($sql);
            foreach ($_FILES["receipts"]["error"] as $key =>$error) {
                if ($error == UPLOAD_ERR_OK) {
                    $fileName = $_FILES["receipts"]["name"][$key];
                    $fileSize = $_FILES["receipts"]["size"][$key];
                    $fileType = $_FILES["receipts"]["type"][$key];
                    move_uploaded_file($_FILES["receipts"]["tmp_name"][$key], "./" . $fileName);
                    $file = fopen("./" .  $fileName, 'rb');
                    $query->bindParam(1, $reqId);
                    $query->bindParam(2, $fileName);
                    $query->bindParam(3, $fileSize);
                    $query->bindParam(4, $fileType);
                    $query->bindParam(5, $file, PDO::PARAM_LOB);
                    $query->execute();
                    fclose($file);
                    //printf("Filename = %s   File Type = %s   File size = %s<br/>", $fileName, $fileType, $fileSize);
                    unlink("./" .  $fileName);
                }
            }
        }

        $sql = "INSERT INTO routing (id, approveDate, fullname, email, approved) VALUES (?, ?, ?, ?, ?)";
        $query = $dbh->prepare($sql);
        $query->execute(array($reqId, $dt, $pmName, $pmEmail, 0));

        echo "Your request has been submitted on " . $dt . "<br/>";
        echo "Your request Id is <strong>" . $reqId . "</strong><br/>" ;

        sendEmail($dbh, $reqId, $arrayForInsert, $file_uploaded, $pmName, $pmEmail, $vendorOrReimburse, $firstName . " " . $lastName );
        $dbh = null;
    }
    catch (Exception $ex) {
        echo $ex->getMessage();
        $dbh = null;
    }

   function sendEmail($dbh, $reqId, $aI, $file_uploaded, $pmName, $pmEmail, $vendorOrReimburse, $fullName)
    {
       /* $arrayForInsert ($aI) = array($dt - 0, $firstName - 1, $lastName - 2, $reqEmail - 3, $vendorOrReimburse - 4, $checkOrCredit - 5,
                      $payToName - 6, $reoccurringVendor - 7, $payToAddress - 8, $payToCity - 9, $payToState - 10, $payToZip - 11, $payToEmail - 12,
                      $pmtAmount - 13, $projectName - 14, $glNumber - 15, $reqComment - 16);  */

        $br = "<br/>";
        $dt = (new DateTime($aI[0]))->format("D M d, Y");
        $subject = ($vendorOrReimburse == "vendor") ? "Request for vendor payment approval from " . $fullName : "Request for member reimbursement from " . $fullName ;

        $from = sprintf("Payment_Request@monumenthillkiwanis.org", $fullName);

        $msg = $subject . $br;
        $msg .= sprintf("Requestor's Email: <a href='' style='text-decoration: none;'>%s</a><br/>", $aI[3]);
        $msg .= sprintf("Request Id: %s<br/>", $reqId);
        if ($aI[4] == "reimburse") {
            $msg .= ($aI[5] == "check") ? "Requester would like to be paid by check<br/>" : "Requester would like his/her account credited<br/>";
        }
        $msg .= "Request submitted on " . $aI[0] . $br; //$dt
        $cityStateZip =  $aI[9] . " " .  $aI[10] . " " . $aI[11];
        $msg .= sprintf("<u>Pay To:</u><br/><ul style='list-style: none;'><li>%s</li><li>%s</li><li>%s</li></ul><br/>",
                                                                                                           $aI[6], $aI[8], $cityStateZip );
        if ($vendorOrReimburse == "vendor")
            $msg .= sprintf("Recurring Vendor: %s<br/>", ucwords($aI[7]));
        $msg .= "Payee Email: <a href='' style='text-decoration: none;'>" . $aI[12] . "</a>" . $br;
        $fmtAmt =  number_format ( $aI[13], 2, "." , "," );
        $msg .= sprintf("Amount: %s <br/>", $fmtAmt);
        $msg .= sprintf("Project: %s<br/>Account Number: %s<br/>", $aI[14], $aI[15]) ;
        $msg .= sprintf("<u>Reason for request</u><br/>%s<br/>", $aI[16]);
        $msg .= $br . $br;

        $msg .= "Project Manager: After approval <span style='font-weight: bold;'>FORWARD</span> this email to <span style='font-weight: bold;'>ALL</span> of the following assistant treasurers listed below:<br/>";
        $myfile = fopen("treasurersdirectory.txt", "r");
        // Output one line until end-of-file
        $msg .= "<table>";
        while(!feof($myfile)) {
            $str1 = fgets($myfile);
            $str = split(':', $str1);
            /* if (strtolower($str[0]) == "treasurer") {
                $treasurerEmail = $str[2];
                $treasurerName = $str[1];
            }    */
            if (strtolower($str[0]) == "bookkeeper") {
                $bookkeeperEmail =  $str[2];
                $bookkeeperName = $str[1];
            }
            else if (strtolower($str[0]) == "assistanttreasurer" || strtolower($str[0]) == "treasurer") {
                 $msg .= sprintf("<tr><td>%s:</td><td>%s</td><td><a href='' style='text-decoration: none;'>%s</a></td></tr>", $str[0], $str[1], $str[2]);
            }
        }
        fclose($myfile);
        $msg .= "</table>";
        $msg .= "<br/>";
        $msg .= "Assistant Treasurers: After approval <span style='font-weight: bold;'>FORWARD</span> this email to the Bookkeeper and copy <span style='font-weight: bold;'>ALL</span> the other assistant treasurers listed above.<br/>";
        // Output one line until end-of-file
        $msg .= "<table>";

        $msg .= sprintf("<tr><td>%s</td><td>%s</td><td><a href='' style='text-decoration: none;'>%s</a></td></tr>", "Bookkeeper", $bookkeeperName, $bookkeeperEmail);

        $msg .= "</table><br/>";

        If (!$file_uploaded) {
            $msg .= "There are no attachments";
        }
        else {
            $msg .= "Documentation has been attached. ";
        }

        $mail = new PHPMailer;
        $sql = "select * from receipts where id = ?";
        $query = $dbh->prepare($sql);
                $query = $dbh->prepare(($sql));
        $query->execute(array($reqId));
        $query->setFetchMode(PDO::FETCH_ASSOC);
        while ($array = $query->fetch()) {
            $mail->addStringAttachment($array["receipt"], $array["fileName"]);
        }
       // $mail->setFrom($from, $fullName);
        $mail->addAddress($pmEmail, $pmName);
        $mail->addCC($bookkeeperEmail, $bookkeeperName);
       // $mail->addCC($treasurerEmail, $treasurerName);
        $mail->Subject = $subject;
        $mail->Body = $msg;

        $mail->isHTML(true);

        if (!$mail->send()) {
            echo "Email was not sent: " . $mail->ErrorInfo;
        }
        else
            echo "Email sent successfully";

        //$retval = mail($to, $subject, $msg, $header);
    }

?>