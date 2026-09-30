
//	search code
var ajaxFilePath = "ajax/orders_report.php";
$(document).ready(function () {
    if (document.getElementById("txtSearch")) {
        $("#txtSearch").focus();
    }
    loadDefaultList(cust_name, delivery);
    $("#form").submit(function () {

        loadingIndicator('start');
        $.post(ajaxFilePath, $(this).serializeArray(), function (data) {

            $("#MyAjaxInterView").html(data);

            loadingIndicator('end');
        }, "html");
        return false;
    });//search submit
});//document ready


//	clear all search values
function clearSearchValues() {
    $("#txtSearch").val("");
    loadDefaultList();
}

function loadDefaultList(cust_name, delivery) {
    loadingIndicator('start');

    $.get("ajax/orders_report.php", {"cust_name": cust_name, "delivery": delivery}, function (data) {
        $("#MyAjaxInterView").html(data);
        loadingIndicator('end');
    }, "html");
}

function ajaxNewModify(manifest_id,delivery_id) {
    var path = window.location.hostname;
    loadingIndicator('start');
    window.location.href = 'http://' + path + '/projects/routetrac/admin/m/master/manifests.php?manifest_id_from_report=' + manifest_id+"&delivery_id_from_report="+delivery_id;
}
        