<script>
$(document).ready(function() {
    //thead 고정
    var thAnnvLogic = $('#tblAnnvLogic').find('thead th');
    $('#tblAnnvLogic').closest('div.tableFixHead').on('scroll', function() {
        thAnnvLogic.css('transform', 'translateY(' + this.scrollTop + 'px)');
    });

    //작업모드
    $("#mode").val("INIT");
    $.ajax({
        type: "POST",
        url: "/gw/bs/bs0304000.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //회사목록
            var html = "";
            $(result["companyList"]).each(function(i, info) {
                //해당하는 회사지정
                if (info["key"] == info["userCoId"]) {
                    html += '<option value="' + info["key"] + '" selected>' + info["value"] + '</option>';
                } else {
                    html += '<option value="' + info["key"] + '">' + info["value"] + '</option>';
                }
            });
            $("#companyId").append(html);
        },
        complete: function(result) {
            onConditionChange()

            //추가 버튼
            $("#btnAddAnnyLogic").on("click", result , onBtnAddAnnyLogicClick);
            //저장 버튼
            $("#btnSaveAnnyLogic").on("click", onBtnSaveAnnyLogicClick);
            //회사 변경
            $("#companyId").on("change", onConditionChange);
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}); 

//회사 변경
function onConditionChange() {
    //작업모드
    $("#mode").val("LIST");
    $.ajax({
        type: "POST",
        url: "/gw/bs/bs0304000.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }
            showInfoList(result);
        },
        complete: function() {
            addValidateElementToInputs("tblAnnvLogic")
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

function showInfoList(list) {
    $('#tblAnnvLogic').closest('div.tableFixHead').scrollTop(0);
    $("#tblAnnvLogic tbody").empty();
    var html = "";
    $(list["infoList"]).each(function(i, info) {
        //소수점 앞 0 채우기
        if (info["baseCnt"].substr(0, 1) == ".") {
            var baseCnt = "0" + info["baseCnt"];
        } else {
            var baseCnt = info["baseCnt"];
        }
        //입사년차
        var stEnterCnt = info["stEnterCnt"];
        var endEnterCnt = info["endEnterCnt"];
        //목록
        html += '<tr class="row" id="exist_' + info["annvLogicId"] + '" name="exist">';
        html += '<td class="col-md-4 col-4">';
        html += '<div class="form-group h-100 d-flex align-items-center">';
        html += '<label for="annvLogicNm_' + info["annvLogicId"] + '" style="display:none">항목명</label>';
        html += '<input type="text" class="form-control validateElement" id="annvLogicNm_' + info["annvLogicId"] + '" name="annvLogicNm[' + info["annvLogicId"] + ']" value="' + info["annvLogicNm"] + '" maxlength="100" required/>';
        html += '<div class="invalid-feedback"></div>';
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-3 col-3">';
        html += '<div class="form-inline form-group h-100 d-flex align-items-center">';
        html += '<label for="stEnterCnt_' + info["annvLogicId"] + '" style="display:none">입사년차</label>';
        html += '<select class="form-control validateElement mr-2" id="stEnterCnt_' + info["annvLogicId"] + '" name="stEnterCnt[' + info["annvLogicId"] + ']" required onchange="onEnterYearChange(this)">';
        $(list["joinYearList"]).each(function(i, year) {
            //입사년차 값
            if (stEnterCnt == year["value"]) {
                html += '<option value="' + year["key"] + '" selected>' + year["value"] + '</option>';
            } else {
                html += '<option value="' + year["key"] + '">' + year["value"] + '</option>';
            }
        });
        html += '</select>';
        html += '-';
        html += '<label for="endEnterCnt_' + info["annvLogicId"] + '" style="display:none">입사년차</label>';
        html += '<select class="form-control validateElement ml-2" id="endEnterCnt_' + info["annvLogicId"] + '" name="endEnterCnt[' + info["annvLogicId"] + ']" required onchange="onEnterYearChange(this)">';
        $(list["joinYearList"]).each(function(i, year) {
            //입사년차 값
            if (endEnterCnt == year["value"]) {
                html += '<option value="' + year["key"] + '" selected>' + year["value"] + '</option>';
            } else {
                html += '<option value="' + year["key"] + '">' + year["value"] + '</option>';
            }
        });
        html += '</select>';
        html += '<div class="invalid-feedback"></div>';
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-4 col-4">';
        html += '<div class="form-group h-100 d-flex align-items-center">';
        html += '<label for="baseCnt_' + info["annvLogicId"] + '" style="display:none">부여일수</label>';
        html += '<input type="number" class="form-control text-right validateElement" id="baseCnt_' + info["annvLogicId"] + '" name="baseCnt[' + info["annvLogicId"] + ']" value="' + baseCnt + '" min="0" max="100" step="0.001" required/>';
        html += '<div class="invalid-feedback"></div>';
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 col-1">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += '<button type="button" class="btn btn-danger" id="btnExtDel_' + info["annvLogicId"] + '" onclick="onBtnDelAnnyLogicClick(this)"><i class="fa-solid fa-trash-can"></i></button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
    });

    $("#tblAnnvLogic tbody").append(html);
} 

// 추가 버튼
function onBtnAddAnnyLogicClick(result) {
    //입사년차 가져오기
    $("#new").val(parseInt($("#new").val()) + 1);
    newNum = $("#new").val();
    var html = "";
    html += '<tr class="row" id="new_' + newNum + '" name="new">';
    html += '<td class="col-md-4 col-4">';
    html += '<div class="form-group h-100 d-flex align-items-center">';
    html += '<label for="newAnnvLogicNm_' + newNum + '" style="display:none">항목명</label>';
    html += '<input type="text" class="form-control validateElement" id="newAnnvLogicNm_' + newNum + '" name="newAnnvLogicNm[' + newNum + ']" maxlength="100" required/>';
    html += '<div class="invalid-feedback"></div>';
    html += '</div>';
    html += '</td>';
    html += '<td class="col-md-3 col-3">';
    html += '<div class="form-inline form-group h-100 d-flex align-items-center">';
    html += '<label for="newStEnterCnt_' + newNum + '" style="display:none">입사년차</label>';
    html += '<select class="form-control validateElement mr-2" id="newStEnterCnt_' + newNum + '" name="newStEnterCnt[' + newNum + ']" required onchange="onEnterYearChange(this)">';
    $(result["data"]["responseJSON"]["joinYearList"]).each(function(i, year) {
        html += '<option value="' + year["key"] + '">' + year["value"] + '</option>';
    });
    html += '</select>';
    html += '-';
    html += '<label for="newEndEnterCnt_' + newNum + '" style="display:none">입사년차</label>';
    html += '<select class="form-control validateElement ml-2" id="newEndEnterCnt_' + newNum + '" name="newEndEnterCnt[' + newNum + ']" required onchange="onEnterYearChange(this)">';
    $(result["data"]["responseJSON"]["joinYearList"]).each(function(i, year) {
        html += '<option value="' + year["key"] + '">' + year["value"] + '</option>';
    });
    html += '</select>';
    html += '<div class="invalid-feedback"></div>';
    html += '</div>';
    html += '</td>';
    html += '<td class="col-md-4 col-4">';
    html += '<div class="form-group h-100 d-flex align-items-center">';
    html += '<label for="newBaseCnt_' + newNum + '" style="display:none">부여일수</label>';
    html += '<input type="number" class="form-control text-right validateElement" id="newBaseCnt_' + newNum + '" name="newBaseCnt[' + newNum + ']" min="0" max="100" step="0.001" required/>';
    html += '<div class="invalid-feedback"></div>';
    html += '</div>';
    html += '</td>';
    html += '<td class="col-md-1 col-1">';
    html += '<div class="h-100 d-flex align-items-center">';
    html += '<button type="button" class="btn btn-danger" id="btnNewDel_' + newNum + '" onclick="onBtnDelAnnyLogicClick(this)"><i class="fa-solid fa-trash-can"></i></button>';
    html += '</div>';
    html += '</td>';
    html += '</tr>';
    $("#tblAnnvLogic tbody").append(html);

    addValidateElementToInputs("new_" + newNum)
}

//삭제 버튼
function onBtnDelAnnyLogicClick(obj) {
    var btnId = $(obj).attr("id");
    var btnSplit = btnId.split('_');
    if (btnSplit[0] == "btnExtDel") {
        //기존항목
        $("#exist_" + btnSplit[1]).hide();

        //삭제할 값 배열로 저장하기
        var existDel = '<input type="hidden" name="existDel[]" value="' + btnSplit[1] + '" />';
        $("#mainForm").append(existDel);

        //값 넘기기 X
        $("#annvLogicNm_" + btnSplit[1]).prop("disabled", true);
        $("#stEnterCnt_" + btnSplit[1]).prop("disabled", true);
        $("#endEnterCnt_" + btnSplit[1]).prop("disabled", true);
        $("#baseCnt_" + btnSplit[1]).prop("disabled", true);
    } else {
        //추가항목
        $("#new_" + btnSplit[1]).hide();

        //값 넘기기 X
        $("#newAnnvLogicNm_" + btnSplit[1]).prop("disabled", true);
        $("#newStEnterCnt_" + btnSplit[1]).prop("disabled", true);
        $("#newEndEnterCnt_" + btnSplit[1]).prop("disabled", true);
        $("#newBaseCnt_" + btnSplit[1]).prop("disabled", true);
    }
}

//저장 버튼
function onBtnSaveAnnyLogicClick() {
    if(validateInputs()) {
        //작업모드
        $("#mode").val("SAVE");
        
        $.ajax({
            type: "POST",
            url: "/gw/bs/bs0304000.php",
            data: $("#mainForm").serialize(),
            dataType: "json",
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }
            },
            complete: function(result) {
                onConditionChange();
                $("#resultMsg").empty().html(result["responseJSON"]["msg"]).fadeIn();
                $("#resultMsg").delay( 5000 ).fadeOut();
            },
            error: function(request, status, error) {
                alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
            }
        });
    }
}

//입사년차 변경
function onEnterYearChange(obj) {
    var enterId = $(obj).attr("id");
    var enterSplit = enterId.split('_');
    //기존 입사년차 항목
    if (enterSplit[0] == "stEnterCnt") {
        var stEnterVal = $("#" + enterId).val();
        var stEnterLast = $("#" + enterId + " option:last").val(); 
        var endEnterVal = $("#endEnterCnt_" + enterSplit[1]).val();

        if (stEnterVal > endEnterVal) {
            var stIndex = $("#" + enterId + " option").index($("#" + enterId + " option:selected"));
            $("#endEnterCnt_" + enterSplit[1] + " option:eq("+ (stIndex + 1) +")").attr("selected", "selected");
            // 마지막옵션 선택시
            if(stEnterVal == stEnterLast) {
                $("#endEnterCnt_" + enterSplit[1] + " option:last").attr("selected", "selected");
            }
        }
    } else if (enterSplit[0] == "endEnterCnt") {
        var stEnterVal = $("#stEnterCnt_" + enterSplit[1]).val();
        var endEnterVal = $("#" + enterId).val();

        if (stEnterVal > endEnterVal) {
            $("#stEnterCnt_" + enterSplit[1]).val(endEnterVal); 
            $("#" + enterId).val(stEnterVal);
        }

    //추가 입사년차 항목
    } else if (enterSplit[0] == "newStEnterCnt") {
        var stEnterVal = $("#" + enterId).val();
        var stEnterLast = $("#" + enterId + " option:last").val(); 
        var endEnterVal = $("#newEndEnterCnt_" + enterSplit[1]).val();

        if (stEnterVal > endEnterVal) {
            var stIndex = $("#" + enterId + " option").index($("#" + enterId + " option:selected"));
            $("#newEndEnterCnt_" + enterSplit[1] + " option:eq("+ (stIndex + 1) +")").attr("selected", "selected");
            // 마지막옵션 선택시
            if(stEnterVal == stEnterLast) {
                $("#newEndEnterCnt_" + enterSplit[1] + " option:last").attr("selected", "selected");
            }
        }
    } else if (enterSplit[0] == "newEndEnterCnt") {
        var stEnterVal = $("#newStEnterCnt_" + enterSplit[1]).val();
        var endEnterVal = $("#" + enterId).val();

        if (stEnterVal > endEnterVal) {
            $("#newStEnterCnt_" + enterSplit[1]).val(endEnterVal);
            $("#" + enterId).val(stEnterVal);
        }
    }
}

//유효성 검사
function validateInputs() {
    var valid = true;

    $(".validateElement").each(function() {

        var validId = $(this).attr("id");

        valid = valid & validateElement(validId);

    });
    
    //입사년도 체크
    if(valid) {
        var stEnterCnt = new Array();
        var endEnterCnt = new Array();
        var newStEnterCnt = new Array();
        var newEndEnterCnt = new Array();
        $("#tblAnnvLogic select").each(function(){
            var selectId = $(this).attr("id");
            var enterSplit = selectId.split('_');

            if(enterSplit[0] == "stEnterCnt") {
                stEnterCnt.push([$("#" + selectId).val(), selectId]);
            }else if(enterSplit[0] == "endEnterCnt"){ 
                endEnterCnt.push([$("#" + selectId).val(), selectId]);
            }else if(enterSplit[0] == "newStEnterCnt") {
                newStEnterCnt.push([$("#" + selectId).val(), selectId]);
            }else  if(enterSplit[0] == "newEndEnterCnt") {
                newEndEnterCnt.push([$("#" + selectId).val(), selectId]);
            }
        })
    
        //기존 입사년차 항목
        for(var  i = 0; i < (stEnterCnt.length) - 1; i++) {
            if( endEnterCnt[i][0] >= stEnterCnt[i + 1][0] ) {
                valid = false;
                $("#" + endEnterCnt[i][1]).addClass("is-invalid");
                $("#" + endEnterCnt[i][1]).closest(".form-group").find(".invalid-feedback").html("입사년차의 범위가 중복됩니다.");
                $("#" + endEnterCnt[i][1]).closest(".form-group").find(".invalid-feedback").show();
                $("#" + stEnterCnt[i + 1][1]).addClass("is-invalid");
                $("#" + stEnterCnt[i + 1][1]).closest(".form-group").find(".invalid-feedback").html("입사년차의 범위가 중복됩니다.");
                $("#" + stEnterCnt[i + 1][1]).closest(".form-group").find(".invalid-feedback").show();
            }
        }
    
        //추가 입사년차 항목
        for(var  i = 0; i < stEnterCnt.length; i++) {
            for(var j = 0; j < newStEnterCnt.length; j++) {
                if(stEnterCnt[i][0] <= newStEnterCnt[j][0] <= endEnterCnt[i][0] || stEnterCnt[i][0] <= newEndEnterCnt[j][0] <= endEnterCnt[i][0]) {
                    valid = false;
                    $("#" + newStEnterCnt[j][1]).addClass("is-invalid");
                    $("#" + newStEnterCnt[j][1]).closest(".form-group").find(".invalid-feedback").html("입사년차의 범위가 중복됩니다.");
                    $("#" + newStEnterCnt[j][1]).closest(".form-group").find(".invalid-feedback").show();
                    $("#" + newEndEnterCnt[j][1]).addClass("is-invalid");
                    $("#" + newEndEnterCnt[j][1]).closest(".form-group").find(".invalid-feedback").html("입사년차의 범위가 중복됩니다.");
                    $("#" + newEndEnterCnt[j][1]).closest(".form-group").find(".invalid-feedback").show();
                }
            }
        }
    }

    return valid;
}

</script>
<form id="mainForm" name="mainForm" method="post">
<div class="btnList">
    <div>
        <button type="button" class="btn btn-primary" id="btnSaveAnnyLogic">저장</button>
    </div>
</div>

<div class="row">
    <div class="col search-inline mb-2">
        <label for="companyList">회사</label>
        <select class="form-control" id="companyId" name="companyId">
        </select>
    </div>
</div>
<div>
    <div id="resultMsg" class="alert alert-primary py-1 mb-2" style="display: none;"></div>
</div>
<div class="tableFixHead">
    <table class="table" id="tblAnnvLogic">
        <thead class="thead-light">
            <tr class="row">
                <th class="d-flex justify-content-center align-items-center col-md-4 col-4">항목명<span class="necessaryInput"> *</span></th>
                <th class="d-flex justify-content-center align-items-center col-md-3 col-3">입사년차<span class="necessaryInput"> *</span></th>
                <th class="d-flex justify-content-center align-items-center col-md-4 col-4">부여일수<span class="necessaryInput"> *</span></th>
                <th class="d-flex justify-content-center align-items-center col-md-1 col-1"><button type="button" class="btn btn-info" id="btnAddAnnyLogic" name="btnAddAnnyLogic"><i class="fa fa-plus"></i></button></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<!-- <div class="d-flex justify-content-end mb-3">
    <div>
        <button type="button" class="btn btn-info" id="btnAddAnnyLogic" name="btnAddAnnyLogic"><i class="fa fa-plus"></i></button>
    </div>
</div> -->

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="new" name="new" value="0" />
</form>
