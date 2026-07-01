@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid mb-3">
            <h4 class="mb-1">{{ __('My Centers / Locations') }}</h4>
            <p class="text-muted mb-0">
                {{ __('Manage your center locations: update address, GPS radius, and employee attendance type.') }}
            </p>
        </div>

        <div class="table-responsive">
            <table id="my-location-table" class="table">
                <thead>
                <tr>
                    <th>{{ trans('file.Location') }}</th>
                    <th>{{ __('Location Heads') }}</th>
                    <th>{{ trans('file.Client') }}</th>
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
            serverSide: false,
            ajax: {
                url: "{{ route('locations.my') }}",
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                dataSrc: 'data',
                error: function (xhr) {
                    console.error('My locations DataTable error', xhr.status, xhr.responseText);
                }
            },
            columns: [
                { data: 'location_name', name: 'location_name' },
                { data: 'location_heads', name: 'location_heads' },
                { data: 'companies', name: 'companies' },
                { data: 'active_employees_count', name: 'active_employees_count' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [],
            language: {
                lengthMenu: '_MENU_ {{ __("records per page") }}',
                info: '{{ __("Showing") }} _START_ - _END_ (_TOTAL_)',
                search: '{{ __("Search") }}',
                emptyTable: '{{ __("No center location is linked to your team yet. Ask admin to assign a work location to team members, or set you as Location Head.") }}',
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
