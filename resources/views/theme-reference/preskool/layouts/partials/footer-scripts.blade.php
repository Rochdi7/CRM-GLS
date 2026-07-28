<!-- jQuery -->
<script src="{{ URL::asset('build/js/jquery-3.7.1.min.js') }}"></script>

<!-- Bootstrap Core JS -->
<script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>

@if (!Route::is(['call']))

    @if (Route::is([
            'data-tables',
            'students',
            'students-active',
            'students-inactive',
            'student-details',
            'student-leaves',
            'student-fees',
            'parents',
            'guardians',
            'student-promotion',
            'teachers',
            'teachers-active',
            'teachers-inactive',
            'teacher-salary',
            'teacher-details',
            'teacher-library',
            'teacher-leaves',
            'routine-teachers',
            'student-time-table',
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
            'blog-categories',
            'blog-comments',
            'blog-tags',
            'membership-transactions',
            'users',
            'roles-permission',
            'delete-account',
            'call-history',
            'permission',
            'players',
            'staff-details',
            'staff-leaves',
            'staff-payroll',
            'staffs-attendance',
            'todo',
            'pages',
            'contact-messages',
            'fees-report',
            'teacher-dashboard',
            'parent-dashboard',
            'chat',
            'file-manager',
        ]))
        <!-- Datatable JS -->
        <script src="{{ URL::asset('build/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ URL::asset('build/js/dataTables.bootstrap5.min.js') }}"></script>
    @endif

@endif
@if (Route::is(['appointments-calendar']))
    <!-- Fullcalendar JS -->
    <script src="{{ URL::asset('build/plugins/fullcalendar/index.global.min.js') }}"></script>
@endif
@if (Route::is(['todo', 'routine-teachers', 'teacher-dashboard', 'student-dashboard', 'parent-dashboard']))
    <!-- Owl Carousel -->
    <script src="{{ URL::asset('build/js/owl.carousel.min.js') }}"></script>
@endif
@if (Route::is(['student-promotion']))
    <!-- Toastr JS -->
    <script src="{{ URL::asset('build/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/toastr/toastr.js') }}"></script>
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
        'leave-report',
        'fees-report',
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
        'todo',
        'students',
        'routine-teachers',
        'student-attendance',
        'notes',
        'accounts-income',
        'invoice',
    ]))
    <!-- Datetimepicker JS -->
    <script src="{{ URL::asset('build/js/moment.js') }}"></script>
    <script src="{{ URL::asset('build/js/bootstrap-datetimepicker.min.js') }}"></script>
@endif

@if (Route::is([
        'add-student',
        'edit-student',
        'add-teacher',
        'edit-teacher',
        'localization',
        'add-invoice',
        'add-staff',
        'appointments-calendar',
        'edit-invoice',
        'edit-staff',
        'invoice',
    ]))
    <!-- Bootstrap Tagsinput JS -->
    <script src="{{ URL::asset('build/plugins/bootstrap-tagsinput/bootstrap-tagsinput.js') }}"></script>
@endif

@if (Route::is(['ui-clipboard']))
    <!-- Clipboard JS -->
    <script src="{{ URL::asset('build/plugins/clipboard/clipboard.min.js') }}"></script>
@endif

@if (Route::is([
        'call',
        'todo',
        'notes',
        'file-manager',
        'student-time-table',
        'routine-teachers',
        'class-time-table',
        'video-call',
        'index',
        '/',
        'layout-default',
        'layout-mini',
        'layout-box',
        'layout-dark',
        'layout-rtl',
    ]))
    <!-- Owl JS -->
    <script src="{{ URL::asset('build/plugins/owlcarousel/owl.carousel.min.js') }}"></script>
@endif

@if (Route::is(['file-manager']))
    <script src="{{ URL::asset('build/js/plyr-js.js') }}"></script>
@endif

@if (Route::is(['ui-drag-drop']))
    <!-- Dragula JS -->
    <script src="{{ URL::asset('build/plugins/dragula/js/dragula.min.js') }}"></script>
    <script src="{{ URL::asset('build//js/dragula.js') }}"></script>
@endif

@if (Route::is(['ui-scrollbar']))
	<!-- Simplebar JS -->
	<script src="{{ URL::asset('build/plugins/simplebar/simplebar.min.js') }}"></script>
@endif

@if (Route::is(['index', 'layout-rtl', 'layout-default', 'layout-mini', 'layout-box', '/', 'layout-dark']))
    <script src="{{ URL::asset('build/plugins/countup/jquery.counterup.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/countup/jquery.waypoints.min.js') }}"></script>
@endif

<!-- Daterangepikcer JS -->
<script src="{{ URL::asset('build/js/moment.js') }}"></script>
<script src="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ URL::asset('build/js/form-validation.js') }}"></script>


@if (Route::is(['ui-stickynote']))
    <!-- Stickynote JS -->
    <script src="{{ URL::asset('build/js/jquery-ui.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/stickynote/sticky.js') }}"></script>
@endif

@if (Route::is([
        'chart-apex',
        'calendar',
        'index',
        '/',
        'layout-default',
        'teacher-dashboard',
        'student-dashboard',
        'parent-dashboard',
        'layout-mini',
        'layout-box',
        'layout-dark',
        'layout-rtl',
    ]))
    <!-- Chart JS -->
    <script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/apexchart/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-c3']))
    <!-- Chart JS -->
    <script src="{{ URL::asset('build/plugins/c3-chart/d3.v5.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/c3-chart/c3.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/c3-chart/chart-data.j') }}s"></script>
@endif

@if (Route::is(['chart-js']))
    <!-- Chart JS -->
    <script src="{{ URL::asset('build/plugins/chartjs/chart.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/chartjs/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-morris']))
    <!-- Chart JS -->
    <script src="{{ URL::asset('build/plugins/morris/raphael-min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/morris/morris.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/morris/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-peity']))
    <!-- Chart JS -->
    <script src="{{ URL::asset('build/plugins/peity/jquery.peity.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/peity/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-flot']))
    <!-- Chart JS -->
    <script src="{{ URL::asset('build/plugins/flot/jquery.flot.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/flot/jquery.flot.fillbetween.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/flot/jquery.flot.pie.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/flot/chart-data.js') }}"></script>
@endif

<!-- Feather Icon JS -->
<script src="{{ URL::asset('build/js/feather.min.js') }}"></script>

<!-- Slimscroll JS -->
<script src="{{ URL::asset('build/js/jquery.slimscroll.min.js') }}"></script>

@if (Route::is(['ui-sweetalerts', 'chat', 'call', 'call-history']))
    <!-- Sweetalert 2 -->
    <script src="{{ URL::asset('build/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/sweetalerts.js') }}"></script>
@endif

@if (Route::is(['chat', 'file-manager', 'notes', 'todo', 'email-templates']))
    <!-- Summernote JS -->
    <script src="{{ URL::asset('build/plugins/summernote/summernote-bs4.min.js') }}"></script>
@endif
@if (Route::is([
        'blog',
        'blog-categories',
        'blog-comments',
        'blog-tags',
        'add-invoice',
        'edit-invoice',
        'ticket-grid',
        'invoice',
    ]))
    <!-- Summernote JS -->
    <script src="{{ URL::asset('build/plugins/summernote/summernote-lite.min.js') }}"></script>
@endif


@if (Route::is(['events', 'calendar']))
    <!-- Calendar JS -->
    <script src="{{ URL::asset('build/plugins/fullcalendar/calendar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/fullcalendar/calendar-data.js') }}"></script>
@endif

@if (Route::is(['ui-lightbox']))
    <!-- Alertify JS -->
    <script src="{{ URL::asset('build/plugins/lightbox/glightbox.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/lightbox/lightbox.js') }}"></script>
@endif

@if (Route::is(['form-wizard']))
    <!-- Wizard JS -->
    <script src="{{ URL::asset('build/plugins/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/twitter-bootstrap-wizard/prettify.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/twitter-bootstrap-wizard/form-wizard.js') }}"></script>
@endif

@if (!Route::is(['data-tables']))
    <!-- Select2 JS -->
    <script src="{{ URL::asset('build/plugins/select2/js/select2.min.js') }}"></script>
@endif

@if (Route::is(['form-mask']))
    <!-- Mask JS -->
    <script src="{{ URL::asset('build/js/jquery.maskedinput.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/mask.js') }}"></script>
@endif

@if (Route::is(['form-fileupload']))
    <!-- Fileupload JS -->
    <script src="{{ URL::asset('build/plugins/fileupload/fileupload.min.js') }}"></script>
@endif

@if (Route::is(['chat']))
    <!-- Mobile Input -->
    <script src="{{ URL::asset('/build/plugins/intltelinput/js/intlTelInput.js') }}"></script>
@endif

@if (
    !Route::is([
        'student-details',
        'add-student',
        'edit-student',
        'add-teacher',
        'student-leaves',
        'edit-teacher',
        'index',
        '/',
        'layout-default',
    ]))
    <!-- Slimscroll JS -->
    <script src="{{ URL::asset('build/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
@endif

@if (Route::is(['chat', 'video-call']))
    <!-- Swiper JS -->
    <script src="{{ URL::asset('build/plugins/swiper/swiper.min.js') }}"></script>

    <!-- FancyBox JS -->
    <script src="{{ URL::asset('build/plugins/fancybox/jquery.fancybox.min.js') }}"></script>
@endif
@if (Route::is([
        'blog',
        'blog-categories',
        'blog-comments',
        'blog-tags',
        'membership-plans',
        'membership-addons',
        'staff-leaves',
        'staffs-attendance',
        'contact-messages',
        'class-routine',
        'teacher-salary',
        'index',
        '/',
        'layout-default',
        'layout-rtl',
        'teacher-dashboard',
        'student-dashboard',
        'parent-dashboard',
        'layout-mini',
        'layout-box',
        'layout-dark',
    ]))
    <!-- Daterangepikcer JS -->
    <script src="{{ URL::asset('build/js/moment.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ URL::asset('build/js/bootstrap-datetimepicker.min.js') }}"></script>
@endif

@if (Route::is([
        'call',
        'todo',
        'notes',
        'file-manager',
        'tickets',
        'ticket-details',
        'ticket-grid',
        'events',
        'appointments-calendar',
        'video-call',
        'routine-teachers',
        'staff-details',
        'staff-leaves',
        'staff-payroll',
        'staffs-attendance',
        'student-details',
        'student-fees',
        'student-leaves',
        'student-library',
        'student-result',
        'student-time-table',
        'teacher-details',
        'teacher-leaves',
        'teacher-library',
        'teacher-salary',
    ]))
    <!-- Sticky Sidebar JS -->
    <script src="{{ URL::asset('build/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
@endif

@if (
    !Route::is([
        'lock-screen',
        'login',
        'login-2',
        'login-3',
        'register',
        'register-2',
        'register-3',
        'reset-password',
        'reset-password-2',
        'reset-password-3',
       'two-step-verification',
       'two-step-verification-2',
       'two-step-verification-3',
       'under-maintenance',
       '404-error',
       '500-error',
       'coming-soon',
       'email-verification',
       'email-verification-2',
       'email-verification-3',
       'forgot-password',
       'forgot-password-2',
       'forgot-password-3',
       'layout-box',
        'layout-mini',
        'layout-rtl',
        'layout-dark',
        'reset-password-success',
        'reset-password-success-2',
        'reset-password-success-3'
    ]))
    <!-- Custom JS -->
    <script src="{{ URL::asset('build/js/theme-script.js') }}"></script>
@endif
<script src="{{ URL::asset('build/js/script.js') }}"></script>
