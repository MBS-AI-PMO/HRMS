<nav class="side-navbar">
    <div class="side-navbar-wrapper">
        <!-- Sidebar Header    -->
        <!-- Sidebar Navigation Menus-->
        <div class="main-menu">
            <ul id="side-main-menu" class="side-menu list-unstyled">

                <li><a href="{{url('/client/dashboard')}}"> <i
                                class="dripicons-meter"></i><span>{{trans('file.Dashboard')}}</span></a>
                </li>

                <li><a href="#Project_Management" aria-expanded="false" data-toggle="collapse"> <i
                                class="dripicons-checklist"></i><span>{{__('Project Management')}}</span></a>
                    <ul id="Project_Management" class="collapse list-unstyled ">

                        <li id="projects"><a
                                    href="{{route('clientProject')}}">{{trans(('file.Projects'))}}</a>
                        </li>

                        <li id="tasks"><a
                                    href="{{route('clientTask')}}">{{trans(('file.Tasks'))}}</a>
                        </li>
                    </ul>
                </li>


                <li><a href="#invoices" aria-expanded="false" data-toggle="collapse"> <i
                                class="dripicons-ticket"></i><span>{{trans('file.Invoice')}}</span></a>
                    <ul id="invoices" class="collapse list-unstyled ">
                        <li id="invoice"><a href="{{route('clientInvoice')}}">{{trans('file.Invoice')}}</a>
                        </li>

                        <li id="paid_invoice"><a href="{{route('clientInvoicePaid')}}">{{__('Invoice Payment')}}</a>
                        </li>

                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
