<script type="text/javascript">
$(document).ready(function() {

    //목록표시
    onConditionChange()

    //등록 버튼
    $("#btnAddVacation").on("click", onBtnAddVacationClick);
    //저장 버튼
    $("#btnSaveVacation").on("click", onBtnSaveVacationClick);
    //삭제 버튼
    $("#btnDelVacation").on("click", onBtnDelVacationClick);
    //삭제 확인 버튼
    $("#btnConfirmDel").on("click", onBtnDeleteVacationClick);

    //유효성 검사
    addValidateElementToInputs("modalEditVacation");

    //IE
    if (!!navigator.userAgent.match(/Trident\/7\./)) { 
        $("#modalEditVacation").removeClass("fade");
        $("#modalDetailVacation").removeClass("fade");
    }

    var thVacation = $('#tblVacation').find('thead th');
    $('#tblVacation').closest('div.tableFixHead').on('scroll', function() {
        thVacation.css('transform', 'translateY('+ this.scrollTop +'px)');
    });
});

//등록 버튼 클릭
function onBtnAddVacationClick() {
    clearEditVacation();
    $("#btnDelVacation").hide();
    $('#newYn').val("Y");
    $("#modalTitle").text('휴가코드 등록');
    $("#modalEditVacation").modal("show");
    $("#cdVal").prop("readonly", false);
}

//저장 버튼 클릭
function onBtnSaveVacationClick() {
    if (validateInputs()) {

        //작업모드
        $("#mode").val("SAVE");
        var proceed;
        proceed = true;

        //numeric체크
        // var minAnn = $("#minAnn").val()
        // if(!$.isNumeric(minAnn)) {
        //     proceed = false;
        //     var obj = document.getElementById("minAnn");
        //     $(obj).addClass("is-invalid");
        //     $(obj).closest(".form-group").find(".invalid-feedback").html("숫자를 입력하세요.");
        //     $(obj).closest(".form-group").find(".invalid-feedback").show();
        // }

        //연차크기
        // else if(minAnn > 1.000){
        //     proceed = false;
        //     var obj = document.getElementById("minAnn");
        //     $(obj).addClass("is-invalid");
        //     $(obj).closest(".form-group").find(".invalid-feedback").html("1이하의 숫자를 입력하세요.");
        //     $(obj).closest(".form-group").find(".invalid-feedback").show();
        // }

        if(proceed) {
            $.ajax({
                type: "POST",
                url: "/gw/bs/bs0308000.php",
                data: $("#mainForm").serialize(),
                dataType: "json",
                success: function(result) {
                    //세션 만료일 경우
                    if (result["session_out"]) {
                        //로그인 화면으로 이동
                        onLogoutClick();
                    }
    
                    proceed = result["proceed"];
                    if (proceed) {
                        $("#modalEditVacation").modal("hide");
                    } else {
                        if (Object.keys(result["resultValidation"]).length > 0) {
                            $.each(result["resultValidation"], function(id, msg) {
                                var obj = document.getElementById(id);
                                var isReadOnly = obj.readOnly;
                                if (isReadOnly) {
                                    obj.readOnly = false;
                                }
                                $(obj).removeClass("is-valid").addClass("is-invalid");
                                obj.setCustomValidity(msg);
                                $(obj).closest(".form-group").find(".invalid-feedback").html(obj.validationMessage);
                                $(obj).closest(".form-group").find(".invalid-feedback").show();
                                if (isReadOnly) {
                                    obj.readOnly = true;
                                }
                            });
                        }
                    }
                },
                complete: function() {
                    $("#modalEditVacation").find("button:button").prop("disabled", false);
                    onConditionChange();
                },
                error: function(request, status, error) {
                    alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
                }
            });
        }
    }
}

//편집 버튼 클릭
function onBtnEditVacationClick(cdVal) {
    $("#btnDelVacation").show();
    clearEditVacation()
    $('#newYn').val("N");
    $("#cdVal").prop("readonly", true);
    $("#cdVal").val(cdVal);

    $("#modalTitle").text('휴가코드 편집');

    //작업모드
    $("#mode").val("DETAIL");
    $.ajax({
        type: "POST",
        url: "/gw/bs/bs0308000.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            var vacationInfo = result["vacationInfo"][0];
            //휴가코드
            $("#cdVal").val(vacationInfo["cdVal"]);
            //휴가명
            $("#cdNm").val(vacationInfo["cdNm"]);
            //영어
            $("#cdNmEn").val(vacationInfo["cdNmEn"]);
            //중국어
            $("#cdNmCn").val(vacationInfo["cdNmCn"]);
            //중국어간체
            $("#cdNmGb").val(vacationInfo["cdNmGb"]);
            //일본어
            $("#cdNmJp").val(vacationInfo["cdNmJp"]);
            //사용연차
            //소수 .앞에 0 채우기
            if (vacationInfo["minAnn"].substr(0, 1) == ".") {
                var minAnn = "0" + vacationInfo["minAnn"];
            } else {
                var minAnn = vacationInfo["minAnn"];
            }
            $("#minAnn").val(minAnn);
            //사용여부
            if (vacationInfo["useYn"] == 1) {
                //사용
                $("input:radio[name='useYn']:radio[value='1']").prop('checked', true);
            } else {
                //미사용
                $("input:radio[name='useYn']:radio[value='0']").prop('checked', true);
            }

            $("#modalEditVacation").modal("show");
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    })
}

//삭제 버튼 클릭
function onBtnDelVacationClick() {
    $("#modalConfirmDel").modal("show");
}

function onBtnDeleteVacationClick() {
    $("#modalConfirmDel").modal("hide");
    //작업모드
    $("#mode").val("DEL");
    var proceed;
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0308000.php", 
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            proceed = result["proceed"];
            if (proceed) {
                $("#modalEditVacation").modal("hide");
            }
            else {
                if (Object.keys(result["resultValidation"]).length > 0) {
                    $.each(result["resultValidation"], function(id, msg) {
                        var obj = document.getElementById(id);
                        var isReadOnly = obj.readOnly;
                        if (isReadOnly) {
                            obj.readOnly = false;
                        }
                        $(obj).removeClass("is-valid").addClass("is-invalid");
                        obj.setCustomValidity(msg);
                        $(obj).closest(".form-group").find(".invalid-feedback").html(obj.validationMessage);
                        $(obj).closest(".form-group").find(".invalid-feedback").show();
                        if (isReadOnly) {
                            obj.readOnly = true;
                        }
                    });
                }
            }
        },
        complete:function() {
            if (proceed) {
                onConditionChange();
            }
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//휴가코드 편집 초기화
function clearEditVacation() {
    $("#modalEditVacation").find("input[type='text']").val("");
    $("#modalEditVacation").find("input[type='number']").val("");
    $("#modalEditVacation").find("input[type='hidden']").val("");
    $("#modalEditVacation").find("textarea").val("");
    $("#modalEditVacation").find(".invalid-feedback").hide();

    //유효성 검사
    $(".is-invalid").removeClass('is-invalid');
    $("input:radio[name='useYn']:radio[value='1']").prop('checked', true);
}

//유효성 검사
function validateInputs() {
    var valid = true;

    //휴가코드
    valid = valid & validateElement("cdVal");

    //휴가명
    valid = valid & validateElement("cdNm");

    //사용연차
    valid = valid & validateElement("minAnn");

    return valid;
}

//목록 표시
function onConditionChange() {
    //작업모드
    $("#mode").val("LIST");
    $.ajax({
        type: "POST",
        url: "/gw/bs/bs0308000.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }
            showInfoList(result["infoList"]);
        }
    });
}

function showInfoList(list) {
    $('#tblVacation').closest('div.tableFixHead').scrollTop(0);
    $("#tblVacation tbody").empty();
    var html = "";
    $(list).each(function(i, info) {

        //소수 .앞에 0 채우기
        if (info["minAnn"].substr(0, 1) == ".") {
            var minAnn = "0" + info["minAnn"]
        } else {
            var minAnn = info["minAnn"]
        }

        html += '<tr class="row">';
        html += '<td class="col-md-1 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["cdVal"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md col">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["cdNm"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["cdNmEn"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["cdNmCn"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["cdNmGb"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["cdNmJp"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += minAnn;
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 d-none d-md-block col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["useYnNm"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 col-3 col-w-btn">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += '<button type="button" class="btn btn-primary" onclick="onBtnEditVacationClick(\'' + info["cdVal"] + '\')">편집</button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
    });
    $("#tblVacation tbody").append(html);
}
</script>
<form id="mainForm" name="mainForm" method="post">
<div class="btnList">
    <div>
        <button type="button" class="btn btn-primary" id="btnAddVacation">등록</button>
    </div>
</div>

<div class="tableFixHead">
    <table class="table" id="tblVacation">
        <thead class="thead-light">
            <tr class="row">
                <th class="col-md-1 col-3">휴가코드</th>
                <th class="col-md col">휴가명</th>
                <th class="col-md-2 d-none d-md-block">영어</th>
                <th class="col-md-1 d-none d-md-block">중국어</th>
                <th class="col-md-1 d-none d-md-block">중국어간체</th>
                <th class="col-md-2 d-none d-md-block">일본어</th>
                <th class="col-md-1 col-3">사용연차</th>
                <th class="col-md-1 d-none d-md-block">사용여부</th>
                <th class="col-md-1 col-3 col-w-btn">편집</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>


<!-- The Modal -->
<div class="modal fade modalMain" id="modalEditVacation" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle"></h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <!-- maxlength는 Stored Procedures를 기준으로 함 -->
            <div class="modal-body mainContents">
                <div class="row form-group">
                    <div class="col-3 colHeader">
                        <label for="cdVal">휴가코드</label><span class="necessaryInput"> *</span>
                    </div>
                    <div class="col-9">
                        <input type="text" class="form-control validateElement" id="cdVal" name="cdVal" maxlength="10" required />
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-3 colHeader">
                        <label for="cdNm">휴가명</label><span class="necessaryInput"> *</span>
                    </div>
                    <div class="col-9">
                        <input type="text" class="form-control validateElement" id="cdNm" name="cdNm" maxlength="20" required />
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">영어</div>
                    <div class="col-9">
                        <input type="text" class="form-control" id="cdNmEn" name="cdNmEn" maxlength="20"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">중국어</div>
                    <div class="col-9">
                        <input type="text" class="form-control" id="cdNmCn" name="cdNmCn" maxlength="20"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">중국어간체</div>
                    <div class="col-9">
                        <input type="text" class="form-control" id="cdNmGb" name="cdNmGb" maxlength="20"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">일본어</div>
                    <div class="col-9">
                        <input type="text" class="form-control" id="cdNmJp" name="cdNmJp" maxlength="20"/>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-3 colHeader">
                        <label for="minAnn">사용연차</label><span class="necessaryInput"> *</span>
                    </div>
                    <div class="col-9">
                        <input type="number" class="form-control validateElement text-right" id="minAnn" name="minAnn" min="0" max="1" step="0.001" required />
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">사용여부</div>
                    <div class="col-9">
                        <div class="form-check-inline">
                            <label class="form-check-label" for="useY">
                                <input type="radio" class="form-check-input" id="useY" name="useYn" value="1" checked> 사용
                            </label>
                        </div>
                        <div class="form-check-inline">
                            <label class="form-check-label" for="useN">
                                <input type="radio" class="form-check-input" id="useN" name="useYn" value="0"> 미사용
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-primary" id="btnSaveVacation">저장</button>
                        <button type="button" class="btn btn-danger" id="btnDelVacation">삭제</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- The Modal -->
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
                        <button type="button" id="btnConfirmDel" class="btn btn-primary">네</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">아니오</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="newYn" name="newYn" />
</form>
