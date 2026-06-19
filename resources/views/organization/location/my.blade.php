@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid mb-3">
            <h4 class="mb-1">{{ __('My Locations') }}</h4>
            <p class="text-muted mb-0">
                {{ __('Manage your center locations: update address, GPS radius, assign employees, and office shifts.') }}
            </p>
        </div>

        <div class="table-responsive">
            <table id="my-location-table" class="table">
                <thead>
                <tr>
                    <th>{{ trans('file.Location') }}</th>
                    <th>{{ __('Location Heads') }}</th>
                    <th>{{ trans('file.Company') }}</th>
                    <th>{{ __('Active Employees') }}</th>
                    <th class="not-exported">{{ trans('file.action') }}</th>
                </tr>
                </thead>
            </table>
        </div>
    </section>

    @include('organization.location.partials.manage_modals')

@endsection

@push('scripts')
<script type="text/javascript">
(function ($) {
    "use strict";

    $(document).ready(function () {
        $('#my-location-table').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('locations.my') }}",
            columns: [
                { data: 'location_name', name: 'location_name' },
                { data: 'location_heads', name: 'location_heads', orderable: false },
                { data: 'companies', name: 'companies', orderable: false },
                { data: 'active_employees_count', name: 'active_employees_count', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [],
            language: {
                lengthMenu: '_MENU_ {{ __("records per page") }}',
                info: '{{ __("Showing") }} _START_ - _END_ (_TOTAL_)',
                search: '{{ __("Search") }}',
                emptyTable: '{{ __("You are not assigned as a location head yet.") }}',
                paginate: {
                    previous: '<i class="dripicons-chevron-left"></i>',
                    next: '<i class="dripicons-chevron-right"></i>'
                }
            }
        });
    });
})(jQuery);
</script>
@include('organization.location.partials.manage_scripts', ['tableSelector' => '#my-location-table'])
@endpush
