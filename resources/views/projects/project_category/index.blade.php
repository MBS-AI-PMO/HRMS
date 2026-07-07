@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid"><span id="general_result"></span></div>

        <div class="container-fluid mb-3">
            @can('store-project-category')
                <button type="button" class="btn btn-info" id="create_record"><i class="fa fa-plus"></i> {{ __('Add Project Category') }}</button>
            @endcan
            @can('delete-project-category')
                <button type="button" class="btn btn-danger" id="bulk_delete"><i class="fa fa-minus-circle"></i> {{ __('Bulk delete') }}</button>
            @endcan
        </div>

        <div class="table-responsive">
            <table id="project-category-table" class="table">
                <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('Category Name') }}</th>
                    <th>{{ trans('file.Description') }}</th>
                    <th>{{ trans('file.Status') }}</th>
                    <th class="not-exported">{{ trans('file.action') }}</th>
                </tr>
                </thead>
            </table>
        </div>
    </section>

    <div id="formModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('Add Project Category') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><i class="dripicons-cross"></i></button>
                </div>
                <div class="modal-body">
                    <span id="form_result"></span>
                    <form method="post" id="sample_form" class="form-horizontal">
                        @csrf
                        <div class="form-group">
                            <label>{{ __('Category Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" id="category_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{ trans('file.Status') }}</label>
                            <select name="is_active" id="is_active" class="form-control selectpicker">
                                <option value="1" selected>{{ __('Active') }}</option>
                                <option value="0">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ trans('file.Description') }}</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="text-center">
                            <input type="hidden" name="hidden_id" id="hidden_id">
                            <input type="submit" id="action_button" class="btn btn-warning" value="{{ trans('file.Add') }}">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <h4 class="text-center">{{ __('Are you sure you want to remove this data?') }}</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" id="ok_button" class="btn btn-danger">{{ trans('file.OK') }}</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('file.Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script type="text/javascript">
(function ($) {
    "use strict";

    var table;

    $(document).ready(function () {
        $('.selectpicker').selectpicker();

        table = $('#project-category-table').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('project_categories.index') }}",
            columns: [
                { data: 'id', orderable: false, searchable: false },
                { data: 'category_name', name: 'category_name' },
                { data: 'description', name: 'description', orderable: false },
                { data: 'status', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[1, 'asc']],
            language: {
                lengthMenu: '_MENU_ {{ __('records per page') }}',
                info: '{{ trans('file.Showing') }} _START_ - _END_ (_TOTAL_)',
                search: '{{ trans('file.Search') }}',
                paginate: {
                    previous: '{{ trans('file.Previous') }}',
                    next: '{{ trans('file.Next') }}'
                }
            },
            columnDefs: [{
                orderable: false,
                targets: [0, 4],
                render: function (data) {
                    if (data == null) {
                        return '';
                    }
                    if (typeof data === 'object' && data.checkbox !== undefined) {
                        return data.checkbox;
                    }
                    return '<div class="form-check"><input type="checkbox" class="form-check-input dt-checkboxes"><label class="form-check-label">&nbsp;</label></div>';
                },
                checkboxes: {
                    selectRow: true
                }
            }]
        });

        $('#create_record').on('click', function () {
            $('#sample_form')[0].reset();
            $('#hidden_id').val('');
            $('.modal-title').text(@json(__('Add Project Category')));
            $('#action_button').val(@json(trans('file.Add')));
            $('#form_result').html('');
            $('#is_active').selectpicker('val', '1');
            $('.selectpicker').selectpicker('refresh');
            $('#formModal').modal('show');
        });

        $('#sample_form').on('submit', function (e) {
            e.preventDefault();
            var id = $('#hidden_id').val();
            var url = id ? "{{ route('project_categories.update') }}" : "{{ route('project_categories.store') }}";

            $.ajax({
                url: url,
                method: 'POST',
                data: $(this).serialize(),
                success: function (data) {
                    var html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        $.each(data.errors, function (_, value) {
                            html += '<p>' + value + '</p>';
                        });
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        $('#formModal').modal('hide');
                        table.ajax.reload(null, false);
                    }
                    $('#form_result').html(html);
                }
            });
        });

        $(document).on('click', '.edit', function () {
            var id = $(this).attr('id');
            $('#form_result').html('');
            $.get("{{ url('project-management/project_categories') }}/" + id + "/edit", function (result) {
                $('#category_name').val(result.data.category_name);
                $('#description').val(result.data.description);
                $('#is_active').selectpicker('val', result.data.is_active ? '1' : '0');
                $('#hidden_id').val(result.data.id);
                $('.modal-title').text(@json(__('Edit Project Category')));
                $('#action_button').val(@json(trans('file.Edit')));
                $('#formModal').modal('show');
            });
        });

        var deleteId;
        $(document).on('click', '.delete', function () {
            deleteId = $(this).attr('id');
            $('#confirmModal').modal('show');
        });

        $('#ok_button').on('click', function () {
            $.ajax({
                url: "{{ url('project-management/project_categories') }}/" + deleteId + "/delete",
                success: function (data) {
                    if (data.error) {
                        $('#general_result').html('<div class="alert alert-danger">' + data.error + '</div>');
                    }
                    if (data.success) {
                        $('#general_result').html('<div class="alert alert-success">' + data.success + '</div>');
                        table.ajax.reload(null, false);
                    }
                    $('#confirmModal').modal('hide');
                }
            });
        });

        $('#bulk_delete').on('click', function () {
            var ids = [];
            table.rows({ selected: true }).data().each(function (row) {
                ids.push(row.id);
            });
            if (!ids.length) {
                alert(@json(__('Please select at least one row')));
                return;
            }
            if (!confirm(@json(__('Are you sure?')))) {
                return;
            }
            $.ajax({
                url: "{{ route('mass_delete_project_categories') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    categoryIdArray: ids
                },
                success: function (data) {
                    if (data.error) {
                        $('#general_result').html('<div class="alert alert-danger">' + data.error + '</div>');
                    }
                    if (data.success) {
                        $('#general_result').html('<div class="alert alert-success">' + data.success + '</div>');
                        table.ajax.reload(null, false);
                    }
                }
            });
        });
    });
})(jQuery);
</script>
@endpush
