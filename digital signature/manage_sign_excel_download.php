<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

require_once "../../lib/include.php";
require_once "../vendor/autoload.php";

//세션 만료일 경우
if (!isset($_SESSION["user"]["uno"])) {
    header("Location: ../logout.php");
    //종료
    exit();
}

$sno = $_GET["sno"];

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Add header data
$sheet = $spreadsheet->getActiveSheet();

//폰트사이즈
$spreadsheet->getDefaultStyle()->getFont()->setSize(10);

// 반복할 행
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);

// 헤더 틀 고정
$spreadsheet->getActiveSheet()->freezePane("A3");

// 헤더 폰트 굵게
$spreadsheet->getActiveSheet()->getStyle('A1:E2')->getFont()->setBold(true);
$sheet->getStyle("A1")->getFont()->setSize(12);
$sheet->getStyle("A1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->mergeCells("A1:E1");

$members = $user->getUserMemberAll();
$depts = $user->getUserDeptInfo();

// title
$params = array();
$SQL = "SELECT s_title 
        FROM [dbo].[sign_list]
        WHERE sno = ?";
$params[] = $sno;
$userDB->query($SQL, $params);
$userDB->next_record();
$row = $userDB->Record;
$sTitle = $row["s_title"];

$sheet->setCellValue('A1', $sTitle);

//헤더
$sheet->setCellValue('A2', "부서");
$sheet->setCellValue('B2', "이름");
$sheet->setCellValue('C2', "직급");
$sheet->setCellValue('D2', "서명여부");
$sheet->setCellValue('E2', "서명일자");

// 헤더 배경색 지정
$sheet->getStyle('A2:E2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('DCDCDC');

$signList = array();
$params = array();
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
        END AS dept_order, su.sign_date,
        CASE WHEN su.uno IS NULL THEN 'X' ELSE 'O' END AS is_sign
        FROM dbo.TCMG_USERDEPT UD
        INNER JOIN dbo.TCMG_USER U ON UD.user_id=U.user_id
        INNER JOIN dbo.TCMG_DEPT D ON UD.dept_id = D.dept_id
        LEFT OUTER JOIN dbo.FCMT_CD_LANG('grade', 'KR') gra on gra.cd_val = ud.grade AND ( gra.co_id = 0 OR gra.co_id = UD.co_id )
        LEFT OUTER JOIN dbo.FCMT_CD_LANG('duty', 'KR') du on du.cd_val = ud.duty AND ( du.co_id = 0 OR du.co_id = UD.co_id )
        LEFT OUTER JOIN dbo.sign_user_list SU on U.user_id = SU.uno AND SU.sno = ?
        WHERE u.fire_yn = 1
        AND u.GW_USER_USE_YN = 1 
        AND u.msg_yn = 1 ";
        // $SQL .= "AND u.mobilegw_use_yn = 1 ";
        $SQL .= "AND UD.hold_office = 1
        AND ud.co_id = 1 
        AND ud.div_attend = 1 
        AND u.user_id NOT IN(9414, 9716, 9946, 1)
        ORDER BY dept_order, D.view_order, gra.view_order, D.par_dept_id, u.user_nm";
$params[] = $sno;
$userDB->query($SQL, $params);

$rowCnt = 3;
while($userDB->next_record()) {
    $row = $userDB->Record;

    $dept_id = $row["dept_id"];
    $dept_nm2 = $depts[$dept_id]["display_name"];

    // 부서
    $sheet->setCellValue('A'.$rowCnt, $dept_nm2);
    // 이름
    $sheet->setCellValue('B'.$rowCnt, $row["user_nm"]);
    // 직급
    $sheet->setCellValue('C'.$rowCnt, $row["grade_nm"]);
    // 서명여부
    $sheet->setCellValue('D'.$rowCnt, $row["is_sign"]);
    // 서명일자
    $sheet->setCellValue('E'.$rowCnt, $row["sign_date"]);

    $rowCnt++;
}

// 자동 필터
$spreadsheet->getActiveSheet()->setAutoFilter("A2:E{$rowCnt}");

// 표 그리기
$rowCnt--;
$sheet->getStyle('A2:E'.$rowCnt)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// 헤더 칼럼 가운데 정렬
$sheet->getStyle('A2:E2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('B3:E'.$rowCnt)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// 들여쓰기
$sheet->getStyle('A3:A'.$rowCnt)->getAlignment()->setIndent(1);

// 셀 높이
for($i = 1; $i <= $rowCnt; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

// 텍스트 맞춤
$sheet->getStyle('A1:E'.$rowCnt)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

// 칼럼 사이즈 자동 조정
$sheet->getColumnDimension('A')->setWidth(34);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(25);

$sTitle = str_replace(" ", "_", $sTitle);
$title = $sTitle . "_서약_현황";

// Rename worksheet
$sheet->setTitle($sTitle);

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
