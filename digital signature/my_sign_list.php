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
    $selIsSign = $_POST["selIsSign"];
    $selSignYear = $_POST["selSignYear"];
    $txtSearchValue = $_POST["txtSearchValue"];
    $pageNo = $_POST["pageNo"];
    $params = array();
    $SQL = "WITH my_sign AS (
                                SELECT s.sno, s_title, u.uno, CASE WHEN u.uno IS NULL THEN 0 ELSE 1 END AS is_sign, CAST(u.sign_date AS DATE) as sign_date
                                FROM [dbo].[sign_list] as s
                                LEFT OUTER JOIN [dbo].[sign_user_list] as u on s.sno = u.sno and u.uno = ?
                                WHERE s_title LIKE '%{$txtSearchValue}%' 
                                AND (GETDATE() >= s.from_date AND GETDATE() <= s.to_date OR u.uno IS NOT NULL) ";
    if($selIsSign != "2") {
        $SQL.= "AND CASE WHEN u.uno IS NULL THEN 0 ELSE 1 END = {$selIsSign}";
    }
    if($selSignYear != "0") {
        $SQL .= "AND YEAR(sign_date) = '{$selSignYear}'";
    }
    $SQL.= ") 
            SELECT COUNT(*) AS cnt FROM my_sign";
    $params[] = $_SESSION["user"]["uno"];
    $userDB->query($SQL, $params);
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
    $SQL = "WITH my_sign AS (
                                SELECT s.sno, s_title, u.uno, CASE WHEN u.uno IS NULL THEN 0 ELSE 1 END AS is_sign, CONVERT(VARCHAR(10), u.sign_date, 120) as sign_date, ROW_NUMBER() OVER (ORDER BY s.to_date, s.sno DESC) AS rownum,
                                    CASE WHEN u.uno IS NULL THEN s.file_path ELSE u.sign_path END AS file_path, s.s_kind_cd
                                FROM [dbo].[sign_list] as s
                                LEFT OUTER JOIN [dbo].[sign_user_list] as u on s.sno = u.sno and u.uno = ?
                                WHERE s_title LIKE '%{$txtSearchValue}%' 
                                AND (GETDATE() >= s.from_date AND GETDATE() <= s.to_date OR u.uno IS NOT NULL) ";
    if($selIsSign != "2") {
        $SQL.= "AND CASE WHEN u.uno IS NULL THEN 0 ELSE 1 END = {$selIsSign}";
    }
    if($selSignYear != "0") {
        $SQL .= "AND YEAR(sign_date) = '{$selSignYear}'";
    }
    $SQL .= ") 
            SELECT * FROM my_sign
            WHERE rownum BETWEEN ? and ?
            ORDER BY sign_date DESC";
    $params[] = $_SESSION["user"]["uno"];
    $params[] = $rownumFr;
    $params[] = $rownumTo;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $infoList[] = array(
            "no" => $startNo--,
            "sno" => $row["sno"],
            "sTitle" => $row["s_title"],
            "isSign" => $row["is_sign"],
            "signDate" => $row["sign_date"],
            "filePath" => $row["file_path"],
            "kindCd" => $row["s_kind_cd"]
        );
    }
    
    $result = array(
        "infoList" => $infoList,
        "pageNo" => $pageNo,
        "pageList" => $pageList,
        "uno" => $_SESSION["user"]["uno"]
    );

    echo json_encode($result);
}
// 초기화면
else if($mode == "INIT") {
    $yearList = array();
    $params = array();
    $SQL = "SELECT DISTINCT YEAR(sign_date) as sign_year
            FROM dbo.sign_list S
            INNER JOIN dbo.sign_user_list U ON S.sno = U.sno and U.uno = ?
            ORDER BY sign_year DESC";
    $params[] = $_SESSION["user"]["uno"];
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $yearList[] = array(
            $row["sign_year"]
        );
    }

    $result = array(
        "yearList" => $yearList
    );

    echo json_encode($result);
}

?>
