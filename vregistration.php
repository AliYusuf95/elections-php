<?php
global $con;
include 'config.php';

// Initialize the session
session_start();

$location_id = 0;
$location_name = '';
$location_error = null;
$check_error = null;
$check_user = null;
$isAdmin = false;

$voters_table = 'voters';
$voting_results_table = 'voting_results';
$voters_data_table = 'voters_data';
$voter_form_fields = VOTER_FORM_FIELDS;
$voter_required_fields = VOTER_REQUIRED_FIELDS;
$users_table = 'users';
$accept_only_from_voters_table = ACCEPT_NEW_VOTERS;
$max_voting_age = 16;

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["id"])){
    header("location: vlogin.php");
    exit;
} else if(isset($_SESSION["user"]) && $_SESSION["user"] === true) {
    $select_user_Location = mysqli_query($con, "SELECT locationId FROM $users_table WHERE id = " . $_SESSION["id"]);
    $location_id = mysqli_fetch_row($select_user_Location)[0];
} else if (isset($_SESSION["admin"]) && $_SESSION["admin"] === true && isset($_GET["l"])) {
    $isAdmin = true;
    $location_id = $_GET["l"];
} else {
    header("location: vadmin.php");
    exit;
}

$user_id = $_SESSION["id"];

function getLocationInfo() {
    global $con, $location_id, $location_name, $location_error;
    
    $sql = "SELECT name, open FROM locations WHERE id = ?";

    if (!($stmt = mysqli_prepare($con, $sql))) {
        $location_error = "Oops! Something went wrong. Please try again later.";
        return;
    }
    
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $location_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        $location_error = "لم يتم إيجاد المركز، يرجى التواصل مع إدارة اللجنة";
        return;
    }
    // Store result
    mysqli_stmt_store_result($stmt);
    // Check if username exists, if yes then verify password
    if (mysqli_stmt_num_rows($stmt) < 1) {
        $location_error = "لم يتم إيجاد المركز، يرجى التواصل مع إدارة اللجنة";
        return;
    }
	
	$location_open = null;
    mysqli_stmt_bind_result($stmt, $location_name, $location_open);

    mysqli_stmt_fetch($stmt);
	mysqli_stmt_close($stmt);

	if(isset($location_open) && !$location_open) {
		$location_error = "تم إغلاق المركز، في حال وجود خطأ يرجى التواصل مع إدارة اللجنة";
        return;
	}
}

function getLocationScreens() {
    global $con, $location_id, $location_error;

	$result = array();

    if (!($stmt = mysqli_prepare($con, "SELECT id, name FROM screens WHERE locationId = ? AND connected = true AND voterId IS null"))) {
        return $result;
    }
    
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $location_id);
    if (!mysqli_stmt_execute($stmt)) {
        return $result;
    }
    // Store result
    mysqli_stmt_bind_result($stmt, $id, $name);
	while ($row = mysqli_stmt_fetch($stmt)) {
		$result[] = array("id" => $id, "name" => $name);
	}
	mysqli_stmt_close($stmt);

	return $result;
}

getLocationInfo();



$logged_user = $_SESSION['username'];
$current_time = date("h:i:s A");


?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

	<title>التسجيل | نظام التصويت الإلكتروني</title>

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
	@media (max-width: 1024px) {
		.nav-container.container {
			width: unset;
		}
	}
	</style>
  <script>
  function checklength(i) {
    'use strict';
    if (i < 10) {
        i = "0" + i;
    }
    return i;
}

var minutes, seconds, count, counter, timer;
count = 301; //seconds
// counter = setInterval(timer, 1000);

function timer() {
    'use strict';
    count = count - 1;
    minutes = checklength(Math.floor(count / 60));
    seconds = checklength(count - minutes * 60);
    if (count < 0) {
        clearInterval(counter);
        return;
    }
    document.getElementById("timer").innerHTML = 'سيتم تحديث الصفحة تلقائيا خلال ' + minutes + ':' + seconds + ' ';
    if (count === 0) {
        location.reload();
    }
}
  </script>
</head>

<body>
<div id="wrapper">
	<header class="fixed-header">
		<div class="header-top">
			<div class="container">
				<div class="pull-right">
					<button type="button" class="btn btn-box-tool hidden-on-desktop js__menu_button"><i class="fa fa-bars padding-10"></i></button>
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
		<!-- <div class="topnav" id="myTopnav">
			<a href="#home" class="active">Home</a>
			<a href="#news">News</a>
			<a href="#contact">Contact</a>
			<a href="#about">About</a>
			<a href="javascript:void(0);" class="icon" onclick="myFunction()"><i class="fa fa-bars"></i></a>
		</div> -->
		<nav class="nav nav-horizontal">
			<button type="button" class="menu-close hidden-on-desktop js__close_menu"><i class="fa fa-times"></i><span>إغلاق</span></button>
			<div class="container nav-container"> 
				<ul class="menu">
					<?php 
						if($isAdmin == true):
						?>
							<li class="current">
								<a class="text-primary" href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">الرئيسية</span></a>
							</li>
						<?php
						else:
						?>
							<li class="current">
								<a class="text-primary" href="vregistration"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">التسجيل</span></a>
							</li>
							<li>
								<a href="vscreens"><i class="ico mdi mdi-monitor-multiple"></i><span style="font-weight: bold;">صفحات الإقتراع</span></a>
							</li>
							<li>
								<a href="voters"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">بيانات الناخبين</span></a>
							</li>
							
						<?php
						endif;
						?>
						<li>
							<a href="logout"><i class="ico mdi mdi-logout"></i><span style="font-weight: bold;">تسجيل خروج</span></a>
						</li>
					</ul>
				<!-- /.menu -->
			<!-- /.container -->
			</div>
		</nav>
		<!-- /.nav-horizontal -->
	</header>
	<!-- /.fixed-header -->

	<div class="main-content container">
		<h1><?=$location_name?></h1>
		</br>
		<?php
		if (isset($_GET['approve_voter'])){

			$voter_id = $_GET['approve_voter'];
			$approve_voter_query = mysqli_query($con,"UPDATE voters SET status ='1', username = '$logged_user' WHERE id = '$voter_id'");


			$select_voter = mysqli_query($con, "SELECT * FROM voters WHERE id = '$voter_id'");
			if($fetch_voter = mysqli_fetch_assoc($select_voter)){
				$unique_key =  $fetch_voter['unique_key'];
				$mobile = $fetch_voter['mobile'];
			}

			if ($approve_voter_query) {
				echo '
				<br />
				<div class="alert alert-success">
				<strong>تم قبول الطلب بنجاح، سيتم فتح الواتس اب الآن لإرسال رابط التصويت</strong>
				<script>window.open("	https://wa.me/973'.$mobile.'?text=السلام عليكم،%0a%0aالرجاء استخدام الرابط التالي للتصويت:%0a https://elections.memamali.com/vote?unique_key='.$unique_key.'","_blank")</script>
				</div>
        <meta http-equiv="refresh" content="5; URL=vadmin" />
				';
        mysqli_query($con, "INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم قبول الناخب رقم $voter_id','$logged_user','$current_time', NOW())" );
      } else {
				echo '
				<br />
				<div class="alert alert-danger">
				<strong>Faild!</strong> there is an error!
				</div>
				';
			}
		}

		if (isset($_GET['reject_voter'])){

			$voter_id = $_GET['reject_voter'];
			$reject_note = $_GET['reject_note'];
			$reject_voter_query = mysqli_query($con,"UPDATE voters SET status ='2', notes = '$reject_note', username = '$logged_user' WHERE id = '$voter_id'");

      $select_voter_r = mysqli_query($con, "SELECT * FROM voters WHERE id = '$voter_id'");
			if($fetch_voter_r = mysqli_fetch_assoc($select_voter_r)){
				$mobile = $fetch_voter_r['mobile'];
			}

			if ($reject_voter_query) {
				echo '
				<br />
				<div class="alert alert-success">
				<strong>تم رفض الطلب بنجاح</strong>
        <script>window.open("	https://wa.me/973'.$mobile.'?text=السلام عليكم،%0a%0aتم رفض طلبك في إنتخابات هيئة الموكب الحسيني لقرية بوري.%0a%0aسبب الرفض: '.$reject_note.'","_blank")</script>
				</div>
        <meta http-equiv="refresh" content="5; URL=vadmin" />
				';
        mysqli_query($con, "INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم رفض الناخب رقم $voter_id والسبب هو: $reject_note','$logged_user','$current_time', NOW())" );
			} else {
				echo '
				<br />
				<div class="alert alert-danger">
				<strong>Faild!</strong> there is an error!
				</div>
				';
			}
		}
		?>
		
		<?php
		
		if(isset($location_error)) {
		    echo '
				<br />
				<div class="alert alert-danger">
				<strong>خطأ</strong> '.$location_error.' !
				</div>
				';
		} else {
		?>
		<div class="container">

			<center>
			<img src="logo.png" alt="logo" width="185" style="filter: drop-shadow(0px 0px 2px #aaa);"/>
			<h3>نظام التصويت الإلكتروني</h3>
			<h4>تسجيل الناخبين</h4>

			<hr />

			<?php 
			if(isset($_POST['check_voter']) && isset($_POST['cpr'])) {

				try {
                    if (!$accept_only_from_voters_table) {
                        preg_match('/(\d{2})(\d{2})\d{5}/', $_POST['cpr'], $cpr_match);

                        if (count($cpr_match) != 3) {
                            throw new Exception('الرقم الشخصي غير صحيح');
                        }
                        // get last 2 digits of the current year
                        $current_year = intval(date('Y'));
                        $current_month = intval(date('n'));
                        $max_voting_year = $current_year - $max_voting_age;

                        $year = intval($cpr_match[1]);
                        $month = intval($cpr_match[2]);

                        if ($month < 0 || $month > 12) {
                            throw new Exception('الرقم الشخصي غير صحيح');
                        }

                        $year += $year <= 22 ? 2000 : 1900;
                        if ($year > $max_voting_year || ($year == $max_voting_year && $month > $current_month)) {
                            throw new Exception("غير مسموح بالتصويت، العمر اصغر من " . $max_voting_age . " عام");
                        }
                    }

                    $cpr = trim($_POST['cpr']);
					$check_error = null;
					$stmt = mysqli_prepare($con, "SELECT id FROM $voters_data_table WHERE TRIM(cpr) = ? AND status > 0");
					mysqli_stmt_bind_param($stmt, "s", $cpr);
					if(!mysqli_stmt_execute($stmt)) {
						$check_error = 'حدث خطأ، يرجى المحاولة مجددا';
					} else {
						mysqli_stmt_store_result($stmt);
						if( mysqli_stmt_num_rows($stmt) > 0) {
							$check_error = 'الناخب صاحب الرقم '. $cpr .' صوت مسبقاً';
						}
						mysqli_stmt_close($stmt);
                        $stmt = $con->prepare("SELECT ".implode(',', $voter_form_fields)." FROM $voters_table WHERE TRIM(cpr) = ?");
                        $stmt->bind_param('s', $cpr);
                        $stmt->execute();
                        $res = $stmt->get_result();
						if (!$res || !($check_user = $res->fetch_assoc())) {
                            if ($accept_only_from_voters_table) {
                                $check_error = 'الرقم الشخصي غير مسجل';
                            } else {
                                $check_user = array_fill_keys($voter_form_fields, '');
                            }
						} else {
							$res->close();
						}
                        $stmt->close();
					}
				} catch (Exception $e) {
					$check_error = $e->getMessage();
				}

				if (isset($check_error)) {
					echo '<div class="alert alert-danger">'.$check_error.'</div>';
				} else {
					echo '<div class="alert alert-info">بإمكان الناخب التصويت</div>';
				}
			}

            if (isset($_POST['add_voter']) && count(array_filter($voter_required_fields, function($field) { return empty($_POST[$field]); })) > 0) {
                echo '<div class="alert alert-danger">الرجاء تعبئة جميع الحقول</div>';
            } else if(isset($_POST['add_voter'])) {
				$con->begin_transaction();
				$add_error = null;
				$screen_name = '';
				try {
                    $cpr = trim($_POST['cpr']);
                    $screen_id = trim($_POST['screen']);

                    if (!$accept_only_from_voters_table) {
                        // validate cpr
                        preg_match('/(\d{2})(\d{2})\d{5}/', $cpr, $cpr_match);
                        if (count($cpr_match) != 3) {
                            throw new Exception('الرقم الشخصي غير صحيح');
                        }
                    }

                    $stmt = $con->prepare("SELECT id FROM $voters_table WHERE TRIM(cpr) = ?");
                    $stmt->bind_param('s', $cpr);
                    $stmt->execute();
                    $stmt->bind_result($voter_id);
                    $stmt->fetch();
                    $stmt->close();

                    if ($accept_only_from_voters_table && !$voter_id) {
                        throw new Exception('الرقم الشخصي غير مسجل');
                    } else if (!$voter_id) {
                        $stmt = $con->prepare("INSERT INTO $voters_table (cpr, name, mobile, fromwhere, createdAt, updatedAt) VALUES (?, NOW(), NOW())");
                        $stmt->bind_param('ssss', $cpr, $_POST['name'], $_POST['mobile'], $_POST['fromwhere']);
                        $stmt->execute();
                        if ($con->affected_rows < 1) {
                            throw new Exception('حدث خطأ في حفظ البيانات، يرجى المحاولة مجدداً');
                        }
                        $stmt->close();
                        $voter_id = $con->insert_id;
                    }

                    // get all fields from voters_data table
                    $stmt = $con->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? and COLUMN_NAME not in ('id', 'createdAt', 'updatedAt')");
                    $stmt->bind_param('s', $voters_data_table);
                    $stmt->execute();
                    $stmt->bind_result($column_name);
                    $voter_table_fields = [];
                    while ($stmt->fetch()) {
                        $voter_table_fields[] = $column_name;
                    }
                    // insert data defined in $voter_table_fields
                    $stmt = $con->prepare("INSERT INTO $voters_data_table (".implode(',', $voter_table_fields).", createdAt, updatedAt) VALUES(".implode(',', array_fill(0, count($voter_table_fields), '?')).", NOW(), NOW())");

                    $types = str_repeat('s', count($voter_table_fields));
                    // use data from post
                    $values = array_map(function($field) use ($location_id, $user_id, $voter_id) {
                        if ($field == 'locationId') {
                            return $location_id;
                        }
                        if ($field == 'userId') {
                            return $user_id;
                        }
                        if ($field == 'voterId') {
                            return $voter_id;
                        }
                        if ($field == 'status') {
                            return 2;
                        }
                        return isset($_POST[$field]) ? trim($_POST[$field]) : null;
                    }, $voter_table_fields);
					$stmt->bind_param($types, ...$values);
					$stmt->execute();

					if ($con->affected_rows < 1) {
						throw new Exception('تم تسجيل الناخب مسبقاً، في حال عدم التأكد يرجى التواصل مع إدارة اللجنة');
					}
					$stmt->close();

					// select screen name
					$stmt = $con->prepare("SELECT name from screens WHERE id = ?");
					$stmt->bind_param('i', $screen_id);
					$stmt->execute();
					$stmt->bind_result($screen_name);
					$stmt->fetch();
					$stmt->close();

					$screen_name = empty($screen_name) ? $screen_id : $screen_name;

					// update screen record
					$stmt = $con->prepare("UPDATE screens SET voterId = $voter_id WHERE id = ? AND voterId IS null");
					$stmt->bind_param('i', $screen_id);
					$stmt->execute();

					if ($con->affected_rows != 1) {
						throw new Exception('حدث خطأ في حفظ البيانات، يرجى المحاولة مجدداً');
					}
					$stmt->close();

					$con->query("INSERT INTO system_log (title, username, created_at, createdAt) VALUES ('تم تسجيل الناخب رقم ($voter_id) صاحب الرقم الشخصي ($cpr) في ($location_name)','$logged_user','$current_time', NOW())" );

					$con->commit();
					$jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
						[
							'PHPSESSID' => session_id()
						],
                        INTERNAL_WS_COOKIE_DOMAIN
					);					
					$client = new \GuzzleHttp\Client();
					try {
						$client->request('POST', INTERNAL_WS_URL."/location/$location_id/show-vote/".$screen_id, ['cookies'  => $jar]);
					} catch(\GuzzleHttp\Exception\RequestException $e) {
						if( $e->hasResponse() && $e->getResponse()->getStatusCode() == '400'){
							throw new Exception('لم يتم تحديث صفحة الإقتراع تلقائيا، يرجى تحديثها يدوياً');
						}
					} catch (Exception $e) {
						throw new Exception($e->getMessage());
					}
				} catch (mysqli_sql_exception $e) {
					$con->rollback();
                    if ($e->getCode() == 1062) {
                        $add_error = 'تم تسجيل الناخب مسبقاً، في حال عدم التأكد يرجى التواصل مع إدارة اللجنة';
                    } else {
                        $add_error = 'حدث خطأ في حفظ البيانات، يرجى المحاولة مجدداً';
                    }
				} catch (Exception $e) {
					$add_error = $e->getMessage();
				}

				if (isset($add_error)) {
					echo '<div class="alert alert-danger">'.$add_error.'</div>';
				} else {
					echo '<div class="alert alert-success">تم فتح صفحة الترشح ('.$screen_name.')</div>';
				}
				
				// $stmt = mysqli_prepare($con, "INSERT INTO $voters_table (cpr, name, mobile, fromwhere, status, locationId, userId) VALUES(?, ?, ?, ?, 1, ?, ?) 
				// ON DUPLICATE KEY UPDATE name = ?, mobile = ?, fromwhere = ?, status = ?, locationId = ?, userId = ?;  ");

				// $name = isset($_POST['name']) ? $_POST['name'] : '';
				// $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : '';

				// mysqli_stmt_bind_param($stmt, "isssiiisssiii", $_POST['cpr'], $name, $mobile, $_POST['fromwhere'], 1, $location_id, $user_id,
				// $name, $mobile, $_POST['fromwhere'], 1, $location_id, $user_id);

				// if (mysqli_stmt_execute($stmt)) {
				// 	mysqli_query($con, "UPDATE voters SET status ='1', username = '$logged_user' WHERE id = '$voter_id'" );
				// 	mysqli_query($con, "INSERT INTO system_log (title, username, created_at) VALUES ('تم قبول الناخب رقم $voter_id','$logged_user','$current_time')" );
				// 	return;
				// }

			}
			?>
			<div class="row">
				<div class="form-box">

					<form action="vregistration" method="POST">

						<?php
						if(isset($_POST['check_voter']) && !isset($check_error)):
							$screens = getLocationScreens();
						?>

						<div id="no-screen-error">
							<?php
							if (count($screens) == 0) {
								echo '<div class="alert alert-danger">لا توجد صفحة إقتراع شاغرة .. يرجى تحديث الصفحة عند وجود شاغر</div>';
							}
							?>
						</div>

						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
								<input type="text" name="cpr" value="<?php echo $_POST['cpr'].'' ?>" class="form-control" placeholder="الرقم الشخصي" readonly required>
							</div>
						</div>

						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
								<input type="text" name="name" value="<?php echo $check_user['name'] ?>" class="form-control" placeholder="الاسم الثلاثي">
							</div>
						</div>

						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><span class="glyphicon glyphicon-phone"></span></div>
								<input name="mobile" value="<?php echo ($check_user['mobile'] == 0 ? '' : $check_user['mobile']) ?>" class="form-control" placeholder="رقم الهاتف/الواتس اب" title="يجب ان يكون رقم الموبايل 8 ارقام">
							</div>
						</div>

                        <?php
                        if (in_array('fromwhere', $voter_required_fields)):
                        ?>
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><span class="glyphicon glyphicon-map-marker"></span></div>
								<select class="form-control" name="fromwhere" required>
								<option value="">اختر المأتم</option>
								<?php
								$places = array(
									"مأتم الامام الباقر عليه السلام",
									"مأتم الامام علي عليه السلام",
									"مأتم الامام الصادق عليه السلام",
									"مأتم الامام الرضا عليه السلام",
									"مأتم الطويلة",
									//"قاطني قرية بوري"
								);
								foreach ($places as $place) {
									echo '<option value="'.$place.'" '.($place == $check_user['fromwhere'] ? 'selected' : '').'>'.$place.'</option>';
								}
								?>
							</select>
							</div>
                        <?php endif; ?>
						</div>

						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><span class="fa fa-desktop"></span></div>
								<select class="form-control" id="screens" name="screen" required>
								<option value="">اختر شاشة التصويت</option>
								<?php
								foreach ($screens as $screen) {
									echo '<option value="'.$screen['id'].'">'.(empty($screen['name']) ? $screen['id'] : $screen['name']).'</option>';
								}
								?>
							</select>
							</div>
						</div>



						<button type="submit" name="add_voter" class="btn btn-icon btn-icon-left btn-success btn-sm waves-effect waves-light"><i class="ico fa fa-check"></i>تسجيل</button>
						<?php
						else:
						?>
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
								<input name="cpr" class="form-control" placeholder="الرقم الشخصي" pattern=".{9,9}" maxlength="9" title="يجب ان يكون الرقم الشخصي 9 ارقام" required>
							</div>
						</div>

						<button type="submit" name="check_voter" class="btn btn-icon btn-icon-left btn-info btn-sm waves-effect waves-light"><i class="ico fa fa-search"></i>تحقق</button>
						<?php
						endif;
						?>
					</form>
				</div>
			</div>
		</div>
			<span id="timer"></span>
				<!-- /.box-content -->
			</div>
		</div>
		
		<?php
		} // end location error else
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
    <script src="<?php echo WS_URL ?>/socket.io/socket.io.js"></script>
	<script>
        var socket = io("<?php echo WS_URL ?>/users", {
			withCredentials: true,
			auth: {
				locationId: <?php echo $location_id ?>
			}
		});
        socket.on("screens-list", function(data) {
			if(data.screens && Array.isArray(data.screens)) {
				console.log(data.screens);
				var html = data.screens.filter(function(s) {
					return s.available && s.connected;
				}).map(function(s) {
					return '<option value="' + s.id + '">' + (!!s.name ? s.name : s.id) + '</option>';
				});
				console.log(html);
				if(html.length > 0) {
					$('#no-screen-error').html('');
				} else {
					$('#no-screen-error').html('<div class="alert alert-danger">لا توجد صفحة إقتراع شاغرة .. يرجى تحديث الصفحة عند وجود شاغر</div>');
				}
				$('#screens').html('<option value="">اختر شاشة التصويت</option>' + html);
			}
        });
	</script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
