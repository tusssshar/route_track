
//	search code
var ajaxFilePath = "ajax/manifests.php";
$(document).ready(function () {

    if (manifest_id_from_report > 0) {
        //loadingIndicator('start');
        $.ajax({
            url: "ajax/manifests_new.php",
            method: "POST",
            data: {manifest_id: manifest_id_from_report, delivery_id: delivery_id_from_report},
            dataType: "html"
        }).done(function (data) {
            $("#MyAjaxInterView").html(data);
            //loadingIndicator('end');
        }).fail(function (jqXHR, textStatus) {
            alert("Request failed: " + textStatus);
        });

    } else {

        if (document.getElementById("txtSearch")) {
            $("#txtSearch").focus();
        }
        loadDefaultList();

        $("#form").submit(function () {

            loadingIndicator('start');
            $.post(ajaxFilePath, $(this).serializeArray(), function (data) {

                $("#MyAjaxInterView").html(data);

                loadingIndicator('end');
            }, "html");
            return false;
        });//search submit
    }
});//document ready


//	clear all search values
function clearSearchValues() {
    $("#txtSearch").val("");
    loadDefaultList();
}


function loadDefaultList() {
    loadingIndicator('start');
    $.get("ajax/manifests.php", "", function (data) {
        $("#MyAjaxInterView").html(data);
        loadingIndicator('end');
    }, "html");
}

function ajaxNewModify(manifest_id) {
    //$("#divList").removeClass("col-md-12").addClass("col-md-8");
    //$("#divForm").removeClass("col-md-0").addClass("col-md-4").show();

    loadingIndicator('start');
    $.ajax({
        url: "ajax/manifests_new.php",
        method: "POST",
        data: {manifest_id: manifest_id},
        dataType: "html"
    }).done(function (data) {
        $("#MyAjaxInterView").html(data);
        loadingIndicator('end');
    }).fail(function (jqXHR, textStatus) {
        alert("Request failed: " + textStatus);
    });
}



function ajaxNewModifyFormCancel() {
    loadDefaultList();
    $("#divList").removeClass("col-md-8").addClass("col-md-12");
    $("#divForm").removeClass("col-md-4").addClass("col-md-0").hide();
    $("#divForm").html("");
}





