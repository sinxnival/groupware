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

if("INIT" == $mode) {
    $officeList = array();
    $params = array();
    $SQL = "[dbo].[PSM_WORK_0101000_S_H] ";
    // @inGrpID AS INT
    $params[] = $grpId;
    // , @inCOID		AS INT
    $params[] = $_SESSION["user"]["company_id"];
    // , @inWorkID	AS INT
    $params[] = 0;
    // , @sLangKind AS NVARCHAR(10) = 'KR'
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

        $officeList[] = array(
            "deptworkId" => $row["work_id"],
            "deptworkNm" => $row["work_nm"]
        );
    }

    //연도
    $yearList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "year";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $yearList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //월
    $monthList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "month";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $monthList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    // 현재 년도, 월
    $today = new DateTime();
    $year = $today->format("Y");
    $month = $today->format("m");

    $result = array(
        "officeList" => $officeList,
        "yearList" => $yearList,
        "monthList" => $monthList,
        "year" => $year,
        "month" => $month
    );

    echo json_encode($result);
}
// 월별근태현황
else if("LIST" == $mode) {
    $ddlYear = $_POST["ddlYear"];
    $ddlMonth = $_POST["ddlMonth"];
    $selOffice = $_POST["selOffice"];

    $monthAttendList = array();
    $params = array();
    $SQL  = "[dbo].[PBS_ATTEND_0300300_S] ";
    // @inGrpID		INT
    $params[] = $grpId;
	// @inWorkCOID	INT
    $params[] = $_SESSION["user"]["company_id"];
	// @inWorkID		INT
    $params[] = 0;
	// @year			NCHAR(4)
    $params[] = $ddlYear;
	// @month		NCHAR(2)
    $params[] = $ddlMonth;
	// @work_id		INT
    $params[] = $selOffice;
	// @sLang		NVARCHAR(10) = 'KR'
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

        $monthAttendList[] = array(
            "dt" => $row["dt"],
            "week" => $row["dw"],
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
            "nowUserCnt" => $row["now_user_count"]
        );
    }

    $result = array(
        "monthAttendList" => $monthAttendList,
        "params" => $params
    );

    echo json_encode($result);
}
?>
