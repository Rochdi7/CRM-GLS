<!-- Favicon -->
<link rel="shortcut icon" type="image/x-icon" href="{{ URL::asset('build/img/favicon.png') }}">

@if (!Route::is(['layout-rtl']))
<!-- Bootstrap CSS -->
<link rel="stylesheet" href="{{ url('build/css/bootstrap.min.css') }}">
@endif
@if (Route::is(['layout-rtl']))
<!-- Bootstrap CSS -->
<link rel="stylesheet" href="{{ url('build/css/bootstrap.rtl.min.css')}}">
@endif
@if (Route::is([
        'form-fileupload',
        'data-tables',
        'todo',
        'notes',
        'file-manager',
        'students',
        'students-active',
        'students-inactive',
        'student-details',
        'student-leaves',
        'student-fees',
        'parents',
        'guardians',
        'teachers',
        'student-promotion',
        'teachers-active',
        'teachers-inactive',
        'teacher-salary',
        'teacher-details',
        'teacher-library',
        'teacher-leaves',
        'routine-teachers',
        'student-time-table',
        'student-leaves',
        'student-fees',
        'student-result',
        'student-library',
        'classes',
        'schedule-classes',
        'class-routine',
        'class-room',
        'class-section',
        'class-subject',
        'class-home-work',
        'exam',
        'exam-schedule',
        'grade',
        'exam-attendance',
        'language',
        'countries',
        'states',
        'cities',
        'testimonials',
        'faq',
        'class-time-table',
        'class-syllabus',
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
        'hostel-list',
        'hostel-rooms',
        'hostel-room-type',
        'transport-routes',
        'transport-pickup-points',
        'transport-vehicle-drivers',
        'transport-vehicle',
        'transport-assign-vehicle',
        'staffs',
        'departments',
        'designation',
        'student-attendance',
        'teacher-attendance',
        'staff-attendance',
        'list-leaves',
        'approve-request',
        'holidays',
        'payroll',
        'expenses',
        'expenses-category',
        'accounts-income',
        'accounts-invoices',
        'accounts-transactions',
        'attendance-report',
        'student-attendance-type',
        'daily-attendance',
        'student-day-wise',
        'teacher-day-wise',
        'teacher-report',
        'staff-day-wise',
        'staff-report',
        'class-report',
        'student-report',
        'grade-report',
        'leave-report',
        'fees-report',
        'blog-categories',
        'blog-comments',
        'blog-tags',
        'membership-transactions',
        'users',
        'roles-permission',
        'delete-account',
        'permission',
        'players',
        'staff-details',
        'staff-leaves',
        'staff-payroll',
        'staffs-attendance',
        'video-call',
        'pages',
        'contact-messages',
        'fees-report',
        'teacher-dashboard',
         'parent-dashboard',
         'chat',
         'call-history'
         
    ]))
    <!-- Datatable CSS -->
    <link rel="stylesheet" href="{{ url('build/css/dataTables.bootstrap5.min.css') }}">
@endif
@if (Route::is(['chart-c3']))
 <!-- ChartC3 CSS -->
 <link rel="stylesheet" href="{{url('build/plugins/c3-chart/c3.min.css')}}">
 @endif
@if (
    !Route::is([
        'form-fileupload',
        'todo',
        'notes',
        'students',
        'students-active',
        'students-inactive',
        'student-details',
        'student-leaves',
        'add-student',
        'edit-student',
        'student-promotion',
        'add-teacher',
        'edit-teacher',
        'class-time-table',
    ]))

    @if (Route::is([
            'chart-apex',
            'chart-c3',
            'chart-flot',
            'chart-js',
            'chart-morris',
            'chart-peity',
            'ui-nav-tabs',
            'form-fileupload',
            'data-tables',
            'call',
            'todo',
            'notes',
            'file-manager',
            'appointments-calendar',
            'call-history',
            'edit-invoice',
            'video-call',
            'chat'
        ]))
        <!-- animation CSS -->
        <link rel="stylesheet" href="{{ url('build/css/animate.css') }}">
    @endif


    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="{{ url('build/css/feather.css') }}">
@endif



@if (Route::is(['call','video-call','index','/','layout-default','layout-mini','layout-box','layout-dark','layout-rtl']))
    <!-- Owl Carousel CSS -->
    @if (Route::is(['call','todo','video-call','file-manager','index','/','layout-default','layout-mini','layout-box','layout-dark','layout-rtl']))
        <link rel="stylesheet" href="{{ url('build/plugins/owlcarousel/owl.carousel.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ url('build/plugins/owlcarousel/owl.theme.default.min.css') }}">
@endif

@if (Route::is(['notes', 'file-manager', 'student-time-table', 'routine-teachers', 'class-time-table','todo','teacher-dashboard','student-dashboard','parent-dashboard']))
    <link rel="stylesheet" href="{{ url('build/css/owl.carousel.min.css') }}">
@endif

@if (Route::is(['file-manager']))
    <link rel="stylesheet" href="{{ url('/build/css/plyr.css') }}">
@endif

@if (Route::is(['ui-clipboard', 'ui-drag-drop']))
    <!-- Dragula CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/dragula/css/dragula.min.css') }}">
@endif

@if (Route::is(['ui-scrollbar']))
    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/simplebar/simplebar.min.css') }}">
@endif

<!-- Tabler Icon CSS -->
<link rel="stylesheet" href="{{ url('build/plugins/tabler-icons/tabler-icons.css') }}">

@if (Route::is(['ui-lightbox']))
    <!-- animation CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/lightbox/glightbox.min.css') }}">
@endif

@if (Route::is(['ui-scrollbar']))
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/scrollbar/scroll.min.css') }}">
@endif

@if (Route::is(['ui-stickynote']))
    <!-- Sticky CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/stickynote/sticky.css') }}">
@endif

@if (Route::is(['chat', 'file-manager', 'notes', 'todo', 'email-templates']))
    <!-- Summernote CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/summernote/summernote-bs4.min.css') }}">
@endif
@if (Route::is(['blog','blog-categories','blog-comments','blog-tags','add-invoice','edit-invoice','ticket-grid','invoice']))
<!-- Summernote CSS -->
<link rel="stylesheet" href="{{ url('build/plugins/summernote/summernote-lite.min.css')}}">
@endif
<!-- Daterangepikcer CSS -->
<link rel="stylesheet" href="{{ url('build/plugins/daterangepicker/daterangepicker.css') }}">

<!-- Fontawesome CSS -->
<link rel="stylesheet" href="{{ url('build/plugins/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ url('build/plugins/fontawesome/css/all.min.css') }}">

@if (Route::is(['icon-flag']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/icons/flags/flags.css') }}">
@endif

@if (Route::is([
        'icon-feather',
        'students',
        'students-active',
        'students-inactive',
        'student-details',
        'add-student',
        'edit-student',
        'student-promotion',
        'add-teacher',
        'student-leaves',
        'edit-teacher',
        'class-time-table',
        'class-report',
        'blog-categories',
        'blog-comments',
        'blog-tags',
        'membership-plans',
        'membership-addons',
        'membership-transactions',
        'users',
        'roles-permission',
        'delete-account',
        'add-invoice',
        'add-staff',
        'edit-invoice',
        'edit-staff',
        'permission',
        'players',
        'staff-details',
        'staff-leaves',
        'staff-payroll',
        'staffs-attendance',
        'routine-teachers',
        'pages',
        'contact-messages',
        'index',
        '/',
        'layout-default',
        'teacher-dashboard',
        'student-dashboard',
        'parent-dashboard',
        'layout-mini',
        'layout-box',
        'layout-dark',
        'invoice',
        'layout-rtl'
    
    ]))
    <!-- Feather CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/icons/feather/feather.css') }}">
@endif

@if (Route::is(['icon-ionic']))
    <!-- Ionic CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/icons/ionic/ionicons.css') }}">
@endif

@if (Route::is(['icon-material']))
    <!-- Material CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/material/materialdesignicons.css') }}">
@endif

@if (Route::is(['icon-pe7']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/icons/pe7/pe-icon-7.css') }}">
@endif

@if (Route::is(['icon-simpleline']))
    <!-- Material CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/simpleline/simple-line-icons.css') }}">
@endif

@if (Route::is(['icon-themify']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/icons/themify/themify.css') }}">
@endif

@if (Route::is(['icon-typicon']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/icons/typicons/typicons.css') }}">
@endif

@if (!Route::is(['students-active', 'students-inactive', 'student-details']))
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/select2/css/select2.min.css') }}">
@endif

@if (Route::is(['form-wizard']))
    <!-- Wizard CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/twitter-bootstrap-wizard/form-wizard.css') }}">
@endif

@if (Route::is(['icon-weather']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/icons/weather/weathericons.css') }}">
@endif

@if (Route::is(['call', 'call-history', 'video-call', 'chat','call-history','video-call']))
    <!-- Boxicons CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/boxicons/css/boxicons.min.css') }}">
@endif

@if (Route::is([
        'chat',
        'add-student',
        'edit-student',
        'add-teacher',
        'student-leaves',
        'edit-teacher',
        'teacher-details',
        'teacher-leaves',
        'student-details',
        'student-time-table',
        'student-fees',
        'student-result',
        'student-library',
        'exam',
        'testimonials',
        'tickets',
        'fees-master',
        'collect-fees',
        'transport-vehicle',
        'notice-board',
        'events',
        'attendance-report',
        'teacher-report',
        'staff-report',
        'student-report',
        'grade-report',
        'leave-report',
        'fees-report',
        'blog-categories',
        'blog-comments',
        'blog-tags',
        'membership-plans',
        'membership-addons',
        'membership-transactions',
        'users',
        'roles-permission',
        'delete-account',
        'add-invoice',
        'add-staff',
        'appointments-calendar',
        'edit-invoice',
        'edit-staff',
        'permission',
        'players',
        'staff-leaves',
        'staffs-attendance',
        'students',
        'student-attendance',
        'contact-messages',
        'class-routine',
        'index',
        '/',
        'layout-default',
        'teacher-dashboard',
        'student-dashboard',
        'parent-dashboard',
        'layout-mini',
        'layout-box',
        'layout-dark',
        'invoice',
        'layout-rtl'
    ]))
    <!-- Datetimepicker CSS -->
    <link rel="stylesheet" href="{{ url('build/css/bootstrap-datetimepicker.min.css') }}">
@endif


@if (Route::is(['add-student', 'edit-student', 'add-teacher', 'edit-teacher', 'localization','add-invoice','add-staff','appointments-calendar','edit-invoice','edit-staff','invoice']))
    <!-- Bootstrap Tagsinput CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css') }}">
@endif

@if (Route::is(['chat']))
    <!-- Mobile CSS-->
    <link rel="stylesheet" href="{{ url('build/plugins/intltelinput/css/intlTelInput.css') }}">
    <link rel="stylesheet" href="{{ url('build/plugins/intltelinput/css/demo.css') }}">
@endif

@if (Route::is(['chat','video-call']))
    <!-- Fancybox -->
    <link rel="stylesheet" href="{{ url('build/plugins/fancybox/jquery.fancybox.min.css') }}">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="{{ url('build/plugins/swiper/swiper.min.css') }}">
@endif

<!-- Main CSS -->
<link rel="stylesheet" href="{{ url('build/css/style.css') }}">
