
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li>
                    <a href="javascript:void(0);" class="d-flex align-items-center border bg-white rounded p-2 mb-4">
                        <img src="build/img/icons/global-img.svg" class="avatar avatar-md img-fluid rounded" alt="Profile">
                        <span class="text-dark ms-2 fw-normal">Global International</span>
                    </a>
                </li>
            </ul>
            <ul>
                <li>
                    <h6 class="submenu-hdr"><span>Main</span></h6>
                    <ul>
                        <li class="submenu">
                           
                            <a href="javascript:void(0);"
                                class="{{ Request::is('/','index', 'teacher-dashboard', 'student-dashboard', 'parent-dashboard','activities') ? 'subdrop active' : '' }}"><i
                                    class="ti ti-layout-dashboard"></i><span>Dashboard</span><span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('index','/') ? 'active' : '' }}"
                                        href="{{ url('index') }}">Admin Dashboard</a></li>
                                <li><a class="{{ Request::is('teacher-dashboard') ? 'active' : '' }}"
                                        href="{{ url('teacher-dashboard') }}">Teacher Dashboard</a></li>
                                <li><a class="{{ Request::is('student-dashboard','activities') ? 'active' : '' }}"
                                        href="{{ url('student-dashboard') }}">Student Dashboard</a></li>
                                <li><a class="{{ Request::is('parent-dashboard') ? 'active' : '' }}"
                                        href="{{ url('parent-dashboard') }}">Parent Dashboard</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                           
                            <a href="javascript:void(0);"
                            class="{{ Request::is('chat', 'call', 'calendar', 'email', 'todo', 'notes', 'file-manager','video-call') ? 'subdrop active' : '' }}"><i
                                class="ti ti-layout-list"></i><span>Application</span><span
                                class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('chat') ? 'active' : '' }}"
                                        href="{{ url('chat') }}">Chat</a></li>
                                <li><a class="{{ Request::is('call','video-call') ? 'active' : '' }}"
                                        href="{{ url('call') }}">Call</a></li>
                                <li><a class="{{ Request::is('calendar') ? 'active' : '' }}"
                                        href="{{ url('calendar') }}">Calendar</a></li>
                                <li><a class="{{ Request::is('email') ? 'active' : '' }}"
                                        href="{{ url('email') }}">Email</a></li>
                                <li><a class="{{ Request::is('todo') ? 'active' : '' }}" href="{{ url('todo') }}">To
                                        Do</a></li>
                                <li><a class="{{ Request::is('notes') ? 'active' : '' }}"
                                        href="{{ url('notes') }}">Notes</a></li>
                                <li><a class="{{ Request::is('file-manager') ? 'active' : '' }}"
                                        href="{{ url('file-manager') }}">File Manager</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Layout</span></h6>
                    <ul>
                        <li class="{{ Request::is('layout-default') ? 'active' : '' }}"><a  href="{{url('layout-default')}}"><i class="ti ti-layout-sidebar"></i><span>Default </span></a></li>
                        <li class="{{ Request::is('layout-mini') ? 'active' : '' }}"><a href="{{url('layout-mini')}}"><i class="ti ti-layout-align-left"></i><span>Mini</span></a></li>
                        <li class="{{ Request::is('layout-rtl') ? 'active' : '' }}"><a href="{{url('layout-rtl')}}"><i class="ti ti-text-direction-rtl"></i><span>RTL</span></a></li>
                        <li class="{{ Request::is('layout-box') ? 'active' : '' }}"><a href="{{url('layout-box')}}"><i class="ti ti-layout-distribute-vertical"></i><span>Box</span></a></li>
                        <li  class="{{ Request::is('layout-dark') ? 'active' : '' }}" ><a href="{{url('layout-dark')}}"><i class="ti ti-moon"></i><span>Dark</span></a></li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Peoples</span></h6>
                    <ul>
                        <li class="submenu">
                           
                            <a href="javascript:void(0);"
                            class="{{ Request::is(
                                'students',
                                'students-active',
                                'students-inactive',
                                'student-promotion',
                                'add-student',
                                'edit-student',
                                'student-details',
                                'student-grid',
                                'student-time-table',
                                'student-leaves',
                                'student-fees',
                                'student-result',
                                'student-library',
                            )
                                ? 'subdrop active'
                                : '' }}"><i
                                class="ti ti-school"></i><span>Students</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is(
                                    'students',
                                    'students-active',
                                    'students-inactive',
                                    'add-student',
                                    'edit-student',
                                    
                                    'student-grid',
                                    'student-time-table',
                                    'student-leaves',
                                    'student-fees',
                                    'student-result',
                                    'student-library',
                                )
                                    ? 'active'
                                    : '' }}"
                                        href="{{ url('student-grid') }}">All Students</a></li>

                                <li><a class="{{ Request::is('students') ? 'active' : '' }}" href="{{url('students')}}">Student List</a></li>
                                <li><a class="{{ Request::is('student-details') ? 'active' : '' }}" href="{{url('student-details')}}">Student Details</a></li>
                                <li><a class="{{ Request::is('student-promotion') ? 'active' : '' }}" href="{{url('student-promotion')}}">Student Promotion</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('parent-grid', 'parents') ? 'subdrop active' : '' }}"><i class="ti ti-user-bolt"></i><span>Parents</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li ><a href="{{url('parent-grid')}}" class="{{ Request::is('parent-grid') ? 'active' : '' }}" >All Parents</a></li>
                                <li><a href="{{url('parents')}}" class="{{ Request::is('parents') ? 'active' : '' }}">Parent List</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('guardian-grid', 'guardians') ? 'subdrop active' : '' }}"><i class="ti ti-user-shield"></i><span>Guardians</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{url('guardian-grid')}}" class="{{ Request::is('guardian-grid') ? 'active' : '' }}" >All Guardians</a></li>
                                <li><a href="{{url('guardians')}}" class="{{ Request::is('guardians') ? 'active' : '' }}">Guardian List</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('teachers','teacher-grid', 'add-teacher', 'edit-teacher', 'teacher-details', 'teacher-leaves', 'teacher-salary', 'teacher-library', 'routine-teachers') ? 'subdrop active' : '' }}"><i class="ti ti-users"></i><span>Teachers</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{url('teacher-grid')}}"  class="{{ Request::is('teacher-grid') ? 'active' : '' }}">All Teachers</a></li>
                                <li><a href="{{url('teachers')}}"  class="{{ Request::is('teachers','add-teacher', 'edit-teacher','teacher-leaves', 'teacher-salary', 'teacher-library') ? 'active' : '' }}">Teacher List</a></li>
                                <li><a href="{{url('teacher-details')}}"  class="{{ Request::is('teacher-details') ? 'active' : '' }}">Teacher Details</a></li>
                                <li><a href="{{url('routine-teachers')}}"  class="{{ Request::is('routine-teachers') ? 'active' : '' }}">Routine</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Academic</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('classes','schedule-classes') ? 'subdrop active' : '' }}"><i
                                class="ti ti-school-bell"></i><span>Classes</span><span
                                class="menu-arrow"></span></a>
                                   <ul>
                                <li><a href="{{url('classes')}}"  class="{{ Request::is('classes') ? 'active' : '' }}">All Classes</a></li>
                                <li><a href="{{url('schedule-classes')}}"  class="{{ Request::is('schedule-classes') ? 'active' : '' }}">Schedule</a></li>
                            </ul>
                        </li>
                        <li  class="{{ Request::is('class-room') ? 'active' : '' }}"><a href="{{url('class-room')}}"><i class="ti ti-building"></i><span>Class
                            Room</span></a>
                </li>
                <li  class="{{ Request::is('class-routine') ? 'active' : '' }}"><a href="{{url('class-routine')}}"><i class="ti ti-bell-school"></i><span>Class
                            Routine</span></a></li>
                <li  class="{{ Request::is('class-section') ? 'active' : '' }}"><a href="{{url('class-section')}}"><i
                            class="ti ti-square-rotated-forbid-2"></i><span>Section</span></a></li>
                <li  class="{{ Request::is('class-subject') ? 'active' : '' }}"><a href="{{url('class-subject')}}"><i class="ti ti-book"></i><span>Subject</span></a></li>
                <li  class="{{ Request::is('class-syllabus') ? 'active' : '' }}"><a href="{{url('class-syllabus')}}"><i
                            class="ti ti-book-upload"></i><span>Syllabus</span></a></li>
                <li  class="{{ Request::is('class-time-table') ? 'active' : '' }}"><a href="{{url('class-time-table')}}"><i class="ti ti-table"></i><span>Time
                            Table</span></a></li>
                <li class="{{ Request::is('class-home-work') ? 'active' : '' }}"><a href="{{url('class-home-work')}}"><i class="ti ti-license"></i><span>Home
                            Work</span></a></li>
                            <li class="submenu">
                                <a href="javascript:void(0);" class="{{ Request::is('exam','exam-schedule','grade','exam-attendance','exam-results') ? 'subdrop active' : '' }}"><i
                                        class="ti ti-hexagonal-prism-plus"></i><span>Examinations</span><span
                                        class="menu-arrow"></span></a>
                                <ul>
                                    <li><a href="{{url('exam')}}"  class="{{ Request::is('exam') ? 'active' : '' }}">Exam</a></li>
                                    <li><a href="{{url('exam-schedule')}}" class="{{ Request::is('exam-schedule') ? 'active' : '' }}">Exam Schedule</a></li>
                                    <li><a href="{{url('grade')}}" class="{{ Request::is('grade') ? 'active' : '' }}">Grade</a></li>
                                    <li><a href="{{url('exam-attendance')}}" class="{{ Request::is('exam-attendance') ? 'active' : '' }}">Exam Attendance</a></li>
                                    <li><a href="{{url('exam-results')}}"  class="{{ Request::is('exam-results') ? 'active' : '' }}">Exam Results</a></li>
                                </ul>
                            </li>
                            <li  class="{{ Request::is('academic-reasons') ? 'active' : '' }}"><a href="{{url('academic-reasons')}}" ><i
                                        class="ti ti-lifebuoy"></i><span>Reasons</span></a></li>


                 </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Management</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('fees-group', 'fees-type', 'fees-master', 'fees-assign', 'collect-fees') ? 'subdrop active' : '' }}"><i class="ti ti-report-money"></i><span>Fees Collection</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('fees-group') ? 'active' : '' }}"
                                    href="{{ url('fees-group') }}">Fees Group</a></li>
                            <li><a class="{{ Request::is('fees-type') ? 'active' : '' }}"
                                    href="{{ url('fees-type') }}">Fees Type</a></li>
                            <li><a class="{{ Request::is('fees-master') ? 'active' : '' }}"
                                    href="{{ url('fees-master') }}">Fees Master</a></li>
                            <li><a class="{{ Request::is('fees-assign') ? 'active' : '' }}"
                                    href="{{ url('fees-assign') }}">Fees Assign</a></li>
                            <li><a class="{{ Request::is('collect-fees') ? 'active' : '' }}"
                                    href="{{ url('collect-fees') }}">Collect Fees</a></li>

                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('library-members', 'library-books', 'library-issue-book', 'library-return') ? 'subdrop active' : '' }}" ><i class="ti ti-notebook"></i><span>Library</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('library-members') ? 'active' : '' }}"
                                    href="{{ url('library-members') }}">Library Members</a></li>
                            <li><a class="{{ Request::is('library-books') ? 'active' : '' }}"
                                    href="{{ url('library-books') }}">Books</a></li>
                            <li><a class="{{ Request::is('library-issue-book') ? 'active' : '' }}"
                                    href="{{ url('library-issue-book') }}">Issue Book</a></li>
                            <li><a class="{{ Request::is('library-return') ? 'active' : '' }}"
                                    href="{{ url('library-return') }}">Return</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('sports') ? 'active' : '' }}"><a href="{{ url('sports') }}"><i
                            class="ti ti-run"></i><span>Sports</span></a></li>
                 <li class="{{ Request::is('players') ? 'active' : '' }}"><a href="{{url('players')}}"><i
                                class="ti ti-play-football"></i><span>Players</span></a></li>

                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('hostel-list', 'hostel-rooms', 'hostel-room-type') ? 'subdrop active' : '' }}"><i class="ti ti-building-fortress"></i><span>Hostel</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('hostel-list') ? 'active' : '' }}"
                                    href="{{ url('hostel-list') }}">Hostel List</a></li>
                            <li><a class="{{ Request::is('hostel-rooms') ? 'active' : '' }}"
                                    href="{{ url('hostel-rooms') }}">Hostel Rooms</a></li>
                            <li><a class="{{ Request::is('hostel-room-type') ? 'active' : '' }}"
                                    href="{{ url('hostel-room-type') }}">Room Type</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('transport-routes', 'transport-pickup-points', 'transport-vehicle-drivers', 'transport-vehicle', 'transport-assign-vehicle') ? 'subdrop active' : '' }}" ><i class="ti ti-bus"></i><span>Transport</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('transport-routes') ? 'active' : '' }}"
                                    href="{{ url('transport-routes') }}">Routes</a></li>
                            <li><a class="{{ Request::is('transport-pickup-points') ? 'active' : '' }}"
                                    href="{{ url('transport-pickup-points') }}">Pickup Points</a></li>
                            <li><a class="{{ Request::is('transport-vehicle-drivers') ? 'active' : '' }}"
                                    href="{{ url('transport-vehicle-drivers') }}">Vehicle Drivers</a></li>
                            <li><a class="{{ Request::is('transport-vehicle') ? 'active' : '' }}"
                                    href="{{ url('transport-vehicle') }}">Vehicle</a></li>
                            <li><a class="{{ Request::is('transport-assign-vehicle') ? 'active' : '' }}"
                                    href="{{ url('transport-assign-vehicle') }}">Assign Vehicle</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>HRM</span></h6>
                    <ul>
                        <li class="{{ Request::is('staffs','add-staff','edit-staff') ? 'active' : '' }}"><a href="{{ url('staffs') }}"><i
                            class="ti ti-users-group"></i><span>Staffs</span></a>
                </li>
                <li class="{{ Request::is('departments') ? 'active' : '' }}"><a
                        href="{{ url('departments') }}"><i
                            class="ti ti-layout-distribute-horizontal"></i><span>Departments</span></a></li>
                <li class="{{ Request::is('designation') ? 'active' : '' }}"><a
                        href="{{ url('designation') }}"><i
                            class="ti ti-user-exclamation"></i><span>Designation</span></a></li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('student-attendance', 'teacher-attendance', 'staff-attendance') ? 'subdrop active' : '' }}" ><i class="ti ti-calendar-share"></i><span>Attendance</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('student-attendance') ? 'active' : '' }}"
                                    href="{{ url('student-attendance') }}">Student Attendance</a></li>
                            <li><a class="{{ Request::is('teacher-attendance') ? 'active' : '' }}"
                                    href="{{ url('teacher-attendance') }}">Teacher Attendance</a></li>
                            <li><a class="{{ Request::is('staff-attendance') ? 'active' : '' }}"
                                    href="{{ url('staff-attendance') }}">Staff Attendance</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('list-leaves', 'approve-request') ? 'subdrop active' : '' }}" ><i class="ti ti-calendar-stats"></i><span>Leaves</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('list-leaves') ? 'active' : '' }}"
                                    href="{{ url('list-leaves') }}">List of leaves</a></li>
                            <li><a class="{{ Request::is('approve-request') ? 'active' : '' }}"
                                    href="{{ url('approve-request') }}">Approve Request</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('holidays') ? 'active' : '' }}"><a
                            href="{{ url('holidays') }}"><i
                                class="ti ti-briefcase"></i><span>Holidays</span></a>
                    </li>
                    <li class="{{ Request::is('payroll') ? 'active' : '' }}"><a href="{{ url('payroll') }}"><i
                                class="ti ti-moneybag"></i><span>Payroll</span></a>
                    </li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Finance & Accounts</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('expenses', 'expenses-category','invoice','accounts-income', 'accounts-invoices', 'accounts-transactions','add-invoice','edit-invoice') ? 'subdrop active' : '' }}">
                                <i class="ti ti-swipe"></i><span>Accounts</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('expenses') ? 'active' : '' }}"
                                    href="{{ url('expenses') }}">Expenses</a></li>
                            <li><a class="{{ Request::is('expenses-category') ? 'active' : '' }}"
                                    href="{{ url('expenses-category') }}">Expense Category</a></li>
                            <li><a class="{{ Request::is('accounts-income') ? 'active' : '' }}"
                                    href="{{ url('accounts-income') }}">Income</a></li>
                            <li><a class="{{ Request::is('accounts-invoices','add-invoice','edit-invoice') ? 'active' : '' }}"
                                    href="{{ url('accounts-invoices') }}">Invoices</a></li>
                            <li><a class="{{ Request::is('invoice') ? 'active' : '' }}" href="{{url('invoice')}}">Invoice View</a></li>
                            <li><a class="{{ Request::is('accounts-transactions') ? 'active' : '' }}"
                                    href="{{ url('accounts-transactions') }}">Transactions</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                 
                <li>
                    <h6 class="submenu-hdr"><span>Announcements</span></h6>
                    <ul>
                        <li class="{{ Request::is('notice-board') ? 'active' : '' }}"><a
                            href="{{ url('notice-board') }}"><i class="ti ti-clipboard-data"></i><span>Notice
                                Board</span></a></li>
                    <li class="{{ Request::is('events') ? 'active' : '' }}"><a href="{{ url('events') }}"><i
                                class="ti ti-calendar-question"></i><span>Events</span></a></li>
                    </ul>
                    
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Reports</span></h6>
                    <ul>
                        <li class="{{ Request::is('attendance-report','student-attendance-type','daily-attendance','student-day-wise','teacher-day-wise','teacher-report','staff-day-wise') ? 'active' : '' }}"><a href="{{ url('attendance-report') }}"><i
                            class="ti ti-calendar-due"></i><span>Attendance
                            Report</span></a></li>
                        <li class="{{ Request::is('class-report') ? 'active' : '' }}"><a href="{{ url('class-report') }}"><i class="ti ti-graph"></i><span>Class
                            Report</span></a></li>
                <li class="{{ Request::is('student-report') ? 'active' : '' }}"><a href="{{ url('student-report') }}"><i
                            class="ti ti-chart-infographic"></i><span>Student
                            Report</span></a></li>
                <li class="{{ Request::is('grade-report') ? 'active' : '' }}"><a href="{{ url('grade-report') }}"><i class="ti ti-calendar-x"></i><span>Grade
                            Report</span></a>
                </li>
                <li class="{{ Request::is('leave-report') ? 'active' : '' }}"><a href="{{ url('leave-report') }}"><i class="ti ti-line"></i><span>Leave
                            Report</span></a></li>
                <li class="{{ Request::is('fees-report') ? 'active' : '' }}"><a href="{{ url('fees-report') }}"><i class="ti ti-mask"></i><span>Fees
                            Report</span></a></li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>User Management</span></h6>
                    <ul>
                        <li class="{{ Request::is('users') ? 'active' : '' }}"><a href="{{ url('users') }}"><i class="ti ti-users-minus"></i><span>Users</span></a>
                        </li>
                        <li class="{{ Request::is('roles-permission') ? 'active' : '' }}"><a href="{{ url('roles-permission') }}"><i class="ti ti-shield-plus"></i><span>Roles &
                                    Permissions</span></a></li>
                        <li class="{{ Request::is('delete-account') ? 'active' : '' }}"><a href="{{ url('delete-account') }}"><i class="ti ti-user-question"></i><span>Delete
                                    Account
                                    Request</span></a></li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Membership</span></h6>
                    <ul>
                        <li class="{{ Request::is('membership-plans') ? 'active' : '' }}"><a href="{{ url('membership-plans') }}"><i class="ti ti-user-plus"></i><span>Membership
                            Plans</span></a></li>
                <li class="{{ Request::is('membership-addons') ? 'active' : '' }}"><a href="{{ url('membership-addons') }}"><i class="ti ti-cone-plus"></i><span>Membership
                            Addons</span></a></li>
                <li class="{{ Request::is('membership-transactions') ? 'active' : '' }}"><a href="{{ url('membership-transactions') }}"><i
                            class="ti ti-file-power"></i><span>Transactions</span></a></li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Content</span></h6>
                    <ul>
                        <li class="{{ Request::is('pages') ? 'active' : '' }}"><a href="{{url('pages')}}"><i class="ti ti-page-break"></i><span>Pages</span></a></li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('blog', 'blog-categories', 'blog-comments','blog-tags') ? 'subdrop active' : '' }}">
                                <i class="ti ti-brand-blogger"></i><span>Blog</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('blog') ? 'active' : '' }}" href="{{ url('blog') }}">All Blogs</a></li>
                                <li><a class="{{ Request::is('blog-categories') ? 'active' : '' }}" href="{{ url('blog-categories') }}">Categories</a></li>
                                <li><a class="{{ Request::is('blog-comments') ? 'active' : '' }}" href="{{ url('blog-comments') }}">Comments</a></li>
                                <li><a class="{{ Request::is('blog-tags') ? 'active' : '' }}" href="{{ url('blog-tags') }}">Tags</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('countries', 'states', 'cities') ? 'subdrop active' : '' }}">
                                <i class="ti ti-map-pin-search"></i><span>Location</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('countries') ? 'active' : '' }}" class="{{ Request::is('countries') ? 'active' : '' }}"
                                    href="{{ url('countries') }}">Countries</a></li>
                            <li><a class="{{ Request::is('states') ? 'active' : '' }}" class="{{ Request::is('states') ? 'active' : '' }}"
                                    href="{{ url('states') }}">States</a></li>
                            <li ><a class="{{ Request::is('cities') ? 'active' : '' }}"
                                    href="{{ url('cities') }}">Cities</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('testimonials') ? 'active' : '' }}"><a
                            href="{{ url('testimonials') }}"><i
                                class="ti ti-quote"></i><span>Testimonials</span></a></li>
                    <li class="{{ Request::is('faq') ? 'active' : '' }}"><a href="{{ url('faq') }}"><i
                                class="ti ti-question-mark"></i><span>FAQ</span></a></li>

                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Support</span></h6>
                    <ul>
                        <li class="{{ Request::is('contact-messages') ? 'active' : '' }}"><a
                            href="{{ url('contact-messages') }}"><i class="ti ti-message"></i><span>Contact
                                Messages</span></a></li>
                    <li class="{{ Request::is('tickets','ticket-grid') ? 'active' : '' }}"><a href="{{ url('tickets') }}"><i
                                class="ti ti-ticket"></i><span>Tickets</span></a></li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Pages</span></h6>
                    <ul>
                        <li class="{{ Request::is('profile') ? 'active' : '' }}"><a href="{{url('profile')}}"><i class="ti ti-user"></i><span>Profile</span></a></li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-lock-open"></i><span>Authentication</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li class="submenu submenu-two"><a href="javascript:void(0);" class="">Login<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li class="{{ Request::is('login') ? 'active' : '' }}"><a href="{{url('login')}}">Cover</a></li>
                                        <li class="{{ Request::is('login-2') ? 'active' : '' }}"><a href="{{url('login-2')}}">Illustration</a></li>
                                        <li class="{{ Request::is('login-3') ? 'active' : '' }}"><a href="{{url('login-3')}}">Basic</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two"><a href="javascript:void(0);" class="">Register<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li class="{{ Request::is('register') ? 'active' : '' }}"><a href="{{url('register')}}">Cover</a></li>
                                        <li class="{{ Request::is('register-2') ? 'active' : '' }}"><a href="{{url('register-2')}}">Illustration</a></li>
                                        <li class="{{ Request::is('register-3') ? 'active' : '' }}"><a href="{{url('register-3')}}">Basic</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two"><a href="javascript:void(0);">Forgot Password<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li class="{{ Request::is('forgot-password') ? 'active' : '' }}"><a href="{{url('forgot-password')}}">Cover</a></li>
                                        <li class="{{ Request::is('forgot-password-2') ? 'active' : '' }}"><a href="{{url('forgot-password-2')}}">Illustration</a></li>
                                        <li class="{{ Request::is('forgot-password-3') ? 'active' : '' }}"><a href="{{url('forgot-password-3')}}">Basic</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two"><a href="javascript:void(0);">Reset Password<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li class="{{ Request::is('reset-password') ? 'active' : '' }}"><a href="{{url('reset-password')}}">Cover</a></li>
                                        <li class="{{ Request::is('reset-password-2') ? 'active' : '' }}"><a href="{{url('reset-password-2')}}">Illustration</a></li>
                                        <li class="{{ Request::is('reset-password-3') ? 'active' : '' }}"><a href="{{url('reset-password-3')}}">Basic</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two"><a href="javascript:void(0);">Email Verification<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li class="{{ Request::is('email-verification') ? 'active' : '' }}"><a href="{{url('email-verification')}}">Cover</a></li>
                                        <li class="{{ Request::is('email-verification-2') ? 'active' : '' }}"><a href="{{url('email-verification-2')}}">Illustration</a></li>
                                        <li class="{{ Request::is('email-verification-3') ? 'active' : '' }}"><a href="{{url('email-verification-3')}}">Basic</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two"><a href="javascript:void(0);">2 Step Verification<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li class="{{ Request::is('two-step-verification') ? 'active' : '' }}"><a href="{{url('two-step-verification')}}">Cover</a></li>
                                        <li class="{{ Request::is('two-step-verification-2') ? 'active' : '' }}"><a href="{{url('two-step-verification-2')}}">Illustration</a></li>
                                        <li class="{{ Request::is('two-step-verification-3') ? 'active' : '' }}"><a href="{{url('two-step-verification-3')}}">Basic</a></li>
                                    </ul>
                                </li>
                                <li class="{{ Request::is('lock-screen') ? 'active' : '' }}"><a href="{{url('lock-screen')}}">Lock Screen</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-error-404"></i><span>Error Pages</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li class="{{ Request::is('404-error') ? 'active' : '' }}"><a href="{{url('404-error')}}">404 Error</a></li>
                                <li class="{{ Request::is('500-error') ? 'active' : '' }}"><a href="{{url('500-error')}}">500 Error</a></li>

                            </ul>
                        </li>
                        <li class="{{ Request::is('blank-page') ? 'active' : '' }}"><a href="{{url('blank-page')}}"><i class="ti ti-brand-nuxt"></i><span>Blank Page</span></a></li>
                        <li class="{{ Request::is('coming-soon') ? 'active' : '' }}"><a href="{{url('coming-soon')}}"><i class="ti ti-file"></i><span>Coming Soon</span></a></li>
                        <li class="{{ Request::is('under-maintenance') ? 'active' : '' }}"><a href="{{url('under-maintenance')}}"><i class="ti ti-moon-2"></i><span>Under
                                    Maintenance</span></a></li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Settings</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('profile-settings', 'security-settings', 'notifications-settings', 'connected-apps') ? 'subdrop active' : '' }}">
                                <i class="ti ti-shield-cog"></i><span>General Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('profile-settings') ? 'active' : '' }}"
                                    href="{{ url('profile-settings') }}">Profile Settings</a></li>
                            <li><a class="{{ Request::is('security-settings') ? 'active' : '' }}"
                                    href="{{ url('security-settings') }}">Security Settings</a></li>
                            <li><a class="{{ Request::is('notifications-settings') ? 'active' : '' }}"
                                    href="{{ url('notifications-settings') }}">Notifications Settings</a></li>
                            <li><a class="{{ Request::is('connected-apps') ? 'active' : '' }}"
                                    href="{{ url('connected-apps') }}">Connected Apps</a></li>

                            </ul>
                        </li>

                        <!-- Till -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('company-settings', 'localization', 'prefixes', 'preferences', 'social-authentication', 'language') ? 'subdrop active' : '' }}">
                                <i class="ti ti-device-laptop"></i><span>Website Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('company-settings') ? 'active' : '' }}"
                                    href="{{ url('company-settings') }}">Company Settings</a></li>
                            <li><a class="{{ Request::is('localization') ? 'active' : '' }}"
                                    href="{{ url('localization') }}">Localization</a></li>
                            <li><a class="{{ Request::is('prefixes') ? 'active' : '' }}"
                                    href="{{ url('prefixes') }}">Prefixes</a></li>
                            <li><a class="{{ Request::is('preferences') ? 'active' : '' }}"
                                    href="{{ url('preferences') }}">Preferences</a></li>
                            <li><a class="{{ Request::is('social-authentication') ? 'active' : '' }}"
                                    href="{{ url('social-authentication') }}">Social Authentication</a></li>
                            <li><a class="{{ Request::is('language') ? 'active' : '' }}"
                                    href="{{ url('language') }}">Language</a></li>  
                                
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('invoice-settings', 'custom-fields') ? 'subdrop active' : '' }}">
                                <i class="ti ti-apps"></i><span>App Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('invoice-settings') ? 'active' : '' }}"
                                    href="{{ url('invoice-settings') }}">Invoice Settings</a></li>
                            <li><a class="{{ Request::is('custom-fields') ? 'active' : '' }}"
                                    href="{{ url('custom-fields') }}">Custom Fields</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('email-settings', 'email-templates', 'sms-settings', 'otp-settings', 'gdpr-cookies') ? 'subdrop active' : '' }}">
                                <i class="ti ti-file-symlink"></i><span>System Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('email-settings') ? 'active' : '' }}"
                                    href="{{ url('email-settings') }}">Email Settings</a></li>
                            <li><a class="{{ Request::is('email-templates') ? 'active' : '' }}"
                                    href="{{ url('email-templates') }}">Email Templates</a></li>
                            <li><a class="{{ Request::is('sms-settings') ? 'active' : '' }}"
                                    href="{{ url('sms-settings') }}">SMS Settings</a></li>
                            <li><a class="{{ Request::is('otp-settings') ? 'active' : '' }}"
                                    href="{{ url('otp-settings') }}">OTP</a></li>
                            <li><a class="{{ Request::is('gdpr-cookies') ? 'active' : '' }}"
                                    href="{{ url('gdpr-cookies') }}">GDPR Cookies</a></li>

                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('payment-gateways', 'tax-rates') ? 'subdrop active' : '' }}">
                                <i class="ti ti-zoom-money"></i><span>Financial Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('payment-gateways') ? 'active' : '' }}"
                                    href="{{ url('payment-gateways') }}">Payment Gateways </a></li>
                            <li><a class="{{ Request::is('tax-rates') ? 'active' : '' }}"
                                    href="{{ url('tax-rates') }}">Tax Rates</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('school-settings', 'religion') ? 'subdrop active' : '' }}">
                                <i class="ti ti-calendar-repeat"></i><span>Academic Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('school-settings') ? 'active' : '' }}"
                                    href="{{ url('school-settings') }}">School Settings </a></li>
                            <li><a class="{{ Request::is('religion') ? 'active' : '' }}"
                                    href="{{ url('religion') }}">Religion</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('storage', 'ban-ip-address') ? 'subdrop active' : '' }}">
                                <i class="ti ti-flag-cog"></i><span>Other Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('storage') ? 'active' : '' }}"
                                    href="{{ url('storage') }}">Storage</a></li>
                            <li><a class="{{ Request::is('ban-ip-address') ? 'active' : '' }}"
                                    href="{{ url('ban-ip-address') }}">Ban IP Address</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <h6 class="submenu-hdr"><span>UI Interface</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is(
                                'ui-alerts',
                                'ui-accordion',
                                'ui-avatar',
                                'ui-badges',
                                'ui-buttons',
                                'ui-buttons-group',
                                'ui-breadcrumb',
                                'ui-cards',
                                'ui-carousel',
                                'ui-dropdowns',
                                'ui-grid',
                                'ui-images',
                                'ui-lightbox',
                                'ui-modals',
                                'ui-offcanvas',
                                'ui-pagination',
                                'ui-popovers',
                                'ui-progress',
                                'ui-placeholders',
                                'ui-links',
                                'ui-list-group',
                                'ui-collapse',
                                'ui-spinner',
                                'ui-sweetalerts',
                                'ui-nav-tabs',
                                'ui-tooltips',
                                'ui-typography'
                            )
                                ? 'subdrop active'
                                : '' }}">
                                <i class="ti ti-hierarchy-2"></i><span>Base UI</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('ui-alerts') ? 'active' : '' }}"
                                    href="{{ url('ui-alerts') }}">Alerts</a></li>
                            <li><a class="{{ Request::is('ui-accordion') ? 'active' : '' }}"
                                    href="{{ url('ui-accordion') }}">Accordion</a></li>
                            <li><a class="{{ Request::is('ui-avatar') ? 'active' : '' }}"
                                    href="{{ url('ui-avatar') }}">Avatar</a></li>
                            <li><a class="{{ Request::is('ui-badges') ? 'active' : '' }}"
                                    href="{{ url('ui-badges') }}">Badges</a></li>
                            <li><a class="{{ Request::is('ui-buttons') ? 'active' : '' }}"
                                    href="{{ url('ui-buttons') }}">Buttons</a></li>
                            <li><a class="{{ Request::is('ui-buttons-group') ? 'active' : '' }}"
                                    href="{{ url('ui-buttons-group') }}">Button Group</a></li>
                            <li><a class="{{ Request::is('ui-breadcrumb') ? 'active' : '' }}"
                                    href="{{ url('ui-breadcrumb') }}">Breadcrumb</a></li>
                            <li><a class="{{ Request::is('ui-cards') ? 'active' : '' }}"
                                    href="{{ url('ui-cards') }}">Card</a></li>
                            <li><a class="{{ Request::is('ui-carousel') ? 'active' : '' }}"
                                    href="{{ url('ui-carousel') }}">Carousel</a></li>
                            <li><a class="{{ Request::is('ui-collapse') ? 'active' : '' }}"
                                    href="{{ url('ui-collapse') }}">Collapse</a></li>                                    
                            <li><a class="{{ Request::is('ui-dropdowns') ? 'active' : '' }}"
                                    href="{{ url('ui-dropdowns') }}">Dropdowns</a></li>
                            <li><a class="{{ Request::is('ui-grid') ? 'active' : '' }}"
                                    href="{{ url('ui-grid') }}">Grid</a></li>
                            <li><a class="{{ Request::is('ui-images') ? 'active' : '' }}"
                                    href="{{ url('ui-images') }}">Images</a></li>
                            <li><a class="{{ Request::is('ui-links') ? 'active' : '' }}"
                                    href="{{ url('ui-links') }}">Links</a></li>
                            <li><a class="{{ Request::is('ui-list-group') ? 'active' : '' }}"
                                    href="{{ url('ui-list-group') }}">List Group</a></li>                                                                        
                            <li><a class="{{ Request::is('ui-lightbox') ? 'active' : '' }}"
                                    href="{{ url('ui-lightbox') }}">Lightbox</a></li>
                            <li><a class="{{ Request::is('ui-modals') ? 'active' : '' }}"
                                    href="{{ url('ui-modals') }}">Modals</a></li>
                            <li><a class="{{ Request::is('ui-offcanvas') ? 'active' : '' }}"
                                    href="{{ url('ui-offcanvas') }}">Offcanvas</a></li>
                            <li><a class="{{ Request::is('ui-pagination') ? 'active' : '' }}"
                                    href="{{ url('ui-pagination') }}">Pagination</a></li>
                            <li><a class="{{ Request::is('ui-popovers') ? 'active' : '' }}"
                                    href="{{ url('ui-popovers') }}">Popovers</a></li>
                            <li><a class="{{ Request::is('ui-progress') ? 'active' : '' }}"
                                    href="{{ url('ui-progress') }}">Progress</a></li>
                            <li><a class="{{ Request::is('ui-placeholders') ? 'active' : '' }}"
                                    href="{{ url('ui-placeholders') }}">Placeholders</a></li>
                           
                            <li><a class="{{ Request::is('ui-spinner') ? 'active' : '' }}"
                                    href="{{ url('ui-spinner') }}">Spinner</a></li>
                            <li><a class="{{ Request::is('ui-sweetalerts') ? 'active' : '' }}"
                                    href="{{ url('ui-sweetalerts') }}">Sweet Alerts</a></li>
                            <li><a class="{{ Request::is('ui-nav-tabs') ? 'active' : '' }}"
                                    href="{{ url('ui-nav-tabs') }}">Tabs</a></li>
                            <li><a class="{{ Request::is('ui-tooltips') ? 'active' : '' }}"
                                    href="{{ url('ui-tooltips') }}">Tooltips</a></li>
                            <li><a class="{{ Request::is('ui-typography') ? 'active' : '' }}"
                                    href="{{ url('ui-typography') }}">Typography</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('ui-clipboard', 'ui-drag-drop', 'ui-scrollbar') ? 'subdrop active' : '' }}">
                                <i class="ti ti-hierarchy-3"></i><span>Advanced UI</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                            <li><a class="{{ Request::is('ui-clipboard') ? 'active' : '' }}"
                                    href="{{ url('ui-clipboard') }}">Clipboard</a></li>
                            <li><a class="{{ Request::is('ui-drag-drop') ? 'active' : '' }}"
                                    href="{{ url('ui-drag-drop') }}">Drag & Drop</a></li>
                            <li><a class="{{ Request::is('ui-scrollbar') ? 'active' : '' }}"
                                    href="{{ url('ui-scrollbar') }}">Scrollbar</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('chart-apex', 'chart-js', 'chart-morris', 'chart-flot', 'chart-peity', 'chart-c3') ? 'subdrop active' : '' }}"><i class="ti ti-chart-line"></i>
                                <span>Charts</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('chart-apex') ? 'active' : '' }}"
                                    href="{{ url('chart-apex') }}">Apex Charts</a></li>
                            <li><a class="{{ Request::is('chart-c3') ? 'active' : '' }}"
                                    href="{{ url('chart-c3') }}">C3 Charts</a></li>
                            <li><a class="{{ Request::is('chart-js') ? 'active' : '' }}"
                                    href="{{ url('chart-js') }}">Chart Js</a></li>
                            <li><a class="{{ Request::is('chart-morris') ? 'active' : '' }}"
                                    href="{{ url('chart-morris') }}">Morris Charts</a></li>
                            <li><a class="{{ Request::is('chart-flot') ? 'active' : '' }}"
                                    href="{{ url('chart-flot') }}">Flot Charts</a></li>
                            <li><a class="{{ Request::is('chart-peity') ? 'active' : '' }}"
                                    href="{{ url('chart-peity') }}">Peity Charts</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"  class="{{ Request::is('icon-fontawesome', 'icon-feather', 'icon-ionic', 'icon-material', 'icon-pe7', 'icon-simpleline', 'icon-themify', 'icon-weather', 'icon-typicon', 'icon-flag') ? 'subdrop active' : '' }}"><i class="ti ti-icons"></i>
                                <span>Icons</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="{{ Request::is('icon-fontawesome') ? 'active' : '' }}"
                                    href="{{ url('icon-fontawesome') }}">Fontawesome Icons</a></li>
                            <li><a class="{{ Request::is('icon-feather') ? 'active' : '' }}"
                                    href="{{ url('icon-feather') }}">Feather Icons</a></li>
                            <li><a class="{{ Request::is('icon-ionic') ? 'active' : '' }}"
                                    href="{{ url('icon-ionic') }}">Ionic Icons</a></li>
                            <li><a class="{{ Request::is('icon-material') ? 'active' : '' }}"
                                    href="{{ url('icon-material') }}">Material Icons</a></li>
                            <li><a class="{{ Request::is('icon-pe7') ? 'active' : '' }}"
                                    href="{{ url('icon-pe7') }}">Pe7 Icons</a></li>
                            <li><a class="{{ Request::is('icon-simpleline') ? 'active' : '' }}"
                                    href="{{ url('icon-simpleline') }}">Simpleline Icons</a></li>
                            <li><a class="{{ Request::is('icon-themify') ? 'active' : '' }}"
                                    href="{{ url('icon-themify') }}">Themify Icons</a></li>
                            <li><a class="{{ Request::is('icon-weather') ? 'active' : '' }}"
                                    href="{{ url('icon-weather') }}">Weather Icons</a></li>
                            <li><a class="{{ Request::is('icon-typicon') ? 'active' : '' }}"
                                    href="{{ url('icon-typicon') }}">Typicon Icons</a></li>
                            <li><a class="{{ Request::is('icon-flag') ? 'active' : '' }}"
                                    href="{{ url('icon-flag') }}">Flag Icons</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"class="{{ Request::is('form-basic-inputs', 'form-elements','form-checkbox-radios', 'form-input-groups', 'form-grid-gutters', 'form-select', 'form-mask', 'form-fileupload', 'form-horizontal', 'form-vertical', 'form-floating-labels', 'form-validation', 'form-wizard', 'form-select2') ? 'subdrop active' : '' }}">
                                <i class="ti ti-input-search"></i><span>Forms</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);"  class="{{ Request::is('form-basic-inputs', 'form-checkbox-radios', 'form-input-groups','form-elements', 'form-grid-gutters', 'form-select', 'form-mask', 'form-fileupload') ? 'subdrop active' : '' }}">Form Elements<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a class="{{ Request::is('form-basic-inputs') ? 'active' : '' }}"
                                            href="{{ url('form-basic-inputs') }}">Basic Inputs </a></li>
                                    <li><a class="{{ Request::is('form-checkbox-radios') ? 'active' : '' }}"
                                            href="{{ url('form-checkbox-radios') }}">Checkbox & Radios </a></li>
                                    <li><a class="{{ Request::is('form-input-groups') ? 'active' : '' }}"
                                            href="{{ url('form-input-groups') }}">Input Groups </a></li>
                                    <li><a class="{{ Request::is('form-grid-gutters') ? 'active' : '' }}"
                                            href="{{ url('form-grid-gutters') }}"> Grid & Gutters </a></li>
                                    <li><a class="{{ Request::is('form-select') ? 'active' : '' }}"
                                            href="{{ url('form-select') }}">Form Select </a></li>
                                    <li><a class="{{ Request::is('form-mask') ? 'active' : '' }}"
                                            href="{{ url('form-mask') }}"> Input Masks </a></li>
                                    <li><a class="{{ Request::is('form-fileupload') ? 'active' : '' }}"
                                            href="{{ url('form-fileupload') }}">File Upload </a></li>
                                    <li><a class="{{ Request::is('form-elements') ? 'active' : '' }}"
                                            href="{{ url('form-elements') }}">File Element</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="{{ Request::is('form-horizontal', 'form-vertical', 'form-floating-labels') ? 'subdrop active' : '' }}">Layouts<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a class="{{ Request::is('form-horizontal') ? 'active' : '' }}"
                                            href="{{ url('form-horizontal') }}">Horizontal Form </a></li>
                                    <li><a class="{{ Request::is('form-vertical') ? 'active' : '' }}"
                                            href="{{ url('form-vertical') }}"> Vertical Form </a></li>
                                    <li><a class="{{ Request::is('form-floating-labels') ? 'active' : '' }}"
                                            href="{{ url('form-floating-labels') }}">Floating Labels </a></li>
                                    </ul>
                                </li>
                                <li><a class="{{ Request::is('form-validation') ? 'active' : '' }}"
                                    href="{{ url('form-validation') }}"> Form Validation </a></li>
                            <li><a class="{{ Request::is('form-select2') ? 'active' : '' }}"
                                    href="{{ url('form-select2') }}">Select2 </a></li>
                            <li><a class="{{ Request::is('form-wizard') ? 'active' : '' }}"
                                    href="{{ url('form-wizard') }}">Form Wizard</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"   class="{{ Request::is('tables-basic', 'data-tables') ? 'subdrop active' : '' }}"><i class="ti ti-table-plus"></i><span>Tables</span><span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('tables-basic') ? 'active' : '' }}"
                                    href="{{ url('tables-basic') }}">Basic Tables </a></li>
                            <li><a class="{{ Request::is('data-tables') ? 'active' : '' }}"
                                    href="{{ url('data-tables') }}">Data Table </a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li>
                    <h6 class="submenu-hdr"><span>Help</span></h6>
                    <ul>
                        <li><a href="https://preschool.dreamstechnologies.com/documentation/laravel.html"><i class="ti ti-file-text"></i><span>Documentation</span></a></li>
                        
                        <li><a href="https://preschool.dreamstechnologies.com/documentation/changelog.html"><i class="ti ti-exchange"></i><span>Changelog</span><span class="badge badge-primary badge-xs text-white fs-10 ms-auto">v1.9.7</span></a></li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><i class="ti ti-menu-2"></i><span>Multi Level</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="javascript:void(0);">Multilevel  1</a></li>
                                <li class="submenu submenu-two"><a href="javascript:void(0);">Multilevel  2<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a href="javascript:void(0);">Multilevel  2.1</a></li>
                                        <li class="submenu submenu-two submenu-three"><a href="javascript:void(0);">Multilevel  2.2<span class="menu-arrow inside-submenu inside-submenu-two"></span></a>
                                            <ul>
                                                <li><a href="javascript:void(0);">Multilevel  2.2.1</a></li>
                                                <li><a href="javascript:void(0);">Multilevel  2.2.2</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                                <li><a href="javascript:void(0);">Multilevel  3</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->




