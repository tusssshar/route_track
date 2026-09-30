$(document).ready(function () {
    $("#quickydelilookup").submit(function () {
        var cust_name = document.getElementById("cust_name").value;
        var delivery = document.getElementById("delivery").value;
        if (cust_name.length == 0 && delivery.length==0) {
            alert("Please enter Customer Name or Delivery#");
            return false;
        } else {
            return  true;
        }
    });
    loadDefaultList();
  

});
//############ Load Default Route status- Start################//
function loadDefaultList() {
   
    loadingIndicator('start');
    $.get("ajax/home_route_status.php", "", function (data) {
//         alert("load..."+data);
        $("#MyAjaxInterView_route_status").html(data);
        loadingIndicator('end');
    }, "html");
}
//############ Load Default Route status- End################//

function show_route_status() {
    var route_id = document.getElementById("route_id").value;
    loadingIndicator('start');
    $.get("ajax/home_route_status.php", {"route_id": route_id}, function (data) {
        $("#MyAjaxInterView_route_status").html(data);
        loadingIndicator('end');
    }, "html");
}


function disable_delivery() {
    var cust_name = document.getElementById("cust_name").value;
    document.getElementById("delivery").disabled = false;
    if (cust_name.length != 0) {
        document.getElementById("delivery").disabled = true;
    }
}
function disable_customer_name() {
    var delivery = document.getElementById("delivery").value;
    document.getElementById("cust_name").disabled = false;
    if (delivery.length != 0) {
        document.getElementById("cust_name").disabled = true;
    }
}

function show_route() {
    var deliver_type = $("#deliver_type").val();
    var limit = $("#limit").val();
    if (deliver_type != "") {
        loadingIndicator('start');
        $.get("ajax/home.php", {"deliver_type": deliver_type, "limit": limit}, function (data) {
            $("#MyAjaxInterView").html(data);
            loadingIndicator('end');
        }, "html");
    } else {
        $("#MyAjaxInterView").empty();
    }

}
