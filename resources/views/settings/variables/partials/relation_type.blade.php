<div class="container-fluid">
    <div class="card mb-0">
        <div class="card-body">
            <h3 class="card-title">{{__('Add Relation Type')}}</h3>
            <form method="post" id="relation_type_form" class="form-horizontal" >
                @csrf
                <div class="input-group">
                    <input type="text" name="relation_type_name" class="form-control" placeholder="{{__('Relation Type')}}">
                    <input type="submit" id="relation_type_submit" class="btn btn-success" value={{trans("file.Save")}}>
                </div>
            </form>
        </div>
    </div>
</div>

<span class="relation_type_result"></span>
<div class="table-responsive">
    <table id="relation_type-table" class="table ">
        <thead>
        <tr>
            <th>{{__('Type name')}}</th>
            <th class="not-exported">{{trans('file.action')}}</th>
        </tr>
        </thead>

    </table>
</div>


<div id="RelationTypeEditModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">{{trans('file.Edit')}}</h5>

                <button type="button" data-dismiss="modal" id="relation_type_close" aria-label="Close" class="close"><span
                            aria-hidden="true">×</span></button>
            </div>
            <span class="relation_type_result_edit"></span>

            <div class="modal-body">
                <form method="post" id="relation_type_form_edit" class="form-horizontal" enctype="multipart/form-data" >

                    @csrf
                    <div class="col-md-6 form-group">
                        <label>{{__('Relation Type')}} *</label>
                        <input type="text" name="relation_type_name_edit" id="relation_type_name_edit"  class="form-control"
                               placeholder="{{__('Type')}}">
                    </div>
                    <div class="col-md-4 form-group">
                        <input type="hidden" name="hidden_relation_type_id" id="hidden_relation_type_id" />
                        <input type="submit" name="relation_type_edit_submit" id="relation_type_edit_submit" class="btn btn-success" value={{trans("file.Edit")}} />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
