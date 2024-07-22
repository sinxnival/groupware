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

if($mode == "LIST") {
    $txtSearchValue = $_POST["txtSearchValue"];
    $selProcess = $_POST["selProcess"];
    $selDateType = $_POST["selDateType"];
    $searchFrom = $_POST["searchFrom"];
    $searchTo = $_POST["searchTo"];
    $pageNo = $_POST["pageNo"];
    $teamId = $_SESSION["user"]["team_id"];

    $SQL = "WITH sign_list AS(
                                SELECT sno, s_title, from_date, to_date, file_path, reg_uno, u.user_nm
                                FROM [dbo].[sign_list] S
                                INNER JOIN [dbo].[TCMG_USER] U on s.reg_uno = u.user_id
                                INNER JOIN [dbo].[TCMG_DEPT] D on s.dept_id = d.dept_id
                                WHERE (s_title LIKE '%{$txtSearchValue}%' OR u.user_nm LIKE '%{$txtSearchValue}%') ";
    if($selProcess == 0) {
        $SQL .= "AND GETDATE() BETWEEN from_date AND to_date ";
    } else if($selProcess == 1) {
        $SQL .= "AND GETDATE() NOT BETWEEN from_date AND to_date ";
    }
    if($selDateType == 1) {
        $SQL .= "AND (from_date BETWEEN '{$searchFrom}' AND '{$searchTo}') ";
    } else {
        $SQL .= "AND (to_date BETWEEN '{$searchFrom}' AND '{$searchTo}') ";
    }
    if($teamId != '90') {
        $SQL .= "AND S.dept_id = {$teamId}";
    }
    $SQL .= ") SELECT COUNT(*) AS cnt FROM sign_list;";
    $userDB->query($SQL);
    $userDB->next_record();
    $row = $userDB->Record;
    $totalCnt = $row["cnt"];
    if (empty($totalCnt)) {
        $totalCnt = 0;
    }

    $pageList = getPageList($pageNo, $totalCnt);

    $startNo = $totalCnt - (($pageNo - 1) * $pageUnit);

    $rownumFr = $pageUnit * ($pageNo - 1) + 1;
    $rownumTo = $pageUnit * $pageNo;

    $params = array();
    $infoList = array();
    $SQL = "WITH sign_list AS (
                                SELECT sno, s_title, CONVERT(VARCHAR(10), from_date, 120) as from_date, CONVERT(VARCHAR(10), to_date, 120) as to_date, file_path, reg_uno, u.user_nm, d.dept_nm,
                                ROW_NUMBER() OVER (ORDER BY s.to_date, s.sno DESC) AS rownum
                                FROM [dbo].[sign_list] S
                                INNER JOIN [dbo].[TCMG_USER] U on s.reg_uno = u.user_id
                                INNER JOIN [dbo].[TCMG_DEPT] D on s.dept_id = d.dept_id
                                WHERE (s_title LIKE '%{$txtSearchValue}%' OR u.user_nm LIKE '%{$txtSearchValue}%') ";
    if($selProcess == 0) {
        $SQL .= "AND GETDATE() BETWEEN from_date AND to_date ";
    } else if($selProcess == 1) {
        $SQL .= "AND GETDATE() NOT BETWEEN from_date AND to_date ";
    }
    if($selDateType == 1) {
        $SQL .= "AND (from_date BETWEEN '{$searchFrom}' AND '{$searchTo}') ";
    } else {
        $SQL .= "AND (to_date BETWEEN '{$searchFrom}' AND '{$searchTo}') ";
    }
    if($teamId != '90') {
        $SQL .= "AND S.dept_id = {$teamId}";
    }
    $SQL .= ") SELECT * FROM sign_list
            WHERE rownum BETWEEN ? and ?
            ORDER BY SNO DESC";
    $params[] = $rownumFr;
    $params[] = $rownumTo;

    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $infoList[] = array(
            "no" => $startNo--,
            "sno" => $row["sno"],
            "sTitle" => $row["s_title"],
            "fromDate" => $row["from_date"],
            "toDate" => $row["to_date"],
            "regUno" => $row["regUno"],
            "userNm" => $row["user_nm"],
            "filePath" => $row["file_path"],
            "deptNm" => $row["dept_nm"]
        );
    }

    $result = array(
        "infoList" => $infoList,
        "pageNo" => $pageNo,
        "pageList" => $pageList
    );

    echo json_encode($result);
}
// 초기 화면
else if ("INIT" == $mode) {
    $tempDate = new DateTime();
    $searchTo = $tempDate->format("Y-m-d");
    $searchFrom = $tempDate->format("Y") . "-01-01";

    $result = array(
        "searchFrom" => $searchFrom,
        "searchTo" => $searchTo
    );

    echo json_encode($result);
}
// 서명 / 미서명 리스트
else if($mode == "USER_LIST") {
    $sno = $_POST["sno"];

    $members = $user->getUserMemberAll();
    $depts = $user->getUserDeptInfo();
    
    $params = array();
    $signUserList = array();
    $signUnoList = array();
    $SQL = "SELECT sno, uno, CONVERT(VARCHAR(10), sign_date, 120) as sign_date, sign_path, u.user_nm, d.dept_nm, gra.cd_nm as grade_nm, s.sign_date as order_date, d.dept_id
            FROM dbo.sign_user_list S
            INNER JOIN dbo.TCMG_USERDEPT UD ON s.uno = UD.user_id
            INNER JOIN dbo.TCMG_USER U ON UD.user_id=U.user_id
            INNER JOIN dbo.TCMG_DEPT D ON UD.dept_id = D.dept_id
            LEFT OUTER JOIN dbo.FCMT_CD_LANG('grade', 'KR') gra on gra.cd_val = ud.grade AND ( gra.co_id = 0 OR gra.co_id = UD.co_id )
            LEFT OUTER JOIN dbo.FCMT_CD_LANG('duty', 'KR') du on du.cd_val = ud.duty AND ( du.co_id = 0 OR du.co_id = UD.co_id )
            WHERE s.sno = ?
            ORDER BY order_date desc";
    $params[] = $sno;
    $userDB->query($SQL, $params);
    $signCnt = $userDB->nf();

    while($userDB->next_record()) {
        $row = $userDB->Record;

        array_push($signUnoList, $row["uno"]);

        $dept_id = $row["dept_id"];
        $dept_nm2 = $depts[$dept_id]["display_name"];

        $signUserList[] = array(
            "sno" => $row["sno"],
            "uno" => $row["uno"],
            "signDate" => $row["sign_date"],
            "signPath" => $row["sign_path"],
            "userNm" => $row["user_nm"],
            "deptNm" => $row["dept_nm"],
            "deptNm2" => $dept_nm2,
            "gradeNm" => $row["grade_nm"]
        );
    }

    $extUnoList = array(
        9414, 9716, 9946, 1
    );

    $mergeUnoList = array_merge($signUnoList, $extUnoList);

    if(count($mergeUnoList) > 0) {
        $strUnoList = implode(",", $mergeUnoList);
    }

    $unSignUserList = array();
    $SQL = "SELECT u.user_id, u.user_nm, d.dept_nm, gra.cd_nm as grade_nm, d.dept_id,
            CASE WHEN d.dept_id = 11 THEN 1
            WHEN d.par_dept_id = 172 THEN 2
            WHEN d.par_dept_id = 14 THEN 3
            WHEN d.par_dept_id = 176 THEN 4
            WHEN d.par_dept_id = 118 THEN 5
            WHEN d.par_dept_id = 13 THEN 6
            WHEN d.par_dept_id = 61 THEN 7
            WHEN d.par_dept_id = 12 THEN 8
            ELSE 9
            END AS dept_order
            FROM dbo.TCMG_USERDEPT UD
            INNER JOIN dbo.TCMG_USER U ON UD.user_id=U.user_id
            INNER JOIN dbo.TCMG_DEPT D ON UD.dept_id = D.dept_id
            LEFT OUTER JOIN dbo.FCMT_CD_LANG('grade', 'KR') gra on gra.cd_val = ud.grade AND ( gra.co_id = 0 OR gra.co_id = UD.co_id )
            LEFT OUTER JOIN dbo.FCMT_CD_LANG('duty', 'KR') du on du.cd_val = ud.duty AND ( du.co_id = 0 OR du.co_id = UD.co_id )
            WHERE u.fire_yn = 1
            AND u.GW_USER_USE_YN = 1 
            AND u.msg_yn = 1 ";
            // $SQL .= "AND u.mobilegw_use_yn = 1 ";
            $SQL .= "AND UD.hold_office = 1
            AND ud.co_id = 1 
            AND ud.div_attend = 1
            AND ud.enter_dt < (SELECT to_date FROM dbo.sign_list WHERE sno = ?) ";
    if(count($mergeUnoList) > 0) {
        $SQL .= " AND u.user_id NOT IN({$strUnoList})";
    }
    $SQL .= " ORDER BY dept_order, D.view_order, gra.view_order, D.par_dept_id, u.user_nm";
    $params[] = $sno;
    $userDB->query($SQL, $params);
    $unSignCnt = $userDB->nf();
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $dept_id = $row["dept_id"];
        $dept_nm2 = $depts[$dept_id]["display_name"];

        $unSignUserList[] = array(
            "uno" => $row["user_id"],
            "userNm" => $row["user_nm"],
            "deptNm" => $row["dept_nm"],
            "deptNm2" => $dept_nm2,
            "gradeNm" => $row["grade_nm"]
        );
    }
    

    $result = array(
        "signUserList" => $signUserList,
        "signCnt" => $signCnt,
        "unSignUserList" => $unSignUserList,
        "unSignCnt" => $unSignCnt
    );

    echo json_encode($result);

}
?>
