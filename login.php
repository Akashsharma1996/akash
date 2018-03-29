
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {font-family: Arial, Helvetica, sans-serif;}
form {border: 3px solid #f1f1f1;}

input[type=text], input[type=password] {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

button {
    background-color: #4CAF50;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    cursor: pointer;
    width: 100%;
}

button:hover {
    opacity: 0.8;
}

.cancelbtn {
    width: auto;
    padding: 10px 18px;
    background-color: #f44336;
}

.imgcontainer {
    text-align: center;
    margin: 24px 0 12px 0;
}

img.avatar {
    width: 40%;
    border-radius: 50%;
}

.container {
    padding: 16px;
}

span.psw {
    float: right;
    padding-top: 16px;
}

/* Change styles for span and cancel button on extra small screens */
@media screen and (max-width: 300px) {
    span.psw {
       display: block;
       float: none;
    }
    .cancelbtn {
       width: 100%;
    }
}
</style>
</head>
<body>

<h2></h2>

<form action="login.php" method="post">
  <div class="imgcontainer">
    <img src="img_avatar2.png" alt="Admin Login" class="avatar">
  </div>

  <div class="container">
    <label for="uname"><b>Username</b></label>
    <input type="text" placeholder="Enter Username" name="username" required>

    <label for="psw"><b>Password</b></label>
    <input type="password" placeholder="Enter Password" name="password" required>
        
    <button type="submit" name="submit">Login</button>
    
  </div>

  <div class="container" style="background-color:#f1f1f1">
  </div>
</form>

</body>
</html>

<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');
$conn = mysqli_connect('localhost', 'root', 'admin', 'company');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if(isset($_POST['submit']))
{
  $username=$_POST['username'];
  $password=$_POST['password'];
  $qry="SELECT * FROM `login` where `username`='$username' AND `password`='$password'";
$run=mysqli_query($conn,$qry);
$row = mysqli_num_rows($run);

if($row <1)
{
     ?>
  <script>alert('not match');
    
window.open('login.php','_self');

  </script>
  <?php 
}
else
{
  $data=mysqli_fetch_assoc($run);

  $id=$data['id'];

session_start();

$_SESSION['id']=$id;
header('Location:admin.php');



}

}




?>








