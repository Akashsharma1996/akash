<?php
require 'database/connection.php';

       function showdata(){
       	 GLOBAL $conn;
	

	$query="SELECT  id, firstname, education FROM `users`";
	$run = mysqli_query($conn,$query);

	if($run == true){
	?>
	<table border="1" width="40%" align="center" style="margin-top: 100px;">
	<tr><td>S.NO.</td><td>NAME</td><td>EDUCATION</td>
	</tr>
	<?php
	 while($data = mysqli_fetch_assoc($run)){
	 	?>
	 	<tr>
	 	<td><?php echo $data['id'];?></td>
	 	<td><?php echo $data['firstname'];?></td>
	 	<td><?php echo $data['education'];?></td>

	 	</tr>
	 	<?php
	 }
	 ?> </table><?php
	}
	else{
		echo "error!";
	 }
	}
	?>
	<html>
	<head>
	<title>
	show name</title>
	</head>
	<body>
		<?php showdata();
		?>
	</body>
	</html>
	
		
	
