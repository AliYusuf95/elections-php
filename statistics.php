<?php
global $con;
include 'config.php';
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin"]) || $_SESSION["admin"] !== true){
    header("location: vlogin.php");
    exit;
}

$logged_user = $_SESSION['username'];
$isAllLocationsClosed = mysqli_num_rows(mysqli_query($con,"SELECT id FROM locations WHERE open = true")) == 0;

if ($isAllLocationsClosed) {

	$stmt = $con->prepare("SELECT COUNT(id) as total_voters FROM voters");
    $stmt->execute();
    $stmt->bind_result($total_voters);
    $stmt->fetch();
    $stmt->close();

    $stmt = $con->prepare("SELECT COUNT(id) as total_voters_registered FROM voters_data");
    $stmt->execute();
    $stmt->bind_result($total_voters_registered);
    $stmt->fetch();
    $stmt->close();

    $stmt = $con->prepare("SELECT COUNT(id) as total_done_voters FROM voters_data WHERE status = 3");
    $stmt->execute();
    $stmt->bind_result($total_done_voters);
    $stmt->fetch();
    $stmt->close();

    $stmt = $con->prepare("SELECT COUNT(id) as total_candidates FROM candidates");
    $stmt->execute();
    $stmt->bind_result($total_candidates);
    $stmt->fetch();
    $stmt->close();

    $voters_percent = $total_voters_registered > 0 ? round(($total_done_voters / $total_voters) * 100, 2) : 0;

    $localTime = new DateTime('now');
    $utcOffset = $localTime->getOffset() / 3600;

    $stmt = $con->prepare("SELECT COUNT(id) as total_voters_done, HOUR(updatedAt) as hour FROM voters_data WHERE status = 3 GROUP BY HOUR(updatedAt)");
    $stmt->execute();
    $stmt->bind_result($total_voters_done, $hour);
    $voters_done_volume_per_hour = [];
    while ($stmt->fetch()) {
        $hour += $utcOffset;
        $hour = $hour < 0 ? 24 + $hour : $hour;
        $voters_done_volume_per_hour[$hour] = $total_voters_done;
    }
    $stmt->close();

}


?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

	<title>نظام التصويت الإلكتروني</title>

	<!-- Main Styles -->
	<link rel="stylesheet" href="assets/styles/style-horizontal.min.css">

	<!-- Material Design Icon -->
	<link rel="stylesheet" href="assets/fonts/material-design/css/materialdesignicons.css">

	<!-- mCustomScrollbar -->
	<link rel="stylesheet" href="assets/plugin/mCustomScrollbar/jquery.mCustomScrollbar.min.css">

	<!-- Waves Effect -->
	<link rel="stylesheet" href="assets/plugin/waves/waves.min.css">

	<!-- Sweet Alert -->
	<link rel="stylesheet" href="assets/plugin/sweet-alert/sweetalert.css">

	<!-- Percent Circle -->
	<link rel="stylesheet" href="assets/plugin/percircle/css/percircle.css">

	<!-- Chartist Chart -->
	<link rel="stylesheet" href="assets/plugin/chart/chartist/chartist.min.css">

	<!-- FullCalendar -->
	<link rel="stylesheet" href="assets/plugin/fullcalendar/fullcalendar.min.css">
	<link rel="stylesheet" href="assets/plugin/fullcalendar/fullcalendar.print.css" media='print'>

	<!-- RTL -->
	<link rel="stylesheet" href="assets/styles/style-rtl.min.css">
    <style>
        img:hover {
            -webkit-transform:scale(3.8) !important; /* or some other value */
            transform:scale(2.8) !important;
        }
        @media (max-width: 1024px) {
            .nav-container.container {
                width: unset;
            }
        }
        .ct-label.ct-horizontal.ct-start {
            justify-content: end;
        }
    </style>
</head>

<body>
<div id="wrapper">
    <header class="fixed-header">
        <div class="header-top">
            <div class="container">
                <div class="pull-right">
                    <button type="button" aria-label="Close" class="btn btn-box-tool hidden-on-desktop js__menu_button"><i class="fa fa-bars padding-10"></i></button>
                    <a class="logo" style="font-weight: bold;">نظام التصويت الإلكتروني</a>
                </div>
                <!-- /.pull-right -->
                <div class="pull-left">
                    <a class="logo" style="font-size: 15px;">مرحبا <?php echo $logged_user; ?></a>
                </div>

            </div>
            <!-- /.container -->
        </div>
        <!-- /.header-top -->
        <nav class="nav nav-horizontal">
            <button type="button" class="menu-close hidden-on-desktop js__close_menu"><i class="fa fa-times"></i><span>إغلاق</span></button>
            <div class="container nav-container">
                <ul class="menu">
                    <li>
                        <a href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">المراكز</span></a>
                    </li>
                    <li>
                        <a href="vusers"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">الأعضاء</span></a>
                    </li>
                    <li>
                        <a href="vcandidates"><i class="ico mdi mdi-account-multiple"></i><span style="font-weight: bold;">المرشحون</span></a>
                    </li>
                    <li class="current">
                        <a class="text-primary" href="statistics"><i class="ico mdi mdi-chart-bar"></i><span style="font-weight: bold;">الإحصائيات</span></a>
                    </li>
                    <li>
                        <a href="log"><i class="ico mdi mdi-menu"></i><span style="font-weight: bold;">سجل النظام</span></a>
                    </li>
                    <li>
                        <a href="logout"><i class="ico mdi mdi-logout"></i><span style="font-weight: bold;">تسجيل خروج</span></a>
                    </li>
                </ul>
                <!-- /.menu -->
            </div>
            <!-- /.container -->
        </nav>
        <!-- /.nav-horizontal -->
    </header>
    <!-- /.fixed-header -->

	<div class="main-content container">
		<?php 
		if(!$isAllLocationsClosed):
		?>
		<br />
		<div class="alert alert-danger">
			<strong>خطأ</strong> لا يمكن عرض النتائج إلا في حال إغلاق جميع المراكز
		</div>
		<?php 
		else:
		?>
		<div class="row small-spacing">

            <div class="col-md-6 col-xs-12">
                <div class="box-content bg-purple text-white">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-users"></i>
                        <p class="text text-white" style="font-weight:bold;">عدد المرشحين</p>
                        <h2 class="counter"><?php echo $total_candidates; ?></h2>
                    </div>
                </div>
            </div>

			<div class="col-md-6 col-xs-12">
				<div class="box-content bg-info text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-users"></i>
						<p class="text text-white" style="font-weight:bold;">عدد الناخبين في السجل</p>
						<h2 class="counter"><?php echo $total_voters; ?></h2>
					</div>
				</div>
			</div>

            <div class="col-md-6 col-xs-12">
                <div class="box-content bg-primary text-white">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-file-text"></i>
                        <p class="text text-white" style="font-weight:bold;">الناخبين المسجلين للتصويت</p>
                        <h2 class="counter"><?php echo $total_voters_registered; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xs-12">
                <div class="box-content text-white <?php echo $total_done_voters != $total_voters_registered ? 'bg-danger' : 'bg-success'; ?>">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-check"></i>
                        <p class="text text-white" style="font-weight:bold;">الناخبين الذين قاموا بالتصويت</p>
                        <h2 class="counter"><?php echo $total_done_voters; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-xs-12">
                <div class="box-content text-white <?php echo $voters_percent >= 50 ? 'bg-success' : 'bg-danger'; ?>">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-percent"></i>
                        <p class="text text-white" style="font-weight:bold;">نسبة الناخبين الذين قاموا بالتصويت</p>
                        <h2 class="counter"><?php echo $voters_percent; ?>%</h2>
                    </div>
                </div>
            </div>

		</div>
		<!-- .row -->

    <!-- voters volume chart        -->
        <div class="row small-spacing">
            <div class="col-xs-12">
                <div class="box-content">
                    <h4 class="box-title">عدد الناخبين الذين قاموا بالتصويت حسب الساعة</h4>
                    <div class="chart-container">
                        <div class="chart has-fixed-height" id="voters-volume-chart"></div>
                    </div>
                </div>
            </div>
        </div>

		<div class="row small-spacing">
			<div class="col-xs-12">
				<div class="box-content">
					<h4 class="box-title">النتائج</h4>
					<!--<h5>سيتم عرض النتائج بعد إنتهاء المدة الزمنية للتصويت</h5>-->
					<br />
					<!-- /.box-title -->

					<table id="main" class="table table-striped table-bordered display" style="width:100%">
						<thead>
							<tr>
								<th></th>
								<th>الإسم</th>
								<th>عدد الأصوات</th>
							</tr>
						</thead>
						<tbody>
							<?php
							
							$select_candidates = mysqli_query($con, "SELECT c.name as name, v.votes as votes FROM candidates c LEFT JOIN voting_results v ON c.id = v.candidateId ORDER BY c.name");
                            
							while($fetch_candidates = mysqli_fetch_assoc($select_candidates)){

							echo '<tr>
									<td></td>
									<td>'.$fetch_candidates['name'].'</td>
									<td>'.($fetch_candidates['votes'] ?? 0).'</td>
									</tr>';
							}
							?>
						</tbody>
					</table>


				</div>
                <?php
                // select voters group by fromwhere
                $select_voters_group = mysqli_query($con, "SELECT fromwhere, count(*) as total FROM voters_data where fromwhere <> '' GROUP BY fromwhere");
                if (mysqli_num_rows($select_voters_group) > 0):
                    ?>
                    <div class="box-content">
                        <h4 class="box-title">إجمالي الناخبين حسب المنطقة</h4>
                        <?php
                        // select voters group by fromwhere
                        $select_voters_group = mysqli_query($con, "SELECT fromwhere, count(*) as total FROM voters_data where fromwhere <> '' GROUP BY fromwhere");
                        foreach ($select_voters_group as $key => $value):
                            ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="box-contact">
                                    <h3 class="name margin-top-10"><?php echo $value['fromwhere']; ?></h3>
                                    <h4 class="job"><?php echo $value['total']; ?></h4>
                                </div>
                            </div>
                        <?php
                        endforeach;
                        ?>
                    </div>
                <?php
                endif;
                ?>
			</div>
		</div>
		<?php
		endif;
		?>
	</div>
	<!-- /.main-content -->
</div><!--/#wrapper -->

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="assets/script/html5shiv.min.js"></script>
		<script src="assets/script/respond.min.js"></script>
	<![endif]-->
	<!--
	================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script src="assets/scripts/jquery.min.js"></script>
	<script src="assets/scripts/modernizr.min.js"></script>
	<script src="assets/plugin/bootstrap/js/bootstrap.min.js"></script>
	<script src="assets/plugin/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
	<script src="assets/plugin/nprogress/nprogress.js"></script>
	<script src="assets/plugin/sweet-alert/sweetalert.min.js"></script>
	<script src="assets/plugin/waves/waves.min.js"></script>
	<!-- Full Screen Plugin -->
	<script src="assets/plugin/fullscreen/jquery.fullscreen-min.js"></script>

	<!-- Data Tables -->
	<script src="assets/plugin/datatables/media/js/jquery.dataTables.min.js"></script>
	<script src="assets/plugin/datatables/media/js/dataTables.bootstrap.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/responsive.bootstrap.min.js"></script>
	<script src="assets/scripts/datatables.demo.min.js"></script>

    <!-- Chartist -->
    <script src="assets/plugin/chart/chartist/chartist.min.js"></script>


	<script>
        var t = $('#main').DataTable({
            pageLength: 25,
            ordering: true,
            language: {
                search: "البحث ",
                sZeroRecords: "لايوجد نتائج",
                sInfoEmpty: "",
                sInfoFiltered: "",
                sInfo: "يتم عرض من _START_ الى _END_",
                sLengthMenu: " _MENU_ ",
                paginate: {
                    first: "الأول",
                    previous: "السابق",
                    next: "التالي",
                    last: "الأخير"
                },
            }

        });
        t.on('order.dt search.dt', function () {
            let i = 1;

            t.cells(null, 0, {search: 'applied', order: 'applied'}).every(function (cell) {
                this.data(i++);
            });
        }).draw();

        <? if ($isAllLocationsClosed): ?>
        // draw line chart with number of voters in that hour
        new Chartist.Line('#voters-volume-chart', {
            labels: ['6 AM', '7 AM', '8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM', '7 PM', '8 PM', '9 PM'],
            series: [
                [<?php
                    for ($i = 0; $i < 16; $i++) {
                        echo ($voters_done_volume_per_hour[$i + 6] ?? 0);
                        echo $i < 15 ? ',': '';
                    }
                    ?>]
            ]
        }, {
            fullWidth: true,
            height: '300px',
            chartPadding: {
                right: 40
            },
            axisY: {
                onlyInteger: true,
            },
            classNames: {
                start: 'ct-end',
                end: 'ct-start',
            }
        }, [
            ['screen and (min-width: 641px) and (max-width: 1024px)', {
                showPoint: false,
                axisX: {
                    labelInterpolationFnc: function (value, index) {
                        return index % 2 === 0 ? value : null;
                    }
                }
            }],
            ['screen and (max-width: 640px)', {
                showLine: false,
                axisX: {
                    labelInterpolationFnc: function (value, index) {
                        return index % 2 === 0 ? value : null;
                    }
                }
            }]
        ]);
        <? endif; ?>
    </script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
