<style>
/* #divDF11 {
    all: revert;
} */
#divDF11 p {
    margin-top: 0;
    margin-bottom: 0rem;
}
#textareaContent {
    height: 40rem !important;
}
@media (max-width: 1578px) {
    .search-inline {
        position: relative !important;
        display:contents;
    }
    .search-inline #ddlDocStat, .search-inline #ddlRead, .search-inline #ddlReadKind, .search-inline #ddlDocType {
        display: inline-block;
        width: auto !important;
        vertical-align: middle;
        -ms-flex: 0 0 auto !important;
        flex: 1 1 auto!important;
    }
    .search-inline select, .search-inline input, .search-inline button, .search-inline label {
        margin-bottom: 0.5rem !important;
    }
    .search-inline input[type="date"] {
        flex: 1 1 auto!important;
    }
    #divSearch .row {
        margin: 0 !important;
    }
}
@media (max-width: 1400px) {
    .col-w-state {
        display: none !important;
    }
}
@media print {
    body.modal-open {
        visibility: hidden;
    }
    body.modal-open #modalDetailAppDoc {
        position: absolute;
        left: 0;
        top: 0;
        margin: 0;
        padding: 0;
        overflow: visible!important;
    }
    body.modal-open #modalDetailAppDoc .modal-dialog {
        width: 768px;
        /* transform-origin: 50% 0; */
        transform: scale(1.31, 1.32);
    }
    body.modal-open #modalDetailAppDoc .exVersion {
        transform-origin: 50% 0;
    }
    body.modal-open #modalDetailAppDoc .nowVersion {
        transform-origin: 0 0;
    }
    body.modal-open #divPrintReplyList:not(.notPrint) {
        visibility: visible!important;
        break-before: page;
    }
    body.modal-open #modalDetailAppDoc .modal-header {
        visibility: visible;
        padding-top: 1rem!important;
        padding-bottom: 1rem!important;
    }
    body.modal-open #modalDetailAppDoc #divBtnList {
        display: none;
    }
    body.modal-open #modalDetailAppDoc #divDetailAppDocTitle {
        display: flex!important;
        justify-content: center!important;
    }
    body.modal-open #modalDetailAppDoc .modal-body {
        visibility: visible;
    }
    body.modal-open #modalDetailAppDoc .includePrint {
        visibility: visible!important;
    }
    body.modal-open #modalDetailAppDoc .btn, 
    body.modal-open #modalDetailAppDoc .close {
        visibility: hidden;
    }
    body.modal-open #modalDetailAppDoc .notPrint {
        height: 0!important;
        visibility: hidden;
    }
    body.modal-open #modalDetailAppDoc a:link {
        text-decoration: none !important;
    }
    body.modal-open #modalDetailAppDoc .mainContents .row div[class*=col] {
        min-height: 2rem;
    }
    body.modal-open #modalDetailAppDoc #divDetailContent {
        /* display: block!important; */
        overflow-x: hidden!important;
        min-height: auto;
        margin-bottom: 0.5rem; 
        display: flex!important;
        justify-content: center!important;
    }
    body.modal-open #modalDetailAppDoc #divDetailContent .printSize{
        transform-origin: 100 0;
        transform: scale(0.67, 0.67);
    }
    .row-direction-reverse {
        flex-direction: row-reverse!important;
    }
    .col-md-3 {
        flex: 0 0 25% !important;
        max-width: 25% !important;
    }
    .col-md-5 {
        flex: 0 0 41.666667%;
        max-width: 41.666667%;
    }
    .col-md-7 {
        flex: 0 0 58.333333%;
        max-width: 58.333333%;
    }
    .col-md-9 {
        flex: 0 0 75% !important;
        max-width: 75% !important;
    }
    .recipientAll {
        display: block !important;
    }

    /* .page-number {
        justify-content: center;
        display:flex !important;
        position: fixed !important;
        bottom: 0 !important;
   }

    .page-number::after {
        content: "Page " counter(page);
    } */

    .cutPage {
        break-before: page !important;
    }

    .printFooter {
        display: block !important;
    }

    .printFont {
        font-size: smaller !important;
    }

    @page {
        size : auto;
        margin : 10mm;
    }
}
</style>
<!-- <script type="text/javascript" src="/gw/vendor/ckeditor/ckeditor/ckeditor.js"></script> -->
<script type="text/javascript" src="/js/ea.js?random=<?php echo uniqid(); ?>"></script>
<script>
var editor = new SynapEditor("textareaContent", synapEditorConfig);
$(document).ready(function() {
    $("#menuId").val('<?php echo $_POST["menuId"]; ?>');
    $("#appbox").val('<?php echo $_POST["appbox"]; ?>');
    
    onConditionChange();
    
    //작업모드
    // $("#mode").val("INIT");

    // $.ajax({ 
    //     type: "POST", 
    //     url: "/gw/ol/ol_eas_list.php",
    //     data: $("#mainForm").serialize(), 
    //     dataType: "json", 
    //     success: function(result) {
            
    //     },
    //     complete: function() {
            
    //     },
    //     error: function (request, status, error) {
    //         alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
    //     }
    // })

    // var $th = $('#tblEasList').find('thead th');
    // $('#tblEasList').closest("div.tableFixHead").on('scroll', function() {
    //     $th.css('transform', 'translateY('+ this.scrollTop +'px)');
    // });

    // 브라우저 버전에 따른 인쇄
    var userAgent = navigator.userAgent;
    
    var browserVersion = 118;
    if (userAgent.indexOf("Chrome") !== -1) {
        var browserVersion = parseInt(userAgent.split("Chrome/")[1]);
    } else if (userAgent.indexOf("Edge") !== -1) {
        browserVersion = parseInt(userAgent.split("Edge/")[1]);
    }

    if(browserVersion >= 119) {
        $("#modalDetailAppDoc .modal-dialog").addClass("nowVersion");
    } else {
        $("#modalDetailAppDoc .modal-dialog").addClass("exVersion");
    }

    $("#btnPush").on('click', function() {
        $(".row input").each(function() {
            var inputId = $(this).attr("id");

            editor.setText('.' + inputId, $(this).val());
        });

        if($('input[name="chkDocCd"]:checked').val() == "outDocCd") {
            editor.setText('.docCd', $("#outDocCd").val());
        }

        if($("#docType").val() == "S") {
            var checkedValues = [];

            $("input[name='chkSendKind[]']:checked").each(function () {
                checkedValues.push($(this).val());
            });

            $("#sendKind").val(checkedValues.join("|"));
        }
    });

    //결재함 이동 버튼
    $("#btnShowMoveTray").on("click", onBtnShowTrayClick);
    //이동 버튼 - 결재함 이동
    $("#btnMoveTray").on("click", onBtnMoveTrayClick);
    //일괄결재 버튼
    $("#btnBatchSign").on("click", onBtnBatchSignClick);
    //일괄열람 버튼
    $("#btnBatchRead").on("click", onBtnBatchReadClick);
    //검색조건 - 입력란
    $("#txtSearchValue").on("keyup", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키
        if (cd == 13) {
            onBtnSearchAppDocClick();
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    //검색 버튼
    $("#btnSearchAppDoc").on("click", onBtnSearchAppDocClick);
//     //검색조건 - 일자
//     $("#ddlSearchDate").on("change", onConditionChange);
    //검색조건 - 일자(시작)
    $("#searchFrom").on("blur", function(e) {
        onSearchDateChange('from');
    });
    $("#searchFrom").on("keyup", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키
        if (cd == 13) {
            if (onSearchDateChange('from')) {
                onConditionChange();
            }
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    //검색조건 - 일자(종료)
    $("#searchTo").on("blur", function(e) {
        onSearchDateChange('to');
    });
    $("#searchTo").on("keyup", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키
        if (cd == 13) {
            if (onSearchDateChange('to')) {
                onConditionChange();
            }
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    $("#btnSearchDate").on('click', onConditionChange);
    //수정 버튼
    $("#btnModifyAppDoc").on("click", onBtnModifyAppDocClick);
    //상신취소 버튼
    $("#btnCancelAppDoc").on("click", onBtnCancelAppDocClick);
    //결재이력 버튼
    $("#btnShowSignInfo").on("click", onBtnShowSignInfoClick);
    //보류 버튼
    $("#btnHoldAppDoc").on("click", onBtnHoldAppDocClick);
    //결재 취소 버튼
    $("#btnCancelAppAgr").on("click", onBtnCancelAppAgrClick);
    //재작성 버튼
    $("#btnResubmitAppDoc").on("click", onBtnResubmitAppDocClick);
    //저장 버튼
    $("#btnSaveAppDoc").on("click", onBtnSaveAppDocClick);
    $("#btnSaveTempAppDoc").on("click", onBtnSaveTempAppDocClick);
    //수정취소
    $("#btnCancelEditAppDoc").on("click", onBtnCancelEditAppDocClick);
    //닫기 버튼
    $("button[name='btnCloseEditAppDoc']").on("click", onBtnCloseEditAppDocClick);
    //결재특이사항 삭제 버튼
    $("#btnConfirmDelReply").on("click", onBtnDeleteReplyClick);

    //날짜 min, max값 넣기
    dateMinMaxAppend();
});

//검색 버튼 클릭
function onBtnSearchAppDocClick() {
    var elem = $("#txtSearchValue");
    elem.val(elem.val().trim());
    if (elem.data("oldVal") != elem.val()) {
        onConditionChange();
    }
}

//일자 변경
function onSearchDateChange(type) {
    var from = new Date($("#searchFrom").val());
    var to = new Date($("#searchTo").val());
    if (isNaN(from.getTime())) {
        $("#searchFrom").css("background-color", "pink");
        return false;
    }
    else {
        $("#searchFrom").removeAttr("style");
    }
    if (isNaN(to.getTime())) {
        $("#searchTo").css("background-color", "pink");
        return false;
    }
    else {
        $("#searchTo").removeAttr("style");
    }
    //시작일이 미래일 경우
    if (from > to) {
        if (type == "from") {
            //종료일을 시작일로 변경
            $("#searchTo").val($("#searchFrom").val());
        }
        else if (type == "to") {
            //시작일을 종료일로 변경
            $("#searchFrom").val($("#searchTo").val());
        }
    }
    return true;
}

//조건 변경 시 검색
function onConditionChange() {
    onPageNoClick(1, "", false);
    // if (onSearchDateChange("")) {
    //     var elem = $("#ddlSearchDate");
    //     elem.data('oldVal', elem.val());

    //     elem = $("#searchFrom");
    //     elem.data('oldVal', elem.val());

    //     elem = $("#searchTo");
    //     elem.data('oldVal', elem.val());

    //     elem = $("#txtSearchValue");
    //     elem.val(elem.val().trim());
    //     elem.data('oldVal', elem.val());

    //     onPageNoClick(1, "", false);
    // }
    //작업모드
    // $("#mode").val("LIST");
    // // $("#pageNo").val("1");
    // $.ajax({ 
    //     type: "POST", 
    //     url: "/gw/ol/ol_eas_list.php",
    //     data: $("#mainForm").serialize(),
    //     dataType: "json",  
    //     success: function(result) {
    //         //세션 만료일 경우
    //         if (result["session_out"]) {
    //             //로그인 화면으로 이동
    //             onLogoutClick();
    //         }

    //         showInfoList(result["easList"]);
    //         // //현재 페이지
    //         // $("#pageNo").val(result["pageNo"]);
    //         // //페이지 목록
    //         // $("#pageList").empty().append(result["pageList"]);
    //     },
    //     beforeSend:function(){
    //         // $("#divSearch").find("input").prop("readonly", true);
    //         // $("#divSearch option").not(":selected").prop("disabled", true)
    //         // $("#btnSearchAppDoc").find("span.spinner-border").show();
    //     },
    //     complete: function() {
    //         // $("#divSearch").find("input").prop("readonly", false);
    //         // $("#divSearch option").not(":selected").prop("disabled", false)
    //         // $("#btnSearchAppDoc").find("span.spinner-border").hide();
    //     },
    //     error: function (request, status, error) {
    //         alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
    //     }
    // });
}

function showInfoList(list) {
    $('#tblEasList').closest('div.tableFixHead').scrollTop(0);
    $("#tblEasList tbody").empty();
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr class="row">';
        // html += '<td class="col-md-1 col-1 col-w-chk">';
        // if ($.inArray($("#appbox").val(), ["mytray", "intray", "outtray", "proctray", "rcptray"]) > -1) {
        //     html += '<div class="h-100 d-flex align-items-center">';
        //     html += '<input type="checkbox" name="chkDocId[]" value="' + info["chkDocId"] + '" onclick="onChkDocIdClick()" />';
        //     html += '</div>';
        // }
        // html += '</td>';
        html += '<td class="col-md-3 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["docCd"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md d-none d-md-block notAlign text-ellipsis">';
        html += '<div class="h-100 d-flex align-items-center notAlign">';
        html += '<div class="ellipsisLongTxt">';
        //보관함
        // if ($("#appbox").val() == "temptray") {
        //     html += '<a href="javascript:void(0);" onclick="onBtnEditAppDocClick(\'' + info["docId"] + '\', \'' + info["formId"] + '\')">';
        // }
        // else {
        //     //열람권한 체크
        //     if (info["chkView"] == "Y") {
        //         html += '<a href="javascript:void(0);" onclick="onBtnDetailAppDocClick(\'' + info["docId"] + '\', \'' + info["formId"] + '\')">';
        //     }
        // }
        html += info["title"];
//         if (info["existAttachFile"]) {
// //             html += '<i class="fa-solid fa-paperclip"></i>';
//             html += '&nbsp;&nbsp;<i class="fa-solid fa-floppy-disk"></i>';
//         }
        html += '</div>';
        html += '</div>';
        html += '</td>';
//         html += '<td class="col-md-2 d-none d-md-block">';
//         html += '<div class="h-100 d-flex align-items-center">';
//         html += info["drafter"];
//         html += '</div>';
//         html += '</td>';
//         html += '<td class="col-md-2 d-none d-md-block">';
//         html += '<div class="h-100 d-flex align-items-center">';
//         html += info["reportDate"];
//         html += '</div>';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["userNm"] + '<br />' + info["issueDate"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 d-none d-md-block col-w-state">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["procStatus"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 col-2 col-w-btn">';
        //열람권한 체크
        // if (info["chkView"] == "Y") {
            html += '<div class="h-100 d-flex align-items-center">';
            if ($("#appbox").val() == "STORAGE") {
                html += '<button type="button" class="btn btn-primary" name="btnDetailAppDoc" onclick="onBtnEditObClick('+ info["ono"] +')">상세</button>';
            } else {
                html += '<button type="button" class="btn btn-primary" name="btnDetailAppDoc" onclick="showObDetail('+ info["ono"] +')">상세</button>';
            }
            html += '</div>';
        // }
        html += '</td>';
        html += '</tr>';
    });

    $("#tblEasList tbody").append(html);
    onAfterChkDocClick();
}

//결재함 이동 클릭
function onBtnShowTrayClick() {
    $("#ddlMoveTray").val("0");
    onDdlMoveTrayChange();

    $("#modalMoveTray").modal("show");
}

//결재함 이동에서 결재함 선택 시
function onDdlMoveTrayChange() {
    //결재함 미선택
    if ($("#ddlMoveTray").val() == "0") {
        $("#btnMoveTray").prop("disabled", true);
    }
    //결재함 선택
    else {
        $("#btnMoveTray").prop("disabled", false);
    }
}

//결재함 이동
function onBtnMoveTrayClick() {
    $("#mode").val("MOVE_TRAY");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            $("#modalMoveTray").modal("hide");

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(5000).fadeOut();
        },
        complete: function() {
            getSubMenuCnt();
            onConditionChange();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//체크박스 전체 선택/해제
function onChkAllDocClick(obj) {
    onChkAllClick(obj, "chkDocId");

    onAfterChkDocClick();
}

//문서 별 체크박스 선택 변경 시
function onChkDocIdClick() {
    whenChkClick_chkAll("chkDocId", "chkAll");

    onAfterChkDocClick();
}

function onAfterChkDocClick() {
    if ($("input[type='checkbox'][name='chkDocId[]']:checked").length > 0) {
        //일괄결재 버튼
        $("#btnBatchSign").prop("disabled", false);
        //일괄열람 버튼
        $("#btnBatchRead").prop("disabled", false);
        //결재함 이동 버튼
        $("#btnShowMoveTray").prop("disabled", false);
    }
    else {
        //일괄결재 버튼
        $("#btnBatchSign").prop("disabled", true);
        //일괄열람 버튼
        $("#btnBatchRead").prop("disabled", true);
        //결재함 이동 버튼
        $("#btnShowMoveTray").prop("disabled", true);
    }
}

//일괄결재 버튼 클릭
function onBtnBatchSignClick() {
    $("#txtSignKind").text("batch_sign");

    $("#modalConfirmSign .modal-title").text("일괄결재");
    $("#msgSign").text("일괄 결재 하시겠습니까?");
    $("#returnReason").closest("div").hide();

    $("#modalConfirmSign").modal("show");
}

//일괄열람 버튼 클릭
function onBtnBatchReadClick() {
    $("#mode").val("BATCH_READ");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(5000).fadeOut();
        },
        complete: function() {
            onPageNoClick($("#pageNo").val(), "", true);
            getSubMenuCnt();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//편집 버튼 클릭
function onBtnEditAppDocClick(docId, formId) {
    $("#docId").val(docId);
    $("#formId").val(formId);
    $("#actType").val("U");

    //보관함
    if ($("#appbox").val() == "temptray") {
        $("#btnCancelEditAppDoc").hide();
    }
    else {
        $("#btnCancelEditAppDoc").show();
    }

    editAppDoc();
}

function clearDetailAppDoc() {
    $("#divBtnList").find("button:gt(1)").hide();
    $("#btnEditAppLine").hide();
    $("#txtDF10").closest("div").find("button").remove();
    $("#modalDetailAppDoc .modal-footer").find("button[id^='btn']").not("button[id='btnShowSignInfo']").hide();
    //첨부파일 지우기
    $("#txtAttachedList").empty();
    //참조문서 지우기
    $("#txtRelatedDocList").empty();
}

//상세 버튼 클릭
function onBtnDetailAppDocClick(docId, formId) {
    if ($("#modalDetailAppDoc").data("processing")) {
        return false;
    }
    $("#modalDetailAppDoc").data('processing', true);
    $("input:button[name='btnDetailAppDoc']").prop("disabled", true);
    clearDetailAppDoc();
    $("#docId").val(docId);
    $("#formId").val(formId);

    $("#appDocTxtContents").empty();
    $.ajax({
        url: "/gw/ea2/form/form_" + $("#formId").val() + "_detail.php", 
        success: function(result) {
            $("#appDocTxtContents").append(result);
        },
        complete: function() {
            showDetailAppDoc();
        }
    })
//     $("#appDocTxtContents").load("/gw/ea2/form/form_" + $("#formId").val() + "_detail.php");
}

function showDetailAppDoc() {
    //작업모드
    $("#mode").val("DETAIL");
    $("#divDetailAppLine").empty();
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php", 
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            if (result["authDoc"] == 0) {
                $("#modalAlertMsg .modal-body").html("이 문서를 볼 권한이 없습니다.");
                $("#modalAlertMsg").modal("show");
                if ($('#modalDetailAppDoc').hasClass('show')) {
                    $("#modalDetailAppDoc").modal("hide");
                }
            }
            else {
                var docInfo = result["appDocInfo"];
                $("#kindImportant").html(docInfo["txtKindImportant"]);
                $("#modalDetailAppDoc .modal-title").text(docInfo["formNm"]);
                $("#txtDF01").text(docInfo["DF01"]);
                $("#txtDF02").text(docInfo["DF02"]);
                $("#txtDF03").text(docInfo["DF03"]);
                $("#txtDF04").text(docInfo["userNm"] + " " + docInfo["gradeNm"]);
                $("#txtDF09").text(docInfo["DF09"]);
                $("#txtDF10").text(docInfo["subject"]);
                $(".txtDF10").text(docInfo["subject"]);
                if($("#formId").val() == 10040 || $("#formId").val() == 2) {
                    $(".txtDF10").hide();
                } else {
                    $(".txtDF10").show();
                }
                // 사내공문 인쇄시 수신참조 전체리스트 출력
                var html = '';
                $(".recipientAll").empty();
                $(".txtDF20").empty();
                if($("#formId").val() == 10040) {
                    html += '수신참조 전체 리스트 : '+ result["recipientAllNms"]
                    $(".recipientAll").append(html);
                    $(".txtDF20").text(docInfo["DF20"]);
                }
                var contents = $("<div>" + docInfo["contents"] + "</div>");
                contents.find("title").remove();
                contents.find("meta").remove();
                contents.find("style").remove();
                contents.find("link").remove();
                $("#divDF11").empty().append(contents);
                $("#txtDFPJT").text(docInfo["DFPjtNm"]);
                $("#txtDF20").text(docInfo["DF20"]);
                $("#txtDF21").text(docInfo["DF21"]);
                $("#txtDF22").text(docInfo["DF22"]);
                $("#txtDF23").text(docInfo["DF23"]);
                $("#txtDF24").text(docInfo["DF24"]);
                $("#txtDF34").text(docInfo["DF34"]);

                $("#seq").val(docInfo["seq"]);
                $("#nowApp").val(docInfo["nowApp"]);
                $("#appDocUserId").val(docInfo["userId"]);
                $("#proKind").val(docInfo["proKind"]);
                $("#proId").val(docInfo["proId"]);
                $("#docCoId").val(docInfo["coId"]);
                $("ebMenuId").val(docInfo["menuId"]);

                $("#appAgrCnt").val(docInfo["appAgrCnt"]);

                $("#appKindDisplay").val(result["appKindDisplay"]);
                var html = drawAppLine(result["appLine"]);
                $("#divDetailAppLine").append(html);

                var canEditList = result["canEditList"];

                //수신참조
                $("#recipientIds").val(result["recipientIds"]);
                var nms = "";
                if (result["recipientIds"] != "") {
//                     html = '<button type="button" class="btn btn-success btn-sm ml-2" onclick="onBtnListRecipientClick()">수신참조리스트</button>';
//                     $("#txtRecipientNms").closest("div").append(html);
                    nms += '<a href="javascript:void(0);" onclick="onBtnListRecipientClick(' + $("#docId").val() + ')">';
                }
                nms += result["recipientNms"]
                if (result["recipientIds"] != "") {
                    nms += '</a>';
                }
                $("#txtRecipientNms").html(nms);
                // 수신부서
                $("#txtRecipientDeptNms").html(result["recipientDeptNms"]);
                var width = 0;
                if (canEditList["recipient"] == "Y") {
                    html = '<button type="button" class="btn btn-info btn-sm py-0 ml-2" style="float: left;" onclick="onBtnSelectAppLineClick(\'save\', \'recipient\')">수정</button>';
                    $("#txtRecipientNms").closest("div[class^='col']").append(html);
                    width += 3.5;
                }
                if (canEditList["showRecipientHis"] == "Y") {
                    html = '<button type="button" class="btn btn-info btn-sm py-0 ml-2" style="float: left;" onclick="onBtnListROHisClick(\'recipient\')">이력</button>';
                    $("#txtRecipientNms").closest("div[class^='col']").append(html);
                    width += 3.5;
                }
                if (width > 0) {
                    $("#txtRecipientNms").css({"float": "left", "max-width": "calc(100% - " + width + "rem)"});
                }
                else {
                    $("#txtRecipientNms").css({"max-width": "100%"});
                }
                $("#txtRecipientNms").addClass("ellipsisLongTxt");

                var essentialNmList = [];
                var directorNms = result["directorNms"];
                // 본부장 열람
                // $(result["essentialUserList"]["Receip"]).each(function(i, info) {
                //     essentialNmList.push(info["nm"]);
                // });
                // var essentialNmStr = essentialNmList.join(",");
                $("#txtDirectorNms").text(directorNms);

                //로그인 유저가 시행자인 경우
                if (result["operator"]) {
                    $("#btnOperateDoc").show();
                }
                else {
                    $("#btnOperateDoc").hide();
                }
                //시행자
                $("#operatorIds").val(result["operatorIds"]);
                nms = "";
                if (result["operatorIds"] != "") {
//                     html = '<button type="button" class="btn btn-success btn-sm ml-2" onclick="onBtnOperatorListClick()">시행자리스트</button>';
//                     $("#txtOperatorNms").closest("div").append(html);
                    nms += '<a href="javascript:void(0);" onclick="onBtnOperatorListClick(' + $("#docId").val() + ')">';
                }
                nms += result["operatorNms"]
                if (result["operatorIds"] != "") {
                    nms += '</a>';
                }
                $("#txtOperatorNms").html(nms);
                width = 0;
                if (canEditList["operator"] == "Y") {
                    html = '<button type="button" class="btn btn-info btn-sm py-0 ml-2" style="float: left;" onclick="onBtnSelectAppLineClick(\'save\', \'operator\')">수정</button>';
                    $("#txtOperatorNms").closest("div[class^='col']").append(html);
                    width += 3.5;
                }
                if (canEditList["showOperatorHis"] == "Y") {
                    html = '<button type="button" class="btn btn-info btn-sm py-0 ml-2" style="float: left;" onclick="onBtnListROHisClick(\'operator\')">이력</button>';
                    $("#txtOperatorNms").closest("div[class^='col']").append(html);
                    width += 3.5;
                }
                if (width > 0) {
                    $("#txtOperatorNms").css({"float": "left", "max-width": "calc(100% - " + width + "rem)"});
                }
                else {
                    $("#txtOperatorNms").css({"max-width": "100%"});
                }
                $("#txtOperatorNms").addClass("ellipsisLongTxt");

                //본문 수정
                if (canEditList["contents"] != "N") {
                    html = '<button type="button" class="btn btn-info btn-sm py-0 ml-2" onclick="onBtnEditContentsClick(\'' + result["canEditList"]["contents"] + '\')">수정</button>';
                    $("#txtDF10").append(html);
                }
                if (canEditList["showContentsHis"] == "Y") {
                    html = '<button type="button" class="btn btn-info btn-sm py-0 ml-2" onclick="onBtnListContentsHisClick(\'W\')">내역</button>';
                    $("#txtDF10").append(html);
                }

                //필수 결재라인
                $("input[type='hidden'][name^='essential'").remove();
                $("input[type='hidden'][name^='extDirector'").remove();
                $.each(result["essentialUserList"], function(key, list) {
                    var name = "essential" + key + "[]";
                    $(list).each(function(i, info) {
                        $("<input>").attr({
                            type: "hidden",
                            name: name,
                            value : info["id"]
                        }).appendTo($("#mainForm"));
                    });
                });

                if($("#formId").val() == 10040) {
                    var extDirectorList = result["extDirectorList"];
                    $.each(extDirectorList, function(key, userNm) {
                        var name = "extDirector[]";
                        $("<input>").attr({
                            type: "hidden",
                            name: name,
                            value : userNm
                        }).appendTo($("#mainForm"));
                    });
                }

                //반려 문서 목록
                html = "";
                $(result["appDocReturnList"]).each(function(i, info) {
                    html += '<div class="row mb-2">';
                    html += '<div class="col bg-warning" style="cursor: pointer;" onclick="onBtnDetailAppDocClick(\'' + info["docId"] + '\', \'' + info["formId"] + '\')">';
                    html += (i + 1) + ' ' + info["docNm"];
                    html += '</div>';
                    html += '</div>';
                });
                if (html != "") {
                    $("#divAppDocReturnList").empty().append(html);
                    $("#divAppDocReturnList").show();
                }
                else {
                    $("#divAppDocReturnList").empty();
                    $("#divAppDocReturnList").hide();
                }

                if (Object.keys(result["relatedDocList"]).length > 0) {
                    //참조문서
                    html = "";
                    $(result["relatedDocList"]).each(function(i, info) {
//                         html += '<tr class="row">';
//                         html += '<td class="col-md-5 col-5">';
                        //품의번호
                        html += '<div class="ellipsisLongTxt">';
                        html += '<a href="javascript:void(0);" onclick="showEaAppDocDetail(' + info["docId"] + ',' + info["formId"] + ')">';
                        html += '<i class="fa-solid fa-thumbtack"></i> ' + " [" + info["docSeqCd"] + "] " + info["subject"];
                        html += '</a>';
                        html += '</div>';
//                         html += '</td>';
//                         html += '<td class="col-md-2 col-3">';
//                         //문서분류
//                         html += info["formNm"];
//                         html += '</td>';
//                         html += '<td class="col-md-5 col-4 notAlign">';
//                         //제목
//                         html += info["subject"];
//                         html += '</td>';
//                         html += '</tr>';
                    });
//                 $("#txtRelatedDocList tbody").append(html);
                    $("#txtRelatedDocList").append(html);
                    $("#txtRelatedDocList").closest("div.row").show();
                }
                else {
                    $("#txtRelatedDocList").closest("div.row").hide();
                }

                if (Object.keys(result["attachFileList"]).length > 0) {
                    //첨부파일
                    html = "";
                    $(result["attachFileList"]).each(function(i, info) {
                        html += '<div class="ellipsisLongTxt">';
                        html += '<a href="/gw/cm/cm_file_download.php?mKind=EA&fid=' + info["attachId"] + '" target="_blank">';
//                         html += '<a href="' + info["fileDownloadLink"] + '" target="_blank">';
                        html += '<i class="fa-regular fa-file-lines"></i> ' + info["oriFileNm"];
                        html += '</a>'; 
                        html += '<span style="display: none;" class="txtAttachFileId">' + info["attachId"] + '</span>';
                        html += '<span style="display: none;" class="txtAttachFile">' + info["fileNm"] + '</span>';
                        html += '<span style="display: none;" class="txtAttachOriFileNm">' + info["oriFileNm"] + '</span>';
                        html += '</div>';
                    });
                    $("#txtAttachedList").append(html);
                    $("#txtAttachedList").closest("div.row").show();
                }
                else {
                    $("#txtAttachedList").closest("div.row").hide();
                }

                $("#divReplyNote_new").empty();
                showReplyList(result["replyList"]);

                if (result["inputDocReply"] == "Y") {
                    $("#btnAddReply").show();
                }
                else if (result["inputDocReply"] == "N") {
                    $("#btnAddReply").hide();
                }

                $.each(result["showBtnList"], function(i, val) {
                    $("#" + val).show();
                });

                //휴가신청서
                if ($("#formId").val() == "10012") {
                    //연차 휴가 현황
                    if (result["isTblAnnvacShow"] == "Y") {
                        var html = '';
                        $(result["annualVacationList"]).each(function(i, info) {
                            html += '<tr class="row">';
                            html += '<td class="col-3" style="text-align:center">';
                            html += info["userNm"];
                            html += '</td>';
                            html += '<td class="col-3" style="text-align: right;">';
                            html += info["baseCnt"];
                            html += '</td>';
                            html += '<td class="col-3" style="text-align: right;">';
                            html += info["remCnt"];
                            html += '</td>';
                            html += '<td class="col-3" style="text-align: right;">';
                            html += info["useDay"];
                            html += '</td>';
                            html += '</tr>';
                        });
                        $("#txtTblAnnualVacation > tbody").append(html);
                        $("#txtTblAnnualVacation").show();
                    }
                    else {
                        $("#txtTblAnnualVacation").hide();
                    }
                }

                $("#alterSign").val(result["alterSign"]);
                var myAppAgr = result["myAppAgr"];
                $("#myAppAgrUser").val(myAppAgr["docUserId"]);
                $("#myAppKind").val(myAppAgr["appKind"]);

                $("#modalDetailAppDoc .imgLogo").attr("src", result["logo"]);

                if (!$('#modalDetailAppDoc').hasClass('show')) {
                    $("#modalDetailAppDoc").modal("show");
                }
            }
        },
        complete: function() {
            var detailWidth = $("#divDF11 table").width();
            if(detailWidth > 700) {
                $("#divDF11").addClass("printSize");
            } else {
                $("#divDF11").removeClass("printSize");
            }
            $("input:button[name='btnDetailAppDoc']").prop("disabled", false);
            $("#modalDetailAppDoc").data("processing", false);

            // 폰트사이즈가 잘 안먹는 버그
            $("#divDF11 strong").css("font-size", "inherit");
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//상신취소
function onBtnCancelAppDocClick() {
    $("#mode").val("CANCEL");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            $("#modalDetailAppDoc").modal("hide");

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(5000).fadeOut();
        },
        beforeSend: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", true);
        },
        complete: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", false);
            onPageNoClick($("#pageNo").val(), "", true);
            getSubMenuCnt();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//수정
function onBtnModifyAppDocClick() {
    $("#modalDetailAppDoc").modal("hide");

    $("#actType").val("U");

    editAppDoc();
}

//삭제
function onBtnDeleteAppDocClick() {
    $("#modalConfirmDel").modal("hide");

    $("#mode").val("DEL");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            if ($("#modalDetailAppDoc").hasClass('show')) {
                $("#modalDetailAppDoc").modal("hide");
            }
            if ($("#modalEditAppDoc").hasClass('show')) {
                $("#modalEditAppDoc").modal('hide')
            }

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(5000).fadeOut();
        },
        beforeSend: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", true);
        },
        complete: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", false);
            onPageNoClick($("#pageNo").val(), "", true);
            getSubMenuCnt();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//결재이력
function onBtnShowSignInfoClick() {
    showSignInfo($("#docId").val());
}

//보류
function onBtnHoldAppDocClick() {
    $("#mode").val("HOLD");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            $("#modalDetailAppDoc").modal("hide");

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(5000).fadeOut();
        },
        beforeSend: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", true);
        },
        complete: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", false);
            onPageNoClick($("#pageNo").val(), "", true);
            getSubMenuCnt();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//결재 취소
function onBtnCancelAppAgrClick() {
   $("#txtSignKind").text("cancel");

   $("#modalConfirmSign .modal-title").text("결재취소");
   $("#msgSign").text("결재 취소 하시겠습니까?");
   $("#returnReason").closest("div").hide();

   $("#modalConfirmSign").modal("show");
}

//일괄결재
function appAgrBatchSignAppDoc() {
    $("#mode").val("BATCH_SIGN");
    var proceed = false; 
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            proceed = result["proceed"];
            if (proceed) {
                $("#modalConfirmSign").modal("hide");

                $("#resultMsg").empty().html(result["msg"]).fadeIn();
                $("#resultMsg").delay(5000).fadeOut();
            }
            else {
                $("#resultSign").empty().html(result["msg"]).fadeIn();
                $("#resultSign").delay(5000).fadeOut();
            }
        },
        beforeSend: function() {
            $("#modalConfirmSign").find("input").prop("readonly", true);
            $("#modalConfirmSign").find("textarea").prop("readonly", true);
            $("#modalConfirmSign").find("button").prop("disabled", true);
        },
        complete: function() {
            $("#modalConfirmSign").find("input").prop("readonly", false);
            $("#modalConfirmSign").find("textarea").prop("readonly", false);
            $("#modalConfirmSign").find("button").prop("disabled", false);

            if (proceed) {
                onPageNoClick($("#pageNo").val(), "", true);
                getSubMenuCnt();
            }
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//반려
function appAgrReturnAppDoc() {
    $("#mode").val("RETURN");
    var proceed = false; 
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            proceed = result["proceed"];
            if (proceed) {
                $("#modalConfirmSign").modal("hide");

                $("#modalDetailAppDoc").modal("hide");

                $("#resultMsg").empty().html(result["msg"]).fadeIn();
                $("#resultMsg").delay(5000).fadeOut();
            }
            else {
                $("#resultSign").empty().html(result["msg"]).fadeIn();
                $("#resultSign").delay(5000).fadeOut();
            }
        },
        beforeSend: function() {
            $("#modalConfirmSign").find("input").prop("readonly", true);
            $("#modalConfirmSign").find("textarea").prop("readonly", true);
            $("#modalConfirmSign").find("button").prop("disabled", true);
        },
        complete: function() {
            $("#modalConfirmSign").find("input").prop("readonly", false);
            $("#modalConfirmSign").find("textarea").prop("readonly", false);
            $("#modalConfirmSign").find("button").prop("disabled", false);

            if (proceed) {
                onPageNoClick($("#pageNo").val(), "", true);
                getSubMenuCnt();
            }
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//결재 취소
function appAgrCancelAppDoc() {
    $("#mode").val("CANCEL_APPAGR");
    var proceed = false; 
    $.ajax({ 
        type: "POST", 
        url: "/gw/ea2/ea_appdoc_list_2.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            proceed = result["proceed"];
            if (proceed) {
                $("#modalConfirmSign").modal("hide");

                $("#modalDetailAppDoc").modal("hide");

                $("#resultMsg").empty().html(result["msg"]).fadeIn();
                $("#resultMsg").delay(5000).fadeOut();
            }
            else {
                $("#userPwd").prop("readonly", false);
                $("#returnReason").prop("readonly", false);
                $("#modalConfirmSign").find("button").prop("disabled", false);

                $("#resultSign").empty().html(result["msg"]).fadeIn();
                $("#resultSign").delay(5000).fadeOut();
            }
        },
        beforeSend: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", true);
        },
        complete: function() {
            $("#modalDetailAppDoc").find("button:button").prop("disabled", false);
            if (proceed) {
                onPageNoClick($("#pageNo").val(), "", true);
                getSubMenuCnt();
            }
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//재작성
function onBtnResubmitAppDocClick() {
    $("#modalDetailAppDoc").modal("hide");

    $("#actType").val("R");

    editAppDoc();
}

//수정취소
function onBtnCancelEditAppDocClick() {
    onBtnCloseEditAppDocClick();

    onBtnDetailAppDocClick($("#docId").val(), $("#formId").val());
}

//편집 창 닫기 버튼 클릭
function onBtnCloseEditAppDocClick() {
    if ($("#detectEditAppDoc").val() == "Y") {
        $("#modalConfirmCloseEditAppDoc").modal("show");
    }
    else {
        $("#modalEditAppDoc").modal("hide");
    }
}

//작성취소 버튼 클릭
function onBtnConfirmCloseEditAppDocClick() {
    $("#modalConfirmCloseEditAppDoc").modal("hide");
    $("#modalEditAppDoc").modal("hide");
}

// 인쇄
function printAppDoc() {
    $(".cutPage, .printFooter").remove();

    var contentHeight = $("#modalDetailAppDoc .modal-content").height();
    var pageHeight = 1000;
    var cutCnt = Math.trunc(contentHeight / pageHeight);
    var pageCnt = 1;
    $("#modalDetailAppDoc .modal-content *").each(function() {
        var tagHeight = $(this).offset().top + 45;

        for (let i = 1; i <= cutCnt; i++) {
            if(i == 1) {
                pageHeight = 1080;
            } else {
                pageHeight = 1000;
            }
            if (tagHeight >= pageHeight * (i) && pageCnt == i) {
                $(this).before('<div class="cutPage"></div>');
                pageCnt++;
            }
        }
    });

    window.print();
}
</script>
<form id="mainForm" name="mainForm" method="post" enctype="multipart/form-data" action="/gw/ol/ol_eas_list.php">
<div class="btnList">
    <div style="display: none;">
        <button type="button" class="btn btn-primary" id="btnBatchSign" disabled>일괄결재</button>
    </div>
    <div style="display: none;">
        <button type="button" class="btn btn-primary mr-2" id="btnBatchRead" disabled>일괄열람</button>
    </div>
    <div style="display: none;">
        <button type="button" class="btn btn-primary" id="btnShowMoveTray" disabled>결재함 이동</button>
    </div>
</div>
<div id="divSearch">
<div class="row">
    <div class="col-lg-8 search-inline mb-2">
        <!-- <div class="input-group">
            <div class="input-group-prepend">
                <select class="form-control prependDdlSearch" id="ddlSearchDate" name="ddlSearchDate">
                </select>
            </div>
            <input type="date" class="form-control mr-2" id="searchFrom" name="searchFrom" />
            -
            <input type="date" class="form-control ml-2" id="searchTo" name="searchTo" />
            <div class="input-group-append">
                <button type="button" id="btnSearchDate" name="btnSearchDate" class="btn btn-info">
                    <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                    <span class="fas fa-magnifying-glass"></span>
                </button>
            </div>
        </div> -->
    </div>
    <div class="col-lg-4 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend">
                <select class="form-control prependDdlSearch" id="ddlSearchKind" name="ddlSearchKind">
                    <option value="ALL">전체</option>
                    <option value="DOC_CD">문서번호</option>
                    <option value="TITLE">제목</option>
                    <option value="USER_NAME">작성자</option>
                </select>
            </div>
            <input type="search" class="form-control" id="txtSearchValue" name="txtSearchValue" maxlength="50"/>
            <div class="input-group-append">
                <button type="button" id="btnSearch" name="btnSearch" class="btn btn-info">
                    <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                    <span class="fas fa-magnifying-glass"></span>
                </button>
            </div>
        </div>
        <!-- 자동완성 방지 -->
        <input type="text" style="width:0rem; height:0rem; border: 0;" aria-hidden="true">
    </div>
</div>
<input type="hidden" id="ddlAppKind" name="ddlAppKind" value="10" />
<input type="hidden" id="ddlOperKind" name="ddlOperKind" value="-999" />
</div>
<div id="resultMsg" class="alert alert-primary py-1 mb-2" style="display: none;"></div>

<div class="tableFixHead">
    <table class="table" id="tblEasList" style="table-layout: fixed;">
        <thead class="thead-light">
        <tr class="row">
            <!-- <th class="col-md-1 col-1 col-w-chk"><input type="checkbox" id="chkAll" onclick="onChkAllDocClick(this);" /></th> -->
            <th class="col-md-3 d-none d-md-block">품의번호</th>
            <th class="col-md d-none d-md-block">제목</th>
            <th class="col-md-2 d-none d-md-block">기안자/기안일</th>
            <th class="col-md-block d-md-none col-9">문서</th>
            <th class="col-md-2 d-none d-md-block col-w-state">상태</th>
            <th class="col-md-1 col-2 col-w-btn">상세</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>

<ul class="pagination justify-content-center" id="pageList">
</ul>

<?php 
require_once 'ol0000011_detail_view.php';
require_once 'ol0000011_edit_view.php';
require_once '../so/so600200_view.php';
require_once '../cm/cm_select_appline_view.php'; 
require_once '../cm/cm_select_dept_user_view.php';
?>

<input type="hidden" id="mode" name="mode" /> 
<input type="hidden" id="appAgrCnt" name="appAgrCnt" value="5" />
<input type="hidden" id="menuId" name="menuId" />
<input type="hidden" id="loginUserDeptId" name="loginUserDeptId" />
<input type="hidden" id="userMenuId" name="userMenuId" />
<input type="hidden" id="appbox" name="appbox" />
<input type="hidden" id="viewOrderField" name="viewOrderField" />
<input type="hidden" id="viewOrderDirect" name="viewOrderDirect" />
<input type="hidden" id="pageNo" name="pageNo" value="1" />
<input type="hidden" id="formId" name="formId" />
<input type="hidden" id="formNm" name="formNm" />
<input type="hidden" id="formKind" name="formKind" />
<input type="hidden" id="formAppKind" name="formAppKind" />
<input type="hidden" id="ebMoveYn" name="ebMoveYn" />
<input type="hidden" id="ebKind2" name="ebKind2" />
<input type="hidden" id="eaAppLineEdit" name="eaAppLineEdit" />
<input type="hidden" id="eaLastPreApp" name="eaLastPreApp" />
<input type="hidden" id="eaPrintReplyPosition" name="eaPrintReplyPosition" />
<input type="hidden" id="eaReturnReason" name="eaReturnReason" />
<input type="hidden" id="docId" name="docId" />
<input type="hidden" id="nowApp" name="nowApp" />
<input type="hidden" id="seq" name="seq" />
<input type="hidden" id="actType" name ="actType" />
<input type="hidden" id="appLineType" name="appLineType" />
<input type="hidden" id="appUserYn" name="appUserYn" />
<input type="hidden" id="reqRelYn" name="reqRelYn" />
<input type="hidden" id="eaAppDtEdit" name="eaAppDtEdit" />
<input type="hidden" id="appKindDisplay" name="appKindDisplay" />
<input type="hidden" id="recipientIds" name="recipientIds" />
<input type="hidden" id="operatorIds" name="operatorIds" />
<input type="hidden" id="replyId" name="replyId" />
<input type="hidden" id="detectEditAppDoc" name="detectEditAppDoc" value="N" />
<input type="hidden" id="moveToPage" name="moveToPage" value="" />
<input type="hidden" id="ono" name="ono" />
<input type="hidden" id="docType" name="docType" />
</form>