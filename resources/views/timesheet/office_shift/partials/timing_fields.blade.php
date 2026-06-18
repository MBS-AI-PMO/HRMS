@php
    $shiftDayOptions = [
        ['key' => 'monday', 'label' => trans('file.Monday')],
        ['key' => 'tuesday', 'label' => trans('file.Tuesday')],
        ['key' => 'wednesday', 'label' => trans('file.Wednesday')],
        ['key' => 'thursday', 'label' => trans('file.Thursday')],
        ['key' => 'friday', 'label' => trans('file.Friday')],
        ['key' => 'saturday', 'label' => trans('file.Saturday')],
        ['key' => 'sunday', 'label' => trans('file.Sunday')],
    ];
    $selectedWorkingDays = $shiftFormState['workingDays'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $timingMode = $shiftFormState['timingMode'] ?? 'same';
    $commonIn = $shiftFormState['commonIn'] ?? '';
    $commonOut = $shiftFormState['commonOut'] ?? '';
    $dayTimes = $shiftFormState['dayTimes'] ?? [];
@endphp

<div class="col-md-12">
    <div class="form-group">
        <label class="d-block">{{ __('Working Days') }} *</label>
        <div class="d-flex flex-wrap">
            @foreach ($shiftDayOptions as $dayOption)
                <div class="custom-control custom-checkbox mr-4 mb-2">
                    <input type="checkbox" class="custom-control-input working-day-checkbox"
                           id="working_day_{{ $dayOption['key'] }}"
                           name="working_days[]"
                           value="{{ $dayOption['key'] }}"
                           {{ in_array($dayOption['key'], $selectedWorkingDays, true) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="working_day_{{ $dayOption['key'] }}">
                        {{ $dayOption['label'] }}
                    </label>
                </div>
            @endforeach
        </div>
        <small class="text-muted">{{ __('Select which days are working days for this shift.') }}</small>
    </div>
</div>

<div class="col-md-12">
    <div class="form-group">
        <label class="d-block">{{ __('Shift Timing') }} *</label>
        <div class="custom-control custom-radio custom-control-inline">
            <input type="radio" id="timing_mode_same" name="timing_mode" value="same"
                   class="custom-control-input timing-mode-radio" {{ $timingMode === 'same' ? 'checked' : '' }}>
            <label class="custom-control-label" for="timing_mode_same">{{ __('Same timing for all working days') }}</label>
        </div>
        <div class="custom-control custom-radio custom-control-inline">
            <input type="radio" id="timing_mode_different" name="timing_mode" value="different"
                   class="custom-control-input timing-mode-radio" {{ $timingMode === 'different' ? 'checked' : '' }}>
            <label class="custom-control-label" for="timing_mode_different">{{ __('Different timing for each day') }}</label>
        </div>
    </div>
</div>

<div class="col-md-12" id="same_timing_wrap">
    <div class="card bg-light border-0 mb-3">
        <div class="card-body py-3">
            <label class="d-block mb-2">{{ __('Timing for all selected working days') }}</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="common_in" id="common_in" class="form-control time"
                           value="{{ $commonIn }}" placeholder="{{ __('In Time') }}">
                </div>
                <div class="col-md-4">
                    <input type="text" name="common_out" id="common_out" class="form-control time"
                           value="{{ $commonOut }}" placeholder="{{ __('Out Time') }}">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-md-12" id="different_timing_wrap">
    <div class="row" id="different_timing_rows">
        @foreach ($shiftDayOptions as $dayOption)
            @php
                $dayKey = $dayOption['key'];
                $dayIn = $dayTimes[$dayKey]['in'] ?? '';
                $dayOut = $dayTimes[$dayKey]['out'] ?? '';
                $isWorkingDay = in_array($dayKey, $selectedWorkingDays, true);
            @endphp
            <div class="col-md-6 different-day-row" data-day="{{ $dayKey }}" style="{{ $isWorkingDay ? '' : 'display:none;' }}">
                <label>{{ $dayOption['label'] }}</label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" name="{{ $dayKey }}_in" id="{{ $dayKey }}_in"
                               class="form-control time mb-3 day-time-input"
                               value="{{ $dayIn }}" placeholder="{{ __('In Time') }}">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="{{ $dayKey }}_out" id="{{ $dayKey }}_out"
                               class="form-control time mb-3 day-time-input"
                               value="{{ $dayOut }}" placeholder="{{ __('Out Time') }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    (function ($) {
        "use strict";

        function initShiftClockpickers($context) {
            ($context || $(document)).find('.time').each(function () {
                var $input = $(this);
                if ($input.data('clockpicker')) {
                    return;
                }
                $input.clockpicker({
                    placement: 'top',
                    align: 'left',
                    donetext: 'done',
                    twelvehour: true,
                });
            });
        }

        function selectedWorkingDays() {
            var days = [];
            $('.working-day-checkbox:checked').each(function () {
                days.push($(this).val());
            });
            return days;
        }

        function toggleDifferentDayRows() {
            var days = selectedWorkingDays();
            $('.different-day-row').each(function () {
                var day = $(this).data('day');
                $(this).toggle(days.indexOf(day) !== -1);
            });
        }

        function toggleTimingMode() {
            var mode = $('.timing-mode-radio:checked').val();
            if (mode === 'same') {
                $('#same_timing_wrap').show();
                $('#different_timing_wrap').hide();
            } else {
                $('#same_timing_wrap').hide();
                $('#different_timing_wrap').show();
                toggleDifferentDayRows();
            }
        }

        function syncSameTimingToWorkingDays() {
            if ($('.timing-mode-radio:checked').val() !== 'same') {
                return;
            }

            var commonIn = $('#common_in').val();
            var commonOut = $('#common_out').val();

            selectedWorkingDays().forEach(function (day) {
                $('#' + day + '_in').val(commonIn);
                $('#' + day + '_out').val(commonOut);
            });
        }

        function clearNonWorkingDayInputs() {
            var days = selectedWorkingDays();
            @json(array_column($shiftDayOptions, 'key')).forEach(function (day) {
                if (days.indexOf(day) === -1) {
                    $('#' + day + '_in').val('');
                    $('#' + day + '_out').val('');
                }
            });
        }

        $(document).ready(function () {
            initShiftClockpickers($('#same_timing_wrap, #different_timing_wrap'));
            toggleTimingMode();

            $('.working-day-checkbox').on('change', function () {
                if (selectedWorkingDays().length === 0) {
                    $(this).prop('checked', true);
                    alert(@json(__('Please select at least one working day.')));
                    return;
                }
                toggleDifferentDayRows();
            });

            $('.timing-mode-radio').on('change', toggleTimingMode);
            $('#common_in, #common_out').on('change blur', syncSameTimingToWorkingDays);

            $('#sample_form').on('submit', function () {
                if (selectedWorkingDays().length === 0) {
                    alert(@json(__('Please select at least one working day.')));
                    return false;
                }

                if ($('.timing-mode-radio:checked').val() === 'same') {
                    if (!$('#common_in').val() || !$('#common_out').val()) {
                        alert(@json(__('Please enter in and out time for the selected working days.')));
                        return false;
                    }
                    syncSameTimingToWorkingDays();
                } else {
                    var missingDay = null;
                    selectedWorkingDays().forEach(function (day) {
                        if (!$('#' + day + '_in').val() || !$('#' + day + '_out').val()) {
                            missingDay = day;
                        }
                    });
                    if (missingDay) {
                        alert(@json(__('Please enter in and out time for each selected working day.')));
                        return false;
                    }
                }

                clearNonWorkingDayInputs();
            });
        });
    })(jQuery);
</script>
@endpush
