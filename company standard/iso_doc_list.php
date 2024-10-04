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

if($mode == "SHEET") {
    $sheetList = array();
    $SQL = "SELECT SNO, SHEET_NM, FNO
            FROM ISO_DOC_SHEET
            WHERE IS_USE = 'Y'";
    $db->query($SQL);

    $fno = '';
    while($db->next_record()) {
        $row = $db->Record;

        $sheetList[] = array(
            "sno" => $row["sno"],
            "sheetNm" => $row["sheet_nm"],
        );

        $fno = $row["fno"];
    }

    $hashTagList = array();
    $SQL = "SELECT HASH_TXT
            FROM ISO_DOC_HASH
            GROUP BY HASH_TXT";
    $db->query($SQL);
    while($db->next_record()) {
        $row = $db->Record;

        $hashTagList[] = $row["hash_txt"];
    }

    $isManager = 'N';
    $params = array();
    $SQL = 'SELECT * FROM [dbo].[tcmg_userrole]
            WHERE USER_ID = ?
            AND ROLE_ID = ?';
    $params[] = $user->uno;
    $params[] = 117;
    $userDB->query($SQL, $params);
    $cnt = $userDB->nf();

    if($cnt > 0) {
        $isManager = 'Y';
    }

    $result = array(
        "sheetList" => $sheetList,
        "fno" => $fno,
        "hashTagList" => $hashTagList,
        "isManager" => $isManager
    );

    echo json_encode($result);
} else if ($mode == "CATEGORY") {
    $sno = $_POST["sno"];

    $categoryList = array();
    $SQL = "WITH A AS (
                        SELECT SNO, CATEGORY_NM, LPAD(SNO, 5, '0') AS SNO_SORT_VAL1
                                    FROM ISO_DOC_LIST
                                    WHERE IS_USE = 'Y'
                                    GROUP BY SNO, CATEGORY_NM ";
    if(!empty($sno)) {
        $SQL .= "HAVING SNO = :sno";
    }
    $SQL .= "       )
            SELECT A.*, LPAD(SNO, 5, '0') AS SNO_SORT_VAL2 FROM A
            ORDER BY LPAD(SNO, 5, '0'), SNO_SORT_VAL1, SNO_SORT_VAL2";
    $params = array(
        ":sno" => $sno
    );
    $db->query($SQL, $params);
    while($db->next_record()) {
        $row = $db->Record;

        $categoryList[] = array(
            $row["category_nm"]
        );
    }

    $result = array(
        "categoryList" => $categoryList
    );

    echo json_encode($result);
} else if ($mode == "KIND") {
    $sno = $_POST["sno"];
    $categoryNm = $_POST["ddlCategory"];

    $kindList = array();
    $SQL = "SELECT SNO, CATEGORY_NM, CATEGORY_KIND
            FROM ISO_DOC_LIST
            WHERE IS_USE = 'Y' 
            AND TRIM(CATEGORY_KIND) IS NOT NULL ";
    if (!empty($categoryNm)) {
        $SQL .= " AND CATEGORY_NM = :categoryNm ";
    }
    if (!empty($sno)) {
        $SQL .= " AND SNO = :sno ";
    }
    $SQL .= "GROUP BY SNO, CATEGORY_NM, CATEGORY_KIND";
    $params = array(
        ":sno" => $sno,
        ":categoryNm" => $categoryNm
    );
    $db->query($SQL, $params);
    while($db->next_record()) {
        $row = $db->Record;

        $kindList[] = array(
            $row["category_kind"]
        );
    }

    $result = array(
        "kindList" => $kindList
    );

    echo json_encode($result);
} else if ($mode == "LIST") {
    $sno = $_POST["sno"];
    $categoryNm = $_POST["ddlCategory"];
    $categoryKind = $_POST["ddlKind"];
    $ddlSearchKind = $_POST["ddlSearchKind"];
    $txtSearchValue = $_POST["txtSearchValue"];
    $sortQuery = $_POST["sortQuery"];
    $rebrowsing = $_POST["rebrowsing"];
    $searchCondition = $_POST["searchCondition"];
    
    $rebrowsingQuery = '';
    if($rebrowsing == "Y" && $searchCondition) {
        $rebrowsingArray = explode('♡', $searchCondition);

        foreach ($rebrowsingArray as $value) {
            $rebrowsingQuery .= "AND ";
            list($key, $searchVal) = explode('=', $value);

            $searchVal = "%" . strtolower($searchVal) . "%";

            if($key == "all") {
                $rebrowsingQuery .= "(LOWER(CHARGE_DEPT) LIKE '{$searchVal}' OR LOWER(CHARGE_STAFF) LIKE '{$searchVal}' OR LOWER(DOC_CD) LIKE '{$searchVal}' OR LOWER(DOC_NM) LIKE '{$searchVal}' OR LOWER(DOC_DETAIL) LIKE '{$searchVal}' OR LOWER(HASH_TXT) LIKE '{$searchVal}') ";
            } else {
                $rebrowsingQuery .= "LOWER({$key}) LIKE '{$searchVal}' ";
            }
        }
    }

    $isoList = array();
    $SQL = "WITH HASH_AGG AS (
                    SELECT LNO, '#' || LISTAGG(HASH_TXT, ' #') WITHIN GROUP (ORDER BY HNO) AS  HASH_TXT
                    FROM ISO_DOC_HASH
                    GROUP BY LNO
            ),
            HIT AS (
                        SELECT LG.ECM_DOC_OID, LG.REVISION_NO, COUNT(*) AS VIEW_CNT 
                        FROM ISO_DOC_LOG LG 
                        LEFT OUTER JOIN ISO_DOC_LIST L ON LG.ECM_DOC_OID = L.ECM_DOC_OID AND LG.REVISION_NO = L.REVISION_NO 
                        WHERE L.IS_USE = 'Y' 
                        GROUP BY LG.ECM_DOC_OID, LG.REVISION_NO
            )
            SELECT L.CATEGORY_NM, L.CATEGORY_KIND, L.CHARGE_DEPT, L.CHARGE_STAFF, L.DOC_CD, L.DOC_NM, L.DOC_DETAIL, L.ECM_FILE_OID, L.ECM_PROPERTY_URL, L.ECM_DIR_PATH, L.ECM_FULL_TXT, A.HASH_TXT, L.ECM_DOC_OID, L.ECM_VERSION, L.REVISION_NO, TO_CHAR(L.REVISION_DATE, 'YYYY-MM-DD') AS REVISION_DATE,
                 H.VIEW_CNT, S.CAN_DOWNLOAD
            FROM ISO_DOC_LIST L
            INNER JOIN ISO_DOC_SHEET S ON L.SNO = S.SNO
            LEFT OUTER JOIN HIT H ON H.ECM_DOC_OID = L.ECM_DOC_OID
            RIGHT OUTER JOIN HASH_AGG A ON L.LNO = A.LNO
            WHERE L.IS_USE = 'Y' ";
    if($rebrowsing == "Y" && $rebrowsingQuery) {
        $SQL .= $rebrowsingQuery;
    } else {
        if(!$ddlSearchKind) {
            $SQL .= "AND (LOWER(CHARGE_DEPT) LIKE :searchTxt OR LOWER(CHARGE_STAFF) LIKE :searchTxt OR LOWER(DOC_CD) LIKE :searchTxt OR LOWER(DOC_NM) LIKE :searchTxt OR LOWER(DOC_DETAIL) LIKE :searchTxt OR LOWER(HASH_TXT) LIKE :searchTxt) ";
        } else {
            $SQL .= "AND LOWER({$ddlSearchKind}) LIKE :searchTxt ";
        }
    }
    if(!empty($sno)) {
        $SQL .= "AND L.SNO = :sno ";
    }
    if(!empty($categoryNm)) {
        $SQL .= "AND CATEGORY_NM = :categoryNm ";
    }
    if(!empty($categoryKind)) {
        $SQL .= "AND CATEGORY_KIND = :categoryKind ";
    }
    $SQL .= "ORDER BY ";
    if(!empty($sortQuery)) {
        $SQL .= $sortQuery;
    }
    $SQL .= "L.LNO";
    $params = array(
        ":sno" => $sno,
        ":categoryNm" => $categoryNm,
        ":categoryKind" => $categoryKind,
        ":searchTxt" => "%" . strtolower($txtSearchValue) . "%"
    );
    $db->query($SQL, $params);
    $isFunctionWide = "N";
    $andCondition = 0;
    while($db->next_record()) {
        $row = $db->Record;

        if($andCondition == 0 && $row["can_download"] == "Y") {
            $isFunctionWide = "Y";
        }

        $isoList[] = array(
            "categoryNm" => $row["category_nm"],
            "categoryKind" => $row["category_kind"],
            "chargeDept" => $row["charge_dept"],
            "chargeStaff" => $row["charge_staff"],
            "docCd" => $row["doc_cd"],
            "docNm" => $row["doc_nm"],
            "docDetail" => $row["doc_detail"],
            "ecmFileOid" => $row["ecm_file_oid"],
            "ecmPropertyUrl" => $row["ecm_property_url"],
            "ecmDirPath" => $row["ecm_dir_path"],
            "ecmFullTxt" => $row["ecm_full_txt"],
            "revisionNo" => $row["revision_no"],
            "revisionDt" => $row["revision_date"],
            "hashTxt" => $row["hash_txt"],
            "viewCnt" => $row["view_cnt"],
            "canDownload" => $row["can_download"]
        );
    }

    $result = array(
        "isoList" => $isoList,
        "isFunctionWide" => $isFunctionWide
    );

    echo json_encode($result);
} else if ($mode == "PREVIEW") {
    $oid = $_POST["oid"];

    $SQL = "SELECT FILENAME, TARGETOID
            FROM XFILE
            WHERE OID = :oid";
    $params = array(
        "oid" => $oid
    );
    $ecmDB->query($SQL, $params);
    $ecmDB->next_record();
    $row = $ecmDB->Record;

    $fileName = $row["filename"];
    $targetOid = $row["targetoid"];

    $proceed = false;
    $msg = "";
    if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) == "pdf") {
        // cURL 초기화
        $ch = curl_init();
    
        // cURL 옵션 설정
        curl_setopt($ch, CURLOPT_URL, "https://ecm.htenc.co.kr/restApi/file/download/fileOID");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['fileOID' => $oid]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // 응답을 바이너리 데이터(blob)로 받기 위해 헤더 설정
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/octet-stream'
        ]);
    
        // cURL 실행 및 응답 저장
        $response = curl_exec($ch);
    
        // 에러 체크
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            // 바이너리 데이터를 파일로 저장
            $file = fopen($fileName, 'wb');
            fwrite($file, $response);
            fclose($file);
        }
    
        // cURL 세션 닫기
        curl_close($ch);
    
        $client = new SoapClient('http://file.hi-techeng.co.kr/transferweb/Service1.svc?singleWsdl');
        $type = "ECM";
        $newFileName = "";
        $uploadFile = $fileName;
        $parameter = array(
            'strFileBinary' => file_get_contents($uploadFile),
            'strSaveFileName' => $type . "/" . $fileName
        );
        $resultUpload = $client->UploadFileWebGW($parameter);
        //파일 업로드 실패 시
        if ($resultUpload->UploadFileWebResult->ErrorMessage) {
            $proceed = false;
            $msg .= $resultUpload->UploadFileWebResult->ErrorMessage;
        }
        //파일 업로드 성공 시
        else {
            $proceed = true;
            unlink($fileName);
        }
    } else if(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) == "zip") {
        $msg = "zip파일은 미리보기 할 수 없습니다.";
    } else {
        $encodingFileNm = rawurlencode($fileName);

        $url = "http://61.41.17.50:7070/api/ConvertToPdf/{$targetOid}/{$encodingFileNm}";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        $responseResult = json_decode($response);

        if($responseResult == "True") {
            $proceed = true;
        } else {
            $proceed = false;
        }
    }
    if($proceed) {
        $previewUrl = $urlPathAbsolute . 'ECM/' . rawurlencode(pathinfo($fileName, PATHINFO_FILENAME) . ".pdf") . '#toolbar=0';
    } else {
        $previewUrl = '';
    }

    $result = array(
        "proceed" => $proceed,
        "previewUrl" => $previewUrl,
        "msg" => $msg
    );

    echo json_encode($result);
} else if ($mode == "LOG") {
    $oid = $_POST["oid"];
    $actType = $_POST["actType"];

    $SQL = "SELECT OID
            FROM XDOCUMENT
            WHERE OID = (SELECT TARGETOID
                            FROM XFILE
                            WHERE OID = :oid
                        )";
    $params = array(
        ":oid" => $oid
    );
    $ecmDB->query($SQL, $params);
    $ecmDB->next_record();
    $row = $ecmDB->Record;

    $docOid = $row["oid"];

    $ano = $db->nextid("SEQ_ISO_ANO");

    $SQL = "INSERT INTO ISO_DOC_LOG (ANO, LNO, UNO, ACT_TYPE, ECM_DOC_OID, VERSION, REVISION_NO)
            SELECT :ano, lno, :uno, :actType, :ecmDocOid, ecm_version, revision_no
            FROM (
                SELECT LNO, ECM_VERSION, REVISION_NO
                FROM ISO_DOC_LIST 
                WHERE ECM_FILE_OID = :oid
                AND IS_USE = 'Y'
            )";
    $params = array(
        ":ano" => $ano,
        ":uno" => $user->uno,
        ":actType" => $actType,
        ":ecmDocOid" => $docOid,
        ":oid" => $oid
    );
    if($db->query($SQL, $params)) {
        $proceed = true;
    } else {
        $proceed = false;
    }

    $result = array(
        "proceed" => $proceed,
    );

    echo json_encode($result);
}
?>
