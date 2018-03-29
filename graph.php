<?php
$connect = mysqli_connect("localhost", "root", "admin", "firstjob");
$query = "SELECT * FROM users";
$result = mysqli_query($connect, $query);
$chart_data = '';
while($row = mysqli_fetch_array($result))
{
 $chart_data = "{ date:'".$row["dateposted"]."', user:".$row["id"]."}, ";
}
$chart_data   = substr($chart_data, 0, -2);

echo $date;


?>
<!DOCTYPE html>
<html>
 <head>
  <title>user Graph </title>
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
  
 </head>
 <body>
  <br /><br />
  <div class="container" style="width:900px;">
   <h2 align="center"></h2>
   <h3 align="center">USER GRAPH</h3>   
   <br /><br />
   <div id="chart"></div>
  </div>
 </body>
</html>
 
<script>
Morris.Line({
 element : 'chart',
 data:[<?php echo $chart_data; ?>],
 xkey:'date',
 ykeys:['user'],
 labels:['user'],
 hideHover:'auto',
 stacked:true
});
</script>

