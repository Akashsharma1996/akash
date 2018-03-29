<?php
require 'database/connection.php';

       function showdata(){
       	 GLOBAL $conn;
	

	$query="SELECT * FROM `users`";
	$run = mysqli_query($conn,$query);
	if($run == true){
	?>
	<table border="1" width="100%" align="center" style="margin-top: 100px;">
	<tr><td>S.NO.</td><td>F.NAME</td><td>L.NAME</td><td>EMAIL ID</td><td>AGE</td><td>LOCATION</td><td>GENDER</td><td>IMAGE</td><td>EDUCATION</td><td>SKILLS</td><td>DATE</td>
	</tr>
	<?php
	 while($data = mysqli_fetch_assoc($run)){
	 	?>
	 	<tr>
	 	<td><?php echo $data['id'];?></td>
	 	<td><?php echo $data['firstname'];?></td>
	 	<td><?php echo $data['lastname'];?></td>
	 	<td><?php echo $data['email'];?></td>
	 	<td><?php echo $data['age'];?></td>
	 	<td><?php echo $data['location'];?></td>
	 	<td><?php echo $data['gender'];?></td>
	 	<td><?php echo $data['image'];?></td>
	 	<td><?php echo $data['education'];?></td>
	 	<td><?php echo $data['skills'];?></td>
	 	<td><?php echo $data['dateposted'];?></td>
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
	show data</title>
	</head>
	<body>
		<?php showdata();
		?>
	</body>
	</html>
	
		

	
