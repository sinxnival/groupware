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

$members = $user->getUserMemberAll();
$depts = $user->getUserDeptInfo();

if($mode == "INIT") {
    $attendList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ?";
    // @CD AS NVARCHAR(30) = ''
    $params[] = "div_attend";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $attendList[] = array(
            "cdVal" => $row["cd_val"],
            "cdNm" => $row["cd_nm"]
        );
    }

    $result = array(
        "attendList" => $attendList
    );

    echo json_encode($result);
}
else if($mode == "DEPT_LIST") {
    $searchFrom = str_replace('-', '', $_POST["searchFrom"]);
    $holidayOption = $_POST["holidayOption"];

    $params = array();
    $SQL  = "[dbo].[PBS_ATTEND_0300100_S_Dept] ";
    // @inGrpID		INT
    $params[] = $grpId;
	// , @inWorkCOID	INT
    $params[] = $_SESSION["user"]["company_id"];
	// , @inWorkID		INT
    $params[] = $user->uno;
	// , @dt			NCHAR(8)	-- 검색일자
    $params[] = $searchFrom;
	// , @sSEARCH_DIV  INT  = 1    -- 검색구분 0:모두보기 1:근태등록자만보기
    $params[] = $holidayOption;
	// , @sLang		NVARCHAR(10) ='KR' -- 다국어구분
    $params[] = 'KR';
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);

    while($userDB->next_record()) {
        $row = $userDB->Record;

        $dept_nm2 = $row["dept_nm"];
        if(isset($members) && is_array($members) && isset($depts) && is_array($depts) && $row["dept_id"] != "0")
        {
            $member = $members[$user->uno];
            $dept_id = $row["dept_id"];
            $dept_nm2 = $depts[$dept_id]["display_name"];
            //echo $dept_nm2;
        }

        $deptAttendList[] = array(
            "deptNm" => $dept_nm2,
            "total" => $row["total"],
            "aTotal" => $row["atotal"],
            "a05" => $row["a05"],
            "a07" => $row["a07"],
            "a06" => $row["a06"],
            "a04" => $row["a04"],
            "a03" => $row["a03"],
            "a08" => $row["a08"],
            "b02" => $row["b02"],
            "b10" => $row["b10"],
            "bTotal" => $row["btotal"],
            "nowUserCnt" => $row["now_user_count"],
            "deptId" => $row["dept_id"]
        );
    }

    $result = array(
        "deptAttendList" => $deptAttendList
    );

    echo json_encode($result);
}
// 유저별 목록
else if("USER_LIST" == $mode) {
    $searchFrom = str_replace('-', '', $_POST["searchFrom"]);
    $holidayOption = $_POST["holidayOption"];
    $deptId = $_POST["deptId"];
    
    $userAttendList = array();
    $params = array();
    $SQL  = "[dbo].[PBS_ATTEND_0300100_S_User] ";
    // @inGrpID		INT			-- 그룹코드
    $params[] = $grpId;
	// , @inWorkCOID	INT			-- 회사코드
    $params[] = $_SESSION["user"]["company_id"];
	// , @inWorkID		INT			-- 사용자코드
    $params[] = $user->uno;
	// , @dt			NCHAR(8)	-- 검색일자
    $params[] = $searchFrom;
	// , @dept_id		INT			-- 검색부서
    $params[] = $deptId;
	// , @sSEARCH_DIV  INT  = 1    -- 검색구분 0:모두보기 1:근태등록자만보기
    $params[] = $holidayOption;
	// , @sLang		NVARCHAR(10) = 'KR'-- 다국어구분
    $params[] = 'KR';
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }

    $userDB->query($SQL, $params);

    while($userDB->next_record()) {
        $row = $userDB->Record;

        $dept_nm2 = $row["dept_nm"];
        if(isset($members) && is_array($members) && isset($members[$user->uno]) && is_array($members[$user->uno]) && $members[$user->uno] && isset($depts) && is_array($depts))
        {
            $member = $members[$user->uno];
            $dept_id = $row["dept_id"];
            $dept_nm2 = $depts[$dept_id]["display_name"];
            //echo $dept_nm2;
        }

        $userAttendList[] = array(
            // 내역
            "divAttNm" => $row["div_att_nm"],
            // 상세
            "divSubAttNm" => $row["div_sub_att_nm"],
            // 부서
            "deptNm" => $dept_nm2,
            // 직급
            "gradeNm" => $row["grade_nm"],
            // 사원명
            "userNm" => $row["user_nm"],
            // 출근시간
            "timeFr" => $row["time_fr"],
            // 퇴근시간
            "timeTo" => $row["time_to"],
            // 품의제목
            "appTitle" => $row["app_title"],
            // 승인
            "divAppNm" => $row["div_app_nm"],
            // 변경사유
            "content" => $row["content"],
            // 결재번호
            "appNo" => $row["app_no"],
            // 폼 번호
            "formId" => $row["form_id"],
            //사원uno
            "userId" => $row["user_id"],
            // 근태 조정 id
            "atId" => $row["at_id"]
        );
    }

    $result = array(
        "userAttendList" => $userAttendList
    );

    echo json_encode($result);
}
// 근태 변경
else if ("ATTEND_CHANGE" == $mode) {
    $isModal = $_POST["isModal"];
    if($isModal == 0) {
        $selAttend = $_POST["selAttend"];
    } else {
        $selAttend = $_POST["selMdAttend"];
    }

    switch ($selAttend) {
        case "04":
            $attendCode = "div_att_01";
            break;
        case "05":
            $attendCode= "div_att_02";
            break;
        case "06":
            $attendCode = "div_att_03";
            break;
        case "07":
            $attendCode = "div_att_04";
            break;
        case "10":
            $attendCode = "div_att_05";
            break;
        case "12":
            $attendCode = "div_att_06";
            break;
        default:
            $attendCode = "div_none";
            break;
    }

    $attendDetail = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ?";
    // @CD AS NVARCHAR(30) = ''
    $params[] = $attendCode;

    $userDB->query($SQL, $params);

    while($userDB->next_record()) {
        $row = $userDB->Record;

        $attendDetail[] = array(
            "cdVal" => $row["cd_val"],
            "cdNm" => $row["cd_nm"]
        );
    }

    $result = array(
        "attendDetail" => $attendDetail
    );

    echo json_encode($result);
}
// 승인 버튼 클릭
else if("APPROVE" == $mode) {
    $searchFrom = str_replace('-', '', $_POST["searchFrom"]);
    $chkUserList = $_POST["chkUserList"];
    $userList = array();
    foreach($chkUserList as $userAt) {
        list($uno, $atId) = explode("|", $userAt);
        $userList[] = $uno;
    }
    $chkUserString = implode(',', $userList);
    
    $proceed = false;
    $params = array();
    $SQL  = "[dbo].[PBS_ATTENDBASE_bak1_0300100_S_CheckAppEnable] ";
    // @inGrpID		INT
    $params[] = $grpId;
	// , @inWorkCOID	INT
    $params[] = $_SESSION["user"]["company_id"];
	// , @inWorkID		INT
    $params[] = $user->uno;
	// , @time			NCHAR(4)
    $params[] = date("Hi");
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $enableApp = $userDB->Record;
    $msg = "";

    if($enableApp["result"] == 1) {
        $proceed = true;
    } else {
        $proceed = false;
        $msg = "승인 가능시간이 아닙니다.";
    }

    if($proceed) {
        $params = array();
        $SQL  = "[dbo].[PBS_ATTEND_0300100_U_UserApp] ";
        // @inGrpID		INT				
        $params[] =  $grpId;
        // , @inWorkCOID	INT
        $params[] = $_SESSION["user"]["company_id"];
        // , @inWorkID		INT
        $params[] = $user->uno;
        // , @dt			NCHAR(8)
        $params[] = $searchFrom;
        // , @sUSER_LIST   NVARCHAR(3000)
        $params[] = $chkUserString;
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }

        if($userDB->query($SQL, $params)) {
            $proceed = true;
            $msg = "승인하였습니다.";
        } else {
            $proceed = false;
        }
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
// 취소 버튼
else if ("CANCLE" == $mode) {
    $searchFrom = str_replace('-', '', $_POST["searchFrom"]);
    $chkUserList = $_POST["chkUserList"];
    $userList = array();
    foreach($chkUserList as $userAt) {
        list($uno, $atId) = explode("|", $userAt);
        $userList[] = $uno;
    }
    $chkUserString = implode(',', $userList);

    $proceed = false;
    $params = array();
    $SQL = "[dbo].[PBS_ATTEND_0300100_CONFIRM_CANCLE_U] ";
    // @nGrpID       INT
    $params[] = $grpId;
    // , @nCoID        INT
    $params[] = $_SESSION["user"]["company_id"];
    // , @sDtAtt       NVARCHAR(8)
    $params[] = $searchFrom;
    // , @sUSER_LIST   NVARCHAR(3000)
    $params[] = $chkUserString;
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);

    if($userDB->query($SQL, $params)) {
        $proceed = true;
        $msg = "취소되었습니다.";
    } else {
        $proceed = false;
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
    
}
// 일괄 변경 버튼
else if ("BATCH" == $mode) {
    $selAttend = $_POST["selAttend"];
    $selAtDetail = $_POST["selAtDetail"];
    $searchFrom = str_replace('-', '', $_POST["searchFrom"]);
    $chkUserList = $_POST["chkUserList"];
    $ipAddr = $_SERVER["REMOTE_ADDR"];

    $proceed = true;
    $msg = '';
    // 휴가 일 경우 남은 연차 
    if($selAttend == "04") {
        $params = array();
        $SQL = "[dbo].[PBS_0308000_S] ";
        // @sCD_VAL          AS NVARCHAR(10)
        $params[] = $selAtDetail;
        // ,@sLangKind		  AS NVARCHAR(10)
        $params[] = 'KR';
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        
        $userDB->query($SQL, $params);
        $userDB->next_record();
        $row = $userDB->Record;
        
        $minAnn = '';
        if($row != null && $row["min_ann"] > 0) {
            $minAnn = $row["min_ann"];
        }
        
        $errorCnt = 0;
        foreach($chkUserList as $userAt) {
            list($uno, $atId) = explode("|", $userAt);
            $params = array();
            $SQL = "[dbo].[PBS_ATTEND_0300700_YEAR_VACATION_CHK] ";
            // @nCO_ID		INT
            $params[] = $_SESSION["user"]["company_id"];
            // ,	@nUSER_ID	INT
            $params[] = $uno;
            // ,	@sFR_DT		NVARCHAR(8)
            $params[] = $searchFrom;
            // ,	@sTO_DT		NVARCHAR(8)
            $params[] = $searchFrom;
            // ,   @nREQ_UNIT  NUMERIC(4,1) = 1
            $params[] = $minAnn;
            // ,   @nAT_ID     INT          = 0
            $params[] = 0;
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            $userDB->query($SQL, $params);
            $userDB->next_record();
            $row = $userDB->Record;
    
            if($row["chk_return"] == 1 && $row["rem_count"] < 0) {
                $errorCnt++;
            }
        }
        if($errorCnt > 0) {
            $msg = '근태를 변경할 수 없습니다. 잔여 연차 수를 확인하십시오.';
            $proceed = false;
        }
    }

    $changeCnt = 0;
    if($proceed) {
        foreach($chkUserList as $userAt) {
            list($uno, $atId) = explode("|", $userAt);
            if($atId == 0) {
                $plagWay = "I";
            } else {
                $plagWay = "U";
            }
            $params = array();
            $SQL = "[dbo].[PBS_ATTEND_0300100_IU] ";
            // @inGrpID				INT			-- 그룹코드
            $params[] = $grpId;
            // , @inWorkCOID			INT			-- 회사코드
            $params[] = $_SESSION["user"]["company_id"];
            // , @inWorkID				INT			-- 사용자코드
            $params[] = $user->uno;
            // , @cPlag				Nchar(1)	-- 등록구분 I=추가, U=수정
            $params[] = $plagWay;
            // , @at_id				int
            $params[] = $atId;
            // , @co_id				int
            $params[] = $_SESSION["user"]["company_id"];
            // , @user_id				int
            $params[] = $uno;
            // , @dt_att				Nchar(8)
            $params[] = $searchFrom;
            // , @time_fr				Nchar(4)
            $params[] = '';
            // , @time_to				Nchar(4)
            $params[] = '';
            // , @dt_closing_hour		datetime
            $params[] = '';
            // , @dt_office_going_hour	datetime
            $params[] = '';
            // , @div_att			    NVARCHAR(3)
            $params[] = $selAttend;
            // , @content				Ntext
            $params[] = "일괄변경";
            // , @ip_fr				NVARCHAR(100)
            $params[] = $ipAddr;
            // , @ip_to				NVARCHAR(100)
            $params[] = $ipAddr;
            // , @sDiv_Sub_Att         NVARCHAR(10) --r
            $params[] = $selAtDetail;
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            if($userDB->query($SQL, $params)) {
                $params = array();
                $SQL = "[dbo].[PBS_ATTEND_HISTORY_I] ";
                // @nUSER_ID			INT
                $params[] = $uno;
                // , @sDT_ATT			NCHAR(16)
                $params[] = $searchFrom;
                // , @sACT_DIV			NVARCHAR(20)
                $params[] = "일괄변경";
                // , @sATT_TIME		NVARCHAR(20)
                $params[] = '';
                // , @sDIV_ATT			NVARCHAR(20)
                $params[] = $selAttend;
                // , @sDIV_APP			NVARCHAR(20)
                $params[] = '';
                // , @sAPP_NO			INT
                $params[] = 0;
                // , @sAPP_TITLE		NVARCHAR(200)
                $params[] = "[그룹웨어 메뉴에서 일괄변경]";
                // , @sCONTENT			NTEXT
                $params[] = '';
                // , @sIP				NVARCHAR(100)
                $params[] = $ipAddr;
                // , @nCR_USER_ID		INT
                $params[] = $user->uno;
                for($i = 0; $i < count($params); $i++) {
                    if ($i > 0) {
                        $SQL .= ", ";
                    }
                    $SQL .= "?";
                }
                if($userDB->query($SQL, $params)) {
                    $changeCnt++;
                    $proceed = true;
                }
            }
        }
    }

    if($proceed) {
        $msg = $changeCnt . "건 변경되었습니다.";
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
?>
