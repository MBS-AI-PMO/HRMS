"use strict";
let displayErrorMessage = (htmlContent) => {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        html: htmlContent
    });
};
let displaySuccessMessage = (customMessage) => {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    Toast.fire({
        icon: 'success',
        title: customMessage
    });
};
let isErrorCodes = (errorCode) => {
    return laravelErrorCodes.some(error => error.code === errorCode);
};
let laravelErrorCodes = [
    { code: 400, message: "Bad Request" },
    { code: 401, message: "Unauthorized" },
    { code: 403, message: "Forbidden" },
    { code: 404, message: "Not Found" },
    { code: 500, message: "Internal Server Error" },
];
