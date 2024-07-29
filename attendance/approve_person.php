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

// 승인자 목록
if ("INIT" == $mode) {
    $approverList = array();
    $params = array();
    $SQL = "[dbo].[PBS_ATTENDAPPLINE_0301200_S_User] ";
    // @inGrpID		INT
    $params[] = $grpId;
	// , @inWorkCOID	INT
    $params[] = $_SESSION["user"]["company_id"];
	// , @inWorkID		INT
    $params[] = $user->uno;
	// , @sLang		NVARCHAR(10) = 'KR'
    $params[] = "KR";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);

    while($userDB->next_record()) {
        $row = $userDB->Record;

        $approverList[] = array(
            "workNm" => $row["work_nm"],
            "deptNm" => $row["dept_nm"],
            "gradeNm" => $row["grade_nm"],
            "userNm" => $row["user_nm"],
            "attappmId" => $row["attappm_id"]
        );
    }

    $result = array(
        "approverList" => $approverList
    );

    echo json_encode($result);
}
// 근태 승인자 추가
else if ("EDIT_USER" == $mode) {
    $addOrDel = $_POST["addOrDel"];
    $attappmId = $_POST["attappmId"];
    list($userId, $coId) = explode('|', $_POST["searchUserId"]);
    $proceed = false;

    $params = array();
    $SQL = "[dbo].[PBS_ATTENDAPPLINE_0301200_ID_User] ";
    // @inGrpID		INT
    $params[] = $grpId;
	// , @inWorkCOID	INT
    $params[] = $_SESSION["user"]["company_id"];
	// , @inWorkID		INT
    $params[] = $user->uno;
	// , @div			NCHAR(1)		-- 0:입력, 1:삭제
    $params[] = $addOrDel;
	// , @attappm_id	INT
    $params[] = $attappmId;
	// , @co_id			INT
    $params[] = $coId;
	// , @work_id		INT
    $params[] = '';
	// , @user_id		INT
    $params[] = $userId;
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }

    if($userDB->query($SQL, $params)) {
        $proceed = true;
    }

    $result = array(
        "proceed" => $proceed
    );

    echo json_encode($result);
}
else if("LIST" == $mode) {
    $attappmId = $_POST["attappmId"];

    // 관리부서
    $managedDept = array();
    $params = array();
    $SQL = "[dbo].[PBS_ATTENDAPPLINE_0301200_S_DeptSelected] ";
    // @inGrpID			INT
    $params[] = $grpId;
	// , @inWorkCOID		INT
    $params[] = $_SESSION["user"]["company_id"];
	// , @inWorkID			INT
    $params[] = $user->uno;
	// , @attappm_id		INT
    $params[] = $attappmId;
	// , @sLang			NVARCHAR(10) = 'KR'
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

        $managedDept[] = array(
            "deptworkNm" => $row["deptwork_nm"],
            "deptNm" => $row["dept_nm"],
            "deptId" => $row["dept_id"],
            "attappdId" => $row["attappd_id"]
        );
    }

    $unManagedDept = array();
    $params = array();
    $SQL = "[dbo].[PBS_ATTENDAPPLINE_0301200_S_DeptSelectEnable] ";
    // @inGrpID			INT
    $params[] = $grpId;
	// , @inWorkCOID		INT
    $params[] = $_SESSION["user"]["company_id"];
	// , @inWorkID			INT
    $params[] = $user->uno;
	// , @sLang			NVARCHAR(10) = 'KR'
    $params[] = "KR";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);

    while($userDB->next_record()) {
        $row = $userDB->Record;

        $unManagedDept[] = array(
            "deptworkNm" => $row["deptwork_nm"],
            "deptNm" => $row["dept_nm"],
            "deptId" => $row["dept_id"]
        );
    }

    $result = array(
        "managedDept" => $managedDept,
        "unManagedDept" => $unManagedDept
    );

    echo json_encode($result);
}

// 부서 반영, 삭제
else if ("EDIT_DEPT" == $mode) {
    $addOrDel = $_POST["addOrDel"];
    $attappmId = $_POST["attappmId"];
    if($addOrDel == 0) {
        $chkDept = $_POST["chkMoveManageDept"];
    } else {
        $chkDept = $_POST["chkDelManageDept"];
    }

    if($chkDept) {
        $errorCnt = 0;
        foreach($chkDept as $info) {
            $params = array();
            // 삭제
            if($addOrDel == 1) {
                list($deptId, $attappdId) = explode("|", $info);
            } else {
                $deptId = $info;
                $attappdId = '';
            }
            $SQL = "[dbo].[PBS_ATTENDAPPLINE_0301200_ID_Dept] ";
            // @inGrpID			INT
            $params[] = $grpId;
            // , @inWorkCOID		INT
            $params[] = $_SESSION["user"]["company_id"];
            // , @inWorkID			INT
            $params[] = $user->uno;
            // , @sDiv				NCHAR(1)  /* 0:신규등록, 1:삭제 */
            $params[] = $addOrDel;
            // , @attappm_id		INT
            $params[] = $attappmId;
            // , @attappd_id			INT
            $params[] = $attappdId;
            // , @dept_id			INT
            $params[] = $deptId;
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            if(!$userDB->query($SQL, $params)) {
                $errorCnt++;
            }
        }
    }

    $result = array(
        "errorCnt" => $errorCnt
    );

    echo json_encode($result);
}
?>
