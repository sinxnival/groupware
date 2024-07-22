<style>
@media (min-width: 576px) {
    #selProcess {
        flex: 0 1 auto !important;
    }

    #selDateType {
        margin-right: 0.5rem !important;
    }
}
</style>
<script>
$(document).ready(function() {

    $("#mode").val("INIT");

    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/wo0000012.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            var searchFrom = result["searchFrom"];
            var searchTo = result["searchTo"];

            $("#searchFrom").val(searchFrom);
            $("#searchTo").val(searchTo);
        },
        complete: function() {
            onConditionChange();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });


    $("#modalShowPledge").on('hide.bs.modal', function () {
        onConditionChange();
    });

    $("#txtSearchValue").on("keyup", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키
        if (cd == 13) {
            onBtnSearchSignClick();
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    var thSignUser = $('#tblSignUser').find('thead th');
    $('#tblSignUser').closest('div.tableFixHead-modal').on('scroll', function() {
        thSignUser.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    var thUnSignUser = $('#tblUnSignUser').find('thead th');
    $('#tblUnSignUser').closest('div.tableFixHead-modal-modal').on('scroll', function() {
        thUnSignUser.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    // 검색 버튼
    $("#btnSearchSign").on("click", onBtnSearchSignClick);
    $("#btnSearchDate").on("click", onBtnSearchSignClick);

    // 엑셀 내보내기
    $("#btnExportExcel").on('click', onBtnExportExcelClick);
});

// 리스트
function onConditionChange() {
    elem = $("#txtSearchValue");
    elem.val(elem.val().trim());
    elem.data('oldVal', elem.val());

    $("#mode").val("LIST");

    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/wo0000012.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            //현재 페이지
            $("#pageNo").val(result["pageNo"]);
            //페이지 목록
            $("#pageList").empty().append(result["pageList"]);

            var infoList = result["infoList"];

            var html = '';
            $(infoList).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="col-md-1 col-1">';
                html += '<div class="h-100 d-flex align-items-center">';
                html += info["no"];
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md col">';
                html += '<div class="h-100 d-flex align-items-center notAlign">';
                html += `<a href="javascript:void(0);" onclick="showSignSituation(${info["sno"]}, '${info["sTitle"]}')">`;
                html += info["sTitle"];
                html += '</a>';
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-1 d-none d-md-block">'
                html += '<div class="h-100 d-flex align-items-center">';
                html += info["fromDate"]
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-1 d-none d-md-block">'
                html += '<div class="h-100 d-flex align-items-center">';
                html += info["toDate"]
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-1 d-none d-md-block">'
                html += '<div class="h-100 d-flex align-items-center">';
                html += info["deptNm"]
                html += '</div>';
                html += '</td>';
                html += `<td class="col-md-1 col-2 col-w-btn"><button type="button" class="btn btn-primary" onclick="showSignSituation(${info["sno"]}, '${info["sTitle"]}')">상세</button></td>`;
                html += '</tr>';
            });

            $("#tblSignList tbody").empty().append(html);
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    })
}

// 검색 버튼
function onBtnSearchSignClick() {
    var elem = $("#txtSearchValue");
    elem.val(elem.val().trim());
    if ($("#ddlSearchKind").data("oldVal") != $("#ddlSearchKind").val() || elem.data("oldVal") != elem.val()) {

        onConditionChange();
    }
}

// 서약 현황
function showSignSituation(sno, sTitle) {
    $("#sno").val(sno);

    $("#mode").val("USER_LIST");

    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/wo0000012.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }
            var signUserList = result["signUserList"];
            var unSignUserList = result["unSignUserList"];
            var signCnt = result["signCnt"];
            var unSignCnt = result["unSignCnt"];

            var html = '';
            $(signUserList).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="col-md-5 notAlign d-none d-md-block">' + info["deptNm2"] + '</td>';
                html += '<td class="col-md-2 col-4">' + info["gradeNm"] + '</td>';
                html += '<td class="col-md-3 col-4">';
                html += `<a href="javascript:void(0);" onclick="showPldegePdf(${info["sno"]}, ${info["uno"]}, '${sTitle}', false)">`;
                html += info["userNm"];
                html += '</a>';
                html += '</td>';
                html += '<td class="col-md-2 col-4">' + info["signDate"] + '</td>';
                html += '</tr>';
            });
            $("#tblSignUser tbody").empty().append(html);

            $("#spanCntSign").text(signCnt);
            $("#spanCntUnSign").text(unSignCnt);

            html = '';
            $(unSignUserList).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="col-md-5 d-none d-md-block notAlign">'+ info["deptNm2"] +'</td>';
                html += '<td class="col-md-3 col-6">' + info["gradeNm"] + '</td>';
                html += '<td class="col-md-4 col-6">'+ info["userNm"] +'</td>';
                html += '</tr>';
            });
            $("#tblUnSignUser tbody").empty().append(html);
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    })

    $("#modalSignSituation").modal('show');
}

// 목록 내보내기
function onBtnExportExcelClick() {
    var sno = $("#sno").val();

    window.location.href = '/gw/wo/wo0000012_excel_download.php?sno=' + sno;
}
</script>
<form id="mainForm" name="mainForm" method="post">
    <div id="divSearch">
        <div class="row">
            <div class="col-lg-3 mb-2">
                <div class="search-inline">
                    <label>진행여부</label>
                    <select class="form-control" id="selProcess" name="selProcess" onchange="onConditionChange()">
                        <option value="2">전체</option>
                        <option value="0">진행중</option>
                        <option value="1">종료</option>
                    </select>
                </div>
            </div>
            <div class="search-inline mb-2 col-lg-5 d-flex justify-content-center">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <select class="form-control prependDdlSearch" id="selDateType" name="selDateType">
                            <option value="1">시작일</option>
                            <option value="2">마감일</option>
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
                </div>
            </div>
            <div class="col-lg-4 search-inline mb-2">
                <div class="input-group">
                    <input type="search" class="form-control" id="txtSearchValue" name="txtSearchValue" maxlength="50" autocomplete="false">
                    <div class="input-group-append">
                        <button type="button" id="btnSearchSign" name="btnSearchSign" class="btn btn-info">
                            <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                            <span class="fas fa-magnifying-glass"></span>
                        </button>
                    </div>
                </div>
                <!-- 자동완성 방지 -->
                <input type="text" style="width:0rem; height:0rem; border: 0;" aria-hidden="true">
                <input type="hidden" id="ddlSearchKind" name="ddlSearchKind" value="all">
            </div>
        </div>
    </div>
<div class="tableFixHead">
    <table class="table" id="tblSignList" style="table-layout: fixed;">
        <thead class="thead-light">
            <tr class="row">
                <th class="col-md-1 col-1">No</th>
                <th class="col-md col">제목</th>
                <th class="col-md-1 d-none d-md-block">시작일</th>
                <th class="col-md-1 d-none d-md-block">마감일</th>
                <th class="col-md-1 d-none d-md-block">등록부서</th>
                <th class="col-md-1 col-2 col-w-btn">상세</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<div class="modal fade" id="modalSignSituation" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">서약 현황</h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>서명 리스트 (<span id="spanCntSign"></span>명)</h6>
                        <div class="tableFixHead-modal">
                            <table class="table table-bordered table-sm" id="tblSignUser">
                                <thead class="thead-light">
                                    <tr class="row">
                                        <th class="col-md-5 d-none d-md-block">부서</th>
                                        <th class="col-md-2 col-4">직급</th>
                                        <th class="col-md-3 col-4">이름</th>
                                        <th class="col-md-2 col-4">서명일자</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>미서명 리스트 (<span id="spanCntUnSign"></span>명)</h6>
                        <div class="tableFixHead-modal-modal">
                            <table class="table table-bordered table-sm" id="tblUnSignUser">
                                <thead class="thead-light">
                                    <tr class="row">
                                        <th class="col-md-5 d-none d-md-block">부서</th>
                                        <th class="col-md-3 col-6">직급</th>
                                        <th class="col-md-4 col-6">이름</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-primary" id="btnExportExcel">목록 내보내기</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<ul class="pagination justify-content-center" id="pageList">
</ul>
<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="pageNo" name="pageNo" value="1" />
<input type="hidden" id="sno" name="sno" />
</form>
