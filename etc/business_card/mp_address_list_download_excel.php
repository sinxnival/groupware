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

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Add header data
$sheet = $spreadsheet->getActiveSheet();

//폰트사이즈
$spreadsheet->getDefaultStyle()->getFont()->setSize(10);
// 헤더 폰트 굵게
$spreadsheet->getActiveSheet()->getStyle('A1:O1')->getFont()->setBold(true);

//헤더
//그룹명	이름	직책(급)    회사	부서	휴대폰	근무처 전화     근무지 팩스     전자 메일 주소	기타1	기타2	비고	우편번호	주소	상세주소
$sheet->setCellValue('A1', "그룹명");
$sheet->setCellValue('B1', "이름");
$sheet->setCellValue('C1', "직책(급)");
$sheet->setCellValue('D1', "회사");
$sheet->setCellValue('E1', "부서");
$sheet->setCellValue('F1', "휴대폰");
$sheet->setCellValue('G1', "근무처 전화");
$sheet->setCellValue('H1', "근무지 팩스");
$sheet->setCellValue('I1', "전자 메일 주소");
$sheet->setCellValue('J1', "기타1");
$sheet->setCellValue('K1', "기타2");
$sheet->setCellValue('L1', "비고");
$sheet->setCellValue('M1', "우편번호");
$sheet->setCellValue('N1', "주소");
$sheet->setCellValue('O1', "상세주소");

// 헤더 배경색 지정
$sheet->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('DCDCDC');

$rbl_private = $_POST["rbl_private"];
$ddlGroupList = $_POST["ddlGroupList"];
$ddlSearchKind = $_POST["ddlSearchKind"];
$txtSearchValue = $_POST["txtSearchValue"];

$params = array();
$SQL  = "[dbo].[PMP_AddrList_S2] ";
//@nCoId				AS INT
$params[] = $_SESSION["user"]["company_id"];
//,@sAddrGrpTp			AS NVARCHAR(1)
$params[] = $rbl_private; //선택한 그룹구분
//,@nAddrGrpId		AS INT
$params[] = $ddlGroupList; //선택한 그룹ID
//,@sUserNm			AS NVARCHAR(4)
$params[] = "";
//,@nUserId			AS INT
$params[] = $user->uno;
//,@sKind				AS NVARCHAR(30)
$params[] = $ddlSearchKind; //검색구분
//,@sValue			AS NVARCHAR(50)
$params[] = $txtSearchValue; //검색어
for($i = 0; $i < count($params); $i++) {
    if ($i > 0) {
        $SQL .= ", ";
    }
    $SQL .= "?";
}
$userDB->query($SQL, $params);
$rowCnt = 2;
while($userDB->next_record()) {
    $row = $userDB->Record;

    //addr_grp_nm 그룹명
    $sheet->setCellValue('A'.$rowCnt, $row["addr_grp_nm"]);
    //addr_nm 이름
    $sheet->setCellValue('B'.$rowCnt, $row["addr_nm"]);
    //addr_duty 직책(급)
    $sheet->setCellValue('C'.$rowCnt, $row["addr_duty"]);
    //addr_comp 회사
    $sheet->setCellValue('D'.$rowCnt, $row["addr_comp"]);
    //addr_dept 부서
    $sheet->setCellValue('E'.$rowCnt, $row["addr_dept"]);
    //addr_hp 휴대폰
    $sheet->setCellValue('F'.$rowCnt, $row["addr_hp"]);
    //addr_tel_no 근무처 전화
    $sheet->setCellValue('G'.$rowCnt, $row["addr_tel_no"]);
    //addr_fax_no 근무지 팩스
    $sheet->setCellValue('H'.$rowCnt, $row["addr_fax_no"]);
    //addr_mail 전자 메일 주소
    $sheet->setCellValue('I'.$rowCnt, $row["addr_mail"]);
    //addr_etc1 기타1
    $sheet->setCellValue('J'.$rowCnt, $row["addr_etc1"]);
    //addr_etc2 기타2
    $sheet->setCellValue('K'.$rowCnt, $row["addr_etc2"]);
    //addr_note 비고
    $sheet->setCellValue('L'.$rowCnt, $row["addr_note"]);
    //addr_zip_cd 우편번호
    $sheet->setCellValue('M'.$rowCnt, $row["addr_zip_cd"]);
    //addr_zip_addr 주소
    $sheet->setCellValue('N'.$rowCnt, $row["addr_zip_addr"]);
    //addr_addr_detail 상세주소
    $sheet->setCellValue('O'.$rowCnt, $row["addr_addr_detail"]);

    $rowCnt++;
}

// 표 그리기
$rowCnt--;
$sheet->getStyle('A1:O'.$rowCnt)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// 헤더 칼럼 가운데 정렬
$sheet->getStyle('A1:O'.$rowCnt)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// 셀 높이
for($i = 1; $i <= $rowCnt; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

// 텍스트 맞춤
$sheet->getStyle('A1:O'.$rowCnt)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

//자동 줄바꿈
$sheet->getStyle('A1:O'.$rowCnt)->getAlignment()->setWrapText(true);

// 칼럼 사이즈 자동 조정
$sheet->getColumnDimension('A')->setWidth(12);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(15);
$sheet->getColumnDimension('I')->setWidth(22);
$sheet->getColumnDimension('J')->setWidth(15);
$sheet->getColumnDimension('K')->setWidth(15);
$sheet->getColumnDimension('L')->setWidth(15);
$sheet->getColumnDimension('M')->setWidth(9);
$sheet->getColumnDimension('N')->setWidth(40);
$sheet->getColumnDimension('O')->setWidth(40);

$today = new DateTime();
$title = "MAIL_ADDRESS(" . $today->format("Y-m-d") . ")";

// Rename worksheet
$sheet->setTitle($title);

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
