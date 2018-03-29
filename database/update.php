<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');
require 'connection.php';

if ($_REQUEST['row_id']) {

    $row_id = $_REQUEST['row_id'];
    $age = $_REQUEST['age'];
    $location = $_REQUEST['location'];
    $gender = $_REQUEST['gender'];

    $sql = "UPDATE users SET age='$age', location ='$location', gender = '$gender' WHERE id= $row_id";     
     $q = mysqli_query($conn,$sql);

      if($q){

        echo $row_Id;
        
    }
    else {
        echo "0";
    }
}

?>