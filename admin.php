<?php  

if(empty($_GET['v'])){
		$value = '';
	}
else {
		$value = $_GET['v'];
	}	
	
?>
<!DOCTYPE html>
<html>
<head>
	<title>admin</title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
	<header id="header1">	
		<div class="navigationadmin">
			<div class="container">
				<div class="row">
					<div class="col-md-6 col-sm-6 col-xs-6">
						<div class="logo">	
						      <a href="index.php"><img src="images/firstlogo.png" class="img-responsive"></a>	
						</div>
					</div>
					<div class="col-md-6 col-sm-6 col-xs-6">
						<div class="nav-button">
							  <a href="index.php" style="color: white;">Home</a>
							  <a href="logout.php" style="color: white">Logout</a>
					   </div>

					</div>
				</div>
			</div>
		</div>
	</header>
	     <div class="container">
				<div class="row">
					<div class="col-md-4 col-sm-4 col-xs-4">
						<div class="sidebar">
							<ul>
							<li><a href="admin.php?v=view graph">graph</a></li>
							<li><a href="admin.php?v=view user">Users</a></li>
							<li><a href="admin.php?v=view Education">Education</a></li>
							<li><a href="admin.php?v=view weekly">Weekly data</a></li>
							<li><a href="admin.php?v=Add education">Add education</a></li>
					</ul>
							
						</div>
					</div>
					<div class="col-md-8 col-sm-8 col-xs-8">
						<div class="main content">
							<?php 
						     switch ($value) {
						     	case 'view graph':
						     		    require "graph.php";
						     			break;
						     	case 'view user':
						     		    require "user.php";
						     			break;	

						     	case 'view Education':
						     		    require "education.php";
						     			break;
						     	case 'view weekly':
						     		    require "weeklydata.php";
						     			break;
						     	case 'Add education':
						     		    require "addeducation.php";
						     			break;									
						     }

 
							?>
						</div>
						

					</div>
				</div>				
			</div>			


</body>
</html>