@extends('layout.main') @section('content')

    @if(session()->has('message'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
    @endif
    @if(session()->has('not_permitted'))
        <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
    @endif
    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{__('Mail Setting')}}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                            <span id="mail_test_result"></span>
                            <form method="POST" id="mail_settings_form" action="{{route('setting.mailStore')}}" >
                                @csrf

                                <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>{{__('Mail Host')}} *</strong></label>
                                        <input type="text" name="mail_host" class="form-control" value="{{env('MAIL_HOST')}}" required />
                                    </div>
                                    <div class="form-group">
                                        <label><strong>{{__('Mail Address')}} *</strong></label>
                                        <input type="text" name="mail_address" class="form-control" value="{{env('MAIL_FROM_ADDRESS')}}" required />
                                    </div>
                                    <div class="form-group">
                                        <label><strong>{{__('Mail From Name')}} *</strong></label>
                                        <input type="text" name="mail_name" class="form-control" value="{{env('MAIL_FROM_NAME')}}" required />
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>{{__('Mail Port')}} *</strong></label>
                                        <input type="text" name="port" class="form-control" value="{{env('MAIL_PORT')}}" required />
                                    </div>
                                    <div class="form-group">
                                        <label><strong>{{trans('file.Password')}} *</strong></label>
                                        <input type="password" name="password" class="form-control" value="" autocomplete="new-password" />
                                        <small class="text-muted">{{ __('Leave empty when testing if password is already saved in settings.') }}</small>
                                    </div>
                                    <div class="form-group">
                                        <label><strong>{{trans('file.Encryption')}} *</strong></label>
                                        <input type="text" name="encryption" class="form-control" value="{{env('MAIL_ENCRYPTION')}}" required />
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>{{ __('Test email recipient') }} *</strong></label>
                                        <input type="email" id="test_email" name="test_email" class="form-control"
                                            value="{{ auth()->user()->email }}" required />
                                        <small class="text-muted">{{ __('Uses the SMTP values above. Save settings after a successful test.') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-group mb-3">
                                        <button type="button" id="send_test_mail_btn" class="btn btn-outline-primary">
                                            {{ __('Send Test Email') }}
                                        </button>
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
<script type="text/javascript">
    (function($) {
        "use strict";

        $("ul#setting").siblings('a').attr('aria-expanded','true');
        $("ul#setting").addClass("show");
        $("ul#setting #mail-setting-menu").addClass("active");

        $('.selectpicker').selectpicker({
            style: 'btn-link',
        });

        $('#send_test_mail_btn').on('click', function() {
            var $btn = $(this);
            var form = $('#mail_settings_form');

            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            $btn.prop('disabled', true).text(@json(__('Sending...')));

            $.ajax({
                type: 'POST',
                url: @json(route('setting.mailTest')),
                data: form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    var html = '<div class="alert alert-success alert-dismissible text-center">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button>' +
                        (response.success || @json(__('Test email sent.'))) +
                        '</div>';
                    $('#mail_test_result').html(html);
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.error)
                        ? xhr.responseJSON.error
                        : @json(__('Could not send test email. Check storage/logs/laravel.log for details.'));
                    var html = '<div class="alert alert-danger alert-dismissible text-center">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button>' +
                        msg +
                        '</div>';
                    $('#mail_test_result').html(html);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(@json(__('Send Test Email')));
                }
            });
        });
    })(jQuery);
</script>
@endpush
