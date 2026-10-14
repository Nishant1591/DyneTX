jQuery(document).ready(function($){

 
 

$("input.usedefault[type='radio']").change(function(){
alert('change');
if ($('.usedefault:checked').val() == '1') {
  $(".defaultinput").prop("readonly",true);
  $(".defaultinput").prop("disabled",true);  
 } else {
  $(".defaultinput").prop("readonly",false);
  $(".defaultinput").prop("disabled",false);
 
 }
});


});

 