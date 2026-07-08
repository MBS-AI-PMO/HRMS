(function ($) {
    "use strict";

    $(document).on('click', '#bulk_delete', function () {
        var id = [];
        let table = $('#dataListTable').DataTable();
        id = table.rows({selected: true}).ids().toArray();

        if (id.length > 0) {
            Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post({
                        url: bulkDeleteURL,
                        data: {
                            idsArray: id
                        },
                        error: function(response) {
                            console.log(response);
                            let htmlContent = prepareMessage(response);
                            displayErrorMessage(htmlContent);
                        },
                        success: function (response) {
                            console.log(response);
                            displaySuccessMessage(response.success);
                            $('#dataListTable').DataTable().ajax.reload();
                        }
                     });
                }
            })
        } else {
            Swal.fire({
                title: "",
                text: "Please select at least one checkbox.",
                icon: "warning"
            });
        }
    });

})(jQuery);
