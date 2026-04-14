<div class="container-fluid">
    <div class="card mb-0">
        <div class="card-body">
            <h3 class="card-title">{{__('Add Deduction Type')}}</h3>
            <form method="post" id="deduction_type_form" class="form-horizontal" >
                @csrf
                <div class="input-group">
                    <input type="text" name="deduction_type_name" class="form-control" placeholder="{{__('Deduction Type')}}">
                    <input type="submit" id="deduction_type_submit" class="btn btn-success" value={{trans("file.Save")}}>
                </div>
            </form>
        </div>
    </div>
</div>

<span class="deduction_type_result"></span>
<div class="table-responsive">
    <table id="deduction_type-table" class="table ">
        <thead>
        <tr>
            <th>{{__('Type name')}}</th>
            <th class="not-exported">{{trans('file.Action')}}</th>
        </tr>
        </thead>

    </table>
</div>


<div id="DeductionTypeEditModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">{{trans('file.Edit')}}</h5>

                <button type="button" data-dismiss="modal" id="deduction_type_close" aria-label="Close" class="close"><span
                            aria-hidden="true">×</span>
                </button>
            </div>
            <span class="deduction_type_result_edit"></span>

            <div class="modal-body">
                <form method="post" id="deduction_type_form_edit" class="form-horizontal" enctype="multipart/form-data" >

                    @csrf
                    <div class="col-md-6 form-group">
                        <label>{{__('Deduction Type')}} *</label>
                        <input type="text" name="deduction_type_name_edit" id="deduction_type_name_edit"  class="form-control"
                               placeholder="{{__('Type')}}">
                    </div>
                    <div class="col-md-4 form-group">
                        <input type="hidden" name="hidden_deduction_type_id" id="hidden_deduction_type_id" />
                        <input type="submit" name="deduction_type_edit_submit" id="deduction_type_edit_submit" class="btn btn-success" value={{trans("file.Edit")}} />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
