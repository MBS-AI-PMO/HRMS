"use strict";
const discountTypeHiddenValue = $('input[name="discount_type_hidden"]').val();
if (discountTypeHiddenValue !== undefined) {
    $('select[name="discount_type"]').val(discountTypeHiddenValue);
}
$(document).on('click', '.remove-invoice-item', function () {
    let item_id = $(this).parent().parent().parent().attr('id');
    let rmv_sub_total = Number($('#' + item_id + ' .sub-total-item').val());
    let all_total = Number($('#sub_total').text());
    let new_sub_total = all_total - rmv_sub_total;
    $('#sub_total').html(new_sub_total.toFixed(2));
    $('#grand_total').html(new_sub_total.toFixed(2));
    let all_tax = Number($('#total_tax').text());
    let rmv_tax = Number($('#' + item_id + ' .tax_amount').val());
    let new_tax = all_tax - rmv_tax;
    $('#total_tax').html(new_tax.toFixed(2));
    calcDiscount();
    $('#' + item_id).remove();
});
$(document).on('change', '.discount_type', function () {
    var total = Number($('#sub_total').text());
    $('.discount_figure').val(0);
    $('.discount_amount').val(0);
    $('#grand_total').html(total.toFixed(2));
});
//Calculate Grand Total
$(document).on('change keyup', function () {
    let total = Number($('#sub_total').text());
    let discount_figure = Number($('.discount_figure').val());
    let grand_total = 0;
    if ($('.discount_amount').val() === 0) {
        $('#grand_total').html(total.toFixed(2));
    }
    else {
        if ($('.discount_type').val() === 0) {
            grand_total = (total - discount_figure);
            $('#grand_total').html(grand_total.toFixed(2));
        }
        else {
            grand_total = (total - ((total * discount_figure) / 100));
            $('#grand_total').html(grand_total.toFixed(2));
        }
    }
    calcDiscount();
});
$(document).on('keyup', '.discount_figure', function () {
    calcDiscount();
});
let calcDiscount = () => {
    var discount_type = Number($('.discount_type').val());
    var discount_figure = Number($('.discount_figure').val());
    if (discount_type === 0) {
        $('.discount_amount').val(discount_figure);
    }
    else {
        var total = Number($('#sub_total').text());
        discount_figure = ((total * discount_figure) / 100);
        $('.discount_amount').val(discount_figure);
    }
};
$(document).on('keyup', '.calc', function () {
    var k = Number($(this).parent().parent().parent().attr('id'));
    var qty = Number($('#' + k + ' .qty_hrs').val());
    var unit_price = Number($('#' + k + ' .unit_price').val());
    var tax_rate = Number($('#' + k + ' .tax_type').val());
    if ($(this).val() !== '') {
        if (!isNaN(tax_rate)) {
            if (tax_rate.toString().indexOf("$") >= 0) {
                tax_rate = Number(tax_rate.substr(1));
                var sub_total = ((qty * unit_price) + tax_rate);
                $('#' + k + ' .sub-total-item').val(sub_total.toFixed(2));
                var tax_amount = tax_rate;
                $('#' + k + ' .tax_amount').val(tax_amount.toFixed(2));
            }
            else {
                var sub_total = ((qty * unit_price) + (((qty * unit_price) * tax_rate) / 100));
                $('#' + k + ' .sub-total-item').val(sub_total.toFixed(2));
                var tax_amount = (((qty * unit_price) * tax_rate) / 100);
                $('#' + k + ' .tax_amount').val(tax_amount.toFixed(2));
            }
        }
        else {
            var sub_total = (qty * unit_price);
            $('#' + k + ' .sub-total-item').val(sub_total.toFixed(2));
        }
    }
    var sum = 0;
    $('.sub-total-item').each(function () {
        sum += Number($(this).val());
    });
    $('#sub_total').html(sum.toFixed(2));
    var total_tax = 0;
    $('.tax_amount').each(function () {
        total_tax += Number($(this).val());
    });
    $('#total_tax').html(total_tax.toFixed(2));
});
$(document).on('change', '.tax_type', function () {
    var k = Number($(this).parent().parent().parent().attr('id'));
    var qty = Number($('#' + k + ' .qty_hrs').val());
    var unit_price = Number($('#' + k + ' .unit_price').val());
    var tax_rate = Number($('#' + k + ' .tax_type').val());
    if ($(this).val() !== '') {
        if (tax_rate.toString().indexOf("$") >= 0) {
            tax_rate = Number(tax_rate.substr(1));
            var sub_total = ((qty * unit_price) + tax_rate);
            $('#' + k + ' .sub-total-item').val(sub_total.toFixed(2));
            var tax_amount = tax_rate;
            $('#' + k + ' .tax_amount').val(tax_amount.toFixed(2));
        }
        else {
            var sub_total = ((qty * unit_price) + (((qty * unit_price) * tax_rate) / 100));
            $('#' + k + ' .sub-total-item').val(sub_total.toFixed(2));
            var tax_amount = (((qty * unit_price) * tax_rate) / 100);
            $('#' + k + ' .tax_amount').val(tax_amount.toFixed(2));
        }
    }
    var sum = 0;
    $('.sub-total-item').each(function () {
        sum += Number($(this).val());
    });
    $('#sub_total').html(sum.toFixed(2));
    var total_tax = 0;
    $('.tax_amount').each(function () {
        total_tax += Number($(this).val());
    });
    $('#total_tax').html(total_tax.toFixed(2));
});
