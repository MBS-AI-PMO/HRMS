<script>
    (function() {
        var PRECISE_TARGET_ACCURACY_METERS = 25;
        var PRECISE_MAX_WAIT_MS = 25000;
        var PRECISE_MAX_SAMPLES = 10;
        var PRECISE_MIN_SAMPLES = 2;

        function attendanceClockDistanceMeters(lat1, lon1, lat2, lon2) {
            var R = 6371000;
            var dLat = (lat2 - lat1) * Math.PI / 180;
            var dLon = (lon2 - lon1) * Math.PI / 180;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
                * Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        }

        function formatCoordinate(value) {
            return Number(value).toFixed(7);
        }

        function ensureHiddenInput(form, name) {
            var input = form.querySelector('[name="' + name + '"]');

            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                form.appendChild(input);
            }

            return input;
        }

        function submitAttendanceClockForm(form) {
            if (!form) {
                return;
            }

            form.submit();
        }

        function showAttendanceLocationError(message) {
            Swal.fire({
                icon: 'error',
                title: @json(__('Location Error')),
                text: message
            });
        }

        function showPreciseLocationLoading() {
            Swal.fire({
                title: @json(__('Getting precise location')),
                html: @json(__('Please wait while we capture your GPS position. Stay outdoors or near a window for best accuracy.')),
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });
        }

        function closePreciseLocationLoading() {
            if (Swal.isVisible()) {
                Swal.close();
            }
        }

        function geolocationErrorMessage(error) {
            var message = @json(__('Location access is required to clock in or out. Please allow location access and try again.'));

            if (error && error.code === 1) {
                message = @json(__('Location permission was denied. Please allow location access in your browser and try again.'));
            } else if (error && error.code === 2) {
                message = @json(__('We were unable to detect your current location. Please check your device settings and try again.'));
            } else if (error && error.code === 3) {
                message = @json(__('The location request took too long to complete. Please try again.'));
            }

            return message;
        }

        function validateLocationGeofence(form, userLat, userLng) {
            var officeLat = parseFloat(form.dataset.officeLat || '');
            var officeLng = parseFloat(form.dataset.officeLng || '');
            var maxRadius = parseFloat(form.dataset.maxRadius || '');

            if (isNaN(officeLat) || isNaN(officeLng)) {
                showAttendanceLocationError(@json(__('The office location is not configured yet. Please contact the administrator.')));

                return false;
            }

            if (isNaN(maxRadius) || maxRadius <= 0) {
                showAttendanceLocationError(@json(__('The allowed office radius is not configured correctly. Please contact the administrator.')));

                return false;
            }

            var distance = attendanceClockDistanceMeters(officeLat, officeLng, userLat, userLng);

            if (distance > maxRadius) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('Outside Office Area')),
                    text: @json(__('You are currently outside the allowed office area. Please move closer to the office and try again.')),
                    confirmButtonText: 'OK'
                });

                return false;
            }

            return true;
        }

        function capturePrecisePosition(done, fail) {
            if (!navigator.geolocation) {
                fail({ code: 0 });

                return;
            }

            var bestPosition = null;
            var sampleCount = 0;
            var finished = false;
            var watchId = null;

            showPreciseLocationLoading();

            function cleanup() {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
            }

            function finish(position) {
                if (finished) {
                    return;
                }

                finished = true;
                cleanup();
                closePreciseLocationLoading();
                done(position);
            }

            function failOnce(error) {
                if (finished) {
                    return;
                }

                finished = true;
                cleanup();
                closePreciseLocationLoading();

                if (bestPosition) {
                    done(bestPosition);

                    return;
                }

                fail(error || { code: 3 });
            }

            var deadline = setTimeout(function() {
                if (bestPosition) {
                    finish(bestPosition);
                } else {
                    failOnce({ code: 3 });
                }
            }, PRECISE_MAX_WAIT_MS);

            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    sampleCount++;

                    var accuracy = position.coords.accuracy;

                    if (!bestPosition || accuracy < bestPosition.coords.accuracy) {
                        bestPosition = position;
                    }

                    var accurateEnough = accuracy <= PRECISE_TARGET_ACCURACY_METERS;
                    var enoughSamples = sampleCount >= PRECISE_MAX_SAMPLES;
                    var stableEnough = sampleCount >= PRECISE_MIN_SAMPLES && accuracy <= 40;

                    if (accurateEnough || enoughSamples || stableEnough) {
                        clearTimeout(deadline);
                        finish(bestPosition);
                    }
                },
                function(error) {
                    clearTimeout(deadline);
                    failOnce(error);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: PRECISE_MAX_WAIT_MS
                }
            );

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    sampleCount++;

                    if (!bestPosition || position.coords.accuracy < bestPosition.coords.accuracy) {
                        bestPosition = position;
                    }

                    if (position.coords.accuracy <= PRECISE_TARGET_ACCURACY_METERS && !finished) {
                        clearTimeout(deadline);
                        finish(bestPosition);
                    }
                },
                function() {
                    // watchPosition remains the primary source
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: PRECISE_MAX_WAIT_MS
                }
            );
        }

        function applyPositionToForm(form, position) {
            var latInput = ensureHiddenInput(form, 'latitude');
            var lngInput = ensureHiddenInput(form, 'longitude');
            var accuracyInput = ensureHiddenInput(form, 'location_accuracy');
            var capturedAtInput = ensureHiddenInput(form, 'location_captured_at');

            latInput.value = formatCoordinate(position.coords.latitude);
            lngInput.value = formatCoordinate(position.coords.longitude);
            accuracyInput.value = position.coords.accuracy != null
                ? Number(position.coords.accuracy).toFixed(2)
                : '';
            capturedAtInput.value = position.timestamp
                ? new Date(position.timestamp).toISOString()
                : new Date().toISOString();
        }

        window.handleAttendanceClockSubmit = function() {
            var form = document.getElementById(@json($clockFormId ?? 'set_clocking'));

            if (!form) {
                return;
            }

            var attendanceType = form.dataset.attendanceType || 'general';
            var requireGeofence = attendanceType === 'location_based';

            capturePrecisePosition(
                function(position) {
                    applyPositionToForm(form, position);

                    var userLat = parseFloat(position.coords.latitude);
                    var userLng = parseFloat(position.coords.longitude);

                    if (requireGeofence && !validateLocationGeofence(form, userLat, userLng)) {
                        return;
                    }

                    submitAttendanceClockForm(form);
                },
                function(error) {
                    showAttendanceLocationError(geolocationErrorMessage(error));
                }
            );
        };
    })();
</script>
