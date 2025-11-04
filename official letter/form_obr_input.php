<script type="text/javascript">
$(document).ready(function() {
    if(!$("#ono").val()) {
        $("#receiveDate").val(getTodayDate());
    }

    onChangeIssueDate();

    $("#receiveDate").on('change', onChangeIssueDate);
});

//유효성 검사
function validateInputsDetail() {
    var valid = true;

    valid = valid & validateElement("recipient");

    valid = valid & validateElement("reference");

    valid = valid & validateElement("outDocCd");

    valid = valid & validateElement("sendVal");

    valid = valid & validateElement("receiveDate");

    valid = valid & validateElement("title");

    valid = valid & validateElement("approvalKind");

    //첨부파일
    isAttach = $.trim($("#divAttachedList").html()) || $.trim($("#divNewAttachedList").html()) ? true : false;

    if(!isAttach) {
        $("#divAttachBox").find(".invalid-feedback").html("첨부파일은 필수입니다.");
        $("#divAttachBox").find(".invalid-feedback").show();
    } else {
        $("#divAttachBox").find(".invalid-feedback").html("");
        $("#divAttachBox").find(".invalid-feedback").hide();
    }

    valid = valid & isAttach;

    if (!valid) {
        $("#mainForm").addClass('was-validated');
    }

    return valid;
}

// 시행일 변경
function onChangeIssueDate() {
    var receiveDate = $("#receiveDate").val();

    if (receiveDate) {
        var dateParts = receiveDate.split('-');

        var yearYY = dateParts[0].slice(2, 4);
        var monthMM = dateParts[1];

        var yymm = yearYY + monthMM;

        $("#receiveYm").text(yymm);
    }
}

// 문서분류 변경
function docScopeChange() {
    var docScope = $("#docScope").val();

    if(docScope == "PROJECT") {
        $("#pjtGroup").show();
    } else {
        $("#pjtGroup").hide();
    }
}
</script>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label>접수번호(자동)</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <div class="d-flex">
            <input type="text" id="companyCd" name="companyCd" class="form-control mr-2" value="HTE" style="width:100px" required/>
            <span>-</span>
            <select class="form-control mb-1 mx-2" id="sendDept" name="sendDept" style="width:100px"></select>
            <!-- <span id="sendDept">-XX</span> -->
            <span id="docForm">-EB</span>
            <span id="docType">-R-</span>
            <span id="receiveYm"></span>
            <span id="issueNum">-NNNN</span>
        </div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="outDocCd">발신측 문서번호</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <input type="text" class="form-control validateElement" id="outDocCd" name="outDocCd" required/>
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">
        <label for="outDocCd">발신명의(기관)</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <input type="input" class="form-control validateElement mb-1" id="sendVal" name="sendVal" required/>
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="title">제목</label><span class="necessaryInput"> *</span>
    </div> 
    <div class="col-md-9">
        <input type="text" class="form-control validateElement" id="title" name="title" maxlength="255" required />
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="recipient">수신명의(당사)</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <input type="text" class="form-control validateElement" id="recipient" name="recipient" required />
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="reference">참조(당사)</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <input type="text" class="form-control validateElement" id="reference" name="reference" required/>
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">분류</div>
    <div class="col-md-9">
        <select class="form-control mb-1" id="docScope" name="docScope" onchange="docScopeChange()">
            <option value="HEAD">본사</option>
            <option value="PROJECT">프로젝트</option>
        </select>
        <div class="input-group mb-1" id="pjtGroup" style="display:none">
            <input type="text" class="form-control" id="txtPjt_nm_fr" name="txtPjt_nm_fr" readonly />
            <input type="hidden" id="txtPjt_cd_fr" name="txtPjt_cd_fr" />
            <input type="hidden" id="txtPjt_id_fr" name="txtPjt_id_fr" />
            <div class="input-group-append">
                <button class="btn btn-secondary" type="button" onclick="onBtnClearPjtClick('fr', '')">&times;</button>
                <button class="btn btn-success" type="button" onclick="onShowPjtListClick('fr', '')">선택</button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">수신매체</div>
    <div class="col-md-9">
        <select class="form-control mb-1" id="receiveKind" name="receiveKind">
            <option value="E-MAIL">E-MAIL</option>
            <option value="우편(서면)">우편(서면)</option>
            <option value="내용증명(등기)">내용증명(등기)</option>
            <option value="Fax">Fax</option>
            <option value="문서24">문서24</option>
            <option value="기타">기타</option>
        </select>
    </div> 
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="receiveDate">접수일자</label><span class="necessaryInput"> *</span>
    </div> 
    <div class="col-md-9">
        <input type="date" class="form-control validateElement" id="receiveDate" name="receiveDate" required />
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 colHeader">열람등급</div>
    <div class="col-md-9">
        <select class="form-control mb-1" id="readGrade" name="readGrade"></select>
    </div>
</div>
<!-- <div class="row">
    <div class="col-md-3 colHeader">공유부서</div>
    <div class="col-md-9">
        <div class="input-group">
            <input type="text" class="form-control validateElement" id="readDeptNm" name="readDeptNm" required readonly />
            <input type="hidden" id="readDeptId" name="readDeptId" />
            <div class="input-group-append">
                <button class="btn btn-success" type="button" onclick="onDeptIdClick()">선택</button>
            </div>
        </div>
        <div class="invalid-feedback"></div>
    </div>
</div> -->
<div class="row">
    <div class="col-md-3 colHeader">
        <label for="recipientNms">수신참조</label>
    </div> 
    <div class="col-md-9">
        <input type="text" class="form-control" id="recipientNms" name="recipientNms" readonly />
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">
        <label for="approvalKind">결재방법</label><span class="necessaryInput"> *</span>
    </div> 
    <div class="col-md-9">
        <select class="form-control mb-1 validateElement" id="approvalKind" name="approvalKind" required>
            <option value="ELEC">전자결재</option>
            <!-- <option value="EMAIL">E-MAIL</option> -->
            <option value="WRIT">서면결재 (스캔본을 업로드 하세요)</option>
            <!-- <option value="VIEW">공람</option> -->
        </select>
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">보존기간</div>
    <div class="col-md-9">
        <select class="form-control mb-1" id="storePeriod" name="storePeriod">
            <option value="1">1년</option>
            <option value="3">3년</option>
            <option value="5">5년</option>
            <option value="10" selected>10년</option>
            <option value="영구">영구</option>
        </select>
    </div>
</div>