<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

require_once "../../lib/include.php";
require_once "../vendor/autoload.php";
require_once "../common/func.php";

//세션 만료일 경우
if (!isset($_SESSION["user"]["uno"])) {
    header("Location: ../logout.php");
    //종료
    exit();
}

// 새 시트 생성 및 이름 설정
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 기본 폰트 및 행 높이
$spreadsheet->getDefaultStyle()->getFont()->setSize(10);
$sheet->getDefaultRowDimension()->setRowHeight(20);

// 헤더 정의
$headers = [
    "A1" => "순번",
    "B1" => "문서번호",
    "C1" => "시행일",
    "D1" => "제목",
    "E1" => "수신처",
    "F1" => "참조처",
    "G1" => "발신명의",
    "H1" => "분류",
    "I1" => "본부명 / Proj.",
    "J1" => "작성자",
    "K1" => "심의",
    "L1" => "승인",
    "M1" => "결재방법",
    "N1" => "붙임파일",
    "O1" => "발신방법"
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// 헤더 스타일 적용
$sheet->getStyle('A1:O1')->getFont()->setBold(true);
$sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('DCDCDC');

// 너비 조정
$columnWidths = [10, 20, 15, 30, 20, 20, 15, 15, 25, 15, 20, 15, 15, 25, 25, 20];
$colIdx = 'A';
foreach ($columnWidths as $width) {
    $sheet->getColumnDimension($colIdx)->setWidth($width);
    $colIdx++;
}

$SQL = "SELECT DEPT_ID FROM SYS_USER_SET
WHERE UNO = :uno";
$params = array(
":uno" => $user->uno
);
$db->query($SQL, $params);
$db->next_record();
$row = $db->Record;
$deptNo = $row["dept_id"];
$readGradeCondition = getReadGradeCondition();
$storePeriodCondiion = getStorePeriodCondiion();

$SQL = "WITH HIER_DEPT AS (
                    SELECT DEPT_NO, DEPT_NAME
                    FROM SYS_DEPT_SET
                    WHERE LEVEL_NO = 3
                    CONNECT BY PRIOR DEPT_NO = PARENT_NO
                    START WITH LEVEL_NO = 3 
                )
                SELECT E.ONO,
                        CASE 
                            WHEN E.OUT_DOC_CD IS NOT NULL THEN E.OUT_DOC_CD 
                            ELSE E.DOC_CD 
                        END AS DOC_CD,
                        E.READ_DEPT, E.RECIPIENT, E.REFERENCE, E.SENDER, E.WRITER, 
                        U.USER_NAME, A.APPROVERS, E.APPROVAL_KIND, E.APPROVAL_BASIS, 
                        TO_CHAR(E.ISSUE_DATE, 'YYYY-MM-DD') AS ISSUE_DATE, 
                        E.TITLE, E.PJT_NAME, F.ATCH_FILES, E.SEND_KIND, H.DEPT_NAME, 
                        FA.FILE_NAME, FA.FILE_SAVE, ROW_NUMBER() OVER (ORDER BY E.ISSUE_DATE, E.DOC_CD) AS RNUM
                FROM EAS_DOC_INFO E
                INNER JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = E.WRITER
                LEFT JOIN (
                    SELECT S.ONO,
                        LISTAGG(TO_CHAR(U.USER_NAME), ',') 
                            WITHIN GROUP (ORDER BY STEP_ORDER) AS APPROVERS
                    FROM EAS_APPR_STEP S
                    INNER JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = S.APPROVER
                    INNER JOIN EAS_DOC_INFO D ON D.ONO = S.ONO
                    WHERE S.APPR_KIND = 'APPROVE'
                    AND S.STATUS IN ('01', '05')  -- 결재완료 or 전결
                    AND (S.APPROVER != D.WRITER AND (D.RECEIVE_USER IS NULL OR S.APPROVER != D.RECEIVE_USER))
                    GROUP BY S.ONO
                ) A ON A.ONO = E.ONO
                LEFT JOIN HIER_DEPT H ON H.DEPT_NO = U.DEPT_ID
                LEFT JOIN (
                    SELECT FA.ONO,
                        LISTAGG(FNO || ':' || FILE_NAME, ', ') 
                            WITHIN GROUP (ORDER BY FNO) AS ATCH_FILES
                    FROM EAS_DOC_ATCH FA
                    WHERE IS_FINAL != 'Y'
                    GROUP BY FA.ONO
                ) F ON F.ONO = E.ONO
                LEFT JOIN EAS_DOC_ATCH FA ON E.ONO = FA.ONO AND FA.IS_FINAL = 'Y'
                WHERE E.DOC_TYPE = :docType 
                AND (E.STATUS IS NULL OR E.STATUS != '06') 
                AND (E.STATUS IS NULL OR E.STATUS != '02') 
                AND (E.STATUS IS NULL OR E.STATUS != '04') 
                AND (
                    E.WRITER = :uno
                    OR E.RECEIVE_USER = :uno
                    OR EXISTS (
                        SELECT 1 FROM EAS_APPR_STEP S
                        WHERE S.ONO = E.ONO
                        AND S.APPROVER = :uno
                    )
                    OR EXISTS (
                        SELECT 1 FROM EAS_DOC_RECEIPOPER R
                        WHERE R.ONO = E.ONO
                        AND R.CO_DEPT_USER_KIND = 'U'
                        AND R.CO_DEPT_USER_ID = :uno
                    )
                    OR EXISTS (
                        SELECT 1 
                        FROM EAS_DOC_RECEIPOPER R
                        WHERE R.ONO = E.ONO
                        AND R.CO_DEPT_USER_KIND = 'D'
                        AND R.CO_DEPT_USER_ID IN (
                            SELECT DEPT_NO 
                            FROM SYS_DEPT_SET 
                            START WITH DEPT_NO = :deptNo 
                            CONNECT BY PRIOR DEPT_NO = PARENT_NO
                        )
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM EAS_APPR_STEP S
                        WHERE S.ONO = E.ONO
                        AND S.APPROVER = :uno
                        AND S.STATUS IS NULL
                        AND NOT EXISTS (
                            SELECT 1 FROM EAS_APPR_STEP P
                            WHERE P.ONO = S.ONO
                            AND P.STEP_ORDER < S.STEP_ORDER
                            AND (
                                P.STATUS IS NULL 
                                OR P.STATUS IN ('02', '04') 
                                OR P.IS_DELEGATE = 'Y'
                            )
                        )
                    )
                    {$readGradeCondition}
                )
                AND {$storePeriodCondiion} ";
$params = array(
    ":docType" => 'S',
    ":deptNo" => $deptNo,
    ":uno" => $user->uno
);
$db->query($SQL, $params);
$totalCnt = $db->nf();

$rowIndex = 2;
while($db->next_record()) {
    $row = $db->Record;

    // 심의, 승인
    $approvers = array_map('trim', explode(',', $row["approvers"]));
    $count = count($approvers);

    if ($count === 1) {
        $consider = $approvers[0];
        $director = $approvers[0];
    } elseif ($count === 2) {
        $consider = $approvers[0];
        $director = $approvers[1];
    } else {
        $director = array_pop($approvers);
        $consider = implode(', ', $approvers);
    }

    $apprKind = '';
    if($row["appr_kind"] == "ELEC") {
        $apprKind = "전자결재";
    } else if($row["appr_kind"] == "WRIT") {
        $apprKind = "서면결재";
    } else if($row["appr_kind"] == "MAIL") {
        $apprKind = "메일결재";
    }

    if(!$row["pjt_name"]) {
        $type = "본사";
    } else {
        $type = "Project";
    }

    //순번
    $seq = $row["rnum"];

    $approvalKind = $row["approval_kind"];
    if($approvalKind == "ELEC") {
        $txtApprovalKind = "전자결재";
    } else if($approvalKind == "EMAIL") {
        $txtApprovalKind = "E-MAIL";
    } else if($approvalKind == "WRIT") {
        $txtApprovalKind = "서면결재";
    }

    $sendKind = str_replace("|", ", ", $row["send_kind"]);

    //분류
    if($row["pjt_name"]) {
        $docScope = "프로젝트";
        $docGroup = $row["pjt_name"];
    } else {
        $docScope = "본사";
        $docGroup = $row["dept_name"];
    }

    $sheet->setCellValue('A' . $rowIndex, $seq);
    $sheet->setCellValue('B' . $rowIndex, $row['doc_cd']);
    $sheet->setCellValue('C' . $rowIndex, $row['issue_date']);
    $sheet->setCellValue('D' . $rowIndex, $row['title']);
    $sheet->setCellValue('E' . $rowIndex, $row['recipient']);
    $sheet->setCellValue('F' . $rowIndex, $row['reference']);
    $sheet->setCellValue('G' . $rowIndex, $row['sender']);
    $sheet->setCellValue('H' . $rowIndex, $docScope);
    $sheet->setCellValue('I' . $rowIndex, $docGroup);
    $sheet->setCellValue('J' . $rowIndex, $row['user_name']);
    $sheet->setCellValue('K' . $rowIndex, $consider);
    $sheet->setCellValue('L' . $rowIndex, $director);
    $sheet->setCellValue('M' . $rowIndex, $txtApprovalKind);
    $sheet->setCellValue('N' . $rowIndex, $row['atch_files']);
    $sheet->setCellValue('O' . $rowIndex, $sendKind);

    $rowIndex++;
}

// 전체 테두리 적용
$lastRow = $rowIndex - 1;
$sheet->getStyle("A1:O{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// 세로 정렬
$sheet->getStyle("A1:O{$lastRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$sheet->getStyle("A2:O{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("D2:D$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // 제목
$sheet->getStyle("N2:N$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // 결재근거

for ($i = 1; $i <= $lastRow; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

// Rename worksheet
$sheet->setTitle('발신');

$receiveSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, '접수');
$spreadsheet->addSheet($receiveSheet);

$spreadsheet->setActiveSheetIndexByName('접수');
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    "A1" => "순번",
    "B1" => "수신번호",
    "C1" => "문서번호",
    "D1" => "발신명의",
    "E1" => "제목",
    "F1" => "수신명의",
    "G1" => "참조",
    "H1" => "분류",
    "I1" => "수신매체",
    "J1" => "접수일자",
    "K1" => "소속",
    "L1" => "접수자",
    "M1" => "처리상태",
    "N1" => "결재방법",
    "O1" => "결재근거"
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// 4. 헤더 스타일 적용
$sheet->getStyle('A1:O1')->getFont()->setBold(true);
$sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:O1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$sheet->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('DCDCDC');

// 5. 열 너비 설정 (필요시 조정 가능)
$columnWidths = [10, 20, 20, 15, 30, 20, 20, 15, 15, 15, 20, 15, 15, 15, 30];
$colIdx = 'A';
foreach ($columnWidths as $width) {
    $sheet->getColumnDimension($colIdx)->setWidth($width);
    $colIdx++;
}

$SQL = "WITH HIER_DEPT AS (
            SELECT DEPT_NO, DEPT_NAME
            FROM SYS_DEPT_SET
            WHERE LEVEL_NO = 3
            CONNECT BY PRIOR DEPT_NO = PARENT_NO
            START WITH LEVEL_NO = 3
        ),
        EBR_STEP AS (
            SELECT ONO, EBR_PROCESS
            FROM (
                SELECT ONO, EBR_PROCESS,
                    ROW_NUMBER() OVER (PARTITION BY ONO ORDER BY STEP_ORDER DESC) AS RN
                FROM EAS_APPR_STEP
                WHERE EBR_PROCESS IS NOT NULL
            )
            WHERE RN = 1
        ),
        ROP AS (
            SELECT R.ONO,
                LISTAGG(
                    CASE 
                        WHEN R.CO_DEPT_USER_KIND = 'U' THEN U.USER_NAME
                        WHEN R.CO_DEPT_USER_KIND = 'D' THEN V.DEPT_NAME
                    END,
                    '/'
                ) WITHIN GROUP (ORDER BY R.CO_DEPT_USER_KIND, R.CO_DEPT_USER_ID) AS CO_RECEIVER_LIST
            FROM EAS_DOC_RECEIPOPER R
            LEFT JOIN COMMON.V_BIZ_USER_SET U 
                ON R.CO_DEPT_USER_KIND = 'U' AND R.CO_DEPT_USER_ID = U.UNO
            LEFT JOIN V_SYS_DEPT_SET V 
                ON R.CO_DEPT_USER_KIND = 'D' AND R.CO_DEPT_USER_ID = V.DEPT_NO
            GROUP BY R.ONO
        ),
        DOC_REF_STATUS AS (
            SELECT REF_DOC, REF_ONO,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM EAS_APPR_STEP S
                        WHERE S.ONO = REF_ONO
                        AND S.STATUS = '05'
                    ) THEN 'O'
                    WHEN (
                        SELECT COUNT(*) FROM EAS_APPR_STEP WHERE ONO = REF_ONO
                    ) = (
                        SELECT COUNT(*) FROM EAS_APPR_STEP WHERE ONO = REF_ONO AND STATUS IN ('01', '03')
                    ) THEN 'O'
                    ELSE 'X'
                END AS REF_DOC_DONE
            FROM (
                SELECT R.REF_DOC, D.ONO AS REF_ONO,
                    ROW_NUMBER() OVER (PARTITION BY R.REF_DOC ORDER BY D.ISSUE_DATE DESC) AS RN
                FROM EAS_DOC_REF R
                JOIN EAS_DOC_INFO D ON R.ONO = D.ONO
            ) SUB
            WHERE RN = 1
        )
        SELECT 
            E.ONO, E.DOC_CD, E.OUT_DOC_CD, E.SENDER, E.TITLE, E.RECIPIENT,
            TO_CHAR(E.RECEIVE_DATE, 'YYYY-MM-DD') AS RECEIVE_DATE,
            E.RECEIVE_USER, U.USER_NAME,
            H.DEPT_NAME, E.RECEIVE_KIND, E.APPROVAL_BASIS, E.REFERENCE, E.PJT_NAME,
            F.ATCH_FILES, S.EBR_PROCESS, RA.RG_VAL, ROP.CO_RECEIVER_LIST,
            NVL(RS.REF_DOC_DONE, 'X') AS REF_DOC_DONE, ROW_NUMBER() OVER (ORDER BY E.RECEIVE_DATE, E.DOC_CD) AS RNUM
        FROM EAS_DOC_INFO E
        LEFT JOIN COMMON.V_BIZ_USER_SET U ON U.UNO = E.RECEIVE_USER
        LEFT JOIN (
            SELECT ONO,
                LISTAGG(FNO || ':' || FILE_NAME, ', ') 
                    WITHIN GROUP (ORDER BY FNO) AS ATCH_FILES
            FROM EAS_DOC_ATCH
            WHERE IS_FINAL != 'Y'
            GROUP BY ONO
        ) F ON F.ONO = E.ONO
        LEFT JOIN HIER_DEPT H ON H.DEPT_NO = U.DEPT_ID
        LEFT JOIN EBR_STEP S ON S.ONO = E.ONO
        LEFT JOIN EAS_READ_AUTH RA ON RA.RG_CD = E.RG_CD
        LEFT JOIN ROP ON ROP.ONO = E.ONO
        LEFT JOIN DOC_REF_STATUS RS ON RS.REF_DOC = E.ONO
        WHERE E.DOC_TYPE = :docType
        AND (E.STATUS IS NULL OR E.STATUS != '06')
        AND (E.STATUS IS NULL OR E.STATUS != '02') 
        AND (E.STATUS IS NULL OR E.STATUS != '04') 
        AND (
            E.WRITER = :uno
            OR E.RECEIVE_USER = :uno
            OR EXISTS (
                SELECT 1 FROM EAS_APPR_STEP S
                WHERE S.ONO = E.ONO AND S.APPROVER = :uno
            )
            OR EXISTS (
                SELECT 1 FROM EAS_DOC_RECEIPOPER R
                WHERE R.ONO = E.ONO
                AND R.CO_DEPT_USER_KIND = 'U'
                AND R.CO_DEPT_USER_ID = :uno
            )
            OR EXISTS (
                SELECT 1 FROM EAS_DOC_RECEIPOPER R
                WHERE R.ONO = E.ONO
                AND R.CO_DEPT_USER_KIND = 'D'
                AND R.CO_DEPT_USER_ID IN (
                    SELECT DEPT_NO 
                    FROM SYS_DEPT_SET 
                    START WITH DEPT_NO = :deptNo 
                    CONNECT BY PRIOR DEPT_NO = PARENT_NO
                )
            )
            {$readGradeCondition}
        ) 
        AND {$storePeriodCondiion} ";
$params = array(
    ":docType" => 'R',
    ":deptNo" => $deptNo,
    ":uno" => $user->uno
);

$db->query($SQL, $params);
$totalCnt = $db->nf();
$rowIndex = 2;
while($db->next_record()) {
    $row = $db->Record;

    //분류
    if($row["pjt_name"]) {
        $docScope = "프로젝트";
        $docGroup = $row["pjt_name"];
    } else {
        $docScope = "본사";
        $docGroup = $row["dept_name"];
    }

    //순번
    $seq = $row["rnum"];

    $approvalKind = $row["approval_kind"];
    if($approvalKind == "ELEC") {
        $txtApprovalKind = "전자결재";
    } else if($approvalKind == "EMAIL") {
        $txtApprovalKind = "E-MAIL";
    } else if($approvalKind == "WRIT") {
        $txtApprovalKind = "서면결재";
    }

    $sheet->setCellValue("A$rowIndex", $seq);
    $sheet->setCellValue("B$rowIndex", $row['doc_cd']);
    $sheet->setCellValue("C$rowIndex", $row['out_doc_cd']);
    $sheet->setCellValue("D$rowIndex", $row['sender']);
    $sheet->setCellValue("E$rowIndex", $row['title']);
    $sheet->setCellValue("F$rowIndex", $row['recipient']);
    $sheet->setCellValue("G$rowIndex", $row['reference']);
    $sheet->setCellValue("H$rowIndex", $docScope);
    $sheet->setCellValue("I$rowIndex", $row['receive_kind']);
    $sheet->setCellValue("J$rowIndex", $row['receive_date']);
    $sheet->setCellValue("K$rowIndex", $docGroup);
    $sheet->setCellValue("L$rowIndex", $row['user_name']);
    $sheet->setCellValue("M$rowIndex", $row['ebr_process']);
    $sheet->setCellValue("N$rowIndex", $txtApprovalKind);
    $sheet->setCellValue("O$rowIndex", $row['approval_basis']);
    $rowIndex++;
}

// 전체 테두리 적용
$lastRow = $rowIndex - 1;
$sheet->getStyle("A1:O{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// 세로 정렬
$sheet->getStyle("A1:O{$lastRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$sheet->getStyle("A2:O{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("D2:D$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // 발신명의
$sheet->getStyle("E2:E$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // 제목
$sheet->getStyle("O2:O$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // 결재근거

for ($i = 1; $i <= $lastRow; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

$spreadsheet->setActiveSheetIndex(0);

$title = "대외공문 리스트";

// Redirect output to a client’s web browser (Excel2007)
@header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//IE EDGE
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Edge') !== FALSE)) {
    $title = rawurlencode($title);
    @header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
    @header('Cache-Control: private, no-transform, no-store, must-revalidate');
    @header('Pragma: no-cache');
}
//IE
else if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE || strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) {
    $title = iconv("UTF-8","EUC-KR", $title);
    @header('Content-Disposition: attachment;filename=' . $title . '.xlsx');
    @header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    @header('Pragma: public'); // HTTP/1.0
}
else {
    @header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
    @header('Cache-Control: private, no-transform, no-store, must-revalidate');
    @header('Pragma: no-cache');
}
@header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
@header('Cache-Control: max-age=1');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
?>