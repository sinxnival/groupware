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

//저장
if ("SAVE" == $mode) {
    
    //사진
    $photoNm = $_POST["photoNm"];
    //사인
    $signNm = $_POST["signNm"];

    $proceed = true;
    $msg = "";
    $client = new SoapClient('http://file.hi-techeng.co.kr/transferweb/Service1.svc?singleWsdl');
    if (!empty($_FILES['filePhotoNm']['name'])) {
        $type = "EMPPIC";
        $newFileName = "";
        $info = pathinfo($_FILES['filePhotoNm']['name']);
        $ext = "." . $info['extension'];
        $newFileName = getNewFileName($type, $info['filename'], $ext, $user->uno);

        $uploadFile = file_get_contents($_FILES['filePhotoNm']['tmp_name']);
        $parameter = array(
            'strFileBinary' => $uploadFile,
            'strSaveFileName' => $type . "/" . $newFileName
        );
        $resultUpload = $client->UploadFileWebGW($parameter);
        //파일 업로드 실패 시
        if ($resultUpload->UploadFileWebResult->ErrorMessage) {
            $proceed = false;
            $msg .= $resultUpload->UploadFileWebResult->ErrorMessage;
        }
        //파일 업로드 성공 시
        else {
            $photoNm = $newFileName;
        }
    }
    if ($proceed) {
        if (!empty($_FILES['fileSignNm']['name'])) {
            $type = "EMPSign";
            $newFileName = "";
            $info = pathinfo($_FILES['fileSignNm']['name']);
            $ext = "." . $info['extension'];
            $newFileName = getNewFileName($type, $info['filename'], $ext, $user->uno);

            $uploadFile = file_get_contents($_FILES['fileSignNm']['tmp_name']);
            $parameter = array(
                'strFileBinary' => $uploadFile,
                'strSaveFileName' => $type . "/" . $newFileName
            );
            $resultUpload = $client->UploadFileWebGW($parameter);
            //파일 업로드 실패 시
            if ($resultUpload->UploadFileWebResult->ErrorMessage) {
                $proceed = false;
                $msg .= $resultUpload->UploadFileWebResult->ErrorMessage;
            }
            //파일 업로드 성공 시
            else {
                $signNm = $newFileName;
            }
        }
    }

    if ($proceed) {

        //생년월일 8자리
        $birthDt = str_replace('-','',$_POST["birthDt"]);
        //법정생일 8자리
        $lawBirthDt = str_replace('-','',$_POST["lawBirthDt"]);
        //성별
        $sex = $_POST["sex"];
        //비상연락망
        $tel1 = $_POST["tel1"];
        $tel2 = $_POST["tel2"];
        $tel3 = $_POST["tel3"];
        //본인 핸드폰
        $mobile1 = $_POST["mobile1"];
        $mobile2 = $_POST["mobile2"];
        $mobile3 = $_POST["mobile3"];
        //주민등록상 주소
        $zipCd = $_POST["zipCd"];
        $zipAddr = $_POST["zipAddr"];
        $detailAddr = $_POST["detailAddr"];
        //회사 전화
        $coTel1 = $_POST["coTel1"];
        $coTel2 = $_POST["coTel2"];
        $coTel3 = $_POST["coTel3"];
        $coTel4 = $_POST["coTel4"];
        //팩스
        $fax1 = $_POST["fax1"];
        $fax2 = $_POST["fax2"];
        $fax3 = $_POST["fax3"];
        //실거주지
        $coZipCd = $_POST["coZipCd"];
        $coZipAddr = $_POST["coZipAddr"];
        $coDetailAddr = $_POST["coDetailAddr"];
        //음력여부
        $lunar = $_POST["lunar"];
        //담당 업무
        $charBiz = $_POST["charBiz"];
        //기본 로그인 회사
        $ddlMainLoginCompany = $_POST["ddlMainLoginCompany"];
        //결혼 유뮤, 결혼 기념일
        $marryYn = $_POST["marryYn"];
        if($_POST["marryDt"] == null) {
            $marryDt = "";
        }else {
            $marryDt = str_replace('-', '', $_POST["marryDt"]);
        }
        //차량 정보
        $vehicleNum1 = $_POST["vehicleNum1"];
        $vehicleNum2 = $_POST["vehicleNum2"];
        $insuranceNm = $_POST["insuranceNm"];

        $params = array();
        $SQL  = "[dbo].[PMP_USER_0100400_U] ";

        //@nGrpID			AS INT
        $params[] = $grpId;
        //, @nCOID		AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //, @nUserID		AS INT
        $params[] = $user->uno;
        //, @sLogonPwd	AS NVARCHAR(100)
        $params[] = "";
        //, @sBirthDT		AS NCHAR(8)
        $params[] = $birthDt; //생년월일 8자리
        //, @sLawBirthDT		AS NCHAR(8)
        $params[] = $lawBirthDt; //법정생일 8자리
        //, @sSex			AS NCHAR(1)
        $params[] = $sex; //성별
        //, @sPhotoNM		AS NVARCHAR(255)
        $params[] = $photoNm; //사진 파일명
        //, @sSignNM		AS NVARCHAR(255)
        $params[] = $signNm; //사인 파일명
        //, @sTel1		AS NVARCHAR(30)
        $params[] = $tel1; //비상연락망
        //, @sTel2		AS NVARCHAR(6)
        $params[] = $tel2; //비상연락망
        //, @sTel3		AS NVARCHAR(6)
        $params[] = $tel3; //비상연락망
        //, @sMobile1		AS NVARCHAR(30)
        $params[] = $mobile1; //본인 핸드폰
        //, @sMobile2		AS NVARCHAR(6)
        $params[] = $mobile2; //본인 핸드폰
        //, @sMobile3		AS NVARCHAR(6)
        $params[] = $mobile3; //본인 핸드폰
        //, @sZipCD		AS NVARCHAR(10)
        $params[] = $zipCd; //주민등록상 주소 우편번호
        //, @sZipAddr		AS NVARCHAR(255)
        $params[] = $zipAddr; //주민등록상 주소 기본주소
        //, @sDetailAddr	AS NVARCHAR(255)
        $params[] = $detailAddr; //주민등록상 주소 상세주소
        //, @sCOTel1		AS NVARCHAR(30)
        $params[] = $coTel1; //회사전화
        //, @sCOTel2		AS NVARCHAR(6)
        $params[] = $coTel2; //회사전화
        //, @sCOTel3		AS NVARCHAR(6)
        $params[] = $coTel3; //회사전화
        //, @sCOTel4		AS NVARCHAR(6)
        $params[] = $coTel4; //회사전화
        //, @sCOFax1		AS NVARCHAR(30)
        $params[] = $fax1; //팩스
        //, @sCOFax2		AS NVARCHAR(6)
        $params[] = $fax2; //팩스
        //, @sCOFax3		AS NVARCHAR(6)
        $params[] = $fax3; //팩스
        //, @sCOZipCD		AS NVARCHAR(10)
        $params[] = $coZipCd; //실거주지 주소 우편번호
        //, @sCOZipAddr	AS NVARCHAR(255)
        $params[] = $coZipAddr; //실거주지 주소 기본주소
        //, @sCODetailAddr	AS NVARCHAR(255)
        $params[] = $coDetailAddr; //실거주지 주소 상세주소
        //, @sLunar		AS NCHAR(1)
        $params[] = $lunar; //음력여부
        //, @sCharBiz		AS NVARCHAR(100)
        $params[] = $charBiz; //담당업무
        //, @sWebMail		AS NVARCHAR(50)
        $params[] = "";
        //, @sLangKind	AS NVARCHAR(10) = 'N'
        $params[] = "KR";
        //, @nMainLoginCompany AS INT = 99999
        $params[] = $ddlMainLoginCompany; //기본로그인회사
        //, @sIdnNo	    AS NVARCHAR(255) = 'N'
        $params[] = "N";
        //, @sMarryYN		AS NCHAR(1) = 'N'
        $params[] = $marryYn; //결혼여부
        //, @sMarryDT		AS NVARCHAR(8) = 'N'
        $params[] = $marryDt; //결혼기념일
        //, @sIPPoneID	AS NVARCHAR(30) = 'N'
        $params[] = $vehicleNum1;
        //, @sIPPonePW	AS NVARCHAR(30) = 'N'
        $params[] = $insuranceNm;
        //, @sIPPoneCMPW	AS NVARCHAR(30) = 'N'
        $params[] = $vehicleNum2;
        //, @sOutMailID	AS NVARCHAR(50) = 'N'
        $params[] = "N";
        //, @sMotyAuthType AS NVARCHAR(10) = 'N'
        $params[] = "N";
        //, @sAuthMail	AS NVARCHAR(100) = 'N'
        $params[] = "N";
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
        $userDB->next_record();
        $row = $userDB->Record;
        if ($row["return_value"] > 0) {
            $msg = "저장되었습니다.";
        }
        else {
            $proceed = false;
            $msg = "저장시 에러 발생하였습니다. 다시 시도해 주십시오.";
        }
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
//비밀번호 변경
else if ("SAVE_PASSWORD" == $mode) {

    $pwdModifyMode = $_POST["pwdModifyMode"];
    $existPwd = $_POST["existPwd"];
    $newPwd = $_POST["newPwd"];
    $newPwdCheck = $_POST["newPwdCheck"];
    $eOption_CM_PW_Chk2 = $_POST["eOption_CM_PW_Chk2"];
    $eOption_CM_PW_Chk3 = $_POST["eOption_CM_PW_Chk3"];

    $params = array();
    $SQL  = "[dbo].[PMP_USER_PW_CHANGE] ";
    //@USER_ID	INT
    $params[] = $user->uno;
    //, @PW		NVARCHAR(100)
    $params[] = $existPwd; //기존 패스워드
    //, @NPW		NVARCHAR(100)
    $params[] = $newPwd; //새로운 패스워드
    //, @ENC_PW	NVARCHAR(100)
    $params[] = "";
    //, @NEW_PW	NVARCHAR(100)
    $params[] = $newPwd;
    //, @PW_DIV	NVARCHAR(1)
    $params[] = $pwdModifyMode; // 1: 로그인 2:결재 3:급여
    //, @PW_CHK2	NVARCHAR(1) = '0'
    $params[] = $eOption_CM_PW_Chk2; //eOption_CM_PW_Chk2
    //, @PW_CHK3	NVARCHAR(1) = '0'
    $params[] = $eOption_CM_PW_Chk3; //eOption_CM_PW_Chk3
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    if ($row["return_value"] < 0) {
        switch ($row["return_value"]) {
            case -9:
                $msg = "비밀번호에 주민등록번호가 포함되어 있습니다.";
                break;
            case -8:
                $msg = "비밀번호에 생년월일이 포함되어 있습니다.";
                break;
            case -7:
                $msg = "비밀번호에 ERP사번이 포함되어 있습니다.";
                break;
            case -6:
                $msg = "비밀번호에 회사전화번호가 포함되어 있습니다.";
                break;
            case -5:
                $msg = "비밀번호에 이메일아이디가 포함되어 있습니다.";
                break;
            case -4:
                $msg = "비밀번호에 아이디가 포함되어 있습니다.";
                break;
            case -3:
                $msg = "비밀번호에 휴대폰번호가 포함되어 있습니다.";
                break;
            case -2:
                $msg = "비밀번호에 전화번호가 포함되어 있습니다.";
                break;
            case -1:
                $msg = "기존 비밀번호가 일치하지 않습니다.";
                break;
        }
    }
    else {
        //비밀번호 realtime 동기화
        if($pwdModifyMode == 1) {
            $SQL = "UPDATE BIZ_USER_SET
                    SET USER_PWD = :newPwd
                    WHERE UNO = :UNO";
            $params = array(
                ":newPwd" => $newPwd,
                ":UNO" => $user->uno
            );
            $commonDB->query($SQL, $params);
        }
        $msg = "변경되었습니다.";
    }

    $result = array(
        "msg" => $msg,
        "return_value" => $row["return_value"]
    );

    echo json_encode($result);
}
//상세
else if ("DETAIL" == $mode) {
    $params = array();
    $SQL  = "[dbo].[PSM_USER_0100300_S] ?, ?, ?, ? ";
    //@inGrpID AS INT
    $params[] = $grpId;
    //, @inUserID		AS INT
    $params[] = $user->uno;
    //, @sLangKind	AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    //, @inCoID		AS INT = 0
    $params[] = 0;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;

    //사진 파일명 photo_nm
    $imgPhoto = "";
    if (!empty($row["photo_nm"])) {
        $imgPhoto = "{$urlPathAbsolute}EMPPIC/" . $row["photo_nm"];
    }

    //사인 파일명 sign_nm
    $imgSign = "";
    if (!empty($row["sign_nm"])) {
        $imgSign = "{$urlPathAbsolute}EMPSign/" . $row["sign_nm"];
    }

    $userInfoList = array(
        //아이디 logon_cd
        "logonCd" => $row["logon_cd"],
        //성명 user_nm
        "userNm" => $row["user_nm"],
        //사진 파일명 photo_nm
        "photoNm" => $imgPhoto,
        //성별 sex
        "sex" => $row["sex"],
        //본인 핸드폰 mobile1 mobile2 mobile3
        "mobile1" => $row["mobile1"],
        "mobile2" => $row["mobile2"],
        "mobile3" => $row["mobile3"],
        //비상연락망 tel1 tel2 tel3
        "tel1" => $row["tel1"],
        "tel2" => $row["tel2"],
        "tel3" => $row["tel3"],
        //주민등록상 주소 우편번호 zip_cd 기본주소 zip_addr 상세주소 detail_addr
        "zipCd" => $row["zip_cd"],
        "zipAddr" => $row["zip_addr"],
        "detailAddr" => $row["detail_addr"],
        //생년월일 birth_dt lunar
        "birthDt" => $row["birth_dt"],
        //법정생일
        "lawBirthDt" => $row["law_birth_dt"],
        "lunar" => $row["lunar"],
        //사인 파일명 sign_nm
        "signNm" => $imgSign,
        //결혼여부 marry_yn
        "marryYn" => $row["marry_yn"],
        //결혼기념일 marry_dt
        "marryDt" => $row["marry_dt"],
        //담당업무 char_biz
        "charBiz" => $row["char_biz"],
        //기본로그인회사 mainlogin_coid
        "mainloginCoid" => $row["mainlogin_coid"],
        //사용언어 lang_kind
        "langKind" => $row["lang_kind"],
        //차량번호
        "vehicleNum1" => $row["ippone_id"],
        "vehicleNum2" => $row["ippone_cm_pwd"],
        "insuranceNm" => $row["ippone_pw"]
    );

    $params = array();
    $SQL  = "[dbo].[PSM_USERDEPT_0100800_UserDept] ?, ?, ?, ? ";
    //@inGrpID AS INT
    $params[] = $grpId;
    //, @inUserID	AS INT
    $params[] = $user->uno;
    //, @inDeptID	AS INT
    $params[] = $_SESSION["user"]["team_id"];
    //, @sLang	AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;

    $userDeptList = array(
        //부서 dept_nm
        "deptNm" => $row["dept_nm"],
        //직급 grade_nm
        "gradeNm" => $row["grade_nm"],
        //메일주소 email_id + "@" + maindomain
        "emailId" => $row["email_id"] . "@" . $row["maindomain"],
        //입사일 enter_dt
        "enterDt" => $row["enter_dt"],
        //회사전화 cotel1 cotel2 cotel3 cotel4
        "cotel1" => $row["cotel1"],
        "cotel2" => $row["cotel2"],
        "cotel3" => $row["cotel3"],
        "cotel4" => $row["cotel4"],
        //팩스 fax1 fax2 fax3
        "fax1" => $row["fax1"],
        "fax2" => $row["fax2"],
        "fax3" => $row["fax3"],
        //실거주지 주소 우편번호 cozip_cd 기본주소 cozip_addr 상세주소 codetail_addr
        "coZipCd" => $row["cozip_cd"],
        "coZipAddr" => $row["cozip_addr"],
        "coDetailAddr" => $row["codetail_addr"] 
    );

    //사용자정보변경설정 - 수정불가항목
    $params = array();
    $SQL  = "SELECT DD.CD_VAL ";
    $SQL .= "FROM TCMM_CD MD ";
    $SQL .= " INNER JOIN TCMD_CD DD ON DD.CD_GRP_ID=MD.CD_GRP_ID ";
    $SQL .= "WHERE MD.CD_GRP = ? ";
    $SQL .= " AND DD.USE_YN = ? ";
    $SQL .= " AND DD.ETC4 = ? ";
    $params[] = "Change_Userinfo";
    $params[] = 1;
    $params[] = "N";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;
//         사진	01
//         사인	02
//         주민번호	03
//         성별	04
//         자택전화	05
//         개인주소	06
//         핸드폰	07
//         사용여부	08
//         팩스번호	09
//         담당업무^	10
//         사용언어	11
//         회사전화	12
//         회사주소	13
//         생년월일	14
//         결혼여부	15
//         결혼기념일	16
//         IP폰 아이디	17
//         IP폰 비밀번호	18
//         IP폰 패스워드	19
//         car	20
        $notModify = array(
            "cdVal" => $row["cd_val"]
        );
    }

    $result = array(
        "userInfoList" => $userInfoList,
        "userDeptList" => $userDeptList,
        "notModify" => $notModify
    );

    echo json_encode($result);
}
//패스워드 체크
else if ("CHECK_PASSWORD" == $mode) {

    $proceed = true;
    $pwdCheck = $_POST["pwdCheck"];

    $params = array();
    $SQL  = "[dbo].[PSM_USER_0100300_S] ?, ?, ?, ? ";
    //@inGrpID AS INT
    $params[] = $grpId;
    //, @inUserID		AS INT
    $params[] = $user->uno;
    //, @sLangKind	AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    //, @inCoID		AS INT = 0
    $params[] = 0;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;

    //logon_pwd 와 입력된 패스워드 비교
    // 일치 -> 개인정보수정 화면 표시
    // 불일치 -> "패스워드가 일치하지 않습니다." 메시지 표시
    if(!($pwdCheck == $row["logon_pwd"])) {
        $proceed = false;
        $msg = "패스워드가 일치하지 않습니다.";
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
else if ("INIT" == $mode) {
    //지역번호
    $areaCodeList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "ddd";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $areaCodeList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //핸드폰 앞자리
    $mobileList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "mobile";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $mobileList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //성별
    $genderList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "sex";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $genderList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //음력구분
    $lunarList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "lunar";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $lunarList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //회사
    $companyList = array();
    $params = array();
    $SQL  = "[dbo].[PMP_MainLoginCompanyLIst_S] ? ";
    $params[] = $user->uno;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $companyList[] = array(
            "key" => $row["co_id"],
            "val" => $row["co_nm"]
        );
    }

    //비밀번호 입력 규칙 
    $passwordOptionList = array();
    $params = array();
    $SQL  = "SELECT option_id, option_set_code, option_value ";
    $SQL .= "FROM FCMT_GetModuleOpton(?) ";
    $SQL .= "WHERE option_group = 'cm' ";
    $SQL .= " AND option_id IN (64, 65, 66, 67, 90) ";
    $params[] = $_SESSION["user"]["company_id"];
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $passwordOptionList[$row["option_set_code"]] = $row["option_value"];
    }
    //eOption_CM_PW_Chk1
    // 1 : 8 ~ 12자
    // 0 : 4 ~ 16자

    $result = array(
        "areaCodeList" => $areaCodeList,
        "mobileList" => $mobileList,
        "genderList" => $genderList,
        "lunarList" => $lunarList,
        "companyList" => $companyList,
        "passwordOptionList" => $passwordOptionList
    );

    echo json_encode($result);
}

//중복되는 파일 이름 변경
function getNewFileName($type, $fileName, $ext, $userId) {
    global $userDB;

    //지원하지 않는 특수문자 제거
    $fileName = iconv("UTF-8", "EUC-KR//TRANSLIT", $fileName);
    $fileName = iconv("EUC-KR", "UTF-8", $fileName);

    $fileList = array();
    $params = array();
    //사진
    if ("EMPPIC" == $type) {
        $SQL  = "SELECT photo_nm AS exist_file_name ";
        $SQL .= "FROM TCMG_USER ";
        $SQL .= "WHERE LOWER(photo_nm) LIKE ? ";
        if (!empty($userId)) {
            $SQL .= " AND user_id <> ? ";
        }
        $params[] = strtolower($fileName) . "%" . $ext;
        if (!empty($userId)) {
            $params[] = $userId;
        }
    }
    //사인
    else if("EMPSign" == $type) {
        $SQL  = "SELECT sign_nm AS exist_file_name ";
        $SQL .= "FROM TCMG_USER ";
        $SQL .= "WHERE LOWER(sign_nm) LIKE ? ";
        if (!empty($userId)) {
            $SQL .= " AND user_id <> ? ";
        }
        $params[] = strtolower($fileName) . "%" . $ext;
        if (!empty($userId)) {
            $params[] = $userId;
        }
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $fileList[] = $row["exist_file_name"];
    }

    $newFileName = "";
    if (count($fileList) > 0) {
        $tempFileName = $fileName . $ext;
        //파일 이름이 중복된다면 (n) 번을 붙여서 저장
        if (in_array(strtolower($tempFileName), $fileList)) {
            $i = 1;
            do {
                $tempFileName = $fileName . " (" . $i++ . ")" . $ext;
            } while(in_array(strtolower($tempFileName), $fileList));
        }
        $newFileName = $tempFileName;
    }
    else {
        $newFileName = $fileName . $ext;
    }

    return $newFileName;
}

?>
