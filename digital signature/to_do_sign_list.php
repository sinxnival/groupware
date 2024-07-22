<?php 
require_once "../../lib/include.php";
require_once "../common/biz_ini.php";
error_reporting(E_ALL);
ini_set('display_errors', '1');

//세션 만료일 경우
if (!isset($_SESSION["user"]["uno"])) {
    echo json_encode(array("session_out" => true));
    //종료
    exit();
}

//작업모드
$mode = $_POST["mode"];

if($mode == "LIST") {
    $params = array();
    $exceptPledge = array();
    // 이미 서명한 파일 제외
    $SQL = "SELECT sno
            FROM [dbo].[sign_user_list]
            WHERE uno = ?";
    $params[] = $_SESSION["user"]["uno"];
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        array_push($exceptPledge, $row["sno"]);
    }

    if(count($exceptPledge) > 0) {
        $strExceptPledge = implode(",", $exceptPledge);
    }

    $params = array();
    $toDoSignList = array();
    $SQL = "SELECT sno, s_title, file_path, s_kind_cd
            FROM [dbo].[sign_list] 
            WHERE CONVERT(date, GETDATE()) BETWEEN from_date AND to_date ";
    if(count($exceptPledge) > 0) {
        $SQL .= "AND sno NOT IN(". $strExceptPledge .") ";
    }
    $SQL .= "ORDER BY to_date, sno DESC";

    $userDB->query($SQL);
    $cnt = $userDB->nf();
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $toDoSignList[] = array(
            "no" => $cnt,
            "sno" => $row["sno"],
            "sTitle" => $row["s_title"],
            "filePath" => $row["file_path"],
            "kindCd" => $row["s_kind_cd"]
        );

        $cnt--;
    }

    $result = array(
        "toDoSignList" => $toDoSignList,
        "uno" => $_SESSION["user"]["uno"]
    );

    echo json_encode($result);
}
// 사인하기
else if($mode == "SIGN") {
    // TCPDF 라이브러리를 불러옵니다.
    require_once('../TCPDF/tcpdf.php');
    $imageData = $_POST['imageDataURL'];
    $sno = $_POST["sno"];
    $decodedImageData = base64_decode(explode(',', $imageData)[1]);
    $uno = $_SESSION["user"]["uno"];
    $sTitle = $_POST["sTitle"];
    $kindCd = $_POST["kindCd"];
    //법정생일 8자리
    $lawBirthDt = $_POST["lawBirthDt"];

    if($lawBirthDt) {
        $lawBirthDt = str_replace('-','', $lawBirthDt);

        $params = array();
        $SQL = "UPDATE [dbo].[tcmg_user] 
                SET law_birth_dt = ?
                WHERE user_id = ?";
        $params[] = $lawBirthDt;
        $params[] = $_SESSION["user"]["uno"];

        if(!$userDB->query($SQL, $params)) {
            echo json_encode(false);
            exit;
        }
    }

    // 생년월일 여부 및 사인 여부 체크
    $params = array();
    $SQL = "SELECT law_birth_dt, sign_nm
            FROM [dbo].[tcmg_user]
            WHERE user_id = ?";
    $params[] = $_SESSION["user"]["uno"];
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;

    $birthdayDt = trim($row["law_birth_dt"]);

    if(!$birthdayDt) {
        echo json_encode(false);
        exit;
    }

    $birthdayDt = preg_replace("/(\d{4})(\d{2})(\d{2})/", "$1.$2.$3", $birthdayDt);
    $signNm = $row["sign_nm"];

    // 이미지를 파일로 저장
    $imageFileName = '../wo/image/tempSign' . $_SESSION["user"]["uno"] . '.jpg';
    file_put_contents($imageFileName, $decodedImageData);

    $members = $user->getUserMemberAll();
    $depts = $user->getUserDeptInfo();

    // PDF 객체를 생성합니다.
    $pdf = new TCPDF();

    $pdf->SetMargins(0, 0, 0, 0);

    // 자동 페이지 나눔 비활성화
    $pdf->SetAutoPageBreak(false);

    // 페이지를 추가합니다.
    $pdf->AddPage('A4', 'P');

    // 흰색 채우기 색상 설정
    $pdf->SetFillColor(255, 255, 255);

    // 페이지 전체를 흰색 사각형으로 덮기
    $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'F');

    // 이미지 파일 경로를 지정합니다.
    $backgroundImage = $_POST["filePath"]; // 기존 사진

    $pageWidth = $pdf->getPageWidth();
    $pageHeight = $pdf->getPageHeight();

    // 배경 이미지를 추가합니다.
    $pdf->Image($backgroundImage, 0, 0, $pageWidth, $pageHeight, '', '', '', true, 195, '', false, false, 0);
    
    // 날짜
    $todayYear = date("Y");
    $todayMonth = date("m");
    $todayDay = date("d");

    // 소속
    $dept_id = $_SESSION["user"]["team_id"];
    $deptNm = $depts[$dept_id]["display_name"];

    $targetImagePath = '';
    // 이미지
    if($signNm) {
        $signNm = rawurlencode($signNm);
        $overlayImage = 'https://gw.htenc.co.kr/Upload/1323/EMPSign/' . $signNm; // 위에 올릴 사진
        $targetImagePath = $overlayImage;
        $extension = strtolower(pathinfo($signNm, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = imagecreatefromjpeg($overlayImage);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($overlayImage);
                break;
            case 'gif':
                $sourceImage = imagecreatefromgif($overlayImage);
                break;
        }

        if($sourceImage) {
            // 이미지 크기를 가져옵니다.
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);

            $hasTransparency = false;
            // 이미지의 각 픽셀을 확인하여 알파 채널 값이 127 이상인지 확인합니다.
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $pixelColor = imagecolorsforindex($sourceImage, imagecolorat($sourceImage, $x, $y));
                    $alpha = $pixelColor['alpha'];

                    if ($alpha >= 127) {
                        $hasTransparency = true;
                        break 2;
                    }
                }
            }

            if(!$hasTransparency) {
                // 새로운 이미지를 생성하고 투명 배경을 설정합니다.
                $targetImage = imagecreatetruecolor($width, $height);
                $transparentColor = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
                imagefill($targetImage, 0, 0, $transparentColor);
                imagesavealpha($targetImage, true);
        
                // 흰색을 알파 채널로 변경합니다.
                $whiteColor = imagecolorallocate($sourceImage, 255, 255, 255);
                imagecolortransparent($sourceImage, $whiteColor);
        
                // 원본 이미지를 새로운 이미지에 복사합니다.
                imagecopy($targetImage, $sourceImage, 0, 0, 0, 0, $width, $height);
        
                // PNG 이미지로 저장합니다.
                $targetImagePath = '../wo/image/' . $signNm;
                imagepng($targetImage, $targetImagePath);
        
                imagedestroy($sourceImage);
                imagedestroy($targetImage);
            }
        } else {
            $targetImagePath = '../wo/image/tempSign' . $_SESSION["user"]["uno"] . '.jpg';
        }
    }

    if (!file_exists($targetImagePath)) {
        if(!$targetImagePath) {
            $targetImagePath = '../wo/image/tempSign' . $_SESSION["user"]["uno"] . '.jpg';
        }
    }

    $params = array();
    $coordinateList = array();
    $SQL = "SELECT s.sno, crd_kind, crd_font, crd_size, x_crd, y_crd
            FROM [dbo].[sign_coordinate] c
            INNER JOIN [dbo].[sign_list] s ON c.sno = s.sno
            WHERE s.s_kind_cd = ?";
    $params[] = $kindCd;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $coordinateList[] = array(
            "sno" => $row["sno"],
            "crdKind" => $row["crd_kind"],
            "crdFont" => $row["crd_font"],
            "crdSize" => $row["crd_size"],
            "xCrd" => $row["x_crd"],
            "yCrd" => $row["y_crd"]
        );
    }

    if($coordinateList) {
        foreach($coordinateList as $element) {
            $pdf->SetFont($element["crdFont"], '', $element["crdSize"]);
            if($element["crdKind"] == "year") {
                $pdf->Text($element["xCrd"], $element["yCrd"], $todayYear);
            } else if ($element["crdKind"] == "month") {
                $pdf->Text($element["xCrd"], $element["yCrd"], $todayMonth);
            } else if ($element["crdKind"] == "day") {
                $pdf->Text($element["xCrd"], $element["yCrd"], $todayDay);
            } else if($element["crdKind"] == "dept") {
                $pdf->Text($element["xCrd"], $element["yCrd"], $deptNm);
            } else if($element["crdKind"] == "name") {
                $pdf->Text($element["xCrd"], $element["yCrd"], $_SESSION["user"]["user_name"]);
            } else if($element["crdKind"] == "birthday") {
                $pdf->Text($element["xCrd"], $element["yCrd"], $birthdayDt);
            } else if($element["crdKind"] == "sign") {
                $plusX = 0;
                $plusY = 0;
                $xSize = '';
                $ySize = $element["crdSize"];
                if(isset($width) && isset($height)) {
                    if($width < $height) {
                        $ratio = $element["crdSize"] / $height;
                        $width = $width * $ratio;

                        $xSize = '';
                        $ySize = $element["crdSize"];
    
                        $plusX = ($width - $element["crdSize"]) / 2 + 1.2;
                        $plusY = 0;
                    } else {
                        $ratio = $element["crdSize"] / $width;
                        $height = $height * $ratio;

                        $xSize = $element["crdSize"] + 5.5;
                        $ySize = '';

                        $plusX = 4;
                        $plusY = ($height - $element["crdSize"]) / 2 + 1.2;
                    }
                }
                $pdf->Image($targetImagePath, $element["xCrd"] - $plusX, $element["yCrd"] - $plusY, $xSize, $ySize, '', '', '', true, 300, '', false, false, 0);
            }
        }
    }

    // // 글꼴 및 글자 크기 설정
    // $pdf->SetFont('malgungothic', '', 10);
    
    // // 날짜
    // $pdf->Text(84, 226.6, $todayYear);
    // $pdf->Text(98.4, 226.6, $todayMonth);
    // $pdf->Text(109, 226.6, $todayDay);
    
    // $pdf->Text(54, 238.6, $deptNm);

    // $pdf->Text(54, 244.9, $birthdayDt);

    // // 성명
    // $pdf->Text(54, 250.9, $_SESSION["user"]["user_name"]);
    
    // $pdf->Image($targetImagePath, 85.5, 248, 15, '', '', '', '', true, 300, '', false, false, 0);

    // PDF를 파일로 저장합니다.
    $directory = $_SERVER["DOCUMENT_ROOT"]. "gw/wo/userDoc/{$sno}";
    if(!is_dir($directory)) {
        // 디렉토리가 존재하지 않으면 생성
        mkdir($directory, 0755, true);
    }
	$today = new DateTime();
    $pdfTitle = str_replace(" ", "_", $sTitle) . "_" . $today->format("YmdHis") . "_" . $_SESSION["user"]["uno"] . ".pdf";
    $filename = $directory. '/'. $pdfTitle;
    $dbFilename = "/gw/wo/userDoc/{$sno}/" . $pdfTitle;
    $pdf->Output($filename, 'F');

    $SQL = "MERGE INTO sign_user_list AS s
            USING (SELECT 1 AS dual) AS b
            ON (s.uno = {$uno} and s.sno = {$sno})
            WHEN MATCHED THEN
            UPDATE SET s.sign_date = GETDATE(), s.sign_path = '{$dbFilename}'
            WHEN NOT MATCHED THEN
            INSERT(sno, uno, sign_date, sign_path) VALUES({$sno}, {$uno}, GETDATE(), '{$dbFilename}');";
    $userDB->query($SQL);
}
// 파일 경로 가져오기
else if($mode == "GET_PATH") {
    $sno = $_POST["sno"];
    $uno = $_POST["uno"];

    $SQL = "SELECT u.sign_path, s.dept_id
            FROM [dbo].[sign_user_list] u
            INNER JOIN [dbo].[sign_list] s ON u.sno = s.sno
            WHERE u.uno = ?
            AND u.sno = ?";
    $params[] = $uno;
    $params[] = $sno;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    
    $result = array(
        "filePath" => $row["sign_path"],
        "mngDeptId" => $row["dept_id"],
        "teamId" => $_SESSION["user"]["team_id"]
    );

    echo json_encode($result);
}
// 삭제하기
else if($mode == "DEL") {
    $sno = $_POST["sno"];
    $uno = $_POST["delUno"];

    $proceed = true;
    $params = array();
    $SQL = "DELETE FROM dbo.sign_user_list
            WHERE sno = ?
            AND uno = ?";
    $params[] = $sno;
    $params[] = $uno;
    if(!$userDB->query($SQL, $params)) {
        $proceed = false;
    }

    $result = array(
        "proceed" => $proceed
    );

    echo json_encode($result);
}
?>
