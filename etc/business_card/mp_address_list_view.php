<style>
#divBtnListMoveCopyAddr {
    display: -ms-flexbox!important;
    display: flex!important;
    -ms-flex-direction: column!important;
    flex-direction: column!important;
    -ms-flex-pack: center!important;
    justify-content: center!important;
}
#divBtnListMoveCopyAddr .btn {
    padding-left: 0;
    padding-right: 0;
}
#divBtnListMoveCopyAddr .fa-angles-right,
#divBtnListMoveCopyAddr .fa-angles-left {
    display: inline;
}
#divBtnListMoveCopyAddr .fa-angles-down,
#divBtnListMoveCopyAddr .fa-angles-up {
    display: none;
}

@media (max-width: 767px) {
    #divBtnListMoveCopyAddr {
        -ms-flex-direction: row!important;
        flex-direction: row!important;
        -ms-flex-pack: distribute!important;
        justify-content: space-around!important;
    }
    #divBtnListMoveCopyAddr .btn {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    #divBtnListMoveCopyAddr .fa-angles-right,
    #divBtnListMoveCopyAddr .fa-angles-left {
        display: none;
    }
    #divBtnListMoveCopyAddr .fa-angles-down,
    #divBtnListMoveCopyAddr .fa-angles-up {
        display: inline;
    }
}

@media (min-width: 992px) {
    .btnHeight {
        height:24.5px;
    }
}
</style>
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
$(document).ready(function(){
    //작업모드
    $("#mode").val("INIT");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_address_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //수정권한
            if (result["authAddress"] == 1) {
                $("#btnSave").hide();
                $("#btnDel").hide();
            }
            //그룹구분
            var html = "";
            $(result["groupTypeList"]).each(function(i, info) {
                html += '<div class="form-check-inline">';
                html += '<label class="form-check-label">';
                html += '<input type="radio" class="form-check-input" name="rbl_private" value="' + info["key"] + '" onchange="onGroupListChange()">' + info["val"];
                html += '</label>';
                html += '</div>';
            });
            $("#groupOption").append(html);
            $("input:radio[name=rbl_private]").eq(0).prop("checked", true);
            //명함 이동/복사 그룹리스트
            onMoveCopyGrpListChange();
            //검색조건
            html = "";
            $(result["searchKindList"]).each(function(i, info) {
                html += '<option value="'+ info["key"] +'">' + info["val"] + '</option>';
            });
            $("#ddlSearchKind").append(html);
        },
        complete: function() {
            //그룹 목록
            onGroupListChange();

            //유효성 검사
            addValidateElementToInputs("modalIuAddr");
        }
    });

    //thead 고정
    var thAddrList = $('#tblAddrList').find('thead th');
    $('#tblAddrList').closest('div.tableFixHead').on('scroll', function() {
        thAddrList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    //유효성 검사 지우기
    $("#modalIuAddr").on('hide.bs.modal', function () {
        $("#ddlGroup").removeClass('is-valid is-invalid');
        $("#ddlGroup").closest(".form-group").find(".invalid-feedback").html("");
        $("#addrNm").removeClass('is-valid is-invalid');
        $("#addrNm").closest(".form-group").find(".invalid-feedback").html("");
        $("#mainForm").removeClass('was-validated');

        //모달 값 초기화
        $("#ddlGroup option:eq(0)").prop("selected", true);

        $("#modalIuAddr").find("input[type=text], textarea").each(function() {
            $(this).val('');
        });
        $("#publicScopeIds").val('');
        $("#modifyAddrId").val('');
        $("#modifyGrpId").val('');

        //사진초기화
        $("#photoNm").val('');
        $("#filePhotoNm").val('');
        $("#filePhotoNm").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
        $("#imgPhoto").attr('src', '/gw/images/mp/nosign.gif');
    });

    $("#modalMoveCopyAddr").on('shown.bs.modal', function () {
        $(document.activeElement)[0].blur();
        onMoveCopyGrpListChange();
    });
    
    //이동/복사 값 초기화
    $("#modalMoveCopyAddr").on('hide.bs.modal', function () {
        $("#showGroupList option:eq(0)").prop("selected", true);
        $("#moveCopyGroupList option:eq(0)").prop("selected", true);
        $.each($("#showAddrList option"), function() {
            $(this).remove();
        });
        $.each($("#moveCopyList option"), function() {
            $(this).remove();
        });
    });

    //그룹 선택시
    $("#ddlGroupList").on("change", onConditionChange);
//     //검색조건 - 분류
//     $("#ddlSearchKind").on("change", onConditionChange);
    //검색조건 - 입력란
    $("#txtSearchValue").on("keyup", function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            onConditionChange();
        };
    });
    //검색 버튼
    $("#btnSearch").on("click", onConditionChange);
    //등록 버튼
    $("#btnAdd").on("click", onBtnAddClick);
    //저장 버튼
    $("#btnSave").on("click", onBtnSaveClick);
    //삭제 버튼
    $("#btnDel").on('click', onBtnDelClick);
    $("#btnChkDel").on('click', onBtnDelClick);
    //삭제 (예) 버튼
    $("#btnConfirmDel").on('click', onBtnConfirmDel);
    //명함이동/복사 명함리스트
    $("#showGroupList").on('change', onShowGrpListChange);
    //선택이동
    $("#btnSelectMove").on('click', onBtnSelectMoveClick);
    //선택취소
    $("#btnSelectCancel").on('click', onBtnSelectCancelClick);
    //이동 버튼
    $("#btnMove").on('click', onBtnMoveClick);
    //이동 버튼
    $("#btnCopy").on('click', onBtnCopyClick);
    //주소록 내보내기
    $("#btnAddrOut").on('click', onBtnAddrOutClick);
    //주소록 가져오기
    $("#btnAddrIn").on('click', onBtnAddrInClick);
    //양식 다운로드
    $("#btnFormDownload").on('click', onBtnFormDownloadClick);
    //업로드 버튼
    $("#btnExcelUpload").on('click', onBtnExcelUploadClick);
});


//명함 이동/복사 그룹리스트
function onMoveCopyGrpListChange() {

    $("#mode").val("MOVE_COPY_GRP");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_address_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            //명함 이동/복사 그룹리스트
            var html = "";
            $("#showGroupList").empty();
            $("#moveCopyGroupList").empty();
            html += '<option value="0|0">그룹선택</option>';
            $(result["groupList"]).each(function(i, info) {
                html += '<option value="'+ info["key"] +'">' + info["val"] + '</option>';
            });
            $("#showGroupList").append(html);
            $("#moveCopyGroupList").append(html);
        }
    });
}

//검색 버튼 클릭
function onBtnSearchNoticeClick() {
    var elem = $("#txtSearchValue");
    elem.val(elem.val().trim());
    if (elem.data("oldVal") != elem.val()) {
        onConditionChange();
    }
}

//그룹 목록
function onGroupListChange() {
    //작업모드
    $("#mode").val("LIST_GROUP");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_address_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            //그룹 목록
            var html = "";
            $("#ddlGroupList").empty();
            html += '<option value="0">전체그룹</option>';
            $(result["groupList"]).each(function(i, info){
                html += '<option value="' + info["key"] + '">' + info["val"] + '</option>'
            });
            $("#ddlGroupList").append(html);
            //모달 그룹 선택
            var html = "";
            $("#ddlGroup").empty();
            $(result["groupList"]).each(function(i, info){
                html += '<option value="' + info["key"] + '">' + info["val"] + '</option>'
            });
            $("#ddlGroup").append(html);
        },
        complete: function() {
            onConditionChange();
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

//목록표시
function onConditionChange() {
    var elem = $("#ddlSearchKind");
    elem.data('oldVal', elem.val());

    elem = $("#txtSearchValue");
    elem.val(elem.val().trim());
    elem.data('oldVal', elem.val());

    onPageNoClick(1, "", false);
}

function showInfoList(list) {
    //체크박스 초기화
    $("#chkAll").prop('checked', false);
    $('#tblAddrList').closest('div.tableFixHead').scrollTop(0);
    $("#tblAddrList tbody").empty();
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr class="addr">';
        html += '<td class=" col-w-btn" style="white-space: nowrap;min-width: 3rem">';
        html += '<input type="checkbox" id="delCheck_'+ info["addrId"] +'" name="delCheck[]" value="'+ info["addrId"] + "|" + info["addrGrpId"] +'" onclick="onChkAddrIdClick()"/>';
        html += '</td>';
        html += '<td class="col-w-btn" style="white-space: nowrap;min-width: 6rem">';
        html += '<button type="button" id="btnModifyAddr_'+ info["addrId"] +'_' + info["addrGrpId"] + '" name="btnModifyAddr" class="btn btn-primary" onclick="onBtnModifyAddrClick(this)" data-toggle="modal">편집</button>';
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 7.5rem">';
        html += info["addrGrpNm"];
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 7.5rem">';
        html += info["addrNm"];
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 10rem">';
        html += info["addrDuty"];
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 15rem">';
        html += info["addrComp"];
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 15rem">';
        html += info["addrDept"];
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 15rem">';
        html += info["addrHp"];
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 15rem">';
        html += info["addrTelNo"];
        html += '</td>';
        html += '<td style="white-space: nowrap;min-width: 10rem">';
        html += info["addrMail"];
        html += '</td>';
        html += '<td class="notAlign" style="white-space: nowrap;min-width: 30rem">';
        html += info["addrZip"];
        html += '</td>';
        html += '</tr>';
    });
    $("#tblAddrList tbody").append(html);
    onAfterChkAddrClick();
}

//편집 버튼
function onBtnModifyAddrClick(obj) {
    var btnId = $(obj).attr("id");
    var addrId = btnId.split("_");
    $("#modifyAddrId").val(addrId[1]);
    $("#modifyGrpId").val(addrId[2]);

    //작업모드
    $("#mode").val("DETAIL");

    //수정 모드
    $("#dbMode").val('U');

    //삭제버튼 보이기
    $("#btnDel").show();

    //모달 헤더
    $("#modalTitle").text('명함편집');

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_address_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            //그룹명
            var grpId = result["infoDetail"]["addrGrpId"];
            $("#ddlGroup").val(grpId).prop("selected", true);
            //이름
            var addrNm = result["infoDetail"]["addrNm"];
            $("#addrNm").val(addrNm);
            //회사명
            var addrComp = result["infoDetail"]["addrComp"];
            $("#addrComp").val(addrComp);
            //부서명
            var addrDept = result["infoDetail"]["addrDept"];
            $("#addrDept").val(addrDept);
            //직책(급)
            var addrDuty = result["infoDetail"]["addrDuty"];
            $("#addrDuty").val(addrDuty);
            //휴대전화
            var addrHp = result["infoDetail"]["addrHp"];
            $("#addrHp").val(addrHp);
            //회사번호
            var addrTelNo = result["infoDetail"]["addrTelNo"];
            $("#addrTelNo").val(addrTelNo);
            //팩스번호
            var addrFaxNo = result["infoDetail"]["addrFaxNo"];
            $("#addrFaxNo").val(addrFaxNo);
            //메일주소
            var addrMail = result["infoDetail"]["addrMail"];
            $("#addrMail").val(addrMail);
            //우편번호
            var addrZipCd = result["infoDetail"]["addrZipCd"];
            var addrZipAddr = result["infoDetail"]["addrZipAddr"];
            var addrAddrDeail = result["infoDetail"]["addrAddrDeail"];

            $("#addrZipCd").val(addrZipCd);
            $("#addrZipAddr").val(addrZipAddr);
            $("#addrAddrDeail").val(addrAddrDeail);
            //기타1
            var addrEtc1 = result["infoDetail"]["addrEtc1"];
            $("#addrEtc1").val(addrEtc1);
            //기타2
            var addrEtc2 = result["infoDetail"]["addrEtc2"];
            $("#addrEtc2").val(addrEtc2);
            //비고
            var addrNote = result["infoDetail"]["addrNote"];
            $("#addrNote").val(addrNote);
            //공개범위
            publicScopeNms = '';
            publicScopeIds = '';

            if(result["addrScope"]) {
                $.each(result["addrScope"]["nm"], function(index, nm) {
                    if(index > 0) {
                        publicScopeNms += ", ";
                    }
                    publicScopeNms += result["addrScope"]["nm"][index];
                });

                $("#publicScopeNms").val(publicScopeNms);

                $.each(result["addrScope"]["id"], function(index, id) {
                    if(index > 0) {
                        publicScopeIds += "/";
                    }
                    publicScopeIds += result["addrScope"]["id"][index];
                });

                $("#publicScopeIds").val(publicScopeIds);
            }
            //사진
            var imgPhoto = result["infoDetail"]["photoNm"];
            var imgPhotoRoot = imgPhoto.split("\\").pop();

            if(imgPhoto) {
                $("#imgPhoto").attr("src", imgPhoto);
                $("#photoNm").val(imgPhotoRoot);
            } else {
                $("#imgPhoto").attr('src', '/gw/images/mp/nosign.gif');
            }

            $("#modalIuAddr").modal("show");
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

//첨부파일 선택 시
function onAttachFileChange(obj) {
    var fileName = $(obj).val().split("\\").pop();
    $(obj).siblings(".custom-file-label").addClass("selected").html(fileName);

    //업로드버튼 활성화
    $("#btnExcelUpload").prop('disabled', false);

    //이미지 미리보기
    if(obj.id == "filePhotoNm") {
        if (obj.files && obj.files[0]) {
            var reader = new FileReader();
    
            reader.onload = function(e) {
                $("#imgPhoto").attr('src', e.target.result);
            }
    
            reader.readAsDataURL(obj.files[0]);
        }
    }
}

//첨부파일 삭제
function delAttachedFile(obj) {
    //이미지 삭제
    if(obj.id == "btnPhotoDel") {
        if($("#filePhotoNm").val() || $("#photoNm").val()) {
            $("#modalImgConfirmDel").modal('show');
            $("#btnConfirmImgDel").on('click', function() {
                $("#modalImgConfirmDel").modal('hide');
                $("#photoNm").val('');
                $("#filePhotoNm").val('');
                $("#filePhotoNm").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
                $("#imgPhoto").attr('src', '/gw/images/mp/nosign.gif');
            });
        }
    } 
    //엑셀파일 삭제 (주소록 가져오기)
    else if (obj.id == "btnExcelDel") {
        $("#fileExcel").val('');
        $("#fileExcel").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
    }
}

//우편번호 찾기
function execDaumPostcode(obj) {

    new daum.Postcode({
        oncomplete: function(data) {
            // 팝업에서 검색결과 항목을 클릭했을때 실행할 코드를 작성하는 부분.
    
            // 도로명 주소의 노출 규칙에 따라 주소를 표시한다.
            // 내려오는 변수가 값이 없는 경우엔 공백('')값을 가지므로, 이를 참고하여 분기 한다.
            var roadAddr = data.roadAddress; // 도로명 주소 변수
            var extraRoadAddr = ''; // 참고 항목 변수
    
            // 건물명이 있는 경우 추가한다.
            if(data.buildingName !== ''){
                extraRoadAddr += (extraRoadAddr !== '' ? ', ' + data.buildingName : data.buildingName);
            }
            // 표시할 참고항목이 있을 경우, 괄호까지 추가한 최종 문자열을 만든다.
            if(extraRoadAddr !== ''){
                extraRoadAddr = ' (' + extraRoadAddr + ')';
            }
    
            // 우편번호와 주소 정보를 해당 필드에 넣는다.
            $("#addrZipCd").val(data.zonecode);
            $("#addrZipAddr").val(roadAddr);
            // 참고항목 문자열이 있을 경우 해당 필드에 넣는다.
            if(extraRoadAddr !== ''){
                $("#addrAddrDeail").val(extraRoadAddr);
            } 
            else {
                $("#addrAddrDeail").val('');
            }
        }
    }).open();
}

//등록 버튼
function onBtnAddClick() {
    
    //삽입 모드
    $("#dbMode").val('I');
    //삭제버튼 숨기기
    $("#btnDel").hide();
    //초기 이미지
    $("#imgPhoto").attr('src', '/gw/images/mp/nosign.gif');
    //모달 헤더
    $("#modalTitle").text('명함등록');

    $("#modalIuAddr").modal("show");
}

//유효성 검사
function validateInputs() {
    var valid = true;

    //그룹명
    valid = valid & validateElement("ddlGroup");
    
    //이름
    valid = valid & validateElement("addrNm");

    return valid;
}

//저장 버튼
function onBtnSaveClick() {
    //작업모드
    $("#mode").val("SAVE");

    var formdata = new FormData($("#mainForm")[0]);
    if(validateInputs()) {
        $.ajax({
            type: "POST",
            url: "/gw/mp/mp_address_list.php",
            data: formdata,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                onConditionChange();
                $("#modalIuAddr").modal("hide");
            },
            error: function (request, status, error) {
                alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            }
        });
    }
}

//체크박스 전체 선택/해제
function onChkAllAddrClick(obj) {
    onChkAllClick(obj, "delCheck");

    onAfterChkAddrClick();
}

//명함 별 체크박스 선택 변경 시
function onChkAddrIdClick() {
    whenChkClick_chkAll("delCheck", "chkAll");

    onAfterChkAddrClick();
}

function onAfterChkAddrClick() {
    if ($("input[type='checkbox'][name='delCheck[]']:checked").length > 0) {
        //삭제 버튼
        $("#btnChkDel").prop('disabled', false);
    }
    else {
        //삭제 버튼
        $("#btnChkDel").prop('disabled', true);
    }
}

//삭제 버튼
function onBtnDelClick() {
    $("#modalConfirmDel").modal("show");
}

function onBtnConfirmDel() {
    //작업모드
    $("#mode").val("DEL");

    //삭제할 아이디
    if($("#modifyAddrId").val()) {
        //삭제모드
        $("#delMode").val("single");
        $("#deleteAddrId").val($("#modifyAddrId").val() + "|" + $("#modifyGrpId").val());
    } else {
        //삭제모드
        $("#delMode").val("multi");
    }

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_address_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function (result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            $("#modalConfirmDel").modal("hide");
            $("#modalIuAddr").modal("hide");
            $('#chkAll').prop('checked', false);
            onConditionChange();
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

//명함이동/복사 목록
function onShowGrpListChange() {
    //작업모드
    $("#mode").val("LIST_BY_GROUP");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp_address_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function (result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            var html = "";
            $("#showAddrList").empty();
            html = '';
            $(result["grpByList"]).each(function(i, info){
                html += '<option value="' + info["key"] + '">' + info["val"] + '</option>'
            });
            $("#showAddrList").append(html);
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

//선택이동 버튼
function onBtnSelectMoveClick() {
    selectAddr = $("#showAddrList option:selected");

    $("#moveCopyList").append(selectAddr);
    
    var Deduplication = [];
    $("#moveCopyList option").each(function() {
        if($.inArray(this.value, Deduplication) != -1) {
            $(this).remove();
        }
        Deduplication.push(this.value);
    });
    onShowGrpListChange();
}

//선택취소 버튼
function onBtnSelectCancelClick() {
    var selectAddr = $("#moveCopyList option:selected");
    selectAddr.remove();
}

//이동 버튼
function onBtnMoveClick() {

    //작업 모드
    $("#mode").val("MOVE_ADDR");

    //이동 그룹 유무
    var valid = true;
    if($("#moveCopyGroupList").val() == "0|0") {
        valid = false;
        $("#resultMsg").removeClass("alert-primary");
        $("#resultMsg").addClass("alert-danger");
        $("#resultMsg").empty().html('이동/복사할 그룹을 선택하세요.').fadeIn();
        $("#resultMsg").delay(5000).fadeOut();
    } 
    //이동 명함 유무
    else if(!$("#moveCopyList option:eq(0)").val()) {
        valid = false;
        $("#resultMsg").removeClass("alert-primary");
        $("#resultMsg").addClass("alert-danger");
        $("#resultMsg").empty().html('이동/복사할 명함을 선택하세요.').fadeIn();
        $("#resultMsg").delay(5000).fadeOut();
    }
    
    if(valid) {
        var hidden = '';
        //리스트 항목 모두 선택하기
        $("#moveCopyList option").each(function() {
            $(this).prop('selected', true);
            hidden += '<input type="hidden" name="moveList[]" value="'+ $(this).val() + '" />';
        });
        $("#hiddenMove").empty().append(hidden);
    
        $.ajax({
            type: "POST",
            url: "/gw/mp/mp_address_list.php",
            data: $("#mainForm").serialize(),
            dataType: "json",
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                if(result["proceed"] == false) {
                    $("#resultMsg").removeClass("alert-primary");
                    $("#resultMsg").addClass("alert-danger");
                    $("#resultMsg").empty().html(result["msg"]).fadeIn();
                    $("#resultMsg").delay(5000).fadeOut();
                } else {
                    $("#resultMsg").removeClass("alert-danger");
                    $("#resultMsg").addClass("alert-primary");
                    $("#resultMsg").empty().html(result["msg"]).fadeIn();
                    $("#resultMsg").delay(5000).fadeOut();
                }
    
                onShowGrpListChange();
                $("#moveCopyList").empty();
                onConditionChange();
            },
            error: function(request, status, error) {
                alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
            }
        });
    }
}

//복사 버튼
function onBtnCopyClick() {
    //작업모드
    $("#mode").val("COPY_ADDR");

    //복사 그룹 유무
    var valid = true;
    if($("#moveCopyGroupList").val() == "0|0") {
        valid = false;
        $("#resultMsg").removeClass("alert-primary");
        $("#resultMsg").addClass("alert-danger");
        $("#resultMsg").empty().html('이동/복사할 그룹을 선택하세요.').fadeIn();
        $("#resultMsg").delay(5000).fadeOut();
    } 
    //복사 명함 유무
    else if(!$("#moveCopyList option:eq(0)").val()) {
        valid = false;
        $("#resultMsg").removeClass("alert-primary");
        $("#resultMsg").addClass("alert-danger");
        $("#resultMsg").empty().html('이동/복사할 명함을 선택하세요.').fadeIn();
        $("#resultMsg").delay(5000).fadeOut();
    }

    if(valid) {
        var hidden = '';
        //리스트 항목 모두 선택하기
        $("#moveCopyList option").each(function() {
            $(this).prop('selected', true);
            hidden += '<input type="hidden" name="copyList[]" value="'+ $(this).val() + '" />';
        });
        $("#hiddenCopy").empty().append(hidden);

        $.ajax({
            type: "POST",
            url: "/gw/mp/mp_address_list.php",
            data: $("#mainForm").serialize(),
            dataType: "json",
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                if(result["proceed"] == false) {
                    $("#resultMsg").removeClass("alert-primary");
                    $("#resultMsg").addClass("alert-danger");
                    $("#resultMsg").empty().html(result["msg"]).fadeIn();
                    $("#resultMsg").delay(5000).fadeOut();
                } else {
                    $("#resultMsg").removeClass("alert-danger");
                    $("#resultMsg").addClass("alert-primary");
                    $("#resultMsg").empty().html(result["msg"]).fadeIn();
                    $("#resultMsg").delay(5000).fadeOut();
                }
    
                onShowGrpListChange();
                $("#moveCopyList").empty();
                onConditionChange();
            },
            error: function(request, status, error) {
                alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
            }
        });
    }
}

//주소록 내보내기 버튼
function onBtnAddrOutClick() {
    $("#mainForm").attr("action","/gw/mp/mp_address_list_download_excel.php");
    $("#mainForm").submit();
    $("#mainForm").attr("action","/gw/mp/mp_address_list.php");
}

//주소록 가져오기 버튼
function onBtnAddrInClick() {
    //파일초기화
    $("#fileExcel").val('');
    $("#fileExcel").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
    //실패목록/오류메시지 숨기기
    $("#returnValue").hide();
    $("#errorMsg").hide();

    html = '';
    //개인
    if($('input[name="rbl_private"]:checked').val() == "0") {
        html = '<input type="radio" class="form-check-input" name="publicScopeUpload" value ="all" checked/>개인';
    }
    //공용
    else {
        html = '<input type="radio" class="form-check-input" name="publicScopeUpload" value ="all" checked/>전체';
    }
    $("#publicSopeRadio").empty().append(html);

    //업로드 공개범위
    $("#uploadPublicScope").hide();
    $("#uploadPublicScopeNms").val('');
    $("#uploadPublicScopeIds").val('');

    $('input[name=publicScopeUpload]').on('change', function() {
        if($('input[name=publicScopeUpload]:checked').val() == "all") {
            $("#uploadPublicScope").hide();
            $("#uploadPublicScopeNms").val('');
            $("#uploadPublicScopeIds").val('');
        } else {
            $("#uploadPublicScope").show();
        }
    });

    $("#modalUpload").modal("show");
}

//양식 다운로드 버튼
function onBtnFormDownloadClick() {
    window.location.href = "/gw/mp/mp_address_list_download_excel_form.php";
}

//업로드 버튼
function onBtnExcelUploadClick() {
    //작업 모드
    $("#mode").val("UPLOAD_ADDR");
    //업로드 버튼 비활성화
    $("#btnExcelUpload").prop('disabled', true);

    var proceed = true;
    var uploadFile = $("#fileExcel").val();
    //확장자분리
    var uploadExt = uploadFile.split('.').pop().toLowerCase();

    //파일이 없을 시
    if(!uploadFile) {
        proceed = false;
        $("#errorMsg").removeClass("alert-primary");
        $("#errorMsg").addClass("alert-danger");
        $("#errorMsg").empty().html('파일을 선택하세요.').fadeIn();
        $("#errorMsg").delay(5000).fadeOut();
    }
    //엑셀파일이 아닐 시
    else if ($.inArray(uploadExt, ['xlsx', 'xls']) == -1) {
        proceed = false;
        $("#errorMsg").removeClass("alert-primary");
        $("#errorMsg").addClass("alert-danger");
        $("#errorMsg").empty().html('엑셀파일을 선택하세요.').fadeIn();
        $("#errorMsg").delay(5000).fadeOut();
    }

    if(proceed) {
        var formdata = new FormData($("#mainForm")[0]);
        $.ajax({
            type: "POST",
            url: "/gw/mp/mp_address_list.php",
            data: formdata,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                var html = '';
                //저장이 안된 데이터가 있을 경우
                if(result["returnValueList"]) {
                    //알림메시지
                    $("#errorMsg").removeClass("alert-primary");
                    $("#errorMsg").addClass("alert-danger");
                    $("#errorMsg").empty().html('아래의 명함을 제외하여 저장되었습니다.').fadeIn();
                    $("#errorMsg").delay(5000).fadeOut();

                    //실패사유
                    $(result["returnValueList"]).each(function(i, info) {
                        html += '<tr class="row">';
                        html += '<td class="col-2">';
                        if(info["addrGrpNm"] == null || info["addrGrpNm"] == 'undefined') {
                            html += '';
                        }else{
                            html += info["addrGrpNm"];
                        }
                        html += '</td>';
                        html += '<td class="col-2">';
                        if(info["addrNm"] == null || info["addrNm"] == 'undefined') {
                            html += '';
                        }else{
                            html += info["addrNm"];
                        }
                        html += '</td>';
                        html += '<td class="col-2">';
                        if(info["addrComp"] == null || info["addrComp"] == 'undefined') {
                            html += '';
                        }else{
                            html += info["addrComp"];
                        }
                        html += '</td>';
                        html += '<td class="col-2">';
                        if(info["addrHp"] == null || info["addrHp"] == 'undefined') {
                            html += '';
                        }else{
                            html += info["addrHp"];
                        }
                        html += '</td>';
                        html += '<td class="col-2">';
                        if(info["addrMail"] == null || info["addrMail"] == 'undefined') {
                            html += '';
                        }else{
                            html += info["addrMail"];
                        }
                        html += '</td>';
                        html += '<td class="col-2">';
                        if(info["reason"] == null || info["reason"] == 'undefined') {
                            html += '';
                        }else{
                            html += info["reason"];
                        }
                        html += '</td>';
                        html += '</tr>';
                    });
                    $("#returnValue tbody").empty().append(html);
                    $("#returnValue").show();
                }
                //전부 저장되었을 경우
                else {
                    $("#errorMsg").removeClass("alert-danger");
                    $("#errorMsg").addClass("alert-primary");
                    $("#errorMsg").empty().html(result["msg"]).fadeIn();
                    $("#errorMsg").delay(5000).fadeOut();
                }
            },
            beforeSend: function() {
                $("#btnExcelUpload").find("span.spinner-border").show();
            },
            complete: function() {
                $("#btnExcelUpload").find("span.spinner-border").hide();
                onGroupListChange();
                onConditionChange();
                onShowGrpListChange();
            },
            error: function(request, status, error) {
                alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
            }
        });
    }
}
</script>
<form id="mainForm" name="mainForm" method="post" enctype="multipart/form-data" action="/gw/mp/mp_address_list.php">
<div class="btnList">
    <div>
        <button type="button" class="btn btn-primary" data-toggle="modal" id="btnAdd" name="btnAdd">등록</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" data-toggle="modal" id="btnChkDel" name="btnChkDel" disabled>삭제</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#modalMoveCopyAddr">이동/복사</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" id="btnFormDownload" name="btnFormDownload">양식 다운로드</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" data-toggle="modal" id="btnAddrIn" name="btnAddrIn">주소록 가져오기</button>
    </div>
    <div>
        <button type="button" class="btn btn-primary ml-2" id="btnAddrOut" name="btnAddrOut">주소록 내보내기</button>
    </div>
</div>

<div id="divSearch">
<div class="row">
    <div class="col-lg-4 search-inline mb-2" id="groupOption">
        <label class="control-label" for="rbl_private">그룹구분&nbsp;</label>
    </div>
    <div class="col-lg-4 search-inline mb-2 flex-row-reverse">
        <select class="form-control" id="ddlGroupList" name="ddlGroupList" style="flex: 0 1 auto !important">
        </select>
        <label class="control-label" for="ddlGroupList">그룹</label>
    </div>
    <div class="col-lg-4 search-inline mb-2">
        <div class="input-group">
            <div class="input-group-prepend">
                <select class="form-control prependDdlSearch" id="ddlSearchKind" name="ddlSearchKind">
                </select>
            </div>
            <input type="search" class="form-control" id="txtSearchValue" name="txtSearchValue" maxlength="50"/>
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

<div class="table-responsive">
<div class="tableFixHead">
<table class="table" id="tblAddrList">
    <thead class="thead-light">
        <tr>
            <th class="col-w-chk" style="white-space: nowrap;min-width: 3rem"><input type="checkbox" id="chkAll" onclick="onChkAllAddrClick(this)" /></th>
            <th class="col-w-btn" style="white-space: nowrap;min-width: 6rem">편집</th>
            <th style="white-space: nowrap;min-width: 7.5rem">그룹명</th>
            <th style="white-space: nowrap;min-width: 7.5rem">이름</th>
            <th style="white-space: nowrap;min-width: 10rem">직책(급)</th>
            <th style="white-space: nowrap;min-width: 15rem">회사명</th>
            <th style="white-space: nowrap;min-width: 15rem">부서명</th>
            <th style="white-space: nowrap;min-width: 15rem">휴대전화</th>
            <th style="white-space: nowrap;min-width: 15rem">회사번호</th>
            <th style="white-space: nowrap;min-width: 10rem">E-MAIL</th>
            <th style="white-space: nowrap;min-width: 30rem">주소</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
</div>

<ul class="pagination justify-content-center" id="pageList" name="pageList">
</ul>

<!-- The Modal -->
<div class="modal fade modalMain" id="modalIuAddr" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle"></h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body mainContents">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row form-group">
                            <div class="col-3 colHeader">
                                <label for="ddlGroup">그룹명</label><span class="necessaryInput"> *</span>
                            </div>
                            <div class="col-9">
                                <select class="form-control validateElement" id="ddlGroup" name="ddlGroup" required>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-3 colHeader">
                                <label for="addrNm">이름</label><span class="necessaryInput"> *</span>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control validateElement" id="addrNm" name="addrNm" maxlength="100" required />
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 colHeader">
                                <label for="addrComp">회사명</label>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control" id="addrComp" name="addrComp" maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 colHeader">
                                <label for="addrDept">부서명</label>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control" id="addrDept" name="addrDept" maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 colHeader">
                                <label for="addrDuty">직책(급)</label>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control" id="addrDuty" name="addrDuty" maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 colHeader">
                                <label for="addrHp">휴대전화</label>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control" id="addrHp" name="addrHp" maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 colHeader">
                                <label for="addrTelNo">회사번호</label>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control" id="addrTelNo" name="addrTelNo" maxlength="50"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 colHeader">
                                <label for="addrFaxNo">팩스번호</label>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control" id="addrFaxNo" name="addrFaxNo" maxlength="50"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 colHeader">
                                <label for="addrMail">메일주소</label>
                            </div>
                            <div class="col-9">
                                <input type="text" class="form-control" id="addrMail" name="addrMail" maxlength="100"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row mb-2">
                            <div class="col-md-5 col-3 colHeader">
                                <label for="txtPhotoNM1">사진(120*160)</label>
                            </div>
                            <div class="col-md-7 col-9">
                                <img src="" id="imgPhoto" name="imgPhoto" height="160px" width="120px"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="input-group mb-2">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="filePhotoNm" name="filePhotoNm" onchange="onAttachFileChange(this)" accept="image/*"/>';
                                        <label class="custom-file-label" for="customFile"><i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요</label>
                                    </div>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-secondary" id="btnPhotoDel" name="btnPhotoDel" onclick="javascript:delAttachedFile(this);">&times;</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-3 colHeader">
                        <label for="addrZipCd">우편번호</label>
                    </div>
                    <div class="col-md-10 col-9 d-flex">
                        <div>
                            <input type="text" class="form-control" id="addrZipCd" name="addrZipCd" readonly onclick="execDaumPostcode(this);" maxlength="10" />
                        </div>
                        <div>
                            <button type="button" id="btnZip" name="btnZip" class="btn btn-info btn-sm ml-2 btnHeight" onclick="execDaumPostcode(this);">우편번호 찾기</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-3 colHeader">
                        <label for="addrZipAddr">우편주소</label>
                    </div>
                    <div class="col-md-10 col-9">
                        <input type="text" class="form-control" id="addrZipAddr" name="addrZipAddr" readonly maxlength="255"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-3 colHeader">
                        <label for="addrAddrDeail">상세주소</label>
                    </div>
                    <div class="col-md-10 col-9">
                        <input type="text" class="form-control" id="addrAddrDeail" name="addrAddrDeail" maxlength="255"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-3 colHeader">
                        <label for="addrEtc1">기타1</label>
                    </div>
                    <div class="col-md-10 col-9">
                        <input type="text" class="form-control" id="addrEtc1" name="addrEtc1" maxlength="100"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-3 colHeader">
                        <label for="addrEtc2">기타2</label>
                    </div>
                    <div class="col-md-10 col-9">
                        <input type="text" class="form-control" id="addrEtc2" name="addrEtc2" maxlength="100"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-3 colHeader">
                        <label for="addrNote">비고</label>
                    </div>
                    <div class="col-md-10 col-9">
                        <textarea class="form-control mb-2" rows="3" id="addrNote" name="addrNote" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-3 colHeader">
                        <label for="public_Box">공개범위</label>
                    </div>
                    <div class="col-md-10 col-9">
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
                <div class="container-fluid">
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

<div class="modal fade" id="modalMoveCopyAddr" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">명함이동/복사</h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <select class="form-control mb-2" id="showGroupList" name="showGroupList">
                        </select>
                        <select class="form-control" style="height:30rem!important;" id="showAddrList" name="showAddrList" multiple>
                        </select>
                    </div>
                    <div id="divBtnListMoveCopyAddr" class="col-md-1">
                        <button type="button" class="btn btn-info btn-sm my-2" id="btnSelectMove" name="btnSelectMove"><span class="fa-solid fa-angles-right"></span><span class="fa-solid fa-angles-down"></span>선택이동</button>
                        <button type="button" class="btn btn-warning btn-sm my-2" id="btnSelectCancel" name="btnSelectCancel"><span class="fa-solid fa-angles-left"></span><span class="fa-solid fa-angles-up"></span>선택취소</button>
                    </div>
                    <div class="col-md-6">
                        <select class="form-control mb-2" id="moveCopyGroupList" name="moveCopyGroupList">
                        </select>
                        <select class="form-control mb-2" style="height:30rem!important;" id="moveCopyList" name="moveCopyList" multiple>
                        </select>
                        <div>
                            <label>중복체크</label>
                            <div class="form-check-inline">
                                <label class="form-check-label" for="check1">
                                    <input type="checkbox" class="form-check-input" id="chName" name="chName" value="chName" checked>이름
                                </label>
                            </div>
                            <div class="form-check-inline">
                                <label class="form-check-label" for="check2">
                                    <input type="checkbox" class="form-check-input" id="chHp" name="chHp" value="chHp">휴대전화
                                </label>
                            </div>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" id="chMail" name="chMail" value="chMail">메일주소
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mr-auto">
                    <div id="resultMsg" class="alert alert-danger py-1 mb-0" style="display: none;"></div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-primary" id="btnMove" name="btnMove">이동</button>
                        <button type="button" class="btn btn-primary" id="btnCopy" name="btnCopy">복사</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- The Modal -->
<div class="modal fade" id="modalImgConfirmDel" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <!-- Modal body -->
            <div class="modal-body">
                <p>설정된 이미지를 제거 하시겠습니까?</p>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" id="btnConfirmImgDel" class="btn btn-primary">네</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">아니오</button>
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
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" id="btnConfirmDel" class="btn btn-primary">네</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">아니오</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUpload" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">주소록 가져오기</h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body mainContents">
                <!-- <div class="row">
                    <div class="col-3 colHeader">양식 다운로드</div>
                    <div class="col-9">
                        <button type="button" class="btn btn-warning btn-sm py-0 ml-2" id="btnFormDownload" name="btnFormDownload"><i class="fa-solid fa-file-excel"></i> 양식 다운로드</button>
                    </div>
                </div> -->
                <div class="row">
                    <div class="col-3 colHeader">엑셀파일 등록</div>
                    <div class="col-9">
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="fileExcel" name="fileExcel" onchange="onAttachFileChange(this)" accept="application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"/>
                                <label class="custom-file-label" for="customFile"><i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요</label>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-secondary" id="btnExcelDel" name="btnExcelDel" onclick="javascript:delAttachedFile(this);">&times;</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3 colHeader">공개범위</div>
                    <div class="col-9 form-inline">
                        <div class="form-check-inline">
                            <label class="form-check-label" id="publicSopeRadio">
                            </label>
                        </div>
                        <div class="form-check-inline">
                            <label class="form-check-label">
                                <input type="radio" class="form-check-input" name="publicScopeUpload" value ="auth" />선택
                            </label>
                        </div>
                        <div class="input-group" id="uploadPublicScope" style="-ms-flex: 1 1 auto !important;flex: 1 1 auto !important;">
                            <input type="text" class="form-control" id="uploadPublicScopeNms" name="uploadPublicScopeNms" readonly />
                            <input type="hidden" id="uploadPublicScopeIds" name="uploadPublicScopeIds" />
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" onclick="onBtnSelectMultiDeptUserClick('MP', 'uploadPublicScope', 'Y', 0)">선택</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="errorMsg" class="alert alert-danger" style="display: none;"></div>
                <div id="returnValue" style="display: none;">
                    <table class="table">
                        <thead class="thead-light">
                            <tr class="row">
                                <th class="col-2">그룹명</th>
                                <th class="col-2">이름</th>
                                <th class="col-2">회사명</th>
                                <th class="col-2">휴대전화</th>
                                <th class="col-2">메일주소</th>
                                <th class="col-2">사유</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-primary" id="btnExcelUpload" name="btnExcelUpload">
                            업로드&nbsp;<span class="spinner-border spinner-border-sm" style="display: none;"></span>
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../cm/cm_select_multi_dept_user_view.php';
?>

<div id="hiddenMove"></div>
<div id="hiddenCopy"></div>
<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="modifyAddrId" name="modifyAddrId" />
<input type="hidden" id="deleteAddrId" name="deleteAddrId" />
<input type="hidden" id="modifyGrpId" name="modifyGrpId" />
<input type="hidden" id="dbMode" name="dbMode" />
<input type="hidden" id="photoNm" name="photoNm" />
<input type="hidden" id="pageNo" name="pageNo" value="1" />
<input type="hidden" id="delMode" name="delMode" />
</form>
