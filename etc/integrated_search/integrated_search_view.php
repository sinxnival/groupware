<script type="text/javascript" src="/gw/js/ea.js"></script>
<script type="text/javascript" src="/gw/js/nb.js"></script>
<script>
$(document).ready(function() {
    //작업모드
    $("#mode").val("INIT");
    $("#menuId").val('<?php echo $_POST["menuId"]; ?>');

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp0100600.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //권한 설정
            if((result["msg"])) {
                var html = "";
                $("#mainForm").empty();
                html = "<h2>" + result["msg"] + "</h2>";
                $("#mainForm").append(html);
            } else {
                //검색
                var html = "";
                $(result["searchCondition"]).each(function(i, info) {
                    html += '<option value="' + info["key"] + '">' + info["val"] + '</option>';
                });
                $("#ddlSearchKind").append(html);
                onBtnSearchClick();
            }
        }
    })

    //검색 버튼
    $("#btnSearch").on("click", onBtnSearchClick);

    $("#txtSearchValue").keydown(function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            onBtnSearchClick();
        };
    });

});

function onBtnSearchClick() { 
    if ($("#ddlSearchKind").data("oldVal") != $("#ddlSearchKind").val() || $("#txtSearchValue").data("oldVal") != $("#txtSearchValue").val()) {
        var elem = $("#ddlSearchKind");
        elem.data('oldVal', elem.val());

        elem = $("#txtSearchValue");
        elem.val(elem.val().trim());
        elem.data('oldVal', elem.val());

        //작업모드
        $("#mode").val("LIST");
        $("#menuId").val('<?php echo $_POST["menuId"]; ?>');
        // 페이지 초기화
        $("#pageNoDoc").val("1");
        $("#pageNoNotice").val("1");
        $("#pageNoBoard").val("1");
        $("#pageNoAppDoc").val("1");
        // 문서관리
        $("#componentName").val("");
        var timer;
        $.ajax({
            type: "POST",
            url: "/gw/mp/mp0100600.php",
            data: $("#mainForm").serialize(),
            dataType: "json",
            start_time: new Date().getTime(),
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                showInfoListDoc(result["infoListDoc"]);
                showInfoListNotice(result["infoListNotice"]);
                showInfoListBoard(result["infoListBoard"]);
                showInfoListAppDoc(result["infoListAppDoc"]);
                //현재 페이지
                $("#pageNoDoc").val(result["pageNoDoc"]);
                $("#pageNoNotice").val(result["pageNoNotice"]);
                $("#pageNoBoard").val(result["pageNoBoard"]);
                $("#pageNoAppDoc").val(result["pageNoAppDoc"]);
                //페이지 목록
                $("#pageListDoc").empty().append(result["pageListDoc"]);
                $("#pageListNotice").empty().append(result["pageListNotice"]);
                $("#pageListBoard").empty().append(result["pageListBoard"]);
                $("#pageListAppDoc").empty().append(result["pageListAppDoc"]);
            },
            beforeSend:function(){
                $("#divSearch").find("input").prop("readonly", true);
                $("#divSearch option").not(":selected").prop("disabled", true);
                $("#divSearch").find("button").prop("disabled", true);
                $("#btnSearch").find("span.spinner-border").show();
                timer = setTimeout(function() {
                    $("#modalLoading").modal("show");
                }, 1000);
            },
            complete: function() {
                $("#divSearch").find("input").prop("readonly", false);
                $("#divSearch option").not(":selected").prop("disabled", false);
                $("#divSearch").find("button").prop("disabled", false);
                $("#divSearch").find("span.spinner-border").hide();
                if ((new Date().getTime() - this.start_time) < 1000) {
                    clearTimeout(timer);
                }
                setTimeout(function () {
                    if ($("#modalLoading").hasClass('show')) {
                        $("#modalLoading").modal("hide");
                    }
                }, 500);
            },
            error: function(request, status, error) {
                alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
            }
        });
    }
}

// 문서관리
function showInfoListDoc(list) {
    $("#tblDoc tbody").empty();
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr class="row">';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["menuNm"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["docCd"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-4 col-6 notAlign text-ellipsis">';
        html += info["subject"];
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["frDt10"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["userNm"];
        html += '</div>';
        html += '</td>';
    });
    $("#tblDoc tbody").append(html);
}

// 공지사항
function showInfoListNotice(list) {
    $("#tblNotice tbody").empty();
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr class="row">';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["menuNm"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["noticeId"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-4 col-6 notAlign text-ellipsis">';
        html += '<a href="javascript:void(0);" onclick="onBtnDetailNoticeClick(' + info["mId"] + ',' + info["menuId"] + ')">';
        html += info["subject"];
        html += '</a>';
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["regDt10"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        if(info["user_type"] == '') {
            html += '<i>익명</i>';
        }
        else {
            html += info["user_type"];
        }
        html += '</div>';
        html += '</td>';
    });
    $("#tblNotice tbody").append(html);
}

// 게시함
function showInfoListBoard(list) {
    $("#tblBoard tbody").empty();
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr class="row">';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["menuNm"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["vId"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-4 col-6 notAlign text-ellipsis">';
        html += '<a href="javascript:void(0);" onclick="onBtnDetailBoardClick(' + info["mId"] + ',' + info["menuId"] + ')">';
        html += info["subject"];
        html += '</a>';
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["regDt10"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["user_type"];     
        html += '</div>';
        html += '</td>';
    });
    $("#tblBoard tbody").append(html);
}

function showInfoListAppDoc(list) {
    // 전자결재
    $("#tblAppDoc tbody").empty();
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr class="row">';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["selOt"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["docCd"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-4 col-6 notAlign text-ellipsis">';
        html += '<a href="javascript:void(0);" onclick="showEaAppDocDetail(' + info["docId"] + ',' + info["formId"] + ')">';
        html += info["subject"];
        html += '</a>';
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["frDt10"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["userNm"];
        html += '</div>';
        html += '</td>';
    });
    $("#tblAppDoc tbody").append(html);
}
</script>
<form id="mainForm" name="mainForm" method="post" action="/gw/mp/mp0100600.php">
<div id="divSearch">
<div class="row">
    <div class="col">
        <div class="input-group">
            <div class="input-group-prepend">
                <select class="form-control prependDdlSearch" id="ddlSearchKind" name="ddlSearchKind">
                </select>
            </div>
            <input type="search" class="form-control" id="txtSearchValue" name="txtSearchValue" maxlength="50"/>
            <!-- <input type="text" class="form-control" style="display:none" /> -->
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
<br />
<h5>공지사항</h5>
<table class="table" id="tblNotice">
    <thead class="alignCenter">
        <tr class="row">
            <th class="col-md-2 d-none d-md-block">폴더명</th>
            <th class="col-md-2 d-none d-md-block">No.</th>
            <th class="col-md-4 col-6">제목</th>
            <th class="col-md-2 col-3">등록일자</th>
            <th class="col-md-2 col-3">등록자</th>
        </tr>
    </thead>
    <tbody class="alignCenter">
    </tbody>
</table>
<ul class="pagination justify-content-center" id="pageListNotice">
</ul>
<br />
<h5>게시함</h5>
<table class="table" id="tblBoard">
    <thead class="alignCenter">
        <tr class="row">
            <th class="col-md-2 d-none d-md-block">폴더명</th>
            <th class="col-md-2 d-none d-md-block">No.</th>
            <th class="col-md-4 col-6">제목</th>
            <th class="col-md-2 col-3">등록일자</th>
            <th class="col-md-2 col-3">등록자</th>
        </tr>
    </thead>
    <tbody class="alignCenter">
    </tbody>
</table>
<ul class="pagination justify-content-center" id="pageListBoard">
</ul>
<br />
<h5>전자결재</h5>
<table class="table" id="tblAppDoc" name="tblAppDoc">
    <thead class="alignCenter">
        <tr class="row">
            <th class="col-md-2 d-none d-md-block">결재함명</th>
            <th class="col-md-2 d-none d-md-block">품의번호</th>
            <th class="col-md-4 col-6">제목</th>
            <th class="col-md-2 col-3">기안일자</th>
            <th class="col-md-2 col-3">기안자</th>
        </tr>
    </thead>
    <tbody class="alignCenter">
    </tbody>
</table>
<ul class="pagination justify-content-center" id="pageListAppDoc">
</ul>
<!-- <br />
<h5>문서관리</h5>
<table class="table" id="tblDoc" name="tblDoc">
    <thead class="alignCenter">
        <tr class="row">
            <th class="col-md-2 d-none d-md-block">문서함명</th>
            <th class="col-md-2 d-none d-md-block">문서번호</th>
            <th class="col-md-4 col-6">문서명</th>
            <th class="col-md-2 col-3">등록일자</th>
            <th class="col-md-2 col-3">등록자</th>
        </tr>
    </thead>
    <tbody class="alignCenter">
    </tbody>
</table>
<ul class="pagination justify-content-center" id="pageListDoc">
</ul> -->

<?php 
//공지
require_once '../nb/nb0100100_detail_view.php';
require_once '../nb/nb_read_list_view.php';
//게시
require_once '../nb/nb0200100_detail_view.php';
require_once '../nb/nb_read_list_view.php';
//전자결재
require_once '../ea2/ea_appdoc_detail_view.php';
require_once '../ea2/ea_appdoc_sign_info_view.php';
?>

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="menuId" name="menuId" />
<input type="hidden" id="pageNoDoc" name="pageNoDoc" value="1" />
<input type="hidden" id="pageNoNotice" name="pageNoNotice" value="1" />
<input type="hidden" id="pageNoBoard" name="pageNoBoard" value="1" />
<input type="hidden" id="pageNoAppDoc" name="pageNoAppDoc" value="1" />
<input type="hidden" id="componentName" name="componentName" />
</form>
