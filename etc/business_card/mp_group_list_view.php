<script>
$(document).ready(function(){
    //작업모드
    $("#mode").val("INIT");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_group_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //그룹구분
            var html = "";
            $(result["groupList"]).each(function(i, info) {
                html += '<div class="form-check-inline">';
                html += '<label class="form-check-label">';
                html += '<input type="radio" class="form-check-input" name="rbl_private" value="' + info["key"] + '" onchange="onConditionChange()" >' + info["val"];
                html += '</label>';
                html += '</div>';
            });
            $("#groupOption").append(html);
        },
        complete: function() {
            //그룹구분 기본값 설정
            $('input:radio[name=rbl_private]').eq(0).prop("checked", true);
            //유효성 검사(편집창)
            addValidateElementToInputs("modalModifyAdGrp");
            //목록 표시
            onConditionChange();
        }
    });

    //유효성 검사 지우기
    $("#modalModifyAdGrp").on('hide.bs.modal', function () {
        $("#grpNm").removeClass('is-valid is-invalid');
        $("#grpNm").closest(".form-group").find(".invalid-feedback").html("");
        $("#mainForm").removeClass('was-validated');

        //모달 비우기
        $("#modifyGrpId").val('');
        $("#grpNm").val('');
        $("#grpDesc").val('');
        $("#publicScopeNms").val('');
        $("#publicScopeIds").val('');
    });

    //등록 버튼
    $("#btnAdd").on('click', onBtnAddClick);
    //저장 버튼
    $("#btnSave").on('click', onBtnSaveClick);
    //삭제 버튼
    $("#btnDel").on('click', onBtnDelClick);
    //삭제 (예) 버튼
    $("#btnConfirmDel").on('click', onBtnConfirmDel);

    //thead 고정
    var thAddrGrp = $('#tblAddrGrp').find('thead th');
    $('#tblAddrGrp').closest('div.tableFixHead').on('scroll', function() {
        thAddrGrp.css('transform', 'translateY('+ this.scrollTop +'px)');
    });
});

//목록표시
function onConditionChange() {
    //작업모드
    $("#mode").val("LIST");

    $.ajax({
        type:"POST",
        url: "/gw/mp/mp_group_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }
            showInfoList(result["addrGrpList"]);

            //스크롤 초기화
            $('#tblAddrGrp').closest('div.tableFixHead').scrollTop(0);
        }
    });
}

function showInfoList(list) {
    $('#tblAddrGrp').closest('div.tableFixHead').scrollTop(0);
    $("#tblAddrGrp tbody").empty();
    var html = "";
    $(list).each(function(i,info) {
        html += '<tr class="row">';
        html += '<td class="col-md-3 col-3">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["addrGrpNm"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 col-2">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["userCnt"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md col">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += info["addrGrpDesc"];
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-1 col-2 col-w-btn">';
        html += '<div class="h-100 d-flex align-items-center">';
        html += '<button type="button" id="btnModifyAdGrp_'+ info["addrGrpId"] +'" name="btnModifyAdGrp" class="btn btn-primary" onclick="onBtnModifyAdGrpClick(this)" data-toggle="modal">편집</button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
    });
    $("#tblAddrGrp tbody").append(html);
}

//편집 버튼
function onBtnModifyAdGrpClick(obj) {
    var btnId = $(obj).attr("id");
    var grpId = btnId.split("_");
    $("#modifyGrpId").val(grpId[1]);

    //작업모드
    $("#mode").val("DETAIL");

    //수정 모드
    $("#dbMode").val('U');

    //모달헤더
    $("#modalTitle").text('그룹편집');

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_group_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            //그룹명
            $("#grpNm").val(result["addrGrpDetail"]["addrGrpNm"]);
            //그룹설명
            $("#grpDesc").val(result["addrGrpDetail"]["addrGrpDesc"]);
            //공개범위
            publicScopeNms = '';

            if(result["addrGrpScope"]) {
                $.each(result["addrGrpScope"]["nm"], function(index, nm) {
                    if(index > 0) {
                        publicScopeNms += ", ";
                    }
                    publicScopeNms += result["addrGrpScope"]["nm"][index];
                });

                $("#publicScopeNms").val(publicScopeNms);

                var publicScopeIds = '';

                $.each(result["addrGrpScope"]["id"], function(index, id) {
                    if(index > 0) {
                        publicScopeIds += "/";
                    }
                    publicScopeIds += result["addrGrpScope"]["id"][index];
                });

                $("#publicScopeIds").val(publicScopeIds);
            }

            //삭제 버튼 보이기
            $("#btnDel").show();

            //모달 열기
            $("#modalModifyAdGrp").modal("show");
        }
    })
}

//유효성 검사
function validateInputs() {
    var valid = true;

    //그룹명
    valid = valid & validateElement("grpNm");

    return valid;
}

//등록 버튼
function onBtnAddClick() {
    //삽입 모드
    $("#dbMode").val('I');
    //삭제 버튼 숨기기
    $("#btnDel").hide();
    $("#modalModifyAdGrp").modal("show");
    //모달헤더
    $("#modalTitle").text('그룹등록');
}

//저장 버튼
function onBtnSaveClick() {
    //작업모드
    $("#mode").val("SAVE");

    if(validateInputs()) {
        $.ajax({
            type: "POST",
            url: "/gw/mp/mp_group_list.php",
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
                    $("#modalModifyAdGrp").modal("hide");
                    onConditionChange()
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
            }
        });
    }
}

//삭제 버튼
function onBtnDelClick() {
    $("#modalConfirmDel").modal("show");
}

//삭제 (예) 버튼
function onBtnConfirmDel() {
    //작업모드
    $("#mode").val("DEL");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_group_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function (result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            proceed = result["proceed"];
            if (proceed) {
                $("#modalConfirmDel").modal("hide");
                $("#modalModifyAdGrp").modal("hide");
                onConditionChange();
            }
            else {
                $("#modalConfirmDel").modal("hide");
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
        }
    });
}
</script>
<form id="mainForm" name="mainForm" method="post">
<div class="btnList">
    <div>
        <button type="button" class="btn btn-primary" id="btnAdd">등록</button>
    </div>
</div>

<div class="row">
    <div class="col search-inline mb-2" id="groupOption">
        <label class="control-label" for="rbl_private">그룹구분</label>
    </div>
</div>

<div class="tableFixHead">
<table class="table" id="tblAddrGrp">
    <thead class="thead-light">
        <tr class="row">
            <th class="col-md-3 col-3">그룹명</th>
            <th class="col-md-2 col-2">인원수</th>
            <th class="col-md col">그룹설명</th>
            <th class="col-md-1 col-2 col-w-btn">편집</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>

<!-- The Modal -->
<div class="modal fade modalMain" id="modalModifyAdGrp" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle"></h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body mainContents">
                <div class="row form-group">
                    <div class="col-3 colHeader">
                        <label for="grpNm">그룹명</label><span class="necessaryInput"> *</span>
                    </div>
                    <div class="col-9">
                        <input type="text" class="form-control validateElement" id="grpNm" name="grpNm" maxlength="50" required/>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">
                        <label for="grpDesc">그룹설명</label>
                    </div>
                    <div class="col-9">
                        <input type="text" class="form-control" id="grpDesc" maxlength="100" name="grpDesc"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">
                        <label for="public_Box">공개범위</label>
                    </div>
                    <div class="col-9">
                        <div class="input-group">
                            <input type="text" class="form-control" id="publicScopeNms" name="publicScopeNms" readonly />
                            <input type="hidden" id="publicScopeIds" name="publicScopeIds" />
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" onclick="onBtnSelectMultiDeptUserClick('MP', 'publicScope', 'Y', 0)">선택</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-primary" id="btnSave">저장</button>
                        <button type="button" class="btn btn-danger" id="btnDel">삭제</button>
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

<?php 
require_once '../cm/cm_select_multi_dept_user_view.php';
?>

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="modifyGrpId" name="modifyGrpId" />
<input type="hidden" id="dbMode" name="dbMode" />
</form>
