<?php

$usrID = $_SESSION['USRID'];

$rowID1 = isset($_POST['rowID']) ? $_POST['rowID'] : "-1";
$tblnm1 = isset($_POST['tblnm']) ? $_POST['tblnm'] : "";
$id_col_nm1 = isset($_POST['id_col_nm']) ? $_POST['id_col_nm'] : "";
$qryNm = isset($_POST['q']) ? $_POST['q'] : "";

function get_Gnrl_Rec_Hstry($rowID, $tblnm, $id_col_nm) {
    $strSQL = "SELECT a.created_by, 
to_char(to_timestamp(a.creation_date,'YYYY-MM-DD HH24:MI:SS'),'DD-Mon-YYYY HH24:MI:SS'), 
a.last_update_by, 
to_char(to_timestamp(a.last_update_date,'YYYY-MM-DD HH24:MI:SS'),'DD-Mon-YYYY HH24:MI:SS') 
FROM " . $tblnm . " a WHERE(a." . $id_col_nm . " = " . $rowID . ")";
    $fnl_str = "";
    $result = executeSQLNoParams($strSQL);
    while ($row = loc_db_fetch_array($result)) {
        $fnl_str = "<p> CREATED BY: " . getUserName($row[0]) .
                "<br/>CREATION DATE: " . $row[1] . "<br/>LAST UPDATE BY:" .
                getUserName($row[2]) .
                "<br/>LAST UPDATE DATE: " . $row[3] . "</p>";
        return $fnl_str;
    }
    return "";
}

function get_Gnrl_Create_Hstry($rowID, $tblnm, $id_col_nm) {
    $strSQL = "SELECT a.created_by, 
to_char(to_timestamp(a.creation_date,'YYYY-MM-DD HH24:MI:SS'),'DD-Mon-YYYY HH24:MI:SS') " .
            "FROM " . $tblnm . " a WHERE(a." . $id_col_nm . " = " . $rowID . ")";
    $fnl_str = "";
    $result = executeSQLNoParams($strSQL);
    while ($row = loc_db_fetch_array($result)) {
        $fnl_str = "<p> CREATED BY: " . getUserName($row[0]) .
                "<br/>CREATION DATE: " . $row[1] . "</p>";
        return $fnl_str;
    }
    return "";
}

function sendPswdResetLink($UNM) {
    //echo "Inside sendPswdResetLink!$UNM";
    global $app_name;
    global $app_url;
    global $admin_email;
    global $smplTokenWord1;

    if ($UNM == "") {
        echo "Please select a User First!";
        return "";
    }
    echo "<div style=\"background-color:#e3e3e3;border: 1px solid #999;padding:10px;\" class=\"rho-postcontent rho-postcontent-0 clearfix\"> ";
    $errMsg = "";
    checkNCreateUser($UNM, $errMsg);
    echo $errMsg;
    $inUsrID = getUserID($UNM);
    if ($inUsrID > 0) {
        $numChars = rand(10, 25);
        $numChars1 = rand(10, 25);
        $nwTxt = getRandomTxt($numChars);
        $nwTxt1 = getRandomTxt($numChars1);
        $expDate = getDB_Date_TmIntvlAdd('12 hours');
        $encrptdUrl = encrypt1("" . $nwTxt . "|$numChars|$expDate|changepassword|$UNM|$numChars1|" . $nwTxt1 . "", $smplTokenWord1);
        //storeOldPassword($inUsrID, getUserPswd($UNM));
        //changeUserPswd($inUsrID, $nwPswd, 'TRUE', 1);
        /* "<br/>"
          . $app_url . "?g=" . $encrptdUrl . */
        $prsnID = getUserPrsnID($UNM);
        $to = getPrsnEmail($prsnID);
        $nameto = getPrsnFullNm($prsnID);
        $subject = $app_name . " PASSWORD RESET LINK";
        $message = "<p style=\"font-family: Calibri;font-size:18px;\">Hello $nameto <br/><br/>"
                . "A password reset was requested for your account with the Username: $UNM" .
                "<br/>If this request was made by you then Please <br/><a href=\""
                . $app_url . "?g=" . $encrptdUrl . "\">Click on this link to RESET it!</a>"
                . "<br/><br/>"
                . "If on the other hand you didn't request for this, please ignore this message!<br/><br/>"
                . "Thank you!</p>";
        $errMsg = "";
        $isSent = sendEMail(trim(str_replace(";", ",", $to), ","), $nameto, $subject, $message, $errMsg, "", $admin_email, "");
        if ($isSent === TRUE) {
            echo "<span style=\"color:green;\">Email Sent Successfully! Please Check your Registered E-mail for Further Instructions!</span>";
        } else {
            echo "<span style=\"color:red;\">$errMsg</span>";
        }
    } else {
        echo "<span style=\"color:red;\">Please enter a Valid User Name or ID No.!</span>";
    }
    echo "</div>";
}

function changePswdAuto($UNM) {
    global $dfltPrvldgs;
    global $app_name;
    if (test_prmssns($dfltPrvldgs[17], 'System Administration') == false) {
        echo "You don't have permission to perform" .
        " this action!\nContact your System Administrator!";
        return "";
    }
    if ($UNM == "") {
        echo "Please select a User First!";
        return "";
    }
    $nwPswd = getRandomPswd();
    echo"<div style=\"background-color:#e3e3e3;border: 1px solid #999;padding:10px;\" class=\"rho-postcontent rho-postcontent-0 clearfix\"> ";

    $prsnID = getUserPrsnID($UNM);
    $to = getPrsnEmail($prsnID);
    $nameto = getPrsnFullNm($prsnID);
    $subject = $app_name . " PASSWORD CHANGE";
    $message = "Hello $nameto <br/><br/>Your Login Details have been changed as follows:<br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Username: $UNM" .
            "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Password: " . $nwPswd .
            "<br/>Please login immediately to change it!<br/>Thank you!";
    $errMsg = "";
    sendEMail(trim(str_replace(";", ",", $to), ","), $nameto, $subject, $message, $errMsg, "", "", "");
    if ($errMsg == "Mail sent successfully!") {
        storeOldPassword(getUserID($UNM), getUserPswd($UNM));
        changeUserPswd(getUserID($UNM), $nwPswd, 'TRUE', 1);
    }
    echo $errMsg;
    echo "</div>";
}

function getDivGrpTyp($divID) {
    $sqlStr = "select gst.get_pssbl_val(div_typ_id) from org.org_divs_groups where 
        div_id=$divID";
    $result = executeSQLNoParams($sqlStr);
    while ($row = loc_db_fetch_array($result)) {
        return $row[0];
    }
    return "";
}

function getLogMsg($msgid, $logTblNm) {
    $sqlStr = "select log_messages from $logTblNm where msg_id = $msgid";
    $result = executeSQLNoParams($sqlStr);
    while ($row = loc_db_fetch_array($result)) {
        return "$row[0]";
    }
    return "";
}

function getQualfyngAddress($grpTyp, $grpNm, $grpID, $wrkplcID, $wrkplcSiteID, $msgTyp, &$errMsg) {
    $rsltAddresses = "";

    if ($grpTyp != "Everyone" && $grpTyp != "Currently Selected Person") {
        if ($grpID == "-1" || $grpID == "") {
            $errMsg = "Please select a Group First!";
            return "";
        }
    }

    if ($msgTyp == "") {
        $errMsg = "Please select a Message Type!";
        return "";
    }
    //$curid = getOrgFuncCurID($_SESSION['ORG_ID']);
    if ($grpTyp == "Companies/Institutions") {
        $cstmrIDs = explode(";", $grpID);
        $rsltAddresses = "";
        for ($a = 0; $a < count($cstmrIDs); $a++) {
            //this.prsnID = this.prsnIDs[a];
            if ($msgTyp == "Email") {
                $rsltAddresses .= str_replace(",", ";", getCstmrSpplrEmails($cstmrIDs[$a])) . ";";
            } else if ($msgTyp == "SMS") {
                $rsltAddresses .= str_replace(",", ";", getCstmrSpplrMobiles($cstmrIDs[$a])) . ";";
            } else {
                $rsltAddresses .= str_replace(",", ";", $cstmrIDs[$a]) . ";";
            }
        }
    } else {
        $prsnIDs = getPrsnsInvolved($wrkplcID, $wrkplcSiteID, $grpTyp, $grpNm, $grpID);
        //int rwidx = 0;
        //var_dump($prsnIDs);
        $rsltAddresses = "";
        for ($a = 0; $a < count($prsnIDs); $a++) {
            $prsnID = $prsnIDs[$a];
            if ($msgTyp == "Email") {
                $rsltAddresses .= str_replace(",", ";", getPrsnEmail($prsnID)) . ";";
            } else if ($msgTyp == "SMS") {
                $rsltAddresses .= str_replace(",", ";", getPrsnMobile($prsnID)) . ";";
            } else {
                $rsltAddresses .= $prsnIDs[$a] . ";";
            }

            return $rsltAddresses;
        }
    }
}

function getPrsnsInvolved($cstmrID, $siteID, $grpTyp, $grpNm, $grpID) {
    $dateStr = getDB_Date_time();
    $extrWhr = "";
    if ($cstmrID > 0) {
        $extrWhr .= " and (Select distinct z.lnkd_firm_org_id From prs.prsn_names_nos z where z.person_id=a.person_id)=" . $cstmrID;
    }
    if ($siteID > 0) {
        $extrWhr .= " and (Select distinct z.lnkd_firm_site_id From prs.prsn_names_nos z where z.person_id=a.person_id)=" . $siteID;
    }
    $grpSQL = "";
    if ($grpTyp == "Divisions/Groups") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_divs_groups a Where ((a.div_id = " .
                $grpID . ") and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Grade") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_grades a Where ((a.grade_id = " .
                $grpID . ") and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Job") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_jobs a Where ((a.job_id = " .
                $grpID . ") and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Position") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_positions a Where ((a.position_id = " .
                $grpID . ") and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Site/Location") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_locations a Where ((a.location_id = " .
                $grpID . ") and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Person Type") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_prsntyps a, prs.prsn_names_nos b " .
                "Where ((a.person_id = b.person_id) and (b.org_id = " . $_SESSION['ORG_ID'] . ") and (a.prsn_type = '" .
                loc_db_escape_string($grpNm) . "') and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Working Hour Type") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_work_id a Where ((a.work_hour_id = " .
                $grpID . ") and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Gathering Type") {
        $grpSQL = "Select distinct a.person_id From pasn.prsn_gathering_typs a Where ((a.gatherng_typ_id = " .
                $grpID . ") and (to_timestamp('" . $dateStr .
                "','YYYY-MM-DD HH24:MI:SS') between to_timestamp(a.valid_start_date|| ' 00:00:00','YYYY-MM-DD HH24:MI:SS') " .
                "AND to_timestamp(a.valid_end_date || ' 23:59:59','YYYY-MM-DD HH24:MI:SS'))" . $extrWhr . ") ORDER BY a.person_id";
    } else if ($grpTyp == "Everyone") {
        $grpSQL = "Select distinct a.person_id From prs.prsn_names_nos a Where ((a.org_id = "
                . $_SESSION['ORG_ID'] . ")" . $extrWhr . ") ORDER BY a.person_id";
    } else {
        $grpSQL = "Select distinct a.person_id From prs.prsn_names_nos a Where ((a.person_id = " . $grpID . ")" . $extrWhr . ") ORDER BY a.person_id";
    }

    $result = executeSQLNoParams($grpSQL);

    $prsnIDs = array(-1);
    $i = 0;
    if (loc_db_num_rows($result) > 0) {
        while ($row = loc_db_fetch_array($result)) {
            $prsnIDs[$i] = $row[0];
            $i++;
        }
    }

    return $prsnIDs;
}

if ($usrID > 0) {
    if ($qryNm == "Record History") {
        echo get_Gnrl_Rec_Hstry($rowID1, $tblnm1, $id_col_nm1);
    } else if ($qryNm == "Record History1") {
        echo get_Gnrl_Create_Hstry($rowID1, $tblnm1, $id_col_nm1);
    } else if ($qryNm == "Check Session") {
        echo 1;
    } else if ($qryNm == "Download") {
        $dwnldfile = isset($_POST['fnm']) ? $_POST['fnm'] : "";
//Go to the FTP Folder
//Copy the requested file to dwnlds folder using a different FileName
//Send this new fileNm in a new URL
        echo "dwnlds/dwnlds.php?q=" . $dwnldfile;
    } else if ($qryNm == "Get Addresses") {
        $sendIndvdl = isset($_POST['val1']) ? cleanInputData($_POST['val1']) : TRUE;
        $msgTyp = isset($_POST['val2']) ? cleanInputData($_POST['val2']) : 'Email';
        $grpTyp = isset($_POST['val3']) ? cleanInputData($_POST['val3']) : 'Currently Selected Person';
        $grpID = isset($_POST['val4']) ? cleanInputData($_POST['val4']) : -1;
        $grpNm = isset($_POST['val7']) ? cleanInputData($_POST['val7']) : '';
        $wrkPlcID = isset($_POST['val5']) ? cleanInputData($_POST['val5']) : -1;
        $siteID = isset($_POST['val6']) ? cleanInputData($_POST['val6']) : -1;
        $errMsg = "";
        if ($grpTyp == "Currently Selected Person") {
            $grpID = getPersonID($grpID);
        }
        $addrs = getQualfyngAddress($grpTyp, $grpNm, $grpID, $wrkPlcID, $siteID, $msgTyp, $errMsg);
        echo ($errMsg == "") ? $addrs : $errMsg;
    } else if ($qryNm == "Send Message") {
        //var_dump($_POST);
        set_time_limit(3000);
        $sendIndvdl = isset($_POST['sendIndvdl']) ? cleanInputData($_POST['sendIndvdl']) : TRUE;
        $msgTyp = isset($_POST['msgTypeCombo']) ? cleanInputData($_POST['msgTypeCombo']) : 'Email';
        $toAddresses = isset($_POST['toAddresses']) ? cleanInputData($_POST['toAddresses']) : "";
        $ccAddresses = isset($_POST['ccAddresses']) ? cleanInputData($_POST['ccAddresses']) : "";
        $bccAddresses = isset($_POST['bccAddresses']) ? cleanInputData($_POST['bccAddresses']) : "";
        $subjectDetails = isset($_POST['subjectDetails']) ? cleanInputData($_POST['subjectDetails']) : "";
        $attachments = isset($_POST['attachments']) ? cleanInputData($_POST['attachments']) : "";
        $messageBody = isset($_POST['msgHtml']) ? cleanInputData($_POST['msgHtml']) : "";
        $messageBody = urldecode($messageBody);
        //print_r($messageBody);
        $toAddresses = trim(str_replace("\r\n", "", str_replace(",", ";", $toAddresses)), ";");
        $ccAddresses = trim(str_replace("\r\n", "", str_replace(",", ";", $ccAddresses)), ";");
        $bccAddresses = trim(str_replace("\r\n", "", str_replace(",", ";", $bccAddresses)), ";");
        $attachments = trim(str_replace("\r\n", "", str_replace(",", ";", $attachments)), ";");

        if ($msgTyp == "SMS") {
            $toAddresses = $toAddresses . ";" . $ccAddresses . ";" . $bccAddresses;
        }

        $toEmails = explode(";", $toAddresses);

        $errMsg = "";
        $cntrnLmt = 0;
        $mailLst = "";
        $emlRes = false;
        $failedMails = "";
        //string errMsg = "";
        for ($i = 0; $i < count($toEmails); $i++) {
            if ($cntrnLmt == 0) {
                $mailLst = "";
            }
            $mailLst .= $toEmails[$i] . ",";

            $cntrnLmt++;
            if ($cntrnLmt == 50 || $i == count($toEmails) - 1 || $sendIndvdl == true || $msgTyp != "Email") {
                if ($msgTyp == "Email") {
                    $emlRes = sendEmail(
                            trim($mailLst, ","), trim($mailLst, ","), $subjectDetails, $messageBody, $errMsg, $ccAddresses, $bccAddresses, $attachments);
                } else if ($msgTyp == "SMS") {
                    $emlRes = sendSMS(cleanOutputData($messageBody), trim($mailLst, ","), $errMsg);
                } else {
                    
                }
                if ($emlRes == false) {
                    $failedMails .= trim($mailLst, ",") . ";";
                }
                $cntrnLmt = 0;
            }
        }
        if ($failedMails == "") {
            //cmnCde.showMsg("Message Successfully Sent to all Recipients!", 3);
            print_r(json_encode(array(
                'success' => true,
                'message' => 'Sent Successfully',
                'data' => array('src' => 'Message Successfully Sent to all Recipients!'),
                'total' => '1',
                'errors' => ''
            )));
        } else {
            //cmnCde.showSQLNoPermsn("Messages to some Recipients Failed!\r\n" + errMsg);
            print_r(json_encode(array(
                'success' => false,
                'message' => 'Error',
                'data' => '',
                'total' => '0',
                'errors' => 'Messages to some Recipients Failed!<br/>' . $errMsg
            )));
        }
    } else if ($qryNm == "Send Feedback") {
        //var_dump($_POST);
        set_time_limit(3000);
        $sendIndvdl = TRUE;
        $msgTyp = 'Email';
        $UNM = $_SESSION['USRID'];
        $prsnID = getUserPrsnID($UNM);
        //$from = getPrsnEmail($prsnID);
        $namefrom = getPrsnFullNm($prsnID);
        $toAddresses = $admin_email;
        $ccAddresses = isset($_POST['fdbckCcAddresses']) ? cleanInputData($_POST['fdbckCcAddresses']) : "";
        $bccAddresses = "";
        $subjectDetails = isset($_POST['fdbckSubjectDetails']) ? cleanInputData($_POST['fdbckSubjectDetails']) : "";
        $attachments = isset($_POST['fdbckAttachments']) ? cleanInputData($_POST['fdbckAttachments']) : "";
        $messageBody = isset($_POST['msgHtml']) ? cleanInputData($_POST['msgHtml']) : "";
        $messageBody = urldecode($messageBody);
        //print_r($messageBody);
        $toAddresses = trim(str_replace("\r\n", "", str_replace(",", ";", $toAddresses)), ";");
        $ccAddresses = trim(str_replace("\r\n", "", str_replace(",", ";", $ccAddresses)), ";");
        $bccAddresses = trim(str_replace("\r\n", "", str_replace(",", ";", $bccAddresses)), ";");
        $attachments = trim(str_replace("\r\n", "", str_replace(",", ";", $attachments)), ";");

        if ($msgTyp == "SMS") {
            $toAddresses = $toAddresses . ";" . $ccAddresses . ";" . $bccAddresses;
        }

        $toEmails = explode(";", $toAddresses);

        $errMsg = "";
        $cntrnLmt = 0;
        $mailLst = "";
        $emlRes = false;
        $failedMails = "";
        //string errMsg = "";
        for ($i = 0; $i < count($toEmails); $i++) {
            if ($cntrnLmt == 0) {
                $mailLst = "";
            }
            $mailLst .= $toEmails[$i] . ",";

            $cntrnLmt++;
            if ($cntrnLmt == 50 || $i == count($toEmails) - 1 || $sendIndvdl == true || $msgTyp != "Email") {
                if ($msgTyp == "Email") {
                    $emlRes = sendEmail(
                            trim($mailLst, ","), trim($mailLst, ","), $subjectDetails, $messageBody, $errMsg, $ccAddresses, $bccAddresses, $attachments, $namefrom);
                } else if ($msgTyp == "SMS") {
                    $emlRes = sendSMS(cleanOutputData($messageBody), trim($mailLst, ","), $errMsg);
                } else {
                    
                }
                if ($emlRes == false) {
                    $failedMails .= trim($mailLst, ",") . ";";
                }
                $cntrnLmt = 0;
            }
        }
        if ($failedMails == "") {
            //cmnCde.showMsg("Message Successfully Sent to all Recipients!", 3);
            print_r(json_encode(array(
                'success' => true,
                'message' => 'Sent Successfully',
                'data' => array('src' => 'Message Successfully Sent to all Recipients!'),
                'total' => '1',
                'errors' => ''
            )));
        } else {
            //cmnCde.showSQLNoPermsn("Messages to some Recipients Failed!\r\n" + errMsg);
            print_r(json_encode(array(
                'success' => false,
                'message' => 'Error',
                'data' => '',
                'total' => '0',
                'errors' => 'Messages to some Recipients Failed!<br/>' . $errMsg
            )));
        }
    } else if ($qryNm == "DELETE_ARTICLE") {
        if (test_prmssns("Delete Extra Info Labels", "System Administration")) {
            $in_val = isset($_POST['appID']) ? $_POST['appID'] : "";
            $delSQL = "DELETE FROM self.self_articles WHERE article_id = " . $in_val;
            $affctd = execUpdtInsSQL($delSQL);
            echo "<span style=\"color:green;\">" . $affctd . " Notice(s) Deleted Successfully!</span>";
        } else {
            echo "<span style=\"color:red;\">Sorry you don't have Permission to do this!</span>";
        }
    } else if ($qryNm == "Users Full Name") {
        $in_val = isset($_POST['in_val']) ? $_POST['in_val'] : "";
        $uprID = getUserPrsnID($in_val);
        if ($uprID <= 0) {
            $uprID = getPersonID($in_val);
        }
        echo getPrsnFullNm($uprID) . " (" . getPersonLocID($uprID) . ")";
    } else if ($qryNm == "Full Name") {
        $in_val = isset($_POST['in_val']) ? $_POST['in_val'] : "";
        echo getPrsnFullNm(getPersonID($in_val));
    } else if ($qryNm == "Uom Conversion Factor") {
        $in_val = isset($_POST['in_val']) ? $_POST['in_val'] : "";
        $in_itmuomid = isset($_POST['in_itmuomid']) ? $_POST['in_itmuomid'] : "";
        echo getUomCnvsnFactor($in_itmuomid, $in_val);
    } else if ($qryNm == "SendPswdLnk") {
        //var_dump($_POST);
        $in_val = isset($_POST['in_val']) ? $_POST['in_val'] : "";
        sendPswdResetLink($in_val); //getUserName()
    } else if ($qryNm == "Change Password Auto") {
        $in_val = isset($_POST['in_val']) ? $_POST['in_val'] : "";
        changePswdAuto($in_val); //getUserName()
    } else if ($qryNm == "Load Modules") {
        set_time_limit(300);
        loadMdlsNthrRolesNLovs();
    } else if ($qryNm == "action_url") {
        $RoutingID = isset($_POST['RoutingID']) ? cleanInputData($_POST['RoutingID']) : "";
        $actyp = isset($_POST['actyp']) ? cleanInputData($_POST['actyp']) : "";
        $actReason = isset($_POST['actReason']) ? cleanInputData($_POST['actReason']) : "";
        $toPrsLocID = isset($_POST['toPrsLocID']) ? cleanInputData($_POST['toPrsLocID']) : "";
        $arry1 = explode(";", $actyp);
        $webParams = "";
        for ($r = 0; $r < count($arry1); $r++) {
            if ($arry1[$r] !== "") {
                $webUrl = getActionUrl($RoutingID, $arry1[$r]);
                if ($webUrl != "") {
                    $webUrl = str_replace("{:wkfRtngID}", "$RoutingID", $webUrl);
                    $webUrl = str_replace("{:wkfAction}", "$arry1[$r]", $webUrl);
                    $webUrl = str_replace("{:wkfActReason}", "$actReason", $webUrl);
                    $webUrl = str_replace("{:wkfToPrsLocID}", "$toPrsLocID", $webUrl);
                    $webParams = $webUrl;
                    echo trim($webParams);
                    exit();
                }
            }
        }
        echo "<p style=\"text-align:left;font-weight:bold;font-style:italic; color:red;\"> |ERROR| No Url Params behind Action!</p>";
    } else if ($qryNm == "Divs Group Type") {
        $in_val = isset($_POST['in_val']) ? $_POST['in_val'] : "-1";
        echo getDivGrpTyp($in_val);
    } else if ($qryNm == "Report Log Message") {
        $in_val = isset($_POST['run_id']) ? $_POST['run_id'] : "-1";
        echo "<div style=\"min-width:700px;\" ><code style=\"font-weight:regular;font-size:12px;font-family:'Courier New';\" >" . str_replace("\r\n", "<br/>", getLogMsg(getLogMsgID("rpt.rpt_run_msgs", "Process Run", $in_val), "rpt.rpt_run_msgs")) . "</code></div>";
    } else if ($qryNm == "Report Run Output") {
        $in_val = isset($_POST['run_id']) ? $_POST['run_id'] : "-1";
        $dfltPrvldgs111 = array("View Runs from Others");
        $mdlNm = "Reports And Processes";
        $canviewall = test_prmssns($dfltPrvldgs111[0], $mdlNm);
        $runnrID = getGnrlRecNm("rpt.rpt_report_runs", "rpt_run_id", "run_by", $in_val);
        if ($usrID == $runnrID || $canviewall == TRUE) {
            $outptUsd = getGnrlRecNm("rpt.rpt_report_runs", "rpt_run_id", "output_used", $in_val);
            $rpt_src = str_replace("\\", "/", $ftp_base_db_fldr . "/Rpts");
            $rpt_dest = "";
            $rptmsg = "No Output File";
            $rpt_src1 = str_replace("\\", "/", $ftp_base_db_fldr . "/Rpts");
            $rpt_dest1 = "";
            if ($outptUsd == "HTML" || $outptUsd == "COLUMN CHART" || $outptUsd == "SIMPLE COLUMN CHART" || $outptUsd == "BAR CHART" || $outptUsd == "PIE CHART" || $outptUsd == "LINE CHART") {
                $rpt_src1 .= "/amcharts_2100/images/";
                $rpt_dest1 = "dwnlds/$db_folder/Rpts/amcharts_2100/images/";
                $rpt_src .= "/amcharts_2100/samples/$in_val.html";
                $rpt_dest = "dwnlds/$db_folder/Rpts/amcharts_2100/samples/$in_val.html";
            } else if ($outptUsd == "STANDARD") {
                $rpt_src .= "/$in_val.txt";
                $rpt_dest = "dwnlds/$db_folder/Rpts/$in_val.txt";
            } else if ($outptUsd == "PDF") {
                $rpt_src .= "/$in_val.pdf";
                $rpt_dest = "dwnlds/$db_folder/Rpts/$in_val.pdf";
            } else if ($outptUsd == "MICROSOFT WORD") {
                $rpt_src .= "/$in_val.doc";
                $rpt_dest = "dwnlds/$db_folder/Rpts/$in_val.doc";
            } else if ($outptUsd == "MICROSOFT EXCEL") {
                $rpt_src .= "/$in_val.xls";
                $rpt_dest = "dwnlds/$db_folder/Rpts/$in_val.xls";
            } else if ($outptUsd == "CHARACTER SEPARATED FILE (CSV)") {
                $rpt_src .= "/$in_val.csv";
                $rpt_dest = "dwnlds/$db_folder/Rpts/$in_val.csv";
            }
            if ($rpt_dest1 != "") {
                recurse_copy($rpt_src1, $rpt_dest1);
            }
            if (file_exists($rpt_src)) {
//file exists!
                if (copy("$rpt_src", "$rpt_dest") == TRUE) {
                    $_SESSION['CUR_RPT_FILES'] .= "$rpt_dest" . "|";
                }
            } else {
//file does not exist.   
                echo $rptmsg;
            }
            header("location: $rpt_dest");
        } else {
            echo "Permission Denied!";
        }
    }
} else {
//echo "Please Login First!"; 
    if ($qryNm == "SendPswdLnk") {
        //var_dump($_POST);
        $in_val = isset($_POST['in_val']) ? $_POST['in_val'] : "";
        sendPswdResetLink($in_val); //getUserName()
    } else {
        //restricted();
    }
}
?>
