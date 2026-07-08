(function ($) {
    'use strict';

    var SUBMITTING_CLASS = 'hrms-is-submitting';
    var LOADING_CLASS = 'hrms-modal-loading';
    var THEME_COLOR = '#7c5cc4';

    window.hrmsSwalResponse = function (data, options) {
        options = options || {};

        if (typeof Swal === 'undefined') {
            var fallback = (data && (data.success || data.error)) || options.fallbackError || 'Done';
            alert(fallback);
            return data && data.success ? 'success' : 'error';
        }

        var base = { confirmButtonColor: THEME_COLOR };

        if (!data) {
            Swal.fire(Object.assign({}, base, {
                icon: 'error',
                title: options.errorTitle || 'Error',
                text: options.fallbackError || 'Something went wrong. Please try again.'
            }));
            return 'error';
        }

        if (data.success) {
            var html = typeof data.success === 'string' ? data.success : 'Saved successfully.';

            if (data.staff_id) {
                html += '<br><br><strong>Staff Id:</strong> ' + data.staff_id;
            }

            Swal.fire(Object.assign({}, base, {
                icon: 'success',
                title: options.successTitle || 'Success',
                html: html,
                timer: options.timer !== undefined ? options.timer : 2200,
                showConfirmButton: options.showConfirmButton !== false
            }));

            return 'success';
        }

        if (data.error) {
            Swal.fire(Object.assign({}, base, {
                icon: 'error',
                title: options.errorTitle || 'Error',
                text: data.error
            }));
            return 'error';
        }

        if (data.errors && data.errors.length) {
            var errorHtml = '';

            for (var i = 0; i < data.errors.length; i++) {
                errorHtml += '<p style="margin:0 0 6px;">' + data.errors[i] + '</p>';
            }

            Swal.fire(Object.assign({}, base, {
                icon: 'error',
                title: options.errorTitle || 'Validation Error',
                html: errorHtml
            }));
            return 'error';
        }

        return null;
    };

    function lockModalForm(form) {
        var $form = $(form);
        var $modal = $form.closest('.modal');

        form.classList.add(SUBMITTING_CLASS);
        $modal.addClass(LOADING_CLASS);
        $form.find('button[type="submit"], input[type="submit"]').prop('disabled', true);
    }

    function unlockModalForm(form) {
        if (!form) {
            return;
        }

        var $form = $(form);
        var $modal = $form.closest('.modal');

        form.classList.remove(SUBMITTING_CLASS);
        form.removeAttribute('data-hrms-submit-pending');
        $modal.removeClass(LOADING_CLASS);
        $form.find('button[type="submit"], input[type="submit"]').prop('disabled', false);
    }

    function getModalFormResult($form) {
        return $form.closest('.modal').find('#form_result, [id$="_form_result"], .form_result').first();
    }

    function parseJsonResponse(xhr) {
        if (xhr.responseJSON) {
            return xhr.responseJSON;
        }

        try {
            return JSON.parse(xhr.responseText);
        } catch (error) {
            return null;
        }
    }

    function handleModalFormResponse(form, data) {
        var $form = $(form);

        if (!data || data.errors || data.error) {
            if (data && (data.error || data.errors)) {
                window.hrmsSwalResponse(data);
            }
            return;
        }

        if (!data.success || $form.is('[data-hrms-no-refresh]')) {
            return;
        }

        window.hrmsSwalResponse(data, {
            timer: 1800,
            showConfirmButton: false
        });

        setTimeout(function () {
            window.location.reload();
        }, 1800);
    }

    function wrapAjaxCallback(options, name, form, wrapper) {
        var original = options[name];

        options[name] = function () {
            wrapper(form, options, original, arguments);
        };
    }

    function onAjaxFinished(form, xhr, original, originalArgs) {
        unlockModalForm(form);

        if (xhr && xhr.status >= 200 && xhr.status < 300) {
            handleModalFormResponse(form, parseJsonResponse(xhr));
        }

        if (typeof original === 'function') {
            original.apply(this, originalArgs);
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form || form.tagName !== 'FORM' || !form.closest('.modal')) {
            return;
        }

        if (form.hasAttribute('data-hrms-no-auto-handler')) {
            return;
        }

        if (form.classList.contains(SUBMITTING_CLASS)) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            return false;
        }

        form.setAttribute('data-hrms-submit-pending', '1');
    }, true);

    $.ajaxPrefilter(function (options) {
        var form = document.querySelector('.modal form[data-hrms-submit-pending="1"]')
            || document.querySelector('.modal form.' + SUBMITTING_CLASS);

        if (!form) {
            return;
        }

        form.removeAttribute('data-hrms-submit-pending');

        if (!form.classList.contains(SUBMITTING_CLASS)) {
            lockModalForm(form);
        }

        options.global = false;
        options.hrmsModalForm = form;

        wrapAjaxCallback(options, 'complete', form, function (lockedForm, ajaxOptions, original, args) {
            var xhr = args[0];
            onAjaxFinished(lockedForm, xhr, original, args);
        });

        wrapAjaxCallback(options, 'error', form, function (lockedForm, ajaxOptions, original, args) {
            unlockModalForm(lockedForm);

            if (typeof original !== 'function') {
                window.hrmsSwalResponse(null, {
                    fallbackError: 'Request failed. Please try again.'
                });
            }

            if (typeof original === 'function') {
                original.apply(this, args);
            }
        });
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (!settings || !settings.hrmsModalForm) {
            return;
        }

        unlockModalForm(settings.hrmsModalForm);
    });
})(jQuery);
