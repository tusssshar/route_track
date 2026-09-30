var reqdMsg = 'Field is mandatory.';
var emailMsg = " Invalid e-mail format.";
var valid_mobile = " Invalid mobile number.";
var passwordlenMsg = "Length should not be more than or equal 5 Characters!.";
var wrongNoFormat = "Wrong No. Format !!";
function admin_enter_login(e, login_form, request_name,root_path)
{
    if (e.which == 13)
    {
        check_admin_login(login_form,request_name,root_path);
    }

}
///////check login script
function check_admin_login(form_id, request_name,root_path)
{
    $("#common_error_div").html("");
    //var is_true = form_validation();
    var userid=$("#userid").val().trim();
    var password=$("#password").val().trim();
    
    if(userid=="")
    {
        $("#euserid").html("Please enter the userId");
        $("#userid").focus();
        return false;
    }
    if(password=="")
    {
        $("#epassword").html("Please enter the password");
        $("#password").focus();
        return false;
    }
        var str = $("#" + form_id).serialize();
        $.ajax
        ({
            url: request_name,
            data: str,
            success: function (result)
            {
                //alert(result);
                $("#common_error_div").html("");
                if (result == "true")
                {
                    window.location.href = root_path+"/admin/home.php";
                 
                } else
                {

                    $("#common_error_div").html("UserID or password did not match ");
                }
            },
            error: function ()
            {
                alert("error");
            }
        });
}

//////////get code selecetd value
function get_selected_value(code)
{
   
    if(code=="add_new")
    {
        alert("Not available");
        return false;
        $("#new_code_tr").show();
    }
    else
    {
        $("#new_code_tr").hide();
    }
}
//////////////save masters
function save_master_value(root_path)
{
    var exist_code=$("#exist_code").val().trim();
    var new_value=$("#new_value").val().trim();
    var hidden_edit_id=$("#hidden_edit_id").val().trim();
    var is_true='true';
    if(new_value=="")
    {
        $("#enew_value").html(reqdMsg);
        is_true='false';
    }
    if(is_true=='true')
    {
        new_value=encodeURIComponent(new_value);
        var str="new_value="+new_value+"&hidden_edit_id="+hidden_edit_id+"&exist_code="+exist_code;
        var request_name=root_path+"/admin/ajax/admin_ajax_action.php?action=save_master_values";
        $.ajax
            ({
                url: request_name,
                data: str,
                type: "POST",
                success: function (result)
                {
                    //alert(result);
                    location.reload();
                },
                error: function ()
                {
                    alert("errorrrr");
                }
            });
    }
}


/////gert search text
function get_search_text(page_path, flag, menu_id)
{
    var search_text = $("#search_text").val().trim();

    if (flag == 1)
    {
        search_text = replaceall(search_text, ' ', '%20');
        page_path = page_path + "&search_text=" + search_text;
    } else
    {
        if (search_text == "")
        {
            $("#er_search_textbox").html("Please eneter text...");
            return false;
        }
        //search_text=search_text.replaceAll(' ', "%20");
        search_text = replaceall(search_text, ' ', '%20');
        //alert(search_text);
        //alert(page_path);
        //var n = page_path.search("?");
        var str = page_path; 
        var n = str.indexOf("?");
        if(n==-1)
        {
            page_path = page_path + "?page=0&search_text=" + search_text + "";
        }
        else
        {
            page_path = page_path + "&page=0&search_text=" + search_text + "";
        }
        //alert(page_path);
        //
        //page_path=page_path+"?page=0&search_text='asda%20d'";
    }
    // alert(page_path);
    load_new_page(page_path, menu_id);
}

//////////search enter event script
function serach_by_enter(e, target_page, menu_id)
{
    if (e.which == 13)
    {
        get_search_text(target_page, menu_id)
    }

}

function replaceall(str, replace, with_this)
{
    var str_hasil = "";
    var temp;

    for (var i = 0; i < str.length; i++) // not need to be equal. it causes the last change: undefined..
    {
        if (str[i] == replace)
        {
            temp = with_this;
        } else
        {
            temp = str[i];
        }

        str_hasil += temp;
    }

    return str_hasil;
}
////////edit maste values
function edit_master_code(root_path,current_id)
{
    //alert("#table_hidden_val_"+current_id);
    var hidden_val=$("#table_hidden_val_"+current_id).val().trim();
    //alert(hidden_val);
    var parts=hidden_val.split("??");
    var id=parts[0];
    var code=parts[1];
    var value=parts[2];
    $("#hidden_edit_id").val(id);
    $("#new_value").val(value);
}

///////////view employer details
function view_admin_details(root_path,employer_id)
{
    //var redirectUrl=root_path+"/admin/content/employer/admin_view_employer_details.php";
    var redirectUrl=root_path;
    //alert(redirectUrl);
    myRedirect(redirectUrl, 'id', employer_id) 
}

////////////manage credit points
function manage_credit_points111(form_id,root_path)
{
    var request_name=root_path+"/admin/ajax/admin_ajax_action.php?action=manage_credit_points";
    var str = $("#" + form_id).serialize();
    $.ajax
    ({
        url: request_name,
        data: str,
        type: "POST",
        success: function (result)
        {
            //alert(result);
            alert("Request submitted successfully");
            location.reload();
        },
        error: function ()
        {
            alert("errorrrr");
        }
    });
}

////////////package edit
function edit_packages(package_id,package_type,package_amt,package_points)
{
    $("#hidden_edit_id").val(package_id);
    $("#package_type").val(package_type);
    $("#package_amt").val(package_amt);
    $("#package_points").val(package_points);
}
//////////////login to employee or employer from admin panel
function login_to_employee_employer(root_path,request_name,user_id,password,empl_emplyoer)
{
    var str="userid="+user_id+"&password="+password+"&employye_emplyer="+empl_emplyoer;
    //alert(str);
    
    $.ajax
                ({
                    url: request_name,
                    data: str,
                    success: function (result)
                    {
                        //alert(result);
                        $("#common_error_div").html("");
                        if (result == "true")
                        {//alert(empl_emplyoer);
                            if(empl_emplyoer=='employee')
                            {
                                window.location.href = root_path+"/employee/pages/employee_dashboard.php";
                            }
                            else if(empl_emplyoer=='employer')
                            {
                                window.location.href = root_path+"/employer/pages/employer_dashboard.php";
                            }
                            
                        } else
                        {

                            $("#common_error_div").html("UserID or password did not match ");
                            alert("Problem in login details");
                        }
                    },
                    error: function ()
                    {
                        alert("error");
                    }
                });
}

////delete master value
function delete_master_code(root_path,code_id)
{
    if(confirm("Do you want to delete this data"))
    {
        var str="code_id="+code_id;
        var request_name=root_path+"/admin/ajax/admin_ajax_action.php?action=delete_master_value";
        $.ajax
        ({
            url: request_name,
            data: str,
            success: function (result)
            {
                //alert(result);
                location.reload();
            },
            error: function ()
            {
                alert("error");
            }
        });
    }
}

/////delete employer and employer
function delete_employee_employer(request_name)
{
    //alert(request_name);
    if(confirm("Do you want to delete this data"))
    {
        $.ajax
        ({
            url: request_name,
            data: '',
            success: function (result)
            {
                //alert(result);
                location.reload();
            },
            error: function ()
            {
                alert("error");
            }
        });
    }
}
$(document).ready(function(){
	
//	$('.item .delete').click(function(){
		
//		var elem = $(this).closest('.item');
//		
//		$.confirm({
//			'title'		: 'Delete Confirmation',
//			'message'	: 'You are about to delete this item. <br />It cannot be restored at a later time! Continue?',
//			'buttons'	: {
//				'Yes'	: {
//					'class'	: 'blue',
//					'action': function(){
//						elem.slideUp();
//					}
//				},
//				'No'	: {
//					'class'	: 'gray',
//					'action': function(){}	// Nothing to do in this case. You can as well omit the action property.
//				}
//			}
//		});
		
//	});
	//var is_trueee=confirmation_box();
        
});
//confirmation_box();
function confirmation_box()
{
    var is_flag="true";
    var elem = $(this).closest('.item');
		
        $.confirm({
                'title'		: 'Delete Confirmation',
                'message'	: 'You are about to delete this item. <br />It cannot be restored at a later time! Continue?',
                'buttons'	: {
                        'Yes'	: {
                                'class'	: 'blue',
                                'action': function(){
                                        elem.slideUp();
                                        is_flag="true";
                                }
                        },
                        'No'	: {
                                'class'	: 'gray',
                                'action': function()
                                {
                                    is_flag="false";
                                }	// Nothing to do in this case. You can as well omit the action property.
                        }
                }
        });
        return is_flag;
}

function myRedirect(redirectUrl, arg, value) {
   
  var form = document.createElement("form");
    input = document.createElement("input");

    form.action = redirectUrl;
    form.method = "post"

    input.type = "hidden";
    input.name = arg;
    input.value = value;
    form.appendChild(input);
//    window.addEventListener('load', function () {
    document.body.appendChild(form);
    form.submit();
//    });
    
};
function setDeceleratingTimeout( callback, factor, times )
{
  var internalCallback = function( t, counter )
  {
    return function()
    {
      if ( --t > 0 )
      {
        window.setTimeout( internalCallback, ++counter * factor );
        callback();
      }
    }
  }( times, 0 );

  window.setTimeout( internalCallback, factor );
};

// console.log() requires firebug
var yes_flag=0;
window.setInterval(function(){
    var errormsg_content=jQuery(".error-msg").html();
    //alert(errormsg_content);
    if(errormsg_content.trim() !="" && yes_flag==0)
    {
        $(".error-msg").css("display","block");
        yes_flag=1;
        jQuery(window).scrollTop(0);
        //alert(jQuery("#errormsg").html());
        setDeceleratingTimeout( function(){hide_error_msg();yes_flag=0;}, 4000, 2 );
    }
}, 1000);

function hide_error_msg()
{
  var request_name=SITEROOT+"/admin/ajax/admin_ajax_action.php?action=unset_error_msg"
  $.ajax
        ({
            url: request_name,
            data: "",
            type: "POST",
            success: function (result)
            {
                //alert(result);
                $(".error-msg").html("");
                $(".error-msg").css("display","none");
            },
            error: function ()
            {
                alert("errorrrr");
            }
        });
}