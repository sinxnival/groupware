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
$menuId = $_POST["menuId"];
$mpPageSize = 3;

if ("LIST" == $mode) {
    $searchKind = $_POST["ddlSearchKind"];
    $searchValue = $_POST["txtSearchValue"];
    $componentName = $_POST["componentName"];

    //문서관리 페이지 번호
    $pageNoDoc = $_POST["pageNoDoc"];
    //공지사항 페이지 번호
    $pageNoNotice = $_POST["pageNoNotice"];
    //게시함 페이지 번호
    $pageNoBoard = $_POST["pageNoBoard"];
    //전자결재 페이지 번호
    $pageNoAppDoc = $_POST["pageNoAppDoc"];
    
    //문서관리
    if ($componentName == "Doc") {

        $dataDoc = infoListDoc($pageNoDoc, $mpPageSize, $searchKind, $searchValue);

        $result = array(
            "infoList" => $dataDoc["infoListDoc"],
            "pageNo" => $dataDoc["pageNoDoc"],
            "pageList" => $dataDoc["pageListDoc"]
        );
    }

    //공지사항
    else if ($componentName == "Notice") {

        $dataNotice = infoListNotice($pageNoNotice, $mpPageSize, $searchKind, $searchValue);

        $result = array(
            "infoList" => $dataNotice["infoListNotice"],
            "pageNo" => $dataNotice["pageNoNotice"],
            "pageList" => $dataNotice["pageListNotice"]
        );
    }

    //게시함
    else if ($componentName == "Board") {

        $dataBoard = infoListBoard($pageNoBoard, $mpPageSize, $searchKind, $searchValue);

        $result = array(
            "infoList" => $dataBoard["infoListBoard"],
            "pageNo" => $dataBoard["pageNoBoard"],
            "pageList" => $dataBoard["pageListBoard"]
        );
    }
    
    //전자결재
    else if ($componentName == "AppDoc") {

        $dataAppDoc = infoListAppDoc($pageNoAppDoc, $mpPageSize, $searchKind, $searchValue);

        $result = array(
            // 문서관리
            "infoList" => $dataAppDoc["infoListAppDoc"],
            "pageNo" => $dataAppDoc["pageNoAppDoc"],
            "pageList" => $dataAppDoc["pageListAppDoc"]
        );
    } 
    
    // 전체목록
    else {

//         $dataDoc = infoListDoc($pageNoDoc, $mpPageSize, $searchKind, $searchValue);
        $dataNotice = infoListNotice($pageNoNotice, $mpPageSize, $searchKind, $searchValue);
        $dataBoard = infoListBoard($pageNoBoard, $mpPageSize, $searchKind, $searchValue);
        $dataAppDoc = infoListAppDoc($pageNoAppDoc, $mpPageSize, $searchKind, $searchValue);

        $result = array(
            // 문서관리
            "infoListDoc" => $dataDoc["infoListDoc"],
            "pageNoDoc" => $dataDoc["pageNoDoc"],
            "pageListDoc" => $dataDoc["pageListDoc"],
            // 공지사항
            "infoListNotice" => $dataNotice["infoListNotice"],
            "pageNoNotice" => $dataNotice["pageNoNotice"],
            "pageListNotice" => $dataNotice["pageListNotice"],
            // 게시함
            "infoListBoard" => $dataBoard["infoListBoard"],
            "pageNoBoard" => $dataBoard["pageNoBoard"],
            "pageListBoard" => $dataBoard["pageListBoard"],
            // 전자결재
            "infoListAppDoc" => $dataAppDoc["infoListAppDoc"],
            "pageNoAppDoc" => $dataAppDoc["pageNoAppDoc"],
            "pageListAppDoc" => $dataAppDoc["pageListAppDoc"]
        );
    }

    echo json_encode($result);

}
//초기 화면
else if ("INIT" == $mode) {

    $params = array();
    $SQL  = "[dbo].[PSM_EAuthCheck] ?, ?, ?, ? ";
    //@inGrpID		AS INT
    $params[] = $grpId;
    //, @inCOID	AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //, @inUserID	AS INT
    $params[] = $user->uno;
    //, @inMenuID	AS INT
    $params[] = $menuId;
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    if ($row['fn_inq_yn'] == 0) {
        //화면에 [권한이 없습니다.] 라고 표시
        $msg = "권한이 없습니다.";
    }
    
    //검색조건
    $params = array();
    $SQL  = "[dbo].[PSM_COMMON_CODE_ALL_SELECT] ? ";
    $params[] = "PortalSearchTotal";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $searchCondition[] = array(
            //option value : $row["cd_val"]
            "key" => $row["cd_val"],
            //option text : $row["cd_nm"]
            "val" => $row["cd_nm"]
        );
    }
    
    $result = array(
        "searchCondition" => $searchCondition,
        "msg" => $msg
    );
    
    //문서관리 조회 가능 여부
    //설정 확인 중
    //[dbo].[PCM_GRPMENUCHECK_S]
    
    echo json_encode($result);
}

// 문서관리 프로시저
function infoListDoc($pageNoDoc, $mpPageSize, $searchKind, $searchValue) {
    global $userDB;
    global $user;
    global $grpId;

    //문서관리 페이지 설정
    $params = array();
    $SQL  = "[dbo].[PKD_DOC_0100100_SListSearch_H_CNT] ";
    //@inGrpID			AS INT
    $params[] = $grpId;
    //, @inCOID		AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //, @inUserID		AS INT
    $params[] = $user->uno;
    //, @inMenuID		AS INT
    $params[] = 0;
    //, @inPageNo		AS INT
    $params[] = &$pageNoDoc;
    //, @inPageSize	AS INT
    $params[] = $mpPageSize;
    //, @ivKind		AS NVARCHAR(30)
    $params[] = $searchKind;
    //, @ivValue		AS NVARCHAR(50)
    $params[] = $searchValue;
    //, @inLangKind	AS NVARCHAR(10)
    $params[] = "KR";
    //, @sDocValue	AS NVARCHAR(50)
    $params[] = "";
    //, @sContents	AS NVARCHAR(50)
    $params[] = "";
    //, @sWriter		AS NVARCHAR(50)
    $params[] = "";
    //, @sSort	AS NVARCHAR(10) = '0'
    $params[] = "0";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    $totalCntDoc = $row["cnt"];
    $pageListDoc = getPageList($pageNoDoc, $totalCntDoc, $mpPageSize, "Doc");
    
    //문서관리
    $SQL  = "[dbo].[PKD_DOC_0100100_SListSearch_H] ";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $infoListDoc[] = array(
            //메뉴고유번호 : menu_id
            "menuId" => $row["menu_id"],
            //문서고유번호 : m_id
            "mId" => $row["m_id"],
            //버전 : cnt
            "cnt" => $row["cnt"],
            //문서함명 : menu_nm
            "menuNm" => $row["menu_nm"],
            //문서번호 : doc_cd
            "docCd" => $row["doc_cd"],
            //문서명 : subject
            "subject" => $row["subject"],
            //등록일자 : fr_dt10
            "frDt10" => $row["fr_dt10"],
            //등록자 : user_nm
            "userNm" => $row["user_nm"]
        );
    }

    $dataDoc = array(
        "infoListDoc" => $infoListDoc,
        "pageNoDoc" => $pageNoDoc,
        "pageListDoc" => $pageListDoc
    ); 

    return $dataDoc;
}

// 공지사항 프로시저
function infoListNotice($pageNoNotice, $mpPageSize, $searchKind, $searchValue) {
    global $userDB;
    global $user;
    global $grpId;

    //검색옵션이 결재번호일 경우
    if($searchKind == "doc_cd" && isset($searchValue)) {
        $totalCntNotice = 0;
    } else {
        $params = array();
        //공지사항 페이지 설정
        $SQL  = "[dbo].[PNB_NOTICE_0100100_SListSearch_H_CNT] ";
        //@inGrpID		AS INT
        $params[] = $grpId;
        //, @inCOID		AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //, @inUserID		AS INT
        $params[] = $user->uno;
        //, @inMenuID		AS INT
        $params[] = 0;
        //, @inPageNo		AS INT
        $params[] = &$pageNoNotice;
        //, @inPageSize	AS INT
        $params[] = $mpPageSize;
        //, @ivKind		AS NVARCHAR(30)
        $params[] = $searchKind;
        //, @ivValue		AS NVARCHAR(50)
        $params[] = $searchValue;
        //, @ivTopYn      AS NVARCHAR(1) = 'N' -- Y: 탑게시보여주기 N: 탑게시 안보여주기
        $params[] = "Y";
        //, @sLangKind	AS NVARCHAR(10) = 'KR'
        $params[] = "KR";
        //, @sChk_UserDiv	AS NVARCHAR(1) =''
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
        $totalCntNotice = $row["cnt"];
    }

    $pageListNotice = getPageList($pageNoNotice, $totalCntNotice, $mpPageSize, "Notice");

    
    //검색옵션이 결재번호일 경우
    if($searchKind == "doc_cd" && isset($searchValue)) {
        $infoListNotice = array();
    }
    else {
        //공지사항
        $SQL  = "[dbo].[PNB_NOTICE_0100100_SListSearch_H] ";
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
    
        $userDB->query($SQL, $params);
        while($userDB->next_record()) {
            $row = $userDB->Record;
    
            //등록자
            if($row["writer_show_type"] == 0) {
                $user_type = $row["user_nm"];
            } else if($row["writer_show_type"] == 1) {
                $user_type = '';
            } else if($row["writer_show_type"] == 2) {
                $user_type = $row["dept_nm"];
            } else {
                $user_type = $row["user_nm"];
            }
    
            $infoListNotice[] = array(
                //메뉴고유번호 : menu_id
                "menuId" => $row["menu_id"],
                //게시고유번호 : m_id
                "mId" => $row["m_id"],
                //폴더명 : menu_nm
                "menuNm" => $row["menu_nm"],
                //No. : notice_id
                "noticeId" => $row["notice_id"],
                //제목 : subject
                "subject" => $row["subject"],
                //등록일자 : reg_dt10
                "regDt10" => $row["reg_dt10"],
                //등록자
                // writer_show_type = 0 : user_nm
                // writer_show_type = 1 : ""
                // writer_show_type = 2 : dept_nm
                // 그 외 : user_nm
                "user_type" => $user_type
            );
        }
    }

    $dataNotice = array(
        "infoListNotice" => $infoListNotice,
        "pageNoNotice" => $pageNoNotice,
        "pageListNotice" => $pageListNotice
    ); 

    return $dataNotice;
}

// 게시함 프로시저
function infoListBoard($pageNoBoard, $mpPageSize, $searchKind, $searchValue) {
    global $userDB;
    global $user;
    global $grpId;

    //검색옵션이 결재번호일 경우
    if($searchKind == "doc_cd" && isset($searchValue)) {
        $totalCntBoard = 0;
    } else {
        //게시함 페이지 설정
        $params = array();
        $SQL  = "[dbo].[PNB_FREEBOARD_0200100_SListSearch_H_CNT] ";
        //@inGrpID		AS INT
        $params[] = $grpId;
        //, @inCOID		AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //, @inUserID		AS INT
        $params[] = $user->uno;
        //, @inMenuID		AS INT
        $params[] = 0;
        //, @inPageNo		AS INT
        $params[] = &$pageNoBoard;
        //, @inPageSize	AS INT
        $params[] = $mpPageSize;
        //, @ivKind		AS NVARCHAR(30)
        $params[] = $searchKind;
        //, @ivValue		AS NVARCHAR(50)
        $params[] = $searchValue;
        //, @ivTopYn      AS NVARCHAR(1) = 'N' -- Y: 탑게시보여주기 N: 탑게시 안보여주기
        $params[] = "Y";
        //, @sLangKind	AS NVARCHAR(10) = 'KR'
        $params[] = "KR";
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
        $userDB->next_record();
        $row = $userDB->Record;
        $totalCntBoard = $row["cnt"];
    }
    $pageListBoard = getPageList($pageNoBoard, $totalCntBoard, $mpPageSize, "Board");

    //검색옵션이 결재번호일 경우
    if($searchKind == "doc_cd" && isset($searchValue)) {
        $infoListBoard = array();
    } else {
        //게시함
        $SQL  = "[dbo].[PNB_FREEBOARD_0200100_SListSearch_H] ";
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
        while($userDB->next_record()) {
            $row = $userDB->Record;
    
            //등록자
            if($row["doc_mng_type"] == 1) {
                $user_type = '';
            } else {
                $user_type = $row["user_nm"];
            }
    
            $infoListBoard[] = array(
                //메뉴고유번호 : menu_id
                "menuId" => $row["menu_id"],
                //게시고유번호 : m_id
                "mId" => $row["m_id"],
                //문서타입 : doc_mng_type
                "docMngType" => $row["doc_mng_type"],
                //폴더명 : menu_nm
                "menuNm" => $row["menu_nm"],
                //No. : v_id
                "vId" => $row["v_id"],
                //제목 : subject
                "subject" => $row["subject"],
                //등록일자 : reg_dt10
                "regDt10" => $row["reg_dt10"],
                //등록자
                // doc_mng_type = 1 : 익명 
                // 그외 : user_nm
                "user_type" => $user_type
            );
        }
    }

    $dataBoard = array(
        "infoListBoard" => $infoListBoard,
        "pageNoBoard" => $pageNoBoard,
        "pageListBoard" => $pageListBoard
    ); 

    return $dataBoard;
}

// 전자결재 프로시저
function infoListAppDoc($pageNoAppDoc, $mpPageSize, $searchKind, $searchValue) {
    global $userDB;
    global $user;
    global $grpId;
    
    //전자결재 페이지 설정
    $params = array();
    $SQL  = "[dbo].[PEA2_APPDOC_Search_Total_S_H_CNT] ";
    //@nGrpID		AS INT
    $params[] = $grpId;
    //, @nCOID		AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //, @nUserID		AS INT
    $params[] = $user->uno;
    //, @nPageNo		AS INT
    $params[] = &$pageNoAppDoc;
    //, @nPageSize	AS INT
    $params[] = $mpPageSize;
    //, @sKind		AS NVARCHAR(20)
    $params[] = $searchKind;
    //, @sValue		AS NVARCHAR(50)
    $params[] = $searchValue;
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    $userDB->next_record();
    $row = $userDB->Record;
    $totalCntAppDoc = $row["cnt"];
    $pageListAppDoc = getPageList($pageNoAppDoc, $totalCntAppDoc, $mpPageSize, "AppDoc");

    //전자결재
    $SQL  = "[dbo].[PEA2_APPDOC_Search_Total_S_H] ";
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $infoListAppDoc[] = array(
            //결재고유번호 : doc_id
            "docId" => $row["doc_id"],
            //결재양식고유번호 : form_id
            "formId" => $row["form_id"],
            //결재함명 : sel_ot
            "selOt" => $row["sel_ot"],
            //품의번호 : doc_cd
            "docCd" => $row["doc_cd"],
            //제목 : subject
            "subject" => $row["subject"],
            //기안일자 : fr_dt10
            "frDt10" => $row["fr_dt10"],
            //기안자 : user_nm
            "userNm" => $row["user_nm"]
        );
    }

    $dataAppDoc = array(
        "infoListAppDoc" => $infoListAppDoc,
        "pageNoAppDoc" => $pageNoAppDoc,
        "pageListAppDoc" => $pageListAppDoc
    ); 

    return $dataAppDoc;
}
?>
