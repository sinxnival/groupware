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

if($mode == "DETAIL_SHOW") {
    $ono = $_POST["showOno"];

    $appLine = array();
    $appLine["app"] = [];
    $appLine["agr"] = [];
    $SQL = "SELECT APPROVER, APPR_KIND, STEP_ORDER, IS_SIGN, SIGN_IMG, TO_CHAR(APPROVE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS APPROVE_DATE, STATUS, U.USER_NAME, U.COMPANY_ID, U.DEPT_ID, U.DUTY_NAME, U.JOBDUTY_NAME
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

        $appAgrValue = $row["approver"] . "|" . $row["company_id"] . "|" . $row["dept_id"] . "|" . $row["user_name"] . "|" . $row["duty_name"] . "|" . $row["jobduty_name"] . "|" . $appNum . "|" . $row["step_order"];

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

    $docInfo = array();
    $SQL = "SELECT DOC_CD, RECIPIENT, REFERENCE, TO_CHAR(I.ISSUE_DATE, 'YYYY-MM-DD') AS ISSUE_DATE, 
                TITLE, CONTENT, SENDER, R.RG_VAL, D.DEPT_NAME, ROP.CO_RECEIVER_LIST, 
                DOC_TYPE, OUT_DOC_CD, RECEIVE_KIND, PJT_NAME, TO_CHAR(I.RECEIVE_DATE, 'YYYY-MM-DD') AS RECEIVE_DATE, 
                APPROVAL_KIND, SEND_KIND, APPROVAL_BASIS, AST.EBR_PROCESS, SD_CD, I.STATUS, I.STORE_PERIOD, UW.USER_NAME AS WRITER, UR.USER_NAME AS RECEIVE_USER  
            FROM EAS_DOC_INFO I
            INNER JOIN EAS_READ_AUTH R ON R.RG_CD = I.RG_CD
            LEFT OUTER JOIN V_SYS_DEPT_SET D ON D.DEPT_NO = I.READ_DEPT
            LEFT JOIN SYS_USER_SET UW ON UW.UNO = I.WRITER
            LEFT JOIN SYS_USER_SET UR ON UR.UNO = I.RECEIVE_USER
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
            LEFT JOIN (
                        SELECT E1.ONO, E1.EBR_PROCESS
                        FROM EAS_APPR_STEP E1
                        WHERE E1.EBR_PROCESS IS NOT NULL
                        AND E1.STEP_ORDER = (
                            SELECT MAX(E2.STEP_ORDER)
                            FROM EAS_APPR_STEP E2
                            WHERE E2.ONO = E1.ONO
                                AND E2.EBR_PROCESS IS NOT NULL
                        )
            ) AST ON AST.ONO = I.ONO
            WHERE I.ONO = :ono";

    $params = [
        [":ono", $ono, false]
    ];

    $columns = [
        ["doc_cd", false],
        ["sd_cd", false],
        ["recipient", false],
        ["reference", false],
        ["issue_date", false],
        ["title", false],
        ["content", true],
        ["content_footer", true],
        ["sender", false],
        ["rg_val", false],
        ["dept_name", false],
        ["co_receiver_list", false],
        ["doc_type", false],
        ["out_doc_cd", false],
        ["receive_kind", false],
        ["pjt_name", false],
        ["receive_date", false],
        ["approval_kind", false],
        ["send_kind", false],
        ["approval_basis", false],
        ["ebr_process", false],
        ["status", false],
        ["store_period", false],
        ["writer", false],
        ["receive_user", false]
    ];

    $ociDB->query_lob($SQL, $params, $columns);
    // $ociDB->next_record();
    $row = $ociDB->RecordAll[0];

    if($row["pjt_name"]) {
        $docScope = "프로젝트";
    } else {
        $docScope = "본사";
    }

    $approvalKind = iconvEuckrToUtf8($row["approval_kind"]);
    $sendKind = iconvEuckrToUtf8($row["send_kind"]);
    $sendKind = str_replace("|", ", ", $sendKind);

    if($approvalKind == "ELEC") {
        $txtApprovalKind = "전자결재";
    } else if($approvalKind == "EMAIL") {
        $txtApprovalKind = "E-MAIL";
    } else if($approvalKind == "WRIT") {
        $txtApprovalKind = "서면결재";
    } else if($approvalKind == "VIEW") {
        $txtApprovalKind = "공람";
    }

    $storePeriod = iconvEuckrToUtf8($row["store_period"]);
    if (preg_match('/^\d+/', $row["store_period"])) {
        $storePeriod = iconvEuckrToUtf8($row["store_period"]) . '년';
    }

    $docInfo = array(
        "txtDocCd"        => iconvEuckrToUtf8($row["doc_cd"]),
        "txtRecipient"    => iconvEuckrToUtf8($row["recipient"]),
        "txtReference"    => iconvEuckrToUtf8($row["reference"]),
        "txtIssueDate"    => iconvEuckrToUtf8($row["issue_date"]),
        "txtTitle"        => iconvEuckrToUtf8($row["title"]),
        "content"         => $row["content"],
        "txtSendVal"      => iconvEuckrToUtf8($row["sender"]),
        "txtReadGrade"    => iconvEuckrToUtf8($row["rg_val"]),
        "txtReadDeptNm"   => iconvEuckrToUtf8($row["dept_name"]),
        "txtRecipientNms" => iconvEuckrToUtf8($row["co_receiver_list"]),
        "txtOutDocCd" => iconvEuckrToUtf8($row["out_doc_cd"]),
        "txtReceiveKind" => iconvEuckrToUtf8($row["receive_kind"]),
        "txtPjtNmFr" => iconvEuckrToUtf8($row["pjt_name"]),
        "txtReceiveDate" => iconvEuckrToUtf8($row["receive_date"]),
        "txtApprovalKind" => $txtApprovalKind,
        "txtSendKind" => $sendKind,
        "txtApprovalBasis" => iconvEuckrToUtf8($row["approval_basis"]),
        "txtProcess" => iconvEuckrToUtf8($row["ebr_process"]),
        "sdCd" => iconvEuckrToUtf8($row["sd_cd"]),
        "txtDocScope" => $docScope,
        "txtStorePeriod" => $storePeriod,
        "txtWriter" => iconvEuckrToUtf8($row["writer"]),
        "txtReceiveUser" => iconvEuckrToUtf8($row["receive_user"])
    );

    $docType = iconvEuckrToUtf8($row["doc_type"]);
    $status = $row["status"];

    // 결재합의 권한
    $SQL = "SELECT S.APPR_KIND, S.DELEGATE_AUTH
            FROM (
                SELECT 
                    A.APPR_KIND,
                    CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM EAS_DOC_INFO D
                            WHERE D.ONO = A.ONO
                            AND (:uno = D.WRITER OR :uno = D.RECEIVE_USER)
                        )
                        THEN 'N'
                        ELSE A.DELEGATE_AUTH
                    END AS DELEGATE_AUTH
                FROM EAS_APPR_STEP A
                WHERE A.ONO = :ono
                AND A.APPROVER = :uno
                AND A.STATUS IS NULL
                AND NOT EXISTS (
                        SELECT 1
                        FROM EAS_APPR_STEP P
                        WHERE P.ONO = A.ONO
                        AND P.STEP_ORDER < A.STEP_ORDER
                        AND (P.STATUS IS NULL OR P.STATUS IN ('02','04','05'))
                )
            ) S";
    $params = array(
        ":ono" => $ono,
        ":uno" => $user->uno
    );
    $db->query($SQL, $params);
    $db->next_record();
    $row = $db->Record;

    $isTurn = "";
    $deleAuth = "N";
    if($db->nf() > 0) {
        $isTurn = $row["appr_kind"];
        $deleAuth = $row["delegate_auth"];
    }

    //결재취소 권한
    $SQL = "SELECT CASE
         WHEN
           -- 1) 종결 아님
           NOT EXISTS (SELECT 1 FROM EAS_APPR_STEP WHERE ONO = :ono AND STATUS = '05') AND
           NOT EXISTS (SELECT 1 FROM EAS_APPR_STEP WHERE ONO = :ono AND STATUS IN ('02','04')) AND
           EXISTS     (SELECT 1 FROM EAS_APPR_STEP WHERE ONO = :ono AND STATUS IS NULL) AND
           -- 2) 직전 결재자가 나(:uno)인가?
           EXISTS (
             SELECT 1
             FROM EAS_APPR_STEP s
             WHERE s.ONO = :ono
               AND s.APPROVER = :uno
               AND s.STATUS IN ('01','03')
               AND s.STEP_ORDER = (
                 SELECT MIN(step_order) FROM EAS_APPR_STEP
                 WHERE ONO = :ono AND STATUS IS NULL
               ) - 1
           )
         THEN 'Y' ELSE 'N'
       END AS CAN_CANCEL
    FROM dual";
    $db->query($SQL, $params);
    $db->next_record();
    $row = $db->Record;
    $isCancel = $row["can_cancel"];

    //첨부파일
    $attachFileList = array();
    $params = array();
    $SQL = "SELECT FNO, FILE_NAME, FILE_SAVE
            FROM EAS_DOC_ATCH
            WHERE ONO = :ono
            AND IS_FINAL != 'Y'";
    $params = array(
        ":ono" => $ono,
        ":uno" => $user->uno
    );

    $db->query($SQL, $params);
    while($db->next_record()) {
        $row = $db->Record;

        $fileExt = strtolower(pathinfo($row["file_save"], PATHINFO_EXTENSION));

        if ($fileExt === 'txt') {
            $fileLink = "/gw/cm/cm_file_download.php?mKind=OL&fid={$row['fno']}";
        } else {
            $fileLink = buildFileUrl($urlPathAbsolute . "OL/{$ono}/" , $row["file_save"]);
        }

        $attachFileList[] = array(
            "attachId" => $row["fno"],
            "fileLink" => $fileLink,
            "fileNm" => $row["file_name"],
            "oriFileNm" => $row["file_save"]
        );
    }
    
    // 직인 여부
    $SQL = "SELECT 
                CASE
                    WHEN EXISTS (
                        SELECT 1 
                        FROM EAS_APPR_STEP 
                        WHERE EAS_APPR_STEP.ONO = A.ONO
                        AND STATUS = '05'  -- 전결
                    ) THEN 'Y'  -- 종결
                    WHEN (
                        SELECT COUNT(*) 
                        FROM EAS_APPR_STEP 
                        WHERE ONO = A.ONO
                    ) = (
                        SELECT COUNT(*) 
                        FROM EAS_APPR_STEP 
                        WHERE ONO = A.ONO
                        AND STATUS IN ('01', '03')  -- 결재완료, 합의완료
                    ) THEN 'Y'  -- 종결
                    ELSE 'N'  -- 미종결
                END AS IS_COMPLETED
            FROM (
                SELECT DISTINCT ONO
                FROM EAS_APPR_STEP
            ) A
            WHERE ONO = :ono";
    $db->query($SQL, $params);
    $db->next_record();
    $row = $db->Record;
    $isComplete = $row["is_completed"];

    $signUrl = '';
    if($isComplete == "Y" & $docType == "S") {
        if($docInfo["sdCd"] == "CP" || $docInfo["sdCd"] == "A010") {
            $signUrl = "/images/seal.png";
        } else {
            $SQL = "SELECT APPROVER, SIGN_IMG
                    FROM (
                        SELECT APPROVER, SIGN_IMG
                        FROM EAS_APPR_STEP
                        WHERE ONO = :ono
                        AND APPR_KIND = 'APPROVE'
                        ORDER BY STEP_ORDER DESC
                    )
                    WHERE ROWNUM = 1";
            $db->query($SQL, $params);
            $db->next_record();
            $row = $db->Record;

            $signImg = $row["sign_img"];
            $approver = $row["approver"];

            if($signImg) {
                $signUrl = "{$urlPathAbsolute}EMPSign/" . $row["sign_img"];
            } else {
                $params = array();
                // 이미지 가져오기
                $SQL = "SELECT sign_nm
                        FROM [dbo].[tcmg_user]
                        WHERE user_id = ?";
                $params[] = $approver;
                $userDB->query($SQL, $params);
                $userDB->next_record();
                $row = $userDB->Record;
                $signUrl = "{$urlPathAbsolute}EMPSign/" . $row["sign_nm"];
                if(!$row["sign_nm"]) {
                    $signUrl = "/images/seal.png";
                }
            }
        }
    }

    // 수정 가능 여부
    $SQL = "SELECT * FROM EAS_DOC_INFO
            WHERE ONO = :ono
            AND STATUS IS NULL
            AND (WRITER = :uno OR RECEIVE_USER = :uno)";
    $params = array(
        ":ono" => $ono,
        ":uno" => $user->uno
    );
    $db->query($SQL, $params);
    $db->next_record();
    $moCnt = $db->nf();
    if($moCnt > 0) {
        $isEdit = "Y";
    } else {
        $isEdit = "N";
    }

    // 인쇄 버튼 유무
    $SQL = "SELECT * FROM EAS_DOC_INFO
            WHERE ONO = :ono
            AND STATUS IS NOT NULL
            AND (STATUS != '02' OR STATUS != '04')";
    $db->query($SQL, $params);
    $db->next_record();
    $prCnt = $db->nf();
    if($prCnt > 0) {
        $isPrint = "Y";
    } else {
        $isPrint = "N";
    }

    // 업로드 권한
    $SQL = "SELECT CASE 
                    WHEN EXISTS (
                        SELECT 1
                        FROM (
                            SELECT WRITER AS UPLOAD_AUTH
                            FROM EAS_DOC_INFO
                            WHERE WRITER IS NOT NULL AND ONO = :ono
                            
                            UNION
                            
                            SELECT RECEIVE_USER AS UPLOAD_AUTH
                            FROM EAS_DOC_INFO
                            WHERE RECEIVE_USER IS NOT NULL AND ONO = :ono
                            
                            UNION
                            
                            SELECT APPROVER AS UPLOAD_AUTH
                            FROM EAS_APPR_STEP
                            WHERE ONO = :ono AND APPR_KIND = 'APPROVE'
                        ) T
                        WHERE T.UPLOAD_AUTH = :uno
                    )
                    THEN 'Y'
                    ELSE 'N'
                END AS UPLOAD_AUTH
            FROM DUAL";
    $db->query($SQL, $params);
    $db->next_record();
    $row = $db->Record;
    $uploadAuth = $row["upload_auth"];

    $finalFile = array();
    if($uploadAuth == "Y") {
        $SQL = "SELECT FILE_NAME, FILE_SAVE
                FROM EAS_DOC_ATCH
                WHERE ONO = :ono
                AND IS_FINAL = 'Y'";
        $db->query($SQL, $params);
        $db->next_record();
        $row = $db->Record;
        if($db->nf() > 0) {
            $finalFile = array(
                "fileNm" => $row["file_name"],
                "url" => $urlPathAbsolute . "OL/{$ono}/" . $row["file_save"]
            );
        } 
    }

    // 참조파일
    $relatedEBList = array();
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

    $result = array(
        "appLine" => $appLine,
        "docInfo" => $docInfo,
        "isTurn" => $isTurn,
        "deleAuth" => $deleAuth,
        "attachFileList" => $attachFileList,
        "isComplete" => $isComplete,
        "isEdit" => $isEdit,
        "docType" => $docType,
        "isPrint" => $isPrint,
        "uploadAuth" => $uploadAuth,
        "finalFile" => $finalFile,
        "signUrl" => $signUrl,
        "relatedEBList" => $relatedEBList,
        "status" => $status,
        "isCancel" => $isCancel
    );

    echo json_encode($result);
} else if($mode == "SIGN") {
    $userPwd = $_POST["userPwd"];
    $ono = $_POST["showOno"];

    $proceed = true;
    $msg = "";
    $SQL = "SELECT COUNT(1) AS IS_SUCCESS
            FROM [dbo].[tcmg_user]
            WHERE USER_ID = ?
            AND (EA_PW = ? OR LOGON_PWD = ?) ";
    $params[] = $user->uno;
    $params[] = $userPwd;
    $params[] = $userPwd;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;

    if($row["is_success"] == 0) {
        $proceed = false;
        $msg = '비밀번호가 일치하지 않습니다.';
    } else {
        $signKind = $_POST["signKind"];
        $signVal = $_POST["signVal"];
        $returnReason = $_POST["returnReason"];
        $alterSign = $_POST["alterSign"];
        $process = $_POST["process"];

        // 이미지 가져오기
        $SQL = "SELECT law_birth_dt, sign_nm
                FROM [dbo].[tcmg_user]
                WHERE user_id = ?";
        $params[] = $_SESSION["user"]["uno"];
        $userDB->query($SQL, $params);
        $userDB->next_record();
        $row = $userDB->Record;
        $signNm = $row["sign_nm"];

        if(!$signNm) {
            $proceed = false;
            $msg = "[개인설정] -> [개인정보수정]에서 사인을 추가하세요.";
        }

        if($signVal) {
            $isSign = 'Y';
        } else {
            $isSign = 'N';
        }

        if($proceed) {
            $SQL = "UPDATE EAS_APPR_STEP
                    SET STATUS = :status,
                        APPROVE_DATE = SYSDATE,
                        RETURN_REASON = :returnReason,
                        EBR_PROCESS = :process,
                        IS_DELEGATE = :alterSign,
                        SIGN_IMG = :signNm,
                        IS_SIGN = :isSign,
                        MOD_USER = :approver
                    WHERE ONO = :ono ";
            if($signKind) {
                $SQL.= "AND APPR_KIND = :signKind ";
            }
            $SQL.= "AND APPROVER = :approver";
            $params = array(
                ":status" => $signVal,
                ":returnReason" => $returnReason,
                ":process" => $process,
                ":alterSign" => $alterSign,
                ":isSign" => $isSign,
                ":signNm" => $signNm,
                ":approver" => $user->uno,
                ":ono" => $ono,
                ":signKind" => $signKind
            );
            
            $db->query($SQL, $params);
    
            $SQL = "UPDATE EAS_DOC_INFO
                    SET STATUS = :status
                    WHERE ONO = :ono";
            if($db->query($SQL, $params)) {
                $proceed = true;
                if($signVal == "01" || $signVal == "05") {
                    $msg = "결재되었습니다.";
                } else if($signVal == "02") {
                    $msg = "반려되었습니다.";
                } else if($signVal == "03") {
                    $msg = "합의되었습니다.";
                } else if($signVal == "04") {
                    $msg = "거부되었습니다.";
                } else {
                    $msg = "결재취소되었습니다.";
                }
    
                // 일련번호
                // $SQL = "SELECT 
                //             CASE
                //                 WHEN EXISTS (
                //                     SELECT 1 
                //                     FROM EAS_APPR_STEP 
                //                     WHERE EAS_APPR_STEP.ONO = A.ONO
                //                     AND STATUS = '05'  -- 전결
                //                 ) THEN 'Y'  -- 종결
                //                 WHEN (
                //                     SELECT COUNT(*) 
                //                     FROM EAS_APPR_STEP 
                //                     WHERE ONO = A.ONO
                //                 ) = (
                //                     SELECT COUNT(*) 
                //                     FROM EAS_APPR_STEP 
                //                     WHERE ONO = A.ONO
                //                     AND STATUS IN ('01', '03')  -- 결재완료, 합의완료
                //                 ) THEN 'Y'  -- 종결
                //                 ELSE 'N'  -- 미종결
                //             END AS IS_COMPLETED
                //         FROM (
                //             SELECT DISTINCT ONO
                //             FROM EAS_APPR_STEP
                //         ) A
                //         WHERE ONO = :ono";
                // $db->query($SQL, $params);
                // $db->next_record();
                // $row = $db->Record;
                // $isComplete = $row["is_completed"];
                // if($isComplete == "Y") {
                    // $SQL = "SELECT DOC_TYPE, ISSUE_YM
                    //         FROM EAS_DOC_INFO
                    //         WHERE ONO = :ono";
                    // $db->query($SQL, $params);
                    // $db->next_record();
                    // $row = $db->Record;
                    // $docType = $row["doc_type"];
                    // $docYm = $row["issue_ym"];
                    
                    // $SQL = "SELECT MAX(TO_NUMBER(ISSUE_NUM)) AS SERIAL_NUM
                    //         FROM EAS_DOC_INFO
                    //         WHERE DOC_TYPE = :docType
                    //         AND ISSUE_YM = :docYm
                    //         AND ISSUE_NUM != 'XXX'";
                    // $params = array(
                    //     ":docType" => $docType,
                    //     ":docYm" => $docYm
                    // );
                    // $db->query($SQL, $params);
                    // $db->next_record();
                    // $row = $db->Record;
                    // $serialNum = $row["serial_num"];
    
                    // if($row["serial_num"]) {
                    //     $serialNum++;
                    //     $issueNum = str_pad($serialNum, 3, "0", STR_PAD_LEFT);
                    // } else {
                    //     $issueNum = '001';
                    // }
    
                    // $SQL = "UPDATE EAS_DOC_INFO 
                    //         SET ISSUE_NUM = :issueNum,
                    //             DOC_CD = SUBSTR(DOC_CD, 1, LENGTH(DOC_CD) - 3) || :issueNum
                    //         WHERE ONO = :ono";
                    // $params = array(
                    //     ":ono" => $ono,
                    //     ":issueNum" => $issueNum
                    // );
                    // $db->query($SQL, $params);
                // }
            } else {
                $proceed = false;
                $msg = "결재 실패하였습니다.";
            }
        }
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
else if($mode == "UPLOAD_SCAN") {
    $ono = $_POST["showOno"];

    $proceed = false;
    $client = new SoapClient('http://file.hi-techeng.co.kr/transferweb/Service1.svc?singleWsdl');
    $location = "OL/{$ono}/";
    // if (count($delAttachFile) > 0) {
    //     foreach($delAttachFile as $fNm) {
    //         $parameter = array(
    //             'strFileName' => $location . $fNm
    //         );
    //         $resultDel = $client->DeleteFileWebGW($parameter);
    //     }
    // }
    $oriFileName = '';
    $url = '';
    if (!empty($_FILES['fileScan']['name'])) {
        $newFileName = "";
        $info = pathinfo($_FILES['fileScan']['name']);
        $oriFileName = $info['basename'];
        $ext = "." . $info['extension'];
        // $newFileName = getNewFileName($info['filename'], $ext);
        $newFileName = $info['filename'] . $ext;

        $saveFileName = $location . $newFileName;

        $uploadFile = file_get_contents($_FILES['fileScan']['tmp_name']);
        $parameter = array(
            'strFileBinary' => $uploadFile,
            'strSaveFileName' => $saveFileName
        );
        $resultUpload = $client->UploadFileWebGW($parameter);
        //파일 업로드 실패 시
        if ($resultUpload->UploadFileWebResult->ErrorMessage) {
            $proceed = false;
            $msg = $resultUpload->UploadFileWebResult->ErrorMessage;
            $msg = "업로드 실패하였습니다.";
        }
        //파일 업로드 성공 시
        else {
            // $fileList["name"][] = $newFileName;
            // $fileList["size"][] = $_FILES['newAttachFile']['size'];
            // $fileList["oriName"][] = $oriFileName;

            $SQL = "INSERT INTO EAS_DOC_ATCH (ONO, FILE_NAME, FILE_SAVE, FILE_SIZE, IS_FINAL)
                    VALUES(:ono, :fileName, :fileSave, :fileSize, :isFinal)";
            $params = array(
                ":ono" => $ono,
                ":fileName" => $oriFileName,
                ":fileSave" => $oriFileName,
                ":fileSize" => $_FILES['fileScan']['size'],
                ":isFinal" => 'Y'
            );
            $db->query($SQL, $params);
            $proceed = true;
            $msg = "업로드 성공하였습니다.";
            $url = $urlPathAbsolute . $saveFileName;
        }
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg,
        "oriFileName" => $oriFileName,
        "url" => $url
    );

    echo json_encode($result);
}
else if($mode == "DEL_FILE") {
    $ono = $_POST["showOno"];

    $SQL = "DELETE FROM EAS_DOC_ATCH
            WHERE ONO = :ono
            AND IS_FINAL = 'Y'";
    $params = array(
        ":ono" => $ono
    );
    if($db->query($SQL, $params)) {
        $proceed = "true";
    } else {
        $proceed = "false";
    }

    $result = array(
        "proceed" => $proceed
    );

    echo json_encode($result);
}

function iconvEuckrToUtf8($string) {
    return iconv("EUC-KR", "UTF-8//IGNORE", $string);
}

function buildFileUrl($base, $relativePath) {
    // $base = "/Upload/1323"; $relativePath = "OL/{$ono}/파일명.pdf"
    $parts = explode('/', trim($relativePath, '/'));
    $encoded = array_map('rawurlencode', $parts);
    return rtrim($base, '/') . '/' . implode('/', $encoded);
}

?>
