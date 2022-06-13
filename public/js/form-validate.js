
// var score_card_data = JSON.parse(JSON.stringify(data));  //solves the problem
// var config_data = JSON.parse(JSON.stringify(config));
// var spinner = $('#loader');

// $(document).ready(function(){
//     // Score Card Name Append
//     string = "";
//     for (let index = 0; index < config_data.score_card_name.length; index++) {
//         score_val = index+1;
//         string+="<option value="+score_val+">"+config_data.score_card_name[index].name+"</option>"
//     }
//     $("#score_card").html(string);

//     //  Score Card Version Append
  
//     update_version();
// })
// var total = 0;
// owner = "maha";
// var api_url = "https://api.scorecardengine.com";
$(function () {
    
    $.validator.setDefaults({
      submitHandler: function () {
        alert( "Form successful submitted!" );
      }
    });
    $('#validateForm').validate({
      rules: {
            // score_card: {
            //     required: true
            // },
            // version: {
            //     required: true
            // },
            file:{
                required:true
            }
        },
        messages: {
            score_card: "Please select score card",
            version: "Version number is required"
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        },
        submitHandler: function(form) {
            form.submit();
            // setInterval(form_process(), 300000);
            
        }
    });
});



