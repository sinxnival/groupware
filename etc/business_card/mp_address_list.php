<?php 
use PhpOffice\PhpSpreadsheet\IOFactory;

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

//주소록 업로드
if ("UPLOAD_ADDR" == $mode) {
    require_once "../vendor/autoload.php";

    $fileName = $_FILES['fileExcel']['tmp_name'];
    $objReader = IOFactory::createReaderForFile($fileName);
    //읽기 전용으로 설정
    $objReader->setReadDataOnly(true);
    //엑셀파일 읽기
    $objExcel = $objReader->load($fileName);
    //첫번째 시트 선택
    $objExcel->setActiveSheetIndex(0);
    $objWorksheet = $objExcel->getActiveSheet();
    $rowIterator = $objWorksheet->getRowIterator();

    foreach($rowIterator as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
    }

    $maxRow = $objWorksheet->getHighestRow();

    $rbl_private = $_POST["rbl_private"];
    $publicScopeUpload =  $_POST["publicScopeUpload"];
    
    for ($rowCnt = 2; $rowCnt <= $maxRow; $rowCnt++) {
        //선택한 그룹구분의 그룹목록
        $params = array();
        $SQL  = "[dbo].[PMP_AddrList_Group_S] ";
        //@nGrpId			AS INT
        $params[] = $grpId;
        //,@nCOId			AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //,@nUserId		AS INT
        $params[] = $user->uno;
        //,@sAddrGrpTp	AS NCHAR(3) --그룹구분
        $params[] = $rbl_private; //선택한 그룹구분
        //,@sYn			AS NCHAR(3) --구분자
        $params[] = "I";
        //,@sLang			AS NVARCHAR(10) = 'KR'
        $params[] = "KR";
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);

        while($userDB->next_record()) {
            $row = $userDB->Record;
    
            $addrGrpList[] = array(
                //addr_grp_id
                "addrGrpId" => $row["addr_grp_id"],
                //addr_grp_nm
                "addrGrpNm" => $row["addr_grp_nm"]
            );
        }
        $addrGrpNm = $objWorksheet->getCell('A' . $rowCnt)->getValue();

        $sameGrpCnt = 0;
        if($addrGrpList) {
            foreach($addrGrpList as $extAddrGrp) {
                if($extAddrGrp["addrGrpNm"] == $addrGrpNm) {
                    $addr_grp_id = $extAddrGrp["addrGrpId"];
                    $sameGrpCnt++;
                }
            }
        }

        if($sameGrpCnt == 0 && !($addrGrpNm == '') && (mb_strlen($addrGrpNm) <= 50) ) {
            //엑셀의 그룹명이 존재하지 않을 경우, 그룹 등록 
            $params = array();
            $SQL  = "[dbo].[PMP_GROUP_POP_IU] ";
            //@nAddrGrpId		 AS INT 			 --그룹ID
            $params[] = 0;
            //,@nCoId			 AS INT				 --회사아이디
            $params[] = $_SESSION["user"]["company_id"];
            //,@sAddrGrpNm	 AS NVARCHAR(50)		 --그룹명
            $params[] = $addrGrpNm; //엑셀의 그룹명
            //,@sAddrGrpTp	 AS NVARCHAR(30)		 --그룹구분
            $params[] = $rbl_private; //선택한 그룹구분
            //,@sAddrGrpDesc	 AS NVARCHAR(100)	 --그룹설명
            $params[] = "";
            //,@nCreatedEditBy AS INT 			 --작성자/수정자
            $params[] = $user->uno;
            //,@sYN			 AS NCHAR(1)			 --인서트/업데이트 구분
            $params[] = "I";
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            $userDB->query($SQL, $params);
            $userDB->next_record();
            $row = $userDB->Record;
            $addr_grp_id = $row["return_value"];
        }

        $addrNm = $objWorksheet->getCell('B' . $rowCnt)->getValue();
        $addrDuty = $objWorksheet->getCell('C' . $rowCnt)->getValue();
        $addrComp = $objWorksheet->getCell('D' . $rowCnt)->getValue();
        $addrDept = $objWorksheet->getCell('E' . $rowCnt)->getValue();
        $addrHp = $objWorksheet->getCell('F' . $rowCnt)->getValue();
        $addrTelNo = $objWorksheet->getCell('G' . $rowCnt)->getValue();
        $addrFaxNo = $objWorksheet->getCell('H' . $rowCnt)->getValue();
        $addrMail = $objWorksheet->getCell('I' . $rowCnt)->getValue();
        $addrEtc1 = $objWorksheet->getCell('J' . $rowCnt)->getValue();
        $addrEtc2 = $objWorksheet->getCell('K' . $rowCnt)->getValue();
        $addrNote = $objWorksheet->getCell('L' . $rowCnt)->getValue();
        $addrZipCd = $objWorksheet->getCell('M' . $rowCnt)->getValue();
        $addrZipAddr = $objWorksheet->getCell('N' . $rowCnt)->getValue();
        $addrAddrDeail = $objWorksheet->getCell('O' . $rowCnt)->getValue();
    
        //maxlength 체크
        $maxLngCnt = 0;
        $errorLength = array(
            "addrGrpNm" => $addrGrpNm . "|50|그룹명",
            "addrNm" => $addrNm . "|100|이름",
            "addrComp" => $addrComp . "|100|회사명",
            "addrDept" => $addrDept . "|100|부서명",
            "addrDuty" => $addrDuty . "|100|직책(급)",
            "addrHp" => $addrHp . "|100|휴대전화",
            "addrTelNo" => $addrTelNo . "|50|회사번호",
            "addrFaxNo" => $addrTelNo . "|50|팩스번호",
            "addrMail" => $addrMail . "|100|메일주소",
            "addrZipCd" => $addrZipCd . "|50|우편번호",
            "addrZipAddr" => $addrZipAddr . "|255|우편주소",
            "addrAddrDeail" => $addrAddrDeail . "|255|상세주소",
            "addrEtc1" => $addrEtc1 . "|100|기타1",
            "addrEtc2" => $addrEtc2 . "|100|기타2",
            "addrNote" => $addrNote . "|500|비고"
        );

        $errorValue = array();

        foreach($errorLength as $value) {
            list($wrtVal, $maxLength, $errorName) = explode("|", $value);
            if(mb_strlen($wrtVal) > $maxLength) {
                $maxLngCnt++;
                $errorValue[] = $maxLength . "|" . $errorName;
            }
        }

        if($addrGrpNm && $maxLngCnt == 0) {
            //명함 등록
            $params = array();
            $SQL  = "[dbo].[PMP_AddrList_Pop_IU] ";
            //@nAddrId		AS INT					--주소록아이디
            $params[] = 0;
            //,@nCoId			AS INT					--회사아이디
            $params[] = $_SESSION["user"]["company_id"];
            //,@nAddrGrpId	AS INT					--기존그룹아이디
            $params[] = $addr_grp_id;
            //,@nAddrGrpNewId	AS INT					--변경될그룹아이디
            $params[] = $addr_grp_id;
            //,@AddrNm		AS NVARCHAR(100)		--사용자아이디
            $params[] = $addrNm; //엑셀의 이름
            //,@AddrComp		AS NVARCHAR(100)		--회사명
            $params[] = $addrComp; //엑셀의 회사
            //,@AddrDept		AS NVARCHAR(100)		--부서명
            $params[] = $addrDept; //엑셀의 부서
            //,@AddrHp		AS NVARCHAR(100)		--휴대전화
            $params[] = $addrHp; //엑셀의 휴대폰
            //,@AddrTelNo		AS NVARCHAR(50)			--회사전화
            $params[] = $addrTelNo; //엑셀의 근무처 전화
            //,@AddrFaxNo		AS NVARCHAR(50)			--팩스번호
            $params[] = $addrFaxNo; //엑셀의 근무지 팩스
            //,@AddrMail		AS NVARCHAR(100)		--메일주소
            $params[] = $addrMail; //엑셀의 전자 메일 주소
            //,@AddrEtc1		AS NVARCHAR(100)		--기타1
            $params[] = $addrEtc1; //엑셀의 기타1
            //,@AddrEtc2		AS NVARCHAR(100)		--기타2
            $params[] = $addrEtc2; //엑셀의 기타2
            //,@AddrNote		AS NVARCHAR(500)		--비고
            $params[] = $addrNote; //엑셀의 비고
            //,@AddrZipCD		AS NVARCHAR(10)			--우편번호
            $params[] = $addrZipCd; //엑셀의 우편번호
            //,@AddrZipAddr	AS NVARCHAR(255)		--주소
            $params[] = $addrZipAddr; //엑셀의 주소
            //,@AddrAddrDetail AS NVARCHAR(255)		--상세주소
            $params[] = $addrAddrDeail; //엑셀의 상세주소
            //,@nCreateEditBy AS INT					--등록/수정자
            $params[] = $user->uno;
            //,@sYn			AS NCHAR(1)				--인서트/업데이트 구분자
            $params[] = "I";
            //,@sDuty			AS VARCHAR(100)			--직급
            $params[] = $addrDuty; //직책(급)
            //,@sPhotoNM		AS NVARCHAR(255)
            $params[] = "";
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            $userDB->query($SQL, $params);
            $userDB->next_record();
            $row = $userDB->Record;
            $returnValue = $row["return_value"];
            if ($row["return_value"] > 0) {
                //공개범위 선택 시 등록
                if($publicScopeUpload == "auth" && $_POST["uploadPublicScopeIds"]) {
                    //공개범위
                    $publicScopeIds = $_POST["uploadPublicScopeIds"];

                    $publicScopeArray = array();
                    $publicScopeArray = explode('/', $publicScopeIds);

                    $publicScopeDept = array();
                    $publicScopeUser = array();
                    $publicScopeUserId = array();
                    $publicScopeCoId = array();
                    
                    foreach($publicScopeArray as $value) {
                        //부서ID : 'D'로 시작, 'D' 제외
                        if(substr($value,0,1) == "D") {
                            $publicScopeDept[] = substr($value,1);
                        }
                        //직원ID : 직원ID|회사ID 를 분리
                        else {
                            list($publicScopeUserId[], $publicScopeCoId[]) = explode('|', $value);
                        }
                    }

                    //부서나열
                    for($i = 0; $i < count($publicScopeDept); $i++) {
                        if ($i > 0) {
                            $publicScopeDeptList .= ", ";
                        }
                        $publicScopeDeptList .= $publicScopeDept[$i];
                    }

                    //유저나열
                    for($i = 0; $i < count($publicScopeUserId); $i++) {
                        if ($i > 0) {
                            $publicScopeUserIdList .= ", ";
                            $publicScopeCoIdList .= ", ";
                        }
                        $publicScopeUserIdList .= $publicScopeUserId[$i];
                        $publicScopeCoIdList .= $publicScopeCoId[$i];
                    }

                    //부서ID : 'D'로 시작, 'D' 제외
                    //직원ID : 직원ID|회사ID 를 분리
                    $params = array();
                    $SQL  = "[dbo].[PMP_ADDR_PUBLIC] ";
                    //@inCOID			AS INT
                    $params[] = $_SESSION["user"]["company_id"];
                    //, @inUserID			AS INT
                    $params[] = $user->uno;
                    //, @inAddrGrpID		AS INT
                    $params[] = $addr_grp_id; //선택한 그룹ID
                    //, @inAddrID			AS INT
                    $params[] = $returnValue; //선택한 명함ID
                    //, @ivAuth			AS NVARCHAR(30)
                    $params[] = "MPADDRPublic";
                    //, @ivCOIDs			AS NVARCHAR(4000)
                    $params[] = $publicScopeCoIdList; //','로 연결한 직원 회사ID
                    //, @ivUserIDs		AS NVARCHAR(4000)
                    $params[] = $publicScopeUserIdList; //','로 연결한 직원ID
                    //, @ivDeptIDs		AS NVARCHAR(4000)
                    $params[] = $publicScopeDeptList; //','로 연결한 부서ID
                    for($i = 0; $i < count($params); $i++) {
                        if ($i > 0) {
                            $SQL .= ", ";
                        }
                        $SQL .= "?";
                    }
                    $userDB->query($SQL, $params);
                    $proceed = true;
                }
            }
            //엑셀 입력 값이 잘못된 경우
            else {
                $proceed = false;
                if (-1 == $returnValue) {
                    //문자 자르기
                    if(mb_strlen($addrGrpNm) > 25) {
                        $addrGrpNm = substr($addrGrpNm, 0, 24) . "..."; 
                    }
                    if(mb_strlen($addrNm) > 25) {
                        $addrNm = substr($addrNm, 0, 24) . "..."; 
                    }
                    if(mb_strlen($addrComp) > 25) {
                        $addrComp = substr($addrComp, 0, 24) . "..."; 
                    }
                    if(mb_strlen($addrHp) > 25) {
                        $addrHp = substr($addrHp, 0, 24) . "..."; 
                    } 
                    if(mb_strlen($addrMail) > 25) {
                        $addrMail = substr($addrMail, 0, 24) . "..."; 
                    }
                    //[이름없음]이름,회사,휴대폰,전자 메일 주소
                    $returnValueList[] = array(
                        "reason" => "이름 없음",
                        "addrGrpNm" => $addrGrpNm,
                        "addrNm" => $addrNm,
                        "addrComp" => $addrComp,
                        "addrHp" => $addrHp,
                        "addrMail" => $addrMail
                    );
                }
                // else {
                //     //[중복되는 Email]이름,회사,휴대폰,전자 메일 주소
                //     $returnValueList[] = array(
                //         "reason" => "중복되는 Email",
                //         "addrGrpNm" => $addrGrpNm,
                //         "addrNm" => $addrNm,
                //         "addrComp" => $addrComp,
                //         "addrHp" => $addrHp,
                //         "addrMail" => $addrMail
                //     );
                // }
            }
        }
        //그룹명이 없을 경우
        else if (($addrGrpNm == '') && ($addrNm)){
            $proceed = false;
            //문자 자르기
            if(mb_strlen($addrGrpNm) > 25) {
                $addrGrpNm = substr($addrGrpNm, 0, 24) . "..."; 
            }
            if(mb_strlen($addrNm) > 25) {
                $addrNm = substr($addrNm, 0, 24) . "..."; 
            }
            if(mb_strlen($addrComp) > 25) {
                $addrComp = substr($addrComp, 0, 24) . "..."; 
            }
            if(mb_strlen($addrHp) > 25) {
                $addrHp = substr($addrHp, 0, 24) . "..."; 
            }
            if(mb_strlen($addrMail) > 25) {
                $addrMail = substr($addrMail, 0, 24) . "..."; 
            }
            $returnValueList[] = array(
                "reason" => "그룹명 없음",
                "addrGrpNm" => $addrGrpNm,
                "addrNm" => $addrNm,
                "addrComp" => $addrComp,
                "addrHp" => $addrHp,
                "addrMail" => $addrMail
            );
        }
        //maxlength를 만족하지 못한 경우
        else if ($maxLngCnt > 0) {
            $proceed = false;
            //문자 자르기
            if(mb_strlen($addrGrpNm) > 25) {
                $addrGrpNm = substr($addrGrpNm, 0, 24) . "..."; 
            }
            if(mb_strlen($addrNm) > 25) {
                $addrNm = substr($addrNm, 0, 24) . "..."; 
            }
            if(mb_strlen($addrComp) > 25) {
                $addrComp = substr($addrComp, 0, 24) . "..."; 
            }
            if(mb_strlen($addrHp) > 25) {
                $addrHp = substr($addrHp, 0, 24) . "..."; 
            }
            if(mb_strlen($addrMail) > 25) {
                $addrMail = substr($addrMail, 0, 24) . "..."; 
            }
            foreach($errorValue as $value) {
                list($maxLengthVal, $reason) = explode("|", $value);
                $returnValueList[] = array(
                    "reason" => $reason . "이 최대글자수(" . $maxLengthVal . ")를 초과함",
                    "addrGrpNm" => $addrGrpNm,
                    "addrNm" => $addrNm,
                    "addrComp" => $addrComp,
                    "addrHp" => $addrHp,
                    "addrMail" => $addrMail
                );
            }
        }

        if( !ini_get('safe_mode') ){ 
            set_time_limit(25); 
        } 
    }

    $msg = "저장 되었습니다.";

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg,
        "returnValueList" => $returnValueList
    );

    echo json_encode($result);
}
//명함복사
else if ("COPY_ADDR" == $mode) {

    $proceed_cnt = 0;
    $proceed = true;

    foreach($_POST["copyList"] as $value) {

        //명함 하나씩 복사
        //key : addr_id | addr_grp_id | addr_nm | addr_hp | addr_mail | addr_grp_tp
        list($addr_id, $addr_grp_id, $addr_nm, $addr_hp, $addr_mail, $addr_grp_tp) = explode('|', $value);
    
        $moveCopyGroupList = explode('|', $_POST["moveCopyGroupList"]);
    
        //중복체크 체크 시
        $dupliChk = '';
        if($_POST["chName"]) {
            // 이름 : AND addr_nm=' + addr_nm + '
            $dupliChk .= "AND addr_nm = '" . $addr_nm . "' ";
        }
        if ($_POST["chHp"]) {
            // 휴대전화 : AND addr_hp=' + addr_hp + '
            $dupliChk .= "AND addr_hp = '" . $addr_hp . "' ";
        }
        if ($_POST["chMail"]) {
            // 메일주소 : AND addr_mail=' + addr_mail + '
            $dupliChk .= "AND addr_mail = '" . $addr_mail . "'";
        }
    
        $params = array();
        $SQL  = "[dbo].[PMP_AddrMList_MoveCopy] ";
        //@nGrpId			AS INT
        $params[] = $grpId;
        //,@nCoId				AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //,@nUserId			AS INT
        $params[] = $user->uno;
        //,@nOAddrGrpId		AS INT
        $params[] = $addr_grp_id; //key에서 addr_grp_id
        //,@nNewAddrGrpId		AS INT
        $params[] = $moveCopyGroupList[0]; //선택된 이동할 그룹ID (그룹구분 제외)
        //,@nAddrId			AS INT
        $params[] = $addr_id; //key에서 addr_id
        //,@sYn				AS NVARCHAR(100)
        $params[] = $dupliChk; //중복체크 문자열
        //,@sCopyMoveYn		AS NCHAR(10)
        $params[] = "Copy";
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
            $proceed = false;
            $proceed_cnt++;
        }
    }

    if ($proceed_cnt == 0) {
        $msg = "복사 되었습니다.";
    } else {
        $proceed = false;
        $msg = "중복되는 명함은 제외되었습니다.";
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
//명함이동
else if ("MOVE_ADDR" == $mode) {

    $tp_cnt = 0;
    $proceed_cnt = 0;

    
    foreach($_POST["moveList"] as $value) {
        //명함 하나씩 이동
        //key : addr_id | addr_grp_id | addr_nm | addr_hp | addr_mail | addr_grp_tp
        list($addr_id, $addr_grp_id, $addr_nm, $addr_hp, $addr_mail, $addr_grp_tp) = explode('|', $value);
        
        $moveCopyGroupList = explode('|', $_POST["moveCopyGroupList"]);
        
        //중복체크 체크 시
        $dupliChk = '';
        if($_POST["chName"]) {
            // 이름 : AND addr_nm=' + addr_nm + '
            $dupliChk .= "AND addr_nm = '" . $addr_nm . "' ";
        }
        if ($_POST["chHp"]) {
            // 휴대전화 : AND addr_hp=' + addr_hp + '
            $dupliChk .= "AND addr_hp = '" . $addr_hp . "' ";
        }
        if ($_POST["chMail"]) {
            // 메일주소 : AND addr_mail=' + addr_mail + '
            $dupliChk .= "AND addr_mail = '" . $addr_mail . "'";
        }
        //공용주소록에서 개인주소록으로 이동 불가
        // addr_grp_tp 가 1이면 제외
        // 1인 명함의 addr_nm을 ',' 연결하여 "{add_nm}은 공용주소록에서 개인주소록으로 이동할 수 없으므로 제외되었습니다. 공용주소록에서 개인주소록으로의 이동은 복사를 사용해주세요."를 표시
        if($addr_grp_tp == 1 && $moveCopyGroupList[1] == 0) {
            $proceed = false;
            $proceed_cnt++;

            // if($tp_cnt > 0) {
            //     $msg .= ", ";
            // }
            // $msg .= $addr_nm;

            $tp_cnt++;

        } else {
            $params = array();
            $SQL  = "[dbo].[PMP_AddrMList_MoveCopy] ";
            //@nGrpId			AS INT
            $params[] = $grpId;
            //,@nCoId				AS INT
            $params[] = $_SESSION["user"]["company_id"];
            //,@nUserId			AS INT
            $params[] = $user->uno;
            //,@nOAddrGrpId		AS INT
            $params[] = $addr_grp_id; //key에서 addr_grp_id
            //,@nNewAddrGrpId		AS INT
            $params[] = $moveCopyGroupList[0]; //선택된 이동할 그룹ID (그룹구분 제외)
            //,@nAddrId			AS INT
            $params[] = $addr_id; //key에서 addr_id
            //,@sYn				AS NVARCHAR(100)
            $params[] = $dupliChk; //중복체크 문자열
            //,@sCopyMoveYn		AS NCHAR(10)
            $params[] = "Move";
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
                $proceed = false;
                $proceed_cnt++;
                $duplicated = true;
            }
        }
    }

    if($duplicated == true) {
        $msg = "중복되는 명함은 제외되었습니다.";
    } else if ($proceed_cnt > 0) {
        $msg = "공용주소록에서 개인주소록으로 이동은 불가능합니다. 복사 명령을 이용하세요.";
    } else {
        $proceed = true;
        $msg = "이동되었습니다.";
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
//명함이동/복사에서 그룹 선택 시
else if ("LIST_BY_GROUP" == $mode) {

    $showGroupList = $_POST["showGroupList"];

     $showGroup = explode('|', $showGroupList);
     $nGrpId = $showGroup[1];
     $nAddGrpId = $showGroup[0];

    $params = array();
    $SQL  = "[dbo].[PMP_AddrMList_S] ?, ?, ? ";
    //@nGrpId		 AS INT
    $params[] = $grpId;
    //,@nCoId		 AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //,@nAddGrpId	 AS INT
    $params[] = $nAddGrpId; //선택한 그룹ID
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $addr_nm = str_replace('<','&lt;', $row["addr_nm"]);
        $addr_nm = str_replace('>','&gt;', $addr_nm);

        $grpByList[] = array(
            //key : addr_id
            "key" => $row["addr_id"],
            //val : addr_nm
            "val" => $addr_nm
        );
    }

    $result = array(
        "grpByList" => $grpByList
    );

    echo json_encode($result);
}
//삭제
else if ("DEL" == $mode) {
    
    $delMode = $_POST["delMode"];
    if($delMode == "single") {
        $deleteAddrId = $_POST["deleteAddrId"];
    } else {
        $deleteAddrsId = $_POST["delCheck"];
        for($i = 0; $i < count($deleteAddrsId); $i++) {
            if ($i > 0) {
                $deleteAddrId .= ", ";
            }
            $deleteAddrId .= $deleteAddrsId[$i];
        }
    }

    $proceed = true;
    $params = array();
    $SQL  = "[dbo].[PMP_AddrList_D] ?, ?, ? ";
    //@sAddrId		AS NVARCHAR(4000) --주소록아이디
    $params[] = $deleteAddrId; //','로 연결한 명함ID
    //,@nCoId			AS INT			  --회사아이디
    $params[] = $_SESSION["user"]["company_id"];
    //,@nUserId		AS INT			  --삭제요청한 유저 아이디
    $params[] = $user->uno;
    $userDB->query($SQL, $params);
    $msg = "삭제 되었습니다.";

    $result = array(
        $proceed,
        $msg
    );

    echo json_encode($result);
}
//저장
else if ("SAVE" == $mode) {
    
    //사진
    $photoNm = $_POST["photoNm"];
    
    $proceed = true;
    $msg = "";
    $client = new SoapClient('http://file.hi-techeng.co.kr/transferweb/Service1.svc?singleWsdl');
    if (!empty($_FILES['filePhotoNm']['name'])) {
        $type = "EADPIC";
        $newFileName = "";
        $info = pathinfo($_FILES['filePhotoNm']['name']);
        $ext = "." . $info['extension'];
        $newFileName = getNewFileName($type, $info['filename'], $ext, $addrId);
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
    
    $dbMode = $_POST["dbMode"];

    if($dbMode == "I") {
        $addrId = 0;
        $grpId = $_POST["ddlGroup"];
    } else {
        $addrId = $_POST["modifyAddrId"];
        $grpId = $_POST["modifyGrpId"];
    }
    
    $ddlGroup = $_POST["ddlGroup"];
    $addrNm = strip_tags(trim($_POST["addrNm"]));
    $addrComp = $_POST["addrComp"];
    $addrDept = $_POST["addrDept"];
    $addrHp = $_POST["addrHp"];
    $addrTelNo = $_POST["addrTelNo"];
    $addrFaxNo = $_POST["addrFaxNo"];
    $addrMail = $_POST["addrMail"];
    $addrEtc1 = $_POST["addrEtc1"];
    $addrEtc2 = $_POST["addrEtc2"];
    $addrNote = $_POST["addrNote"];
    $addrZipCd = $_POST["addrZipCd"];
    $addrZipAddr = $_POST["addrZipAddr"];
    $addrAddrDeail = $_POST["addrAddrDeail"];
    $dbMode = $_POST["dbMode"];
    $addrDuty = $_POST["addrDuty"];

    if ($proceed) {
        $params = array();
        $SQL  = "[dbo].[PMP_AddrList_Pop_IU] ";
        //@nAddrId		AS INT					--주소록아이디
        $params[] = $addrId; //선택한 명함ID, 등록이면 0
        //,@nCoId			AS INT					--회사아이디
        $params[] = $_SESSION["user"]["company_id"];
        //,@nAddrGrpId	AS INT					--기존그룹아이디
        $params[] = $grpId; //기존 그룹ID, 등록이면 선택한 그룹ID
        //,@nAddrGrpNewId	AS INT					--변경될그룹아이디
        $params[] = $ddlGroup; //선택한 그룹ID
        //,@AddrNm		AS NVARCHAR(100)		--사용자아이디
        $params[] = $addrNm; //이름
        //,@AddrComp		AS NVARCHAR(100)		--회사명
        $params[] = $addrComp; //회사명
        //,@AddrDept		AS NVARCHAR(100)		--부서명
        $params[] = $addrDept; //부서명
        //,@AddrHp		AS NVARCHAR(100)		--휴대전화
        $params[] = $addrHp; //휴대전화
        //,@AddrTelNo		AS NVARCHAR(50)			--회사전화
        $params[] = $addrTelNo; //회사번호
        //,@AddrFaxNo		AS NVARCHAR(50)			--팩스번호
        $params[] = $addrFaxNo; //팩스번호
        //,@AddrMail		AS NVARCHAR(100)		--메일주소
        $params[] = $addrMail; //메일주소
        //,@AddrEtc1		AS NVARCHAR(100)		--기타1
        $params[] = $addrEtc1; //기타1
        //,@AddrEtc2		AS NVARCHAR(100)		--기타2
        $params[] = $addrEtc2; //기타2
        //,@AddrNote		AS NVARCHAR(500)		--비고
        $params[] = $addrNote; //비고
        //,@AddrZipCD		AS NVARCHAR(10)			--우편번호
        $params[] = $addrZipCd; //우편번호
        //,@AddrZipAddr	AS NVARCHAR(255)		--주소
        $params[] = $addrZipAddr; //주소
        //,@AddrAddrDetail AS NVARCHAR(255)		--상세주소
        $params[] = $addrAddrDeail; //상세주소
        //,@nCreateEditBy AS INT					--등록/수정자
        $params[] = $user->uno;
        //,@sYn			AS NCHAR(1)				--인서트/업데이트 구분자
        $params[] = $dbMode; //등록 : I, 수정 : U
        //,@sDuty			AS VARCHAR(100)			--직급
        $params[] = $addrDuty; //직책(급)
        //,@sPhotoNM		AS NVARCHAR(255)
        $params[] = $photoNm; //업로드한 파일 명
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
            $proceed = false;
        }
    }

    if ($proceed) {
        //공개범위
        $publicScopeIds = $_POST["publicScopeIds"];

        $publicScopeArray = array();
        $publicScopeArray = explode('/', $publicScopeIds);

        $publicScopeDept = array();
        $publicScopeUser = array();
        $publicScopeUserId = array();
        $publicScopeCoId = array();
        
        foreach($publicScopeArray as $value) {
            //부서ID : 'D'로 시작, 'D' 제외
            if(substr($value,0,1) == "D") {
                $publicScopeDept[] = substr($value,1);
            }
            //직원ID : 직원ID|회사ID 를 분리
            else {
                $publicScopeUser = explode('|', $value);
                $publicScopeUserId[] = $publicScopeUser[0];
                $publicScopeCoId[] = $publicScopeUser[1];
            }
        }

        //부서나열
        for($i = 0; $i < count($publicScopeDept); $i++) {
            if ($i > 0) {
                $publicScopeDeptList .= ", ";
            }
            $publicScopeDeptList .= $publicScopeDept[$i];
        }

        //유저나열
        for($i = 0; $i < count($publicScopeUserId); $i++) {
            if ($i > 0) {
                $publicScopeUserIdList .= ", ";
                $publicScopeCoIdList .= ", ";
            }
            $publicScopeUserIdList .= $publicScopeUserId[$i];
            $publicScopeCoIdList .= $publicScopeCoId[$i];
        }

        $params = array();
        $SQL  = "[dbo].[PMP_ADDR_PUBLIC] ";
        //@inCOID			AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //, @inUserID			AS INT
        $params[] = $user->uno;
        //, @inAddrGrpID		AS INT
        $params[] = $ddlGroup; //선택한 그룹ID
        //, @inAddrID			AS INT
        $params[] = $addrId; //선택한 명함ID
        //, @ivAuth			AS NVARCHAR(30)
        $params[] = "MPADDRPublic";
        //, @ivCOIDs			AS NVARCHAR(4000)
        $params[] = $publicScopeCoIdList; //','로 연결한 직원 회사ID
        //, @ivUserIDs		AS NVARCHAR(4000)
        $params[] = $publicScopeUserIdList; //','로 연결한 직원ID
        //, @ivDeptIDs		AS NVARCHAR(4000)
        $params[] = $publicScopeDeptList; //','로 연결한 부서ID
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
//상세
else if ("DETAIL" == $mode) {

    //명함관리 권한옵션 1 이면 등록자만 수정 가능

    $modifyAddrId = $_POST["modifyAddrId"];
    $modifyGrpId = $_POST["modifyGrpId"];
    
    $params = array();
    $SQL  = "[dbo].[PMP_AddrList_Pop_Detail_S] ?, ?, ? ";
    //@nAddrId		AS INT --주소록 아이디
    $params[] = $modifyAddrId; //선택한 명함ID
    //, @nAddGrprId		AS INT --그룹 아이디
    $params[] = $modifyGrpId; //선택한 그룹ID
    //, @nCoId			AS INT --회사 아이디
    $params[] = $_SESSION["user"]["company_id"];
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;

    //사진 파일명 photo_nm
    $imgPhoto = "";
    if (!empty($row["photo_nm"])) {
        $imgPhoto = "{$urlPathAbsolute}EADPIC/" . $row["photo_nm"];
    }
    
    $infoDetail = array(
        //그룹ID addr_grp_id
        "addrGrpId" => $row["addr_grp_id"],
        //이름 addr_nm
        "addrNm" => $row["addr_nm"],
        //회사명 addr_comp
        "addrComp" => $row["addr_comp"],
        //부서명 addr_dept
        "addrDept" => $row["addr_dept"],
        //휴대전화 addr_hp
        "addrHp" => $row["addr_hp"],
        //직책(급) addr_duty
        "addrDuty" => $row["addr_duty"],
        //휴대전화 addr_tel_no
        "addrTelNo" => $row["addr_tel_no"],
        //팩스번호 addr_fax_no
        "addrFaxNo" => $row["addr_fax_no"],
        //메일주소 addr_mail
        "addrMail" => $row["addr_mail"],
        //사진 photo_nm
        "photoNm" => $imgPhoto,
        //우편번호 addr_zip_cd
        "addrZipCd" => $row["addr_zip_cd"],
        //주소 addr_zip_addr
        "addrZipAddr" => $row["addr_zip_addr"],
        //상세주소 addr_addr_detail
        "addrAddrDeail" => $row["addr_addr_detail"],
        //기타1 addr_etc1
        "addrEtc1" => $row["addr_etc1"],
        //기타2 addr_etc2
        "addrEtc2" => $row["addr_etc2"], 
        //비고 addr_note
        "addrNote" => $row["addr_note"],
        //등록자 created_by
        "createdBy" => $row["created_by"]
    );


    //공개범위
    $params = array();
    $SQL  = "[dbo].[PMP_AddrList_Pop_Public_S] ?, ?, ? ";
    //@inAddrGrpID		AS INT
    $params[] = $modifyGrpId; //선택한 그룹ID
    //, @inAddrID			AS INT
    $params[] = $modifyAddrId; //선택한 명함ID
    //, @inCOID			AS INT
    $params[] = $_SESSION["user"]["company_id"];
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;
        //work_kind : U
        if($row["work_kind"] == "U") {
            // id : work_id + '|' + co_id
            $id[] = $row["work_id"] . "|" . $row["co_id"];
            // nm : work_nm
            $nm[] = $row["work_nm"];
        }
        //work_kind : U 외
        else {
            // id : 'D' + work_id
            $id[] = "D" .  $row["work_id"];
            // nm : work_nm
            $nm[] = $row["work_nm"];
        }
        
        $addrScope = array(
            "id" => $id,
            "nm" => $nm
        );
    }
    //id, nm은 '/'로 구분

    $result = array(
        "infoDetail" => $infoDetail,
        "addrScope" => $addrScope
    );

    echo json_encode($result);
}
//목록
else if ("LIST" == $mode) {
    
    $mpPageSize = 14;
    $rbl_private = $_POST["rbl_private"];
    $ddlGroupList = $_POST["ddlGroupList"];
    $ddlSearchKind = $_POST["ddlSearchKind"];
    $txtSearchValue = $_POST["txtSearchValue"];
    $pageNo = $_POST["pageNo"];

    //페이지 설정
    $params = array();
    $SQL = "[dbo].[PMP_AddrList_S_H_CNT] ";
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
    //,@nCurPage			AS INT
    $params[] = &$pageNo; //페이지 번호
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    $totalCntAttr = $row["total_cnt"];
    $pageList = getPageList($pageNo, $totalCntAttr, $mpPageSize);

    //명함 목록
    $SQL  = "[dbo].[PMP_AddrList_S] ";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $infoList[] = array(
            //명함ID addr_id
            "addrId" => $row["addr_id"],
            //그룹ID addr_grp_id
            "addrGrpId" => $row["addr_grp_id"],
            //그룹명 addr_grp_nm
            "addrGrpNm" => $row["addr_grp_nm"],
            //이름 addr_nm
            "addrNm" => $row["addr_nm"],
            //E-MAIL addr_mail
            "addrMail" => $row["addr_mail"],
            //직책(급) addr_duty
            "addrDuty" => $row["addr_duty"],
            //회사명 addr_comp
            "addrComp" => $row["addr_comp"],
            //부서명 addr_dept
            "addrDept" => $row["addr_dept"],
            //휴대전화 addr_hp
            "addrHp" => $row["addr_hp"],
            //회사번호 addr_tel_no
            "addrTelNo" => $row["addr_tel_no"],
            //주소 addr_zip
            "addrZip" => $row["addr_zip"]
        );
    }

    $result = array(
        "infoList" => $infoList,
        "pageNo" => $pageNo,
        "pageList" => $pageList
    );

    echo json_encode($result);
}
//그룹구분 별 그룹 목록
else if ("LIST_GROUP" == $mode) {

    $rbl_private = $_POST["rbl_private"];
    
    $params = array();
    $SQL  = "[dbo].[PMP_AddrList_Group_S] ";
    //@nGrpId			AS INT
    $params[] = $grpId;
    //,@nCOId			AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //,@nUserId		AS INT
    $params[] = $user->uno;
    //,@sAddrGrpTp	AS NCHAR(3) --그룹구분
    $params[] = $rbl_private; //선택한 그룹구분
    //,@sYn			AS NCHAR(3) --구분자
    $params[] = "";
    //,@sLang			AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $groupList[] = array(
            //key : addr_grp_id
            "key" => $row["addr_grp_id"],
            //val : addr_grp_nm
            "val" => $row["addr_grp_nm"]
        );
    }

    $result = array(
        "groupList" => $groupList
    );

    echo json_encode($result);
}
//초기 화면
else if ("INIT" == $mode) {

    //그룹관리구분
    $groupTypeList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "rbl_Address_private";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $groupTypeList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //명함관리 검색구분
    $searchKindList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "MPAddressList_SearchKind";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $searchKindList[] = array(
            "key" => $row["cd_val"],
            "val" => $row["cd_nm"]
        );
    }

    //명함관리 권한옵션
    $authAddress = "";
    $params = array();
    $SQL  = "[dbo].[PSM_OptionValue_S] ?, ?, ? ";
    //@nGrpID			INT
    $params[] = $grpId;
    //, @nOptionID		INT
    $params[] = 104;
    //, @nCoID		INT=0
    $params[] = $coId;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    if (!empty($row)) {
        $row = $userDB->Record;
        $authAddress = $row["option_value"];
    }

    $result = array(
        "groupTypeList" =>  $groupTypeList,
        "searchKindList" => $searchKindList,
        "authAddress" => $authAddress
    );

    echo json_encode($result);
}
//명함 이동/복사 그룹 리스트
else if("MOVE_COPY_GRP" == $mode) {

    //그룹 목록
    $groupList = array();
    $params = array();
    $SQL  = "[dbo].[PMP_AddrList_Group_S] ";
    //@nGrpId			AS INT
    $params[] = $grpId;
    //,@nCOId			AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //,@nUserId		AS INT
    $params[] = $user->uno;
    //,@sAddrGrpTp	AS NCHAR(3) --그룹구분
    $params[] = "0";
    //,@sYn			AS NCHAR(3) --구분자
    $params[] = "ALL";
    //,@sLang			AS NVARCHAR(10) = 'KR'
    $params[] = "KR";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $groupList[] = array(
            "key" => $row["addr_grp_id"],
            "val" => $row["addr_grp_nm"]
        );
    }

    $result = array(
        "groupList" => $groupList
    );

    echo json_encode($result);
}

//중복되는 파일 이름 변경
function getNewFileName($type, $fileName, $ext, $addrId) {
    global $userDB;

    //지원하지 않는 특수문자 제거
    $fileName = iconv("UTF-8", "EUC-KR//TRANSLIT", $fileName);
    $fileName = iconv("EUC-KR", "UTF-8", $fileName);

    $fileList = array();
    $params = array();
    $SQL  = "SELECT photo_nm AS exist_file_name ";
    $SQL .= "FROM TMPG_ADDR ";
    $SQL .= "WHERE LOWER(photo_nm) LIKE ? ";
    if (!empty($addrId)) {
        $SQL .= " AND addr_id <> ? ";
    }
    $params[] = strtolower($fileName) . "%" . $ext;
    if (!empty($addrId)) {
        $params[] = $addrId;
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
