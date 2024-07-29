<script type="text/javascript" src="/gw/js/ea.js"></script>
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
    $("#searchTo").attr("max", today);
    // 일주일 전
    var beforeWeek = new Date(now.getFullYear(), month - 1, day - 6).toLocaleDateString();
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
        url: "/gw/bs/bs0301900.php",
        data: $("#mainForm").serialize(), 
        dataType: "json",
        success: function(result) {
            var officeList = result["officeList"];
            
            var html = '';
            $(officeList).each(function(i, info) {
                html += '<option value="' + info["deptworkId"] + '">'+ info["deptworkNm"] +'</option>'
            });
            
            $("#selOffice").append(html);
        },
        complete:function() {
            // 주간근태 리스트 가져오기
            getWeekAttendList();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });

    // 검색 버튼 클릭
    $("#btnSearchDate").on('click', getWeekAttendList);
    // 사업장 변경
    $("#selOffice").on('change', getWeekAttendList);
});

// 주간근태현황 리스트 가져오기
function getWeekAttendList() {
    var searchFrom = $("#searchFrom").val();
    var searchTo = $("#searchTo").val();

    // 날짜차이가 30일 이상일 경우 
    if(getDateDiff(searchFrom, searchTo) > 30) {
        $("#modalAlertMsg .modal-body").empty().text("조회기간을 한달 이내로 설정하십시오.");
        $("#modalAlertMsg").modal('show');
    } else {
        // 앞날짜가 뒷날짜보다 클 경우
        if(searchFrom > searchTo) {
            $("#searchFrom").val(searchTo);
            $("#searchTo").val(searchFrom);
        }

        $("#mode").val("LIST");
        $.ajax({ 
            type: "POST", 
            url: "/gw/bs/bs0301900.php",
            data: $("#mainForm").serialize(), 
            dataType: "json",
            success: function(result) {
                var weekAttendList = result["weekAttendList"];
                
                if(weekAttendList.length > 0) {
                    var headerList = Object.keys(weekAttendList[0]);
                    var regex = /\d{6}/;
                    var dateList = [];
                    var dateData = [];
                    $(headerList).each(function(i, value) {
                        if(regex.test(value)) {
                            dateData.push(value);
                            var formatDate = value.replace(/^\d{4}/, '');
                            formatDate = formatDate.replace(/(^\d{2})(\d{2}$)/, '$1-$2');
                            dateList.push(formatDate);
                        }
                    });
                    
                    // 헤더 추가
                    var th = '';
                    th += '<tr>';
                    th += '<th style="white-space: nowrap;min-width: 15rem">부서</th>';
                    th += '<th style="white-space: nowrap;min-width: 15rem">이름</th>';
                    $(dateList).each(function(i, date) {
                        th += '<th style="white-space: nowrap;min-width: 5rem">'+ date +'</th>'
                    });
                    th += '<th style="white-space: nowrap;min-width: 5rem">출근</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">지각</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">결근</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">휴가</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">출장</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">교육</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">훈련</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">정상</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">이상</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">조퇴</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">당직</th>';
                    th += '<th style="white-space: nowrap;min-width: 5rem">재택</th>';
                    th += '<th style="min-width: 30rem">비고</th>';
                    th += '</tr>';
                    
                    $("#tblAttendList thead").empty().append(th);
                }
                
                var  html = '';
                if(weekAttendList.length > 0) {
                    $(weekAttendList).each(function(i, info) {
                        html += '<tr>';
                        html += '<td class="notAlign" style="white-space: nowrap;min-width: 15rem">';
                        html += info["dept_nm2"];
                        html += '</td>';
                        html += '<td style="white-space: nowrap;min-width: 15rem">';
                        html += info["user_nm"];
                        html += '</td>';
                        $(dateData).each(function(i, date) {
                            html += '<td style="white-space: nowrap;min-width: 5rem">';
                            html += info[date];
                            html += '</td>';
                        });
                        // 출근
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att01"];
                        html += '</td>';
                        // 지각
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att02"];
                        html += '</td>';
                        // 결근
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att03"];
                        html += '</td>';
                        // 휴가
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att04"];
                        html += '</td>';
                        // 출장
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att05"];
                        html += '</td>';
                        // 교육
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att06"];
                        html += '</td>';
                        // 훈련
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att07"];
                        html += '</td>';
                        // 정상
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att08"];
                        html += '</td>';
                        // 이상
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att09"];
                        html += '</td>';
                        // 조퇴
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att10"];
                        html += '</td>';
                        // 당직
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att11"];
                        html += '</td>';
                        // 재택
                        html += '<td style="white-space: nowrap;min-width: 5rem">';
                        html += info["att12"];
                        html += '</td>';
                        // 비고
                        html += '<td class="notAlign" style="min-width: 30rem">';
                        html += info["note"];
                        html += '</td>';
                        html += '</tr>';
                    });
                } else {
                    $("#modalLoading").modal("hide");
                }
    
                $("#tblAttendList tbody").empty().append(html);
            },
            beforeSend:function() {
                $("#btnSearchDate").find("button").prop("disabled", true);
                $("#btnSearchDate").find("span.spinner-border").show();
                // timer = setTimeout(function() {
                    $("#modalLoading").modal("show");
                // }, 1000);
            },
            complete:function() {
                $("#btnSearchDate").find("button").prop("disabled", false);
                $("#btnSearchDate").find("span.spinner-border").hide();
                // if ((new Date().getTime() - this.start_time) < 1000) {
                //     clearTimeout(timer);
                // }
                setTimeout(function () {
                    if ($("#modalLoading").hasClass('show')) {
                        $("#modalLoading").modal("hide");
                    }
                }, 500);
                
                // 헤더 고정
                var thAttendList = $('#tblAttendList').find('thead th');
                $('#tblAttendList').closest('div.tableFixHead').on('scroll', function() {
                    thAttendList.css('transform', 'translateY('+ this.scrollTop +'px)');
                });
            },
            error: function (request, status, error) {
                alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            }
        });
    }
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
                <label>조회기간</label>
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
    <table class="table" id="tblAttendList">
        <thead class="thead-light">
        </thead>
        <tbody>
        </tbody>
    </table>
    </div>
</div>

<input type="hidden" id="mode" name="mode" />
</form>
