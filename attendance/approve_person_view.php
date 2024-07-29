<style> 
.hoverColor:hover {
    background-color: #faebd7;
    cursor: pointer;
}
.selectColor {
    background-color: #faebd7;
}
</style>
<script>
$(document).ready(function(){
    // 근태 승인자 가져오기
    getApproverList();

    var thApproverList = $('#tblApproverList').find('thead th');
    $('#tblApproverList').closest('div.tableFixHead').on('scroll', function() {
        thApproverList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    var thUnmanagedList = $('#tblUnmanagedDept').find('thead th');
    $('#tblUnmanagedDept').closest('div.tableMiniFixHead').on('scroll', function() {
        thUnmanagedList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    var thManagedList = $('#tblmanagedDept').find('thead th');
    $('#tblmanagedDept').closest('div.tableMiniFixHead').on('scroll', function() {
        thManagedList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    // 추가 버튼
    $("#btnAddApprover").on('click', addApproverClick);
    // 반영 버튼
    $("#btnAddDept").on('click', addDeptClick);
    // 부서 삭제 버튼
    $("#btnDelDept").on('click', delDeptClick);
    // 근태 승인자 삭제 버튼
    $("#btnDelApprover").on('click', delApproverClick);
});

//검색 - 직원 선택
function onDUSelected() {
    if($("#searchUserId").val()) {
        $("#btnAddApprover").prop('disabled', false);
    } else {
        $("#btnAddApprover").prop('disabled', true);
    }
}

// 근태 승인자 추가
function addApproverClick() {
    $("#mode").val("EDIT_USER");
    $("#addOrDel").val(0);
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0301200.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            getApproverList();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

// 근태 승인자 가져오기
function getApproverList() {
    $("#mode").val("INIT");
    
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0301200.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            var approverList = result["approverList"];

            var html = '';
            $(approverList).each(function(i, info) {
                html += '<tr class="hoverColor" id="approver_'+ info["attappmId"] +'">';
                html += '<td>';
                html += info["workNm"];
                html += '</td>';
                html += '<td>';
                html += info["deptNm"];
                html += '</td>';
                html += '<td>';
                html += info["gradeNm"];
                html += '</td>';
                html += '<td>';
                html += info["userNm"];
                html += '</td>';
                html += '</tr>';
            });
            
            $("#tblApproverList tbody").empty().append(html);
        },
        complete: function() {
            // 근태 승인자 선택
            $(".hoverColor").on("click", function() {
                var approver = $(this).attr("id");
                var approverId = approver.split("_");
                $(".hoverColor").removeClass("selectColor");
                $("#" + approver).addClass("selectColor");

                $("#attappmId").val(approverId[1]);

                $("#btnDelApprover").prop("disabled", false);

                // 관리부서, 미관리부서 가져오기
                getDeptList();
            });

            $("#approver_" + $("#attappmId").val()).addClass("selectColor");
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

// 관리부서, 미관리부서 가져오기
function getDeptList() {
    $("#mode").val("LIST");

    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0301200.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            var managedDept = result["managedDept"];
            var unManagedDept = result["unManagedDept"];
            
            var html = '';
            $(managedDept).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="text-center col-1">';
                html += '<input type="checkbox" name="chkDelManageDept[]" onclick="whenChkClick_chkAll(\'chkDelManageDept\', \'chkAllManaged\')" value="'+ info["deptId"] +'|'+ info["attappdId"] +'">';
                html += '</td>';
                html += '<td class="text-center col-5">';
                html += info["deptworkNm"];
                html += '</td>';
                html += '<td class="text-center col-6">';
                html += info["deptNm"];
                html += '</td>';
                html += '</tr>';
            });
            
            $("#tblmanagedDept tbody").empty().append(html);
            
            html = '';
            $(unManagedDept).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="text-center col-1">';
                html += '<input type="checkbox" name="chkMoveManageDept[]" onclick="whenChkClick_chkAll(\'chkMoveManageDept\', \'chkAllUnManaged\')" value="'+ info["deptId"] +'">';
                html += '</td>';
                html += '<td class="text-center col-5">';
                html += info["deptworkNm"];
                html += '</td>';
                html += '<td class="text-center col-6">';
                html += info["deptNm"];
                html += '</td>';
                html += '</tr>';
            });
            
            $("#tblUnmanagedDept tbody").empty().append(html);
        },
        complete: function() {
            // 버튼 활성화 or 비활성화
            $("input[type=checkbox]").on("click", function() {
                var manageDeptCnt = $("input[name='chkDelManageDept[]']:checked").length;
                var unManageDeptCnt = $("input[name='chkMoveManageDept[]']:checked").length;

                if(manageDeptCnt > 0) {
                    $("#btnDelDept").prop("disabled", false);
                } else {
                    $("#btnDelDept").prop("disabled", true);
                }
                if(unManageDeptCnt > 0) {
                    $("#btnAddDept").prop("disabled", false);
                } else {
                    $("#btnAddDept").prop("disabled", true);
                }
            });
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    })
}

// 반영 버튼
function addDeptClick() {
    $("#mode").val("EDIT_DEPT");

    $("#addOrDel").val(0);

    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0301200.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            var errorCnt = result["errorCnt"];
            if(errorCnt == 0) {
                getDeptList();
            } else {
                alert("오류 발생 : 개발자에게 문의하세요");
            }
        },
        complete: function() {
            $("#chkAllUnManaged").prop("checked", false);
            $("#btnAddDept").prop("disabled", true);
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    })
}

// 부서 삭제 버튼
function delDeptClick() {
    $("#mode").val("EDIT_DEPT");

    $("#addOrDel").val(1);

    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0301200.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            var errorCnt = result["errorCnt"];
            if(errorCnt == 0) {
                getDeptList();
            } else {
                alert("오류 발생 : 개발자에게 문의하세요");
            }
        },
        complete: function() {
            $("#chkAllManaged").prop("checked", false);
            $("#btnDelDept").prop("disabled", true);
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    })
}

// 근태 승인자 삭제
function delApproverClick() {
    $("#mode").val("EDIT_USER");
    $("#addOrDel").val(1);
    $.ajax({ 
        type: "POST", 
        url: "/gw/bs/bs0301200.php",
        data: $("#mainForm").serialize(), 
        dataType: "json", 
        success: function(result) {
            getApproverList();
            getDeptList();
            $("#btnDelApprover").prop('disabled', true);
            $("#attappmId").val('');
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}
</script>
<form id="mainForm" name="mainForm" method="post">
<div class="row">
    <div class="col-md-6">
        <label>근태 승인자</label>
        <div id="divSearchUser" class="search-inline mb-2 row">
            <div class="input-group col">
                <input type="text" class="form-control" id="searchUserNm" name="searchUserNm" readonly />
                <input type="hidden" id="searchUserId" name="searchUserId" />
                <div class="input-group-append">
                    <button class="btn btn-success" type="button" onclick="onBtnSelectDeptUserClick('BS', 'searchUser', false, 'N', 'N')">선택</button>
                </div>
            </div>
            <div class="col-4 text-right">
                <button type="button" class="btn btn-sm btn-info" id="btnAddApprover" disabled>추가</button>
                <button type="button" class="btn btn-sm btn-warning" id="btnDelApprover" disabled>삭제</button>
            </div>
        </div>
        <div class="tableFixHead">
            <table class="table" id="tblApproverList">
                <thead class="thead-light">
                    <tr>
                        <th>사업장</th>
                        <th>부서</th>
                        <th>직급</th>
                        <th>사원명</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <br />
        <div id="divSearchUser" class="search-inline mb-2 mt-2 row">
            <div class="input-group col">
                <label>관리부서</label>
            </div>
            <div class="col-4 text-right">
                <button type="button" class="btn btn-sm btn-warning" id="btnDelDept" disabled>삭제</button>
            </div>
        </div>
        <div class="tableMiniFixHead">
            <table class="table" id="tblmanagedDept">
                <thead class="thead-light">
                    <tr class="row">
                        <th class="col-1"><input type="checkbox" id="chkAllManaged" onclick="onChkAllClick(this, 'chkDelManageDept')"/></th>
                        <th class="col-5">사업장</th>
                        <th class="col-6">부서</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <div id="divSearchUser" class="search-inline mb-2 mt-2 row">
            <div class="input-group col">
                <label>미관리 부서</label>
            </div>
            <div class="col-4 text-right">
                <button type="button" class="btn btn-sm btn-info" id="btnAddDept" disabled>반영</button>
            </div>
        </div>
        <div class="tableMiniFixHead">
            <table class="table" id="tblUnmanagedDept">
                <thead class="thead-light">
                    <tr class="row">
                        <th class="col-1"><input type="checkbox" id="chkAllUnManaged" onclick="onChkAllClick(this, 'chkMoveManageDept')"/></th>
                        <th class="col-5">사업장</th>
                        <th class="col-6">부서</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once '../cm/cm_select_dept_user_view.php';
?>

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="addOrDel" name="addOrDel" />
<input type="hidden" id="attappmId" name="attappmId" />
</form>
