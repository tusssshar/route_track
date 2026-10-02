</head>
<body class="<?php echo company_settings::theme()["dark"] ? "rt-dark" : ""; ?>">
    <!-- loading indicator -->
    <div class="loading-at-top-header" id="topLoadingIndicator" style="display:none">
      <!--<span class="loading-at-top-header-span">&nbsp;&nbsp;&nbsp;<i class="fa fa-circle-o-notch fa-spin"></i> Loading...&nbsp;&nbsp;&nbsp;</span>-->
        <button type="button" class="btn btn-info">&nbsp;&nbsp;&nbsp;<i class="fa fa-circle-o-notch fa-spin"></i> Loading...&nbsp;&nbsp;&nbsp;</button>
    </div>
    <!-- Fixed navbar -->
    <nav class="navbar navbar-default navbar-fixed-top">
        <div style="padding:0 10px;">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
                <a class="navbar-brand" href="<?php echo SITEROOT . '/admin/home.php' ?>"><?php echo company_settings::brand_html(true); ?></a> </div>
            <div id="navbar" class="navbar-collapse collapse">
                <?php
                if (isset($_SESSION["routetrac-admin"]["al_id"])) {
                    ?>
                    <ul class="nav navbar-nav">
                        <li><a href="<?php echo SITEROOT . '/admin/home.php' ?>">Home</a></li>

                        <?php
                        $objGlobalConnection1 = new dbSqlConnection();
                        $oGlobalConnection = $objGlobalConnection1->Connect(HST_1, USR_1, PWD_1, DBN_1);

                        $res_area = $functions->user_access_level(1, $_SESSION["routetrac-admin"]["access_level"]);
                        $res_route = $functions->user_access_level(2, $_SESSION["routetrac-admin"]["access_level"]);
                        $res_package_type = $functions->user_access_level(3, $_SESSION["routetrac-admin"]["access_level"]);

                        if ($res_area == 1 || $res_route == 1 || $res_package_type == 1) {
                            ?>
                            <li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Masters<span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <?php
                                    if ($res_area == 1) {
                                        ?>

                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/area.php' ?>">Delivery
                                                Areas</a></li>
                                    <?php
                                    }

                                    if ($res_route == 1) {
                                        ?>
                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/routes.php' ?>">Routes</a></li>
                                    <?php
                                    }
                                    if ($res_package_type == 1) {
                                        ?>
                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/packages.php' ?>">Package Type</a></li>
        <?php } ?>


                                </ul>
                            </li>
                        <?php } ?>

                        <!--Manifest Menu -->
                        <?php
                        $res_manifest = $functions->user_access_level(7, $_SESSION["routetrac-admin"]["access_level"]);
                        if ($res_manifest == 1) {
                            ?>
                            <li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Manifest <span class="caret"></span></a>

                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo SITEROOT . '/admin/m/master/manifests.php' ?>">List</a></li>
                                    <li><a href="<?php echo SITEROOT . '/admin/m/master/create_manifest.php' ?>">Create</a></li>
                                    <li><a href="#">Import</a></li>


                                </ul>

                            </li>
                            <!--Customer Menu-->
                        <?php
                        }

                        $res_fleet = $functions->user_access_level(fleet::FLEET_MENU_ID, $_SESSION["routetrac-admin"]["access_level"]);
                        if ($res_fleet == 1) {
                            ?>
                            <!--Fleet Menu-->
                            <li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Fleet <span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo SITEROOT . '/admin/m/fleet/trips.php' ?>">Trips &amp; Dispatch</a></li>
                                    <li><a href="<?php echo SITEROOT . '/admin/m/fleet/vehicles.php' ?>">Vehicles</a></li>
                                    <li><a href="<?php echo SITEROOT . '/admin/m/fleet/fleet_report.php' ?>">Vehicle Cost &amp; Load Report</a></li>
                                </ul>
                            </li>
                        <?php
                        }

                        $res_customer = $functions->user_access_level(4, $_SESSION["routetrac-admin"]["access_level"]);

                        if ($res_customer == 1) {
                            ?>
                            <li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Customer <span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <?php
                                    if ($res_customer == 1) {
                                        ?>
                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/customer.php' ?>">Customers</a></li>
        <?php }
        ?>

                                </ul>
                            </li>


                        <?php } ?>

                        <!--Staff Menu-->
                        <?php
                        $res_users = $functions->user_access_level(6, $_SESSION["routetrac-admin"]["access_level"]);
                        $res_access_level = $functions->user_access_level(8, $_SESSION["routetrac-admin"]["access_level"]);

                        if ($res_users == 1 || $res_access_level == 1) {
                            ?>

                            <li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Staff <span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <?php
                                    if ($res_users == 1) {
                                        ?>
                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/users.php' ?>">Users</a></li>
                                    <?php
                                    }

                                    if ($res_access_level == 1) {
                                        ?>
                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/access_level.php' ?>">Access Level</a></li>
                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/options.php' ?>">Options</a></li>
                                    <?php } ?>
                                    <?php if ((int) $_SESSION["routetrac-admin"]["access_level"] === 1) { ?>
                                        <li><a href="<?php echo SITEROOT . '/admin/m/master/company_settings.php' ?>">Company Settings</a></li>
                                    <?php } ?>

                                </ul>
                            </li>
                        <?php } ?>


                        <!--Reports-->
                        <?php
                        $reports_access = $functions->user_access_level(9, $_SESSION["routetrac-admin"]["access_level"]);

                        if ($reports_access == 1) {
                            ?>

                            <li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Reports <span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo SITEROOT . '/admin/m/reports/delivery_status.php' ?>">Order Status</a></li>
                                    <li><a href="<?php echo SITEROOT . '/admin/m/reports/route_status.php' ?>">Route Status</a></li>
                                    <li><a href="<?php echo SITEROOT . '/admin/m/reports/orders_report.php' ?>">Update Order</a></li>
                                </ul>
                            </li>
    <?php } ?>


  <li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Map <span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo SITEROOT . '/admin/m/google_map/locate_drivers.php' ?>">Track Drivers</a></li>
                                   
                                </ul>
                            </li>


                    </ul>



                    <ul class="nav navbar-nav navbar-right">
                        <li class="active1"><a href="<?php echo SITEROOT . '/admin/logout.php' ?>" title="Sign out"><i class="fa fa-sign-out fa-size" aria-hidden="true"></i></a></li>
                    </ul>

                    <div class="pull-right rt-user"><span class="rt-avatar"><?php echo htmlspecialchars(substr($_SESSION["routetrac-admin"]["al_username"], 0, 1)); ?></span><?php echo htmlspecialchars($_SESSION["routetrac-admin"]["al_username"]); ?></div>

    <?php
}
?>
            </div>
            <!--/.nav-collapse -->
        </div>
    </nav>
    <script>
    // Highlight the top menu that contains the current page
    (function(){
        var here = location.pathname.replace(/\/+$/, "");
        document.querySelectorAll(".navbar-nav a[href]").forEach(function(a){
            if(a.getAttribute("href").charAt(0) == "#"){ return; }	// dropdown toggles
            var path = a.pathname.replace(/\/+$/, "");
            if(path && path == here){
                var top = a.closest(".navbar-nav > li");
                if(top){ top.classList.add("active"); }
            }
        });
    })();
    </script>
