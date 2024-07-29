<style>
.table td, .table th {
    vertical-align: middle !important;
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
    $('#searchTo').val(today);
    // 일주일 전
    var beforeWeek = new Date(now.getFullYear(), month - 1, day - 7).toLocaleDateString();
    // 공백제거
    beforeWeek = beforeWeek.replace(/\s/gi, '').split(".");
    var beforeYear = beforeWeek[0];
    var beforeMonth = beforeWeek[1].padStart(2, '0');
    var beforeDay = beforeWeek[2].padStart(2, '0');
    var beforeDate = beforeYear + '-' + beforeMonth + '-' + beforeDay;
    $('#searchFrom').val(beforeDate);

    // 사업장 리스트 가져오기
    $("#mode").val("INIT");
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300300.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var officeList = result["officeList"];
            var yearList = result["yearList"];
            var monthList = result["monthList"];
            
            // 사업장
            var html = '';
            $(officeList).each(function(i, info) {
                html += '<option value="' + info["deptworkId"] + '">'+ info["deptworkNm"] +'</option>';
            });
            $("#selOffice").append(html);
            // 년도 리스트
            html = '';
            $(yearList).each(function(i, info) {
                html += '<option value="' + info["key"] + '">'+ info["val"] +'</option>';
            });
            $("#ddlYear").append(html);
            $("#ddlYear").val(result["year"]);
            // 월 리스트
            html = '';
            $(monthList).each(function(i, info) {
                html += '<option value="' + info["key"] + '">'+ info["val"] +'</option>';
            });
            $("#ddlMonth").append(html);
            $("#ddlMonth").val(result["month"]);
        },
        complete:function() {
            // 월별근태 리스트 가져오기
            getMonthAttendList();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
    // 사업장 변경
    $("#selOffice").on('change', getMonthAttendList);
    $("#ddlYear").on('change', getMonthAttendList);
    $("#ddlMonth").on('change', getMonthAttendList);

    // 헤더 고정
    var thAttendList = $('#tblAttendList').find('thead th');
    $('#tblAttendList').closest('div.tableFixHead').on('scroll', function() {
        thAttendList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });
});

// 월별근태현황 리스트 가져오기
function getMonthAttendList() {
    $("#mode").val("LIST");
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300300.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var monthAttendList = result["monthAttendList"];
            var html = '';
            $(monthAttendList).each(function(i, info) {
                html += '<tr>';
                html += '<td>';
                html += info["dt"]
                html += '</td>';
                html += '<td>';
                html += info["week"]
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

            $("#tblAttendList tbody").empty().append(html);
        },
        beforeSend:function(data) {
            // timer = setTimeout(function() {
                $("#modalLoading").modal("show");
            // }, 1000);
        },
        complete:function() {
            if ((new Date().getTime() - this.start_time) < 1000) {
                clearTimeout(timer);
            }
            setTimeout(function () {
                if ($("#modalLoading").hasClass('show')) {
                    $("#modalLoading").modal("hide");
                }
            }, 1000);
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}
</script>
<form id="mainForm" name="mainForm" method="post">
<!-- <div class="btnList">
    <div>
        
    </div>
</div> -->
<div id="divSearch">
<div class="row">
    <div class="col-xl-6 search-inline mb-2">
        <di<style>
.table td, .table th {
    vertical-align: middle !important;
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
    $('#searchTo').val(today);
    // 일주일 전
    var beforeWeek = new Date(now.getFullYear(), month - 1, day - 7).toLocaleDateString();
    // 공백제거
    beforeWeek = beforeWeek.replace(/\s/gi, '').split(".");
    var beforeYear = beforeWeek[0];
    var beforeMonth = beforeWeek[1].padStart(2, '0');
    var beforeDay = beforeWeek[2].padStart(2, '0');
    var beforeDate = beforeYear + '-' + beforeMonth + '-' + beforeDay;
    $('#searchFrom').val(beforeDate);

    // 사업장 리스트 가져오기
    $("#mode").val("INIT");
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300300.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var officeList = result["officeList"];
            var yearList = result["yearList"];
            var monthList = result["monthList"];
            
            // 사업장
            var html = '';
            $(officeList).each(function(i, info) {
                html += '<option value="' + info["deptworkId"] + '">'+ info["deptworkNm"] +'</option>';
            });
            $("#selOffice").append(html);
            // 년도 리스트
            html = '';
            $(yearList).each(function(i, info) {
                html += '<option value="' + info["key"] + '">'+ info["val"] +'</option>';
            });
            $("#ddlYear").append(html);
            $("#ddlYear").val(result["year"]);
            // 월 리스트
            html = '';
            $(monthList).each(function(i, info) {
                html += '<option value="' + info["key"] + '">'+ info["val"] +'</option>';
            });
            $("#ddlMonth").append(html);
            $("#ddlMonth").val(result["month"]);
        },
        complete:function() {
            // 월별근태 리스트 가져오기
            getMonthAttendList();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
    // 사업장 변경
    $("#selOffice").on('change', getMonthAttendList);
    $("#ddlYear").on('change', getMonthAttendList);
    $("#ddlMonth").on('change', getMonthAttendList);

    // 헤더 고정
    var thAttendList = $('#tblAttendList').find('thead th');
    $('#tblAttendList').closest('div.tableFixHead').on('scroll', function() {
        thAttendList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });
});

// 월별근태현황 리스트 가져오기
function getMonthAttendList() {
    $("#mode").val("LIST");
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0300300.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var monthAttendList = result["monthAttendList"];
            var html = '';
            $(monthAttendList).each(function(i, info) {
                html += '<tr>';
                html += '<td>';
                html += info["dt"]
                html += '</td>';
                html += '<td>';
                html += info["week"]
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

            $("#tblAttendList tbody").empty().append(html);
        },
        beforeSend:function(data) {
            // timer = setTimeout(function() {
                $("#modalLoading").modal("show");
            // }, 1000);
        },
        complete:function() {
            if ((new Date().getTime() - this.start_time) < 1000) {
                clearTimeout(timer);
            }
            setTimeout(function () {
                if ($("#modalLoading").hasClass('show')) {
                    $("#modalLoading").modal("hide");
                }
            }, 1000);
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}
</script>
<form id="mainForm" name="mainForm" method="post">
<!-- <div class="btnList">
    <div>
        
    </div>
</div> -->
<div id="divSearch">
<div class="row">
    <div class="col-xl-6 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend mr-2">
                <select class="form-control" id="ddlYear" name="ddlYear"></select>
            </div>
            <div class="input-group-append">
                <select class="form-control" id="ddlMonth" name="ddlMonth"></select>
            </div>
        </div>
    </div>
    <div class="col-xl-6 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend mr-2">
                <label>사업장</label>
            </div>
            <div class="input-group-append">
                <select class="form-control" id="selOffice" name="selOffice"></select>
            </div>
        </div>
    </div>
</div>
</div>
<div class="table-responsive">
    <div class="tableFixHead">
    <table class="table table-bordered" id="tblAttendList">
        <thead class="thead-light">
            <tr>
                <th>일자</th>
                <th width="6%">요일</th>
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
</div>

<input type="hidden" id="mode" name="mode" />
</form>
v class="input-group">
            <div class="input-group-prepend mr-2">
                <select class="form-control" id="ddlYear" name="ddlYear"></select>
            </div>
            <div class="input-group-append">
                <select class="form-control" id="ddlMonth" name="ddlMonth"></select>
            </div>
        </div>
    </div>
    <div class="col-xl-6 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend mr-2">
                <label>사업장</label>
            </div>
            <div class="input-group-append">
                <select class="form-control" id="selOffice" name="selOffice"></select>
            </div>
        </div>
    </div>
</div>
</div>
<div class="table-responsive">
    <div class="tableFixHead">
    <table class="table table-bordered" id="tblAttendList">
        <thead class="thead-light">
            <tr>
                <th>일자</th>
                <th width="6%">요일</th>
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
</div>

<input type="hidden" id="mode" name="mode" />
</form>
