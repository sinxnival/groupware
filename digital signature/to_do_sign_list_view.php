<script>
$(document).ready(function() {
    //날짜 min, max값 넣기
    dateMinMaxAppend();

    // 리스트 가져오기
    showSignList();

    var userNm = '<?php echo $_SESSION["user"]["user_name"]?>';
    $("#userNm").text(userNm);

    // 동의 버튼
    $("#isAgree").on('change', function() {
        var isAgree = $("#isAgree").prop("checked");

        if(isAgree) {
            $("#btnSubmit").prop("disabled", false);
            $(".chkFill").text("■");
        } else {
            $("#btnSubmit").prop("disabled", true);
            $(".chkFill").text("□");
        }
    });
    // 제출하기 버튼
    $("#btnSubmit").on('click', function() {
        $("#modalConfirm").modal('show');
    });

    // 승인 닫았을 때
    $("#modalShowPledge").on('hide.bs.modal', function () {
        if($("#isList").val() == "true") {
            showSignList();
        }
    });

    // 상세 닫았을 때
    $("#modalSignDetail").on('hide.bs.modal', function () {
        $("#isAgree").prop("checked", false);
        $("#btnSubmit").prop("disabled", true);
    });

    // 삭제
    $("#btnDelPdf").on('click', function() {
        $("#modalDelConfirm").modal("show");
    });

    $("#lawBirthDt").on('change', function() {
        if($(this).val()) {
            $("#btnReSubmit").prop("disabled", false);
        } else {
            $("#btnReSubmit").prop("disabled", true);
        }
    });
    
    // 다운로드
    $("#btnSignPdfDownload").on('click', onBtnSignPdfDownload);
    // 삭제(예) 버튼
    $("#btnDelYes").on('click', onBtnDelPdfClick);
    // 승인(예) 버튼
    $("#btnSignYes").on('click', onBtnSubmitClick);
});

function showSignList() {
    $("#mode").val("LIST");

    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/to_do_sign_list.php",
        data: $("#signForm").serialize(),
        dataType: "json", 
        success: function(result) {
            var uno = result["uno"];
            $("#loginUno").val(uno);

            var toDoSignList = result["toDoSignList"];

            // 메인화면에서만 보이기
            var currentURL = window.location.href;
            var isMain = currentURL.replace(/^https?:\/\/[^\/]+(\/)?(gw\/)?(index.php)?/, '');
            if(toDoSignList.length > 0 && !isMain) {
                $("#modalSignList").modal('show');

                var html = '';
                $(toDoSignList).each(function(i, info) {
                    html += '<tr class="row">';
                    html += '<td class="col-md-1 col-1">';
                    html += '<div class="h-100 d-flex align-items-center">';
                    html += info["no"];
                    html += '</div>';
                    html += '</td>';
                    html += '<td class="col">';
                    html += '<div class="h-100 d-flex align-items-center notAlign">';
                    html += `<a href="javascript:void(0);" onclick="showSignDetail(${info["sno"]}, '${info["filePath"]}', '${info["sTitle"]}', '${info["kindCd"]}')">`;
                    html += info["sTitle"];
                    html += '</a>';
                    html += '</div>';
                    html += '</td>';
                    html += '<td class="col-md-1 d-none d-md-block">';
                    html += '<div class="h-100 d-flex align-items-center">';
                    html += '미완료'
                    html += '</div>';
                    html += '</td>';
                    html += `<td class="col-md-1 col-2 col-w-btn"><button type="button" class="btn btn-primary" onclick="showSignDetail(${info["sno"]}, '${info["filePath"]}', '${info["sTitle"]}', '${info["kindCd"]}')">상세</button></td>`;
                    html += '</tr>';
                });
    
                $("#tblSignToDoList tbody").empty().append(html);
            } else {
                $("#modalSignList").modal('hide');
            }

        },
        error: function(request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

function showSignDetail(sno, filePath, sTitle, kindCd) {
    $("#imgPledge").attr("src", filePath);

    $("#sno").val(sno);
    $("#filePath").val(filePath);
    $("#sTitle").val(sTitle);
    $("#kindCd").val(kindCd);

    var note = '';
    // 윤리경영 실천 서약서, 부패 방지 법규 준수 서약서일 경우
    if(kindCd == "01" || kindCd == "02") {
        note = `<div class="alert alert-secondary mt-3">
                    <strong>
                        <span class="chkFill">□</span> 본인은 회사의 윤리경영 규정과 상기 서약서를 읽고 숙지합니다. <br/>
                        <span class="chkFill">□</span> 본인은 서약서의 개인정보를 회사가 수집 및 이용하는 것에 동의합니다. <br/>
                        <span class="chkFill">□</span> 본 서약서와 윤리경영 규정 상의 내용 및 관련 법규를 위반할 경우 민·형사상 책임이 따른다는 것을 인식합니다. <br/>
                        <span class="chkFill">□</span> 본 서약서는 회사의 전산화된 방법으로 본인이 직접 자발적으로 작성하고 제출합니다.
                    </strong>
                </div>`;
        $("#note").html(note);
    }

    $("#modalSignDetail .modal-title").text(sTitle);
    $("#modalSignDetail").modal("show");
}

// 서명하기
function onBtnSubmitClick() { 
    // 서명 시점
    var now = new Date();

    var todayYear = now.getFullYear();
    var todayMonth = String(now.getMonth() + 1);
    todayMonth = todayMonth.padStart(2, "0");
    var todayDay = String(now.getDate());
    todayDay = todayDay.padStart(2, "0");
    var todayHour = String(now.getHours());
    todayHour = todayHour.padStart(2, "0");
    var todayMin = String(now.getMinutes());
    todayMin = todayMin.padStart(2, "0");

    var signDate = `${todayYear}${todayMonth}${todayDay}`;
    var signTime = `${todayHour}:${todayMin}`;
    $("#signDate").text(signDate);
    $("#signTime").text(signTime);

    $("#signData").show();
    $(".spinner-border").show();
    html2canvas(document.getElementById('signData'), { backgroundColor: 'rgba(0, 0, 0, 0)' }).then(function(canvas) {
        $("#signData").hide();
        var imageDataURL = canvas.toDataURL("image/png");

        $("#mode").val("SIGN");
        $.ajax({
            type: 'POST',
            url: '/gw/wo/to_do_sign_list.php',
            data: { 
                mode: $("#mode").val(),
                sno : $("#sno").val(),
                filePath : $("#filePath").val(),
                imageDataURL : imageDataURL,
                sTitle : $("#sTitle").val(),
                kindCd : $("#kindCd").val(),
                lawBirthDt : $("#lawBirthDt").val()
            },
            success: function(response) {
                var isBirthday = response;
                if(isBirthday == "false") {
                    $("#modalAddLawBirthDt").modal('show');
                } else {
                    $("#modalSignDetail").modal("hide");
                    $("#modalAddLawBirthDt").modal('hide');
                    var sno = $("#sno").val();
                    var sTitle = $("#sTitle").val();
                    var uno = $("#loginUno").val();

                    // 생일 입력칸 초기화
                    $("#lawBirthDt").val('');
                    
                    showPldegePdf(sno, uno, sTitle, true);
                }
                $(".spinner-border").hide();
            }
        });
    });
}

// 완성된 pdf 불러오기
function showPldegePdf(sno, uno, sTitle, isList) {
    $("#mode").val("GET_PATH");

    $("#sno").val(sno);
    $("#delUno").val(uno);
    
    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/to_do_sign_list.php",
        data: {
            mode: $("#mode").val(),
            sno : sno,
            uno : uno
        },
        dataType: "json", 
        success: function(result) {
            var filePath = result["filePath"];
            var mngDeptId = result["mngDeptId"];
            var teamId = result["teamId"];

            if(mngDeptId == teamId) {
                $("#btnDelPdf").show();
            } else {
                $("#btnDelPdf").hide();
            }

            $("#downPath").val(filePath);
            filePath = $.trim(filePath);
            filePath = encodeURIComponent(filePath);
            $("#pledgePdf").load("/gw/pdfjs/web/pdfSmViewer.php?filePath=" + filePath, function(response, status, xhr) {
                if (status == "success") {
                    $(".spinner-border").hide();
                    $("#modalShowPledge .modal-title").text(sTitle);
                    $("#modalShowPledge").modal("show");

                    $("#isList").val(isList);
                }
            });
        }
    })
}

// 다운로드
function onBtnSignPdfDownload() {
    var filePath = $("#downPath").val();
    filePath = $.trim(filePath);
    filePath = encodeURIComponent(filePath);
    window.location.href = '/gw/wo/sign_pdf_download.php?filePath=' + filePath;
}

// 삭제
function onBtnDelPdfClick() {
    $("#mode").val("DEL");

    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/to_do_sign_list.php",
        data: {
            mode: $("#mode").val(),
            sno : $("#sno").val(),
            delUno : $("#delUno").val()
        },
        dataType: "json", 
        success: function(result) {
            var proceed = result["proceed"];

            if(proceed) {
                var sno = $("#sno").val();
                var sTitle = $("#sTitle").val();

                $("#modalShowPledge").modal('hide');

                onConditionChange();
                showSignSituation(sno, sTitle);
            }
        }
    });
}
</script>
<form id="signForm" name="signForm">
    <div class="modal fade" id="modalSignList" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
    
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">서약서</h4>
                    <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
                </div>
    
                <!-- Modal body -->
                <div class="modal-body">
                    <div class="tableFixHead">
                        <table class="table table-hover" id="tblSignToDoList">
                            <thead class="thead-light">
                                <tr class="row">
                                    <th class="col-md-1 col-1">No.</th>
                                    <th class="col">제목</th>
                                    <th class="col-md-1 d-none d-md-block">서명여부</th>
                                    <th class="col-md-1 col-2 col-w-btn">상세</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
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
    <div class="modal fade" id="modalSignDetail" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                    <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <img id="imgPledge" width="100%" style="border: 1px solid #C0C0C0;"/>
                    <div id="note"></div>
                    <div class="d-flex justify-content-between">
                        <div class="form-check-inline" style="padding-left:1.25rem !important">
                            <label class="form-check-label">
                                <input type="checkbox" class="form-check-input" value="" id="isAgree">동의 합니다.
                            </label>
                        </div>
                        <button type="button" class="btn btn-primary mt-2" id="btnSubmit" disabled>제출하기 <div class="spinner-border text-light spinner-border-sm" style="display:none"></div></button>
                    </div>
                    <div id="signData" style="height: 6rem;width:5rem;background-color:white;display:none">
                        <div style="height: 8rem; width: 100%; position: relative;">
                            <div style="height: 8rem; width: 100%; position: absolute; text-align: center; z-index: 3; left: 0; top: 0.625rem"></div>
                            <div style="height: 8rem; width: 100%; position: absolute; text-align: center; z-index: 1; left: 0; top: 0.4rem">
                                <span class="fa-regular fa-circle" style="color:#ffb3b3;font-size:5rem !important;"></span>
                            </div>
                            <div style="height: 8rem; width: 100%; vertical-align: middle; position: absolute; text-align: center; z-index: 2; left: 0rem; top: 1.3rem; font-size: 0.5rem;" id="userNm"></div>
                            <div style="height: 8rem; width: 100%; vertical-align: middle; position: absolute; text-align: center; z-index: 2; left: 0rem; top: 2.8rem; font-size: 0.5rem;" id="signDate"></div>
                            <div style="height: 8rem; width: 100%; vertical-align: middle; position: absolute; text-align: center; z-index: 2; left: 0rem; top: 3.8rem; font-size: 0.5rem;" id="signTime"></div>
                        </div>
                    </div>
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
    <div class="modal fade" id="modalShowPledge" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                    <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <div id="pledgePdf" style="border: 1px solid #C0C0C0;"></div>
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-around">
                            <button type="button" class="btn btn-primary" id="btnSignPdfDownload" name="btnSignPdfDownload">다운로드</button>
                            <button type="button" class="btn btn-danger" id="btnDelPdf" name="btnDelPdf" style="display:none">삭제</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="modalConfirm" class="modal fade" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <!-- Modal body -->
                <div class="modal-body">
                    정말 제출하시겠습니까?
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                    <div class="container">
                        <div class="d-flex justify-content-around">
                            <button type="button" class="btn btn-primary" data-dismiss="modal" id="btnSignYes">네</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">아니오</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="modalDelConfirm" class="modal fade" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <!-- Modal body -->
                <div class="modal-body">
                    정말 삭제하시겠습니까?
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                    <div class="container">
                        <div class="d-flex justify-content-around">
                            <button type="button" class="btn btn-primary" data-dismiss="modal" id="btnDelYes">네</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">아니오</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="modalAddLawBirthDt" class="modal fade" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <div>법정 생년월일 정보가 없습니다.</div>
                </div>
                <!-- Modal body -->
                <div class="modal-body">
                    <div style="justify-content: center;display: grid">
                        <div class="mb-2">법정 생년월일을 입력하세요.</div>
                        <div><input type="date" class="form-control" id="lawBirthDt" name="lawBirthDt" /></div>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                    <div class="container">
                        <div class="d-flex justify-content-around">
                            <button type="button" id="btnReSubmit" class="btn btn-primary" data-dismiss="modal" onclick="onBtnSubmitClick()" disabled>제출하기</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="mode" name="mode" />
    <input type="hidden" id="sno" name="sno" />
    <input type="hidden" id="filePath" name="filePath" />
    <input type="hidden" id="sTitle" name="sTitle" />
    <input type="hidden" id="isList" name="isList" />
    <input type="hidden" id="downPath" name="downPath" />
    <input type="hidden" id="loginUno" name="loginUno" />
    <input type="hidden" id="delUno" name="delUno" />
    <input type="hidden" id="kindCd" name="kindCd" />
</form>
</body>
