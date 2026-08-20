@if (Route::is([
        'horizontal-timeline',
        'ui-accordion',
        'ui-alerts',
        'ui-avatar',
        'ui-badges',
        'ui-borders',
        'ui-breadcrumb',
        'ui-buttons',
        'ui-buttons-group',
        'ui-cards',
        'ui-carousel',
        'ui-colors',
        'ui-grid',
        'ui-images',
        'ui-lightbox',
        'ui-media',
        'ui-modals',
        'ui-nav-tabs',
        'ui-notification',
        'ui-offcanvas',
        'ui-pagination',
        'ui-placeholders',
        'ui-popovers',
        'ui-progress',
        'ui-toasts',
        'ui-typography',
        'ui-video',
        'ui-dropdowns',
    ]))
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h3>{{ $title }}</h3>
        </div>
    </div>
    <!-- /Page Header -->
    
@endif

@if (Route::is([
        'chart-apex',
        'chart-c3',
        'chart-flot',
        'chart-js',
        'chart-morris',
        'chart-peity',
        'icon-feather',
        'icon-flag',
        'icon-fontawesome',
        'icon-ionic',
        'icon-material',
        'icon-pe7',
        'icon-simpleline',
        'icon-themify',
        'icon-typicon',
        'icon-weather',
        'ui-counter',
        'ui-clipboard',
        'ui-drag-drop',
        'ui-rangeslider',
        'ui-rating',
        'ui-ribbon',
        'ui-scrollbar',
        'ui-spinner',
        'ui-stickynote',
        'ui-sweetalerts',
        'ui-text-editor',
        'ui-timeline',
        'ui-tooltips',
        'form-basic-inputs',
        'form-checkbox-radios',
        'form-fileupload',
        'form-floating-labels',
        'form-grid-gutters',
        'form-horizontal',
        'form-input-groups',
        'form-mask',
        'form-select',
        'form-select2',
        'form-validation',
        'form-vertical',
        'form-wizard',
        'data-tables',
    ]))
    <!-- Page Header -->
    <div class="page-header">
        <div class="row">
            <div class="col-sm-12">
                <h3 class="page-title">{{ $title }}</h3>
            </div>
        </div>
    </div>
    <!-- /Page Header -->
@endif

@if(Route::is([
    'classes',
    'schedule-classes',
    'class-room',
    'class-routine',
    'class-section',
    'class-subject',
    'class-syllabus',
    'class-time-table',
    'class-home-work',
    'exam',
    'exam-schedule',
    'grade',
    'exam-attendance',
    'exam-results',
    'academic-reasons',
    'fees-group',
    'fees-type',
    'fees-master',
    'fees-assign',
    'collect-fees',
    'library-members',
    'library-books',
    'library-issue-book',
    'library-return',
    'sports',
    'players',
    'hostel-list',
    'hostel-rooms',
    'hostel-room-type',
    'transport-routes',
    'transport-pickup-points',
    'transport-vehicle-drivers',
    'transport-vehicle',
    'transport-assign-vehicle',
    'students',
    'teachers',
    'teacher-grid',
    'departments',
    'designation',
    'student-attendance',
    'teacher-attendance',
    'staff-attendance',
    'holidays',
    'payroll',
    'expenses',
    'expenses-category',
    'accounts-income',
    'accounts-invoices',
    'accounts-transactions',
    'attendance-report',
    'class-report',
    'student-report',
    'grade-report',
    'leave-report',
    'fees-report',
    'users',
    'roles-permission',
    'delete-account',
    'membership-plans',
    'membership-transactions',
    'pages',
    'blog-categories',
    'blog-comments',
    'blog-tags',
    'countries',
    'states',
    'cities',
    'testimonials',
    'faq'
   
]))
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">{{ $title }}</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{url('index')}}">{{ $item1 }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">{{ $item2 }} </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ $item3 }} </li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip"
                data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                <i class="ti ti-refresh"></i>
            </a>
        </div>
        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-light bg-white btn-icon me-1"
                data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print"
                data-bs-original-title="Print">
                <i class="ti ti-printer"></i>
            </button>
        </div>
        <div class="dropdown me-2 mb-2">
            <a href="javascript:void(0);"
                class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"
                data-bs-toggle="dropdown">
                <i class="ti ti-file-export me-2"></i>Export
            </a>
            <ul class="dropdown-menu  dropdown-menu-end p-3">
                <li>
                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                            class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                </li>
                <li>
                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                            class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                </li>
            </ul>
        </div>
        <div class="mb-2">
            @if(Route::is(['classes']))
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_class"><i
                    class="ti ti-square-rounded-plus-filled me-2"></i>Add
                Class</a>
                @endif
                @if(Route::is(['schedule-classes']))
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_Schedule"><i
                    class="ti ti-square-rounded-plus-filled me-2"></i>Add
                Schedule</a>
                @endif
                @if(Route::is(['class-room']))
                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
								data-bs-target="#add_class_room"><i
									class="ti ti-square-rounded-plus-filled me-2"></i>Add
								Class Room</a>
                @endif
                @if(Route::is(['class-routine']))
                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
								data-bs-target="#add_class_routine"><i
									class="ti ti-square-rounded-plus-filled me-2"></i>Add
								Class Routine</a>
                 @endif 
                 @if(Route::is(['class-section'])) 
                 <a href="#" class="btn btn-primary" data-bs-toggle="modal"
						data-bs-target="#add_class_section"><i
							class="ti ti-square-rounded-plus-filled me-2"></i>Add
						Section</a>   
                @endif 
                @if(Route::is(['class-subject']))  
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_subject"><i
                    class="ti ti-square-rounded-plus-filled me-2"></i>Add
                Subject</a>   
                @endif   
                @if(Route::is(['class-syllabus']))  
               <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_syllabus"><i
                        class="ti ti-square-rounded-plus-filled me-2"></i>Add Subject Group</a>
                @endif 
                @if(Route::is(['class-time-table']))
                <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_time_table"><i class="ti ti-square-rounded-plus me-2"></i>Add Time
								Table</a>
                 @endif 
                 @if(Route::is(['class-home-work']))  
                 <a href="#" class="btn btn-primary" data-bs-toggle="modal"
								data-bs-target="#add_home_work"><i class="ti ti-square-rounded-plus-filled me-2"></i>Add
								Home Work</a>
               @endif 
               @if(Route::is(['exam']))  
               <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_exam"><i
                class="ti ti-square-rounded-plus-filled me-2"></i>Add
            Exam</a>
            @endif 
            @if(Route::is(['exam-schedule']))  
            <a href="#" class="btn btn-primary" data-bs-toggle="modal"
								data-bs-target="#add_exam_schedule"><i
									class="ti ti-square-rounded-plus-filled me-2"></i>Add
								Exam Schedule</a>
            @endif 
            @if(Route::is(['grade'])) 
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_grade"><i
                class="ti ti-square-rounded-plus-filled me-2"></i>Add
            Grade</a>
            @endif 
            @if(Route::is(['academic-reasons'])) 
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_reason"><i
                class="ti ti-square-rounded-plus-filled me-2"></i>Add Reasons</a>
            @endif 
            @if(Route::is(['fees-group'])) 
                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#add_fees_group"><i
                    class="ti ti-square-rounded-plus me-2"></i>Add
                Fees Group</a>
             @endif  
             @if(Route::is(['fees-type'])) 
             <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_fees_Type"><i class="ti ti-square-rounded-plus-filled me-2"></i>Add
								Fees Type</a> 
             @endif  
             @if(Route::is(['fees-master'])) 
             <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_fees_master"><i class="ti ti-square-rounded-plus me-2"></i>Add
								Fees Master</a>
            @endif
            @if(Route::is(['fees-assign']))  
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
							data-bs-target="#add_fees_assign"><i class="ti ti-square-rounded-plus me-2"></i>Assign
							New</a> 
             @endif
             @if(Route::is(['library-members'])) 
             <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_library_members"><i
									class="ti ti-square-rounded-plus me-2"></i>Add member</a>
            @endif
            @if(Route::is(['library-books'])) 
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
            data-bs-target="#add_library_book"><i class="ti ti-square-rounded-plus me-2"></i>Add
            Book</a>
            @endif
            @if(Route::is(['sports']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#add_sports"><i
                class="ti ti-square-rounded-plus me-2"></i>Add Sport</a>
             @endif
             @if(Route::is(['players']))
                <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#add_players"><i class="ti ti-square-rounded-plus me-2"></i>Add
                        Player</a>
             @endif   
             @if(Route::is(['hostel-list']))
             <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#add_hostel"><i
                class="ti ti-square-rounded-plus me-2"></i>Add
            Hostel</a>  
            @endif 
            @if(Route::is(['hostel-rooms']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
							data-bs-target="#add_hostel_rooms"><i class="ti ti-square-rounded-plus me-2"></i>Add
							Hostel Room</a>  
            @endif
            @if(Route::is(['hostel-room-type']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
						data-bs-target="#add_hostel_room_type"><i class="ti ti-square-rounded-plus me-2"></i>Add
						Room Type</a> 
           @endif   
           @if(Route::is(['transport-routes']))
           <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_routes"><i class="ti ti-square-rounded-plus me-2"></i>Add Route</a>  
         @endif 
         @if(Route::is(['transport-pickup-points']))
         <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#add_pickup"><i
            class="ti ti-square-rounded-plus me-2"></i>Add Pickup Point</a>  
            @endif
            @if(Route::is(['transport-vehicle-drivers']))  
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
            data-bs-target="#add_driver"><i class="ti ti-square-rounded-plus me-2"></i>Add
            Vehicle</a> 
            @endif
            @if(Route::is(['transport-vehicle']))  
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
						data-bs-target="#add_vehicle"><i class="ti ti-square-rounded-plus me-2"></i>Add
						Vehicle</a> 
           @endif 
           @if(Route::is(['transport-assign-vehicle'])) 
           <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_assign_vehicle"><i
									class="ti ti-square-rounded-plus-filled me-2"></i>Assign New Vehicle</a> 
                                    
            @endif 
            @if(Route::is(['students'])) 
            <a href="{{url('add-student')}}" class="btn btn-primary d-flex align-items-center"><i class="ti ti-square-rounded-plus me-2"></i>Add Student</a>
            @endif
            @if(Route::is(['teachers']))
            <a href="{{url('add-teacher')}}" class="btn btn-primary d-flex align-items-center"><i class="ti ti-square-rounded-plus me-2"></i>Add Teacher</a> 
            @endif
            @if(Route::is(['teacher-grid']))
            <a href="{{url('add-teacher')}}" class="btn btn-primary d-flex align-items-center" 
			><i class="ti ti-square-rounded-plus me-2"></i>Add Teacher</a>
            @endif
            @if(Route::is(['departments']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
            data-bs-target="#add_department"><i
                class="ti ti-square-rounded-plus me-2"></i>Add Department</a>
            @endif
            @if(Route::is(['designation']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
					data-bs-target="#add_designation"><i
						class="ti ti-square-rounded-plus me-2"></i>Add Designation</a>
            @endif
            @if(Route::is(['holidays']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
            data-bs-target="#add_holiday"><i class="ti ti-square-rounded-plus me-2"></i>Add
            Holiday</a>
            @endif
            @if(Route::is(['expenses']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
			data-bs-target="#add_expenses"><i class="ti ti-square-rounded-plus me-2"></i>Add Expense</a>
            @endif
            @if(Route::is(['expenses-category']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
						data-bs-target="#add_expenses_category"><i class="ti ti-square-rounded-plus me-2"></i>Add
						Category</a>
            @endif
            @if(Route::is(['accounts-income']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
            data-bs-target="#add_income"><i class="ti ti-square-rounded-plus me-2"></i>Add
            Income</a>
            @endif
            @if(Route::is(['accounts-invoices']))
            <a href="{{url('add-invoice')}}" class="btn btn-primary d-flex align-items-center"><i class="ti ti-square-rounded-plus me-2"></i>Add
                Invoices</a>
            @endif
           
            @if(Route::is(['roles-permission']))
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
            data-bs-target="#add_role"><i class="ti ti-square-rounded-plus me-2"></i>Add Role</a>
            @endif
            @if(Route::is(['membership-plans']))
            <a href="#" data-bs-toggle="modal" data-bs-target="#add_membership"
            class="btn btn-primary d-flex align-items-center"><i
                class="ti ti-square-rounded-plus me-2"></i>Add Membership</a>
                @endif
                @if(Route::is(['pages'])) 
                <a href="#" class="btn btn-primary d-flex align-items-center"><i
                    class="ti ti-square-rounded-plus me-2"></i>Add Page</a>
                    @endif
                    @if(Route::is(['blog-categories'])) 
                    <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                            data-bs-target="#add_category"><i class="ti ti-square-rounded-plus me-2"></i>Add
                            Category</a>
                            @endif
                            @if(Route::is(['blog-comments'])) 
                            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#add_blog"><i
                                class="ti ti-square-rounded-plus me-2"></i>Add Blog</a>
                                @endif
                                @if(Route::is(['blog-tags'])) 
                                <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#add_tags"><i class="ti ti-square-rounded-plus me-2"></i>Add Tag</a>
                         @endif
                         @if(Route::is(['countries'])) 
                         <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_country"><i
                            class="ti ti-square-rounded-plus-filled me-2"></i>Add
                        Country</a>
                        @endif
                        @if(Route::is(['states'])) 
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_state"><i
                            class="ti ti-square-rounded-plus-filled me-2"></i>Add State</a>
                  @endif
                  @if(Route::is(['cities'])) 
                  <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_cities"><i
                    class="ti ti-square-rounded-plus-filled me-2"></i>Add
                Cities</a>
                @endif
                @if(Route::is(['testimonials'])) 
                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#add_testimonials"><i
                    class="ti ti-square-rounded-plus-filled me-2"></i>Add
                Testimonials</a>
                @endif
                @if(Route::is(['faq'])) 
                <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                data-bs-target="#add_faq"><i class="ti ti-square-rounded-plus me-2"></i>Add FAQ</a>
                @endif
        </div>
    </div>
</div>
<!-- /Page Header -->
@endif