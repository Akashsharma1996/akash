
function first_form(){
      
      var Fname = $("#Fname").val();
      var Lname = $("#Lname").val();
      var Email = $("#Email").val();
      

      $.ajax({

        type : 'POST',
        data : {Fname:Fname, Lname:Lname, Email:Email},
        url  : "database/insert.php",

        success: function(result){
          var rowId = result;
          //alert(rowId);
            $("#applydiv").hide();
            $("#second").show();
            $("#last_id").val(rowId);
            $("#Email").val(Email);
        }
      })


}

function second_form(){

 
  var row_id = $("#last_id").val();
  
  //alert(row_id);

  var row_id = $("#last_id").val();
  var age      = $("#age").val();
  var location = $("#location").val();
  var gender   = $("#gender").val();
  var Email = $("#Email").val();



  $.ajax({

        type : 'POST',
        data : {row_id:row_id, age:age, location:location, gender:gender},
        url  : "database/update.php",

        success: function(next_result){
          //alert(next_result);
          $("#second").hide();
          $("#third").show();
          $("#Email").val(Email);
          
        }
      })

}


function third_form(){

 
  var row_Id = $("#last_id").val();
  
  //alert(row_Id);
  var Email     = $("#Email").val();
  
  alert("you are registered with firstjob.com....THANKYOU");

  var row_Id    = $("#last_id").val();
  var image     = $("#File").val();
  var education = $("#education").val();
  var skills    = $("#skills").val();
  var Email     = $("#Email").val();


  $.ajax({

        type : 'POST',
        data : {row_Id:row_Id, image:image, education:education, skills:skills,Email:Email},
        url  : "database/update2.php",

        success: function(next_results){
          
          $("#third").hide();
          //alert(next_results);
        }
      })

}