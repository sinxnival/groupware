<script>
$(document).ready(function(){
    $("#userPwd").on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // 기본 submit 막기
        }
    });

    $("#btnConfirmDelete").on('click', onBtnDelEBClick);
})

function showObDetail(ono) {
    $("#showOno").val(ono);
    
    var modalId = "modalObDetail_" + ono;
    var modal = $("#modalObDetail").clone().attr("id", modalId);
    $("#mainForm").append(modal);
    $("#" + modalId).find("[id]").each(function() {
        $(this).attr("id", this.id + '_' + ono);
    });
    
    $("#modalObDetail_" + ono).modal("show");

    //작업모드
    $("#mode").val("DETAIL_SHOW");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ol/ol0000011_detail.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }
            
            var docType = result["docType"];
            $("#docType").val(docType);
            var url = '';
            if(docType == "S") {
                url = "/gw/ol/form_obg_detail.php";
                $("#modalObDetail_" + ono + " .modal-title").text("대외공문 발신");
            } else if(docType == "R") {
                url = "/gw/ol/form_obr_detail.php";
                $("#modalObDetail_" + ono + " .modal-title").text("대외공문 접수");
            }

            var docInfo = result["docInfo"];
            $.ajax({
                url: url, 
                success: function(result) {
                    $("#obTxtContents_" + ono).empty().append(result);

                    $("#obTxtContents_" + ono).find("[id]").each(function() {
                        $(this).attr("id", this.id + "_" + ono);
                    });

                    $.each(docInfo, function(key, value) {
                        // textarea, input, span, div 등 요소 유형에 따라 다르게 처리할 수도 있음
                        const $el = $("#" + key + "_" + ono);

                        if ($el.is("input, textarea")) {
                            $el.val(value); // form 요소
                        } else {
                            $el.text(value); // 일반 텍스트 요소
                        }
                    });

                    if(docInfo["txtPjtNmFr"]) {
                        $("#detailPjtGroup_" + ono).show();
                    } else {
                        $("#detailPjtGroup_" + ono).hide();
                    }
                }
            });

            if(hasTextInHtmlString(docInfo["content"])) {
                $("#divDF11_" + ono).empty().append(docInfo["content"]);
            } else {
                $("#divDF11_" + ono).empty().html('<h4>' + docInfo["txtTitle"] + '</h4>');
            }

            var appAgrCnt = $("#appAgrCnt").val();
            var html = drawAppLineShow(result["appLine"], appAgrCnt, "", true);
            $("#divDetailAppLine_" + ono).empty().append(html);

            //권한
            var isTurn = result["isTurn"];
            var deleAuth = result["deleAuth"];
            var isCancel = result["isCancel"];

            if(isTurn == "APPROVE") {
                $("#btnSignApp_" + ono).show();
                $("#btnReturnApp_" + ono).show();
                $("#btnSignAgr_" + ono).hide();
                $("#btnDisAgr_" + ono).hide();
                if(deleAuth == "Y") {
                    $("#btnDeligateSignApp_" + ono).show();
                } else {
                    $("#btnDeligateSignApp_" + ono).hide();
                }

                if(docInfo["txtProcess"]) {
                    $("#process").val(docInfo["txtProcess"]);
                }
            } else if (isTurn == "AGREE") {
                $("#btnSignApp_" + ono).hide();
                $("#btnReturnApp_" + ono).hide();
                $("#btnSignAgr_" + ono).show();
                $("#btnDisAgr_" + ono).show();
                $("#btnDeligateSignApp_" + ono).hide();
            } else {
                $("#btnSignApp_" + ono).hide();
                $("#btnReturnApp_" + ono).hide();
                $("#btnSignAgr_" + ono).hide();
                $("#btnDisAgr_" + ono).hide();
                $("#btnDeligateSignApp_" + ono).hide();
            }

            if(isCancel == "Y") {
                $("#btnCancelSignApp_" + ono).show();
            } else {
                $("#btnCancelSignApp_" + ono).hide();
            }

            // 직인 여부
            if(result["isComplete"] == "Y") {
                var signUrl = result["signUrl"];

                $('#divDF11_' + ono  + ' .sendVal').after('<img src="'+ signUrl +'" class="seal" style="padding-left:10px;height:70px" alt="직인">');
            }

            if(!docInfo["txtOutDocCd"]) {
                $(".docCd span").text(docInfo["txtDocCd"]);
            }

            if(result["status"] == "05") {
                $('#divDF11_' + ono  + ' .director').html("전결");
                let considerArr = [];

                $(result["appLine"]["app"]).each(function(i, line) {
                    if (line["signKind"]) {
                        let val = line["appAgrValue"].split("|")[3];
                        if (val) {
                            considerArr.push(val);
                        }
                    }
                });

                let consider = considerArr.join(', ');
                $('#divDF11_' + ono  + ' .consider').html(consider);
            }

            // 업로드 버튼 
            if(result["isComplete"] == "Y" && result["uploadAuth"] == "Y" && docType == "S") {
                $("#btnUploadScan_" + ono).show();
                
                if (result["finalFile"] && typeof result["finalFile"] === "object" && Object.keys(result["finalFile"]).length > 0) {
                    $("#divFileUpload").hide();
                    $("#divFile").show();
                    
                    $("#divFile .custom-file label").html('<a href="' + result["finalFile"]["url"] + '" target="_blank">' + result["finalFile"]["fileNm"] + '</a>');
                } else {
                    $("#divFileUpload").show();
                    $("#divFile").hide();
                }
            } else {
                $("#btnUploadScan" + ono).hide();
            }
            
            // 수정 가능 여부
            $topMenuCd = $("#topMenuCd").val();
            if(result["isEdit"] == "Y" && $topMenuCd == "ol0000000") {
                $('#btnModifyEB_' + ono).show();
                $('#btnDeleteEB_' + ono).show();
            } else {
                $('#btnModifyEB_' + ono).hide();
                $('#btnDeleteEB_' + ono).hide();
            }

            // 인쇄 버튼 유무
            if(result["isPrint"] == "Y" && docType == "S") {
                $('#btnPrint_' + ono).show();
            } else {
                $('#btnPrint_' + ono).hide();
            }

            $("#btnModifyEB_" + ono).on("click", function() {
                onBtnEditObClick(ono);
            });

            if (Object.keys(result["attachFileList"]).length > 0) {
                //첨부파일
                html = "";
                $(result["attachFileList"]).each(function(i, info) {
                    html += '<div class="ellipsisLongTxt">';
                    html += '<a href="'+ info["fileLink"] +'" target="_blank">';
//                         html += '<a href="' + info["fileDownloadLink"] + '" target="_blank">';
                    html += '<i class="fa-regular fa-file-lines"></i> ' + info["oriFileNm"];
                    html += '</a>'; 
                    html += '<span style="display: none;" class="txtAttachFileId">' + info["attachId"] + '</span>';
                    html += '<span style="display: none;" class="txtAttachFile">' + info["fileNm"] + '</span>';
                    html += '<span style="display: none;" class="txtAttachOriFileNm">' + info["oriFileNm"] + '</span>';
                    html += '</div>';
                });
                $("#external_txtAttachedList_" + ono).empty().append(html);
                $("#external_txtAttachedList_" + ono).closest("div.row").show();
            }
            else {
                $("#external_txtAttachedList_" + ono).closest("div.row").hide();
            }

            html = '';
            if (Object.keys(result["relatedEBList"]).length > 0 && docType == "S") {
                //참조문서
                $(result["relatedEBList"]).each(function(i, info) {
                    html += '<div class="ellipsisLongTxt">';
                    html += '<a href="javascript:void(0);" onclick="showObDetail(' + info["refDoc"] + ')">';
                    html += '<i class="fa-solid fa-thumbtack"></i> ' + info["title"];
                    html += '</a>';
                    html += '</div>';
                });
                $("#txtRelatedEBList_" + ono).empty().append(html);
                $("#txtRelatedEBList_" + ono).closest("div.row").show();
            } else {
                $("#txtRelatedEBList_" + ono).closest("div.row").hide();
            }
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//결재
function onBtnSignAppClick() {
    $("#signKind").val("APPROVE");
    var signVal = "01";
    $("#signVal").val(signVal);
    $("#txtSignKind").text("sign");
    $("#alterSign").val("N");

    $("#modalConfirmSign .modal-title").text("결재하기");
    $("#msgSign").text("결재하시겠습니까?");
    $("#returnReason").closest("div").hide();
    $("#returnReason").prop("required", false);
    
    var docType = $('#docType').val();
    if(docType == "R") {
        $("#process").closest("div").show();
    } else {
        $("#process").closest("div").hide();
    }

    $("#modalConfirmSign").modal("show");
}

//결재취소
function onBtnCancelAppClick() {
    $("#signKind").val("");
    var signVal = "";
    $("#signVal").val('');
    $("#alterSign").val("N");
    $("#process").closest("div").hide();

    $("#modalConfirmSign .modal-title").text("결재 취소하기");
    $("#msgSign").text("결재를 취소하시겠습니까?");
    $("#returnReason").closest("div").hide();
    $("#returnReason").prop("required", false);
    
    var docType = $('#docType').val();
    $("#modalConfirmSign").modal("show");
}

//전결
function onBtnDeligateSignAppClick() {
    $("#signKind").val("APPROVE");
    var signVal = "05";
    $("#signVal").val(signVal);
    $("#txtSignKind").text("sign");
    $("#alterSign").val("Y");

    $("#modalConfirmSign .modal-title").text("결재하기");
    $("#msgSign").text("결재하시겠습니까?");
    $("#returnReason").closest("div").hide();
    $("#returnReason").prop("required", false);

    var docType = $('#docType').val();
    if(docType == "R") {
        $("#process").closest("div").show();
    } else {
        $("#process").closest("div").hide();
    }

    $("#modalConfirmSign").modal("show");
}

//반려
function onBtnReturnAppClick() {
    $("#signKind").val("APPROVE");
    var signVal = "02";
    $("#signVal").val(signVal);
    $("#txtSignKind").text("return");
    $("#alterSign").val("N");

    $("#modalConfirmSign .modal-title").text("반려하기");
    $("#msgSign").text("반려하시겠습니까?");
    //반려시 반려팝업 사용 여부
    // if ($("#eaReturnReason").val() == "0") {
    //     $("#returnReason").closest("div").hide();
    //     $("#returnReason").prop("required", false);
    // }
    // else if ($("#eaReturnReason").val() == "1") {
        $("#returnReason").closest("div").show();
        $("#process").closest("div").hide();
        //반려사유 체크여부
        // if ($("#appComment").val() == "1") {
        //     $("#returnReason").prop("required", true);
        // }
        // else {
            $("#returnReason").prop("required", false);
        // }
    // }

    $("#modalConfirmSign").modal("show");
}

//합의
function onBtnSignAgrClick() {
    $("#signKind").val("AGREE");
    var signVal = "03";
    $("#signVal").val(signVal)
    $("#txtSignKind").text("sign");
    $("#alterSign").val("N");

    $("#modalConfirmSign .modal-title").text("합의하기");
    $("#msgSign").text("합의하시겠습니까?");
    $("#returnReason").closest("div").hide();
    $("#returnReason").prop("required", false);
    $("#process").closest("div").hide();

    $("#modalConfirmSign").modal("show");
}

//합의 거부
function onBtnDisAgrClick() {
    $("#signKind").val("AGREE");
    var signVal = "04";
    $("#signVal").val(signVal)
    $("#txtSignKind").text("sign");
    $("#alterSign").val("N");

    $("#modalConfirmSign .modal-title").text("거부하기");
    $("#msgSign").text("거부하시겠습니까?");
    $("#returnReason").closest("div").show();
    $("#returnReason").prop("required", false);
    $("#process").closest("div").hide();

    $("#modalConfirmSign").modal("show");
}


function onBtnConfirmSignClick() {
    //작업모드
    $("#mode").val("SIGN");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ol/ol0000011_detail.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            proceed = result["proceed"];
            var ono = $("#showOno").val();
            if (proceed) {
                $("#modalConfirmSign").modal("hide");

                $("#modalObDetail_" + ono).modal("hide");
                $("#modalObDetail_" + ono).remove();
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open");
                $("body").css("padding-right", "");

                $("#resultMsg").empty().html(result["msg"]).fadeIn();
                $("#resultMsg").delay(5000).fadeOut();

                var topMenu = $("#topMenuCd").val();
                if(topMenu == "ol0000000") {
                    onConditionChange();
                } else {
                    mainListChange();
                }
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
        }
    })
}

function printObDoc() {
    var ono = $("#showOno").val();
    var docCd = $("#txtDocCd_" + ono);
    var title = $("#txtTitle_" + ono);

    const originalTitle = docCd + ' ' + title;
    const $printContents = $('#modalObDetail_'+ ono +' .printArea').clone();

    const unitHeight = 1080;

    // 높이 측정용 wrapper 생성
    const tempWrapper = $('<div></div>').append($printContents);
    $('body').append(tempWrapper);
    const contentHeight = $printContents.outerHeight(true);
    tempWrapper.remove();

    const totalPages = Math.ceil(contentHeight / unitHeight);
    const remainder = contentHeight % unitHeight;

    // 기본 패딩 계산
    let paddingHeight = remainder === 0 ? 0 : unitHeight - remainder;

    // 페이지 수에 따른 보정 계산 (2페이지부터 적용)
    if (totalPages >= 2) {
        const correction = 100 + (totalPages - 2) * 25;
        paddingHeight = Math.max(0, paddingHeight - correction);
    }

    if (paddingHeight > 0) {
        const fillerDiv = $('<div></div>', {
            css: {
                height: `${paddingHeight}px`,
                visibility: 'hidden',
                pageBreakBefore: 'auto'
            },
            class: 'print-filler'
        });

        const $footerCandidates = $printContents.find('[id^="obgFooter"]'); // id가 obgFooter로 시작하는 모든 요소
        if ($footerCandidates.length) {
            const $lastFooter = $footerCandidates.last(); // 가장 마지막 footer
            fillerDiv.insertBefore($lastFooter);
        } else {
            $printContents.append(fillerDiv); // 없으면 마지막에 붙이기
        }
    }

    const printWindow = window.open('', '', 'width=1700,height=1600');
    printWindow.document.write(`
        <html>
        <head>
            <title>${originalTitle}</title>
            <style>
                .seal {
                    vertical-align: middle;
                }
                .print-filler {
                    visibility: hidden;
                }
                .MsoNormalTable {
                    line-height: 1.8 !important;
                }
                @media print {
                    .print-filler {
                        display: block;
                    }
                }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            ${$printContents.html()}
        </body>
        </html>
    `);
    printWindow.document.close();
}

//첨부파일 삭제
function delAttachedFile(obj) {
    $("#fileScan").val('');
    $("#fileScan").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
}

function delFinalFile(obj) {
    $("#fileScan").val('');
    $("#fileScan").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');

    $("#mode").val("DEL_FILE");

    $.ajax({ 
        type: "POST", 
        url: "/gw/ol/ol0000011_detail.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            var proceed = result["proceed"];

            if(proceed == "true") {
                $("#modalMsg").removeClass("alert-primary");
                $("#modalMsg").addClass("alert-danger");
                
                $("#modalMsg").empty().html('삭제되었습니다.').fadeIn();
                $("#modalMsg").delay(3000).fadeOut();
                
                $("#divFile").hide();
                $("#divFileUpload").show();
            }
        }
    })
}

//첨부파일 선택 시
function onAttachFileChange(obj) {
    var fileName = $(obj).val().split("\\").pop();
    $(obj).siblings(".custom-file-label").addClass("selected").html(fileName);

    $("#mode").val("UPLOAD_SCAN");

    var formdata = new FormData($("#mainForm")[0]);
    $.ajax({
        type: "POST",
        url: "/gw/ol/ol0000011_detail.php",
        data: formdata,
        dataType: "json",
        contentType: false,
        processData: false,
        success: function (result) {
            var proceed = result["proceed"];

            if(proceed == true) {
                $("#modalMsg").removeClass("alert-danger");
                $("#modalMsg").addClass("alert-primary");

                $("#divFileUpload").hide();
                $("#divFile").show();

                $("#divFile .custom-file label").html('<a href="' + result["url"] + '">' + result["oriFileName"] + '</a>');
            } else {
                $("#modalMsg").removeClass("alert-primary");
                $("#modalMsg").addClass("alert-danger");
            }

            $("#modalMsg").empty().html(result["msg"]).fadeIn();
            $("#modalMsg").delay(3000).fadeOut();
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

function hasTextInHtmlString(htmlString) {
  // 임시 DOM 요소에 파싱
  const tempEl = $('<div>').html(htmlString);

  // 모든 텍스트 노드를 찾아 실제 텍스트가 있는지 확인
  const textNodes = tempEl
    .contents()
    .addBack() // 자신도 포함
    .find('*') // 하위 모든 요소
    .addBack() // 본인도 다시 포함
    .contents()
    .filter(function () {
      return this.nodeType === Node.TEXT_NODE && $.trim(this.nodeValue) !== '';
    });

  return textNodes.length > 0;
}

function modalScanUploadShow() {
    $("#modalUploadScan").modal('show');
}

function onBtnDelEBClick() {
    $("#mode").val("DEL_EB");
    var ono = $("#showOno").val();
    $("#ono").val(ono);

    $.ajax({
        type: "POST",
        url: "/gw/ol/ol0000011.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function (result) {
            if(result["proceed"]) {
                $("#modalConfirmDel").modal('hide');
                $("#modalObDetail_" + ono).modal('hide');

                $("#resultMsg").empty().html(result["msg"]).fadeIn();
                $("#resultMsg").delay(5000).fadeOut();

                onConditionChange();
            }
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}
</script>

<!-- The Modal -->
<div class="modal fade modalMain modalEaDocApp" id="modalObDetail" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title"></h4>
                <div id="divBtnList" class="col-md">
                    <div class="d-flex justify-content-end mr-3">
                        <button type="button" class="btn btn-primary mr-2" id="btnPrint" onclick="printObDoc()" style="display: none;">인쇄</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnModifyEB" style="display: none;">수정</button>
                        <button type="button" class="btn btn-danger mr-2" id="btnDeleteEB" style="display: none;" data-toggle="modal" data-target="#modalConfirmDel">삭제</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnUploadScan" style="display: none;" onclick="modalScanUploadShow()">최종본 업로드</button>
                        <!-- <button type="button" class="btn btn-primary mr-2" id="btnPreview" onclick="previewPdfEb()" style="display: none;">미리보기</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnPdfConvert" JavaScript="fn_MakePdfFile_Detail();" style="display: none;">PDF저장</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnCancelAppDoc" style="display: none;">상신취소</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnReUse" JavaScript="true;" style="display: none;">기안작성</button> -->
                    </div>
                </div>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row row-direction-reverse">
                    <div class="col-md-5 mb-2">
                        <div class="d-flex">
                            <div class="ml-auto" style="z-index: 999;">
                                <button type="button" class="btn btn-info" id="btnEditAppLine_" onclick="onBtnEditAppLineClick()" style="display: none;">결재라인수정</button>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div id="divDetailAppLine" class="ml-auto"></div>
                        </div>
                    </div>
                    <div class="col-md-7 mb-2">
                        <div id="obTxtContents" class="mainContents"></div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col d-flex justify-content-center p-0 ml-3 mr-3 printArea" style="border: 0.1rem solid #999;min-height: 28rem;">
                        <div class="py-2">
                            <div class="mb-4">
                                <h4 id="txtDF10_" style="font-weight: bold;"></h4>
                            </div>
                            <div id="divDF11"></div>
                        </div>
                    </div>
                </div>
                <div class="row mb-2" style="display: none">
                    <div class="col-2 colHeader">참조문서</div>
                    <div class="col-10">
                        <div id="txtRelatedEBList" class="px-2 py-1" style="border: 0.1rem solid #999;">
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-2 colHeader">첨부파일</div>
                    <div class="col-10">
                        <div id="external_txtAttachedList" class="px-2 py-1" style="border: 0.1rem solid #999;">
                        </div>
                    </div>
                </div>
                <hr />
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container">
                    <div class="d-flex justify-content-around">
                        <!-- <button type="button" class="btn btn-success mr-2" name="btnShowSignInfoHis">결재이력</button> -->
                        <button type="button" class="btn btn-primary mr-2" id="btnDeligateSignApp" onclick="onBtnDeligateSignAppClick()" style="display: none;">전결</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnSignApp" onclick="onBtnSignAppClick()" style="display: none;">결재</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnCancelSignApp" onclick="onBtnCancelAppClick()" style="display: none;">결재취소</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnReturnApp" onclick="onBtnReturnAppClick()" style="display: none;">반려</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnSignAgr" onclick="onBtnSignAgrClick()" style="display: none;">합의</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnDisAgr" onclick="onBtnDisAgrClick()" style="display: none;">거부</button>
                        <button type="button" class="btn btn-primary mr-2" id="btnReturnAgr" onclick="onBtnReturnAgrClick()" style="display: none;">반려</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 결재 확인창 -->
<div class="modal fade" id="modalConfirmSign" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title"></h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <p id="msgSign"></p>
                <span id="txtSignKind" style="display: none;"></span>
                <div class="form-group mb-2">
                    <label for="userPwd">비밀번호</label><span class="necessaryInput"> *</span>
                    <input type="password" class="form-control validateElement" id="userPwd" name="userPwd" autocomplete="new-password" required />
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group mb-2">
                    <label for="returnReason">반려사유</label>
                    <textarea class="form-control validateElement" rows="5" id="returnReason" name="returnReason"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="process">처리상태</label>
                    <select class="form-control" id="process" name="process">
                        <option value="보고">보고</option>
                        <option value="이관">이관</option>
                        <option value="조치">조치</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div id="resultSign" class="alert alert-danger" style="display: none;"></div>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" id="btnConfirmSign" onclick="onBtnConfirmSignClick()" class="btn btn-primary">확인</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUploadScan" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
      
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">최종본 업로드</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        
        <!-- Modal body -->
        <div class="modal-body">
            <b>최종본 업로드</b>
            <div class="input-group" id="divFileUpload">
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="fileScan" name="fileScan" onchange="onAttachFileChange(this)">
                    <label class="custom-file-label" for="customFile"><i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요</label>
                </div>
                <div class="input-group-append">
                    <button type="button" class="btn btn-secondary" id="btnDel" name="btnDel" onclick="javascript:delAttachedFile(this);">×</button>
                </div>
            </div>
            <div class="input-group" id="divFile" style="display:none">
                <div class="custom-file">
                    <label class="custom-file-label" for="customFile"></label>
                </div>
                <div class="input-group-append">
                    <button type="button" class="btn btn-secondary" onclick="javascript:delFinalFile(this);">×</button>
                </div>
            </div>
            <div id="modalMsg" class="alert alert-primary py-1 mb-2 mt-2" style="display: none;"></div>
        </div>
        
        <!-- Modal footer -->
        <div class="modal-footer">
            <div class="container-fluid">
                <div class="d-flex justify-content-around">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                </div>
            </div>
        </div>
      </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmDel" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <!-- Modal body -->
            <div class="modal-body">
                <p>삭제하시겠습니까?</p>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container">
                    <div class="d-flex justify-content-around">
                        <button type="button" id="btnConfirmDelete" class="btn btn-primary">네</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">아니오</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="showOno" name="showOno" />
<input type="hidden" id="manageEaAppDoc" name="manageEaAppDoc" />
<!-- <input type="hidden" id="appAgrCnt" name="appAgrCnt" /> -->
<input type="hidden" id="proKind" name="proKind" />
<input type="hidden" id="proId" name="proId" />
<input type="hidden" id="alterSign" name="alterSign" />
<input type="hidden" id="myAppAgrUser" name="myAppAgrUser" />
<input type="hidden" id="myAppKind" name="myAppKind" />
<input type="hidden" id="signKind" name="signKind" />
<input type="hidden" id="signVal" name="signVal" />
<input type="hidden" id="docCoId" name="docCoId" />
<input type="hidden" id="appAgrCnt" name="appAgrCnt" value="5" />
