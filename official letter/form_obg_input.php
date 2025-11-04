<script type="text/javascript">
$(document).ready(function() {
    if(!$("#ono").val()) {
        $("#issueDate").val(getTodayDate());
    }
    onChangeIssueDate();

    $("#issueDate").on('change', onChangeIssueDate);

    $('input[name="chkDocCd"], #companyCd, #sendDept').on('change', function () {
        var selectedValue = $('input[name="chkDocCd"]:checked').val();

        if (selectedValue == "docCd") {
            $("#outDocCd").prop("disabled", true);
            const companyCd = $('#companyCd').val();
            const sendDept = $('#sendDept').val();
            const docForm = $('#docForm').text();
            const docType = $('#docType').text();
            const issueNum = $('#issueNum').text();
            var cdYm = '';
            if(docType == "-S-") {
                cdYm = $('#issueYm').text();
            } else if(docType == "-R-") {
                cdYm = $('#receiveYm').text();
            }

            var docCd = companyCd + '-' + sendDept + docForm + docType + cdYm + issueNum;
            editor.setText('.docCd', docCd);
        } else if (selectedValue == "outDocCd") {
            $("#outDocCd").prop("disabled", false);
            var docCd = $("#outDocCd").val()
        }
        editor.setText('.docCd', docCd);
    });
});

//유효성 검사
function validateInputsDetail() {
    var valid = true;

    valid = valid & validateElement("recipient");

    valid = valid & validateElement("issueDate");

    valid = valid & validateElement("title");

    valid = valid & validateElement("reference");

    valid = valid & validateElement("sendVal");

    if (!valid) {
        $("#mainForm").addClass('was-validated');
    }

    return valid;
}

// 시행일 변경
function onChangeIssueDate() {
    var issueDate = $("#issueDate").val();

    if (issueDate) {
        var dateParts = issueDate.split('-');

        var yearYY = dateParts[0].slice(2, 4);
        var monthMM = dateParts[1];

        var yymm = yearYY + monthMM;

        $("#issueYm").text(yymm);
    }
}
</script>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label>문서번호</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <div class="d-flex">
            <input type="text" id="companyCd" name="companyCd" class="form-control mr-2" value="HTE" style="width:100px" required/>
            <span>-</span>
            <select class="form-control mb-1 mx-2" id="sendDept" name="sendDept" style="width:100px"></select>
            <span id="docForm">-EB</span>
            <span id="docType">-S-</span>
            <span id="issueYm"></span>
            <span id="issueNum">-NNNN</span>
        </div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label>공문 내 번호</label>
    </div>
    <div class="col-md-9">
        <div class="form-check-inline">
            <label class="form-check-label">
                <input type="radio" class="form-check-input" name="chkDocCd" value="docCd" checked>자동부여
            </label>
        </div>
        <div class="form-check-inline">
            <label class="form-check-label">
                <input type="radio" class="form-check-input" name="chkDocCd" value="outDocCd">별도기입
            </label>
        </div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label>별도 문서번호</label>
    </div>
    <div class="col-md-9">
        <div class="d-flex">
            <input type="text" class="form-control validateElement" id="outDocCd" name="outDocCd" disabled />
        </div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="recipient">수신처</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <input type="text" class="form-control validateElement" id="recipient" name="recipient" required />
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="reference">참조처</label><span class="necessaryInput"> *</span>
    </div>
    <div class="col-md-9">
        <input type="text" class="form-control validateElement" id="reference" name="reference" required />
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="issueDate">시행일</label><span class="necessaryInput"> *</span>
    </div> 
    <div class="col-md-9">
        <input type="date" class="form-control validateElement" id="issueDate" name="issueDate" required />
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="title">제목</label><span class="necessaryInput"> *</span>
    </div> 
    <div class="col-md-9">
        <input type="text" class="form-control validateElement" id="title" name="title" required />
        <div class="invalid-feedback"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">발신분류</div>
    <div class="col-md-9">
        <select class="form-control mb-1" id="sendType" name="sendType"></select>
        <select class="form-control mb-1" id="director" name="director" style ="display:none"></select>
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
<div class="row form-group">
    <div class="col-md-3 colHeader">
        <label for="sendVal">발신명의</label><span class="necessaryInput"> *</span>
    </div> 
    <div class="col-md-9">
        <input type="input" class="form-control validateElement mb-1" id="sendVal" name="sendVal" readonly required/>
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
    <div class="col-md-3 colHeader">열람부서</div>
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
    <div class="col-md-3 colHeader">전화번호</div>
    <div class="col-md-9">
        <input type="text" class="form-control" id="tel" name="tel" />
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">팩스번호</div>
    <div class="col-md-9">
        <input type="text" class="form-control" id="fax" name="fax" />
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">이메일</div>
    <div class="col-md-9">
        <input type="text" class="form-control" id="email" name="email" />
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">결재방법</div>
    <div class="col-md-9">
        <select class="form-control mb-1" id="approvalKind" name="approvalKind">
            <option value="ELEC">전자결재</option>
            <!-- <option value="EMAIL">E-MAIL</option> -->
            <option value="WRIT">서면결재 (스캔본을 업로드 하세요)</option>
            <!-- <option value="VIEW">공람</option> -->
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-3 colHeader">발신방법</div>
        <div class="col-md-9">
            <div class="form-check-inline">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="chkSendKind[]" value="E-MAIL">E-MAIL
                </label>
            </div>
            <div class="form-check-inline">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="chkSendKind[]" value="우편">우편
                </label>
            </div>
            <div class="form-check-inline">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="chkSendKind[]" value="직접전달">직접전달
                </label>
            </div>
            <div class="form-check-inline">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="chkSendKind[]" value="문서24">문서24
                </label>
            </div>
            <input type="hidden" class="form-control" id="sendKind" name="sendKind" />
        </div>
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
