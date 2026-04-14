@extends('layout.main')
@section('content')
    <section>
        <div class="container-fluid">
            <div class="card">
                <ul class="nav nav-tabs d-flex justify-content-between" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link " href="{{route('leave_type.index')}}" id="Leave_type-tab" data-toggle="tab" data-table= "leave" data-target="#Leave_type" role="tab" aria-controls="Leave_type" aria-selected="true">{{__('Leave Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="{{route('award_type.index')}}" id="Award_type-tab" data-toggle="tab" data-table= "award" data-target="#Award_type" role="tab" aria-controls="Award_type" aria-selected="false">{{__('Award Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('warning_type.index')}}" id="Warning_type-tab" data-toggle="tab" data-table= "warning" data-target="#Warning_type" role="tab" aria-controls="Warning_type" aria-selected="false">{{__('Warning Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('termination_type.index')}}" id="Termination_type-tab" data-toggle="tab" data-table= "termination" data-target="#Termination_type" role="tab" aria-controls="Termination_type" aria-selected="false">{{__('Termination Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('expense_type.index')}}" id="Expense_type-tab" data-toggle="tab" data-table= "expense" data-target="#Expense_type" role="tab" aria-controls="Expense_type" aria-selected="false">{{__('Expense Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('status_type.index')}}" id="Status_type-tab" data-toggle="tab" data-table= "status" data-target="#Status_type" role="tab" aria-controls="Status_type" aria-selected="false">{{__('Employee Status')}}</a>
                    </li>
                </ul>
                <ul class="nav nav-tabs d-flex justify-content-between" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link " href="{{route('document_type.index')}}" id="Document_type-tab" data-toggle="tab" data-table= "document" data-target="#Document_type" role="tab" aria-controls="Document_type" aria-selected="false">{{__('Document Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="#" id="Company_type-tab" data-toggle="tab" data-table="company_type" data-target="#Company_type" role="tab" aria-controls="Company_type" aria-selected="false">{{__('Company Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="#" id="Relation_type-tab" data-toggle="tab" data-table="relation_type" data-target="#Relation_type" role="tab" aria-controls="Relation_type" aria-selected="false">{{__('Relation Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="#" id="Loan_type-tab" data-toggle="tab" data-table="loan_type" data-target="#Loan_type" role="tab" aria-controls="Loan_type" aria-selected="false">{{__('Loan Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="#" id="Deduction_type-tab" data-toggle="tab" data-table="deduction_type" data-target="#Deduction_type" role="tab" aria-controls="Deduction_type" aria-selected="false">{{__('Deduction Type')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="#" id="depositCategory-tab" data-toggle="tab" data-table="deposit_category" data-target="#depositCategory" role="tab" aria-controls="depositCategory" aria-selected="false">{{__('Deposit Category')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="#" id="jobExperience-tab" data-toggle="tab" data-table="job_experience" data-target="#jobExperience" role="tab" aria-controls="jobExperience" aria-selected="false">{{__('Job Experience Type')}}</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="tab-content" id="myTabContent">

            <div class="pt-0 tab-pane fade show active" id="Leave_type" role="tab" aria-labelledby="Leave_type-tab">
              @include('settings.variables.partials.leave_type')
            </div>
            <div class="pt-0 tab-pane fade " id="Award_type" role="tab"  aria-labelledby="Award_type-tab">
               @include('settings.variables.partials.award_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Warning_type" role="tab"  aria-labelledby="Warning_type-tab">
                @include('settings.variables.partials.warning_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Termination_type" role="tab"  aria-labelledby="Termination_type-tab">
                @include('settings.variables.partials.termination_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Expense_type" role="tab"  aria-labelledby="Expense_type-tab">
                @include('settings.variables.partials.expense_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Status_type" role="tab"  aria-labelledby="Status_type-tab">
                @include('settings.variables.partials.status_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Document_type" role="tab"  aria-labelledby="Document_type-tab">
                @include('settings.variables.partials.document_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Company_type" role="tab"  aria-labelledby="Company_type-tab">
                @include('settings.variables.partials.company_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Relation_type" role="tab"  aria-labelledby="Relation_type-tab">
                @include('settings.variables.partials.relation_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Loan_type" role="tab"  aria-labelledby="Loan_type-tab">
                @include('settings.variables.partials.loan_type')
            </div>

            <div class="pt-0 tab-pane fade " id="Deduction_type" role="tab"  aria-labelledby="Deduction_type-tab">
                @include('settings.variables.partials.deduction_type')
            </div>

            <div class="pt-0 tab-pane fade " id="depositCategory" role="tab"  aria-labelledby="depositCategory-tab">
                @include('settings.variables.partials.deposit_category')
            </div>

            <div class="pt-0 tab-pane fade " id="jobExperience" role="tab"  aria-labelledby="jobExperience-tab">
                @include('settings.variables.partials.job_experience')
            </div>
        </div>
    </section>


@endsection

@push('scripts')
<script type="text/javascript">
    (function($) {
        "use strict";

        let leaveLoad = 0;
        $(document).ready(function() {
            if (leaveLoad == 0) {
                @include('settings.variables.JS_DT.leave_type_js')
                    leaveLoad = 1;
            }
        });


        $('[data-table="award"]').one('click', function (e) {
            @include('settings.variables.JS_DT.award_type_js')
        });

        $('[data-table="warning"]').one('click', function (e) {
            @include('settings.variables.JS_DT.warning_type_js')
        });

        $('[data-table="termination"]').one('click', function (e) {
            @include('settings.variables.JS_DT.termination_type_js')
        });

        $('[data-table="expense"]').one('click', function (e) {
            @include('settings.variables.JS_DT.expense_type_js')
        });

        $('[data-table="status"]').one('click', function (e) {
            @include('settings.variables.JS_DT.status_type_js')
        });

        $('[data-table="document"]').on('click', function (e) {
            @include('settings.variables.JS_DT.document_type_js')
        });

        $('[data-table="company_type"]').on('click', function (e) {
            @include('settings.variables.JS_DT.company_type_js')
        });

        $('[data-table="relation_type"]').on('click', function (e) {
            @include('settings.variables.JS_DT.relation_type_js')
        });

        $('[data-table="loan_type"]').on('click', function (e) {
            @include('settings.variables.JS_DT.loan_type_js')
        });

        $('[data-table="deduction_type"]').on('click', function (e) {
            @include('settings.variables.JS_DT.deduction_type_js')
        });

        $('[data-table="deposit_category"]').on('click', function (e) {
            @include('settings.variables.JS_DT.deposit_category_js')
        });

        $('[data-table="job_experience"]').on('click', function (e) {
            @include('settings.variables.JS_DT.job_experience_js')
        });


    })(jQuery);
</script>
@endpush
