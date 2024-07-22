<style>
@media (min-width: 576px) {
    #selIsSign, #selSignYear {
        flex: 0 1 auto !important;
    }
}
</style>
<script>
$(document).ready(function() {
    $("#mode").val("INIT");

    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/wo0000011.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            var yearList = result["yearList"];

            var html = '';
            $(yearList).each(function(i, date) {
                html += '<option value="'+ date +'">'+ date +'</option>';
            });
            $("#selSignYear").append(html);
        },
        complete: function() {
            onConditionChange();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    })

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
    //검색 버튼
    $("#btnSearchSign").on("click", onBtnSearchSignClick);
});

// 리스트
function onConditionChange() {
    elem = $("#txtSearchValue");
    elem.val(elem.val().trim());
    elem.data('oldVal', elem.val());

    $("#mode").val("LIST");

    $.ajax({ 
        type: "POST", 
        url: "/gw/wo/wo0000011.php",
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

            var uno = result["uno"];

            var infoList = result["infoList"];

            var html = '';
            $(infoList).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="col-md-1 col-1">';
                html += '<div class="h-100 d-flex align-items-center">';
                html += info["no"]
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md col">';
                html += '<div class="h-100 d-flex align-items-center notAlign">';
                if(info["isSign"] == 0) {
                    html += `<a href="javascript:void(0);" onclick="showSignDetail(${info["sno"]}, '${info["filePath"]}', '${info["sTitle"]}', '${info["kindCd"]}')">`;
                } else {
                    html += `<a href="javascript:void(0);" onclick="showPldegePdf(${info["sno"]}, ${uno}, '${info["sTitle"]}', false)">`;
                }
                html += info["sTitle"];
                html += '</a>';
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-2 d-none d-md-block">';
                html += '<div class="h-100 d-flex align-items-center">';
                if(info["isSign"] == 0) {
                    html += '미완료';
                } else {
                    html += '완료';
                }
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-2 d-none d-md-block">'
                html += '<div class="h-100 d-flex align-items-center">';
                html += info["signDate"];
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-1 col-2 col-w-btn">';
                if(info["isSign"] == 0) {
                    html += `<button type="button" class="btn btn-primary" onclick="showSignDetail(${info["sno"]}, '${info["filePath"]}', '${info["sTitle"]}', '${info["kindCd"]}')">상세</button></td>`;
                } else {
                    html += `<button type="button" class="btn btn-primary" onclick="showPldegePdf(${info["sno"]}, ${uno}, '${info["sTitle"]}', false)">상세</button></td>`;
                }
                html += '</tr>';
            });

            $("#tblMySignList tbody").empty().append(html);
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
</script>
<form id="mainForm" name="mainForm" method="post">
    <div id="divSearch">
        <div class="row">
            <div class="col-lg-4 mb-2">
                <div class="search-inline">
                    <label>서명여부</label>
                    <select class="form-control" id="selIsSign" name="selIsSign" onchange="onConditionChange()">
                        <option value="2">전체</option>
                        <option value="0">미완료</option>
                        <option value="1">완료</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-4 mb-2">
                <div class="search-inline">
                    <label>서명년도</label>
                    <select class="form-control" id="selSignYear" name="selSignYear" onchange="onConditionChange()">
                        <option value="0">전체</option>
                    </select>
                </div>
            </div>
            <!-- <div class="search-inline mb-2 col-lg-5">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <select class="form-control prependDdlSearch" id="ddlSearchDate" name="ddlSearchDate">
                            <option value="1">기안일</option>
                            <option value="3">최종결재일</option>
                        </select>
                    </div>
                    <input type="date" class="form-control mr-2" id="searchFrom" name="searchFrom" min="1920-01-01" max="2026-12-31">
                    -
                    <input type="date" class="form-control ml-2" id="searchTo" name="searchTo" min="1920-01-01" max="2026-12-31">
                    <div class="input-group-append">
                        <button type="button" id="btnSearchDate" name="btnSearchDate" class="btn btn-info">
                            <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                            <span class="fas fa-magnifying-glass"></span>
                        </button>
                    </div>
                </div>
            </div> -->
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
    <table class="table" id="tblMySignList" style="table-layout: fixed;">
        <thead class="thead-light">
            <tr class="row">
                <th class="col-md-1 col-1">No</th>
                <th class="col-md col">제목</th>
                <th class="col-md-2 d-none d-md-block">서명여부</th>
                <th class="col-md-2 d-none d-md-block">서명일자</th>
                <th class="col-md-1 col-2 col-w-btn">상세</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<ul class="pagination justify-content-center" id="pageList">
</ul>
<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="pageNo" name="pageNo" value="1" />
</form>
