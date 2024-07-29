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

//삭제
if ("DEL" == $mode) {

    $modifyGrpId = $_POST["modifyGrpId"];

    $proceed = true;

    $params = array();
    $SQL  = "[dbo].[PMP_Group_D] ?, ?, ? ";
    //@nCoId			AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //,@sAddrGrpId	AS NVARCHAR(100)	-- 주소록 아이디
    $params[] = $modifyGrpId; //선택한 그룹ID
    //,@nUserId		AS INT			  --삭제요청한 유저 아이디
    $params[] = $user->uno;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    if ($row["return_value"] == 0) {
        $msg = "삭제 되었습니다.";
    }
    else {
        $proceed = false;
        //그룹명
        $resultValidation["grpNm"] = "데이터가 있어 삭제할수 없습니다.";
    }

    $result = array(
        "proceed" => $proceed,
        "resultValidation" => $resultValidation
    );

    echo json_encode($result);
}
//저장
else if ("SAVE" == $mode) {

    $modifyGrpId = $_POST["modifyGrpId"];
    $grpNm = strip_tags(trim($_POST["grpNm"]));
    $rbl_private = $_POST["rbl_private"];
    $grpDesc = $_POST["grpDesc"];
    $dbMode = $_POST["dbMode"];
    
    $resultValidation = array();
    $proceed = true;
    $params = array();
    $SQL  = "[dbo].[PMP_GROUP_POP_IU] ";
    //@nAddrGrpId		 AS INT 			 --그룹ID
    $params[] = $modifyGrpId; //선택한 그룹ID
    //,@nCoId			 AS INT				 --회사아이디
    $params[] = $_SESSION["user"]["company_id"];
    //,@sAddrGrpNm	 AS NVARCHAR(50)		 --그룹명
    $params[] = $grpNm;
    //,@sAddrGrpTp	 AS NVARCHAR(30)		 --그룹구분
    $params[] = $rbl_private;
    //,@sAddrGrpDesc	 AS NVARCHAR(100)	 --그룹설명
    $params[] = $grpDesc;
    //,@nCreatedEditBy AS INT 			 --작성자/수정자
    $params[] = $user->uno;
    //,@sYN			 AS NCHAR(1)			 --인서트/업데이트 구분
    $params[] = $dbMode; //등록 : I, 수정 : U
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    if ($row["return_value"] == 0) {
        $proceed = false;
        //그룹명
        $resultValidation["grpNm"] = "이미 등록된 그룹입니다.";
    }

    if ($proceed) {
        //공개범위
        $publicScopeIds = $_POST["publicScopeIds"];

        $publicScopeArray = array();
        $publicScopeArray = explode('/', $publicScopeIds);

        $publicScopeDept = array();
        $publicScopeUser = array();
        $publicScopeUserId = array();
        $publicScopeCoId = array();
        
        foreach($publicScopeArray as $value) {
            //부서ID : 'D'로 시작, 'D' 제외
            if(substr($value,0,1) == "D") {
                $publicScopeDept[] = substr($value,1);
            }
            //직원ID : 직원ID|회사ID 를 분리
            else {
                $publicScopeUser = explode('|', $value);
                $publicScopeUserId[] = $publicScopeUser[0];
                $publicScopeCoId[] = $publicScopeUser[1];
            }
        }

        //부서나열
        for($i = 0; $i < count($publicScopeDept); $i++) {
            if ($i > 0) {
                $publicScopeDeptList .= ", ";
            }
            $publicScopeDeptList .= $publicScopeDept[$i];
        }

        //유저나열
        for($i = 0; $i < count($publicScopeUserId); $i++) {
            if ($i > 0) {
                $publicScopeUserIdList .= ", ";
                $publicScopeCoIdList .= ", ";
            }
            $publicScopeUserIdList .= $publicScopeUserId[$i];
            $publicScopeCoIdList .= $publicScopeCoId[$i];
        }

        $params = array();
        $SQL  = "[dbo].[PMP_ADDR_GRP_PUBLIC] ";
        //@inCOID			AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //, @inUserID			AS INT
        $params[] = $user->uno;
        //, @inAddrGrpID		AS INT
        $params[] = $modifyGrpId; //선택한 그룹ID
        //, @ivAuth			AS NVARCHAR(30)
        $params[] = "MPADDRPublic";
        //, @ivCOIDs			AS NVARCHAR(4000)
        $params[] = $publicScopeCoIdList; //','로 연결한 직원 회사ID
        //, @ivUserIDs		AS NVARCHAR(4000)
        $params[] = $publicScopeUserIdList; //','로 연결한 직원ID
        //, @ivDeptIDs		AS NVARCHAR(4000)
        $params[] = $publicScopeDeptList; //','로 연결한 부서ID
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
    }

    $result = array(
        "proceed" => $proceed,
        "resultValidation" => $resultValidation,
        "msg" => $msg
    );

    echo json_encode($result);
}
//상세
else if ("DETAIL" == $mode) {

    $modifyGrpId = $_POST["modifyGrpId"];

    $params = array();
    $SQL  = "[dbo].[PMP_AddrGrpPop_S] ?, ? ";
    //@nAddrGrpId AS INT			 --주소록아이디
    $params[] = $modifyGrpId; //선택한 그룹ID
    //,@nCoId		AS INT			 --회사아이디
    $params[] = $_SESSION["user"]["company_id"];
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;

    $addrGrpDetail = array(
        //그룹명 addr_grp_nm
        "addrGrpNm" => $row["addr_grp_nm"],
        //그룹설명 addr_grp_desc
        "addrGrpDesc" => $row["addr_grp_desc"],
        //등록자 created_by
        "createdBy" => $row["created_by"]
    );

    //공개범위
    $SQL  = "[dbo].[PMP_AddrGrpPop_Public_S] ?, ? ";
    //@inAddrGrpID		AS INT
    $params[] = $modifyGrpId; //선택한 그룹ID
    //, @inCOID			AS INT
    $params[] = $_SESSION["user"]["company_id"];
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;
        //work_kind : U
        if($row["work_kind"] == "U") {
            // id : work_id + '|' + co_id
            $id[] = $row["work_id"] . "|" . $row["co_id"];
            // nm : work_nm
            $nm[] = $row["work_nm"];
        }
        //work_kind : U 외
        else {
            // id : 'D' + work_id
            $id[] = "D" .  $row["work_id"];
            // nm : work_nm
            $nm[] = $row["work_nm"];
        }
        
        $addrGrpScope = array(
            "id" => $id,
            "nm" => $nm
        );
    }
    //id, nm은 '/'로 구분


    $result = array(
        "addrGrpDetail" => $addrGrpDetail,
        "addrGrpScope" => $addrGrpScope
    );

    echo json_encode($result);
}
//목록
else if ("LIST" == $mode) {

    $rbl_private = $_POST["rbl_private"];
    $folderInfo = array();
    $params = array();
    $SQL  = "[dbo].[PMP_Group_S] ?, ?, ?, ? ";
    //@nGrpId			AS INT
    $params[] = $grpId;
    //,@nCOId			AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //,@nUserId		AS INT
    $params[] = $user->uno;
    //,@sAddrGrpTp	AS NVARCHAR(30)
    $params[] = $rbl_private; //그룹구분
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $addrGrpList[] = array(
            //그룹ID addr_grp_id
            "addrGrpId" => $row["addr_grp_id"],
            //그룹명 addr_grp_nm
            "addrGrpNm" => $row["addr_grp_nm"],
            //인원수 user_cnt
            "userCnt" => $row["user_cnt"],
            //그룹설명 addr_grp_desc
            "addrGrpDesc" => $row["addr_grp_desc"]
        );
    }

    $result = array(
        "addrGrpList" => $addrGrpList
    );

    echo json_encode($result);
}
//초기 화면
else if ("INIT" == $mode) {
    //그룹관리구분
    $groupList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "rbl_Address_private";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $groupList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //명함관리 권한옵션
    $authAddress = "";
    $params = array();
    $SQL  = "[dbo].[PSM_OptionValue_S] ?, ?, ? ";
    //@nGrpID			INT
    $params[] = $grpId;
    //, @nOptionID		INT
    $params[] = 104;
    //, @nCoID		INT=0
    $params[] = $coId;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    if (!empty($row)) {
        $row = $userDB->Record;
        $authAddress = $row["option_value"];
    }
    //1 이면 등록자만 수정 가능

    $result = array(
        "groupList" => $groupList,
        "authAddress" => $authAddress
    );

    echo json_encode($result);
}
