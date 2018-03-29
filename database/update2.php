<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');
require 'connection.php';

if ($_REQUEST['row_Id']) {

    $row_Id = $_REQUEST['row_Id'];
    $image = $_REQUEST['image'];
    $education = $_REQUEST['education'];
    $skills = implode(',', $_REQUEST['skills']);
    $Email = $_REQUEST['Email'];

  


    $sql = "UPDATE users SET image='$image', education ='$education', skills = '$skills' WHERE id= $row_Id";     
     $q = mysqli_query($conn,$sql);

      if($q){

        echo $row_Id;

    }
    else {
        echo "0";
    }
}
?>