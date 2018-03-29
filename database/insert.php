<?php 

error_reporting(E_ALL);
ini_set('display_errors', '1');


require 'connection.php';


if ($_REQUEST['Fname']) {
    

    $firstname = $_REQUEST['Fname'];
    $lastname = $_REQUEST['Lname'];
    $email = $_REQUEST['Email'];
    

	$query = "INSERT into users (firstname, lastname, email, dateposted) values('$firstname', '$lastname', '$email', now())";


    $q = mysqli_query($conn,$query);
    if($q){
        echo $id = $conn->insert_id;
    }
    else {
        echo "0";
    }
              
}



?>