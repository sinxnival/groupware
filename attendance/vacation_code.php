<?php 
require_once "../../lib/include.php";
require_once "../common/biz_ini.php";

//세션 만료일 경우
if (!isset($_SESSION["user"]["uno"])) {
    echo json_encode(array("session_out" => true));
    //종료
    exit();
}

//작업모드
$mode = $_POST["mode"];

//저장
if ("SAVE" == $mode) {

    //휴가코드
    $cdVal = $_POST["cdVal"];
    //휴가명
    $cdNm = $_POST["cdNm"];
    //영어
    $cdNmEn = $_POST["cdNmEn"];
    //중국어
    $cdNmCn = $_POST["cdNmCn"];
    //중국어간체
    $cdNmGb = $_POST["cdNmGb"];
    //일본어
    $cdNmJp = $_POST["cdNmJp"];
    //사용연차
    $minAnn = $_POST["minAnn"];
    //사용여부
    $useYn = $_POST["useYn"];
    //신규여부
    $newYn = $_POST["newYn"];
    
    $proceed = true;
    $resultValidation = array();

    if ($proceed){
        $params = array();
        $SQL  = "[dbo].[PBS_0308000_POP_IU] ";
        //@CD	VARCHAR(10)
        $params[] = $cdVal;
        //, @NM	VARCHAR(20)
        $params[] = $cdNm;
        //, @NM_EN	VARCHAR(20)
        $params[] = $cdNmEn;
        //, @NM_CN	VARCHAR(20)
        $params[] = $cdNmCn;
        //, @NM_GB	VARCHAR(20)
        $params[] = $cdNmGb;
        //, @NM_JP	VARCHAR(20)
        $params[] = $cdNmJp;
        //, @MIN_ANN  NUMERIC(6,3)
        $params[] = $minAnn;
        //, @USE_YN	INT
        $params[] = $useYn;
        //, @USER_ID	INT
        $params[] = $user->uno;
        //, @CD_VAL	VARCHAR(10)
        //신규일때는 빈칸, 편집일 때는 기존 코드
        if($newYn == 'Y'){
            $params[] = '';
        }else{
            $params[] = $cdVal;
        }
        
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
        while($userDB->next_record()) {
            $row = $userDB->Record;
            if ($row["return_value"] == 1) {
                $proceed = false;
                $resultValidation["cdVal"] = "중복코드입니다.";
            }
        };
    } 
    
    $result = array(
        "proceed" => $proceed,
        "resultValidation" => $resultValidation
    );

    echo json_encode($result);
}
//상세
else if ("DETAIL" == $mode) {
    $cdVal = $_POST["cdVal"];

    //기존 휴가코드
    $code = $cdVal;
     
    $result = array(
        "vacationInfo" => getDivAttCode($code)
    );

    echo json_encode($result);
}
//삭제
else if ("DEL" == $mode){
    $cdVal =  $_POST["cdVal"];

    $proceed = true;
    $resultValidation = array();
    $params = array();
    $SQL1 = "DELETE FROM TBSG_HOL_ANN_CONFIG WHERE HOL_CD = ?";
    $SQL2 = "DELETE FROM TCMD_CD WHERE CD_VAL = ?";
    //@sCD_VAL          AS NVARCHAR(10)
    $params[] = $cdVal;

    $USE_SQL = "SELECT COUNT(doc_id) AS CNT
                FROM TEAG_APPDOC
                WHERE pro_kind = 5 AND DF20 = 'BSPG' AND DF21 = '04' AND DF30 = ?";
    
    $userDB->query($USE_SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
     
    if ($row["cnt"] > 0) {
        $proceed = false;
        $resultValidation["cdVal"] = "사용중인 휴가코드는 삭제되지 않습니다.";
    }
    else {
        $msg = "삭제 하였습니다.";
    }

    if($proceed) {
        $userDB->query($SQL1, $params);
        $userDB->query($SQL2, $params);
    }
    
    $result = array(
        "proceed" => $proceed,
        "resultValidation" => $resultValidation,
        "msg" => $msg
    );

    echo json_encode($result);
}
//목록
else if ("LIST"== $mode) {

    //새 휴가코드
    $code = '';

    $result = array(
        "infoList" => getDivAttCode($code)
    );

    echo json_encode($result);
}

//휴가코드 취득
function getDivAttCode($code) {
    global $userDB;

    //휴가코드
    $vacationList = array();
    $params = array();
    $SQL  = "[dbo].[PBS_0308000_S] ?, ? ";
    //@sCD_VAL          AS NVARCHAR(10)
    $params[] = $code;
    //,@sLangKind		  AS NVARCHAR(10)
    $params[] = "KR";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $vacationList[] = array(
            "cdVal" => $row["cd_val"],
            "cdNm" => $row["cd_nm"],
            "cdNmEn" => $row["cd_nm_en"],
            "cdNmCn" => $row["cd_nm_cn"],
            "cdNmGb" => $row["cd_nm_gb"],
            "cdNmJp" => $row["cd_nm_jp"],
            "minAnn" => $row["min_ann"],
            "useYnNm" => $row["use_yn_nm"]
        );
    }

    return $vacationList;
}

?>
