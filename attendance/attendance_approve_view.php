<script type="text/javascript" src="/gw/js/ea.js"></script>
<style> 
.table td, .table th {
    vertical-align: middle !important;
}
.hoverColor:hover {
    background-color: #faebd7;
    cursor: pointer;
}
.selectColor {
    background-color: #faebd7;
}
</style>
<script>
$(document).ready(function(){
    // 오늘 날짜 삽입
    var now = new Date();
    var month = (now.getMonth() + 1);
    var day = now.getDate();
    if (month < 10) 
        month = "0" + month;
    if (day < 10) 
        day = "0" + day;
    var today = now.getFullYear() + '-' + month + '-' + day;
    $('#searchFrom').val(today);
    $("#searchFrom").attr('max', today);

    $("#mode").val("INIT");
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300100.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var attendList = result["attendList"];

            var html = '';
            $(attendList).each(function(i, info) {
                html += '<option value="'+ info["cdVal"] +'">'+ info["cdNm"] +'</option>';
            });

            $("#selAttend").empty().append(html);
            $("#selMdAttend").empty().append(html);
        },
        complete: function() {
            // 부서별 근태 리스트 가져오기
            getDeptAttendList();
        }
    });

    // 헤더 고정
    var thDeptAttendList = $('#tblDeptAttendList').find('thead th');
    $('#tblDeptAttendList').closest('div.tableFixHead').on('scroll', function() {
        thDeptAttendList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    var thUserAttendList = $('#tblUserAttendList').find('thead th');
    $('#tblUserAttendList').closest('div.tableFixHead').on('scroll', function() {
        thUserAttendList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    // 검색 버튼 클릭
    $("#btnSearchDate").on('click', getDeptAttendList);
    // 휴일조회구분 값 변경
    $("#holidayOption").on('change', getDeptAttendList);
    // 근태 변경
    $("#selAttend").on('change', changeSelAttend);
    // 승인 버튼
    $("#btnApp").on('click', btnAppClick);
    // 취소 버튼
    $("#btnCancle").on('click', btnCancleClick);
    // 일괄 변경 버튼
    $("#btnBatchAttend").on('click', btnBatchAttendClick);
});

// 부서별 근태 리스트 가져오기
function getDeptAttendList() {
    $("#mode").val("DEPT_LIST");

    $("#deptId").val(0);

    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300100.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var deptAttendList = result["deptAttendList"];
            var html = '';
            $(deptAttendList).each(function(i, info) {
                html += '<tr class="hoverColor" id="team_'+ info["deptId"] +'">';
                html += '<td class="text-left">';
                html += info["deptNm"]
                html += '</td>';
                html += '<td class="text-right">';
                html += info["total"]
                html += '</td>';
                html += '<td class="text-right">';
                html += info["aTotal"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["a05"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["a07"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["a06"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["a04"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["a08"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["a03"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["aTotal"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["b02"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["b10"]
                html += '</td>';
                html += '<td class="text-right m-none">';
                html += info["bTotal"]
                html += '</td>';
                html += '<td class="text-right">';
                html += info["nowUserCnt"]
                html += '</td>';
                html += '</tr>';
            });

            $("#tblDeptAttendList tbody").empty().append(html);
        },
        beforeSend:function() {
            $("#btnSearchDate").find("button").prop("disabled", true);
            $("#btnSearchDate").find("span.spinner-border").show();
            timer = setTimeout(function() {
                $("#modalLoading").modal("show");
            }, 500);
        },
        complete: function() {
            // 부서 선택
            $(".hoverColor").on('click', function() {
                var deptString = $(this).attr("id");
                var deptId = deptString.split("_");
                $(".hoverColor").removeClass("selectColor");
                $("#" + deptString).addClass("selectColor");
                
                $("#deptId").val(deptId[1]);

                // 해당 부서 사원 근태
                getUserAttendList();
            });
            // 유저별 근태 가져오기
            getUserAttendList();
            
            $("#team_0").addClass("selectColor");
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

// 유저별 근태 가져오기
function getUserAttendList() {
    $("#mode").val("USER_LIST");
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300100.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var userAttendList = result["userAttendList"];
            var html = '';
            $(userAttendList).each(function(i, info) {
                // 시간 형식
                var timeFr = info["timeFr"].replace(/(^\d{2})(\d{2}$)/gi, '$1:$2');
                var timeTo = info["timeTo"].replace(/(^\d{2})(\d{2}$)/gi, '$1:$2');

                html += '<tr>';
                html += '<td style="white-space: nowrap;min-width: 2rem">';
                html += '<input type="checkbox" name="chkUserList[]" onclick="whenChkClick_chkAll(\'chkUserList\', \'chkAllUser\')" value="' + info["userId"] + "|" + info["atId"] + '">';
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += info["divAttNm"]
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += info["divSubAttNm"]
                html += '</td>';
                html += '<td class="text-left" style="white-space: nowrap;min-width: 10rem">';
                html += info["deptNm"]
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += info["gradeNm"]
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += '<a href="javascript:void(0);" onclick="showEditAttend('+ info["userId"] + ',' + info["atId"] +')">';
                html += info["userNm"]
                html += '</a>';
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += timeFr
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += timeTo
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += '<a href="javascript:void(0);" onclick="showEaAppDocDetail(' + info["appNo"] + ',' + info["formId"] + ')">';
                html += info["appTitle"]
                html += '</a>';
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += info["divAppNm"]
                html += '</td>';
                html += '<td style="white-space: nowrap;min-width: 10rem">';
                html += info["content"]
                html += '</td>';
                html += '</tr>';
            });

            $("#tblUserAttendList tbody").empty().append(html);
        },
        complete:function() {
            $("#btnSearchDate").find("button").prop("disabled", false);
            $("#btnSearchDate").find("span.spinner-border").hide();
            if ((new Date().getTime() - this.start_time) < 1000) {
                clearTimeout(timer);
            }
            setTimeout(function () {
                if ($("#modalLoading").hasClass('show')) {
                    $("#modalLoading").modal("hide");
                }
            }, 1000);

            $("#chkAllUser").prop('checked', false);

            // 승인, 일괄변경 버튼 활성화
            $("input[type=checkbox]").on('click', function() {
                if($("input[name='chkUserList[]']:checked").length > 0) {
                    $("#btnApp").prop('disabled', false);
                    $("#btnCancle").prop('disabled', false);
                    $("#btnBatchAttend").prop('disabled', false);
                } else {
                    $("#btnApp").prop('disabled', true);
                    $("#btnCancle").prop('disabled', true);
                    $("#btnBatchAttend").prop('disabled', true);
                }
            });
        },
    });
}

// 근태 변경
function changeSelAttend() {
    $("#mode").val("ATTEND_CHANGE");
    $("#isModal").val(0);
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300100.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            var attendDetail = result["attendDetail"];

            if(attendDetail.length > 0) {
                $("#selAtDetail").show();

                var html = '';
                $(attendDetail).each(function(i, info) {
                    html += '<option value="'+ info["cdVal"] +'">'+ info["cdNm"] +'</option>';
                });

                $("#selAtDetail").empty().append(html);
            } else {
                $("#selAtDetail").hide();
            }
        }
    });
}

// 승인 버튼 클릭
function btnAppClick() {
    $("#mode").val("APPROVE");

    $.ajax({
        type: "POST",
        url: "/gw/bs/bs0300100.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            var proceed = result["proceed"];

            if(proceed == true) {
                $("#resultMsg").removeClass("alert-danger");
                $("#resultMsg").addClass("alert-primary");
            } else {
                $("#resultMsg").removeClass("alert-primary");
                $("#resultMsg").addClass("alert-danger");
            }

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(3000).fadeOut();

            getUserAttendList();
        }
    });
}

// 취소 버튼 클릭
function btnCancleClick() {
    $("#mode").val("CANCLE");

    $.ajax({
        type: "POST",
        url: "/gw/bs/bs0300100.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            var proceed = result["proceed"];

            if(proceed == true) {
                $("#resultMsg").removeClass("alert-danger");
                $("#resultMsg").addClass("alert-primary");
            } else {
                $("#resultMsg").removeClass("alert-primary");
                $("#resultMsg").addClass("alert-danger");
            }

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(3000).fadeOut();

            getUserAttendList();
        }
    });
}

// 일괄변경 버튼 클릭
function btnBatchAttendClick() {
    $("#mode").val("BATCH");

    $.ajax({
        type: "POST",
        url: "/gw/bs/bs0300100.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }
            var proceed = result["proceed"];

            if(proceed == true) {
                $("#resultMsg").removeClass("alert-danger");
                $("#resultMsg").addClass("alert-primary");
            } else {
                $("#resultMsg").removeClass("alert-primary");
                $("#resultMsg").addClass("alert-danger");
            }

            $("#resultMsg").empty().html(result["msg"]).fadeIn();
            $("#resultMsg").delay(3000).fadeOut();

            getUserAttendList();
        }
    });
}
</script>
<form id="mainForm" name="mainForm" method="post">
<div class="btnList">
    <div>
        <button type="button" class="btn btn-primary" id="btnApp" disabled>승인</button>
        <button type="button" class="btn btn-primary" id="btnCancle" disabled>취소</button>
    </div>
</div>
<div id="resultMsg" class="alert alert-primary py-1 mb-2" style="display: none;"></div>
<div id="divSearch">
<div class="row">
    <div class="col-xl-6 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend mr-2">
                <label>조회일자</label>
            </div>
            <input type="date" class="form-control ml-2" id="searchFrom" name="searchFrom" />
            <div class="input-group-append">
                <button type="button" id="btnSearchDate" name="btnSearchDate" class="btn btn-info">
                    <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                    <span class="fas fa-magnifying-glass"></span>
                </button>
            </div>
        </div>
    </div>
    <div class="col-xl-6 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend mr-2">
                <label>휴일조회구분</label>
            </div>
            <div class="input-group-append">
                <div class="form-check-inline">
                    <label class="form-check-label">
                        <input type="radio" class="form-check-input" name="holidayOption" value="0" onchange="getDeptAttendList()">모두보기
                    </label>
                </div>
                <div class="form-check-inline disabled">
                    <label class="form-check-label">
                        <input type="radio" class="form-check-input" name="holidayOption" value="1" checked onchange="getDeptAttendList()">근태 등록자만 보기
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div class="tableFixHead" style="height: 200px !important">
    <table class="table table-bordered" id="tblDeptAttendList">
        <thead class="thead-light">
            <tr>
                <th>부서</th>
                <th width="6%">총원</th>
                <th width="6%">근태</th>
                <th width="6%" class="m-none">출장</th>
                <th width="6%" class="m-none">훈련</th>
                <th width="6%" class="m-none">교육</th>
                <th width="6%" class="m-none">휴가</th>
                <th width="6%" class="m-none">재택</th>
                <th width="6%" class="m-none">결근</th>
                <th width="6%" class="m-none">계</th>
                <th width="6%" class="m-none">지각</th>
                <th width="6%" class="m-none">조퇴</th>
                <th width="6%" class="m-none">계</th>
                <th width="6%">현재원</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<div class="search-inline mt-3 mb-2" style="justify-content: end">
    <select class="form-control mr-2" style="flex: 0 1 auto !important;" id="selAttend" name="selAttend"></select>
    <select class="form-control mr-2" style="flex: 0 1 auto !important; display:none;" id="selAtDetail" name="selAtDetail"></select>
    <button type="button" class="btn btn-sm btn-info" id="btnBatchAttend" disabled>일괄변경</button>
</div>
<div class="table-responsive">
<div class="tableFixHead" style="height: 500px !important">
    <table class="table table-bordered" id="tblUserAttendList">
        <thead class="thead-light">
            <tr>
                <th style="white-space: nowrap;min-width: 2rem"><input type="checkbox" id="chkAllUser" onclick="onChkAllClick(this, 'chkUserList')"/></th>
                <th style="white-space: nowrap;min-width: 10rem">내역</th>
                <th style="white-space: nowrap;min-width: 10rem">상세</th>
                <th style="white-space: nowrap;min-width: 10rem">부서</th>
                <th style="white-space: nowrap;min-width: 10rem">직급</th>
                <th style="white-space: nowrap;min-width: 10rem">사원명</th>
                <th style="white-space: nowrap;min-width: 10rem">출근시간</th>
                <th style="white-space: nowrap;min-width: 10rem">퇴근시간</th>
                <th style="white-space: nowrap;min-width: 10rem">품의제목</th>
                <th style="white-space: nowrap;min-width: 10rem">승인</th>
                <th style="white-space: nowrap;min-width: 10rem">변경사유</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
</div>

<?php 
require_once '../ea2/ea_appdoc_detail_view.php';
require_once 'bs0300100_detail_view.php';
?>

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="deptId" name="deptId" />
<input type="hidden" id="isModal" name="isModal" />
</form>
