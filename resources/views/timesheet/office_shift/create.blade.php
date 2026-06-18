@extends('layout.main')

@section('content')

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="mb-0">{{__('Add Office Shift')}}</h3>
                            <a href="{{ route('office_shift.index') }}" class="btn btn-secondary btn-sm">
                                <i class="dripicons-arrow-thin-left"></i> {{ __('Back to List') }}
                            </a>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{__('The field labels marked with * are required input fields')}}.
                                </small>
                            </p>
                            <form method="post" id="sample_form" class="form-horizontal">

                                @csrf
                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Company')}} *</label>
                                            <select name="company_id" id="company_id" class="form-control selectpicker"
                                                    data-live-search="true" data-live-search-style="contains"
                                                    title='{{__('Selecting',['key'=>trans('file.Company')])}}...'>
                                                @foreach($companies as $company)
                                                    <option value="{{$company->id}}">{{$company->company_name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Shift')}} *</label>
                                            <input type="text" name="shift_name" id="shift_name" class="form-control" placeholder="shift name">
                                        </div>
                                    </div>

                                    @include('timesheet.office_shift.partials.timing_fields')

                                    <span id="form_result"></span>

                                    <div class="col-md-6 offset-md-3 mt-3">
                                        <div class="form-group" align="center">
                                            <input type="hidden" name="action" id="action"/>
                                            <input type="hidden" name="hidden_id" id="hidden_id"/>
                                            <input type="submit" name="action_button" id="action_button" class="btn btn-warning btn-block" value={{trans('file.Add')}} />
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


@endsection

@push('scripts')
<script>

    (function($) {
        "use strict";

        $('#sample_form').on('submit', function (event) {
            event.preventDefault();

                $.ajax({
                    url: "{{ route('office_shift.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        var html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#form_result').html(html).slideDown(300);
                            setTimeout(function () {
                                window.location.href = "{{ route('office_shift.index') }}";
                            }, 1500);
                            return;
                        }
                        $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
        });

    })(jQuery);
</script>
@endpush
