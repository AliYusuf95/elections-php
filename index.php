<?php
include 'config.php';

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

	<!-- RTL -->
	<link rel="stylesheet" href="assets/styles/style-rtl.min.css">

	<style>
	.custom-file-input::-webkit-file-upload-button {
  visibility: hidden;
}
.custom-file-input::before {
  content: 'اختر الصورة';
  display: inline-block;
  background: #eee;
  border: 1px solid #999;
  border-radius: 3px;
  padding: 5px 8px;
  outline: none;
  white-space: nowrap;
  -webkit-user-select: none;
  cursor: pointer;
  text-shadow: 1px 1px #fff;
  font-weight: 700;
  font-size: 10pt;
}
.custom-file-input:hover::before {
  border-color: black;
}
.custom-file-input:active::before {
  background: -webkit-linear-gradient(top, #e3e3e3, #f9f9f9);
}
	</style>
</head>

<body>
<header class="fixed-header">
	<div class="header-top" style="height: 20px;">
		<div class="container">
			<div class="pull-right">
				<a class="logo"></a>
			</div>
			<!-- /.pull-right -->

		</div>
		<!-- /.container -->
	</div>
</header>
<!-- /.fixed-header -->



<div id="wrapper">
	<div class="main-content container">

		<div class="container">

			<center style="margin-top: -20px;">
			<img src="logo.png" alt="logo" width="220"/>
			<h3>نظام التصويت الإلكتروني</h3>
			<h4>تسجيل الناخبين</h4>
			
			<hr />
			
            <!--<div class="alert alert-danger" role="alert">
				انتهت المدة الزمنية للتصويت
				<br />
				شكراً لكم
				<br />
            </div>-->
            
            <?php
            //die();
            ?>

			<?php
			if (isset($_POST['new_voter'])){
						/*
            ini_set('display_errors', 1);
	          ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
						*/

			      $errors= array();
			      $file_name = $_FILES['cpr_image']['name'];
			      $file_size =$_FILES['cpr_image']['size'];
			      $file_tmp =$_FILES['cpr_image']['tmp_name'];
			      $file_type=$_FILES['cpr_image']['type'];
			      //$file_ext=strtolower(end(explode('.',$_FILES['cpr_image']['name'])));

			      $temp1 = explode('.',$_FILES['cpr_image']['name']);
                  $file_ext = strtolower(end($temp1));
			      $extensions= array("jpeg","jpg","png");

			      if(in_array($file_ext,$extensions)=== false){
			         $errors[]="extension not allowed, please choose a JPEG or PNG file.";
			      }

			      if($file_size > 6291456){
			         $errors[]='File size must be excately 6 MB';
			      }


				$voter_name = $_POST['name'];
				$voter_cpr = $_POST['cpr'];
				$voter_cpr_image = $_POST['cpr'].".".$file_ext;
				$voter_mobile = $_POST['mobile'];
				$voter_fromwhere = $_POST['fromwhere'];
				$voter_status = '0';
				$date_time = date("h:i:s A");
				$unique_key = substr(md5(mt_rand()), 0, 7);


				$select_voters = mysqli_query($con,"SELECT * FROM voters WHERE cpr = '$voter_cpr'");

	      if(mysqli_num_rows($select_voters) == 0 && empty($errors)==true && move_uploaded_file($file_tmp,"uploads/".$_POST['cpr'].".".$file_ext)){
	        $new_voter = mysqli_query($con, "INSERT INTO voters (name, cpr, cpr_image, mobile, fromwhere, status, date_time, unique_key) VALUES ('$voter_name','$voter_cpr','$voter_cpr_image','$voter_mobile','$voter_fromwhere','$voter_status','$date_time','$unique_key')" );


					/*
					if (!$con -> query($new_voter)) {
				  echo("Error description: " . $con -> error);
					}
					*/


					echo'
	        <center><div class="alert alert-success" role="alert">تم إستلام طلبك، سيتم مراسلتك عبر الواتس اب قريباً</div></center>
	        ';
	      }else{
	        echo'
					<center><div class="alert alert-danger" role="alert">يوجد لديك طلب سابق، يرجى الإتصال بنا على 38800825 إذا كنت تحتاج اي مساعدة</div></center>
	        ';
	      }



			}

			?>
			</center>

	<div class="row">
		<div class="form-box">

    	    <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
                        <input type="text" name="name" class="form-control" placeholder="ادخل الاسم الثلاثي" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-phone"></span></div>
												<input name="mobile" class="form-control" placeholder="ادخل رقم الواتس اب" pattern=".{8,8}" required title="يجب ان يكون رقم الموبايل 8 ارقام">
                    </div>
                </div>

								<div class="form-group">
								    <div class="input-group">
								        <div class="input-group-addon"><span class="glyphicon glyphicon-map-marker"></span></div>
								        <select class="form-control" name="fromwhere" required>
								        <option value="">اختر المأتم</option>
								        <option value="مأتم الامام الباقر عليه السلام">حسينية الإمام الباقر عليه السلام</option>
								        <option value="مأتم الامام علي عليه السلام">مأتم الامام علي عليه السلام</option>
								        <option value="مأتم الامام الصادق عليه السلام">مأتم الامام الصادق عليه السلام</option>
								        <option value="مأتم الامام الرضا عليه السلام">مأتم الامام الرضا عليه السلام</option>
								        <option value="مأتم الطويلة">مأتم الطويلة</option>
								        <option value="قاطني قرية بوري">قاطني قرية بوري</option>
								      </select>
								    </div>
								</div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
												<input name="cpr" class="form-control" placeholder="الرقم الشخصي" pattern=".{9,9}" required title="يجب ان يكون الرقم الشخصي 9 ارقام">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
												<h5 style="font-weight: bold;">يرجى ارفاق صورة من البطاقة الذكية</h5>
												<input type="file" name="cpr_image" accept="image/*" class="custom-file-input" required>
												<!--<input id="file" type="file" accept="image/*">-->
                    </div>
                </div>

						<button type="submit" name="new_voter" class="btn btn-icon btn-icon-left btn-success btn-sm waves-effect waves-light"><i class="ico fa fa-check"></i>إرسال الطلب</button>
    	    </form>
		</div>
	</div>

	<hr />
	<div class="row">
		<center>
		<div class="alert alert-info" role="alert" style="color: #f5f7fa; background-color: #333333; border-color: #f5f7fa;">
			<b>إذا كان لديك اي طلب او إستفسار:</b>
			<br />
			<a href="tel:38800825" target="_blank" style="color: #f5f7fa;">إضغط هنا للإتصال بنا</a>
			<br />
			<a href="http://wa.me/97338800825" target="_blank" style="color: #f5f7fa;">راسلنا عبر الواتس اب</a>
		</div>
		</center>
	</div>
</div>

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

	<script>
	$(function(){
	  $("input[name='mobile']").on('input', function (e) {
	    $(this).val($(this).val().replace(/[^0-9]/g, ''));
	  });
	  $("input[name='cpr']").on('input', function (e) {
	    $(this).val($(this).val().replace(/[^0-9]/g, ''));
	  });
	});
	</script>
	<script src="assets/scripts/main.min.js"></script>
</body>
</html>
