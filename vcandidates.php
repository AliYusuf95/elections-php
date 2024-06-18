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
$current_time = date("h:i:s A");

$isAllLocationsClosed = mysqli_num_rows(mysqli_query($con,"SELECT id FROM locations WHERE open = true")) == 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAllLocationsClosed) {
    if (isset($_POST['action']) && $_POST['action'] === 'add-position' && !empty($_POST['name'])) {
        try {
            $con->begin_transaction();
            // get next order value
            $stmt = $con->prepare("SELECT IFNULL(MAX(`order`), 0) + 1 FROM positions");
            $stmt->execute();
            $stmt->bind_result($order);
            $stmt->fetch();
            $stmt->close();

            $stmt = $con->prepare("INSERT INTO positions (name, `order`, maxVotes, createdAt, updatedAt) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->bind_param('sii', $_POST['name'], $order, $_POST['maxVotes']);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                echo 'error';
                exit;
            }
            $stmt->close();
            $con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم إضافة المنصب (" . $_POST['name'] . ")','$logged_user','$current_time', NOW())");

            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            echo 'error';
            exit;
        }
        echo 'success';
        exit;
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete-position' && !empty($_POST['id'])) {
        try {
            $con->begin_transaction();
            // fetch position name
            $stmt = $con->prepare("SELECT name FROM positions WHERE id = ?");
            $stmt->bind_param('i', $_POST['id']);
            $stmt->execute();
            $stmt->bind_result($position_name);
            $stmt->fetch();
            $stmt->close();
            // get all candidates names and images paths in this position
            $stmt = $con->prepare("SELECT name, img FROM candidates WHERE positionId = ?");
            $stmt->bind_param('i', $_POST['id']);
            $stmt->execute();
            $stmt->bind_result($name, $img);
            $candidates = [];
            while ($stmt->fetch()) {
                $candidates[] = ['name' => $name, 'img' => $img];
            }
            $stmt->close();
            $stmt = $con->prepare("DELETE FROM positions WHERE id = ?");
            $stmt->bind_param('i', $_POST['id']);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                echo 'error';
                exit;
            }
            $stmt->close();
            $con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم حذف المنصب (" . $position_name . ")','$logged_user','$current_time', NOW())");
            if (!empty($candidates)) {
                $deleted_candidates = implode(', ', array_column($candidates, 'name'));
                $con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم حذف المرشحين (" . $deleted_candidates . ")','$logged_user','$current_time', NOW())");
            }
            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            echo 'error';
            exit;
        }
        // delete all images
        $img_paths = array_column($candidates, 'img');
        foreach ($img_paths as $img) {
            unlink($img);
        }
        echo 'success';
        exit;
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'add-candidate' && !empty($_POST['name']) && !empty($_POST['cpr']) && !empty($_FILES['img'])) {
        try {
            $con->begin_transaction();
            // check cpr is length 9
            if (strlen($_POST['cpr']) !== 9) {
                echo 'error';
                exit;
            }
            // check if position exists and get name
            $stmt = $con->prepare("SELECT name FROM positions WHERE id = ?");
            $stmt->bind_param('i', $_POST['positionId']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows() !== 1) {
                echo 'error';
                exit;
            }
            $stmt->bind_result($position_name);
            $stmt->fetch();
            $stmt->close();
            // store img in uploads/candidates folder with cpr name
            if (!file_exists('uploads/candidates')) {
                echo 'error missing uploads/candidates folder';
                exit;
            }
            // make sure the file is image
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
            if (!in_array($ext, $allowed)) {
                echo 'error image type';
                exit;
            }
            $img = 'uploads/candidates/' . $_POST['cpr'] . '.' . pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
            if (file_exists($img)) {
                echo 'error image duplicate';
                exit;
            }
            if (!move_uploaded_file($_FILES['img']['tmp_name'], $img)) {
                echo 'error move image';
                exit;
            }
            $positionId = intval($_POST['positionId']);
            $stmt = $con->prepare("INSERT INTO candidates (name, cpr, img, positionId, createdAt, updatedAt) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param('sssi', $_POST['name'], $_POST['cpr'], $img, $positionId);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                echo 'error insert';
                exit;
            }
            $stmt->close();
            $con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم إضافة المرشح (" . $_POST['name'] . ") في المنصب (" . $position_name . ")','$logged_user','$current_time', NOW())");

            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            echo 'error';
            exit;
        }
        echo 'success';
        exit;
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'reorder-positions' && !empty($_POST['positions'])) {
        try {
            $con->begin_transaction();
            $stmt = $con->prepare("UPDATE positions SET `order` = ? WHERE id = ?");
            $stmt->bind_param('ii', $order, $id);
            foreach ($_POST['positions'] as $position) {
                $order = $position['order'];
                $id = $position['id'];
                $stmt->execute();
            }
            $stmt->close();
            $con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم تغيير ترتيب المناصب','$logged_user','$current_time', NOW())" );
            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            echo 'error';
            exit;
        }
        echo 'success';
        exit;
    }
    // delete-candidate
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete-candidate' && !empty($_POST['id'])) {
        try {
            $con->begin_transaction();
            // get position name
            $stmt = $con->prepare("SELECT positions.name FROM candidates LEFT JOIN positions ON candidates.positionId = positions.id WHERE candidates.id = ?");
            $stmt->bind_param('i', $_POST['id']);
            $stmt->execute();
            $stmt->store_result();
            if($stmt->num_rows() !== 1) {
                echo 'error';
                exit;
            }
            $stmt->bind_result($position_name);
            $stmt->fetch();
            $stmt->close();

            // get candidate name and img
            $stmt = $con->prepare("SELECT name, img FROM candidates WHERE id = ?");
            $stmt->bind_param('i', $_POST['id']);
            $stmt->execute();
            $stmt->store_result();
            if($stmt->num_rows() !== 1) {
                echo 'error';
                exit;
            }
            $stmt->bind_result($name, $img);
            $stmt->fetch();
            $stmt->close();
            $stmt = $con->prepare("DELETE FROM candidates WHERE id = ?");
            $stmt->bind_param('i', $_POST['id']);
            $stmt->execute();
            if($stmt->affected_rows !== 1) {
                echo 'error';
                exit;
            }
            $stmt->close();
            $con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم حذف المرشح (".$name.") في المنصب (".$position_name.")','$logged_user','$current_time', NOW())" );
            // commit transaction
            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            echo 'error';
            exit;
        }
        // delete image
        unlink($img);
        echo 'success';
        exit;
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'change-max-votes' && !empty($_POST['id']) && !empty($_POST['maxVotes'])) {
        try {
            $con->begin_transaction();
            // check maxVotes is more than 0
            if (intval($_POST['maxVotes']) <= 0) {
                echo 'error';
                exit;
            }// fetch position name
            $stmt = $con->prepare("SELECT name FROM positions WHERE id = ?");
            $stmt->bind_param('i', $_POST['id']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows() !== 1) {
                echo 'error';
                exit;
            }
            $stmt->bind_result($position_name);
            $stmt->fetch();
            $stmt->close();

            $stmt = $con->prepare("UPDATE positions SET maxVotes = ? WHERE id = ?");
            $stmt->bind_param('ii', $_POST['maxVotes'], $_POST['id']);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                echo 'error';
                exit;
            }
            $stmt->close();
            $con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم تغيير عدد الأصوات المسموحة للمنصب (" . $position_name . ")','$logged_user','$current_time', NOW())");
            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            echo 'error';
            exit;
        }
        echo 'success';
        exit;
    }
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
	<link rel="stylesheet" href="assets/plugin/sweetalert2/sweetalert2.min.css">

	<!-- Percent Circle -->
	<link rel="stylesheet" href="assets/plugin/percircle/css/percircle.css">

	<!-- Chartist Chart -->
	<link rel="stylesheet" href="assets/plugin/chart/chartist/chartist.min.css">

	<!-- FullCalendar -->
	<link rel="stylesheet" href="assets/plugin/fullcalendar/fullcalendar.min.css">
	<link rel="stylesheet" href="assets/plugin/fullcalendar/fullcalendar.print.css" media='print'>

    <!-- Data Tables -->
    <link rel="stylesheet" href="assets/plugin/datatables/media/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="assets/plugin/datatables/extensions/RowReorder/css/rowReorder.bootstrap.min.css">

	<!-- RTL -->
	<link rel="stylesheet" href="assets/styles/style-rtl.min.css">

	<style>
	@media (max-width: 1024px) {
		.nav-container.container {
			width: unset;
		}
	}
    div.swal2-popup,
    div.swal2-container {
        font-size: 1.3rem;
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

				<div class="pull-left">
					<a class="logo" style="font-size: 15px;">مرحبا <?php echo $logged_user; ?></a>
				</div>
				<!-- /.pull-right -->

			</div>
			<!-- /.container -->
		</div>
		<!-- /.header-top -->
		<nav class="nav nav-horizontal">
			<button type="button" class="menu-close hidden-on-desktop js__close_menu"><i class="fa fa-times"></i><span>إغلاق</span></button>
			<div class="container nav-container">
				<ul class="menu">
					<li class="current">
						<a href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">المراكز</span></a>
					</li>
					<li>
						<a href="vusers"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">الأعضاء</span></a>
					</li>
					<li>
						<a class="text-primary" href="vcandidates"><i class="ico mdi mdi-account-multiple"></i><span style="font-weight: bold;">المرشحون</span></a>
					</li>
					<li>
						<a href="statistics"><i class="ico mdi mdi-chart-bar"></i><span style="font-weight: bold;">الإحصائيات</span></a>
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
        <div class="row small-spacing">
            <div class="col-xs-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h4 class="box-title">المناصب</h4>
                        <div class="box-tools">
                            <?php if($isAllLocationsClosed): ?>
                            <button type="button" class="btn btn-icon btn-icon-left btn-info btn-xs waves-effect waves-light" id="add-position"><i class="ico fa fa-plus" style="margin: 0;"></i>إضافة منصب</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <!-- /.box-title -->

                        <table class="table table-striped table-bordered display table-reorder" style="width:100%">
                            <thead>
                            <tr>
                                <th data-dt-order="icon-only">الترتيب</th>
                                <th>id</th>
                                <th>الإسم</th>
                                <th>عددالمرشحين</th>
                                <th>عدد الأصوات المسموحة</th>
                                <?php if($isAllLocationsClosed): ?><th>الأوامر</th><?php endif; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $stmt = $con->prepare("SELECT positions.id as positions_id, positions.name as positions_name, positions.maxVotes as maxVotes, count(candidates.id) candidates_count FROM positions LEFT JOIN candidates ON positions.id = candidates.positionId GROUP BY positions.id, positions.name ORDER BY positions.order ASC");
                            $stmt->execute();
                            $stmt->bind_result($id, $name, $maxVotes, $candidates_count);
                            $fetch_position = [];
                            while ($stmt->fetch()):
                                $fetch_position[] = ['id' => $id, 'name' => $name, 'maxVotes' => $maxVotes, 'candidates_count' => $candidates_count];
                                ?>
                                <tr>
                                    <td data-dt-order="icon-only"></td>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo $name; ?></td>
                                    <td><?php echo $candidates_count; ?></td>
                                    <td><?php echo $maxVotes; ?></td>
                                    <?php if ($isAllLocationsClosed): ?>
                                        <td>
                                        <button class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light delete-position"
                                                data-id="<?php echo $id; ?>"
                                                data-name="<?php echo $name; ?>"
                                        ><i class="ico fa fa-trash"></i>حذف المنصب
                                        </button>
                                        <button class="btn btn-icon btn-icon-left btn-info btn-xs waves-effect waves-light change-max-votes"
                                                data-id="<?php echo $id; ?>"
                                                data-name="<?php echo $name; ?>"
                                        ><i class="ico fa fa-edit"></i>تغيير عدد الأصوات
                                        </button>
                                        </td><?php endif; ?>
                                </tr>
                            <?php
                            endwhile;
                            $stmt->close();
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer"></div>
                </div>
            </div>
        </div>
        <?php
        // loop over $fetch_position
        foreach ($fetch_position as $position) {
        ?>
		<div class="row small-spacing">
			<div class="col-xs-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h4 class="box-title">
                            منصب [
                            <?php echo $position['name']; ?>
                            ]
                        </h4>
                        <div class="box-tools">
                            <?php if($isAllLocationsClosed): ?>
                            <button type="button"
                                    class="btn btn-icon btn-icon-left btn-info btn-xs waves-effect waves-light add-candidate"
                                    data-position-id="<?php echo $position['id']; ?>"
                                    data-position-name="<?php echo $position['name']; ?>"
                            ><i class="ico fa fa-plus" style="margin: 0;"></i>إضافة مرشح
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <!-- /.box-title -->

                        <table class="table table-striped table-bordered display" style="width:100%">
                            <thead>
                            <tr>
                                <!--<th>#</th>-->
                                <th>الإسم</th>
                                <th>الصورة</th>
                                <th>الرقم الشخصي</th>
                                <th>الأوامر</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $select_candidates = mysqli_query($con, "SELECT id, name, cpr, img FROM candidates WHERE positionId = " . $position['id']);

                            while ($fetch_candidates = mysqli_fetch_assoc($select_candidates)) {
                            ?>
                            <tr>
                                <td><?php echo $fetch_candidates['name']; ?></td>
                                <td>
                                    <img src="<?php echo $fetch_candidates['img']; ?>" alt="<?php echo $fetch_candidates['name']; ?>" width="50" height="80" />
                                </td>
                                <td><?php echo $fetch_candidates['cpr']; ?></td>
                                <td>
                                    <?php if($isAllLocationsClosed): ?>
                                    <button class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light delete-candidate"
                                            data-id="<?php echo $fetch_candidates['id']; ?>"
                                            data-name="<?php echo $fetch_candidates['name']; ?>"
                                            data-position-name="<?php echo $position['name']; ?>"
                                    ><i class="ico fa fa-trash"></i>حذف المرشح</button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                                <?php
                                }
                                ?>
                            </tbody>
                        </table>

                    </div>
                    <div class="box-footer"></div>
                </div>
				<!-- /.box-content -->
			</div>
		</div>
        <?php
        }
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
	<script src="assets/plugin/sweetalert2/sweetalert2.all.js"></script>
	<script src="assets/plugin/waves/waves.min.js"></script>
	<!-- Full Screen Plugin -->
	<script src="assets/plugin/fullscreen/jquery.fullscreen-min.js"></script>

	<!-- Data Tables -->
    <script src="assets/plugin/datatables/media/js/jquery.dataTables.js"></script>
    <script src="assets/plugin/datatables/media/js/dataTables.bootstrap.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/responsive.bootstrap.min.js"></script>
	<script src="assets/plugin/datatables/extensions/RowReorder/js/dataTables.rowReorder.js"></script>

    <script>

        function postData(data, options) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: 'vcandidates.php',
                    type: 'POST',
                    data: data,
                    ...(options || {}),
                    success: function (data) {
                        if (data === 'success') {
                            resolve();
                        } else {
                            reject(data);
                        }
                    },
                    error: function (error) {
                        reject(error);
                    }
                });
            });
        }

        const addSwal = Swal.mixin({
            confirmButtonText: "إضافة",
            cancelButtonText: "إلغاء",
            showLoaderOnConfirm: true,
            showCancelButton: true,
            confirmButtonColor: "#34a9df",
        });

        const successSwal = Swal.mixin({
            icon: "success",
            showCancelButton: false,
            showConfirmButton: false,
            allowEscapeKey: false,
            allowOutsideClick: false,
            didOpen: () => {
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        });

        $('#add-position').on('click', function (e) {
            addSwal.fire({
                title: "إضافة منصب",
                text: "الرجاء إدخال إسم المنصب",
                input: "text",
                inputPlaceholder: "إسم المنصب",
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                addSwal.fire({
                    title: "عدد الأصوات",
                    text: "الرجاء إدخال عدد الأصوات المسموح بها لهذا المنصب",
                    input: "number",
                    inputPlaceholder: "عدد الأصوات",
                    allowOutsideClick: () => !Swal.isLoading(),
                    preConfirm: async (inputValue) => {
                        try {
                            await postData({
                                action: 'add-position',
                                name: result.value,
                                maxVotes: inputValue,
                            });
                        } catch (error) {
                            Swal.showValidationMessage('حدث خطأ أثناء إضافة المنصب, يرجى المحاولة مرة أخرى');
                            console.error(error);
                        }
                    },
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }
                    successSwal.fire("تمت العملية بنجاح", "تم إضافة المنصب بنجاح");
                });
            });
        });

        $('.delete-position').on('click', function (e) {
            let $this = $(this);
            let positionName = $this.data('name');
            Swal.fire({
                title: "هل أنت متأكد؟",
                text: `هل أنت متأكد من حذف المنصب [ ${positionName} ] ؟ سيتم حذف جميع البيانات المرتبطة بهذا المنصب`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "نعم، حذف المنصب",
                cancelButtonText: "إلغاء",
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        await postData({
                            action: 'delete-position',
                            id: $this.data('id')
                        });
                    } catch (error) {
                        Swal.showValidationMessage('حدث خطأ أثناء حذف المنصب, يرجى المحاولة مرة أخرى');
                        console.error(error);
                    }
                },
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                successSwal.fire("تمت العملية بنجاح", "تم حذف المنصب بنجاح");
            });
        });

        $('.change-max-votes').on('click', function (e) {
            let $this = $(this);
            let positionName = $this.data('name');
            addSwal.fire({
                title: "تغيير عدد الأصوات",
                text: `الرجاء إدخال عدد الأصوات المسموح بها لهذا المنصب [ ${positionName} ]`,
                input: "number",
                inputPlaceholder: "عدد الأصوات",
                confirmButtonText: "تغيير",
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: async (inputValue) => {
                    try {
                        await postData({
                            action: 'change-max-votes',
                            id: $this.data('id'),
                            maxVotes: inputValue,
                        });
                    } catch (error) {
                        Swal.showValidationMessage('حدث خطأ أثناء تغيير عدد الأصوات المسموحة, يرجى المحاولة مرة أخرى');
                        console.error(error);
                    }
                },
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                successSwal.fire("تمت العملية بنجاح", "تم تغيير عدد الأصوات المسموحة بنجاح");
            });
        });

        $('.add-candidate').on('click', function (e) {
            let $this = $(this);
            let positionName = $this.data('position-name');
            // use one form for both name and cpr
            addSwal.fire({
                title: "إضافة مرشح في منصب [ " + positionName + " ]",
                html: `
                    <form autocomplete="off">
                    <div style="display: block; margin-top: 1em;">
                    الرجاء إدخال إسم المرشح
                    </div>
                    <input type="text" id="name" name="name" class="swal2-input" data-1p-ignore style="width: 100%; margin: 0;" autocomplete="false" placeholder="إسم المرشح">
                    <div style="display: block; margin-top: 1em;">
                    الرجاء إدخال الرقم الشخصي للمرشح
                    </div>
                    <input type="number" id="cpr" name="cpr" class="swal2-input" maxlength="9" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" style="width: 100%; margin: 0;" autocomplete="false" placeholder="الرقم الشخصي">
                    <div style="display: block; margin-top: 1em;">
                    الرجاء إدخال صورة المرشح
                    </div>
                    <input type="file" id="img" name="img" class="swal2-input" style="width: 100%; margin: 0;" accept="image/*">
                    </form>
                `,
                preConfirm: async () => {
                    let name = $('input[name="name"]').val();
                    let cpr = $('input[name="cpr"]').val();
                    let img = $('input[name="img"]').prop('files')[0];

                    if (!name || !cpr || !img) {
                        Swal.showValidationMessage('الرجاء ملء جميع الحقول');
                        return;
                    }
                    if (cpr.length !== 9) {
                        Swal.showValidationMessage('الرجاء إدخال رقم شخصي صحيح');
                        return;
                    }
                    let formData = new FormData();
                    formData.append('action', 'add-candidate');
                    formData.append('name', name);
                    formData.append('cpr', cpr);
                    formData.append('positionId', $this.data('position-id'));
                    formData.append('img', img);

                    try {
                        await postData(formData, {
                            contentType: false,
                            processData: false,
                        });
                    } catch (error) {
                        Swal.showValidationMessage('حدث خطأ أثناء إضافة المرشح, يرجى المحاولة مرة أخرى');
                        console.error(error);
                    }
                },
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                successSwal.fire("تمت العملية بنجاح", "تم إضافة المرشح بنجاح");
            });
        });

        $('.delete-candidate').on('click', function (e) {
            let $this = $(this);
            let candidateName = $this.data('name');
            Swal.fire({
                title: "هل أنت متأكد؟",
                text: "هل أنت متأكد من حذف المرشح [ " + candidateName + " ] ؟",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "نعم، حذف المرشح",
                cancelButtonText: "إلغاء",
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        await postData({
                            action: 'delete-candidate',
                            id: $this.data('id')
                        });
                    } catch (error) {
                        Swal.showValidationMessage('حدث خطأ أثناء حذف المرشح, يرجى المحاولة مرة أخرى');
                        console.error(error);
                    }
                },
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                successSwal.fire("تمت العملية بنجاح", "تم حذف المرشح بنجاح");
            });
        });

        const tableLang = {
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
        };

        $('table.table').each(function () {
            const $this = $(this);
            const isReorder = $this.hasClass('table-reorder');
            const canReorder = <?php echo $isAllLocationsClosed ? 'true' : 'false'; ?>;
            const table = $this.DataTable({
                rowReorder: isReorder && canReorder,
                language: tableLang,
                columnDefs: isReorder ? [
                    { orderable: false, className: canReorder ? 'reorder' : '', targets: 0, searchable: false },
                    { targets: 1, visible: false },
                    { orderable: false, targets: '_all' }
                ] : []
            });
            if (isReorder) {
                table.on('order.dt search.dt', function () {
                    let i = 1;
                    table.cells(null, 0, {search: 'applied', order: 'applied'}).every(function (cell) {
                        this.data(i++);
                    });
                }).draw();
                table.on('row-reorder', function (e, diff, edit) {
                    // send new order to server
                    let positions = diff.map((item) => {
                        console.log();
                        return {
                            id: table.row(item.node).data()[1],
                            order: item.newData
                        };
                    });
                    if (positions.length === 0) {
                        return;
                    }
                    postData({
                        action: 'reorder-positions',
                        positions
                    }).then(() => {
                        Swal.fire({
                            title: "تمت العملية بنجاح",
                            text: "تم تغيير ترتيب المناصب بنجاح",
                            icon: "success",
                            showCancelButton: true,
                            showConfirmButton: false,
                            cancelButtonText: "إلغاء",
                            timer: 5000
                        });
                    }).catch((error) => {
                        Swal.fire("حدث خطأ", "حدث خطأ أثناء تغيير ترتيب المناصب, يرجى المحاولة مرة أخرى", "error");
                        console.error(error);
                    });
                });
            }
        });
	</script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
