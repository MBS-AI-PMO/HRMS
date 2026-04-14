<div class="container-fluid">
    <div class="card mb-0">
        <div class="card-body">
            <h3 class="card-title">{{__('Add Company Type')}}</h3>
            <form method="post" id="company_type_form" class="form-horizontal" >
                @csrf
                <div class="input-group">
                    <input type="text" name="type_name" id="type_name"  class="form-control"
                           placeholder="{{__('Company Type')}}">

                    <input type="submit" id="company_type_submit" class="btn btn-success" value={{trans("file.Save")}}>
                </div>
            </form>
        </div>
    </div>
</div>

<span class="company_type_result"></span>
<div class="table-responsive">
    <table id="company_type-table" class="table ">
        <thead>
        <tr>
            <th>{{__('Type name')}}</th>
            <th class="not-exported">{{trans('file.action')}}</th>
        </tr>
        </thead>

    </table>
</div>


<div id="CompanyEditModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="AwardModalLabel" class="modal-title">{{trans('file.Edit')}}</h5>

                <button type="button" data-dismiss="modal" id="company_type_close" aria-label="Close" class="close"><span
                            aria-hidden="true">×</span></button>
            </div>
            <span class="company_type_result_edit"></span>

            <div class="modal-body">
                <form method="post" id="company_type_form_edit" class="form-horizontal" enctype="multipart/form-data" >
                    <input type="hidden" name="hidden_company_type_id" id="hidden_company_type_id" />

                    @csrf
                    <div class="col-md-6 form-group">
                        <label>{{__('Company Type')}} *</label>
                        <input type="text" name="type_name_edit" id="type_name_edit"  class="form-control"
                               placeholder="{{__('Type Type')}}">
                    </div>
                    <div class="col-md-6 form-group">
                        <input type="submit" name="company_type_edit_submit" id="company_type_edit_submit" class="btn btn-success" value={{trans("file.Edit")}} />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
