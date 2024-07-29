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

if("INIT" == $mode) {
    $officeList = array();
    $params = array();
    $SQL = "[dbo].[PSM_DEPTWork_S_H] ";
    // @nCheck	AS INT = 0
    $params[] = 0;
    // @nGrpID	AS INT
    $params[] = $grpId;
    // @nCOID	AS INT
    $params[] = $_SESSION["user"]["company_id"];
    // @sLang	AS NVARCHAR(10) = 'KR'
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

        $officeList[] = array(
            "deptworkId" => $row["deptwork_id"],
            "deptworkNm" => $row["deptwork_nm"]
        );
    }

    $result = array(
        "officeList" => $officeList
    );

    echo json_encode($result);
}
// 주간근태현황 가져오기
else if("LIST" == $mode) {
    $searchFrom = str_replace('-', '', $_POST["searchFrom"]);
    $searchTo = str_replace('-', '', $_POST["searchTo"]);
    $selOffice = $_POST["selOffice"];

    $weekAttendList = array();
    $params = array();
    $SQL = "[dbo].[PBS_ATTEND_0301900_S] ";
    // @nGrpID	INT
    $params[] = $grpId;
    // @nCoID	INT
    $params[] = $_SESSION["user"]["company_id"];
    // @nWorkID	INT
    $params[] = $selOffice;
    // @sDt		NVARCHAR(8)
    $params[] = $searchFrom;
    // @eDt		NVARCHAR(8)
    $params[] = $searchTo;
    // @sLang	NVARCHAR(10) = 'KR'
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

        $dept_nm2 = $row["dept_nm2"];
        if(isset($members) && is_array($members) && isset($members[$user->uno]) && is_array($members[$user->uno]) && $members[$user->uno] && isset($depts) && is_array($depts))
        {
            $member = $members[$user->uno];
            $dept_id = $row["dept_id"];
            $dept_nm2 = $depts[$dept_id]["display_name"];
            //echo $dept_nm2;
        }
        $row["dept_nm2"] = $dept_nm2;
        $weekAttendList[] = $row;
    }

    $result = array(
        "weekAttendList" => $weekAttendList
    );
 
    echo json_encode($result);
}
?>
