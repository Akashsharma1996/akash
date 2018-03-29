 <?php
 
require 'connection.php';
$sql = "SELECT * FROM Education";
$res = mysqli_query($conn, $sql);
  if (mysqli_num_rows($res)>0) {
  while ($newArray = mysqli_fetch_array($res,MYSQLI_ASSOC)){
    echo "<tr><td>" . $newArray["id"]. "</td><td>" . $newArray["name"] . "</td><td>";
}

}
	else{
		echo"no data";
	}

?>