@extends('layout.main')

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    #clockInMap {
        height: 420px;
        width: 100%;
        border-radius: 6px;
    }

    .clockin-map-legend {
        font-size: 12px;
    }

    .clockin-map-legend span {
        display: inline-block;
        margin-right: 14px;
    }

    .clockin-map-legend .dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 4px;
    }

    .geo-place-pending {
        font-style: italic;
    }
</style>
@endpush

@section('content')

    <section>
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-header with-border">
                    <h3 class="card-title">{{ __('Clock-in Location Report') }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        {{ __('Shows clock-in records with employee GPS at clock-in, assigned office location, nearest office location, and an interactive map.') }}
                    </p>
                    <form method="post" id="login_location_filter_form" class="form-horizontal">
                        @csrf
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ __('Date') }}</label>
                                    <input class="form-control date" placeholder="{{ __('Select Date') }}" readonly
                                        id="filter_date" name="filter_date" type="text">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ trans('file.Company') }}</label>
                                    <select name="company_id" id="company_id" class="form-control selectpicker dynamic"
                                        data-live-search="true" data-live-search-style="contains"
                                        data-first_name="first_name" data-last_name="last_name"
                                        title="{{ __('All') }}">
                                        <option value="">{{ __('All') }}</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ trans('file.Client') }}</label>
                                    <select name="client_id" id="client_id" class="form-control selectpicker"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{ __('All') }}">
                                        <option value="">{{ __('All') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ trans('file.Location') }}</label>
                                    <select name="location_id" id="location_id" class="form-control selectpicker"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{ __('All') }}">
                                        <option value="">{{ __('All') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ trans('file.Employee') }}</label>
                                    <select name="employee_id" id="employee_id" class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{ __('All') }}">
                                        <option value="">{{ __('All') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button name="submit_form" id="submit_form" type="submit" class="btn btn-primary btn-block">
                                        <i class="fa fa-check-square-o"></i> {{ trans('file.Get') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="login-location-table" class="table">
                <thead>
                    <tr>
                        <th>{{ trans('file.Employee') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ trans('file.Company') }}</th>
                        <th>{{ __('Clock-in Time') }}</th>
                        <th>{{ __('IP Address') }}</th>
                        <th>{{ __('Attendance Type') }}</th>
                        <th>{{ __('User Location') }}</th>
                        <th>{{ __('Nearest Office') }}</th>
                        <th>{{ __('User Lat') }}</th>
                        <th>{{ __('User Lng') }}</th>
                        <th>{{ __('Office Location') }}</th>
                        <th>{{ __('Distance (km)') }}</th>
                        <th>{{ __('Map') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </section>

    <div class="modal fade" id="clockInMapModal" tabindex="-1" role="dialog" aria-labelledby="clockInMapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clockInMapModalLabel">{{ __('Clock-in Location Map') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>{{ trans('file.Employee') }}:</strong> <span id="mapEmployeeName">---</span></p>
                    <p class="mb-2"><strong>{{ __('User Location') }}:</strong> <span id="mapUserPlace">---</span></p>
                    <p class="mb-2"><strong>{{ __('Assigned Office') }}:</strong> <span id="mapOfficeName">---</span></p>
                    <p class="mb-3"><strong>{{ __('Nearest Office') }}:</strong> <span id="mapNearestName">---</span></p>
                    <div class="clockin-map-legend mb-2">
                        <span><span class="dot" style="background:#2563eb;"></span>{{ __('User clock-in') }}</span>
                        <span><span class="dot" style="background:#16a34a;"></span>{{ __('Assigned office') }}</span>
                        <span><span class="dot" style="background:#ea580c;"></span>{{ __('Nearest office') }}</span>
                    </div>
                    <div id="clockInMap"></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script type="text/javascript">
    (function($) {
        "use strict";

        var clockInMapInstance = null;
        var clockInMapMarkers = [];
        var geocodeQueue = [];
        var geocodeTimer = null;
        var pendingMapData = null;

        function makePinIcon(color) {
            return L.divIcon({
                className: 'clockin-map-pin',
                html: '<span style="background:' + color + ';width:14px;height:14px;display:block;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.25);"></span>',
                iconSize: [14, 14],
                iconAnchor: [7, 7]
            });
        }

        function clearClockInMap() {
            if (clockInMapInstance) {
                clockInMapInstance.remove();
                clockInMapInstance = null;
            }
            clockInMapMarkers = [];
        }

        function renderClockInMap(data) {
            clearClockInMap();
            clockInMapInstance = L.map('clockInMap').setView([data.userLat, data.userLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(clockInMapInstance);

            var bounds = [];
            var userLabel = data.userPlace || @json(__('Resolving...'));

            var userMarker = L.marker([data.userLat, data.userLng], { icon: makePinIcon('#2563eb') })
                .addTo(clockInMapInstance)
                .bindPopup('<strong>{{ __('User clock-in') }}</strong><br>' + userLabel);
            clockInMapMarkers.push(userMarker);
            bounds.push([data.userLat, data.userLng]);

            if (!isNaN(data.officeLat) && !isNaN(data.officeLng)) {
                var officeMarker = L.marker([data.officeLat, data.officeLng], { icon: makePinIcon('#16a34a') })
                    .addTo(clockInMapInstance)
                    .bindPopup('<strong>{{ __('Assigned office') }}</strong><br>' + data.officeName);
                clockInMapMarkers.push(officeMarker);
                bounds.push([data.officeLat, data.officeLng]);
            }

            if (!isNaN(data.nearestLat) && !isNaN(data.nearestLng)
                && (data.nearestLat !== data.officeLat || data.nearestLng !== data.officeLng)) {
                var nearestMarker = L.marker([data.nearestLat, data.nearestLng], { icon: makePinIcon('#ea580c') })
                    .addTo(clockInMapInstance)
                    .bindPopup('<strong>{{ __('Nearest office') }}</strong><br>' + data.nearestName);
                clockInMapMarkers.push(nearestMarker);
                bounds.push([data.nearestLat, data.nearestLng]);
            }

            if (bounds.length > 1) {
                clockInMapInstance.fitBounds(bounds, { padding: [30, 30] });
            }

            setTimeout(function() {
                clockInMapInstance.invalidateSize();
            }, 200);

            if (!data.userPlace) {
                clientReverseGeocode(data.userLat, data.userLng, function(placeName) {
                    var label = placeName || formatCoordFallback(data.userLat, data.userLng);
                    $('#mapUserPlace').text(label);
                    userMarker.setPopupContent('<strong>{{ __('User clock-in') }}</strong><br>' + label);
                    pendingMapData.userPlace = label;
                });
            }
        }

        function openClockInMap($btn) {
            pendingMapData = {
                employee: $btn.data('employee') || '---',
                userLat: parseFloat($btn.data('user-lat')),
                userLng: parseFloat($btn.data('user-lng')),
                userPlace: $btn.data('user-place') || '',
                officeName: $btn.data('office-name') || '---',
                officeLat: parseFloat($btn.data('office-lat')),
                officeLng: parseFloat($btn.data('office-lng')),
                nearestName: $btn.data('nearest-name') || '---',
                nearestLat: parseFloat($btn.data('nearest-lat')),
                nearestLng: parseFloat($btn.data('nearest-lng')),
                nearestDistance: $btn.data('nearest-distance') || ''
            };

            $('#mapEmployeeName').text(pendingMapData.employee);
            $('#mapOfficeName').text(pendingMapData.officeName);
            $('#mapNearestName').text(
                pendingMapData.nearestName
                + (pendingMapData.nearestDistance ? ' (' + pendingMapData.nearestDistance + ' km)' : '')
            );
            $('#mapUserPlace').text(pendingMapData.userPlace || @json(__('Resolving...')));

            $('#clockInMapModal').modal('show');
        }

        function formatCoordFallback(lat, lng) {
            return parseFloat(lat).toFixed(6) + ', ' + parseFloat(lng).toFixed(6);
        }

        function cachePlaceName(lat, lng, placeName) {
            if (!placeName || placeName === '---') {
                return;
            }

            $.post("{{ route('report.reverse-geocode.store') }}", {
                _token: '{{ csrf_token() }}',
                lat: lat,
                lng: lng,
                place_name: placeName
            });
        }

        function clientReverseGeocode(lat, lng, callback) {
            fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&zoom=18&addressdetails=1', {
                headers: {
                    'Accept': 'application/json',
                    'Accept-Language': 'en'
                }
            })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('reverse geocode failed');
                    }

                    return response.json();
                })
                .then(function(data) {
                    var placeName = (data && data.display_name) ? data.display_name : formatCoordFallback(lat, lng);
                    cachePlaceName(lat, lng, placeName);
                    callback(placeName);
                })
                .catch(function() {
                    callback(formatCoordFallback(lat, lng));
                });
        }

        function resolvePlaceName(lat, lng, callback) {
            $.get("{{ route('report.reverse-geocode') }}", { lat: lat, lng: lng })
                .done(function(response) {
                    if (response.place_name) {
                        callback(response.place_name);
                        return;
                    }

                    clientReverseGeocode(lat, lng, callback);
                })
                .fail(function() {
                    clientReverseGeocode(lat, lng, callback);
                });
        }

        function queuePlaceNameResolve($cell) {
            if ($cell.data('geo-queued')) {
                return;
            }

            var lat = parseFloat($cell.attr('data-lat'));
            var lng = parseFloat($cell.attr('data-lng'));

            if (isNaN(lat) || isNaN(lng)) {
                return;
            }

            $cell.data('geo-queued', true);
            geocodeQueue.push({ lat: lat, lng: lng, $cell: $cell });

            if (!geocodeTimer) {
                geocodeTimer = setInterval(processGeocodeQueue, 1100);
                processGeocodeQueue();
            }
        }

        function processGeocodeQueue() {
            if (!geocodeQueue.length) {
                clearInterval(geocodeTimer);
                geocodeTimer = null;
                return;
            }

            var item = geocodeQueue.shift();
            resolvePlaceName(item.lat, item.lng, function(placeName) {
                item.$cell.removeClass('geo-place-pending').text(placeName || formatCoordFallback(item.lat, item.lng));
            });
        }

        $(document).ready(function() {
            $('.date').datepicker({
                format: '{{ env('Date_Format_JS') }}',
                autoclose: true,
                todayHighlight: true
            });

            var table = $('#login-location-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('report.login-locations') }}",
                    data: function(d) {
                        d.filter_date = $('#filter_date').val();
                        d.company_id = $('#company_id').val();
                        d.client_id = $('#client_id').val();
                        d.location_id = $('#location_id').val();
                        d.employee_id = $('#employee_id').val();
                    }
                },
                columns: [
                    { data: 'employee_name', name: 'employee_name' },
                    { data: 'username', name: 'username' },
                    { data: 'company', name: 'company' },
                    { data: 'clock_in_at', name: 'clock_in' },
                    { data: 'ip_address', name: 'ip_address' },
                    { data: 'attendance_type', name: 'attendance_type' },
                    {
                        data: 'user_place_name',
                        name: 'user_place_name',
                        render: function(data, type) {
                            if (type === 'display' || type === 'filter') {
                                return data;
                            }

                            return $('<div>').html(data).text();
                        }
                    },
                    { data: 'nearest_location_name', name: 'nearest_location_name' },
                    { data: 'user_latitude', name: 'user_latitude' },
                    { data: 'user_longitude', name: 'user_longitude' },
                    { data: 'location_name', name: 'location_name' },
                    { data: 'distance_km', name: 'distance_km' },
                    { data: 'map_action', name: 'map_action', orderable: false, searchable: false }
                ],
                order: [],
                language: {
                    lengthMenu: '_MENU_ {{ __('records per page') }}',
                    info: '{{ __('Showing') }} _START_ - _END_ (_TOTAL_)',
                    search: '{{ __('Search') }}',
                    paginate: {
                        previous: '<i class="dripicons-chevron-left"></i>',
                        next: '<i class="dripicons-chevron-right"></i>'
                    }
                },
                dom: '<"row"lfB>rtip',
                buttons: [
                    {
                        extend: 'pdf',
                        text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                        exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' }
                    },
                    {
                        extend: 'csv',
                        text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                        exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' }
                    },
                    {
                        extend: 'print',
                        text: '<i title="print" class="fa fa-print"></i>',
                        exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' }
                    }
                ],
                drawCallback: function() {
                    var api = this.api();
                    api.rows({ page: 'current' }).every(function() {
                        var node = this.node();
                        $(node).find('.geo-place-pending').each(function() {
                            queuePlaceNameResolve($(this));
                        });
                    });
                }
            });

            $('#login_location_filter_form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            $(document).on('click', '.btn-view-clockin-map', function() {
                openClockInMap($(this));
            });

            $('#clockInMapModal').on('shown.bs.modal', function() {
                if (pendingMapData) {
                    renderClockInMap(pendingMapData);
                }
            });

            $('#clockInMapModal').on('hidden.bs.modal', function() {
                clearClockInMap();
                pendingMapData = null;
            });

            function resetSelect($select, allLabel) {
                $select.selectpicker('destroy');
                $select.html('<option value="">' + allLabel + '</option>');
                $select.selectpicker();
            }

            function loadClients(companyId) {
                resetSelect($('#client_id'), @json(__('All')));

                if (!companyId) {
                    return $.Deferred().resolve().promise();
                }

                return $.post("{{ route('dynamic_clients') }}", {
                    value: companyId,
                    _token: '{{ csrf_token() }}'
                }).done(function(result) {
                    $('#client_id').selectpicker('destroy');
                    $('#client_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                    $('#client_id').selectpicker();
                });
            }

            function loadLocations(companyId, clientId) {
                resetSelect($('#location_id'), @json(__('All')));

                if (!companyId && !clientId) {
                    return $.Deferred().resolve().promise();
                }

                return $.post("{{ route('dynamic_locations') }}", {
                    company_id: companyId || '',
                    client_id: clientId || '',
                    _token: '{{ csrf_token() }}'
                }).done(function(result) {
                    $('#location_id').selectpicker('destroy');
                    $('#location_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                    $('#location_id').selectpicker();
                });
            }

            function loadEmployees(companyId, clientId, locationId) {
                resetSelect($('#employee_id'), @json(__('All')));

                if (!companyId) {
                    return;
                }

                $.post("{{ route('dynamic_employee') }}", {
                    value: companyId,
                    client_id: clientId || '',
                    location_id: locationId || '',
                    first_name: 'first_name',
                    last_name: 'last_name',
                    _token: '{{ csrf_token() }}'
                }).done(function(result) {
                    $('#employee_id').selectpicker('destroy');
                    $('#employee_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                    $('#employee_id').selectpicker();
                });
            }

            $('#company_id').on('changed.bs.select', function() {
                var companyId = $(this).val();
                loadClients(companyId).always(function() {
                    loadLocations(companyId, '').always(function() {
                        loadEmployees(companyId, '', '');
                    });
                });
            });

            $('#client_id').on('changed.bs.select', function() {
                var companyId = $('#company_id').val();
                var clientId = $(this).val();
                loadLocations(companyId, clientId).always(function() {
                    loadEmployees(companyId, clientId, $('#location_id').val());
                });
            });

            $('#location_id').on('changed.bs.select', function() {
                loadEmployees($('#company_id').val(), $('#client_id').val(), $(this).val());
            });
        });
    })(jQuery);
</script>
@endpush
