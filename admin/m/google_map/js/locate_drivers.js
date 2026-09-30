
//	search code

$(document).ready(function () {
    if (document.getElementById("txtSearch")) {
        $("#txtSearch").focus();
    }
    loadDefaultList();
    $("#form").submit(function () {
        loadingIndicator('start');

        var driver_id = $("#diver_id").val();
        var date_range_from = $("#date_range_from").val();
        var date_range_to = $("#date_range_to").val();

        if (driver_id != "" && date_range_from != "" && date_range_to != "") {

            var ajaxFilePath = "ajax/get_datewise_route.php";
            $.post(ajaxFilePath, $(this).serializeArray(), function (data) {
                $("#MyAjaxInterView").html(data);
                loadingIndicator('end');
            }, "html");

        } else {

            var ajaxFilePath = "ajax/locate_drivers.php";
            $.post(ajaxFilePath, $(this).serializeArray(), function (data) {
                $("#MyAjaxInterView").html(data);
                loadingIndicator('end');
            }, "html");
        }
        return false;
    });//search submit
});//document ready


//	clear all search values
function clearSearchValues() {

    $("#txtSearch").val("");
    loadDefaultList();
}


function loadDefaultList() {

    loadingIndicator('start');
    $.get("ajax/locate_drivers.php", "", function (data) {
        $("#MyAjaxInterView").html(data);
        loadingIndicator('end');
    }, "html");
}

function ajaxNewModify(driver_id) {
    loadingIndicator('start');
    $.ajax({
        url: "ajax/locate_drivers.php",
        method: "POST",
        data: {driver_id: driver_id},
        dataType: "html"
    }).done(function (data) {
        $("#MyAjaxInterView").html(data);
        loadingIndicator('end');
    }).fail(function (jqXHR, textStatus) {
        alert("Request failed: " + textStatus);
    });
}


function  show_data(driver_id) {
    var from_date = $("#date_range_from").val();
    var to_date = $("#date_range_to").val();
    if (driver_id > 0) {
        $("#date_range_from").prop('disabled', false);
        $("#date_range_to").prop('disabled', false);
    } else {
        $("#date_range_from").val("");
        $("#date_range_to").val("");
        $("#date_range_from").prop('disabled', true);
        $("#date_range_to").prop('disabled', true);

    }
}