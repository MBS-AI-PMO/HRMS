@php
    $office_shift = $office_shift ?? null;
    $shiftOwnerType = ($office_shift && $office_shift->client_id) ? 'client' : 'company';
    $selectedCompanyId = $office_shift->company_id ?? null;
    $selectedClientId = $office_shift->client_id ?? null;
@endphp

<div class="col-md-12">
    <div class="border rounded bg-light px-3 py-2 mb-3">
        <div class="row align-items-end">
            <div class="col-md-4 form-group mb-md-0">
                <label class="text-bold small d-block mb-1">{{ __('Belongs To') }}</label>
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="shift_owner_type" id="shift_owner_type_company"
                        value="company" {{ $shiftOwnerType === 'company' ? 'checked' : '' }}>
                    <label class="form-check-label small" for="shift_owner_type_company">{{ trans('file.Company') }}</label>
                </div>
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="shift_owner_type" id="shift_owner_type_client"
                        value="client" {{ $shiftOwnerType === 'client' ? 'checked' : '' }}>
                    <label class="form-check-label small" for="shift_owner_type_client">{{ trans('file.Client') }}</label>
                </div>
            </div>
            <div class="col-md-4 form-group mb-md-0 {{ $shiftOwnerType === 'client' ? 'd-none' : '' }}" id="shift_owner_company_wrap">
                <label>{{ trans('file.Company') }} <span class="text-danger">*</span></label>
                <select id="shift_company_id" class="form-control selectpicker"
                    data-live-search="true" data-live-search-style="contains"
                    title='{{ __('Selecting', ['key' => trans('file.Company')]) }}...'
                    @if ($shiftOwnerType === 'company') name="company_id" @else disabled @endif>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>
                            {{ $company->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 form-group mb-md-0 {{ $shiftOwnerType === 'company' ? 'd-none' : '' }}" id="shift_owner_client_wrap">
                <label>{{ trans('file.Client') }} <span class="text-danger">*</span></label>
                <select id="shift_client_id" class="form-control selectpicker"
                    data-live-search="true" data-live-search-style="contains"
                    title='{{ __('Selecting', ['key' => trans('file.Client')]) }}...'
                    @if ($shiftOwnerType === 'client') name="client_id" @else disabled @endif>
                    @foreach ($clients as $client)
                        @php
                            $clientName = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
                            $clientCompany = trim((string) ($client->company_name ?? ''));
                            $clientLabel = ($clientName !== '' && $clientCompany !== '')
                                ? $clientName . ' — ' . $clientCompany
                                : ($clientName !== '' ? $clientName : $clientCompany);
                        @endphp
                        <option value="{{ $client->id }}" @selected((int) $selectedClientId === (int) $client->id)>
                            {{ $clientLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function ($) {
        function toggleShiftOwnerType(type) {
            type = type || 'company';

            if (type === 'client') {
                $('#shift_owner_company_wrap').addClass('d-none');
                $('#shift_owner_client_wrap').removeClass('d-none');
                $('#shift_company_id').prop('disabled', true).removeAttr('name');
                $('#shift_client_id').prop('disabled', false).attr('name', 'client_id');
            } else {
                $('#shift_owner_client_wrap').addClass('d-none');
                $('#shift_owner_company_wrap').removeClass('d-none');
                $('#shift_client_id').prop('disabled', true).removeAttr('name');
                $('#shift_company_id').prop('disabled', false).attr('name', 'company_id');
            }

            $('#shift_company_id, #shift_client_id').selectpicker('refresh');
        }

        $('input[name="shift_owner_type"]').on('change', function () {
            toggleShiftOwnerType($(this).val());
        });

        toggleShiftOwnerType($('input[name="shift_owner_type"]:checked').val());
    })(jQuery);
</script>
@endpush
