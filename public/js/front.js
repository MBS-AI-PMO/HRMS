/*global $, document, Chart, LINECHART, data, options, window*/
$(document).ready(function () {
    $('nav.side-navbar').addClass('shrink');

    'use strict';

    // Main Template Color
    var brandPrimary = '#33b35a';

    $('[data-toggle="tooltip"]').tooltip();

    // ------------------------------------------------------- //
    // Custom Scrollbar
    // ------------------------------------------------------ //

    if ($(window).outerWidth() > 992) {
        $("nav.side-navbar, .right-sidebar, .card-body.list").mCustomScrollbar({
            theme: "light",
            scrollInertia: 200
        });
    }


    $(document).scroll(function() {
        var y = $(this).scrollTop();
        if (y > 65) {
            $('nav.side-navbar').css("top","0");
        } else {
            $('nav.side-navbar').css("top","65px");
        }
    });


    // ------------------------------------------------------- //
    // Side Navbar Functionality
    // ------------------------------------------------------ //
    if ($(window).outerWidth() > 1199) {
        $('nav.side-navbar').removeClass('shrink');
    }
    $('#toggle-btn').on('click', function (e) {

        e.preventDefault();

        if ($(window).outerWidth() > 1199) {
            $('nav.side-navbar').toggleClass('shrink');
            $('.page').toggleClass('active');
        } else {
            $('nav.side-navbar').toggleClass('shrink');
            $('.page').toggleClass('active-sm');
        }
    });


    // ------------------------------------------------------- //
    // Login  form validation
    // ------------------------------------------------------ //
    $('#login-form').validate({
        messages: {
            loginUsername: 'please enter your username',
            loginPassword: 'please enter your password'
        }
    });

    // ------------------------------------------------------- //
    // Material Inputs
    // ------------------------------------------------------ //

    var materialInputs = $('input.input-material');

    // activate labels for prefilled values
    materialInputs.filter(function() { return $(this).val() !== ""; }).siblings('.label-material').addClass('active');

    // move label on focus
    materialInputs.on('focus', function () {
        $(this).siblings('.label-material').addClass('active');
    });

    // remove/keep label on blur
    materialInputs.on('blur', function () {
        $(this).siblings('.label-material').removeClass('active');

        if ($(this).val() !== '') {
            $(this).siblings('.label-material').addClass('active');
        } else {
            $(this).siblings('.label-material').removeClass('active');
        }
    });

    // ------------------------------------------------------- //
    // Jquery Progress Circle
    // ------------------------------------------------------ //
    var progress_circle = $("#progress-circle").gmpc({
        color: brandPrimary,
        line_width: 5,
        percent: 80
    });
    progress_circle.gmpc('animate', 80, 3000);

    // ------------------------------------------------------- //
    // External links to new window
    // ------------------------------------------------------ //

    $('.external').on('click', function (e) {

        e.preventDefault();
        window.open($(this).attr("href"));
    });

    // ------------------------------------------------------- //
    // Jquery clockpicker
    // ------------------------------------------------------ //

    var input = $('.time');
        input.clockpicker({
        placement: 'top',
        autoclose: false,
        twelvehour: true,
        donetext:'Done'

    });

    // ------------------------------------------------------- //
    // Header Dropdown / Right Sidebar
    // ------------------------------------------------------ //
    $('header .dropdown-item').on('click', function(){
        $('.right-sidebar.open').removeClass('open');
        $(this).siblings('.right-sidebar').addClass('open');
        $('.page').on('click', function(){
            $('.right-sidebar.open').removeClass('open');
        })
    });

    // ------------------------------------------------------- //
    // full screen button
    // ------------------------------------------------------ //

    function toggleFullscreen(elem) {
        elem = elem || document.documentElement;
        if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.msRequestFullscreen) {
                    elem.msRequestFullscreen();
                } else if (elem.mozRequestFullScreen) {
                    elem.mozRequestFullScreen();
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    }
    if(('#btnFullscreen').length > 0) {
        document.getElementById('btnFullscreen').addEventListener('click', function() {
            toggleFullscreen();
        });
    }

    // ------------------------------------------------------ //
    // For demo purposes, can be deleted
    // ------------------------------------------------------ //

    var stylesheet = $('link#theme-stylesheet');
    $( "<link id='new-stylesheet' rel='stylesheet'>" ).insertAfter(stylesheet);
    var alternateColour = $('link#new-stylesheet');

    // if ($.cookie("theme_csspath")) {
    //     alternateColour.attr("href", $.cookie("theme_csspath"));
    // }

    $("#colour").change(function () {

        if ($(this).val() !== '') {

            var theme_csspath = 'css/style.' + $(this).val() + '.css';

            alternateColour.attr("href", theme_csspath);

            $.cookie("theme_csspath", theme_csspath, { expires: 365, path: document.URL.substr(0, document.URL.lastIndexOf('/')) });

        }

        return false;
    });

    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function(xhr, settings){
            if (settings && settings.hrmsModalForm) {
                return;
            }
            $('#loader').css('display','block');
        }
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings && settings.hrmsModalForm) {
            return;
        }
        $('#loader').css('display','none');
    });

    function normalizeSidebarPath(path) {
        path = (path || '').replace(/\/index\.php\/?/i, '/');
        path = path.replace(/\/+$/, '');

        return path || '/';
    }

    function highlightSidebarNavigation() {
        var $menu = $('#side-main-menu');

        if (!$menu.length) {
            return;
        }

        var currentPath = normalizeSidebarPath(window.location.pathname);
        var currentSearch = window.location.search || '';
        var $bestLink = null;
        var bestScore = -1;

        $menu.find('a[href]').each(function () {
            var href = $(this).attr('href');

            if (!href || href === '#' || href.indexOf('#') === 0) {
                return;
            }

            var linkUrl;

            try {
                linkUrl = new URL(href, window.location.origin);
            } catch (error) {
                return;
            }

            var linkPath = normalizeSidebarPath(linkUrl.pathname);
            var linkSearch = linkUrl.search || '';
            var matches = false;
            var score = linkPath.length;

            if (linkSearch) {
                matches = linkSearch === currentSearch && currentPath === linkPath;
                score += 1000;
            } else if (!currentSearch) {
                matches = currentPath === linkPath
                    || (linkPath !== '/' && currentPath.indexOf(linkPath + '/') === 0);
            } else if (currentPath === linkPath) {
                matches = true;
            }

            if (matches && score >= bestScore) {
                bestScore = score;
                $bestLink = $(this);
            }
        });

        if ($bestLink && $bestLink.length) {
            var $activeItem = $bestLink.closest('li');

            $activeItem.addClass('active');

            $activeItem.parents('#side-main-menu li').each(function () {
                var $parentItem = $(this);
                var $submenu = $parentItem.children('ul.collapse');

                if (!$submenu.length) {
                    return;
                }

                $parentItem.addClass('active');
                $parentItem.children('a[data-toggle="collapse"]').attr('aria-expanded', true);
                $submenu.addClass('show');
            });
        }
    }

    highlightSidebarNavigation();

    $('#side-main-menu li.active').each(function () {
        var $item = $(this);
        var $submenu = $item.children('ul.collapse');

        if ($submenu.length) {
            $item.children('a[data-toggle="collapse"]').attr('aria-expanded', true);
            $submenu.addClass('show');
        }
    });


});

window.hrmsEmployeeInitials = function (name) {
    var parts = String(name || '').trim().split(/\s+/).filter(Boolean);

    if (parts.length >= 2) {
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    return (parts[0] || '?').substring(0, 2).toUpperCase();
};

window.hrmsStatusBadge = function (status) {
    var normalized = String(status || '').toLowerCase();
    var labels = {
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected'
    };
    var classes = {
        pending: 'hrms-badge-pending',
        approved: 'hrms-badge-approved',
        rejected: 'hrms-badge-rejected'
    };
    var label = labels[normalized] || status || '—';
    var badgeClass = classes[normalized] || 'hrms-badge-default';

    return '<span class="hrms-status-badge ' + badgeClass + '">' + label + '</span>';
};

window.hrmsFormatAppliedDate = function (value) {
    if (!value) {
        return '—';
    }

    return String(value).replace(/\s*--\s*/, ' · ');
};

window.hrmsDisplayValue = function (value) {
    var text = String(value || '').trim();

    return text === '' ? '—' : text;
};

window.hrmsFillLeaveInfoModal = function (result, ids, labels) {
    labels = labels || {};
    var status = String((result.data && result.data.status) || '').toLowerCase();

    $(ids.avatar).text(hrmsEmployeeInitials(result.employee_name));
    $(ids.employee).text(hrmsDisplayValue(result.employee_name));
    $(ids.department).text(hrmsDisplayValue(result.department));
    $(ids.company).text(hrmsDisplayValue(result.company_name));
    $(ids.type).text(hrmsDisplayValue(result.leave_type_name));
    $(ids.startDate).text(hrmsDisplayValue(result.start_date_name));
    $(ids.endDate).text(hrmsDisplayValue(result.end_date_name));
    $(ids.appliedDate).text(hrmsFormatAppliedDate(result.data.created_at));
    $(ids.totalDays).text(hrmsDisplayValue(result.data.total_days));
    $(ids.status).html(hrmsStatusBadge(status));
    $(ids.reason).text(hrmsDisplayValue(result.data.leave_reason));

    if (result.data.remarks) {
        $(ids.remarksSection).show();
        $(ids.remarks).text(result.data.remarks);
    } else {
        $(ids.remarksSection).hide();
    }

    if (result.approved_by_name && result.approved_by_name !== '-' && status !== 'pending') {
        $(ids.approvedByLabel).text(status === 'approved'
            ? (labels.approvedBy || 'Approved By')
            : (labels.rejectedBy || 'Rejected By'));
        $(ids.approvedById).text(result.approved_by_name);
        $(ids.approvedByRow).show();
    } else {
        $(ids.approvedByRow).hide();
    }

    $(ids.halfDay).text(result.data.is_half == 1 ? (labels.yes || 'Yes') : (labels.no || 'No'));
    $(ids.notify).text(result.data.is_notify == 1 ? (labels.on || 'On') : (labels.off || 'Off'));
};

$(window).on('load', function(){
    $("#loader").addClass("d-none");
    $("#content").removeClass("d-none");
});
