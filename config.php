<?php
$con = mysqli_connect("localhost","memamali_elections","3y@iqIC_XQi","memamali_elections");
mysqli_set_charset($con,"utf8");
date_default_timezone_set("Asia/Bahrain");

// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
?>
