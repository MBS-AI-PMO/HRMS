@php
    $employeeOwnerType = $employee->client_id ? 'client' : 'company';
@endphp
<input type="hidden" id="employee_owner_type_hidden" value="{{ $employeeOwnerType }}">
<input type="hidden" id="employee_client_id_hidden" value="{{ $employee->client_id }}">
<input type="hidden" name="company_id_hidden" value="{{ $employee->company_id }}" />

<div class="employee-owner-panel border rounded bg-light px-2 py-2 mb-1">
    <div class="row align-items-end">
        <div class="col-md-4 form-group mb-md-0">
            <label class="text-bold small d-block mb-1">{{ __('Belongs To') }}</label>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="employee_owner_type" id="employee_owner_type_company"
                    value="company" {{ $employeeOwnerType === 'company' ? 'checked' : '' }}>
                <label class="form-check-label small" for="employee_owner_type_company">{{ trans('file.Company') }}</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="employee_owner_type" id="employee_owner_type_client"
                    value="client" {{ $employeeOwnerType === 'client' ? 'checked' : '' }}>
                <label class="form-check-label small" for="employee_owner_type_client">{{ trans('file.Client') }}</label>
            </div>
        </div>
        <div class="col-md-8 form-group mb-0 {{ $employeeOwnerType === 'client' ? 'd-none' : '' }}" id="employee_owner_company_wrap">
            <label class="text-bold small">{{ trans('file.Company') }} <span class="text-danger">*</span></label>
            <select id="company_id"
                class="form-control selectpicker employee-company-select dynamic"
                data-live-search="true" data-live-search-style="contains"
                data-shift_name="shift_name" data-dependent="department_name"
                title="{{ __('Selecting', ['key' => trans('file.Company')]) }}..."
                @if ($employeeOwnerType === 'company') name="company_id" required @else disabled @endif>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 form-group mb-0 {{ $employeeOwnerType === 'company' ? 'd-none' : '' }}" id="employee_owner_client_wrap">
            <label class="text-bold small">{{ trans('file.Client') }} <span class="text-danger">*</span></label>
            <select id="employee_client_id"
                class="form-control selectpicker"
                data-live-search="true" data-live-search-style="contains"
                title="{{ __('Selecting', ['key' => trans('file.Client')]) }}..."
                @if ($employeeOwnerType === 'company') disabled @endif
                @if ($employeeOwnerType === 'client') name="client_id" required @endif>
                @foreach ($clients as $client)
                    @php
                        $clientName = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
                        $clientCompany = trim((string) ($client->company_name ?? ''));
                        $clientLabel = ($clientName !== '' && $clientCompany !== '')
                            ? $clientName . ' — ' . $clientCompany
                            : ($clientName !== '' ? $clientName : $clientCompany);
                    @endphp
                    <option value="{{ $client->id }}" data-company-id="{{ $client->resolved_company_id }}"
                        {{ (int) $employee->client_id === (int) $client->id ? 'selected' : '' }}>
                        {{ $clientLabel }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
