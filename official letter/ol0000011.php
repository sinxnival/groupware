<?php 

require_once "../../lib/include.php";
require_once "../common/biz_ini.php";
require_once "../common/func.php";

//세션 만료일 경우
if (!isset($_SESSION["user"]["uno"])) {
    echo json_encode(array("session_out" => true));
    //종료
    exit();
}

//작업모드
$mode = $_POST["mode"];

if($mode == "EDIT") {
    $docType = $_POST["docType"];
    $dbMode = $_POST["dbMode"];

    // 발신명의
    $sendNameList = array();
    $SQL = "SELECT SD_NO, SD_CD, SD_TYPE
            FROM EAS_SEND_NAME_CODE";
    $db->query($SQL);
    while($db->next_record()) {
        $row = $db->Record;

        $sendNameList[] = array(
            "sdNo" => $row["sd_no"],
            "sdCd" => $row["sd_cd"],
            "sdType" => $row["sd_type"]
        );
    }

    // 열람등급
    $readGradeList = array();
    $SQL = "SELECT RG_NO, RG_CD, RG_VAL 
            FROM EAS_READ_AUTH";
    $db->query($SQL);
    while($db->next_record()) {
        $row = $db->Record;

        $readGradeList[] = array(
            "rgNo" => $row["rg_no"],
            "rgCd" => $row["rg_cd"],
            "rgVal" => $row["rg_val"]
        );
    }

    //상세
    $docHtml = '';
    $deptNick = '';
    $recipientIds = '';
    $docDetail = array();
    $appLine = array();
    $attachFileList = array();
    $relatedEBList = array();
    if($dbMode == "I") {
        if($docType == "S") {
            $SQL = "SELECT DOC_HTML
                    FROM EAS_DOC_HTML";
            $db->query($SQL);
            $db->next_record();
            $row = $db->Record;
            $docHtml = $row["doc_html"];
        }

        // 내 부서코드
        $params = array();
        $SQL = "SELECT D.DEPT_NICK, U.TEL, U.EMAIL
                FROM SYS_DEPT_SET D
                INNER JOIN SYS_USER_SET U ON D.DEPT_NO = U.DEPT_ID 
                WHERE UNO = :uno";
        $params = array(
            ":uno" => $user->uno
        );
        $db->query($SQL, $params);
        $db->next_record();
        $row = $db->Record;

        $deptNick = $row["dept_nick"];
        if (mb_strlen($row["dept_nick"], 'UTF-8') >= 4) {
            $deptNick = 'OO';
        }

        $tel = $row["tel"];
        $email = $row["email"];
        $fax = "0504-013-7090";

    } else {
        $ono = $_POST["ono"];

        $SQL = "SELECT I.ONO, SD_CD, I.RG_CD, DOC_CD, OUT_DOC_CD, COMPANY_CD, SEND_DEPT, DOC_TYPE, READ_DEPT, PJT_NAME, 
                        RECIPIENT, REFERENCE, TO_CHAR(ISSUE_DATE, 'YYYY-MM-DD') AS ISSUE_DATE, TITLE, CONTENT, SENDER, WRITER, PHONE_NUM, 
                        FAX_NUM, EMAIL, RECEIVE_USER, TO_CHAR(RECEIVE_DATE, 'YYYY-MM-DD') AS RECEIVE_DATE, RECEIVE_KIND, APPROVAL_BASIS, STATUS, 
                        ROP.CO_RECEIVER_LIST, APPROVAL_KIND, SEND_KIND, CONTENT_FOOTER, STORE_PERIOD
                FROM EAS_DOC_INFO I
                INNER JOIN EAS_READ_AUTH R ON R.RG_CD = I.RG_CD
                LEFT JOIN (
                            SELECT R.ONO, LISTAGG(
                                    CASE 
                                        WHEN R.CO_DEPT_USER_KIND = 'U' THEN U.USER_NAME
                                        WHEN R.CO_DEPT_USER_KIND = 'D' THEN V.DEPT_NAME
                                    END,
                                    '/') WITHIN GROUP (ORDER BY R.CO_DEPT_USER_KIND, R.CO_DEPT_USER_ID) AS CO_RECEIVER_LIST
                            FROM EAS_DOC_RECEIPOPER R
                            LEFT JOIN COMMON.V_BIZ_USER_SET U 
                                ON R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = U.UNO
                            LEFT JOIN V_SYS_DEPT_SET V 
                                ON R.CO_DEPT_USER_KIND = 'D' AND R.CO_DEPT_USER_ID = V.DEPT_NO
                            GROUP BY R.ONO
                ) ROP ON ROP.ONO = I.ONO
                WHERE I.ONO = :ono";
        $params = array(
            ":ono" => $ono
        );
        $db->query($SQL, $params);
        $db->next_record();
        $row = $db->Record;

        if($row["pjt_name"]) {
            $docScope = "PROJECT";
        } else {
            $docScope = "HEAD";
        }

        $docDetail = array(
            "ono" => $row["ono"],
            "sendType" => $row["sd_cd"],
            "readGrade" => $row["rg_cd"],
            "docCd" => $row["doc_cd"],
            "outDocCd" => $row["out_doc_cd"],
            "companyCd" => $row["company_cd"],
            "sendDept" => $row["send_dept"],
            "docType" => $row["doc_type"],
            "docScope" => $docScope,
            "txtPjt_nm_fr" => $row["pjt_name"],
            "recipient" => $row["recipient"],
            "reference" => $row["reference"],
            "issueDate" => $row["issue_date"],
            "title" => $row["title"],
            "sendVal" => $row["sender"],
            "writer" => $row["writer"],
            "tel" => $row["phone_num"],
            "fax" => $row["fax_num"],
            "email" => $row["email"],
            "receiveUser" => $row["receive_user"],
            "receiveDate" => $row["receive_date"],
            "receiveKind" => $row["receive_kind"],
            "approvalBasis" => $row["approval_basis"],
            "approvalKind" => $row["approval_kind"],
            "sendKind" => $row["send_kind"],
            "status" => $row["status"],
            "recipientNms" => $row["co_receiver_list"],
            "storePeriod" => $row["store_period"]
        );

        $SQL = "SELECT CONTENT FROM EAS_DOC_INFO
                WHERE ONO = :ono";

        $params = [
            [":ono", $ono, false]
        ];

        $columns = [
            ["content", true]
        ];

        $ociDB->query_lob($SQL, $params, $columns);
        $row = $ociDB->RecordAll[0];
        $docHtml = $row["content"];
        
        // 결재라인
        $appLine["app"] = [];
        $appLine["agr"] = [];
        $SQL = "SELECT APPROVER, APPR_KIND, STEP_ORDER, IS_SIGN, SIGN_IMG, TO_CHAR(APPROVE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS APPROVE_DATE, STATUS, U.USER_NAME, U.COMPANY_ID, U.DEPT_ID, U.DUTY_NAME, U.JOBDUTY_NAME, U.TEAM_ID
                FROM EAS_APPR_STEP A
                INNER JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = A.APPROVER
                WHERE ONO = :ono
                ORDER BY APPR_KIND DESC, STEP_ORDER";
        $params = array(
            ":ono" => $ono
        );
        $db->query($SQL, $params);

        while($db->next_record()) {
            $row = $db->Record;

            if($row["is_sign"] == "Y") {
                $signYn = 1;
                
                $signImg = '';
                if (!empty($row["sign_img"])) {
                    $signImg = "{$urlPathAbsolute}EMPSign/" . $row["sign_img"];
                    $signUserNm = "";
                } else{
                    $signImg = "approval";
                    $signUserNm = $row["user_name"];
                }

                // 상세
                $apprDate = new DateTime($row["approve_date"]);
                $signDetail = $apprDate->format("Ymd");
                $signDetail .= "<br />";
                $signDetail .= $apprDate->format("H:i");
            } else {
                $signYn = 0;
                $signImg = "appOrder";
                $signUserNm = '';
                $signDetail = $row["user_name"];
            }

            $appKind = "";
            if($row["appr_kind"] == "APPROVE") {
                $appKind = "app";
                $appNum = "1";
            } else {
                $appKind = "agr";
                $appNum = "2";
            }

            //결재
            $signKind = $row["status"];

            $signKindNm = '';
            $signKindColor = '';
            if($signKind == "02") {
                $signKindNm = "반 려";
                $signKindColor = "info";
            } else if($signKind == "04") {
                $signKindNm = "거 부";
                $signKindColor = "info";
            } else if($signKind == "05") {
                $signKindNm = "전 결";
                $signKindColor = "info";
            }

            $appAgrValue = $row["approver"] . "|" . $row["company_id"] . "|" . $row["team_id"] . "|" . $row["user_name"] . "|" . $row["duty_name"] . "|" . $row["jobduty_name"] . "|" . $appNum . "|" . $row["step_order"];

            $appLine[$appKind][] = array(
                //결재자 고유번호
                "docUserId" => $row["approver"],
                //결재 순서
                "appLevel" => $row["step_order"],
                //사인 유무(1결재사인함, 0결재사인 안함, 2합의 거부)
                "signYn" => $signYn,
                //사인 종류(결재, 전결, 후결, 대체)
                "signKind" => $signKind,
                //결재 결과 명
                "signKindNm" => $signKindNm,
                //결재 결과 표시색
                "signKindColor" => $signKindColor,
                //결재 사인 이미지
                "signImg" => $signImg,
                //결재자 명
                "signUserNm" => $signUserNm,
                //결재 상세
                "signDetail" => $signDetail,
                //직위/직책
                "appGradeDutyNm" => $row["duty_name"],
                //결재자 값
                "appAgrValue" => $appAgrValue
            );
        }

        //수신참조
        $recipientList = array();
        $SQL = "SELECT R.ONO, R.CO_DEPT_USER_KIND, R.CO_DEPT_USER_ID, U.TEAM_ID AS DEPT_ID, U.COMPANY_ID
                FROM EAS_DOC_RECEIPOPER R
                LEFT JOIN COMMON.V_BIZ_USER_SET U 
                    ON R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = U.UNO
                LEFT JOIN V_SYS_DEPT_SET V 
                    ON R.CO_DEPT_USER_KIND = 'D' AND R.CO_DEPT_USER_ID = V.DEPT_NO
                WHERE ONO = :ono";
        $db->query($SQL, $params);
        while($db->next_record()) {
            $row = $db->Record;

            $id = "";
            if ("D" == $row["co_dept_user_kind"]) {
                $id = "D" . $row["co_dept_user_id"];
            }
            else {
                $id = $row["co_dept_user_id"] . "|" . $row["company_id"];
                if (!empty($row["dept_id"])) {
                    $id .= "|" . $row["dept_id"];
                }
            }

            $recipientList["id"][] = $id;
        }

        if (count($recipientList) > 0) {
            $recipientIds = "/" . implode("/", $recipientList["id"]);
        }

        // 첨부파일
        $SQL = "SELECT FNO, FILE_NAME
                FROM EAS_DOC_ATCH
                WHERE ONO = :ono
                AND IS_FINAL != 'Y'";
        $db->query($SQL, $params);
        while($db->next_record()) {
            $row = $db->Record;

            $attachFileList[] = array(
                "attachId" => $row["fno"],
                "fileNm" => $row["file_name"]
            );
        }

        // 참조파일
        $SQL = "SELECT REF_DOC, DOC_CD, OUT_DOC_CD, TITLE
                FROM EAS_DOC_REF R
                INNER JOIN EAS_DOC_INFO D ON D.ONO = R.REF_DOC
                WHERE R.ONO = :ono";
        $db->query($SQL, $params);
        while($db->next_record()) {
            $row = $db->Record;

            $relatedEBList[] = array(
                "refDoc" => $row["ref_doc"],
                "docCd" => $row["doc_cd"],
                "outDocCd" => $row["out_doc_cd"],
                "title" => $row["title"]
            );
        }
    }

    // 부서코드
    $deptNickList = array();
    $SQL = "SELECT DISTINCT DEPT_NICK
            FROM SYS_DEPT_SET
            WHERE IS_USE = 'Y'
            AND DEPT_NICK IS NOT NULL
            AND LENGTH(DEPT_NICK) < 4";
    $db->query($SQL);
    while($db->next_record()) {
        $row = $db->Record;

        $deptNickList[] = $row["dept_nick"];
    }

    $result = array(
        "sendNameList" => $sendNameList,
        "readGradeList" => $readGradeList,
        "docHtml" => $docHtml,
        "writer" => $_SESSION["user"]["user_name"],
        "deptNickList" => $deptNickList,
        "writerDept" => $deptNick,
        "docDetail" => $docDetail,
        "appLine" => $appLine,
        "tel" => $tel,
        "email" => $email,
        "fax" => $fax,
        "attachFileList" => $attachFileList,
        "relatedEBList" => $relatedEBList,
        "recipientIds" => $recipientIds
    );

    echo json_encode($result);
} else if($mode == "SEND_TYPE") {
    $sendType = $_POST["sendType"];

    $sendVal = "";
    $directorList = array();
    if(in_array($sendType, ["CP", "A010", "A030", "C010"])) {
        $SQL  = "SELECT SD_VAL
                FROM EAS_SEND_NAME_CODE
                WHERE SD_CD = :sdCd";
        $params = array(
            ":sdCd" => $sendType
        );
        $db->query($SQL, $params);

        $db->next_record();
        $row = $db->Record;

        $sendVal = $row["sd_val"];

        if(in_array($sendType, ["A010", "A030"])) {
            $SQL = "SELECT USER_NAME
                    FROM BIZ_USER_SET
                    WHERE JOBDUTY_CD = :sdCd";
            $commonDB->query($SQL, $params);
            $commonDB->next_record();
            $row = $commonDB->Record;

            $sendVal .= " " . $row["user_name"];
        } else if($sendType == "C010") {
            $SQL = "SELECT USER_NAME, JOBDUTY_NAME, DEPT_NAME, UNO
                    FROM BIZ_USER_SET
                    WHERE JOBDUTY_CD = :sdCd
                    ORDER BY DUTY_VIEW_ORDER";
            $commonDB->query($SQL, $params);
            while($commonDB->next_record()) {
                $row = $commonDB->Record;

                $directorList[] = array(
                    "uno" => $row["uno"],
                    "directorVal" => $row["dept_name"] . "장 " . $row["user_name"]
                );
            }
        }
    }

    $result = array(
        "sendVal" => $sendVal,
        "directorList" => $directorList
    );

    echo json_encode($result);
} else if($mode == "SAVE" || $mode == "SAVE_TEMP") {
    $ono = $_POST["ono"];
    $sdCd = $_POST["sendType"];
    $rgCd = $_POST["readGrade"];
    $companyCd = $_POST["companyCd"];
    $sendDept = $_POST["sendDept"];
    $docType = $_POST["docType"];
    $issueDate = $_POST["issueDate"];
    $deptId = $_POST["readDeptId"];
    $deptId = str_replace("D", "", $deptId);
    $recipient = $_POST["recipient"];
    $reference = $_POST["reference"];
    $issueDate = $_POST["issueDate"];
    $title = $_POST["title"];
    $content = $_POST["htmlContent"];
    $txtContent = $_POST["txtContent"];
    $footerContent = $_POST["footerContent"];
    $sender = $_POST["sendVal"];
    $outDocCd = $_POST["outDocCd"];
    $receiveKind = $_POST["receiveKind"];
    $receiveDate = $_POST["receiveDate"];
    $tel = $_POST["tel"];
    $fax = $_POST["fax"];
    $email = $_POST["email"];
    $storePeriod = $_POST["storePeriod"];

    if($docType == "S") {
        $writer = $_SESSION["user"]["uno"];
        $receiveUser = '';
        $docDate = $issueDate;
    } else if($docType =="R") {
        $writer = '';
        $receiveUser = $_SESSION["user"]["uno"];
        $docDate = $receiveDate;
    }

    $yy = substr($docDate, 2, 2);
    $mm = substr($docDate, 5, 2);
    $docYm = $yy . $mm;

    $status = '';
    if($mode == "SAVE_TEMP") {
        $status = "06";
        $issueNum = 'NNNN';
    } else {
        $SQL = "SELECT MAX(TO_NUMBER(ISSUE_NUM)) AS SERIAL_NUM
                FROM EAS_DOC_INFO
                WHERE DOC_TYPE = :docType
                AND SUBSTR(ISSUE_YM, 1, 2) = SUBSTR(:docYm, 1, 2)
                AND REGEXP_LIKE(TRIM(ISSUE_NUM), '^[0-9]+$')";
        $params = array(
            ":docType" => $docType,
            ":docYm" => $docYm
        );
        $db->query($SQL, $params);
        $db->next_record();
        $row = $db->Record;
        $serialNum = $row["serial_num"];

        if($row["serial_num"]) {
            $serialNum++;
            $issueNum = str_pad($serialNum, 4, "0", STR_PAD_LEFT);
        } else {
            $issueNum = '0001';
        }
    }

    $pjtNm = '';
    if(isset($_POST["txtPjt_nm_fr"])) {
        $pjtNm = $_POST["txtPjt_nm_fr"];
    }

    $docCd = implode('-', [$companyCd, $sendDept, 'EB', $docType, $docYm, $issueNum]);
    $sendKind = $_POST["sendKind"];
    $approvalKind = $_POST["approvalKind"];
    $approvalBasis = $_POST["approvalBasis"];

    $proceed = true;

    $db->beginTransaction();
    try {
        if(!$ono) {
            $ono = $db->nextid("SEQ_EAS_ONO");
        }
        $SQL = "MERGE INTO EAS_DOC_INFO target
                USING (
                    SELECT
                        :ono AS ONO,
                        :sdCd AS SD_CD,
                        :rgCd AS RG_CD,
                        :docCd AS DOC_CD,
                        :outDocCd AS OUT_DOC_CD,
                        :companyCd AS COMPANY_CD,
                        :sendDept AS SEND_DEPT,
                        :docType AS DOC_TYPE,
                        :docYm AS ISSUE_YM,
                        :issueNum AS ISSUE_NUM,
                        :readDept AS READ_DEPT,
                        :pjtNm AS PJT_NAME,
                        :recipient AS RECIPIENT,
                        :reference AS REFERENCE,
                        :issueDate AS ISSUE_DATE,
                        :title AS TITLE,
                        :sender AS SENDER,
                        :sendKind AS SEND_KIND,
                        :writer AS WRITER,
                        :tel AS PHONE_NUM,
                        :fax AS FAX_NUM,
                        :email AS EMAIL,
                        :receiveUser AS RECEIVE_USER,
                        :receiveDate AS RECEIVE_DATE,
                        :receiveKind AS RECEIVE_KIND,
                        :approvalKind AS APPROVAL_KIND,
                        :approvalBasis AS APPROVAL_BASIS,
                        :status AS STATUS,
                        :storePeriod AS STORE_PERIOD,
                        :regUser AS REG_USER,
                        :modUser AS MOD_USER
                    FROM dual
                ) nd
                ON (target.ONO = nd.ONO)
                WHEN MATCHED THEN
                    UPDATE SET
                        target.SD_CD = nd.SD_CD,
                        target.RG_CD = nd.RG_CD,
                        target.DOC_CD = nd.DOC_CD,
                        target.OUT_DOC_CD = nd.OUT_DOC_CD,
                        target.COMPANY_CD = nd.COMPANY_CD,
                        target.SEND_DEPT = nd.SEND_DEPT,
                        target.DOC_TYPE = nd.DOC_TYPE,
                        target.ISSUE_YM = nd.ISSUE_YM,
                        target.ISSUE_NUM = nd.ISSUE_NUM,
                        target.READ_DEPT = nd.READ_DEPT,
                        target.PJT_NAME = nd.PJT_NAME,
                        target.RECIPIENT = nd.RECIPIENT,
                        target.REFERENCE = nd.REFERENCE,
                        target.ISSUE_DATE = nd.ISSUE_DATE,
                        target.TITLE = nd.TITLE,
                        target.SENDER = nd.SENDER,
                        target.SEND_KIND = nd.SEND_KIND,
                        target.WRITER = nd.WRITER,
                        target.PHONE_NUM = nd.PHONE_NUM,
                        target.FAX_NUM = nd.FAX_NUM,
                        target.EMAIL = nd.EMAIL,
                        target.RECEIVE_USER = nd.RECEIVE_USER,
                        target.RECEIVE_DATE = nd.RECEIVE_DATE,
                        target.RECEIVE_KIND = nd.RECEIVE_KIND,
                        target.APPROVAL_KIND = nd.APPROVAL_KIND,
                        target.APPROVAL_BASIS = nd.APPROVAL_BASIS,
                        target.STATUS = nd.STATUS,
                        target.STORE_PERIOD = nd.STORE_PERIOD,
                        target.MOD_USER = nd.MOD_USER,
                        target.MOD_DATE = SYSDATE
                WHEN NOT MATCHED THEN
                    INSERT (
                        ONO, SD_CD, RG_CD, DOC_CD, OUT_DOC_CD, COMPANY_CD, SEND_DEPT,
                        DOC_TYPE, ISSUE_YM, ISSUE_NUM, READ_DEPT, PJT_NAME, RECIPIENT,
                        REFERENCE, ISSUE_DATE, TITLE, SENDER, SEND_KIND,
                        WRITER, PHONE_NUM, FAX_NUM, EMAIL, RECEIVE_USER, RECEIVE_DATE,
                        RECEIVE_KIND, APPROVAL_KIND, APPROVAL_BASIS, STATUS, STORE_PERIOD, REG_USER
                    ) VALUES (
                        nd.ONO, nd.SD_CD, nd.RG_CD, nd.DOC_CD, nd.OUT_DOC_CD, nd.COMPANY_CD, nd.SEND_DEPT,
                        nd.DOC_TYPE, nd.ISSUE_YM, nd.ISSUE_NUM, nd.READ_DEPT, nd.PJT_NAME, nd.RECIPIENT,
                        nd.REFERENCE, nd.ISSUE_DATE, nd.TITLE, nd.SENDER, nd.SEND_KIND,
                        nd.WRITER, nd.PHONE_NUM, nd.FAX_NUM, nd.EMAIL, nd.RECEIVE_USER, nd.RECEIVE_DATE,
                        nd.RECEIVE_KIND, nd.APPROVAL_KIND, nd.APPROVAL_BASIS, nd.STATUS, nd.STORE_PERIOD, nd.REG_USER
                    )";
        $params = array(
            ":ono" => $ono,
            ":sdCd" => $sdCd,
            ":rgCd" => $rgCd,
            ":docCd" => $docCd,
            ":outDocCd" => $outDocCd,
            ":companyCd" => $companyCd,
            ":sendDept" => $sendDept,
            ":docType" => $docType,
            ":docYm" => $docYm,
            ":issueNum" => $issueNum,
            ":readDept" => $deptId,
            ":pjtNm" => $pjtNm,
            ":recipient" => $recipient,
            ":reference" => $reference,
            ":issueDate" => $issueDate,
            ":title" => $title,
            ":sender" => $sender,
            ":sendKind" => $sendKind,
            ":writer" => $writer,
            ":tel" => $tel,
            ":fax" => $fax,
            ":email" => $email,
            ":receiveUser" => $receiveUser,
            ":receiveDate" => $receiveDate,
            ":receiveKind" => $receiveKind,
            ":approvalKind" => $approvalKind,
            ":approvalBasis" => $approvalBasis,
            ":status" => $status,
            ":storePeriod" => $storePeriod,
            ":regUser" => $user->uno,
            ":modUser" => $user->uno
        );

        if($db->query($SQL, $params)) {
            $proceed = true;
        } else {
            $proceed = false;
            $db->RollBack();
        }

        if($proceed) {
            $content_euckr = iconv("UTF-8", "EUC-KR//IGNORE", $content);

            $SQL = "UPDATE EAS_DOC_INFO SET CONTENT = :clob_data WHERE ONO = :ono";
            $stmt = $db->Conn->prepare($SQL);
            $stmt->bindParam(":clob_data", $content_euckr, PDO::PARAM_STR, strlen($content_euckr));
            $stmt->bindParam(":ono", $ono);
            $stmt->execute();

            $footer_euckr = iconv("UTF-8", "EUC-KR//IGNORE", $footerContent);
            $SQL = "UPDATE EAS_DOC_INFO SET CONTENT_FOOTER = :clob_txt
                    WHERE ONO = {$ono}";

            $db->$Stmt = $db->Conn->prepare($SQL);
            $db->$Stmt->bindParam(":clob_txt", $footer_euckr, PDO::PARAM_STR, strlen($footer_euckr));
            $db->$Stmt->execute();
        }

        if ($proceed) {
            $appAgrLine = $_POST["appAgrLine"];
            $recipientIds = $_POST["recipientIds"];
            if(!changeEasLine($ono, $appAgrLine, $recipientIds)) {
                $proceed = false;
                $db->RollBack();
            } else {
                $db->commit();
            }
        }

        // 첨부파일
        if($proceed) {
            $delAttachFile = $_POST["delAtchFileList"];
            $client = new SoapClient('http://file.hi-techeng.co.kr/transferweb/Service1.svc?singleWsdl');
            $location = "OL/{$ono}/";
            if (count($delAttachFile) > 0) {
                foreach ($delAttachFile as $fno) {
                    $SQL = "DELETE FROM EAS_DOC_ATCH
                            WHERE ONO = :ono
                            AND FNO = :fno";
                    $params = array(
                        ":ono" => $ono,
                        ":fno" => $fno
                    );
                    if(!$db->query($SQL, $params)) {
                        $proceed = false;
                        break;
                    }
                }
            }
            $fileList = array();
            for ($i=0; $i<count($_FILES['newAttachFile']['name']); $i++) {
                if (!empty($_FILES['newAttachFile']['name'][$i])) {
                    $newFileName = "";
                    $info = pathinfo($_FILES['newAttachFile']['name'][$i]);
                    $oriFileName = $info['basename'];
                    $ext = "." . $info['extension'];
                    // $newFileName = getNewFileName($info['filename'], $ext);
                    $newFileName = makeSafeFileName($_FILES['newAttachFile']['name'][$i]);
        
                    $saveFileName = $location . $newFileName;
                    $uploadFile = file_get_contents($_FILES['newAttachFile']['tmp_name'][$i]);
                    $parameter = array(
                        'strFileBinary' => $uploadFile,
                        'strSaveFileName' => $saveFileName
                    );
                    $resultUpload = $client->UploadFileWebGW($parameter);
                    //파일 업로드 실패 시
                    if ($resultUpload->UploadFileWebResult->ErrorMessage) {
                        $proceed = false;
                        $msg = $resultUpload->UploadFileWebResult->ErrorMessage;
                        break;
                    }
                    //파일 업로드 성공 시
                    else {
                        // $fileList["name"][] = $newFileName;
                        // $fileList["size"][] = $_FILES['newAttachFile']['size'][$i];
                        // $fileList["oriName"][] = $oriFileName;

                        $SQL = "INSERT INTO EAS_DOC_ATCH (ONO, FILE_NAME, FILE_SAVE, FILE_SIZE, IS_FINAL)
                                VALUES(:ono, :fileName, :fileSave, :fileSize, :isFinal)";
                        $params = array(
                            ":ono" => $ono,
                            ":fileName" => $oriFileName,
                            ":fileSave" => $newFileName,
                            ":fileSize" => $_FILES['newAttachFile']['size'][$i],
                            ":isFinal" => 'N'
                        );
                        $db->query($SQL, $params);
                    }
                }
            }
        }

        // 참조파일
        if($proceed) {
            $SQL = "DELETE FROM EAS_DOC_REF
                    WHERE ONO = :ono";
            $params = array(
                ":ono" => $ono
            );
            if(!$db->query($SQL, $params)) {
                $proceed = false;
            }
        }

        $relatedEB = $_POST["relatedEB"];
        if($proceed && count($relatedEB)) {
            foreach ($relatedEB as $ref) {
                $SQL = "INSERT INTO EAS_DOC_REF(ONO, REF_DOC)
                        VALUES (:ono, :refDoc)";
                $params = array(
                    ":ono" => $ono,
                    ":refDoc" => $ref
                );
                if(!$db->query($SQL, $params)) {
                    $proceed = false;
                    break;
                }
            }
        }
    } catch(PDOException $e) {
        $proceed = false;
        $db->Error=$db->Conn->errorInfo();
        //ORA-01403 : No data found
        if ($db->Error[1]!=1403 && $db->Error[1]!=0 && $db->sqoe) 
        {
            echo "<BR><FONT color=red><B>".$db->Error[2]."<BR>Query :\"$SQL\"</B></FONT>";
        }
        $db->RollBack();
    } finally {
        $db->endTransaction();
    }

    $result = array(
        "proceed" => $proceed
    );

    echo json_encode($result);

} else if($mode == "LIST") {
    $infoList = array();
    $docType = $_POST["docType"];
    $ddlSearchKind = $_POST["ddlSearchKind"];
    $txtSearchValue = $_POST["txtSearchValue"];
    $pageNo = $_POST["pageNo"];
    $totalCnt = 0;

    $SQL = "SELECT DEPT_ID FROM SYS_USER_SET
            WHERE UNO = :uno";
    $params = array(
        ":uno" => $user->uno
    );
    $db->query($SQL, $params);
    $db->next_record();
    $row = $db->Record;
    $deptNo = $row["dept_id"];
    $readGradeCondition = getReadGradeCondition();
    $storePeriodCondiion = getStorePeriodCondiion();

    if($docType == "S") {
        //cnt
        $SQL = "WITH HIER_DEPT AS (
                    SELECT DEPT_NO, DEPT_NAME
                    FROM SYS_DEPT_SET
                    WHERE LEVEL_NO = 3
                    CONNECT BY PRIOR DEPT_NO = PARENT_NO
                    START WITH LEVEL_NO = 3 
                )
                SELECT E.ONO, E.DOC_CD, E.READ_DEPT, E.RECIPIENT, E.REFERENCE, E.SENDER, E.WRITER, 
                    U.USER_NAME, A.APPROVERS, E.APPROVAL_KIND, E.APPROVAL_BASIS, 
                    TO_CHAR(E.ISSUE_DATE, 'YYYY-MM-DD') AS ISSUE_DATE, 
                    E.TITLE, E.PJT_NAME, F.ATCH_FILES, E.SEND_KIND, H.DEPT_NAME, 
                    FA.FILE_NAME, FA.FILE_SAVE
                FROM EAS_DOC_INFO E
                INNER JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = E.WRITER
                LEFT JOIN (
                    SELECT S.ONO,
                        LISTAGG(TO_CHAR(U.USER_NAME), ',') 
                            WITHIN GROUP (ORDER BY STEP_ORDER) AS APPROVERS
                    FROM EAS_APPR_STEP S
                    INNER JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = S.APPROVER
                    INNER JOIN EAS_DOC_INFO D ON D.ONO = S.ONO
                    WHERE S.APPR_KIND = 'APPROVE'
                    AND S.STATUS IN ('01', '05')  -- 결재완료 or 전결
                    AND (S.APPROVER != D.WRITER AND (D.RECEIVE_USER IS NULL OR S.APPROVER != D.RECEIVE_USER))
                    GROUP BY S.ONO
                ) A ON A.ONO = E.ONO
                LEFT JOIN HIER_DEPT H ON H.DEPT_NO = U.DEPT_ID
                LEFT JOIN (
                    SELECT FA.ONO,
                        LISTAGG(FNO || ':' || FILE_NAME, ', ') 
                            WITHIN GROUP (ORDER BY FNO) AS ATCH_FILES
                    FROM EAS_DOC_ATCH FA
                    WHERE IS_FINAL != 'Y'
                    GROUP BY FA.ONO
                ) F ON F.ONO = E.ONO
                LEFT JOIN EAS_DOC_ATCH FA ON E.ONO = FA.ONO AND FA.IS_FINAL = 'Y'
                INNER JOIN EAS_READ_AUTH R ON R.RG_CD = E.RG_CD
                LEFT JOIN (
                            SELECT R.ONO, LISTAGG(
                                    CASE 
                                        WHEN R.CO_DEPT_USER_KIND = 'U' THEN U.USER_NAME
                                        WHEN R.CO_DEPT_USER_KIND = 'D' THEN V.DEPT_NAME
                                    END,
                                    '/') WITHIN GROUP (ORDER BY R.CO_DEPT_USER_KIND, R.CO_DEPT_USER_ID) AS CO_RECEIVER_LIST
                            FROM EAS_DOC_RECEIPOPER R
                            LEFT JOIN COMMON.V_BIZ_USER_SET U 
                                ON R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = U.UNO
                            LEFT JOIN V_SYS_DEPT_SET V 
                                ON R.CO_DEPT_USER_KIND = 'D' AND R.CO_DEPT_USER_ID = V.DEPT_NO
                            GROUP BY R.ONO
                ) ROP ON ROP.ONO = E.ONO
                WHERE E.DOC_TYPE = :docType 
                AND (E.STATUS IS NULL OR E.STATUS != '06') 
                AND (E.STATUS IS NULL OR E.STATUS != '02') 
                AND (E.STATUS IS NULL OR E.STATUS != '04') 
                AND (
                    E.WRITER = :uno
                    OR E.RECEIVE_USER = :uno
                    OR EXISTS (
                        SELECT 1 FROM EAS_APPR_STEP S
                        WHERE S.ONO = E.ONO
                        AND S.APPROVER = :uno
                    )
                    OR EXISTS (
                        SELECT 1 FROM EAS_DOC_RECEIPOPER R
                        WHERE R.ONO = E.ONO
                        AND R.CO_DEPT_USER_KIND = 'U'
                        AND R.CO_DEPT_USER_ID = :uno
                    )
                    OR EXISTS (
                        SELECT 1 
                        FROM EAS_DOC_RECEIPOPER R
                        WHERE R.ONO = E.ONO
                        AND R.CO_DEPT_USER_KIND = 'D'
                        AND R.CO_DEPT_USER_ID IN (
                            SELECT DEPT_NO 
                            FROM SYS_DEPT_SET 
                            START WITH DEPT_NO = :deptNo 
                            CONNECT BY PRIOR DEPT_NO = PARENT_NO
                        )
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM EAS_APPR_STEP S
                        WHERE S.ONO = E.ONO
                        AND S.APPROVER = :uno
                        AND S.STATUS IS NULL
                        AND NOT EXISTS (
                            SELECT 1 FROM EAS_APPR_STEP P
                            WHERE P.ONO = S.ONO
                            AND P.STEP_ORDER < S.STEP_ORDER
                            AND (
                                P.STATUS IS NULL 
                                OR P.STATUS IN ('02', '04') 
                                OR P.IS_DELEGATE = 'Y'
                            )
                        )
                    )
                    {$readGradeCondition}
                )
                AND {$storePeriodCondiion} ";
        if($ddlSearchKind == "ALL") {
            $SQL .= "AND (
                            E.DOC_CD     LIKE '%' || :txtSearchValue || '%' OR
                            E.OUT_DOC_CD LIKE '%' || :txtSearchValue || '%' OR
                            E.PJT_NAME   LIKE '%' || :txtSearchValue || '%' OR
                            E.RECIPIENT  LIKE '%' || :txtSearchValue || '%' OR
                            E.REFERENCE  LIKE '%' || :txtSearchValue || '%' OR
                            E.TITLE      LIKE '%' || :txtSearchValue || '%' OR
                            E.SENDER     LIKE '%' || :txtSearchValue || '%' OR
                            U.USER_NAME  LIKE '%' || :txtSearchValue || '%'
                    )";
        } else {
            $SQL .= "AND ({$ddlSearchKind} LIKE '%' || :txtSearchValue || '%')";
        }
        $params = array(
            ":docType" => $docType,
            ":txtSearchValue" => $txtSearchValue,
            ":deptNo" => $deptNo,
            ":uno" => $user->uno
        );
        $db->query($SQL, $params);

        $totalCnt = $db->nf();
        $pageList = getPageList($pageNo, $totalCnt);
        $startRow = ($pageNo - 1) * $pageUnit + 1;
        $endRow = $pageNo * $pageUnit;

        //list
        $SQL = "SELECT * FROM (
                                WITH HIER_DEPT AS (
                                    SELECT DEPT_NO, DEPT_NAME
                                    FROM SYS_DEPT_SET
                                    WHERE LEVEL_NO = 3
                                    CONNECT BY PRIOR DEPT_NO = PARENT_NO
                                    START WITH LEVEL_NO = 3
                                )
                                SELECT E.ONO,
                                    CASE 
                                        WHEN E.OUT_DOC_CD IS NOT NULL THEN E.OUT_DOC_CD 
                                        ELSE E.DOC_CD 
                                    END AS DOC_CD, 
                                    E.READ_DEPT, E.RECIPIENT, E.REFERENCE, E.SENDER, E.WRITER,
                                    U.USER_NAME, A.APPROVERS, E.APPROVAL_KIND, E.APPROVAL_BASIS,
                                    TO_CHAR(E.ISSUE_DATE, 'YYYY-MM-DD') AS ISSUE_DATE, E.TITLE, E.PJT_NAME,
                                    F.ATCH_FILES, E.SEND_KIND, H.DEPT_NAME, FA.FILE_NAME, FA.FILE_SAVE, E.STORE_PERIOD, R.RG_VAL, ROP.CO_RECEIVER_LIST,
                                    ROW_NUMBER() OVER (ORDER BY E.ISSUE_DATE DESC, E.DOC_CD DESC) AS RNUM
                                FROM EAS_DOC_INFO E
                                INNER JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = E.WRITER
                                LEFT JOIN (
                                    SELECT S.ONO,
                                        LISTAGG(TO_CHAR(U.USER_NAME), ',') 
                                            WITHIN GROUP (ORDER BY STEP_ORDER) AS APPROVERS
                                    FROM EAS_APPR_STEP S
                                    INNER JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = S.APPROVER
                                    INNER JOIN EAS_DOC_INFO D ON D.ONO = S.ONO
                                    WHERE S.APPR_KIND = 'APPROVE'
                                    AND S.STATUS IN ('01', '05')  -- 결재완료 or 전결
                                    AND (S.APPROVER != D.WRITER AND (D.RECEIVE_USER IS NULL OR S.APPROVER != D.RECEIVE_USER))
                                    GROUP BY S.ONO
                                ) A ON A.ONO = E.ONO
                                LEFT JOIN HIER_DEPT H ON H.DEPT_NO = U.DEPT_ID
                                LEFT JOIN (
                                    SELECT FA.ONO,
                                        LISTAGG(FNO || ':' || FILE_NAME, ', ') 
                                            WITHIN GROUP (ORDER BY FNO) AS ATCH_FILES
                                    FROM EAS_DOC_ATCH FA
                                    WHERE IS_FINAL != 'Y'
                                    GROUP BY FA.ONO
                                ) F ON F.ONO = E.ONO
                                LEFT JOIN EAS_DOC_ATCH FA ON E.ONO = FA.ONO AND FA.IS_FINAL = 'Y'
                                INNER JOIN EAS_READ_AUTH R ON R.RG_CD = E.RG_CD
                                LEFT JOIN (
                                            SELECT R.ONO, LISTAGG(
                                                    CASE 
                                                        WHEN R.CO_DEPT_USER_KIND = 'U' THEN U.USER_NAME
                                                        WHEN R.CO_DEPT_USER_KIND = 'D' THEN V.DEPT_NAME
                                                    END,
                                                    '/') WITHIN GROUP (ORDER BY R.CO_DEPT_USER_KIND, R.CO_DEPT_USER_ID) AS CO_RECEIVER_LIST
                                            FROM EAS_DOC_RECEIPOPER R
                                            LEFT JOIN COMMON.V_BIZ_USER_SET U 
                                                ON R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = U.UNO
                                            LEFT JOIN V_SYS_DEPT_SET V 
                                                ON R.CO_DEPT_USER_KIND = 'D' AND R.CO_DEPT_USER_ID = V.DEPT_NO
                                            GROUP BY R.ONO
                                ) ROP ON ROP.ONO = E.ONO
                                WHERE E.DOC_TYPE = :docType
                                AND (E.STATUS IS NULL OR E.STATUS != '06')
                                AND (E.STATUS IS NULL OR E.STATUS != '02') 
                                AND (E.STATUS IS NULL OR E.STATUS != '04') 
                                AND (
                                    E.WRITER = :uno
                                    OR E.RECEIVE_USER = :uno
                                    OR EXISTS (
                                        SELECT 1 FROM EAS_APPR_STEP S
                                        WHERE S.ONO = E.ONO AND S.APPROVER = :uno
                                    )
                                    OR EXISTS (
                                        SELECT 1 FROM EAS_DOC_RECEIPOPER R
                                        WHERE R.ONO = E.ONO
                                        AND R.CO_DEPT_USER_KIND = 'U'
                                        AND R.CO_DEPT_USER_ID = :uno
                                    )
                                    OR EXISTS (
                                        SELECT 1 
                                        FROM EAS_DOC_RECEIPOPER R
                                        WHERE R.ONO = E.ONO
                                        AND R.CO_DEPT_USER_KIND = 'D'
                                        AND R.CO_DEPT_USER_ID IN (
                                            SELECT DEPT_NO
                                            FROM SYS_DEPT_SET
                                            START WITH DEPT_NO = :deptNo
                                            CONNECT BY PRIOR DEPT_NO = PARENT_NO
                                        )
                                    )
                                    OR EXISTS (
                                        SELECT 1
                                        FROM EAS_APPR_STEP S
                                        WHERE S.ONO = E.ONO
                                        AND S.APPROVER = :uno
                                        AND S.STATUS IS NULL
                                        AND NOT EXISTS (
                                            SELECT 1 FROM EAS_APPR_STEP P
                                            WHERE P.ONO = S.ONO
                                                AND P.STEP_ORDER < S.STEP_ORDER
                                                AND (
                                                    P.STATUS IS NULL
                                                    OR P.STATUS IN ('02', '04')
                                                    OR P.IS_DELEGATE = 'Y'
                                                )
                                        )
                                    )
                                    {$readGradeCondition}
                                ) 
                                AND {$storePeriodCondiion}";
        if($ddlSearchKind == "ALL") {
            $SQL .= "AND (
                            E.DOC_CD     LIKE '%' || :txtSearchValue || '%' OR
                            E.OUT_DOC_CD LIKE '%' || :txtSearchValue || '%' OR
                            E.PJT_NAME   LIKE '%' || :txtSearchValue || '%' OR
                            E.RECIPIENT  LIKE '%' || :txtSearchValue || '%' OR
                            E.REFERENCE  LIKE '%' || :txtSearchValue || '%' OR
                            E.TITLE      LIKE '%' || :txtSearchValue || '%' OR
                            E.SENDER     LIKE '%' || :txtSearchValue || '%' OR
                            U.USER_NAME  LIKE '%' || :txtSearchValue || '%'
                    )";
        } else {
            $SQL .= "AND ({$ddlSearchKind} LIKE '%' || :txtSearchValue || '%') ";
        }
        $SQL .= ") ";
        $SQL .= "WHERE RNUM BETWEEN :startRow AND :endRow ";

        $params = array(
            ":docType" => $docType,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow,
            ":deptNo" => $deptNo,
            ":uno" => $user->uno
        );
        $db->query($SQL, $params);
        while($db->next_record()) {
            $row = $db->Record;

            // 심의, 승인
            $approvers = array_filter(array_map('trim', explode(',', $row["approvers"])));
            $status = $row["status"];
            $count = count($approvers);

            // 기본값 설정
            $consider = '';
            $director = '';

            if ($count === 1) {
                if ($status == "05") {
                    $consider = $approvers[0];
                    $director = $approvers[0];
                } else {
                    $consider = $approvers[0];
                }
            } elseif ($count === 2) {
                $consider = $approvers[0];
                $director = $approvers[1];
            } elseif ($count > 2) {
                $director = array_pop($approvers);
                $consider = implode(', ', $approvers);
            }

            $apprKind = '';
            if($row["appr_kind"] == "ELEC") {
                $apprKind = "전자결재";
            } else if($row["appr_kind"] == "WRIT") {
                $apprKind = "서면결재";
            } else if($row["appr_kind"] == "MAIL") {
                $apprKind = "메일결재";
            }

            if(!$row["pjt_name"]) {
                $type = "본사";
            } else {
                $type = "Project";
            }

            //순번
            $seq = ($totalCnt - $row["rnum"]) + 1;

            $approvalKind = $row["approval_kind"];
            if($approvalKind == "ELEC") {
                $txtApprovalKind = "전자결재";
            } else if($approvalKind == "EMAIL") {
                $txtApprovalKind = "E-MAIL";
            } else if($approvalKind == "WRIT") {
                $txtApprovalKind = "서면결재";
            } else if($approvalKind == "VIEW") {
                $txtApprovalKind = "공람";
            }

            $sendKind = str_replace("|", ", ", $row["send_kind"]);

            //분류
            if($row["pjt_name"]) {
                $docScope = "프로젝트";
                $docGroup = $row["pjt_name"];
            } else {
                $docScope = "본사";
                $docGroup = $row["dept_name"];
            }

            if($row["out_doc_cd"]) {
                $docCd = $row["out_doc_cd"];
            } else {
                $docCd = $row["doc_cd"];
            }

            //첨부파일
            $atchFiles = $row["atch_files"];
            // 문자열을 배열로 변환
            $fileNames = explode(', ', $atchFiles);
            
            $atchPaths = [];
            foreach ($fileNames as $fileItem) {

                [$fno, $fileName] = explode(':', $fileItem, 2);

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if ($fileExt === 'txt') {
                    $fileLink = "/gw/cm/cm_file_download.php?mKind=OL&fid={$fno}";
                } else {
                    $fileLink = buildFileUrl($urlPathAbsolute . "OL/{$row["ono"]}/" , $fileName);
                }

                $atchPaths[$fileName] = $fileLink;
            }

            $storePeriod = $row["store_period"];
            if (preg_match('/^\d+/', $row["store_period"])) {
                $storePeriod = $row["store_period"] . '년';
            }

            $infoList[] = array(
                "ono" => $row["ono"],
                "docCd" => $docCd,
                "issueDate" => $row["issue_date"],
                "title" => $row["title"],
                "recipient" => $row["recipient"],
                "reference" => $row["reference"],
                "sender" => $row["sender"],
                "userName" => $row["user_name"],
                "deptName" => $row["dept_name"],
                // 심의
                "consider" => $consider,
                // 승인
                "director" => $director,
                "apprKind" => $apprKind,
                "approvalBasis" => $row["approval_basis"],
                "type" => $type,
                "pjtNm" => $row["pjt_name"],
                "atchFiles" => $row["atch_files"],
                "seq" => $seq,
                "approvalKind" => $txtApprovalKind,
                "sendKind" => $sendKind,
                "docGroup" => $docGroup,
                "fileNm" => $row["file_name"],
                "finalUrl" => buildFileUrl($urlPathAbsolute . "OL/{$row["ono"]}/" , $row["file_save"]),
                "atchPaths" => $atchPaths,
                "storePeriod" => $storePeriod,
                "rgCd" => $row["rg_val"],
                "coReceiverList" => $row["co_receiver_list"]
            );
        }
    } else if($docType == "R") {
        $ddlReplyKind = $_POST["ddlReplyKind"];
        //cnt
        $SQL = "WITH HIER_DEPT AS (
                    SELECT DEPT_NO, DEPT_NAME
                    FROM SYS_DEPT_SET
                    WHERE LEVEL_NO = 3
                    CONNECT BY PRIOR DEPT_NO = PARENT_NO
                    START WITH LEVEL_NO = 3
                ),
                EBR_STEP AS (
                    SELECT ONO, EBR_PROCESS
                    FROM (
                        SELECT ONO, EBR_PROCESS,
                            ROW_NUMBER() OVER (PARTITION BY ONO ORDER BY STEP_ORDER DESC) AS RN
                        FROM EAS_APPR_STEP
                        WHERE EBR_PROCESS IS NOT NULL
                    )
                    WHERE RN = 1
                ),
                ROP AS (
                    SELECT R.ONO,
                        LISTAGG(
                            CASE 
                                WHEN R.CO_DEPT_USER_KIND = 'U' THEN U.USER_NAME
                                WHEN R.CO_DEPT_USER_KIND = 'D' THEN V.DEPT_NAME
                            END,
                            '/'
                        ) WITHIN GROUP (ORDER BY R.CO_DEPT_USER_KIND, R.CO_DEPT_USER_ID) AS CO_RECEIVER_LIST
                    FROM EAS_DOC_RECEIPOPER R
                    LEFT JOIN COMMON.V_BIZ_USER_SET U 
                        ON R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = U.UNO
                    LEFT JOIN V_SYS_DEPT_SET V 
                        ON R.CO_DEPT_USER_KIND = 'D' AND R.CO_DEPT_USER_ID = V.DEPT_NO
                    GROUP BY R.ONO
                ),
                DOC_REF_STATUS AS (
                    SELECT REF_DOC, REF_ONO,
                        CASE
                            WHEN EXISTS (
                                SELECT 1
                                FROM EAS_APPR_STEP S
                                WHERE S.ONO = REF_ONO
                                AND S.STATUS = '05'
                            ) THEN 'O'
                            WHEN (
                                SELECT COUNT(*) FROM EAS_APPR_STEP WHERE ONO = REF_ONO
                            ) = (
                                SELECT COUNT(*) FROM EAS_APPR_STEP WHERE ONO = REF_ONO AND STATUS IN ('01', '03')
                            ) THEN 'O'
                            ELSE 'X'
                        END AS REF_DOC_DONE
                    FROM (
                        SELECT R.REF_DOC, D.ONO AS REF_ONO,
                            ROW_NUMBER() OVER (PARTITION BY R.REF_DOC ORDER BY D.ISSUE_DATE DESC) AS RN
                        FROM EAS_DOC_REF R
                        JOIN EAS_DOC_INFO D ON R.ONO = D.ONO
                    ) SUB
                    WHERE RN = 1
                )
                SELECT 
                    E.ONO, E.DOC_CD, E.OUT_DOC_CD, E.SENDER, E.TITLE, E.RECIPIENT,
                    TO_CHAR(E.RECEIVE_DATE, 'YYYY-MM-DD') AS RECEIVE_DATE,
                    E.RECEIVE_USER, U.USER_NAME,
                    H.DEPT_NAME, E.RECEIVE_KIND, E.APPROVAL_BASIS, E.REFERENCE, E.PJT_NAME,
                    F.ATCH_FILES, S.EBR_PROCESS, RA.RG_VAL, ROP.CO_RECEIVER_LIST,
                    NVL(RS.REF_DOC_DONE, 'X') AS REF_DOC_DONE
                FROM EAS_DOC_INFO E
                LEFT JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = E.RECEIVE_USER
                LEFT JOIN (
                    SELECT S.ONO,
                        LISTAGG(TO_CHAR(U.USER_NAME), ',') 
                            WITHIN GROUP (ORDER BY S.STEP_ORDER) AS APPROVERS
                    FROM EAS_APPR_STEP S
                    INNER JOIN COMMON.V_BIZ_USER_SET U 
                            ON U.UNO = S.APPROVER
                    INNER JOIN EAS_DOC_INFO D 
                            ON D.ONO = S.ONO
                    WHERE S.APPR_KIND = 'APPROVE'
                    AND S.STATUS IN ('01','05')                      -- 결재완료 or 전결
                    AND S.APPROVER <> D.RECEIVE_USER
                    GROUP BY S.ONO
                ) A ON A.ONO = E.ONO
                LEFT JOIN (
                    SELECT ONO,
                        LISTAGG(FNO || ':' || FILE_NAME, ', ') 
                            WITHIN GROUP (ORDER BY FNO) AS ATCH_FILES
                    FROM EAS_DOC_ATCH
                    WHERE IS_FINAL != 'Y'
                    GROUP BY ONO
                ) F ON F.ONO = E.ONO
                LEFT JOIN HIER_DEPT H ON H.DEPT_NO = U.DEPT_ID
                LEFT JOIN EBR_STEP S ON S.ONO = E.ONO
                LEFT JOIN EAS_READ_AUTH RA ON RA.RG_CD = E.RG_CD
                LEFT JOIN ROP ON ROP.ONO = E.ONO
                LEFT JOIN DOC_REF_STATUS RS ON RS.REF_DOC = E.ONO
                WHERE E.DOC_TYPE = :docType
                AND NVL(E.STATUS, 'NA') NOT IN ('06','02','04')
                AND (
                    E.WRITER = :uno
                    OR E.RECEIVE_USER = :uno
                    OR EXISTS (
                        SELECT 1 FROM EAS_APPR_STEP S
                        WHERE S.ONO = E.ONO AND S.APPROVER = :uno
                    )
                    OR EXISTS (
                        SELECT 1 FROM EAS_DOC_RECEIPOPER R
                        WHERE R.ONO = E.ONO
                        AND R.CO_DEPT_USER_KIND = 'U'
                        AND R.CO_DEPT_USER_ID = :uno
                    )
                    OR EXISTS (
                        SELECT 1 FROM EAS_DOC_RECEIPOPER R
                        WHERE R.ONO = E.ONO
                        AND R.CO_DEPT_USER_KIND = 'D'
                        AND R.CO_DEPT_USER_ID IN (
                            SELECT DEPT_NO 
                            FROM SYS_DEPT_SET 
                            START WITH DEPT_NO = :deptNo 
                            CONNECT BY PRIOR DEPT_NO = PARENT_NO
                        )
                    )
                    {$readGradeCondition}
                ) 
                AND {$storePeriodCondiion} ";
        if($ddlSearchKind == "ALL") {
            $SQL .= "AND (
                            E.DOC_CD     LIKE '%' || :txtSearchValue || '%' OR
                            E.PJT_NAME   LIKE '%' || :txtSearchValue || '%' OR
                            E.RECIPIENT  LIKE '%' || :txtSearchValue || '%' OR
                            E.REFERENCE  LIKE '%' || :txtSearchValue || '%' OR
                            E.TITLE      LIKE '%' || :txtSearchValue || '%' OR
                            E.SENDER     LIKE '%' || :txtSearchValue || '%' OR
                            U.USER_NAME  LIKE '%' || :txtSearchValue || '%'
                    )";
        } else {
            $SQL .= "AND ({$ddlSearchKind} LIKE '%' || :txtSearchValue || '%')";
        }
        if($ddlReplyKind != "all") {
            $SQL .= "AND NVL(RS.REF_DOC_DONE, 'X') = :refDocDone ";
        }

        $params = array(
            ":docType" => $docType,
            ":txtSearchValue" => $txtSearchValue,
            ":deptNo" => $deptNo,
            ":uno" => $user->uno,
            ":refDocDone" => $ddlReplyKind
        );
        $db->query($SQL, $params);
        $totalCnt = $db->nf();
        $pageList = getPageList($pageNo, $totalCnt);
        $startRow = ($pageNo - 1) * $pageUnit + 1;
        $endRow = $pageNo * $pageUnit;

        //list
        $SQL = "SELECT * FROM (
                                WITH HIER_DEPT AS (
                                    SELECT DEPT_NO, DEPT_NAME
                                    FROM SYS_DEPT_SET
                                    WHERE LEVEL_NO = 3
                                    CONNECT BY PRIOR DEPT_NO = PARENT_NO
                                    START WITH LEVEL_NO = 3
                                ),
                                EBR_STEP AS (
                                    SELECT ONO, EBR_PROCESS
                                    FROM (
                                        SELECT ONO, EBR_PROCESS,
                                            ROW_NUMBER() OVER (PARTITION BY ONO ORDER BY STEP_ORDER DESC) AS RN
                                        FROM EAS_APPR_STEP
                                        WHERE EBR_PROCESS IS NOT NULL
                                    )
                                    WHERE RN = 1
                                ),
                                ROP AS (
                                    SELECT R.ONO,
                                        LISTAGG(
                                            CASE 
                                                WHEN R.CO_DEPT_USER_KIND = 'U' THEN U.USER_NAME
                                                WHEN R.CO_DEPT_USER_KIND = 'D' THEN V.DEPT_NAME
                                            END,
                                            '/'
                                        ) WITHIN GROUP (ORDER BY R.CO_DEPT_USER_KIND, R.CO_DEPT_USER_ID) AS CO_RECEIVER_LIST
                                    FROM EAS_DOC_RECEIPOPER R
                                    LEFT JOIN COMMON.V_BIZ_USER_SET U 
                                        ON R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = U.UNO
                                    LEFT JOIN V_SYS_DEPT_SET V 
                                        ON R.CO_DEPT_USER_KIND = 'D' AND R.CO_DEPT_USER_ID = V.DEPT_NO
                                    GROUP BY R.ONO
                                ),
                                DOC_REF_STATUS AS (
                                    SELECT REF_DOC, REF_ONO,
                                        CASE
                                            WHEN EXISTS (
                                                SELECT 1
                                                FROM EAS_APPR_STEP S
                                                WHERE S.ONO = REF_ONO
                                                AND S.STATUS = '05'
                                            ) THEN 'O'
                                            WHEN (
                                                SELECT COUNT(*) FROM EAS_APPR_STEP WHERE ONO = REF_ONO
                                            ) = (
                                                SELECT COUNT(*) FROM EAS_APPR_STEP WHERE ONO = REF_ONO AND STATUS IN ('01', '03')
                                            ) THEN 'O'
                                            ELSE 'X'
                                        END AS REF_DOC_DONE
                                    FROM (
                                        SELECT R.REF_DOC, D.ONO AS REF_ONO,
                                            ROW_NUMBER() OVER (PARTITION BY R.REF_DOC ORDER BY D.ISSUE_DATE DESC) AS RN
                                        FROM EAS_DOC_REF R
                                        JOIN EAS_DOC_INFO D ON R.ONO = D.ONO
                                    ) SUB
                                    WHERE RN = 1
                                )
                                SELECT 
                                    E.ONO, E.DOC_CD, E.OUT_DOC_CD, E.SENDER, E.TITLE, E.RECIPIENT,
                                    TO_CHAR(E.RECEIVE_DATE, 'YYYY-MM-DD') AS RECEIVE_DATE,
                                    E.RECEIVE_USER, U.USER_NAME,
                                    H.DEPT_NAME, E.RECEIVE_KIND, E.APPROVAL_BASIS, E.REFERENCE, E.PJT_NAME,
                                    F.ATCH_FILES, S.EBR_PROCESS, RA.RG_VAL, ROP.CO_RECEIVER_LIST, E.APPROVAL_KIND, E.STORE_PERIOD, A.APPROVERS,
                                    NVL(RS.REF_DOC_DONE, 'X') AS REF_DOC_DONE, ROW_NUMBER() OVER (ORDER BY E.RECEIVE_DATE DESC, E.DOC_CD DESC) AS RNUM
                                FROM EAS_DOC_INFO E
                                LEFT JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = E.RECEIVE_USER
                                LEFT JOIN (
                                    SELECT S.ONO,
                                        LISTAGG(TO_CHAR(U.USER_NAME), ',') 
                                            WITHIN GROUP (ORDER BY S.STEP_ORDER) AS APPROVERS
                                    FROM EAS_APPR_STEP S
                                    INNER JOIN COMMON.V_BIZ_USER_SET U 
                                            ON U.UNO = S.APPROVER
                                    INNER JOIN EAS_DOC_INFO D 
                                            ON D.ONO = S.ONO
                                    WHERE S.APPR_KIND = 'APPROVE'
                                    AND S.STATUS IN ('01','05')                      -- 결재완료 or 전결
                                    AND S.APPROVER <> D.RECEIVE_USER
                                    GROUP BY S.ONO
                                ) A ON A.ONO = E.ONO
                                LEFT JOIN (
                                    SELECT ONO,
                                        LISTAGG(FNO || ':' || FILE_NAME, ', ') 
                                            WITHIN GROUP (ORDER BY FNO) AS ATCH_FILES
                                    FROM EAS_DOC_ATCH
                                    WHERE IS_FINAL != 'Y'
                                    GROUP BY ONO
                                ) F ON F.ONO = E.ONO
                                LEFT JOIN HIER_DEPT H ON H.DEPT_NO = U.DEPT_ID
                                LEFT JOIN EBR_STEP S ON S.ONO = E.ONO
                                LEFT JOIN EAS_READ_AUTH RA ON RA.RG_CD = E.RG_CD
                                LEFT JOIN ROP ON ROP.ONO = E.ONO
                                LEFT JOIN DOC_REF_STATUS RS ON RS.REF_DOC = E.ONO
                                WHERE E.DOC_TYPE = :docType
                                AND NVL(E.STATUS, 'NA') NOT IN ('06','02','04')
                                AND (
                                    E.WRITER = :uno
                                    OR E.RECEIVE_USER = :uno
                                    OR EXISTS (
                                        SELECT 1 FROM EAS_APPR_STEP S
                                        WHERE S.ONO = E.ONO AND S.APPROVER = :uno
                                    )
                                    OR EXISTS (
                                        SELECT 1 FROM EAS_DOC_RECEIPOPER R
                                        WHERE R.ONO = E.ONO
                                        AND R.CO_DEPT_USER_KIND = 'U'
                                        AND R.CO_DEPT_USER_ID = :uno
                                    )
                                    OR EXISTS (
                                        SELECT 1 FROM EAS_DOC_RECEIPOPER R
                                        WHERE R.ONO = E.ONO
                                        AND R.CO_DEPT_USER_KIND = 'D'
                                        AND R.CO_DEPT_USER_ID IN (
                                            SELECT DEPT_NO 
                                            FROM SYS_DEPT_SET 
                                            START WITH DEPT_NO = :deptNo 
                                            CONNECT BY PRIOR DEPT_NO = PARENT_NO
                                        )
                                    )
                                    {$readGradeCondition}
                                ) 
                                AND {$storePeriodCondiion} ";
        if($ddlSearchKind == "ALL") {
            $SQL .= "AND (
                        E.DOC_CD     LIKE '%' || :txtSearchValue || '%' OR
                        E.PJT_NAME   LIKE '%' || :txtSearchValue || '%' OR
                        E.RECIPIENT  LIKE '%' || :txtSearchValue || '%' OR
                        E.REFERENCE  LIKE '%' || :txtSearchValue || '%' OR
                        E.TITLE      LIKE '%' || :txtSearchValue || '%' OR
                        E.SENDER     LIKE '%' || :txtSearchValue || '%' OR
                        U.USER_NAME  LIKE '%' || :txtSearchValue || '%'
                )";
        } else {
            $SQL .= "AND ({$ddlSearchKind} LIKE '%' || :txtSearchValue || '%') ";
        }
        if($ddlReplyKind != "all") {
            $SQL .= "AND NVL(RS.REF_DOC_DONE, 'X') = :refDocDone ";
        }

        $SQL .= ") ";
        $SQL .= "WHERE RNUM BETWEEN :startRow AND :endRow ";

        $params = array(
            ":docType" => $docType,
            ":txtSearchValue" => $txtSearchValue,
            ":pageUnit" => $pageUnit,
            ":startRow" => $startRow,
            ":endRow" => $endRow,
            ":deptNo" => $deptNo,
            ":uno" => $user->uno,
            ":refDocDone" => $ddlReplyKind
        );

        $db->query($SQL, $params);
        while($db->next_record()) {
            $row = $db->Record;

            //분류
            if($row["pjt_name"]) {
                $docScope = "프로젝트";
                $docGroup = $row["pjt_name"];
            } else {
                $docScope = "본사";
                $docGroup = $row["dept_name"];
            }

            //순번
            $seq = ($totalCnt - $row["rnum"]) + 1;

            $approvalKind = $row["approval_kind"];
            if($approvalKind == "ELEC") {
                $txtApprovalKind = "전자결재";
            } else if($approvalKind == "EMAIL") {
                $txtApprovalKind = "E-MAIL";
            } else if($approvalKind == "WRIT") {
                $txtApprovalKind = "서면결재";
            } else if($approvalKind == "VIEW") {
                $txtApprovalKind = "공람";
            }

            //첨부파일
            $atchFiles = $row["atch_files"];

            // 문자열을 배열로 변환
            $fileNames = explode(', ', $atchFiles);
            
            $atchPaths = [];
            foreach ($fileNames as $fileItem) {

                [$fno, $fileName] = explode(':', $fileItem, 2);

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if ($fileExt === 'txt') {
                    $fileLink = "/gw/cm/cm_file_download.php?mKind=OL&fid={$fno}";
                } else {
                    $fileLink = $urlPathAbsolute . "OL/{$row["ono"]}/" . $fileName;
                }

                $atchPaths[$fileName] = $fileLink;
            }

            $storePeriod = $row["store_period"];
            if (preg_match('/^\d+/', $row["store_period"])) {
                $storePeriod = $row["store_period"] . '년';
            }

            // 심의, 승인
            $approvers = array_filter(array_map('trim', explode(',', $row["approvers"])));
            $status = $row["status"];
            $count = count($approvers);

            // 기본값 설정
            $consider = '';
            $director = '';

            if ($count === 1) {
                if ($status == "05") {
                    $consider = $approvers[0];
                    $director = $approvers[0];
                } else {
                    $consider = $approvers[0];
                }
            } elseif ($count === 2) {
                $consider = $approvers[0];
                $director = $approvers[1];
            } elseif ($count > 2) {
                $director = array_pop($approvers);
                $consider = implode(', ', $approvers);
            }

            $infoList[] = array(
                "ono" => $row["ono"],
                "docCd" => $row["doc_cd"],
                "outDocCd" => $row["out_doc_cd"],
                "sender" => $row["sender"],
                "title" => $row["title"],
                "recipient" => $row["recipient"],
                "reference" => $row["reference"],
                "receiveDate" => $row["receive_date"],
                "receiveUser" => $row["receive_user"],
                "receiveKind" => $row["receive_kind"],
                "userName" => $row["user_name"],
                "deptName" => $row["dept_name"],
                "approvalBasis" => $row["approval_basis"],
                "atchFiles" => $row["atch_files"],
                "docScope" => $docScope,
                "seq" => $seq,
                "docGroup" => $docGroup,
                "process" => $row["ebr_process"],
                "approvalKind" => $txtApprovalKind,
                "rgVal" => $row["rg_val"],
                "receiverList" => $row["co_receiver_list"],
                "atchPaths" => $atchPaths,
                "refDone" => $row["ref_doc_done"],
                "storePeriod" => $storePeriod,
                // 심의
                "consider" => $consider,
                // 승인
                "director" => $director
            );
        }
    }

    $result = array(
        "infoList" => $infoList,
        "pageNo" => $pageNo,
        "pageList" => $pageList
    );

    echo json_encode($result);
} else if($mode == "RELATED_EB") {
    $ddlSearchKind = $_POST["searchDocKind"];
    $searchEBKindText = $_POST["searchEBKindText"];

    $ebrList = array();
    $SQL = "SELECT D.ONO, D.DOC_CD, D.OUT_DOC_CD, D.TITLE, D.RECEIVE_DATE
            FROM EAS_DOC_INFO D
            WHERE D.DOC_TYPE = 'R'
            AND EXISTS (
                SELECT 1
                FROM (
                    -- 전결 포함 여부 확인
                    SELECT S.ONO
                    FROM EAS_APPR_STEP S
                    WHERE S.STATUS = '05'
                    GROUP BY S.ONO
                    UNION
                    -- 모든 결재자가 '01' 또는 '03'만 가진 경우
                    SELECT S.ONO
                    FROM EAS_APPR_STEP S
                    GROUP BY S.ONO
                    HAVING COUNT(*) = COUNT(CASE WHEN S.STATUS IN ('01', '03') THEN 1 END)
                ) T
                WHERE T.ONO = D.ONO ";

    if($ddlSearchKind == "all") {
        $SQL .= "AND (
                        D.DOC_CD     LIKE '%' || :txtSearchValue || '%' OR
                        D.OUT_DOC_CD   LIKE '%' || :txtSearchValue || '%' OR
                        D.TITLE      LIKE '%' || :txtSearchValue || '%'
                )";
    } else {
        $SQL .= "AND ({$ddlSearchKind} LIKE '%' || :txtSearchValue || '%')";
    }
    $SQL .= ") ORDER BY D.RECEIVE_DATE DESC, DOC_CD DESC";
    $params = array(
        ":txtSearchValue" => $searchEBKindText
    );
    $db->query($SQL, $params);
    while($db->next_record()) {
        $row = $db->Record;

        $ebrList[] = array(
            "ono" => $row["ono"],
            "docCd" => $row["doc_cd"],
            "outDocCd" => $row["out_doc_cd"],
            "title" => $row["title"],
            "receiveDate" => $row["receive_date"]
        );
    }

    $result = array(
        "ebrList" => $ebrList
    );

    echo json_encode($result);
} else if($mode == "DEL_EB") {
    $ono = $_POST["ono"];
    $proceed = true;

    $SQL = "DELETE FROM EAS_DOC_INFO
            WHERE ONO = :ono";
    $params = array(
        ":ono" => $ono
    );
    if(!$db->query($SQL, $params)) {
        $proceed = false;
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => '삭제되었습니다.'
    );

    echo json_encode($result);
}

// 결재선 변경
function changeEasLine($ono, $appAgrLine, $recipientIds) {
    global $user;
    global $db;

    $errCnt = 0;
    // 삭제 후 재 등록
    $SQL = "DELETE FROM EAS_APPR_STEP
            WHERE ONO = :ono";
    $params = array(
        ":ono" => $ono
    );
    $db->query($SQL, $params);

    $SQL = "DELETE FROM EAS_DOC_RECEIPOPER
            WHERE ONO = :ono";
    $db->query($SQL, $params);
    if(count($appAgrLine) > 0) {
        $approveIndexes = [];
        foreach ($appAgrLine as $idx => $lineInfo) {
            $parts = explode('|', $lineInfo);
            if (isset($parts[6]) && $parts[6] == 1) {
                $approveIndexes[] = $idx;
            }
        }

        $lastApproveIdx = end($approveIndexes);

        foreach($appAgrLine as $idx => $lineInfo) {
            $parts = explode('|', $lineInfo);
            list($userId, $coId, $deptId, $userNm, $gradeNm, $dutyNm, $kind, $level) = $parts;

            // 결재
            if($kind == 1) {
                $apprKind = 'APPROVE';
                $deleAuth = ($idx !== $lastApproveIdx) ? 'Y' : 'N';
            }
            // 합의 
            else {
                $apprKind = 'AGREE';
                $deleAuth = 'N';
            }

            $SQL = "INSERT INTO EAS_APPR_STEP (ONO, STEP_ORDER, APPROVER, APPR_KIND, DELEGATE_AUTH, REG_USER) 
                    VALUES (:ono, :stepOrder, :approver, :apprKind, :deleAuth, :regUser)";
            $params = array(
                ":ono" => $ono,
                ":stepOrder" => $level,
                ":approver" => $userId,
                ":apprKind" => $apprKind,
                ":deleAuth" => $deleAuth,
                ":regUser" => $user->uno
            );
            if(!$db->query($SQL, $params)) {
                $errCnt++;
            }
        }
    }

    // 수신참조
    if($recipientIds) {
        $recipientArray = explode('/', ltrim($recipientIds, '/'));
        foreach($recipientArray as $recipientInfo) {
            if (strpos($recipientInfo, "D") === 0) {
                $kind = "D";
                $id = str_replace("D", "", $recipientInfo);
            } else {
                $kind = "U";
                list($id, $coId, $deptId) = explode("|", $recipientInfo);
            }
    
            $SQL = "INSERT INTO EAS_DOC_RECEIPOPER (ONO, CO_DEPT_USER_KIND, CO_DEPT_USER_ID, REG_USER) 
                    VALUES (:ono, :co_dept_user_kind, :co_dept_user_id, :regUser)";
            $params = array(
                ":ono" => $ono,
                ":co_dept_user_kind" => $kind,
                ":co_dept_user_id" => $id,
                ":regUser" => $user->uno
            );
            if(!$db->query($SQL, $params)) {
                $errCnt++;
            }
        }
    }

    $result = true;
    if($errCnt > 0) {
        $result = false;
    }

    return $result;
}

function makeSafeFileName($name) {
    // 확장자 분리
    $info = pathinfo($name);
    $base = $info['filename'];
    $ext  = isset($info['extension']) && $info['extension'] !== '' ? ('.' . $info['extension']) : '';

    // 위험 문자 치환: # / \ ? % * : | " < > 등
    $base = preg_replace('/[\/\\\\\?\%\*\:\|\"<>\#]/u', '_', $base);
    // 공백 정리
    $base = trim(preg_replace('/\s+/', ' ', $base));

    // 길이 제한 등 필요시 추가
    return $base . $ext;
}

function buildFileUrl($base, $relativePath) {
    // $base = "/Upload/1323"; $relativePath = "OL/{$ono}/파일명.pdf"
    $parts = explode('/', trim($relativePath, '/'));
    $encoded = array_map('rawurlencode', $parts);
    return rtrim($base, '/') . '/' . implode('/', $encoded);
}
?>
