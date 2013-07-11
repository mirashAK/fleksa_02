jQuery(document).ready(function()
{
  
  var send_xhr_form = function (form_name)
  {
    var form = jQuery('form[name='+form_name+']');
    
    jQuery(form).on('submit', function (e)
    {
      e.preventDefault();
      
      var data = new Object ();
      data[form.attr('name')] = form.serialize();

      // Ajax sending
      jQuery.ajax(
      {
        url: form.attr('action'),
        type: 'post',
        dataType: 'json',
        data: data,
        success: function(answer)
        {
          if (answer.valid == true)
          {
            if (answer.redirect !== false) window.location = answer.redirect;
            //if (answer.view !== false) jQuery('div.layout-inner').html(answer.view);
          }
          else
          {
            for ( name in answer.errors )  { alert( name +': '+ answer.errors[name] ); };
          }
        },
        error: function(e){
          console.log("error"+e);
        }
      });
      
    });
  }
  
  send_xhr_form('auth_form');
  send_xhr_form('reg_form');
  
}); 
