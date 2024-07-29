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
    //삭제
    $existDel = $_POST["existDel"];

    $proceed =  true;
    $resultValidation = array();
    $params = array();
    $SQL  = "[dbo].[PBS_ANNVACATION_0304000_D] ?, ?, ?, ? ";
    if($existDel) {
        foreach($existDel as $annvLogicId) {
            //@nGrpID			AS INT
            $params[] = $grpId;
            //, @nCOID		AS INT
            $params[] = $_SESSION["user"]["company_id"];
            //, @nUserID		AS INT
            $params[] = $user->uno;
            //, @nAnnv_ID		AS INT
            $params[] = intval($annvLogicId); //연차로직 고유번호
            $userDB->query($SQL, $params);
        }
    }

    //저장
    $companyId = $_POST["companyId"];

    //기존항목
    $annvLogicNmList = $_POST["annvLogicNm"];
    $stEnterCnt =  $_POST["stEnterCnt"];
    $endEnterCnt = $_POST["endEnterCnt"];
    $baseCnt = $_POST["baseCnt"];
    
    //추가 항목
    $newAnnvLogicNmList = $_POST["newAnnvLogicNm"];
    $newAnnvLogicNm = $_POST["newAnnvLogicNm"];
    $newStEnterCnt = $_POST["newStEnterCnt"];
    $newEndEnterCnt = $_POST["newEndEnterCnt"];
    $newBaseCnt =  $_POST["newBaseCnt"];

    $params = array();
    $SQL  = "[dbo].[PBS_ANNVACATION_0304000_IU] ";
    
    //기존항목 수정
    foreach($annvLogicNmList as $annvLogicId => $annvLogicNm ) {
        //@nGrpID				AS INT
        $params[] = $grpId;
        //, @nCOID			AS INT
        $params[] = $companyId; // 화면에서 선택된 회사 코드
        //, @nUserID			AS INT
        $params[] = $user->uno;
        //, @nAnnv_ID			AS INT
        $params[] = $annvLogicId; //연차로직 고유번호
        //, @sAnnv_Logic_NM	AS NVARCHAR(100)
        $params[] = $annvLogicNm; //항목명
        //, @nST_Enter_Cnt	AS INT
        $params[] = $stEnterCnt[$annvLogicId]; //입사연차(시작)
        //, @nEnd_Enter_Cnt	AS INT
        $params[] = $endEnterCnt[$annvLogicId]; //입사연차(종료)
        //, @nBase_Cnt		AS DECIMAL(5,2)
        $params[] = $baseCnt[$annvLogicId]; //부여일수
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);

        //초기화
        $params = null;
        $SQL  = "[dbo].[PBS_ANNVACATION_0304000_IU] ";
    }

    //새로운 항목 추가
    if($newAnnvLogicNmList) {
        foreach($newAnnvLogicNmList as $annvLogicId => $newAnnvLogicNm ) {
            //@nGrpID				AS INT
            $params[] = $grpId;
            //, @nCOID			AS INT
            $params[] = $companyId; // 화면에서 선택된 회사 코드
            //, @nUserID			AS INT
            $params[] = $user->uno;
            //, @nAnnv_ID			AS INT
            $params[] = 0; //연차로직 고유번호
            //, @sAnnv_Logic_NM	AS NVARCHAR(100)
            $params[] = $newAnnvLogicNm; //항목명
            //, @nST_Enter_Cnt	AS INT
            $params[] = $newStEnterCnt[$annvLogicId]; //입사연차(시작)
            //, @nEnd_Enter_Cnt	AS INT
            $params[] = $newEndEnterCnt[$annvLogicId]; //입사연차(종료)
            //, @nBase_Cnt		AS DECIMAL(5,2)
            $params[] = intval($newBaseCnt[$annvLogicId]); //부여일수
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            $userDB->query($SQL, $params);
    
            //초기화
            $params = null;
            $SQL  = "[dbo].[PBS_ANNVACATION_0304000_IU] ";
        }
    }

    $result = array(
        "proceed" => $proceed,
        "resultValidation" => $resultValidation,
        "msg" => "저장되었습니다."
    );

    echo json_encode($result);
}
//목록
else if ("LIST" == $mode) {
    
    $companyId = $_POST["companyId"];

    $annvLogicList = array();
    $params = array();
    $SQL  = "[dbo].[PBS_ANNVACATION_0304000_S] ?, ?, ?, ? ";
    //@nGrpID			AS INT
    $params[] = $grpId;
    //, @nCOID		AS INT
    $params[] = $companyId; // 화면에서 선택된 회사 코드
    //, @nUserID		AS INT
    $params[] = $user->uno;
    //, @sLang		AS NVARCHAR(10)
    $params[] = "KR";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;
        
        $annvLogicList[] = array(
            "annvLogicId" => $row["annv_logic_id"],
            "annvLogicNm" => $row["annv_logic_nm"],
            "stEnterCnt" => $row["st_enter_cnt"],
            "endEnterCnt" => $row["end_enter_cnt"],
            "baseCnt" => $row["base_cnt"]
        );
    }

    //입사년차
    $params = array();
    $SQL  = "[dbo].[PSM_CD_GetCd_SCD] ?, ?, ?, ?, ? ";
    //@nGrpID AS INT
    $params[] = $grpId;
    //, @nCOID AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //, @sCDGrp AS NVARCHAR(30)
    $params[] = "enter_dt";
    //, @sLangKind AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    //, @sUnSelectedSubCD	AS NVARCHAR(4000) = ''
    $params[] = "";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $joinYearList[] = array(
            "key" => $row["cd_val"],
            "value" => $row["cd_nm"]
        );
    }

    $result = array(
        "infoList" => $annvLogicList,
        "joinYearList" => $joinYearList
    );

    echo json_encode($result);
}
//초기 화면
else if ("INIT" == $mode) {
    //회사목록
    $params = array();
    $SQL  = "[dbo].[PSM_CO_0100100_SUse] ?, ?, ?, ? ";
    //@inGrpID 		AS INT
    $params[] = $grpId;
    //, @inCoID		AS INT
    $params[] = '0';
    //, @sLangKind	AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    //, @sGroupMode   AS NVARCHAR(1) = '0' /*0 단위회사, 1 그룹-회사*/
    $params[] = "0";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $companyList[] = array(
            "key" => $row["co_id"],
            "value" => $row["co_nm"],
            "userCoId" => $_SESSION["user"]["company_id"]
        );
    }
    //초기 회사 표시 : 로그인 유저 소속 회사 $_SESSION["user"]["company_id"]

    //입사년차
    $params = array();
    $SQL  = "[dbo].[PSM_CD_GetCd_SCD] ?, ?, ?, ?, ? ";
    //@nGrpID AS INT
    $params[] = $grpId;
    //, @nCOID AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //, @sCDGrp AS NVARCHAR(30)
    $params[] = "enter_dt";
    //, @sLangKind AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    //, @sUnSelectedSubCD	AS NVARCHAR(4000) = ''
    $params[] = "";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $joinYearList[] = array(
            "key" => $row["cd_val"],
            "value" => $row["cd_nm"]
        );
    }

    $result = array(
        "companyList" => $companyList,
        "joinYearList" => $joinYearList
    );

    echo json_encode($result);
}

?>
