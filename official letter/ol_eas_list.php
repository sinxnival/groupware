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
    $appbox = $_POST["appbox"];
    $ddlSearchKind = $_POST["ddlSearchKind"];
    $txtSearchValue = $_POST["txtSearchValue"];
    $pageNo = $_POST["pageNo"];

    $totalCnt = 0;
    if($appbox == "SUBMIT") {
        $SQL = "SELECT D.ONO, DOC_CD, TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        , CASE
                                WHEN NVL(S.HAS_DECREE,0) = 1
                                    OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                    THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                                WHEN NVL(S.STARTED_STEPS,0) > 0
                                    THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                                ELSE '작성완료'                  -- 아무도 액션 안함
                            END AS PROC_STATUS, D.STATUS
                FROM EAS_DOC_INFO D
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE (WRITER = :uno OR RECEIVE_USER = :uno)
                AND (D.STATUS IS NULL OR D.STATUS != '06') ";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue
        );
    } else if($appbox == "PENDING") {
        $SQL = "SELECT A.ONO, A.STEP_ORDER, A.APPR_KIND, D.DOC_CD, D.TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                    , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                FROM EAS_APPR_STEP A
                INNER JOIN EAS_DOC_INFO D ON A.ONO = D.ONO
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE A.APPROVER = :uno
                AND A.STATUS IS NULL
                AND (D.STATUS IS NULL OR D.STATUS != '06')
                AND NOT EXISTS (
                    SELECT 1 FROM EAS_APPR_STEP P
                    WHERE P.ONO = A.ONO
                        AND P.STEP_ORDER < A.STEP_ORDER
                        AND (P.STATUS IS NULL OR P.STATUS = '02' OR P.STATUS = '04' OR P.STATUS = '05')
                )";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue
        );
    } else if($appbox == "COMPLETE") {
        $SQL = "SELECT A.ONO, A.STEP_ORDER, A.APPR_KIND, D.DOC_CD, D.TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                    , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                FROM EAS_APPR_STEP A
                INNER JOIN EAS_DOC_INFO D ON A.ONO = D.ONO
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE A.APPROVER = :uno
                AND A.IS_SIGN = 'Y' ";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue
        );
    } else if($appbox == "RETURN") {
        $SQL = "SELECT D.ONO, D.DOC_CD, D.TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                    , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                FROM EAS_DOC_INFO D
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE D.WRITER = :uno
                AND EXISTS (
                    SELECT 1
                    FROM EAS_APPR_STEP A
                    WHERE A.ONO = D.ONO
                        AND A.STATUS IN ('02', '04')
                )";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue
        ); 
    } else if($appbox == "DELEGATE") {
        $SQL = "SELECT A.ONO, A.STEP_ORDER, A.APPR_KIND, D.DOC_CD, D.TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                    , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                FROM EAS_APPR_STEP A
                INNER JOIN EAS_DOC_INFO D ON A.ONO = D.ONO
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE A.APPROVER = :uno
                AND A.IS_SIGN = 'N'
                AND A.ONO IN (
                    SELECT ONO
                    FROM EAS_APPR_STEP
                    WHERE IS_DELEGATE = 'Y'
                        AND STATUS = '05'
                )";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue
        ); 
    }  else if($appbox == "RECEIVE") {
        $SQL = "SELECT DEPT_ID FROM SYS_USER_SET
                WHERE UNO = :uno";
        $params = array(
            ":uno" => $user->uno
        );
        $db->query($SQL, $params);
        $db->next_record();
        $row = $db->Record;
        $deptNo = $row["dept_id"];

        $SQL = "SELECT D.ONO, D.DOC_CD, D.TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                    , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                FROM EAS_DOC_INFO D
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE D.ONO IN (
                    SELECT R.ONO
                    FROM EAS_DOC_RECEIPOPER R
                    WHERE 
                    (R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = :uno)
                    OR (
                        R.CO_DEPT_USER_KIND = 'D'
                        AND R.CO_DEPT_USER_ID IN (
                            SELECT DEPT_NO
                            FROM SYS_DEPT_SET
                            START WITH DEPT_NO = :deptNo
                            CONNECT BY PRIOR DEPT_NO = PARENT_NO
                        )
                    )
                )";
        $params = array(
            ":uno" => $user->uno,
            ":deptNo" => $deptNo,
            ":txtSearchValue" => $txtSearchValue
        ); 
    } else if($appbox == "STORAGE") {
        $SQL = "SELECT ONO, DOC_CD, TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        ,'작성중' AS PROC_STATUS, D.STATUS
                FROM EAS_DOC_INFO D
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                WHERE (WRITER = :uno OR RECEIVE_USER = :uno)
                AND STATUS = '06'";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue
        );
    }
    if($ddlSearchKind == "ALL") {
        $SQL .= "AND (
                        D.DOC_CD     LIKE '%' || :txtSearchValue || '%' OR
                        D.TITLE      LIKE '%' || :txtSearchValue || '%' OR
                        U.USER_NAME  LIKE '%' || :txtSearchValue || '%'
                )";
    } else {
        $SQL .= "AND ({$ddlSearchKind} LIKE '%' || :txtSearchValue || '%')";
    }
    $db->query($SQL, $params);
    $totalCnt = $db->nf();
    $pageList = getPageList($pageNo, $totalCnt);
    $startRow = ($pageNo - 1) * $pageUnit + 1;
    $endRow = $pageNo * $pageUnit;

    $infoList = array();
    $SQL = "SELECT * FROM (";
    if($appbox == "SUBMIT") {
        $SQL .= "SELECT D.ONO, DOC_CD, TITLE, U.USER_NAME, TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                        ,ROW_NUMBER() OVER (ORDER BY D.DOC_CD DESC) AS RNUM
                FROM EAS_DOC_INFO D
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE (WRITER = :uno OR RECEIVE_USER = :uno)
                AND (D.STATUS IS NULL OR D.STATUS != '06') ";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow
        );
    } else if($appbox == "PENDING") {
        $SQL .= "SELECT A.ONO, A.STEP_ORDER, A.APPR_KIND, D.DOC_CD, D.TITLE, U.USER_NAME,
                        TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                        ,ROW_NUMBER() OVER (ORDER BY D.DOC_CD DESC) AS RNUM
                FROM EAS_APPR_STEP A
                INNER JOIN EAS_DOC_INFO D ON A.ONO = D.ONO
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE A.APPROVER = :uno
                AND A.STATUS IS NULL
                AND (D.STATUS IS NULL OR D.STATUS != '06')
                AND NOT EXISTS (
                    SELECT 1 FROM EAS_APPR_STEP P
                    WHERE P.ONO = A.ONO
                        AND P.STEP_ORDER < A.STEP_ORDER
                        AND (P.STATUS IS NULL OR P.STATUS = '02' OR P.STATUS = '04' OR P.STATUS = '05')
                )";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow
        );
    } else if($appbox == "COMPLETE") {
        $SQL .= "SELECT A.ONO, A.STEP_ORDER, A.APPR_KIND, D.DOC_CD, D.TITLE, U.USER_NAME, 
                        TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                        ,ROW_NUMBER() OVER (ORDER BY D.DOC_CD DESC) AS RNUM
                FROM EAS_APPR_STEP A
                INNER JOIN EAS_DOC_INFO D ON A.ONO = D.ONO
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE A.APPROVER = :uno
                AND A.IS_SIGN = 'Y' ";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow
        );
    } else if($appbox == "RETURN") {
        $SQL .= "SELECT D.ONO, D.DOC_CD, D.TITLE, U.USER_NAME, 
                        TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                        ,ROW_NUMBER() OVER (ORDER BY D.DOC_CD DESC) AS RNUM
                FROM EAS_DOC_INFO D
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE D.WRITER = :uno
                AND EXISTS (
                    SELECT 1
                    FROM EAS_APPR_STEP A
                    WHERE A.ONO = D.ONO
                        AND A.STATUS IN ('02', '04')
                )";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow
        ); 
    } else if($appbox == "DELEGATE") {
        $SQL .= "SELECT A.ONO, A.STEP_ORDER, A.APPR_KIND, D.DOC_CD, D.TITLE, U.USER_NAME, 
                        TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        , CASE
                            WHEN NVL(S.HAS_DECREE,0) = 1
                                OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                            WHEN NVL(S.STARTED_STEPS,0) > 0
                                THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                            ELSE '작성완료'                  -- 아무도 액션 안함
                        END AS PROC_STATUS, D.STATUS
                        ,ROW_NUMBER() OVER (ORDER BY D.DOC_CD DESC) AS RNUM
                FROM EAS_APPR_STEP A
                INNER JOIN EAS_DOC_INFO D ON A.ONO = D.ONO
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                LEFT JOIN (
                    SELECT
                        ONO,
                        MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                        COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                        SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                        SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                    FROM EAS_APPR_STEP
                    GROUP BY ONO
                ) S ON S.ONO = D.ONO
                WHERE A.APPROVER = :uno
                AND A.IS_SIGN = 'N'
                AND A.ONO IN (
                    SELECT ONO
                    FROM EAS_APPR_STEP
                    WHERE IS_DELEGATE = 'Y'
                        AND STATUS = '05'
                )";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow
        ); 
    }  else if($appbox == "RECEIVE") {
        $SQL = "SELECT DEPT_ID FROM SYS_USER_SET
                WHERE UNO = :uno";
        $params = array(
            ":uno" => $user->uno
        );
        $db->query($SQL, $params);
        $db->next_record();
        $row = $db->Record;
        $deptNo = $row["dept_id"];

        $SQL = "SELECT * FROM (
                                    SELECT D.ONO, D.DOC_CD, D.TITLE, U.USER_NAME, 
                                            TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                                            , CASE
                                                WHEN NVL(S.HAS_DECREE,0) = 1
                                                    OR (NVL(S.TOTAL_STEPS,0) > 0 AND NVL(S.TOTAL_STEPS,0) = NVL(S.COMPLETED_STEPS,0))
                                                    THEN '결재완료'              -- 전결이 있거나, 모든 스텝이 01/03로 완료
                                                WHEN NVL(S.STARTED_STEPS,0) > 0
                                                    THEN '진행중'                -- 한 명이라도 결재/합의 등 액션 발생
                                                ELSE '작성완료'                  -- 아무도 액션 안함
                                            END AS PROC_STATUS, D.STATUS
                                            ,ROW_NUMBER() OVER (ORDER BY D.DOC_CD DESC) AS RNUM
                                    FROM EAS_DOC_INFO D
                                    INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                                    LEFT JOIN (
                                        SELECT
                                            ONO,
                                            MAX(CASE WHEN STATUS IN ('02', '04', '05') THEN 1 ELSE 0 END) AS HAS_DECREE,          -- 전결 여부
                                            COUNT(*) AS TOTAL_STEPS,                                                -- 전체 단계
                                            SUM(CASE WHEN STATUS IN ('01','03') THEN 1 ELSE 0 END) AS COMPLETED_STEPS, -- 완료 단계(결재완료/합의완료)
                                            SUM(CASE WHEN STATUS IS NOT NULL THEN 1 ELSE 0 END) AS STARTED_STEPS   -- 시작된 단계(누군가 액션함)
                                        FROM EAS_APPR_STEP
                                        GROUP BY ONO
                                    ) S ON S.ONO = D.ONO
                                    WHERE D.ONO IN (
                                        SELECT R.ONO
                                        FROM EAS_DOC_RECEIPOPER R
                                        WHERE 
                                        (R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = :uno)
                                        OR (
                                            R.CO_DEPT_USER_KIND = 'D'
                                            AND R.CO_DEPT_USER_ID IN (
                                                SELECT DEPT_NO
                                                FROM SYS_DEPT_SET
                                                START WITH DEPT_NO = :deptNo
                                                CONNECT BY PRIOR DEPT_NO = PARENT_NO
                                            )
                                        )
                                    )";
        $params = array(
            ":uno" => $user->uno,
            ":deptNo" => $deptNo,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow
        ); 
    } else if($appbox == "STORAGE") {
        $SQL .= "SELECT ONO, DOC_CD, TITLE, U.USER_NAME, 
                        TO_CHAR(COALESCE(ISSUE_DATE, RECEIVE_DATE), 'YYYY-MM-DD') AS ISSUE_DATE
                        ,'작성중' AS PROC_STATUS, D.STATUS
                        ,ROW_NUMBER() OVER (ORDER BY D.DOC_CD DESC) AS RNUM
                FROM EAS_DOC_INFO D
                INNER JOIN SYS_USER_SET U ON (D.WRITER = U.UNO OR D.RECEIVE_USER = U.UNO)
                WHERE (WRITER = :uno OR RECEIVE_USER = :uno)
                AND STATUS = '06'";
        $params = array(
            ":uno" => $user->uno,
            ":txtSearchValue" => $txtSearchValue,
            ":startRow" => $startRow,
            ":endRow" => $endRow
        );
    }
    if($ddlSearchKind == "ALL") {
        $SQL .= "AND (
                        D.DOC_CD     LIKE '%' || :txtSearchValue || '%' OR
                        D.TITLE      LIKE '%' || :txtSearchValue || '%' OR
                        U.USER_NAME  LIKE '%' || :txtSearchValue || '%'
                )";
    } else {
        $SQL .= "AND ({$ddlSearchKind} LIKE '%' || :txtSearchValue || '%')";
    }
    
    $SQL .= ") ";
    $SQL .= "WHERE RNUM BETWEEN :startRow AND :endRow ";

    $db->query($SQL, $params);
    while($db->next_record()) {
        $row = $db->Record;

        $procStatus = $row["proc_status"];
        if($procStatus == "결재완료") {
            if($row["status"] == '02') {
                $procStatus .= " (반려)";
            } else if($row["status"] == '04') {
                $procStatus .= " (합의거부)";
            } else if($row["status"] == '05') {
                $procStatus .= " (전결)";
            }
        }

        $infoList[] = array(
            "ono" => $row["ono"],
            "docCd" => $row["doc_cd"],
            "title" => $row["title"],
            "userNm" => $row["user_name"],
            "issueDate" => $row["issue_date"],
            "procStatus" => $procStatus
        );
    }

    $result = array(
        "infoList" => $infoList,
        "pageNo" => $pageNo,
        "pageList" => $pageList
    );

    echo json_encode($result);
}

?>

