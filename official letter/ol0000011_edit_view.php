<script>
$(document).ready(function(){
    // 결재선 수정
    $("#modalEditAppLine").on('hide.bs.modal', function () {
        let appList = [];
        $('input[name="appAgrLine[]"]').each(function () {
            const appLineInfo = $(this).val(); // 문자열 하나

            var writer = $("#userNm").text();

            if (appLineInfo.split('|')[6] === "1") {
                if(writer != appLineInfo.split('|')[3]) {
                    appList.push(appLineInfo.split('|')[3]); // 이름
                }
            }
        });

        // 이후 분기 처리
        let consider = "", director = "";
        if (appList.length === 0) {
            consider = director = "";
        } else if (appList.length === 1) {
            consider = director = appList[0];
        } else {
            consider = appList.slice(0, -1).join(', ');
            director = appList[appList.length - 1];
        }

        // 텍스트 세팅
        editor.setText('.consider', consider);
        editor.setText('.director', director);
    });

    // 헤더 고정
    var thEBList = $('#tblEBList').find('thead th');
    $('#tblEBList').closest('div.tableFixHead-modal').on('scroll', function() {
        thEBList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    // 헤더 고정
    var thSelEBList = $('#tblSelEBList').find('thead th');
    $('#tblSelEBList').closest('div.tableFixHead-modal').on('scroll', function() {
        thSelEBList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    //검색란 입력 시
    $("#searchEBKindText").on("keypress", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키
        if (cd == 13) {
            onBtnSearchEBKindTextClick();
            e.preventDefault();
            return false;
        }
    });

    //저장 버튼
    $("#btnSaveOb").on("click", onBtnSaveObClick);
    //임시저장 버튼
    $("#btnSaveTempOb").on("click", onBtnSaveObTempClick);
    //미리보기 버튼
    $("#btnPreviewEB").on("click", previewObDocInNewTab);
    //반영 - 참조문서 버튼
    $("#btnApplyRelatedEB").on("click", onBtnApplyRelatedEBClick);
    //참조문서 - 검색 버튼 클릭
    $("#btnSearchEBKindText").on("click", onBtnSearchEBKindTextClick);
    //삭제
    $("#btnConfirmDelete").on('click', onBtnDelEBClick);
})
// 발신분류 값 변경
function sendTypeChange() {
    //작업모드
    $("#mode").val("SEND_TYPE");

    $.ajax({
        type: "POST",
        url: "/gw/ol/ol0000011.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function (result) {
            $("#sendVal").val(result["sendVal"]);

            var sendType = $("#sendType").val();

            // 본부장
            if(sendType == "C010") {
                var html = '';
                var directorList = result["directorList"];
                $(directorList).each(function(i, director) {
                    html += '<option value="'+ director["uno"] +'">' + "하이테크엔지니어링 " + director["directorVal"] + '</option>';
                });
                $("#director").empty().append(html);
                $("#director").show();
                $("#pjtGroup").hide();
                $("#sendVal").val($('#director option:selected').text());

                $("#director").on("change", function() {
                    $("#sendVal").val($('#director option:selected').text());
                });

                $("#sendVal").prop('readonly', true);
            } else if(sendType == "PJM" || sendType == "PJS") {
                $("#pjtGroup").show();
                $("#director").hide();
                $("#sendVal").prop('readonly', false);

                if(sendType == "PJM") {
                    $("#sendVal").val("하이테크엔지니어링 프로젝트매니저 홍길동");
                    $("#sendVal").prop('readonly', false);
                } else {
                    $("#sendVal").val("하이테크엔지니어링 현장소장 홍길동");
                    $("#sendVal").prop('readonly', false);
                }
            } else if(sendType == "TL") {
                $("#pjtGroup").hide();
                $("#sendVal").val("하이테크엔지니어링 팀장 홍길동");
                $("#sendVal").prop('readonly', false);
            } else if(sendType == "INPUT") {
                $("#pjtGroup").hide();
                $("#director").hide();
                $("#sendVal").val("");
                $("#sendVal").prop('readonly', false);
            } else {
                $("#director").hide();
                $("#sendVal").prop('readonly', true);
                $("#pjtGroup").hide();
            }
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

async function onBtnSaveObClick() {
    $("#mode").val("SAVE");

    $("#btnPush").trigger("click");

    var subMenuCd = $("#subMenuCd").val();
    var isValid = false;
    isValid = validateInputsDetail() && validateAppLineEB();

    if($("#showOno").val()) {
        $("#modalObDetail_" + $("#showOno").val()).remove();
        $(".modal-backdrop").remove();
        $("body").removeClass("modal-open");
        $("body").css("padding-right", "");
    }

    if(isValid) {
        $("#modalEditOL").find("button:button").prop("disabled", true);
        $("#htmlContent").val(editor.getPublishingHtml());
        $("#txtContent").val(editor.getTextContent());

        var formdata = new FormData($("#mainForm")[0]);
        $.ajax({
            type: "POST",
            url: "/gw/ol/ol0000011.php",
            data: formdata,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function (result) {
                var proceed = result["proceed"];

                if(proceed) {
                    $("#modalEditOL").modal('hide');
                    $("#modalEditOL").find("button:button").prop("disabled", false);

                    onConditionChange();

                    if($("#ono").val()) {
                        var ono = $("#ono").val();
                        showObDetail(ono);
                    }
                }
            },
            error: function(request, status, error) {
                alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
            }
        });
    }
}

async function onBtnSaveObTempClick() {
    $("#mode").val("SAVE_TEMP");

    $("#btnPush").trigger("click");

    $("#modalEditOL").find("button:button").prop("disabled", true);
    $("#htmlContent").val(editor.getPublishingHtml());
    $("#txtContent").val(editor.getTextContent());
    $("#footerContent").val($("#obgFooter").html());

    var formdata = new FormData($("#mainForm")[0]);
    $.ajax({
        type: "POST",
        url: "/gw/ol/ol0000011.php",
        data: formdata,
        dataType: "json",
        contentType: false,
        processData: false,
        success: function (result) {
            var proceed = result["proceed"];

            if(proceed) {
                $("#modalEditOL").modal('hide');
                $("#modalEditOL").find("button:button").prop("disabled", false);

                onConditionChange();
            }
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

//부서 선택
function onDeptIdClick() {
    onBtnSelectDeptUserClick('OB', 'readDept', true, 'Y', 'N');
}

function onDUSelected() {
    // validateElement("deptNm");
}

// 수정
function onBtnEditObClick(ono) {
    $("#dbMode").val('U');
    $("#btnDelEB").show();
    $("#ono").val(ono);
    
    //작업모드
    $("#mode").val("EDIT");

    $("#modalObDetail").modal("hide");
    $("#divDF11").empty();
    $("#divNewAttachedList").empty();
    
    $.ajax({
        type: "POST",
        url: "/gw/ol/ol0000011.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            var docTypeVal = result["docDetail"]["docType"];
            $("#docType").val(docTypeVal);

            var formUrl = '';
            if(docTypeVal == "S") {
                formUrl = "/gw/ol/form_obg_input.php";
                $("#textareaContent").css("height" , "40rem");
                $("#btnPush").show();
            } else if(docTypeVal == "R") {
                formUrl = "/gw/ol/form_obr_input.php";
                $("#textareaContent").css("height" , "20rem");
                $("#btnPush").hide();
            }

            deptNickList = result["deptNickList"];
            var docDetail = result["docDetail"];
            var readGradeList = result["readGradeList"];
            var sendNameList = result["sendNameList"];
            var attachFileList = result["attachFileList"];
            var relatedEBList = result["relatedEBList"];

            //첨부파일
            var html = '';
            $(attachFileList).each(function(i, file) {
                html += '<div class="input-group mb-2" id="atch_'+ file["attachId"] +'">';
                html += '<div class="custom-file">';
                html += '<label class="custom-file-label" for="customFile"><i class="fa-solid fa-cloud-arrow-up"></i>'+ file["fileNm"] +'</label>';
                html += '</div>';
                html += '<div class="input-group-append">';
                html += '<button type="button" class="btn btn-secondary" onclick="javascript:delAttachedFile(this);">&times;</button>';
                html += '</div>';
                html += '</div>';
            });
            $("#divAttachedList").empty().append(html);

            //참조파일
            var html = '';
            $(relatedEBList).each(function(i, info) {
                html += '<tr id="trSelEB_' + info["refDoc"] + '>';
                html += '<td style="width: 5%;text-align: center;">' + (i++) + '</td>';
                html += '<td style="width: 35%;text-align: center;">';
                //문서번호
                html += info["docCd"];
                html += '</td>';
                html += '<td style="width: 20%;text-align: center;">';
                //발신측 문서번호
                html += info["outDocCd"];
                html += '</td>';
                html += '<td style="width: 40%;">';
                html += '<div class="h-100 d-flex align-items-center notAlign">';
                html += '<div class="ellipsisLongTxt">';
                // html += '<a href="javascript:void(0);" onclick="showEaAppDocDetail(' + info["ono"] + ',' + info["formId"] + ')">';
                //제목
                html += info["title"];
                // html += '</a>';
                html += '</div>';
                html += '<input type="hidden" name="relatedEB[]" value="' + info["refDoc"] + '" />';
                html += '</td>';
                html += '</tr>';
            });
            $("#tblRelatedEBList tbody").empty().append(html);

            $.ajax({
                url: formUrl, 
                success: function(inputPage) {
                    $("#appDocContents").empty().append(inputPage);

                    html = '';
                    $(deptNickList).each(function(i, nick) {
                        html += '<option value="'+ nick +'">' + nick + '</option>';
                    });
                    $("#sendDept").empty().append(html);

                    var html = '';
                    $(sendNameList).each(function(i, info) {
                        html += '<option value="'+ info["sdCd"] +'">' + info["sdType"] + '</option>';
                    })
                    $("#sendType").empty().append(html);

                    html = '';
                    $(readGradeList).each(function(i, info) {
                        html += '<option value="'+ info["rgCd"] +'">' + info["rgVal"] + '</option>';
                    })
                    $("#readGrade").empty().append(html);

                    if(docDetail["sendKind"]) {
                        var sendType = docDetail["sendKind"];
                        var selectedTypes = sendType.split("|");
                        $("input[name='chkSendKind[]']").prop("checked", false);
    
                        $("input[name='chkSendKind[]']").each(function () {
                            if (selectedTypes.includes($(this).val())) {
                                $(this).prop("checked", true);
                            }
                        });
                    }

                    //부서코드
                    deptNickList = result["deptNickList"];

                    html = '';
                    $(deptNickList).each(function(i, nick) {
                        html += '<option value="'+ nick +'">' + nick + '</option>';
                    });
                    
                    $("#sendDept").empty().append(html);
                    $("#sendDept").val(docDetail["sendDept"]);

                    // 결재라인
                    html = drawAppLine(result["appLine"]);
                    $("#divAppLine").empty().append(html);

                    // 수신참조
                    $("#recipientIds").val(result["recipientIds"]);
                },
                complete: function() {
                    if($("#docType").val() == "S") {
                    sendTypeChange();

                        //발신분류 변경
                        $("#sendType").on("change", sendTypeChange);
                    } else {
                        $("#sendVal").prop("readonly", false);
                    }

                    editor.openHTML(result["docHtml"]);
                    editor.setText('.userName', result["writer"]);
                    editor.setText('.docCd', docDetail["docCd"]);

                    for (var key in docDetail) {
                        $("#" + key).val(docDetail[key]);
                    }

                    $("#appDocContents input").on("blur", function() {
                        $("#btnPush").trigger("click");
                    });
                }
            })
        }
    });

    $("#modalEditOL").modal("show");
}

//결재라인 유효성 검사
function validateAppLineEB() {
    var id = "setAppLine";
    var name = "결재라인지정";
    var validCount = 0;

    $("input[type='hidden'][name='appAgrLine[]']").each(function(i, obj) {
        var val = $(obj).val();
        var arr = val.split("|");

        // 결재자 여부 확인 (값이 1이면 결재자)
        if (arr[arr.length - 2] == 1) {
            validCount++;
        }
    });

    var obj = document.getElementById(id);

    // 결재자가 2명 이상일 경우에만 유효
    if (validCount >= 2) {
        obj.value = "Y";
        obj.setCustomValidity("");
    } else {
        obj.value = "";
        obj.setCustomValidity(name + "은(는) 2명 이상 지정되어야 합니다.");
    }

    // 유효성 클래스 처리
    if (obj.validity.valid) {
        $(obj).closest(".form-group").find(".invalid-feedback").html("");
        $(obj).removeClass('is-valid is-invalid');
    } else {
        $(obj).closest(".form-group").find(".invalid-feedback").html(obj.validationMessage);
        $(obj).addClass("is-invalid");
    }

    return obj.validity.valid;
}

// function previewObDocInNewTab() {
//     const unitHeight = 1100;
//     const originalTitle = document.title;

//     // 1. 에디터 HTML 가져오기 및 DOM으로 파싱
//     const html = editor.getPublishingHtml();
//     const $content = $('<div></div>').html(html);

//     // 기존 filler 제거
//     $content.find('.print-filler').remove();

//     // 2. 높이 측정용 temp wrapper 생성
//     const tempWrapper = $('<div></div>').css({
//         position: 'absolute',
//         visibility: 'hidden',
//         width: '100%',
//         left: '-9999px'
//     }).append($content.clone());

//     $('body').append(tempWrapper);

//     const contentHeight = tempWrapper.outerHeight(true);
//     const totalPages = Math.ceil(contentHeight / unitHeight);
//     const remainder = contentHeight % unitHeight;

//     let paddingHeight = remainder === 0 ? 0 : unitHeight - remainder;

//     // 보정 로직
//     if (totalPages >= 2) {
//         const correction = 100 + (totalPages - 2) * 25;
//         paddingHeight = Math.max(0, paddingHeight - correction);
//     }

//     tempWrapper.remove(); // 측정용 요소 제거

//     // 3. filler 삽입
//     if (paddingHeight > 0) {
//         const fillerDiv = $('<div></div>', {
//             class: 'print-filler',
//             css: {
//                 height: `${paddingHeight}px`,
//                 visibility: 'hidden',
//                 pageBreakBefore: 'auto'
//             }
//         });

//         const $footerCandidates = $content.find('[id^="obgFooter"]');
//         if ($footerCandidates.length) {
//             fillerDiv.insertBefore($footerCandidates.last());
//         } else {
//             $content.append(fillerDiv);
//         }
//     }

//     // 4. 페이지 번호 마커 생성
//     const pageMarkers = Array.from({ length: totalPages }).map((_, i) => {
//         return `<div class="page-marker" style="top: ${i * unitHeight}px">${i + 1}page</div>`;
//     }).join('');

//     // 5. Blob으로 미리보기 HTML 생성
//     const previewHTML = `
//         <!DOCTYPE html>
//         <html>
//         <head>
//             <meta charset="utf-8">
//             <title>${originalTitle} - 미리보기</title>
//             <style>
//                 body {
//                     margin: 0;
//                     font-family: sans-serif;
//                     background: #f9f9f9;
//                     position: relative;
//                 }
//                 .preview-container {
//                     display: flex;
//                     justify-content: center;
//                     align-items: flex-start;
//                     gap: 10px;
//                 }
//                 .page-marker-column {
//                     width: 60px;
//                     position: relative;
//                     padding-top: 38px;
//                 }
//                 .page-marker {
//                     position: absolute;
//                     left: 0;
//                     font-size: 12px;
//                     color: red;
//                     font-weight: bold;
//                 }
//                 .print-preview-wrapper {
//                     width: 710px;
//                     background: white;
//                     padding: 38px;
//                     box-shadow: 0 0 10px rgba(0,0,0,0.1);
//                 }
//                 .print-filler {
//                     visibility: hidden;
//                 }
//                 .MsoNormalTable {
//                     line-height: 1.8 !important;
//                 }
//                 @media print {
//                     .print-filler {
//                         display: block;
//                     }
//                     body {
//                         background: none;
//                         padding: 0;
//                     }
//                     .print-preview-wrapper {
//                         box-shadow: none;
//                         padding: 0;
//                     }
//                     .page-marker-column {
//                         display: none;
//                     }
//                 }
//             </style>
//         </head>
//         <body>
//             <div class="preview-container">
//                 <div class="page-marker-column">
//                     ${pageMarkers}
//                 </div>
//                 <div class="print-preview-wrapper">
//                     ${$content.html()}
//                 </div>
//             </div>
//         </body>
//         </html>
//     `;

//     const blob = new Blob([previewHTML], { type: 'text/html' });
//     const url = URL.createObjectURL(blob);

//     // 6. a 태그로 새 탭 유도
//     const a = document.createElement('a');
//     a.href = url;
//     a.target = '_blank';
//     a.rel = 'noopener';
//     a.click();

//     // 7. URL 메모리 해제
//     setTimeout(() => URL.revokeObjectURL(url), 1000);
// }

function previewObDocInNewTab() {
    const unitHeight = 1100; // A4 한 페이지 높이(px) 추정
    const GAP = 38;          // 10mm ≈ 38px
    const GAP2 = GAP * 2;    // 경계 한 곳당 총 추가 높이 (위+아래)
    const originalTitle = document.title;

    // 1) 에디터 HTML 가져오기 및 DOM으로 파싱
    const html = editor.getPublishingHtml();
    const $content = $('<div></div>').html(html);

    // 기존 filler 제거
    $content.find('.print-filler').remove();

    // 2) 1차 측정: 원본 콘텐츠 기준으로 총 페이지 수
    const tempWrapper1 = $('<div></div>').css({
        position: 'absolute', visibility: 'hidden', width: '100%', left: '-9999px'
    }).append($content.clone());
    $('body').append(tempWrapper1);

    const contentHeight1 = tempWrapper1.outerHeight(true);
    const totalPages = Math.ceil(contentHeight1 / unitHeight);

    // 블록 인덱싱(측정본 ↔ 실제본 매핑)
    const BLOCK_SEL = 'p, div, table, ul, ol, li, h1, h2, h3, h4, h5, h6, blockquote, pre, section, article, header, footer, aside';
    const $measureBlocks = tempWrapper1.find(BLOCK_SEL);
    const $contentBlocks = $content.find(BLOCK_SEL);
    $measureBlocks.each(function (i) { $(this).attr('data-node-idx', i); });
    $contentBlocks.each(function (i) { $(this).attr('data-node-idx', i); });

    // 3) 경계마다 실제 DOM에 여백(+첫번째만 라인) 삽입
    for (let i = 1; i < totalPages; i++) {
        // i번째 경계의 목표 Y (누적 GAP2 보정)
        const targetY = i * unitHeight + (i - 1) * GAP2;

        // targetY 이상인 첫 블록 찾기 (footer 제외)
        let $anchor = null;
        $measureBlocks.each(function () {
            if ($(this).closest('[id^="obgFooter"]').length) return;
            if ($(this).position().top >= targetY) { $anchor = $(this); return false; }
        });
        if (!$anchor) {
            const $lastBeforeFooter = $measureBlocks.filter(function () {
                return !$(this).closest('[id^="obgFooter"]').length;
            }).last();
            if ($lastBeforeFooter.length) $anchor = $lastBeforeFooter;
        }

        if ($anchor && $anchor.length) {
            const idx = $anchor.attr('data-node-idx');
            const $targetInContent = $content.find(`[data-node-idx="${idx}"]`).first();

            // 첫 번째(1→2)에는 라인 포함, 그 이후는 스페이서만
            const gapHtml = (i === 1)
                ? `
                    <div class="page-break-gap" data-gap="${i}" style="page-break-inside: avoid;">
                        <div class="gap-spacer" style="height:${GAP}px;"></div>
                        <div class="gap-line" aria-hidden="true"></div>
                        <div class="gap-spacer" style="height:${GAP}px;"></div>
                    </div>
                  `
                : `
                    <div class="page-break-gap" data-gap="${i}" style="page-break-inside: avoid;">
                        <div class="gap-spacer" style="height:${GAP}px;"></div>
                        <div class="gap-spacer" style="height:${GAP}px;"></div>
                    </div>
                  `;
            if ($targetInContent.length) $targetInContent.before(gapHtml);
            else $content.append(gapHtml);
        }
    }

    tempWrapper1.remove();

    // 4) 2차 측정: gap 포함 높이 재측정 후 filler 계산
    const tempWrapper2 = $('<div></div>').css({
        position: 'absolute', visibility: 'hidden', width: '100%', left: '-9999px'
    }).append($content.clone());
    $('body').append(tempWrapper2);

    const contentHeight2 = tempWrapper2.outerHeight(true);
    const totalPages2 = Math.ceil(contentHeight2 / unitHeight);
    const remainder2 = contentHeight2 % unitHeight;
    let paddingHeight = remainder2 === 0 ? 0 : unitHeight - remainder2;

    if (totalPages2 >= 2) {
        const correction = 100 + (totalPages2 - 2) * 25;
        paddingHeight = Math.max(0, paddingHeight - correction);
    }
    tempWrapper2.remove();

    // 5) filler 삽입 (footer 위)
    if (paddingHeight > 0) {
        const fillerDiv = $('<div></div>', {
            class: 'print-filler',
            css: { height: `${paddingHeight}px`, visibility: 'hidden', pageBreakBefore: 'auto' }
        });
        const $footerCandidates = $content.find('[id^="obgFooter"]');
        if ($footerCandidates.length) fillerDiv.insertBefore($footerCandidates.last());
        else $content.append(fillerDiv);
    }

    // 6) 페이지 번호 마커 (누적 GAP2 반영)
    const pageMarkers = Array.from({ length: totalPages2 }).map((_, i) => {
        const top = i * unitHeight + (i > 0 ? i * GAP2 : 0);
        return `<div class="page-marker" style="top:${top}px">${i + 1}page</div>`;
    }).join('');

    // 7) 미리보기 HTML 구성
    const previewHTML = `
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>${originalTitle} - 미리보기</title>
<style>
  body { margin:0; font-family:sans-serif; background:#f9f9f9; position:relative; }
  .preview-container { display:flex; justify-content:center; align-items:flex-start; gap:10px; }
  .page-marker-column { width:60px; position:relative; padding-top:38px; }
  .page-marker { position:absolute; left:0; font-size:12px; color:red; font-weight:bold; }
  .print-preview-wrapper {
      width:710px; background:#fff; padding:38px; box-shadow:0 0 10px rgba(0,0,0,0.1);
      position:relative; overflow:visible;
  }
  .print-filler { visibility:hidden; }
  .MsoNormalTable { line-height:1.8 !important; }

  /* 페이지 경계 블록 */
  .page-break-gap { position:relative; }
  .gap-line { height:0; border-top:1px dashed rgba(0,0,0,0.35); position:relative; }
  .gap-spacer { display:block; width:100%; height:${GAP}px; }

  @media print {
    .print-filler { display:block; }
    body { background:none; padding:0; }
    .print-preview-wrapper { box-shadow:none; padding:0; }
    .page-marker-column { display:none; }
    .gap-line { display:none; } /* 인쇄 시 라인은 숨기되 스페이서는 그대로 */
  }
</style>
</head>
<body>
  <div class="preview-container">
    <div class="page-marker-column">${pageMarkers}</div>
    <div class="print-preview-wrapper">
      ${$content.html()}
    </div>
  </div>
</body>
</html>`;

    // 8) 새 탭 열기
    const blob = new Blob([previewHTML], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.target = '_blank'; a.rel = 'noopener'; a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

//참조문서 선택 버튼 클릭
function onBtnSelectRelatedEBClick() {
    if ($('#modalEditRelatedDoc').hasClass('show')) {
        return false;
    }
    $("#tblSelEBList tbody").empty();

    // const d = new Date();
    // $("#searchDocDateTo").val(d.toISOString().substring(0,10));
    // d.setMonth(d.getMonth() - 3);
    // $("#searchEBDateFrom").val(d.toISOString().substring(0,10));

    var html = "";
    $("#tblRelatedEBList tbody tr").each(function() {
        var docId = $(this).find("input[name='relatedEB[]']").val();
        console.log(docId);
        html += '<tr id="trSelEB_' + docId + '" class="row">';
        html += '<td class="col-md-3 col-5">';
        html += '<div class="h-100 d-flex align-items-center">';
        //품의번호
        html += $(this).find("td:eq(0)").text();
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-3 d-none d-md-block">';
        html += '<div class="h-100 d-flex align-items-center notAlign">';
        //문서분류
        html += $(this).find("td:eq(1)").text();
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md col-4 text-epllipsis">';
        html += '<div class="h-100 d-flex align-items-center notAlign">';
        //제목
        html += $(this).find("td:eq(2) div").prop('outerHTML');
        html += '</div>';
        html += '</td>';
        html += '<td class="col-md-2 col-3 col-w-btn">';
        html += '<span class="selEBId" style="display: none;">' + docId + '</span>';
        html += '<button type="button" class="btn btn-warning" onclick="onBtnDelRelEBClick(\'' + docId + '\')">삭제</button>';
        html += '</td>';
        html += '</tr>';
    });

    $("#tblSelEBList tbody").append(html);

    $("#modalEditRelatedEB").modal("show");

    onRelatedEBConditionChange();
}

//참조문서목록 검색
function onRelatedEBConditionChange() {
    $("#tblEBList tbody").empty();

    //작업모드
    $("#mode").val("RELATED_EB");
    $.ajax({ 
        type: "POST", 
        url: "/gw/ol/ol0000011.php", 
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            var html = "";
            $(result["ebrList"]).each(function(i, info) {
                html += '<tr id="trEB_' + info["ono"] + '" class="row">';
                html += '<td class="col-md-3 col-5">';
                html += '<div class="h-100 d-flex align-items-center">';
                html += info["docCd"];
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-3 d-none d-md-block">';
                html += '<div class="h-100 d-flex align-items-center notAlign">';
                html += info["outDocCd"];
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md col-4 text-ellipsis">';
                html += '<div class="h-100 d-flex align-items-center notAlign">';
                html += '<div class="ellipsisLongTxt">';
                // html += '<a href="javascript:void(0);" onclick="showEaAppDocDetail(' + info["ono"] + ',' + info["formId"] + ')">';
                //제목
                html += info["title"];
                // html += '</a>';
                html += '</div>';
                html += '</div>';
                html += '</td>';
                html += '<td class="col-md-2 col-3 col-w-btn">';
                html += '<div class="h-100 d-flex align-items-center">'
                html += '<button type="button" class="btn btn-info" onclick="onBtnAddEBClick(' + info["ono"] + ')">추가</button>';
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            });
            $("#tblEBList tbody").append(html);
        },
        beforeSend:function(){
            $("#divSearchRelatedDoc").find("input").prop("disabled", true);
            $("#divSearchRelatedDoc").find("select").prop("disabled", true);
            $("#btnSearchDocKindText").find("span.spinner-border").show();
        },
        complete:function(){
            $("#divSearchRelatedDoc").find("input").prop("disabled", false);
            $("#divSearchRelatedDoc").find("select").prop("disabled", false);
            $("#btnSearchDocKindText").find("span.spinner-border").hide();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

function onBtnAddEBClick(ono) {
    if ($("#trSelEB_" + ono).length > 0) {
        return;
    }

    var tr = $("#trEB_" + ono);
//     var subject = tr.find("td:eq(2)");
//     subject = subject.replace(/</g,"&lt;");
//     subject = subject.replace(/>/g,"&gt;");

    var html = "";
    html += '<tr id="trSelEB_' + ono + '" class="row">';
    html += '<td class="col-md-3 col-5">';
    //품의번호
    html += tr.find("td:eq(0)").html();
    html += '</td>';
    html += '<td class="col-md-3 d-none d-md-block">';
    //문서분류
    html += tr.find("td:eq(1)").html();
    html += '</td>';
    html += '<td class="col-md col-4 text-ellipsis">';
    //제목
    html += tr.find("td:eq(2)").html();
    html += '</td>';
    html += '<td class="col-md-2 col-3 col-w-btn">';
    html += '<span class="selDocId" style="display: none;">' + ono + '</span>';
    html += '<div class="h-100 d-flex align-items-center">';
    html += '<button type="button" class="btn btn-warning" onclick="onBtnDelRelEBClick(\'' + ono + '\')">삭제</button>';
    html += '</div>';
    html += '</td>';
    html += '</tr>';
    $("#tblSelEBList tbody").append(html);
}

function onBtnDelRelEBClick(ono) {
    $("#trSelEB_" + ono).remove();
}

//선택된 문서 참조목록에 반영
function onBtnApplyRelatedEBClick() {
    $("#tblRelatedEBList tbody").empty();

    var html = "", i = 1;
    $("#tblSelEBList tbody tr").each(function() {
//         var subject = $(this).find("td:eq(2)").text()
//         subject = subject.replace(/</g,"&lt;");
//         subject = subject.replace(/>/g,"&gt;");

        html += '<tr id="trSelEB_' + $(this).find(".selDocId").text() + '>';
        html += '<td style="width: 5%;text-align: center;">' + (i++) + '</td>';
        html += '<td style="width: 35%;text-align: center;">';
        //품의번호
        html += $(this).find("td:eq(0)").text();
        html += '</td>';
        html += '<td style="width: 20%;text-align: center;">';
        //문서분류
        html += $(this).find("td:eq(1)").text();
        html += '</td>';
        html += '<td style="width: 40%;">';
        //제목
        html += $(this).find("td:eq(2) div").prop('outerHTML');
        html += '<input type="hidden" name="relatedEB[]" value="' + $(this).find(".selDocId").text() + '" />';
        html += '</td>';
        html += '</tr>';
    });

    $("#tblRelatedEBList tbody").append(html);

    // validateRelatedDoc();
    // $("#detectEditAppDoc").val("Y");
    $("#modalEditRelatedEB").modal("hide");
}

//검색 버튼 클릭
function onBtnSearchEBKindTextClick() {
    var elem = $("#searchEBKindText");
    elem.val(elem.val().trim());
    if (elem.data("oldVal") != elem.val()) {
        elem.data('oldVal', elem.val());

        onRelatedEBConditionChange();
    }
}

//첨부파일 삭제
function delAttachedFile(obj) {
    var atachFno = $(obj).closest('div.input-group').attr("id");
    if(atachFno) {
        console.log(atachFno);
        var arrayFno = atachFno.split("_");
        var fno = arrayFno[1];

        const $input = $('<input>', {
            type: 'hidden',
            name: 'delAtchFileList[]',
            value: fno
        });
        $('#mainForm').append($input);
    }

    $(obj).closest('div.input-group').remove();
}

function onBtnDelEBClick() {
    $("#mode").val("DEL_EB");

    $.ajax({
        type: "POST",
        url: "/gw/ol/ol0000011.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function (result) {
            if(result["proceed"]) {
                $("#modalConfirmDel").modal('hide');
                $("#modalEditOL").modal('hide');

                $("#resultMsg").empty().html(result["msg"]).fadeIn();
                $("#resultMsg").delay(5000).fadeOut();

                onConditionChange();
            }
        },
        error: function(request, status, error) {
            alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
        }
    });
}

</script>
<!-- The Modal -->
<div class="modal fade modalMain modalEaDocApp" id="modalEditOL" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title"></h4>
                <button type="button" class="close btn-close" name="btnCloseEditAppDoc" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row" style="display: none;">
                    <div class="col">
                        <div id="divEBMenu">
                            <span id="ebMenuNmPly"></span>
                            <button type="button" class="btn btn-info btn-sm ml-2" id="btnEBChange" name="btnEBChange" onclick="onBtnEBChangeClick()">선택</button>
                            <input type="hidden" id="ply" name="ply" />
                            <input type="hidden" id="plyNm" name="plyNm" />
                            <input type="hidden" id="ebMenuId" name="ebMenuId" />
                            <input type="hidden" id="ebMenuNm" name="ebMenuNm" />
                        </div>
                    </div>
                    <!-- 
                <div class="col-md-4">
                    중요도: <select name="ddlImprotantKind" id="ddlImprotantKind"></select>
                </div>
                    -->
                </div>
                <div class="row row-direction-reverse">
                    <div class="col-md-5 mb-2">
                        <div class="d-flex">
                            <div class="ml-auto" style="z-index: 999;">
                                <button type="button" class="btn btn-info" id="btnSelectAppLine" name="btnSelectAppLine" onclick="onBtnSelectAppLineClick('apply', 'all')">결재선지정(결재/합의/수신)</button>
                            </div>
                        </div>
                        <div class="d-flex flex-column" style="margin-top: -1rem;">
                            <div class="ml-auto">
                                <div id="divAppLine"></div>
                            </div>
                            <div class="ml-auto text-primary" id="msgAppLine" style="font-size: 80%;">
                            </div>
                            <div class="form-group ml-auto">
                                <input type="text" id="setAppLine" name="setAppLine" style="display:none;" required />
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7 mb-2">
                        <div id="appDocContents" class="mainContents"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="btnPush">공문에 반영</button>
                <div class="form-group my-4" id="divContentBox">
                    <!-- <textarea id="textareaContent"></textarea> -->
                    <div id="textareaContent"></div>
                    <input type="hidden" id="txtContent" name="txtContent" />
                    <input type="hidden" id="htmlContent" name="htmlContent" />
                    <input type="hidden" id="footerContent" name="footerContent" />
                </div>
                <div class="form-group mb-2">
                    <label for="tblRelatedEBList" class="colHeader mb-0">참조문서</label><button type="button" class="btn btn-outline-info btn-sm py-0 ml-2" id="btnSelectRelatedEB" name="btnSelectRelatedEB" onclick="onBtnSelectRelatedEBClick()">선택</button>
                    <table class="table table-sm table-bordered mt-2" id="tblRelatedEBList">
                        <tbody></tbody>
                    </table>
                    <input type="text" id="setRelatedEB" name="setRelatedEB" style="display:none;" />
                    <div class="invalid-feedback"></div>
                </div>
                <div id="divAttachBox">
                    <label for="divAttachList" class="colHeader mb-0">첨부파일</label><button type="button" class="btn btn-outline-info btn-sm py-0 ml-2" onclick="javascript:addAttachedFile('new');"><i class="fas fa-plus"></i></button>
                    <div id="divAttachedList" class="mt-2">
                    </div>
                    <div id="divNewAttachedList">
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="d-flex justify-content-center mb-2">
                    <img class="imgLogo" src="" />
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-center">
                        <div>
                            <div id="resultMsgEdit" class="alert alert-primary py-1 mb-2" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-danger" id="btnDelEB" name="btnDelEB" data-toggle="modal" data-target="#modalConfirmDel">삭제</button>
                        <button type="button" class="btn btn-primary" id="btnPreviewEB" name="btnPreviewEB">미리보기</button>
                        <button type="button" class="btn btn-primary" id="btnSaveTempOb" name="btnSaveTempOb">임시저장</button>
                        <button type="button" class="btn btn-primary" id="btnSaveOb" name="btnSaveOb">상신</button>
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

<div class="modal fade" id="modalEditRelatedEB" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <div class="row" style="flex: 1;">
                    <div class="col">
                        <h4 class="modal-title">참조문서목록</h4>
                    </div>
                    <div class="col">
                        <div class="d-flex justify-content-end">
                            <div>
                                <button type="button" class="btn btn-primary" id="btnApplyRelatedEB" name="btnApplyRelatedEB">반영</button>
                            </div>
                            <!-- <div style="margin-left:1rem;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                            </div> -->
                        </div>
                    </div>
                </div>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div id="divSearchRelatedEB" class="row">
                    <div class="col-md search-inline mb-3">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <select class="form-control" id="searchDocKind" name="searchDocKind">
                                    <option value="all">전체</option>
                                    <option value="doc_cd">문서번호</option>
                                    <option value="out_doc_cd">발신측 문서번호</option>
                                    <option value="title">제목</option>
                                </select>
                            </div>
                            <input type="text" class="form-control" id="searchEBKindText" name="searchEBKindText" />
                            <div class="input-group-append">
                                <button type="button" id="btnSearchEBKindText" name="btnSearchEBKindText" class="btn btn-info">
                                    <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                                    <span class="fas fa-magnifying-glass"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md search-inline mb-3">
                        <!-- <div class="input-group">
                            <div class="input-group-prepend">
                                <select class="form-control" id="searchEBDate" name="searchEBDate">
                                </select>
                            </div>
                            <input type="date" class="form-control mr-2" id="searchEBDateFrom" name="searchEBDateFrom" />
                            -
                            <input type="date" class="form-control ml-2" id="searchEBDateTo" name="searchEBDateTo" />
                        </div> -->
                    </div>
                </div>
                <div class="tableFixHead-modal" style="overflow-y:scroll;">
                <table class="table" id="tblEBList">
                    <thead class="thead-light">
                        <tr class="row">
                            <th class="col-md-3 col-5">문서번호</th>
                            <th class="col-md-3 d-none d-md-block">발신측 문서번호</th>
                            <th class="col-md col">제목</th>
                            <th class="col-md-2 col-3 col-w-btn">추가</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                </div>
                <br />
                <caption style="caption-side: top;">선택된 문서 목록</caption>
                <div class="tableFixHead-modal-modal" style="height:16vh !important; overflow-y:scroll;">
                <table class="table" id="tblSelEBList">
                    <thead class="thead-light">
                        <tr class="row">
                            <th class="col-md-3 col-5">문서번호</th>
                            <th class="col-md-3 d-none d-md-block">발신측 문서번호</th>
                            <th class="col-md col">제목</th>
                            <th class="col-md-2 col-3 col-w-btn">삭제</th>
                        </tr>
                    </thead>
                    <tbody class="alignCenter">
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container">
                    <div class="d-flex justify-content-center">
                        <div style="margin-left:1rem;">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>