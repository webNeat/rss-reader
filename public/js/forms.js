$('form.ajax').submit(function(e){
    e.preventDefault();
    var data = $(this).serialize();
    var url = $(this).attr('action');
    var method = $(this).attr('method');
    var redirect = $(this).attr('redirect-to');
    $.ajax(url, { method: method, data: data })
        .done(function(response){
            $('#content').html(response);
            $('#sidebar').load('/sidebar');
        })
        .fail(function(){
            $('#alerts').html('<div class="alert alert-dismissible alert-warning"> Some Error occured ! </div>');
        });
    return false;
});