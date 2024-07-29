<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<style>
/* Hide Input Number Arrows */
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}

@media (min-width: 992px) {
    .btnHeight {
        height:24.5px;
        display: flex;
        align-items: center;
    }
}

@media (max-width: 575px) {
    .btn-confirm {
        text-align: center;
        padding-top: 0.5rem;
    }
}
</style>
<script>
$(document).ready(function() {
    //날짜 min, max값 넣기
    dateMinMaxAppend();

    //비밀번호 체크 전 메인 폼 숨기기
    $("#mainUserForm").hide();

    //비밀번호 확인 버튼 submit 방지
    $("#pwdCheck").on("keydown", function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            onBtnPwdCheckClick();
        }

        //스페이스바 입력 방지
        if (event.keyCode === 32) {
            return false;
        }
    });

    //작업모드
    $("#mode").val("INIT");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp0100400.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //지역번호
            var html = "";
            $(result["areaCodeList"]).each(function(i, info) {
                html += '<option value="' + info["key"] + '">' + info["val"] + '</option>';
            });
            $("#coTel1").append(html);
            $("#fax1").append(html);
            $("#tel1").append(html);

            //핸드폰 앞자리
            var html = "";
            $(result["mobileList"]).each(function(i, info) {
                html += '<option value="' + info["key"] + '">' + info["val"] + '</option>';
            });
            $("#mobile1").append(html);

            //성별
            var html = "";
            $(result["genderList"]).each(function(i, info) {
                html += '<div class="form-check-inline">';
                html += '<label class="form-check-label">'; 
                html += '<input type="radio" id="sex_' + info["key"] + '" class="form-check-input" name="sex" value="' + info["key"] + '">' + info["val"];
                html += '</label>';
                html += '</div>'
            });
            $("#radioSex").append(html);

            //음력구분
            var html = "";
            $(result["lunarList"]).each(function(i, info) {
                html += '<div class="form-check-inline">';
                html += '<label class="form-check-label">'; 
                html += '<input type="radio" id="lunar_' + info["key"] + '" class="form-check-input" name="lunar" value="' + info["key"] + '">' + info["val"];
                html += '</label>';
                html += '</div>'
            });
            $("#lunar").append(html);

            //회사
            var html = "";
            $(result["companyList"]).each(function(i, info) {
                html += '<option value="' + info["key"] + '">' + info["val"] + '</option>';
            });
            $("#ddlMainLoginCompany").append(html);

            //비밀번호 입력 규칙
            $("#eOption_CM_PW_Chk2").val(result["passwordOptionList"]["eOption_CM_PW_Chk2"]);
            $("#eOption_CM_PW_Chk3").val(result["passwordOptionList"]["eOption_CM_PW_Chk3"]);

            var pwdLengthRule = result["passwordOptionList"]["eOption_CM_PW_Chk1"];
            if(pwdLengthRule == "0") {
                $("#pwdLengthRule").prepend("4 ~ 30자 ")
            } else if(pwdLengthRule == "1") {
                $("#pwdLengthRule").prepend("8 ~ 30자 ")
            }

            $("#pwdCheck").data('oldVal', "");
        },
        complete: function() {
            showinfoDetail();

            //유효성 검사
            addValidateElementToInputs("pwdCheckForm");
            addValidateElementToInputs("modalChangePassword");
        }
    })

    //결혼유무에 따른 라디오버튼 제어
    $("input:radio[name=marryYn]").click(function(){
        if($("input[name=marryYn]:checked").val() == "1"){
            $("#marryDt").prop("disabled",false);
            //오늘 일자
            var today = new Date().toISOString().substring(0, 10);
            $("#marryDt").val(today);
        }else if($("input[name=marryYn]:checked").val() == "0"){
            $("#marryDt").prop("disabled",true);
            $("#marryDt").val('');
        }
        validateMarryDate();
    });

    $("#marryDt").on("propertychange change keyup input paste", function(event) {
        validateMarryDate();
    });

    //좌동 클릭
    $("#btnSameBirth").on('click', function() {
        $("#lawBirthDt").val($("#birthDt").val());
        var val = $("#lawBirthDt").val();

        if(val) {
            $("#lawBirthDt").removeClass('is-valid is-invalid');
            $("#lawBirthDt").closest(".form-group").find(".invalid-feedback").html("");
        }
    });

    //invalid 지우기
    $("#lawBirthDt").on('change', function() {
        var val = $("#lawBirthDt").val();

        if(val) {
            $("#lawBirthDt").removeClass('is-valid is-invalid');
            $("#lawBirthDt").closest(".form-group").find(".invalid-feedback").html("");
        }
    });

    //비밀번호 포커스
    $("#pwdCheck").focus();

    //저장 버튼
    $("#btnSaveUserInfo").on("click", onBtnSaveUserInfoClick);
    //상동 버튼
    $("#btnSameAddr").on("click", onBtnSameAddrClick);
    //변경 버튼 (로그인 비밀번호)
    $("#btnLoginPwd").on("click", onBtnLoginPwdClick);
    //변경 버튼 (전자결재 비밀번호)
    $("#btnPaymentPwd").on("click", onBtnPaymentPwdClick);
    //비밀번호 변경 - 저장 버튼
    $("#btnSavePwd").on("click", onBtnSavePwdClick);
    //비밀번호 확인 버튼
    $("#btnPwdCheck").on("click", onBtnPwdCheckClick);
    //초기화 버튼
    $("#btnResetUserInfo").on('click', showinfoDetail);
});

//우편번호 찾기
function execDaumPostcode(obj) {

    var objId = obj.id;

    //주민등록상 주소
    if(objId == "zipCd" || objId == "btnZip" || objId == "coZipAddr") {
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
                $("#zipCd").val(data.zonecode);
                $("#zipAddr").val(roadAddr);
                // 참고항목 문자열이 있을 경우 해당 필드에 넣는다.
                if(extraRoadAddr !== ''){
                    $("#detailAddr").val(extraRoadAddr);
                } 
                else {
                    $("#detailAddr").val('');
                }
            }
        }).open();
    } 

    //실거주지주소
    else {
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
                $("#coZipCd").val(data.zonecode);
                $("#coZipAddr").val(roadAddr);
                // 참고항목 문자열이 있을 경우 해당 필드에 넣는다.
                if(extraRoadAddr !== ''){
                    $("#coDetailAddr").val(extraRoadAddr);
                } 
                else {
                    $("#coDetailAddr").val('');
                }
            }
        }).open();
    }
}

//데이터 불러오기
function showinfoDetail() {

    //작업모드
    $("#mode").val("DETAIL");

    $.ajax({
        type: "POST",
        url: "/gw/mp/mp0100400.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            //아이디
            var logonCd = result["userInfoList"]["logonCd"];
            $("#logonCd").text(logonCd);
            //성명
            var userNm = result["userInfoList"]["userNm"];
            $("#userNm").text(userNm);
            //부서
            var deptNm = result["userDeptList"]["deptNm"];
            $("#deptNm").text(deptNm);
            //직급
            var gradeNm = result["userDeptList"]["gradeNm"];
            $("#gradeNm").text(gradeNm);
            //메일주소
            var emailId = result["userDeptList"]["emailId"];
            $("#emailId").text(emailId);
            //입사일
            var enterDt = result["userDeptList"]["enterDt"];
            var enterYear = enterDt.substring(0, 4);
            var enterMonth = enterDt.substring(4, 6);
            var enterDay = enterDt.substring(6, 8);
            var enterDate = enterYear + '-' + enterMonth + '-' + enterDay;
            $("#enterDt").text(enterDate);
            //성별
            var sex = result["userInfoList"]["sex"];
            $("#sex_" + sex).prop('checked', true);
            //회사전화
            var cotel1 = result["userDeptList"]["cotel1"];
            var cotel2 = result["userDeptList"]["cotel2"];
            var cotel3 = result["userDeptList"]["cotel3"];
            var cotel4 = result["userDeptList"]["cotel4"];
            $("#coTel1").val(cotel1).prop("selected", true);
            $("#coTel2").val(cotel2);
            $("#coTel3").val(cotel3);
            $("#coTel4").val(cotel4);
            //팩스
            var fax1 = result["userDeptList"]["fax1"];
            var fax2 = result["userDeptList"]["fax2"];
            var fax3 = result["userDeptList"]["fax3"];
            if(fax2 || fax3) {
                $("#fax1").val(fax1).prop("selected", true);
            } else {
                $("#fax1").val('061').prop("selected", true);
            }
            $("#fax2").val(fax2);
            $("#fax3").val(fax3);
            //생년월일
            var birthDt = result["userInfoList"]["birthDt"];
            var birthDate = changeInputDateFormat(birthDt);
            $("#birthDt").val(birthDate);
            
            //법정생일
            var lawBirthDt = result["userInfoList"]["lawBirthDt"];
            var lawBirthDate = changeInputDateFormat(lawBirthDt);
            $("#lawBirthDt").val(lawBirthDate);

            var lunar = result["userInfoList"]["lunar"];
            $("#lunar_" + lunar).prop('checked', true);
            //결혼여부
            var marryYn = result["userInfoList"]["marryYn"];
            if(marryYn == 0) {
                $("#marryNo").prop('checked', true);
                $("#marryDt").prop('disabled', true);
            } else {
                $("#marryYes").prop('checked', true);
                $("#marryDt").prop('disabled', false);

                var marryDt = result["userInfoList"]["marryDt"];
                var marryDate = changeInputDateFormat(marryDt);
                $("#marryDt").val(marryDate);
            }
            //담당업무
            var charBiz = result["userInfoList"]["charBiz"];
            $("#charBiz").val(charBiz);
            //본인 핸드폰
            var mobile1 = result["userInfoList"]["mobile1"];
            var mobile2 = result["userInfoList"]["mobile2"];
            var mobile3 = result["userInfoList"]["mobile3"];
            $("#mobile1").val(mobile1).prop("selected", true);
            $("#mobile2").val(mobile2);
            $("#mobile3").val(mobile3);
            //비상 연락망
            var tel1 = result["userInfoList"]["tel1"];
            var tel2 = result["userInfoList"]["tel2"];
            var tel3 = result["userInfoList"]["tel3"];
            $("#tel1").val(tel1).prop("selected", true);
            $("#tel2").val(tel2);
            $("#tel3").val(tel3);
            //주민등록상 주소
            var zipCd = result["userInfoList"]["zipCd"];
            var zipAddr = result["userInfoList"]["zipAddr"];
            var detailAddr = result["userInfoList"]["detailAddr"];
            $("#zipCd").val(zipCd);
            $("#zipAddr").val(zipAddr);
            $("#detailAddr").val(detailAddr);
            //실거주지 주소
            var coZipCd = result["userDeptList"]["coZipCd"];
            var coZipAddr = result["userDeptList"]["coZipAddr"];
            var coDetailAddr = result["userDeptList"]["coDetailAddr"];
            $("#coZipCd").val(coZipCd);
            $("#coZipAddr").val(coZipAddr);
            $("#coDetailAddr").val(coDetailAddr);
            //기본로그인회사
            var mainloginCoid = result["userInfoList"]["mainloginCoid"];
            $("#ddlMainLoginCompany").val(mainloginCoid).prop("selected", true);
            //사진
            var imgPhoto = result["userInfoList"]["photoNm"];
            var imgPhotoRoot = imgPhoto.split("/").pop();

            if(imgPhoto) {
                $("#imgPhoto").attr("src", imgPhoto);
                $("#photoNm").val(imgPhotoRoot);
            } else {
                $("#imgPhoto").attr('src', '/gw/images/mp/nosign.gif');
            }
            //사인
            var imgSign = result["userInfoList"]["signNm"];
            var imgSignRoot = imgSign.split("/").pop();
            if(imgSign) {
                $("#imgSign").attr("src", imgSign);
                $("#signNm").val(imgSignRoot);
            } else {
                $("#imgSign").attr('src', '/gw/images/mp/nosign.gif');
            }
            //차량 정보
            var vehicleNum1 = result["userInfoList"]["vehicleNum1"];
            var vehicleNum2 = result["userInfoList"]["vehicleNum2"];
            var insuranceNm = result["userInfoList"]["insuranceNm"];

            $("#vehicleNum1").val(vehicleNum1);
            $("#vehicleNum2").val(vehicleNum2);
            $("#insuranceNm").val(insuranceNm);

            //사진파일명 초기화
            $(".custom-file-label").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
        }
    })
}

//첨부파일 선택 시
function onAttachFileChange(obj) {
    var fileName = $(obj).val().split("\\").pop();
    var fileExt = fileName.split('.').pop().toLowerCase();
    $(obj).siblings(".custom-file-label").addClass("selected").html(fileName);

    $(obj).removeClass('is-valid is-invalid');
    $(obj).closest(".form-group").find(".invalid-feedback").html("");

    //이미지 미리보기
    if (obj.files && obj.files[0]) {
        var reader = new FileReader();

        //이미지 파일 체크
        if($.inArray(fileExt, ['jpg','gif','png','jpeg','bmp']) == -1) {
            $("#" + obj.id).addClass("is-invalid");
            $("#" + obj.id).closest(".form-group").find(".invalid-feedback").html('이미지 파일을 선택하세요.');
            $("#" + obj.id).closest(".form-group").find(".invalid-feedback").show();
            $('#imgChk').val('false');
        } else {
            reader.onload = function(e) {
                //사진
                if (obj.id == "filePhotoNm") {
                    $("#imgPhoto").attr('src', e.target.result);
                }
                //사인 
                else {
                    $("#imgSign").attr('src', e.target.result);
                }
                $('#imgChk').val('true');
            }
            reader.readAsDataURL(obj.files[0]);
        }
    }
}

//첨부파일 삭제
function delAttachedFile(obj) {
    objId = obj.id;
    
    //사진,사인 유무에 따른 모달창 보이기
    if(objId == "btnPhotoDel") {
        if($("#filePhotoNm").val() || $("#photoNm").val()) {
            $("#modalConfirmDel").modal('show');
        }
    }else {
        if($("#fileSignNm").val() || $("#signNm").val()) {
            $("#modalConfirmDel").modal('show');
        }
    }

    $("#btnConfirmDel").on('click', function() {
        $("#modalConfirmDel").modal('hide');
        //사진 삭제
        if(objId == "btnPhotoDel") {
            $("#photoNm").val('');
            $("#filePhotoNm").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
            $("#imgPhoto").attr('src', '/gw/images/mp/nosign.gif');
            $("#filePhotoNm").removeClass('is-valid is-invalid');
            $("#filePhotoNm").closest(".form-group").find(".invalid-feedback").html("");
            $('#imgChk').val('true');
        }
        //사인 삭제
        else {
            $("#signNm").val('');
            $("#fileSignNm").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
            $("#imgSign").attr('src', '/gw/images/mp/nosign.gif');
            $("#fileSignNm").removeClass('is-valid is-invalid');
            $("#fileSignNm").closest(".form-group").find(".invalid-feedback").html("");
            $('#imgChk').val('true');
        }
    })
}

//저장 버튼
function onBtnSaveUserInfoClick() {
    if (validateMarryDate() && $("#imgChk").val() == 'true' && validateElement("lawBirthDt")) {
        //작업모드
        $("#mode").val("SAVE");

        var formdata = new FormData($("#mainForm")[0]);

        $.ajax({
            type: "POST",
            url: "/gw/mp/mp0100400.php",
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

                $("#resultMsg").empty().html(result["msg"]).fadeIn();
                $("#resultMsg").delay( 5000 ).fadeOut();
            }
        });
    }
}

//결혼기념일 유효성 검사
function validateMarryDate() {
    var id = "marryDt";
    var name = "결혼기념일";
    var obj = document.getElementById(id);
    //기혼
    if($("input[name=marryYn]:checked").val() == "1") {
        if (!checkRequired(obj.value)) {
            obj.setCustomValidity(name + "은(는) 필수 입력입니다.");
        }
        else {
            obj.setCustomValidity("");
        }
        //유효한 경우
        if (obj.validity.valid) {
            $(obj).closest(".form-group").find(".invalid-feedback").html("");
            $(obj).removeClass('is-valid is-invalid');
        }
        //유효하지 않을 경우
        else {
            //에러 메시지 표시
            $(obj).closest(".form-group").find(".invalid-feedback").html(obj.validationMessage);
            $(obj).addClass("is-invalid");
        }
    }
    //미혼
    else {
        obj.setCustomValidity("");
        $(obj).closest(".form-group").find(".invalid-feedback").html("");
        $(obj).removeClass('is-valid is-invalid');
    }

    return obj.validity.valid;
}

//상동 버튼
function onBtnSameAddrClick() {
    $("#coZipCd").val($("#zipCd").val());
    $("#coZipAddr").val($("#zipAddr").val());
    $("#coDetailAddr").val($("#detailAddr").val());
}

//로그인 비밀번호 변경
function onBtnLoginPwdClick() {
    $("#headerPwd").html("> 로그인 비밀번호 변경")
    $("#pwdModifyMode").val(1);
    //모달 값 초기화
    $("#existPwd").val('');
    $("#newPwd").val('');
    $("#newPwdCheck").val('');
    //유효성검사 지우기
    $("#modalChangePassword .validateElement").each(function() {
        var id = $(this).attr("id");
        $("#" + id).removeClass('is-valid is-invalid');
        $("#" + id).closest(".form-group").find(".invalid-feedback").html("");
    });

    $("#modalChangePassword").modal('show');
}

//전자결재 비밀번호 변경
function onBtnPaymentPwdClick() {
    $("#headerPwd").html("> 전자결재 비밀번호 변경")
    $("#pwdModifyMode").val(2);
    //모달 값 초기화
    $("#existPwd").val('');
    $("#newPwd").val('');
    $("#newPwdCheck").val('');
    //유효성검사 지우기
    $("#modalChangePassword .validateElement").each(function() {
        var id = $(this).attr("id");
        $("#" + id).removeClass('is-valid is-invalid');
        $("#" + id).closest(".form-group").find(".invalid-feedback").html("");
    });

    $("#modalChangePassword").modal('show');
}

//유효성 검사 (비밀번호 변경)
function validatePwdInputs() {
    var valid = true;

    //휴가코드
    valid = valid & validateElement("existPwd");

    //휴가명
    valid = valid & validateElement("newPwd");

    //사용연차
    valid = valid & validateElement("newPwdCheck");

    return valid;
}

//비밀번호 변경 - 저장 버튼
function onBtnSavePwdClick() {

    //작업모드
    $("#mode").val("SAVE_PASSWORD");
    var proceed;
    proceed = true;

    if(validatePwdInputs()) {

        //글자 수 확인
        $(".lengthCheck").each(function(){
            var pwdLength = $(this).val();

            if(pwdLength.length < 4 || pwdLength.length > 16) {
                proceed = false;
                $(this).addClass("is-invalid");
                $(this).closest(".form-group").find(".invalid-feedback").html("4 ~ 16자 의 영문 대소문자, 숫자, 특수문자를 입력하세요.");
                $(this).closest(".form-group").find(".invalid-feedback").show();
            }
        });

        if(proceed) {
            //비밀번호 확인
            if(!($("#newPwd").val() == $("#newPwdCheck").val())) {
                proceed = false;
                $("#newPwdCheck").addClass("is-invalid");
                $("#newPwdCheck").closest(".form-group").find(".invalid-feedback").html("비밀번호가 일치하지 않습니다.");
                $("#newPwdCheck").closest(".form-group").find(".invalid-feedback").show();
            }
        }

        if(proceed) {
            $.ajax({
                type: "POST",
                url: "/gw/mp/mp0100400.php",
                data: $("#mainForm").serialize(),
                dataType: "json",
                success: function(result) {
                    //세션 만료일 경우
                    if (result["session_out"]) {
                        //로그인 화면으로 이동
                        onLogoutClick();
                    }

                    //변경되었을 경우
                    if(result["return_value"] == 1) {
                        $("#resultPwdMsg").addClass("alert-primary");
                        $("#resultPwdMsg").removeClass("alert-danger");
                        $("#resultPwdMsg").empty().html(result["msg"]).fadeIn();
                        $("#resultPwdMsg").delay( 5000 ).fadeOut();

                        $("#modalChangePassword").find("input").val('');
                    } 
                    //변경이 안될경우
                    else {
                        $("#resultPwdMsg").addClass("alert-danger");
                        $("#resultPwdMsg").removeClass("alert-primary");
                        $("#resultPwdMsg").empty().html(result["msg"]).fadeIn();
                        $("#resultPwdMsg").delay( 5000 ).fadeOut();
                    }
//                 },
//                 complete: function() {
                    
                }
            });
        }
    }
}

//비밀번호 확인
function onBtnPwdCheckClick() {
    //작업모드
    $("#mode").val("CHECK_PASSWORD");

    if(validateElement("pwdCheck")) {
        $.ajax({
            type: "POST",
            url: "/gw/mp/mp0100400.php",
            data: $("#mainForm").serialize(),
            dataType: "json",
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                if(result["proceed"] == false) {
                    $("#pwdCheck").addClass("is-invalid");
                    $("#pwdCheck").closest(".form-group").find(".invalid-feedback").html(result["msg"]);
                    $("#pwdCheck").closest(".form-group").find(".invalid-feedback").show();
                } else {
                    $("#pwdCheckForm").hide();
                    $("#mainUserForm").show();
                }
            },
        });
    }
}

// input date 형식으로 수정
function changeInputDateFormat(date) {
    var inputDate = '';
    if(date) {
        var dateYear = date.substring(0, 4);
        var dateMonth = date.substring(4, 6);
        var dateDay = date.substring(6, 8);
        inputDate = dateYear + '-' + dateMonth + '-' + dateDay;
    }
    return inputDate;
}

</script>
<form id="mainForm" name="mainForm" method="post" enctype="multipart/form-data">
<!-- 패스워드 확인 -->
<div id="pwdCheckForm">
    <div class="container-sm my-5 border" style="max-width:900px;padding:5%">
        <div class="row form-group">
            <div class="col-3">
                <img src="/gw/images/mp/padlock.png" width="100%">
            </div>
            <div class="col-9">
                <h5><i class="fa-solid fa-right-long"></i>&nbsp;패스워드를 입력하세요.</h5>
                <div class="row">
                    <div class="col-sm-8">
                        <label for="pwdCheck" style="display:none">패스워드</label>
                        <input type="password" class="form-control validateElement" id="pwdCheck" name="pwdCheck" maxlength="100" autocomplete="new-password" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-sm-4 btn-confirm">
                        <button type="button" class="btn btn-sm btn-primary btn-confirm" id="btnPwdCheck" name="btnPwdCheck">확인</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 메인 폼 -->
<div id="mainUserForm">
    <div class="btnList">
        <div>
            <button type="button" class="btn btn-primary" id="btnResetUserInfo" name="btnResetUserInfo">초기화</button>
            <button type="button" class="btn btn-primary" id="btnSaveUserInfo" name="btnSaveUserInfo">저장</button>
        </div>
    </div>
    <div id="resultMsg" class="alert alert-primary py-1 mb-2" style="display: none;"></div>
<div class="mainContents">
    <div class="row">
        <div class="col-md-8">
            <div class="row">
                <div class="col-3 colHeader">아이디</div>
                <div class="col-9" id="logonCd">
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">성명</div>
                <div class="col-9" id="userNm">
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">부서</div>
                <div class="col-9" id="deptNm">
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">직급</div>
                <div class="col-9" id="gradeNm">
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">메일주소</div>
                <div class="col-9" id="emailId">
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">로그인 비밀번호</div>
                <div class="col-9">
                    <button type="button" class="btn btn-info btn-sm py-0 ml-2" id="btnLoginPwd" name="btnLoginPwd">변경</button>
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">전자결재 비밀번호</div>
                <div class="col-9">
                    <button type="button" class="btn btn-info btn-sm py-0 ml-2" id="btnPaymentPwd" name="btnPaymentPwd">변경</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-md-4 col-3 colHeader">사진(120*160)</div>
                <div class="col-md-8 col-9">
                    <img src="" height="160px" width="120px" id="imgPhoto" name="imgPhoto"/>
                </div>
            </div>
            <div class="row form-group">
                <div class="col">
                    <div class="input-group mb-2">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input form-control validateElement" id="filePhotoNm" name="filePhotoNm" onchange="onAttachFileChange(this)" accept="image/*"/>';
                            <label class="custom-file-label" for="customFile"><i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요</label>
                        </div>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="btnPhotoDel" name="btnPhotoDel" onclick="javascript:delAttachedFile(this);">&times;</button>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="row">
                <div class="col-3 colHeader">입사일</div>
                <div class="col-9" id="enterDt">
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">성별</div>
                <div class="col-9" id="radioSex">
                    
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">전화</div>
                <div class="col-9 d-flex">
                    <select class="form-control" id="coTel1" name="coTel1">
                    </select>
                    <input type="number" class="form-control" id="coTel2" name="coTel2" maxlength="4" oninput="maxLengthCheck(this)"/>
                    <input type="number" class="form-control" id="coTel3" name="coTel3" maxlength="4" oninput="maxLengthCheck(this)"/>
                    <input type="number" class="form-control" id="coTel4" name="coTel4" maxlength="6" oninput="maxLengthCheck(this)" style="display: none;"/>
                </div>
            </div>
            <div class="row">
                <div class="col-3 colHeader">팩스</div>
                <div class="col-9 d-flex">
                    <select class="form-control" id="fax1" name="fax1">
                    </select>
                    <input type="number" class="form-control" id="fax2" name="fax2" maxlength="4" oninput="maxLengthCheck(this)"/>
                    <input type="number" class="form-control" id="fax3" name="fax3" maxlength="4" oninput="maxLengthCheck(this)"/>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-md-4 col-3 colHeader">사인(49*49)</div>
                <div class="col-md-8 col-9">
                    <img src="" height="49px" id="imgSign" name="imgSign"/>
                </div>
            </div>
            <div class="row form-group">
                <div class="col">
                    <div class="input-group mb-2">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input form-control validateElement" id="fileSignNm" name="fileSignNm" onchange="onAttachFileChange(this)" accept="image/*"/>
                            <label class="custom-file-label" for="customFile"><i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요</label>
                        </div>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="btnSignDel" name="btnSignDel" onclick="javascript:delAttachedFile(this);">&times;</button>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 col-3 colHeader">실제 생년월일</div>
                <div class="col-md-4 col-5">
                    <input type="date" class="form-control" id="birthDt" name="birthDt"/>
                </div>
                <div class="col-md-4 col-4" id="lunar"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row form-group">
                <div class="col-md-4 col-3 colHeader">법정 생년월일</div>
                <div class="col-md-4 col-9">
                    <div class="d-flex">
                        <input type="date" class="form-control validateElement" id="lawBirthDt" name="lawBirthDt" required/>
                        <button type="button" class="btn btn-sm btn-warning mx-2 btnHeight" id="btnSameBirth" name="btnSameBirth">좌동</button>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 col-3 colHeader">결혼여부</div>
                <div class="col-md-8 col-9">
                    <div class="form-check-inline">
                        <label class="form-check-label">
                            <input type="radio" id="marryNo" name="marryYn" class="form-check-input" value="0" />미혼
                        </label>
                    </div>
                    <div class="form-check-inline">
                        <label class="form-check-label">
                            <input type="radio" id="marryYes" name="marryYn" class="form-check-input" value="1" />기혼
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row form-group">
                <div class="col-md-4 col-3 colHeader">결혼기념일</div>
                <div class="col-md-8 col-9 d-flex">
                    <input type="date" class="form-control" id="marryDt" name="marryDt" />
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">담당업무</div>
        <div class="col-md-10 col-9">
            <textarea class="form-control" rows="2" id="charBiz" name="charBiz" maxlength="100"></textarea>
        </div>
    </div>
    <h6 class="bg-secondary text-white p-1 mt-2">개인 연락처</h6>
    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 col-3 colHeader">본인 핸드폰</div>
                <div class="col-md-8 col-9 d-flex">
                    <select class="form-control" id="mobile1" name="mobile1">
                    </select>
                    <input type="number" class="form-control" id="mobile2" name="mobile2" maxlength="6" oninput="maxLengthCheck(this)"/>
                    <input type="number" class="form-control" id="mobile3" name="mobile3" maxlength="6" oninput="maxLengthCheck(this)"/>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 col-3 colHeader">비상연락처 (본인 제외)</div>
                <div class="col-md-8 col-9 d-flex">
                    <select class="form-control" id="tel1" name="tel1">
                    </select>
                    <input type="number" class="form-control" id="tel2" name="tel2" maxlength="6" oninput="maxLengthCheck(this)"/>
                    <input type="number" class="form-control" id="tel3" name="tel3" maxlength="6" oninput="maxLengthCheck(this)"/>
                </div>
            </div>
        </div>
    </div>
    <h6 class="bg-secondary text-white p-1">주민등록상 주소</h6>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">우편번호</div>
        <div class="col-md-10 col-9 d-flex">
            <div>
                <input type="text" class="form-control" id="zipCd" name="zipCd" readonly onclick="execDaumPostcode(this);" maxlength="10"/>
            </div>
            <div>
                <button type="button" id="btnZip" name="btnZip" class="btn btn-info btn-sm ml-2 btnHeight" onclick="execDaumPostcode(this);">우편번호 찾기</button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">기본주소</div>
        <div class="col-md-10 col-9">
            <input type="text" class="form-control" id="zipAddr" name="zipAddr" readonly onclick="execDaumPostcode(this);" maxlength="255"/>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">상세주소</div>
        <div class="col-md-10 col-9">
            <input type="text" class="form-control" id="detailAddr" name="detailAddr" maxlength="255"/>
        </div>
    </div>
    <h6 class="bg-secondary text-white p-1">실거주지 주소</h6>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">우편번호</div>
        <div class="col-md-10 col-9 d-flex">
            <div>
                <input type="text" class="form-control" id="coZipCd" name="coZipCd" readonly onclick="execDaumPostcode(this);" maxlength="10"/>
            </div>
            <div>
                <button type="button" id="btnCoZip" name="btnCoZip" class="btn btn-info btn-sm ml-2 btnHeight" onclick="execDaumPostcode(this);">우편번호 찾기</button>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-warning mx-2 btnHeight" id="btnSameAddr" name="btnSameAddr">상동</button>
            </div>
            <div>※ 회사숙소인 경우 숙소주소를 입력하세요.</div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">기본주소</div>
        <div class="col-md-10 col-9">
            <input type="text" class="form-control" id="coZipAddr" name="coZipAddr" readonly onclick="execDaumPostcode(this);" maxlength="255"/>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">상세주소</div>
        <div class="col-md-10 col-9">
            <input type="text" class="form-control" id="coDetailAddr" name="coDetailAddr" maxlength="255"/>
        </div>
    </div>
    <h6 class="bg-secondary text-white p-1">차량 정보</h6>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">차량 번호</div>
        <div class="col-md-4 col-3 d-flex">
            <input type="text" class="form-control mr-2" id="vehicleNum1" name="vehicleNum1" maxlength="30"/>,
            <input type="text" class="form-control ml-2" id="vehicleNum2" name="vehicleNum2" maxlength="30"/>
        </div>
        <div class="col-md-2 col-3 colHeader">종합보험 가입사</div>
        <div class="col-md-4 col-3 d-flex">
            <div>
                <input type="text" class="form-control" id="insuranceNm" name="insuranceNm" maxlength="30"/>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-3 colHeader">기본로그인회사</div>
        <div class="col-md-10 col-9">
            <select class="form-control" id="ddlMainLoginCompany" name="ddlMainLoginCompany">
                <option value="0">===설정안함===</option>
            </select>
        </div>
    </div>
</div>

    <!-- The Modal -->
    <div class="modal fade" id="modalChangePassword" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title" id="headerPwd" name="headerPwd"></h4>
                    <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="alert alert-secondary">현재 비밀번호를 입력한 후 새로 사용할 비밀번호를 입력하세요.</div>
                    <div>
                        <div id="resultPwdMsg" class="alert py-1 mb-0" style="display: none;"></div>
                    </div>
                    <div class="row form-group">
                        <div class="col-4 colHeader">
                            <label for="existPwd">기존 비밀번호</label><span class="necessaryInput"> *</span>
                        </div>
                        <div class="col-8">
                            <input type="password" class="form-control validateElement" id="existPwd" name="existPwd" maxlength="100" required />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-4 colHeader">
                            <label for="newPwd">변경 비밀번호</label><span class="necessaryInput"> *</span>
                        </div>
                        <div class="col-8">
                            <input type="password" class="form-control validateElement lengthCheck" id="newPwd" name="newPwd" maxlength="100" required />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-4 colHeader">
                            <label for="newPwdCheck">비밀번호 확인</label><span class="necessaryInput"> *</span>
                        </div>
                        <div class="col-8">
                            <input type="password" class="form-control validateElement lengthCheck" id="newPwdCheck" name="newPwdCheck" maxlength="100" required />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="alert alert-info" id="pwdLengthRule" name="pwdLengthRule">의 영문 대소문자, 숫자, 특수문자 혼용 사용할 수 있습니다.</div>
                    <div class="alert alert-info">아이디와 같은 비밀번호나 주민등록번호, 생일, 학번, 전화번호 등 개인정보와 관련된 숫자, 연속된 숫자, 동일 반복된 숫자 등 다른 사람이 쉽게 알아 낼 수 있는 비밀번호는 유출의 위험이 많습니다.</div>
                    <div class="alert alert-warning">쉬운 비밀번호나 자주 쓰는 사이트의 비밀번호가 같으면 도용되기 쉬우므로 주기적으로 바꿔쓰는 것이 좋습니다.</div>
                    <div class="alert alert-danger">로그인 비밀번호 변경 시, Hi-biz/근무평가 로그인 비밀번호도 변경됩니다.</div>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <div class="container">
                        <div class="d-flex justify-content-around">
                            <button type="button" class="btn btn-primary" id="btnSavePwd" name="btnSavePwd">저장</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div class="modal fade" id="modalConfirmDel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <!-- Modal body -->
                <div class="modal-body">
                    <p>설정된 이미지를 제거 하시겠습니까?</p>
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
</div>
<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="photoNm" name="photoNm" />
<input type="hidden" id="signNm" name="signNm" />
<input type="hidden" id="pwdModifyMode" name="pwdModifyMode" />
<input type="hidden" id="eOption_CM_PW_Chk2" name="eOption_CM_PW_Chk2" />
<input type="hidden" id="eOption_CM_PW_Chk3" name="eOption_CM_PW_Chk3" />
<input type="hidden" id="imgChk" name="imgChk" value="true"/>
</form>
