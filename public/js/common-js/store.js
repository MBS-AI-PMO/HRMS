(function ($) {
    "use strict";

    $("#submitForm").on("submit",function(e){
        e.preventDefault();
        $('#submitButton').text('Saving...');
        $.post({
            url: storeURL,
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            dataType: "json",
            error: function(response) {
                console.log(response);
                let htmlContent = prepareMessage(response);
                displayErrorMessage(htmlContent);
                $('#submitButton').text('Save');

            },
            success: function (response) {
                console.log(response);
                displaySuccessMessage(response.success);
                $('#dataListTable').DataTable().ajax.reload();
                $('#submitForm')[0].reset();
                $("#createModal").modal('hide');
                $('#submitButton').text('Save');
            }
        });
    });

})(jQuery);
