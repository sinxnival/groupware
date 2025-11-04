<style>
#textareaContent {
    height: 40rem;
}

.nowrap10 {
    white-space: nowrap;
    min-width: 10rem;
}

.nowrap5 {
    white-space: nowrap;
    min-width: 5rem;
}

</style>
<script type="text/javascript" src="/js/ea.js?random=<?php echo uniqid(); ?>"></script>
<script>
var editor = new SynapEditor("textareaContent", synapEditorConfig);
$(document).ready(function(){
    var subMenuCd = $("#subMenuCd").val();
    if(subMenuCd == "ol0000011") {
        $("#docType").val("S");
        $("#obgList").show();
        $("#obrList").hide();
        $("#filterReply").hide();
        $("#tblRelatedEBList").closest(".form-group").show();
    } else {
        $("#docType").val("R");
        $("#obrList").show();
        $("#obgList").hide();
        $("#filterReply").show();
        $("#tblRelatedEBList").closest(".form-group").hide();
    }

    onConditionChange();

    //검색조건 - 입력란
    $("#txtSearchValue").on("keyup", function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            onConditionChange();
        };
    });

    $("#btnPush").on('click', function() {
        $(".row input").each(function() {
            var inputId = $(this).attr("id");
    
            editor.setText('.' + inputId, $(this).val());
        });
    
        if($('input[name="chkDocCd"]:checked').val() == "outDocCd") {
            editor.setText('.docCd', $("#outDocCd").val());
        }

        var checkedValues = [];

        $("input[name='chkSendKind[]']:checked").each(function () {
            checkedValues.push($(this).val());
        });

        $("#sendKind").val(checkedValues.join("|"));
    })

    //검색 버튼
    $("#btnSearch").on("click", onConditionChange);
    //등록 버튼
    $("#btnAdd").on("click", onBtnAddClick);
    //삭제 버튼
    $("#btnDel").on('click', onBtnDelClick);
    $("#btnChkDel").on('click', onBtnDelClick);
    $("#ddlReplyKind").on('change', onConditionChange);

    //모달 초기화
    $("#modalEditOL").on("hidden.bs.modal", function () {
        $("#appDocContents").empty();
    });
});

//목록표시
function onConditionChange() {
    onPageNoClick(1, "", false);
}

function showInfoList(list) {
    var subMenuCd = $("#subMenuCd").val();

    if(subMenuCd == "ol0000011") {
        var html = "";
        $(list).each(function(i, info) {
            html += '<tr>';
            html += '<td class="nowrap5" style="padding: 0.5rem !important">';
            html += info["seq"];
            html += '</td>';
            html += '<td style="padding: 0.5rem !important;">';
            html += '<div class="ellipsisLongTxt" style="width: 200px">';
            html += info["docCd"] + '<br/>' + info["issueDate"];
            html += '</div>';
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["issueDate"];
            // html += '</td>';
            html += '<td class="notAlign" style="padding: 0.5rem !important">';
            html += '<div class="ellipsisLongTxt" style="width: 400px">';
            html += '<a href="javascript:void(0);" onclick="showObDetail('+ info["ono"] +')">';
            html += '<b>' + info["title"] + '</b>';
            html += '</a>';
            html += '</div>';
            html += '</td>';
            html += '<td class="nowrap5" style="padding: 0.5rem !important">';
            var atchLink = "";
            if(info["atchFiles"]) {
                $.each(info["atchPaths"], function(fileNm, url) {
                    atchLink += `<div><a href='${url}' target='_blank'>${fileNm}</a></div>`;
                });
                html += `<a href="javascript:void(0);" 
                            class="atchPopover" 
                            data-toggle="popover" 
                            data-trigger="focus" 
                            data-html="true" 
                            data-placement="right" 
                            data-content="${atchLink}">
                            <i class="fa-solid fa-floppy-disk pl-2"></i>
                        </a>`;
            }
            html += '</td>';
            html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            html += info["recipient"] + '<br/>' + info["reference"];
            html += '</td>';
            // html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            // html += info["reference"];
            // html += '</td>';
            html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            html += info["sender"] + '<br/>' + info["type"];
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["type"];
            // html += '</td>';
            html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            html += info["docGroup"] + '<br/>' + info["userName"];
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["userName"];
            // html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["consider"] + '<br/>' + info["director"];
            html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["rgCd"] + '<br/>' + info["coReceiverList"];
            html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["approvalKind"] + '<br/>' + info["sendKind"];
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["approvalBasis"];
            // html += '</td>'; 
            html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            if(info["fileNm"]) {
                html += '<a href="' + info["finalUrl"] + '" target="_blank">' + info["fileNm"] + '</a>';
            } else {
                if(info["approvalKind"] == "서면결재") {
                    html += '<b>서면 진행</b> 시 <span style="color:red"><b>최종본</b></span> 업로드 하세요.';
                } else {
                    html += '<span>필요 시 최종본 업로드 하세요.</span>';
                }
            }
            html += '<br/>' + info["storePeriod"];
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["approvalBasis"];
            // html += '</td>';
            html += '</tr>';
        });
        $("#tblObgList tbody").empty().append(html);
    } else if(subMenuCd == "ol0000012") {
        var html = "";
        $(list).each(function(i, info) {
            html += '<tr>';
            html += '<td class="nowrap5" style="padding: 0.5rem !important">';
            html += info["seq"];
            html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["docCd"] + '<br/>' + info["receiveDate"];
            html += '</td>';
            html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            html += info["outDocCd"] + '<br/>' + info["sender"];
            html += '</td>';
            // html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            // html += info["sender"];
            // html += '</td>';
            html += '<td class="notAlign" style="padding: 0.5rem !important">';
            html += '<div class="ellipsisLongTxt" style="width: 400px">';
            html += '<a href="javascript:void(0);" onclick="showObDetail('+ info["ono"] +')">';
            html += '<b>' + info["title"] + '</b>';
            html += '</a>';
            html += '</div>';
            html += '</td>';
            html += '<td class="nowrap5" style="padding: 0.5rem !important">';
            var atchLink = "";
            if(info["atchFiles"]) {
                $.each(info["atchPaths"], function(fileNm, url) {
                    atchLink += `<div><a href='${url}' target='_blank'>${fileNm}</a></div>`;
                });
                html += `<a href="javascript:void(0);" 
                            class="atchPopover" 
                            data-toggle="popover" 
                            data-trigger="focus" 
                            data-html="true" 
                            data-placement="right" 
                            data-content="${atchLink}">
                            <i class="fa-solid fa-floppy-disk pl-2"></i>
                        </a>`;
            }
            html += '</td>';
            html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            html += info["recipient"] + '<br/>' + info["reference"];
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["reference"];
            // html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["docScope"] + '<br/>' + info["receiveKind"];
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["receiveKind"]
            // html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["receiveDate"];
            // html += '</td>';
            html += '<td class="nowrap10 notAlign" style="padding: 0.5rem !important">';
            html += info["docGroup"] + '<br/>' + info["userName"];
            html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["consider"] + '<br/>' + info["director"];
            html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["storePeriod"] + '<br/>' + info["approvalKind"];
            html += '</td>';
            // html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            // html += info["approvalKind"];
            // html += '</td>';
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["rgVal"] + '<br/>' + info["receiverList"];
            html += '</td>'; 
            html += '<td class="nowrap10" style="padding: 0.5rem !important">';
            html += info["refDone"];
            html += '</td>';
            html += '</tr>';
        });
        $("#tblObrList tbody").empty().append(html);
    }

    $('.atchPopover').popover();
}

//첨부파일 선택 시
// function onAttachFileChange(obj) {
//     var fileName = $(obj).val().split("\\").pop();
//     $(obj).siblings(".custom-file-label").addClass("selected").html(fileName);
// }

//등록 버튼
function onBtnAddClick() {
    //삽입 모드
    $("#dbMode").val('I');
    //삭제버튼 숨기기
    $("#btnDelEB").hide();

    var docTypeVal = $("#docType").val();
    var formUrl = '';
    if(docTypeVal == "S") {
        formUrl = "/gw/ol/form_obg_input.php";
        $("#btnPush").show();
        $("#textareaContent").css("height" , "40rem");
        $("#modalEditOL .modal-title").text("대외공문 발신 작성");
    } else if(docTypeVal == "R") {
        formUrl = "/gw/ol/form_obr_input.php";
        $("#btnPush").hide();
        $("#textareaContent").css("height" , "20rem");
        $("#modalEditOL .modal-title").text("대외공문 접수 작성");
    }
    
    $.ajax({
        url: formUrl, 
        success: function(result) {
            $("#appDocContents").append(result);
        }
    })

    //작업모드
    $("#mode").val("EDIT");

    $.ajax({
        type: "POST",
        url: "/gw/ol/ol0000011.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            var sendNameList = result["sendNameList"];
            var readGradeList = result["readGradeList"];

            var html = '';
            $(sendNameList).each(function(i, info) {
                html += '<option value="'+ info["sdCd"] +'">' + info["sdType"] + '</option>';
            })
            $("#sendType").empty().append(html);

            html = '';
            $(readGradeList).each(function(i, info) {
                html += '<option value="'+ info["rgCd"] +'">' + info["rgVal"] + '</option>';
            })
            $("#readGrade").empty().append(html);
            $('#readGrade option[value="DR"]').prop('selected', true);

            editor.openHTML(result["docHtml"]);
            editor.setText('.userName', result["writer"]);
            editor.setText('.fax', result["fax"]);
            editor.setText('.email', result["email"]);
            editor.setText('.tel', result["tel"]);
            
            //부서코드
            deptNickList = result["deptNickList"];
            writerDept = result["writerDept"];

            html = '';
            $(deptNickList).each(function(i, nick) {
                html += '<option value="'+ nick +'">' + nick + '</option>';
            });
            $("#sendDept").empty().append(html);
            $("#sendDept").val(writerDept);
            $("#fax").val(result["fax"]);
            $("#email").val(result["email"]);
            $("#tel").val(result["tel"]);

            const companyCd = $('#companyCd').val();
            const sendDept = $('#sendDept').val();
            const docForm = $('#docForm').text();
            const docType = $('#docType').text();
            const issueNum = $('#issueNum').text();
            var cdYm = '';
            if(docType == "-S-") {
                cdYm = $('#issueYm').text();
            } else if(docType == "-R-") {
                cdYm = $('#receiveYm').text();
            }

            const docCd = companyCd + '-' + sendDept + docForm + docType + cdYm + issueNum;
            editor.setText('.docCd', docCd);

            var appLine = {};
            var tempApp = [];
            var tempAgr = [];
            appLine["app"] = tempApp;
            appLine["agr"] = tempAgr;
            html = drawAppLine(appLine);
            $("#divAppLine").empty().append(html);
        },
        complete: function() {
            if(docTypeVal == "S") {
                $("#sendType").val("PJM");

                sendTypeChange();
    
                //발신분류 변경
                $("#sendType").on("change", sendTypeChange);
            } else {
                $("#sendVal").prop("readonly", false);
                $("#docScope").val("PROJECT");
                docScopeChange();
            }

            $("#appDocContents input").on("blur", function() {
                $("#btnPush").trigger("click");
            });
        }
    });

    $("#modalEditOL").modal("show");
}

//삭제 버튼
function onBtnDelClick() {
    $("#modalConfirmDel").modal("show");
}

// 목록 내보내기
function onBtnDownloadExcelClick() {
    $("#mainForm").attr("action","/gw/ol/ol0000011_download_excel.php");
    $("#mainForm").submit();
    $("#mainForm").attr("action","/gw/ol/ol0000011.php");
}

</script>
<form id="mainForm" name="mainForm" method="post" enctype="multipart/form-data" action="/gw/ol/ol0000011.php">
<div class="btnList">
    <div>
        <button type="button" class="btn btn-primary" data-toggle="modal" id="btnAdd" name="btnAdd">작성</button>
    </div>
    <!-- <div>
        <button type="button" class="btn btn-primary ml-2" data-toggle="modal" id="btnChkDel" name="btnChkDel" disabled>삭제</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#modalMoveCopyAddr">이동/복사</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" id="btnFormDownload" name="btnFormDownload">양식 다운로드</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" data-toggle="modal" id="btnAddrIn" name="btnAddrIn">주소록 가져오기</button>
    </div> -->
    <div>
        <button type="button" class="btn btn-primary ml-2" id="btnDownList" name="btnDownList" onclick="onBtnDownloadExcelClick()">목록 내보내기</button>
    </div>
</div>

<div id="divSearch">
<div class="row">
    <div class="col-lg-2 search-inline mb-2">
        <div class="input-group" id="filterReply">
            <div class="input-group-prepend">
                <label>회신여부</label>
            </div> 
            <select class="form-control" id="ddlReplyKind" name="ddlReplyKind">
                <option value="all">전체</option>
                <option value="O">O</option>
                <option value="X">X</option>
            </select>
        </div>
    </div>
    <div class="col-lg-6 search-inline mb-2">

    </div>
    <div class="col-lg-4 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend">
                <select class="form-control prependDdlSearch" id="ddlSearchKind" name="ddlSearchKind">
                    <option value="ALL">전체</option>
                    <option value="DOC_CD">문서번호</option>
                    <option value="PJT_NAME">본부명 / Proj.</option>
                    <option value="RECIPIENT">수신처</option>
                    <option value="REFERENCE">참조처</option>
                    <option value="TITLE">제목</option>
                    <option value="CONTENT">본문</option>
                    <option value="SENDER">발신명의</option>
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
    </div>
</div>
</div>

<div id="divContent" class="pt-3">
    <!-- Nav tabs -->
    <!-- <ul class="nav nav-tabs nav-pills" id="tabList">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#obgList" onclick="onChangeTab('S')">발신</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#obrList" onclick="onChangeTab('R')">접수</a>
        </li>
    </ul> -->

    <div id="resultMsg" class="alert alert-primary py-1 mb-2" style="display: none;"></div>
    
    <div id="obgList">
        <div class="table-responsive">
            <div class="tableFixHead">
                <table class="table" id="tblObgList">
                    <thead class="thead-light">
                        <tr>
                            <th class="nowrap5">순번</th>
                            <th class="nowrap10">문서번호 / 시행일</th>
                            <!-- <th class="nowrap10">시행일</th> -->
                            <th class="nowrap10">제목</th>
                            <th class="nowrap5">첨부파일</th>
                            <th class="nowrap10">수신처 / 참조처</th>
                            <!-- <th class="nowrap10">참조처</th> -->
                            <th class="nowrap10">발신명의 / 분류</th>
                            <!-- <th class="nowrap10">분류</th> -->
                            <th class="nowrap10">본부명, PJT / 작성</th>
                            <!-- <th class="nowrap10">작성자</th> -->
                            <th class="nowrap10">심의 / 승인</th>
                            <th class="nowrap10">열람등급 / 수신참조</th>
                            <th class="nowrap10">결재방법 / 발신방법</th>
                            <!-- <th class="nowrap10">결재근거</th> -->
                            <th class="nowrap10">최종본 업로드 / 보존기간</th>
                            <!-- <th class="nowrap10">결재근거</th> -->
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="obrList">
        <div class="table-responsive">
            <div class="tableFixHead">
                <table class="table" id="tblObrList">
                    <thead class="thead-light">
                        <tr>
                            <th class="nowrap5">순번</th>
                            <th class="nowrap10">접수번호(자동) / 접수일자</th>
                            <th class="nowrap10">발신측 문서번호 / 발신명의</th>
                            <!-- <th class="nowrap10">발신명의(기관)</th> -->
                            <th class="nowrap10">제목</th>
                            <th class="nowrap5">첨부파일</th>
                            <th class="nowrap10">수신명의(당사) / 참조(당사)</th>
                            <!-- <th class="nowrap10">참조</th> -->
                            <th class="nowrap10">분류 / 수신매체</th>
                            <!-- <th class="nowrap10">수신매체</th> -->
                            <!-- <th class="nowrap10">접수일자</th> -->
                            <th class="nowrap10">소속 / 접수자</th>
                            <th class="nowrap10">심의 / 승인</th>
                            <th class="nowrap10">보존기간 / 결재방법</th>
                            <!-- <th class="nowrap10">결재방법</th> -->
                            <th class="nowrap10">열람등급 / 수신참조</th>
                            <th class="nowrap10">회신여부</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="alert alert-warning" id="noData" style="display:none">
        <strong>조건에 맞는 결과가 없습니다.</strong>
    </div>
</div>

<ul class="pagination justify-content-center" id="pageList" name="pageList">
</ul>

<?php 
require_once '../so/so600200_view.php';
require_once '../cm/cm_select_appline_view.php'; 
require_once '../cm/cm_select_dept_user_view.php';
require_once '../ea2/ea_appdoc_relay_list_view.php';
require_once 'ol0000011_detail_view.php';
require_once 'ol0000011_edit_view.php';
?>

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="pageNo" name="pageNo" value="1" />
<input type="hidden" id="recipientIds" name="recipientIds" />
<input type="hidden" id="isAgr" name="isAgr" value="N" />
<input type="hidden" id="ono" name="ono" />
<input type="hidden" id="docType" name="docType" />
<input type="hidden" id="dbMode" name="dbMode" />
</form>