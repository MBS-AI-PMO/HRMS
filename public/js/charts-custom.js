$(document).ready(function () {

    'use strict';

    var chartFont = "'Segoe UI', system-ui, -apple-system, sans-serif";

    if (typeof Chart !== 'undefined') {
        Chart.defaults.global.defaultFontFamily = chartFont;
        Chart.defaults.global.defaultFontColor = '#64748b';
        Chart.defaults.global.responsive = true;
    }

    function trackChart(chart) {
        return chart;
    }

    function shouldShowInlineLegend(labels) {
        return Array.isArray(labels) && labels.length > 0 && labels.length <= 6;
    }

    function renderScrollableLegend(containerId, labels, colors, counts) {
        var $container = $('#' + containerId);

        if (!$container.length || !Array.isArray(labels) || labels.length === 0) {
            return;
        }

        var html = '';

        labels.forEach(function (label, index) {
            var color = colors[index] || '#94a3b8';
            var count = counts[index] != null ? counts[index] : 0;

            html += '<div class="hr-chart-legend-item">';
            html += '<span class="hr-chart-legend-dot" style="background:' + color + '"></span>';
            html += '<span class="hr-chart-legend-text" title="' + label + '">' + label + '</span>';
            html += '<span class="hr-chart-legend-count">' + count + '</span>';
            html += '</div>';
        });

        $container.html(html);
    }

    function dashboardDoughnutOptions(labels, showLegend) {
        var useLegend = typeof showLegend === 'boolean'
            ? showLegend
            : shouldShowInlineLegend(labels);

        return {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.35,
            cutoutPercentage: 65,
            legend: {
                display: useLegend,
                position: 'bottom',
                labels: {
                    padding: 10,
                    usePointStyle: true,
                    fontSize: 10,
                    boxWidth: 10,
                },
            },
            layout: {
                padding: {
                    top: 4,
                    bottom: useLegend ? 0 : 4,
                    left: 4,
                    right: 4,
                },
            },
            tooltips: {
                backgroundColor: '#1e293b',
                titleFontSize: 13,
                bodyFontSize: 12,
                cornerRadius: 8,
                xPadding: 12,
                yPadding: 10,
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 900,
            },
        };
    }

    function dashboardPieOptions(labels) {
        return {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.35,
            legend: {
                display: shouldShowInlineLegend(labels),
                position: 'bottom',
                labels: {
                    padding: 10,
                    usePointStyle: true,
                    fontSize: 10,
                    boxWidth: 10,
                },
            },
            layout: {
                padding: {
                    top: 4,
                    bottom: 4,
                    left: 4,
                    right: 4,
                },
            },
            tooltips: {
                backgroundColor: '#1e293b',
                titleFontSize: 13,
                bodyFontSize: 12,
                cornerRadius: 8,
                xPadding: 12,
                yPadding: 10,
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 900,
            },
        };
    }

    // ------------------------------------------------------- //
    // Line Chart
    // ------------------------------------------------------ //

    var PaymentLastSix    = $('#payment_last_six');

    if (PaymentLastSix.length > 0) {
        var last_six_month_payment = PaymentLastSix.data('last_six_month_payment');
        var label1 = PaymentLastSix.data('label1');
        var payment_last_six = new Chart(PaymentLastSix, {
            type: 'bar',
            data: {
                labels: PaymentLastSix.data('months') ,
                datasets: [
                    {
                        label: label1,
                        backgroundColor: [
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                            'rgba(137, 196, 244, 1)',
                        ],
                        borderColor: [
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                            'rgba(34, 49, 63, 1)',
                        ],
                        borderWidth: 2,
                        data: [ last_six_month_payment[0], last_six_month_payment[1],
                            last_six_month_payment[2], last_six_month_payment[3],
                            last_six_month_payment[4], last_six_month_payment[5]
                            ]
                    },
                ]
            }
        });
    };


    var PIECHART = $('#pieChart');
    if (PIECHART.length > 0) {
        var brandPrimary = PIECHART.data('color');
        var brandPrimaryRgba = PIECHART.data('color_rgba');
        var price = PIECHART.data('price');
        var cost = PIECHART.data('cost');
        var label1 = PIECHART.data('label1');
        var label2 = PIECHART.data('label2');
        var label3 = PIECHART.data('label3');
        var myPieChart = new Chart(PIECHART, {
            type: 'pie',
            data: {
                labels: [
                    label1,
                    label2,
                    label3
                ],
                datasets: [
                    {
                        data: [price, cost, price-cost],
                        borderWidth: [1, 1, 1],
                        backgroundColor: [
                            brandPrimary,
                            "#ff8952",
                            "#858c85"
                        ],
                        hoverBackgroundColor: [
                            brandPrimaryRgba,
                            "rgba(255, 137, 82, 0.8)",
                            "rgb(133, 140, 133, 0.8)"
                        ],
                        hoverBorderWidth: [4, 4, 4],
                        hoverBorderColor: [
                            brandPrimaryRgba,
                            "rgba(255, 137, 82, 0.8)",
                            "rgb(133, 140, 133, 0.8)",
                            
                        ],
                    }]
            },
            options: {
                //rotation: -0.7*Math.PI
            }
        });
    }

    var DepartmentDoughnutChart = $('#department_chart');
    if (DepartmentDoughnutChart.length > 0) {
        var dept_bgcolor = DepartmentDoughnutChart.data('dept_bgcolor');
        var hover_dept_bgcolor = DepartmentDoughnutChart.data('hover_dept_bgcolor');
        var dept_emp_count = DepartmentDoughnutChart.data('dept_emp_count');
        var dept_label = DepartmentDoughnutChart.data('dept_label');


        var myDepartmetnDoughnutChart = trackChart(new Chart(DepartmentDoughnutChart, {
            type: 'doughnut',
            data: {
                labels: dept_label,
                datasets:[
                    {
                        data: dept_emp_count,
                        backgroundColor: dept_bgcolor,
                        hoverBackgroundColor: hover_dept_bgcolor,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }
                    ]
            },
            options: dashboardDoughnutOptions(dept_label, false)
        }));

        renderScrollableLegend('department_chart_legend', dept_label, dept_bgcolor, dept_emp_count);
    }

    var DesignationDoughnutChart = $('#designation_chart');
    if (DesignationDoughnutChart.length > 0) {
        var desig_bgcolor = DesignationDoughnutChart.data('desig_bgcolor');
        var hover_desig_bgcolor = DesignationDoughnutChart.data('hover_desig_bgcolor');
        var desig_emp_count = DesignationDoughnutChart.data('desig_emp_count');
        var desig_label = DesignationDoughnutChart.data('desig_label');


        var myDesignationDoughnutChart = trackChart(new Chart(DesignationDoughnutChart, {
            type: 'doughnut',
            data: {
                labels: desig_label,
                datasets:[
                    {
                        data: desig_emp_count,
                        backgroundColor: desig_bgcolor,
                        hoverBackgroundColor: hover_desig_bgcolor,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }
                ]
            },
            options: dashboardDoughnutOptions(desig_label, false)
        }));

        renderScrollableLegend('designation_chart_legend', desig_label, desig_bgcolor, desig_emp_count);
    }

    var AttendanceDoughnutChart = $('#attendance_chart');
    if (AttendanceDoughnutChart.length > 0) {
        var present_count = AttendanceDoughnutChart.data('present_count');
        var absent_count = AttendanceDoughnutChart.data('absent_count');

        var label11 = AttendanceDoughnutChart.data('present_level');
        var label22 =AttendanceDoughnutChart.data('absent_level');

        var myAttendanceDoughnutChart = trackChart(new Chart(AttendanceDoughnutChart, {
            type: 'doughnut',
            data: {
                labels: [label11,label22],
                datasets:[
                    {
                        data: [present_count,absent_count],
                        backgroundColor: ['#10b981', '#cbd5e1'],
                        hoverBackgroundColor: ['#059669', '#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }
                ]
            },
            options: dashboardDoughnutOptions([label11, label22], false)
        }));
    }

    var ExpenseDepositDoughnutChart = $('#expense_deposit_chart');
    if (ExpenseDepositDoughnutChart.length > 0) {
        var expense_count = ExpenseDepositDoughnutChart.data('expense_count');
        var deposit_count = ExpenseDepositDoughnutChart.data('deposit_count');

        var label111 = ExpenseDepositDoughnutChart.data('expense_level');
        var label222 =ExpenseDepositDoughnutChart.data('deposit_level');

        var myExpenseDepositDoughnutChart = new Chart(ExpenseDepositDoughnutChart, {
            type: 'pie',
            data: {
                labels: [label111,label222],
                datasets:[
                    {
                        data: [expense_count,deposit_count],
                        backgroundColor:[  "rgba(39,217,177)",
                            "rgb(133, 140, 133)"],
                        hoverBackgroundColor:  [
                            "rgba(39,217,177, 0.8)",
                            "rgb(133, 140, 133, 0.8)"
                        ],
                    }
                ]
            }
        });
    }

    var ProjectDoughnutChart = $('#project_chart');
    if (ProjectDoughnutChart.length > 0) {
        var project_status = ProjectDoughnutChart.data('project_status');
        var project_label = ProjectDoughnutChart.data('project_label');
        var project_color = ProjectDoughnutChart.data('project_color') || [];
        var defaultProjectColors = [
            '#19AED9',
            '#37205B',
            '#BF8CFF',
            '#F4605B',
        ];
        var projectBackgroundColors = (project_label || []).map(function (label, index) {
            return project_color[index] || defaultProjectColors[index % defaultProjectColors.length];
        });
        var projectHoverColors = projectBackgroundColors.map(function (color) {
            if (color.indexOf('rgba') === 0) {
                return color;
            }
            if (color.indexOf('#') === 0) {
                return color + 'CC';
            }
            return color.replace('rgb(', 'rgba(').replace(')', ', 0.8)');
        });

        var myProjectDoughnutChart = trackChart(new Chart(ProjectDoughnutChart, {
            type: 'doughnut',
            data: {
                labels: project_label,
                datasets:[
                    {
                        data: project_status,
                        backgroundColor: projectBackgroundColors,
                        hoverBackgroundColor: projectHoverColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }
                ]
            },
            options: Object.assign({}, dashboardDoughnutOptions(project_label, true), {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 14,
                        usePointStyle: true,
                        fontSize: 11,
                        generateLabels: function (chart) {
                            var dataset = chart.data.datasets[0];
                            return chart.data.labels.map(function (label, index) {
                                return {
                                    text: label,
                                    fillStyle: dataset.backgroundColor[index],
                                    strokeStyle: '#ffffff',
                                    lineWidth: 1,
                                    hidden: false,
                                    index: index
                                };
                            });
                        }
                    }
                },
                tooltips: {
                    backgroundColor: '#1e293b',
                    titleFontSize: 13,
                    bodyFontSize: 12,
                    cornerRadius: 8,
                    filter: function (tooltipItem, data) {
                        var value = data.datasets[0].data[tooltipItem.index];
                        return value > 0;
                    }
                }
            })
        }));
    }

});
