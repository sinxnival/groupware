<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

require_once "../../lib/include.php";
require_once "../common/biz_ini.php";
require_once "../common/func.php";

require_once "../vendor/autoload.php";

$proceed = true;

// 저장경로
$fileUrl = 'uploadExcel/';

if(!isset($_FILES['isoExcel']) || !is_uploaded_file($_FILES['isoExcel']["tmp_name"])) {
    $errorMsg = "파일 업로드 에러가 발생했습니다."; // output error when above checks fail.
} else {
    $info = pathinfo($_FILES["isoExcel"]["name"]);
    $fileName = $info["filename"] . "." . $info["extension"];
    $saveName = getUniqueFileName($fileUrl, $fileName);

    $fullPath = $fileUrl . $saveName;
    
    $uploadFile = $_FILES['isoExcel']['tmp_name'];

    // 파일을 서버에 저장
    if (move_uploaded_file($uploadFile, $fullPath)) {
        $fno = $db->nextid("SEQ_ISO_FNO");
        $params = array();
        $SQL = "INSERT INTO ISO_DOC_UPLOAD(FNO, FILE_NAME, FILE_SAVE, FILE_PATH, FILE_SIZE)
                VALUES (:fno, :fileName, :fileSave, :filePath, :fileSize)";
        $params = array(
            ":fno" => $fno,
            ":fileName" => $fileName,
            ":fileSave" => $saveName,
            ":filePath" => __DIR__ . '\\' .$fullPath,
            ":fileSize" => $_FILES['isoExcel']["size"]
        );
        $db->query($SQL, $params);

        $objReader = IOFactory::createReaderForFile($fullPath);
        //읽기 전용으로 설정
        $objReader->setReadDataOnly(true);
        //엑셀파일 읽기
        $objExcel = $objReader->load($fullPath);
        $arraySheet = $objExcel->getSheetNames();

        try {
            $db->beginTransaction();

            $SQL = "DELETE FROM ISO_DOC_HASH";
            $db->query($SQL);
            
            foreach ($arraySheet as $index => $sheet) {
                $sheetNmArray = explode('_', $sheet);

                $canDownload = "N";
                if(strpos($sheetNmArray[0], '#') === 0) {
                    $canDownload = "Y";
                    $sheetNm = str_replace("#", "", $sheetNmArray[0]);
                    $sheetNm = str_replace(" ", "", $sheetNm);
                } else {
                    $sheetNm = $sheetNmArray[0];
                }

                $sno = $db->nextid("SEQ_ISO_SNO");
                // 시트추가
                $SQL = "INSERT INTO ISO_DOC_SHEET(SNO, FNO, SHEET_NM, CAN_DOWNLOAD)
                        VALUES (:sno, :fno, :sheetNm, :canDownload)";
                $params = array(
                    ":sno" => $sno,
                    ":fno" => $fno,
                    ":sheetNm" => $sheetNm,
                    ":canDownload" => $canDownload
                );
                $db->query($SQL, $params);

                // 시트선택
                $objExcel->setActiveSheetIndex($index);
                $objWorksheet = $objExcel->getActiveSheet();
                $rowIterator = $objWorksheet->getRowIterator();

                foreach($rowIterator as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                }

                $maxRow = $objWorksheet->getHighestRow();

                $maxNonEmptyRow = 0; // 비어있지 않은 최대 행 번호를 저장할 변수입니다.

                for ($row = 1; $row <= $maxRow; $row++) {
                    $isRowEmpty = true; // 행이 비어있는지 여부를 추적합니다.
                    
                    // A열부터 시트의 마지막 열까지 순회합니다.
                    foreach ($objWorksheet->getColumnIterator() as $column) {
                        $cell = $objWorksheet->getCell($column->getColumnIndex() . $row); // 각 셀의 값을 가져옵니다.
                        if (trim($cell->getValue()) !== null && trim($cell->getValue()) !== '') {
                            $isRowEmpty = false; // 셀이 비어있지 않으면 행이 비어있지 않다고 설정합니다.
                            break;
                        }
                    }
                    
                    // 행이 비어있지 않으면 최대 행 번호를 업데이트합니다.
                    if (!$isRowEmpty) {
                        $maxNonEmptyRow = $row;
                    }
                }

                $categoryNm = '';
                $categoryKind = '';
                for ($rowCnt = 5; $rowCnt <= $maxNonEmptyRow; $rowCnt++) {
                    $lno = $db->nextid("SEQ_ISO_LNO");

                    if(trim($objWorksheet->getCell('B' . $rowCnt)->getValue())) {
                        $categoryNm = $objWorksheet->getCell('B' . $rowCnt)->getValue();
                    }
                    if(trim($objWorksheet->getCell('C' . $rowCnt)->getValue())) {
                        $categoryKind = $objWorksheet->getCell('C' . $rowCnt)->getValue();
                    }
                    $chargeDept = $objWorksheet->getCell('D' . $rowCnt)->getValue();
                    $chargeStaff = $objWorksheet->getCell('E' . $rowCnt)->getValue();
                    $docCd = $objWorksheet->getCell('F' . $rowCnt)->getValue();
                    $revisionNo = $objWorksheet->getCell('G' . $rowCnt)->getValue();
                    $revisionDt = $objWorksheet->getCell('H' . $rowCnt)->getFormattedValue();
                    $docNm = $objWorksheet->getCell('I' . $rowCnt)->getValue();
                    $docDetail = $objWorksheet->getCell('J' . $rowCnt)->getValue();
                    $remark = $objWorksheet->getCell('L' . $rowCnt)->getValue();
                    $revisionDt = $objWorksheet->getCell('H' . $rowCnt)->getValue();
                    if(is_numeric($revisionDt)) {
                        $revisionDt = excelDateToPHPDate($revisionDt);
                    }
                    $ecmInfo = $objWorksheet->getCell('K' . $rowCnt)->getValue();
                    $propertyUrl = '';
                    $dirPath = '';
                    $fileOid = '';
                    $targetOid = '';
                    $version = '';
                    $modDate = '';
                    if($ecmInfo) {
                        // 속성 URL
                        if(preg_match('/속성\s?보기\s?:\s?(https:\/\/.+)/', $ecmInfo, $matches)) {
                            $propertyUrl = $matches[1];
                        }

                        // // 최신 URL
                        // if(preg_match('/최신\s?버전\s?-\s?열기\(Latest\)\s?:\s?(https:\/\/.+)/', $ecmInfo, $matches)) {
                        //     $latestUrl = $matches[1];
                        // } else {
                        //     $latestUrl = '';
                        // }

                        // ECM SHORT URL OID
                        if(preg_match('/최신\s?버전\s?-\s?열기\(Latest\)\s?:\s?https:\/\/.+?key=.{5}(.+)/', $ecmInfo, $matches)) {
                            $shortUrlOid = $matches[1];

                            $SQL = "SELECT S.FILEOID , F.TARGETOID, D.VERSIONCODE, D.LASTMODIFIEDAT
                                    FROM XSHORTURL S
                                    INNER JOIN XFILE F ON S.FILEOID = F.OID
                                    INNER JOIN XDOCUMENT D ON F.TARGETOID = D.OID
                                    WHERE S.OID = :oid";
                            $params = array(
                                ":oid" => $shortUrlOid
                            );
                            $ecmDB->query($SQL, $params);
                            $ecmDB->next_record();
                            $row = $ecmDB->Record;

                            $fileOid = $row["fileoid"];
                            $targetOid = $row["targetoid"];
                            $version = $row["versioncode"];
                            $modDate = $row["lastmodifiedat"];
                        }

                        // 파일 경로
                        if(preg_match('/경로\s?:\s?(.+)/', $ecmInfo, $matches)) {
                            $dirPath = $matches[1];
                            $dirPath = 'U:\\' . str_replace('>','\\', $dirPath);
                        }
                    }
                    
                    $allHashTag = $objWorksheet->getCell('M' . $rowCnt)->getValue();
                    $hashArray = explode("#", $allHashTag);
                    $hashArray = array_filter($hashArray, function($item) {
                        return !empty($item);
                    });

                    $SQL = "INSERT INTO ISO_DOC_LIST(LNO, FNO, SNO, CATEGORY_NM, CATEGORY_KIND, CHARGE_DEPT, CHARGE_STAFF, DOC_CD, DOC_NM, DOC_DETAIL, 
                                                    ECM_FILE_OID, ECM_PROPERTY_URL, ECM_DIR_PATH, ECM_FULL_TXT, ECM_DOC_OID, ECM_VERSION, ECM_MOD_DATE, REMARK, REVISION_NO, REVISION_DATE)
                            VALUES (:lno, :fno, :sno, :categoryNm, :categoryKind, :chargeDept, :chargeStaff, :docCd, :docNm, :docDetail, 
                                    :ecmFileOid, :ecmPropertyUrl, :ecmDirPath, :ecmFullTxt, :ecmDocOid, :ecmVersion, :ecmModDate, :remark, :revisionNo, TO_DATE(:revisionDate, 'YYYY-MM-DD'))";
                    $params = array(
                        ":lno" => $lno,
                        ":fno" => $fno,
                        ":sno" => $sno,
                        ":categoryNm" => $categoryNm,
                        ":categoryKind" => $categoryKind,
                        ":chargeDept" => $chargeDept,
                        ":chargeStaff" => $chargeStaff,
                        ":docCd" => $docCd,
                        ":docNm" => $docNm,
                        ":docDetail" => $docDetail,
                        ":ecmFileOid" => $fileOid,
                        ":ecmPropertyUrl" => $propertyUrl,
                        ":ecmDirPath" => $dirPath,
                        ":ecmFullTxt" => $ecmInfo,
                        ":ecmDocOid" => $targetOid,
                        ":ecmVersion" => $version,
                        ":ecmModDate" => $modDate,
                        ":remark" => $remark,
                        ":revisionNo" => $revisionNo,
                        ":revisionDate" => $revisionDt
                    );

                    $db->query($SQL, $params);

                    foreach($hashArray as $hash) {
                        $hashTag = trim($hash);

                        $hno = $db->nextid("SEQ_ISO_HNO");

                        $SQL = "INSERT INTO ISO_DOC_HASH (HNO, LNO, HASH_TXT)
                                VALUES (:hno, :lno, :hashTxt)";
                        $params = array(
                            ":hno" => $hno,
                            ":lno" => $lno,
                            ":hashTxt" => $hashTag
                        );

                        $db->query($SQL, $params);
                    }
                }
            }
        } catch (Exception $e) {
            $db->rollBack();
        } finally {
            $SQL = "DELETE FROM ISO_DOC_SHEET 
                    WHERE FNO != :fno";
            $params = array(
                ":fno" => $fno
            );
            $db->query($SQL, $params);

            $SQL = "UPDATE ISO_DOC_LIST
                    SET IS_USE = 'N'
                    WHERE FNO != :fno";
            $db->query($SQL, $params);

            $db->Commit();
            $db->endTransaction(); 
            
            $SQL = "UPDATE ISO_DOC_UPLOAD
                    SET IS_SUCCESS = 'Y'
                    WHERE FNO = :fno";
            $db->query($SQL, $params);

        }
    } else {
        $proceed = false;
        $errorMsg = "파일 업로드 에러가 발생했습니다.";
    }
}

$result = array(
    "proceed" => $proceed,
    "errorMsg" => $errorMsg
);

echo json_encode($result);

function excelDateToPHPDate($excelDate) {
    $unixTimestamp = ($excelDate - 25569) * 86400;
    return date('Y-m-d', $unixTimestamp);
}
?>
