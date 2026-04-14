<!-- navbar-->
<header class="header">
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-holder d-flex align-items-center justify-content-between">
                <a id="toggle-btn" href="#" class="menu-btn"><i class="dripicons-menu"> </i></a>
                
                <span class="brand-big" id="site_logo_main">
                    @if($general_settings->site_logo)
						<img src="{{asset('/images/logo/'.$general_settings->site_logo)}}" width="220" height="85">
                        &nbsp; &nbsp;
                    @endif
                </span>


                <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center">
                    {{-- <div class="d-flex flex-row">
                            <div class="dropdown mr-1">
                                <button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-plus"></i> {{__('Create')}}
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    @if(auth()->user()->role_users_id == 1)
                                    <a class="dropdown-item" href="#" name="create_record" id="create_record">Add Admin</a>
                                    @endif
                                    <a class="dropdown-item" href="{{url('/staff/employees')}}#formModal">Add Employee</a>

                                    <a class="dropdown-item" href="{{url('/project-management/clients')}}#formModal">Add Client</a>
                                    <a class="dropdown-item" href="{{url('/organization/companies/')}}#formModal">Add Copmany</a>
                                    <a class="dropdown-item" href="{{url('/subscriptions/')}}#createModal">Add Subscription</a>

                                </div>
                            </div>

                    </div> --}}
                    {{-- <li class="nav-item">
                        <a class="dropdown-header-name" style="padding-right: 10px" href="{{url('/optimize')}}" data-toggle="tooltip" title="Clear all cache with refresh"><i class=" dripicons-plus"></i></a>
                    </li> --}}
                    <li class="nav-item">
                        <div class="btn-group">
                            {{-- <select name="company_id" id="company_id" required
                                            class="form-control selectpicker dynamic"
                                            data-live-search="true" data-live-search-style="contains"
                                            data-first_name="first_name" data-last_name="last_name"
                                            title='{{__('Selecting',['key'=>trans('file.Company')])}}...'>
                                            <option value="1">Test 1</option>
                                            <option value="2">Test 2</option>

                            </select> --}}

                            <button class="btn btn-primary" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Create">
                                <i class="fa fa-plus-circle"></i>
                            </button>
                            <div class="dropdown-menu">
                                @foreach ($addFrom as $item)
                                    <a class="dropdown-item" href="{{ $item['url'] }}">Add {{ $item['title'] }}</a>
                                @endforeach
                            </div>
                          </div>
                    </li>
                    <li class="nav-item">
                        <a class="dropdown-header-name" style="padding-right: 10px" href="{{url('/optimize')}}" data-toggle="tooltip" title="Clear all cache with refresh"><i class="fa fa-refresh"></i></a>
                    </li>
                    <li class="nav-item"><a id="btnFullscreen" data-toggle="tooltip"
                                            title="{{__('Full Screen')}}"><i class="dripicons-expand"></i></a></li>
                    <li class="nav-item">
                        <a rel="nofollow" id="notify-btn" href="#" class="nav-link dropdown-item" data-toggle="tooltip"
                           title="{{__('Notifications')}}">
                            <i class="dripicons-bell"></i>
                            @if(auth()->user()->unreadNotifications->count())
                                <span class="badge badge-danger">
                                    {{auth()->user()->unreadNotifications->count()}}
                                </span>
                            @endif
                        </a>
                        <ul class="right-sidebar">
                            <li class="header">
                                <span class="pull-right"><a href="{{route('clearAll')}}">{{__('Clear All')}}</a></span>
                                <span class="pull-left"><a href="{{route('seeAllNoti')}}">{{__('See All')}}</a></span>
                            </li>
                            @foreach(auth()->user()->notifications as $notification)
                                <li><a class="unread-notification"
                                       href={{$notification->data['link']}}>{{$notification->data['data']}}</a></li>
                            @endforeach
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a rel="nofollow" href="#" class="nav-link dropdown-item" data-toggle="tooltip"
                           title="{{__('Language')}}">
                            <i class="dripicons-web"></i>
                        </a>
                        <ul class="right-sidebar">
                            @foreach($languages as $lang)
                                <li>
                                    <a href="{{route('language.switch',$lang)}}">{{$lang}}</a>
                                </li>
                            @endforeach
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a rel="nofollow" href="#" class="nav-link dropdown-item">
                            @if(!empty(auth()->user()->profile_photo))
                                <img class="profile-photo sm mr-1"
                                     src="{{ asset('uploads/profile_photos/')}}/{{auth()->user()->profile_photo}}">
                            @else
                                <img class="profile-photo sm mr-1"
                                     src="{{ asset('uploads/profile_photos/avatar.jpg')}}">
                            @endif
                            <span> {{auth()->user()->username}}</span>
                        </a>
                        <ul class="right-sidebar">
                            <li>
                                <a href="{{route('profile')}}">
                                    <i class="dripicons-user"></i>
                                    {{trans('file.Profile')}}
                                </a>
                            </li>
                            @if(auth()->user()->role_users_id == 1)
                                <li id="empty_database">
                                    <a href="#">
                                        <i class="dripicons-stack"></i>
                                        {{__('Empty Database')}}
                                    </a>
                                </li>
                            @endif
                            @if(auth()->user()->role_users_id == 1)
                                <li id="export_database">
                                    <a href="{{route('export_database')}}">
                                        <i class="dripicons-cloud-download"></i>
                                        {{__('Export Database')}}
                                    </a>
                                </li>
                            @endif
                            <li>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="btn btn-link" type="submit"><i
                                                class="dripicons-exit"></i> {{trans('file.logout')}}</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @include('shared.flash_message')
</header>
