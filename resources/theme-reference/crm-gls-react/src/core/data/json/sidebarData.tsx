import { all_routes } from "../../../feature-module/router/all_routes";
const routes = all_routes;

export const SidebarData = [
  {
    label: "MAIN",
    submenuOpen: true,
    showSubRoute: false,
    submenuHdr: "Main",
    submenuItems: [
      {
        label: "Dashboard",
        icon: "ti ti-layout-dashboard",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "Admin Dashboard", link: routes.adminDashboard },
          { label: "Teacher Dashboard", link: routes.teacherDashboard },
          { label: "Student Dashboard", link: routes.studentDashboard },
          { label: "Parent Dashboard", link: routes.parentDashboard },
        ],
      },
      {
        label: "Application",
        icon: "ti ti-layout-list",
        submenu: true,
        showSubRoute: false,
        submenuItems: [
          {
            label: "Chat",
            link: routes.chat,
            showSubRoute: false,
          },
          {
            label: "Call",
            link: routes.audioCall,
            showSubRoute: false,
          },
          {
            label: "Calendar",
            link: routes.calendar,
            showSubRoute: false,
          },
          {
            label: "Email",
            link: routes.email,
            showSubRoute: false,
          },
          {
            label: "To Do",
            link: routes.todo,
            showSubRoute: false,
          },
          {
            label: "Notes",
            link: routes.notes,
            showSubRoute: false,
          },
          {
            label: "File Manager",
            link: routes.fileManager,
            showSubRoute: false,
          },
        ],
      },
    ],
  },
  {
    label: "LAYOUT",
    submenuOpen: false,
    showSubRoute: false,
    submenuHdr: "LAYOUT",
    submenuItems: [
      {
        label: "Default",
        icon: "ti ti-layout-sidebar",
        submenu: false,
        showSubRoute: false,
        link: routes.layoutDefault,
        themeSetting: true,
      },
      {
        label: "Mini",
        icon: "ti ti-layout-align-left",
        submenu: false,
        showSubRoute: false,
        link: routes.layoutMini,
        themeSetting: true,
      },
      {
        label: "RTL",
        icon: "ti ti-text-direction-rtl",
        submenu: false,
        showSubRoute: false,
        link: routes.layoutRtl,
        themeSetting: true,
      },
      {
        label: "Box",
        icon: "ti ti-layout-distribute-vertical",
        submenu: false,
        showSubRoute: false,
        link: routes.layoutBox,
        themeSetting: true,
      },
      {
        label: "Dark",
        icon: "ti ti-moon",
        submenu: false,
        showSubRoute: false,
        link: routes.layoutDark,
        themeSetting: true,
      },
    ],
  },
  {
    label: "Peoples",
    submenuOpen: true,
    showSubRoute: false,
    submenuHdr: "Peoples",

    submenuItems: [
      {
        label: "Students",
        icon: "ti ti-school",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          {
            label: "All Students",
            link: routes.studentGrid,
            subLink1: routes.addStudent,
            subLink2: routes.editStudent,
          },
          { label: "Students List", link: routes.studentList },
          {
            label: "Students Details",
            link: routes.studentDetail,
            subLink1: routes.studentLibrary,
            subLink2: routes.studentResult,
            subLink3: routes.studentFees,
            subLink4: routes.studentLeaves,
            subLink5: routes.studentTimeTable,
          },
          { label: "Student Promotion", link: routes.studentPromotion },
        ],
      },
      {
        label: "Parents",
        icon: "ti ti-user-bolt",
        showSubRoute: false,
        submenu: true,
        submenuItems: [
          { label: "All Parents", link: routes.parentGrid },
          { label: "Parents List", link: routes.parentList },
        ],
      },
      {
        label: "Guardians",
        icon: "ti ti-user-shield",
        showSubRoute: false,
        submenu: true,
        submenuItems: [
          { label: "All Guardians", link: routes.guardiansGrid },
          { label: "Guardians List", link: routes.guardiansList },
        ],
      },
      {
        label: "Teachers",
        icon: "ti ti-users",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          {
            label: "All Teachers",
            link: routes.teacherGrid,
            subLink1: routes.addTeacher,
            subLink2: routes.editTeacher,
          },
          { label: "Teacher List", link: routes.teacherList },
          {
            label: "Teacher Details",
            link: routes.teacherDetails,
            subLink1: routes.teacherLibrary,
            subLink2: routes.teacherSalary,
            subLink3: routes.teacherLeaves,
          },
          { label: "Routine", link: routes.teachersRoutine },
        ],
      },
    ],
  },
  {
    label: "Academic",
    submenuOpen: true,
    showSubRoute: false,
    submenuHdr: "Academic",

    submenuItems: [
      {
        label: "Classes",
        icon: "ti ti-school-bell",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "All Classes", link: routes.classes },
          { label: "Schedule", link: routes.sheduleClasses },
        ],
      },
      {
        label: "Class Room",
        link: routes.classRoom,
        icon: "ti ti-building",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Class Routine",
        link: routes.classRoutine,
        icon: "ti ti-bell-school",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Section",
        link: routes.classSection,
        icon: "ti ti-square-rotated-forbid-2",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Subject",
        link: routes.classSubject,
        icon: "ti ti-book",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Syllabus",
        link: routes.classSyllabus,
        icon: "ti ti-book-upload",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Time Table",
        link: routes.classTimetable,
        icon: "ti ti-table",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Home Work",
        link: routes.classHomeWork,
        icon: "ti ti-license",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Examinations",
        icon: "ti ti-hexagonal-prism-plus",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "Exam", link: routes.exam },
          { label: "Exam Schedule", link: routes.examSchedule },
          { label: "Grade", link: routes.grade },
          { label: "Exam Attendance", link: routes.examAttendance },
          { label: "Exam Results", link: routes.examResult },
        ],
      },
      {
        label: "Reasons",
        link: routes.AcademicReason,
        icon: "ti ti-lifebuoy",
        showSubRoute: false,
        submenu: false,
      },
    ],
  },
  {
    label: "MANAGEMENT",
    submenuOpen: true,
    submenuHdr: "Management",
    submenu: false,
    showSubRoute: false,
    submenuItems: [
      {
        label: "Fees Collection",
        icon: "ti ti-report-money",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "Fees Group", link: routes.feesGroup },
          { label: "Fees Type", link: routes.feesType },
          { label: "Fees Master", link: routes.feesMaster },
          { label: "Fees Assign", link: routes.feesAssign },
          { label: "Collect Fees", link: routes.collectFees },
        ],
      },
      {
        label: "Library",
        icon: "ti ti-notebook",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "Library Members", link: routes.libraryMembers },
          { label: "Books", link: routes.libraryBooks },
          { label: "Issue Book", link: routes.libraryIssueBook },
          { label: "Return", link: routes.libraryReturn },
        ],
      },
      {
        label: "Sports",
        link: routes.sportsList,
        icon: "ti ti-run",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Players",
        link: routes.playerList,
        icon: "ti ti-play-football",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Hostel",
        icon: "ti ti-building-fortress",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "Hostel List", link: routes.hostelList },
          { label: "Hostel Rooms", link: routes.hostelRoom },
          { label: "Room Type", link: routes.hostelType },
        ],
      },
      {
        label: "Transport",
        icon: "ti ti-bus",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "Routes", link: routes.transportRoutes },
          { label: "Pickup Points", link: routes.transportPickupPoints },
          { label: "Vehicle Drivers", link: routes.transportVehicleDrivers },
          { label: "Vehicle", link: routes.transportVehicle },
          { label: "Assign Vehicle", link: routes.transportAssignVehicle },
        ],
      },
    ],
  },
  {
    label: "HRM",
    submenuOpen: true,
    submenuHdr: "HRM",
    submenu: false,
    showSubRoute: false,
    submenuItems: [
      {
        label: "Staffs",
        link: routes.staff,
        subLink1: routes.addStaff,
        subLink2: routes.editStaff,
        icon: "ti ti-users-group",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Departments",
        link: routes.departments,
        icon: "ti ti-layout-distribute-horizontal",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Designation",
        link: routes.designation,
        icon: "ti ti-user-exclamation",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Attendance",
        icon: "ti ti-calendar-share",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "Student Attendance", link: routes.studentAttendance },
          { label: "Teacher Attendance", link: routes.teacherAttendance },
          { label: "Staff Attendance", link: routes.staffAttendance },
        ],
      },
      {
        label: "Leaves",
        icon: "ti ti-calendar-stats",
        submenu: true,
        showSubRoute: false,

        submenuItems: [
          { label: "List of leaves", link: routes.listLeaves },
          { label: "Approve Request", link: routes.approveRequest },
        ],
      },
      {
        label: "Holidays",
        link: routes.holidays,
        icon: "ti ti-briefcase",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Payroll",
        link: routes.payroll,
        icon: "ti ti-moneybag",
        showSubRoute: false,
        submenu: false,
      },
    ],
  },
  {
    label: "Finance & Accounts",
    submenuOpen: true,
    submenuHdr: "Finance & Accounts",
    submenu: false,
    showSubRoute: false,
    submenuItems: [
      {
        label: "Accounts",
        icon: "ti ti-swipe",
        submenu: true,
        showSubRoute: false,
        submenuItems: [
          { label: "Expenses", link: routes.expense },
          { label: "Expense Category", link: routes.expenseCategory },
          { label: "Income", link: routes.accountsIncome },
          {
            label: "Invoices",
            link: routes.accountsInvoices,
            subLink1: routes.addInvoice,
            subLink2: routes.editInvoice,
          },
          { label: "Invoice View", link: routes.invoice },
          { label: "Transactions", link: routes.accountsTransactions },
        ],
      },
    ],
  },
  {
    label: "Announcements",
    submenuOpen: true,
    submenuHdr: "Announcements",
    submenu: false,
    showSubRoute: false,
    submenuItems: [
      {
        label: "Notice Board",
        link: routes.noticeBoard,
        icon: "ti ti-clipboard-data",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Events",
        link: routes.events,
        icon: "ti ti-calendar-question",
        showSubRoute: false,
        submenu: false,
      },
    ],
  },
  {
    label: "Reports",
    submenuOpen: true,
    submenuHdr: "Reports",
    submenu: false,
    showSubRoute: false,
    submenuItems: [
      {
        label: "Attendance Report",
        link: routes.attendanceReport,
        subLink1: routes.studentAttendanceType,
        subLink2: routes.staffReport,
        subLink3: routes.teacherReport,
        subLink4: routes.staffDayWise,
        subLink5: routes.teacherDayWise,
        subLink6: routes.studentDayWise,
        subLink7: routes.dailyAttendance,
        icon: "ti ti-calendar-due",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Class Report",
        link: routes.classReport,
        icon: "ti ti-graph",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Student Report",
        link: routes.studentReport,
        icon: "ti ti-chart-infographic",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Grade Report",
        link: routes.gradeReport,
        icon: "ti ti-calendar-x",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Leave Report",
        link: routes.leaveReport,
        icon: "ti ti-line",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Fees Report",
        link: routes.feesReport,
        icon: "ti ti-mask",
        showSubRoute: false,
        submenu: false,
      },
    ],
  },
  {
    label: "USER MANAGEMENT",
    submenuOpen: true,
    submenuHdr: "Sales",
    submenu: false,
    showSubRoute: false,
    submenuItems: [
      {
        label: "Users",
        link: routes.manageusers,
        icon: "ti ti-users-minus",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Roles & Permission",
        link: routes.rolesPermissions,
        icon: "ti ti-shield-plus",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Delete Account Request",
        link: routes.deleteRequest,
        icon: "ti ti-user-question",
        showSubRoute: false,
        submenu: false,
      },
    ],
  },
  {
    label: "MEMBERSHIP",
    submenuOpen: true,
    showSubRoute: false,
    submenuHdr: "Finance & Accounts",
    submenuItems: [
      {
        label: "Membership Plans",
        link: routes.membershipplan,
        icon: "ti ti-user-plus",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Membership Addons",
        link: routes.membershipAddon,
        icon: "ti ti-cone-plus",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Transactions",
        link: routes.membershipTransaction,
        icon: "ti ti-file-power",
        showSubRoute: false,
        submenu: false,
      },
    ],
  },
  {
    label: "CONTENT",
    icon: "ti ti-page-break",
    submenu: true,
    showSubRoute: false,
    submenuItems: [
      {
        label: "Pages",
        link: routes.pages,
        showSubRoute: false,
        icon: "ti ti-page-break",
      },
      {
        label: "Blog",
        icon: "ti ti-brand-blogger",
        submenu: true,
        submenuItems: [
          { label: "All Blogs", link: routes.allBlogs },
          {
            label: "Categories",
            link: routes.blogCategories,
            icon: "ti ti-quote",
          },
          {
            label: "Comments",
            link: routes.blogComments,
            icon: "ti ti-question-mark",
          },
          {
            label: "Tags",
            link: routes.blogTags,
            icon: "ti ti-question-mark",
          },
        ],
      },
      {
        label: "Location",
        icon: "ti ti-map-pin-search",
        submenu: true,
        submenuItems: [
          { label: "Countries", link: routes.countries },
          { label: "States", link: routes.states, icon: "ti ti-quote" },
          {
            label: "Cities",
            link: routes.cities,
            icon: "ti ti-question-mark",
          },
        ],
      },
      {
        label: "Testimonials",
        link: routes.testimonials,
        showSubRoute: false,
        icon: "ti ti-quote",
      },
      {
        label: "FAQ",
        link: routes.faq,
        showSubRoute: false,
        icon: "ti ti-question-mark",
      },
    ],
  },
  {
    label: "Support",
    submenuOpen: true,
    showSubRoute: false,
    submenuHdr: "Finance & Accounts",
    submenuItems: [
      {
        label: "Contact Messages",
        link: routes.contactMessages,
        icon: "ti ti-message",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Tickets",
        link: routes.tickets,
        icon: "ti ti-ticket",
        showSubRoute: false,
        submenu: false,
      },
    ],
  },
  {
    label: "Pages",
    submenu: true,
    showSubRoute: false,
    submenuHdr: "Authentication",
    submenuItems: [
      {
        label: "Profile",
        link: routes.profile,
        icon: "ti ti-user",
        showSubRoute: false,
        submenu: false,
      },

      {
        label: "Authentication",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-lock-square-rounded",
        submenuItems: [
          {
            label: "Login",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Cover", link: routes.login },
              { label: "Illustration", link: routes.login },
              { label: "Basic", link: routes.login },
            ],
          },
          {
            label: "Register",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Cover", link: routes.register },
              { label: "Illustration", link: routes.register },
              { label: "Basic", link: routes.register },
            ],
          },
          {
            label: "Forgot Password",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Cover", link: routes.forgotPassword },
              { label: "Illustration", link: routes.forgotPassword },
              { label: "Basic", link: routes.forgotPassword },
            ],
          },
          {
            label: "Reset Password",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Cover", link: routes.resetPassword },
              { label: "Illustration", link: routes.resetPassword },
              { label: "Basic", link: routes.resetPassword },
            ],
          },
          {
            label: "Email Verfication",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Cover", link: routes.emailVerification },
              { label: "Illustration", link: routes.emailVerification },
              { label: "Basic", link: routes.emailVerification },
            ],
          },
          {
            label: "2 Step Verification",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Cover", link: routes.emailVerification },
              { label: "Illustration", link: routes.emailVerification },
              { label: "Basic", link: routes.emailVerification },
            ],
          },
          { label: "Lock Screen", link: routes.lockScreen },
        ],
      },
      {
        label: "Error Pages",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-error-404",
        submenuItems: [
          {
            label: "404 Error",
            link: routes.error404,
            showSubRoute: false,
          },
          { label: "500 Error", link: routes.error500, showSubRoute: false },
        ],
      },
      {
        label: "Blank Page",
        link: routes.blankPage,
        icon: "ti ti-brand-nuxt",
        showSubRoute: false,
        submenu: false,
      },
      {
        label: "Coming Soon",
        link: routes.comingSoon,
        icon: "ti ti-file",
        showSubRoute: false,
      },
      {
        label: "Under Maintenance",
        link: routes.underMaintenance,
        icon: "ti ti-moon-2",
        showSubRoute: false,
      },
    ],
  },
  {
    label: "Settings",
    submenu: true,
    showSubRoute: false,
    submenuHdr: "Settings",
    submenuItems: [
      {
        label: "General Settings",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-shield-cog",
        submenuItems: [
          { label: "Profile Settings", link: routes.profilesettings },
          { label: "Security Settings", link: routes.securitysettings },
          {
            label: "Notifications Settings",
            link: routes.notificationssettings,
          },
          { label: "Connected Apps", link: routes.connectedApps },
        ],
      },
      {
        label: "Website Settings",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-device-laptop",
        submenuItems: [
          {
            label: "Company Settings",
            link: routes.companySettings,
            showSubRoute: false,
          },
          {
            label: "Localization",
            link: routes.localization,
            showSubRoute: false,
          },
          { label: "Prefixes", link: routes.prefixes, showSubRoute: false },
          { label: "Preference", link: routes.preference, showSubRoute: false },
          {
            label: "Social Authentication",
            link: routes.socialAuthentication,
            showSubRoute: false,
          },
          {
            label: "Language",
            link: routes.language,
            showSubRoute: false,
          },
        ],
      },
      {
        label: "App Settings",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-apps",
        submenuItems: [
          {
            label: "Invoice Settings",
            link: routes.invoiceSettings,
            showSubRoute: false,
          },
          {
            label: "Custom Fields",
            link: routes.customFields,
            showSubRoute: false,
          },
        ],
      },
      {
        label: "System Settings",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-file-symlink",
        submenuItems: [
          {
            label: "Email Settings",
            link: routes.emailSettings,
            showSubRoute: false,
          },
          {
            label: "Email Templates",
            link: routes.emailTemplates,
            showSubRoute: false,
          },
          {
            label: "SMS Settings",
            link: routes.smsSettings,
            showSubRoute: false,
          },
          {
            label: "OTP",
            link: routes.optSettings,
            showSubRoute: false,
          },
          {
            label: "GDPR Cookies",
            link: routes.gdprCookies,
            showSubRoute: false,
          },
        ],
      },
      {
        label: "Financial Settings",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-zoom-money",
        submenuItems: [
          {
            label: "Payment Gateway",
            link: routes.paymentGateways,
            showSubRoute: false,
          },
          { label: "Tax Rates", link: routes.taxRates, showSubRoute: false },
        ],
      },
      {
        label: "Academic Settings",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-calendar-repeat",
        submenuItems: [
          {
            label: "School Settings",
            link: routes.schoolSettings,
            showSubRoute: false,
          },
          { label: "Religion", link: routes.religion, showSubRoute: false },
        ],
      },
      {
        label: "Other Settings",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-flag-cog",
        submenuItems: [
          { label: "Storage", link: routes.storage, showSubRoute: false },
          {
            label: "Ban IP Address",
            link: routes.banIpAddress,
            showSubRoute: false,
          },
        ],
      },
    ],
  },

  {
    label: "UI Interface",
    submenuOpen: true,
    showSubRoute: false,
    submenuHdr: "UI Interface",
    submenuItems: [
      {
        label: "Base UI",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-hierarchy-2",
        submenuItems: [
          { label: "Alerts", link: routes.alert, showSubRoute: false },
          { label: "Accordion", link: routes.accordion, showSubRoute: false },
          { label: "Avatar", link: routes.avatar, showSubRoute: false },
          { label: "Badges", link: routes.uiBadges, showSubRoute: false },
          { label: "Border", link: routes.border, showSubRoute: false },
          { label: "Buttons", link: routes.button, showSubRoute: false },
          {
            label: "Button Group",
            link: routes.buttonGroup,
            showSubRoute: false,
          },
          { label: "Breadcrumb", link: routes.breadcrums, showSubRoute: false },
          { label: "Card", link: routes.cards, showSubRoute: false },
          { label: "Carousel", link: routes.carousel, showSubRoute: false },
          { label: "Collapse", link: routes.uiCollapse, showSubRoute: false },
          { label: "Dropdowns", link: routes.dropdowns, showSubRoute: false },
          { label: "Images", link: routes.images, showSubRoute: false },
          { label: "Links", link: routes.uiLinks, showSubRoute: false },
          { label: "List Group", link: routes.uiListGroup, showSubRoute: false },
       
          { label: "Modals", link: routes.modals, showSubRoute: false },
          { label: "Offcanvas", link: routes.offcanvas, showSubRoute: false },
          { label: "Pagination", link: routes.pagination, showSubRoute: false },
          { label: "Progress", link: routes.progress, showSubRoute: false },
          {
            label: "Placeholders",
            link: routes.placeholder,
            showSubRoute: false,
          },
         
          { label: "Spinner", link: routes.spinner, showSubRoute: false },
         
          { label: "Tabs", link: routes.navTabs, showSubRoute: false },
         
          { label: "Tooltips", link: routes.tooltip, showSubRoute: false },
          { label: "Typography", link: routes.typography, showSubRoute: false },
          
        ],
      },
      {
        label: "Advanced UI",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-hierarchy-3",
        submenuItems: [
         
          { label: "Clipboard", link: routes.clipboard, showSubRoute: false },
          {
            label: "Drag & Drop",
            link: routes.dragandDrop,
            showSubRoute: false,
          },
        
          
         
          { label: "Scrollbar", link: routes.scrollBar, showSubRoute: false },
           { label: "Lightbox", link: routes.lightbox, showSubRoute: false },
        
        ],
      },
      {
        label: "Charts",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-chart-line",
        submenuItems: [
          { label: "Apex Charts", link: routes.apexChat, showSubRoute: false },
          // { label: "Chart Js", link: routes.chart, showSubRoute: false },
        ],
      },
      {
        label: "Icons",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-icons",
        submenuItems: [
          {
            label: "Fontawesome Icons",
            link: routes.fantawesome,
            showSubRoute: false,
          },
          
          {
            label: "Ionic Icons",
            link: routes.iconicIcon,
            showSubRoute: false,
          },
          {
            label: "Material Icons",
            link: routes.materialIcon,
            showSubRoute: false,
          },
          { label: "Pe7 Icons", link: routes.pe7icon, showSubRoute: false },
         
          {
            label: "Themify Icons",
            link: routes.themifyIcon,
            showSubRoute: false,
          },
          {
            label: "Weather Icons",
            link: routes.weatherIcon,
            showSubRoute: false,
          },
          {
            label: "Typicon Icons",
            link: routes.typicon,
            showSubRoute: false,
          },
          { label: "Flag Icons", link: routes.falgIcons, showSubRoute: false },
        ],
      },
      {
        label: "Forms",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-input-search",
        submenuItems: [
          {
            label: "Form Elements",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              {
                label: "Basic Inputs",
                link: routes.basicInput,
                showSubRoute: false,
              },
              {
                label: "Checkbox & Radios",
                link: routes.checkboxandRadion,
                showSubRoute: false,
              },
              {
                label: "Input Groups",
                link: routes.inputGroup,
                showSubRoute: false,
              },
              {
                label: "Grid & Gutters",
                link: routes.gridandGutters,
                showSubRoute: false,
              },
              {
                label: "Form Select",
                link: routes.formSelect,
                showSubRoute: false,
              },
              {
                label: "Input Masks",
                link: routes.formMask,
                showSubRoute: false,
              },
              {
                label: "File Uploads",
                link: routes.fileUpload,
                showSubRoute: false,
              },
            ],
          },
          {
            label: "Layouts",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Horizontal Form", link: routes.horizontalForm },
              { label: "Vertical Form", link: routes.verticalForm },
              { label: "Floating Labels", link: routes.floatingLable },
            ],
          },
          { label: "Form Validation", link: routes.formValidation },
          { label: "Select", link: routes.reactSelect },
          // { label: "Form Wizard", link: routes.formWizard },
        ],
      },
      {
        label: "Tables",
        submenu: true,
        showSubRoute: false,
        icon: "ti ti-table-plus",
        submenuItems: [
          { label: "Basic Tables", link: routes.tableBasic },
          { label: "Data Table", link: routes.dataTable },
        ],
      },
    ],
  },
  {
    label: "Help",
    submenuOpen: true,
    showSubRoute: false,
    submenuHdr: "Help",
    submenuItems: [
      {
        label: "Documentation",
        link: "https://preschool.dreamstechnologies.com/documentation/index.html",
        icon: "ti ti-file-text",
        showSubRoute: false,
      },
      {
        label: "Changelog ",
        version: "v1.9.5",
        link: "https://preschool.dreamstechnologies.com/documentation/changelog.html",
        icon: "ti ti-exchange",
        showSubRoute: false,
      },
      {
        label: "Multi Level",
        showSubRoute: false,
        submenu: true,
        icon: "ti ti-menu-2",
        submenuItems: [
          { label: "Level 1.1", link: "#", showSubRoute: false },
          {
            label: "Level 1.2",
            submenu: true,
            showSubRoute: false,
            submenuItems: [
              { label: "Level 2.1", link: "#", showSubRoute: false },
              {
                label: "Level 2.2",
                submenu: true,
                showSubRoute: false,
                submenuItems: [
                  { label: "Level 3.1", link: "#", showSubRoute: false },
                  { label: "Level 3.2", link: "#", showSubRoute: false },
                ],
              },
            ],
          },
        ],
      },
    ],
  },
];
