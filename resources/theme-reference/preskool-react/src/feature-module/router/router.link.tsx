import { Navigate, Route } from "react-router";
import { all_routes } from "./all_routes";
import React from "react";
import Collapse from "../uiInterface/base-ui/collapse.tsx";
import Links from "../uiInterface/base-ui/links.tsx";
import ListGroup from "../uiInterface/base-ui/listGroup.tsx";
import DragDrop from "../uiInterface/advanced-ui/dragDrop.tsx";

// Lazy load all route components
const Login = React.lazy(() => import("../auth/login/login"));
const Register = React.lazy(() => import("../auth/register/register"));
const TwoStepVerification = React.lazy(() => import("../auth/twoStepVerification/twoStepVerification"));
const EmailVerification = React.lazy(() => import("../auth/emailVerification/emailVerification"));
const ResetPassword = React.lazy(() => import("../auth/resetPassword/resetPassword"));
const ForgotPassword = React.lazy(() => import("../auth/forgotPassword/forgotPassword"));
const Login2 = React.lazy(() => import("../auth/login/login-2"));
const Login3 = React.lazy(() => import("../auth/login/login-3"));
const ResetPassword2 = React.lazy(() => import("../auth/resetPassword/resetPassword-2"));
const ResetPassword3 = React.lazy(() => import("../auth/resetPassword/resetPassword-3"));
const TwoStepVerification2 = React.lazy(() => import("../auth/twoStepVerification/twoStepVerification-2"));
const TwoStepVerification3 = React.lazy(() => import("../auth/twoStepVerification/twoStepVerification-3"));
const Register2 = React.lazy(() => import("../auth/register/register-2"));
const Register3 = React.lazy(() => import("../auth/register/register-3"));
const ForgotPassword2 = React.lazy(() => import("../auth/forgotPassword/forgotPassword-2"));
const ForgotPassword3 = React.lazy(() => import("../auth/forgotPassword/forgotPassword-3"));
const ResetPasswordSuccess = React.lazy(() => import("../auth/resetPasswordSuccess/resetPasswordSuccess"));
const ResetPasswordSuccess2 = React.lazy(() => import("../auth/resetPasswordSuccess/resetPasswordSuccess-2"));
const ResetPasswordSuccess3 = React.lazy(() => import("../auth/resetPasswordSuccess/resetPasswordSuccess-3"));
const LockScreen = React.lazy(() => import("../auth/lockScreen"));
const EmailVerification2 = React.lazy(() => import("../auth/emailVerification/emailVerification-2"));
const EmailVerification3 = React.lazy(() => import("../auth/emailVerification/emailVerification-3"));
const AdminDashboard = React.lazy(() => import("../mainMenu/adminDashboard"));
const ParentDashboard = React.lazy(() => import("../mainMenu/parentDashboard"));
const TeacherDashboard = React.lazy(() => import("../mainMenu/teacherDashboard"));
const StudentDasboard = React.lazy(() => import("../mainMenu/studentDashboard"));
const AudioCall = React.lazy(() => import("../application/call/audioCall"));
const CallHistory = React.lazy(() => import("../application/call/callHistory"));
const Videocall = React.lazy(() => import("../application/call/videoCall"));
const Chat = React.lazy(() => import("../application/chat"));
const Email = React.lazy(() => import("../application/email"));
const FileManager = React.lazy(() => import("../application/fileManager"));
const Todo = React.lazy(() => import("../application/todo"));
const Calendar = React.lazy(() => import("../mainMenu/apps/calendar"));
const Notes = React.lazy(() => import("../application/notes"));
const Error404 = React.lazy(() => import("../pages/error/error-404"));
const Error500 = React.lazy(() => import("../pages/error/error-500"));
const AcademicReason = React.lazy(() => import("../academic/academic-reason"));
const ClassHomeWork = React.lazy(() => import("../academic/class-home-work"));
const ClassRoom = React.lazy(() => import("../academic/class-room"));
const ClassRoutine = React.lazy(() => import("../academic/class-routine"));
const ClassSection = React.lazy(() => import("../academic/class-section"));
const ClassSubject = React.lazy(() => import("../academic/class-subject"));
const ClassSyllabus = React.lazy(() => import("../academic/class-syllabus"));
const ClassTimetable = React.lazy(() => import("../academic/class-timetable"));
const Classes = React.lazy(() => import("../academic/classes"));
const Exam = React.lazy(() => import("../academic/examinations/exam"));
const ExamAttendance = React.lazy(() => import("../academic/examinations/exam-attendance"));
const ExamResult = React.lazy(() => import("../academic/examinations/exam-results"));
const ExamSchedule = React.lazy(() => import("../academic/examinations/exam-schedule"));
const Grade = React.lazy(() => import("../academic/examinations/grade"));
const ScheduleClasses = React.lazy(() => import("../academic/schedule-classes"));
const AccountsIncome = React.lazy(() => import("../accounts/accounts-income"));
const AccountsInvoices = React.lazy(() => import("../accounts/accounts-invoices"));
const AccountsTransactions = React.lazy(() => import("../accounts/accounts-transactions"));
const AddInvoice = React.lazy(() => import("../accounts/add-invoice"));
const EditInvoice = React.lazy(() => import("../accounts/edit-invoice"));
const Expense = React.lazy(() => import("../accounts/expense"));
const ExpensesCategory = React.lazy(() => import("../accounts/expenses-category"));
const Invoice = React.lazy(() => import("../accounts/invoice"));
const Events = React.lazy(() => import("../announcements/events"));
const NoticeBoard = React.lazy(() => import("../announcements/notice-board"));
const AllBlogs = React.lazy(() => import("../content/blog/allBlogs"));
const BlogCategories = React.lazy(() => import("../content/blog/blogCategories"));
const BlogComments = React.lazy(() => import("../content/blog/blogComments"));
const BlogTags = React.lazy(() => import("../content/blog/blogTags"));
const Faq = React.lazy(() => import("../content/faq"));
const Cities = React.lazy(() => import("../content/location/cities"));
const Countries = React.lazy(() => import("../content/location/countries"));
const States = React.lazy(() => import("../content/location/states"));
const Pages = React.lazy(() => import("../content/pages"));
const Testimonials = React.lazy(() => import("../content/testimonials"));
const StaffAttendance = React.lazy(() => import("../hrm/attendance/staff-attendance"));
const StudentAttendance = React.lazy(() => import("../hrm/attendance/student-attendance"));
const Departments = React.lazy(() => import("../hrm/departments"));
const Designation = React.lazy(() => import("../hrm/designation"));
const Holiday = React.lazy(() => import("../hrm/holidays"));
const ApproveRequest = React.lazy(() => import("../hrm/leaves/approve-request"));
const ListLeaves = React.lazy(() => import("../hrm/leaves/list-leaves"));
const Payroll = React.lazy(() => import("../hrm/payroll"));
const AddStaff = React.lazy(() => import("../hrm/staff-list/add-staff"));
const EditStaff = React.lazy(() => import("../hrm/staff-list/edit-staff"));
const Staff = React.lazy(() => import("../hrm/staff-list/staff"));
const StaffDetails = React.lazy(() => import("../hrm/staff-list/staff-details.tsx"));
const StaffLeave = React.lazy(() => import("../hrm/staff-list/staff-leave"));
const StaffPayRoll = React.lazy(() => import("../hrm/staff-list/staff-payroll.tsx"));
const StaffsAttendance = React.lazy(() => import("../hrm/staff-list/staffs-attendance"));
const CollectFees = React.lazy(() => import("../management/feescollection/collectFees"));
const FeesAssign = React.lazy(() => import("../management/feescollection/feesAssign"));
const FeesGroup = React.lazy(() => import("../management/feescollection/feesGroup"));
const FeesMaster = React.lazy(() => import("../management/feescollection/feesMaster"));
const FeesTypes = React.lazy(() => import("../management/feescollection/feesTypes"));
const HostelList = React.lazy(() => import("../management/hostel/hostelList"));
const HostelRooms = React.lazy(() => import("../management/hostel/hostelRooms"));
const HostelType = React.lazy(() => import("../management/hostel/hostelType"));
const Books = React.lazy(() => import("../management/library/books"));
const IssueBook = React.lazy(() => import("../management/library/issuesBook"));
const LibraryMember = React.lazy(() => import("../management/library/libraryMember"));
const ReturnBook = React.lazy(() => import("../management/library/returnBook"));
const PlayersList = React.lazy(() => import("../management/sports/playersList"));
const SportsList = React.lazy(() => import("../management/sports/sportsList"));
const TransportAssignVehicle = React.lazy(() => import("../management/transport/transportAssignVehicle"));
const TransportPickupPoints = React.lazy(() => import("../management/transport/transportPickupPoints"));
const TransportRoutes = React.lazy(() => import("../management/transport/transportRoutes"));
const TransportVehicle = React.lazy(() => import("../management/transport/transportVehicle"));
const TransportVehicleDrivers = React.lazy(() => import("../management/transport/transportVehicleDrivers"));
const MembershipAddon = React.lazy(() => import("../membership/membershipaddon"));
const Membershipplan = React.lazy(() => import("../membership/membershipplan"));
const MembershipTransaction = React.lazy(() => import("../membership/membershiptrasaction"));
const BlankPage = React.lazy(() => import("../pages/blankPage"));
const ComingSoon = React.lazy(() => import("../pages/comingSoon"));
const Profile = React.lazy(() => import("../pages/profile"));
const NotificationActivities = React.lazy(() => import("../pages/profile/activities"));
const UnderMaintenance = React.lazy(() => import("../pages/underMaintenance"));
const GuardianGrid = React.lazy(() => import("../peoples/guardian/guardian-grid"));
const GuardianList = React.lazy(() => import("../peoples/guardian/guardian-list"));
const ParentGrid = React.lazy(() => import("../peoples/parent/parent-grid"));
const ParentList = React.lazy(() => import("../peoples/parent/parent-list"));
const AddStudent = React.lazy(() => import("../peoples/students/add-student"));
const StudentDetails = React.lazy(() => import("../peoples/students/student-details/studentDetails"));
const StudentFees = React.lazy(() => import("../peoples/students/student-details/studentFees"));
const StudentLeaves = React.lazy(() => import("../peoples/students/student-details/studentLeaves"));
const StudentLibrary = React.lazy(() => import("../peoples/students/student-details/studentLibrary"));
const StudentResult = React.lazy(() => import("../peoples/students/student-details/studentResult"));
const StudentTimeTable = React.lazy(() => import("../peoples/students/student-details/studentTimeTable"));
const StudentList = React.lazy(() => import("../peoples/students/student-list"));
const StudentPromotion = React.lazy(() => import("../peoples/students/student-promotion"));
const TeacherDetails = React.lazy(() => import("../peoples/teacher/teacher-details/teacherDetails"));
const TeacherLeave = React.lazy(() => import("../peoples/teacher/teacher-details/teacherLeave"));
const TeacherLibrary = React.lazy(() => import("../peoples/teacher/teacher-details/teacherLibrary"));
const TeacherSalary = React.lazy(() => import("../peoples/teacher/teacher-details/teacherSalary"));
const TeachersRoutine = React.lazy(() => import("../peoples/teacher/teacher-details/teachersRoutine"));
const TeacherGrid = React.lazy(() => import("../peoples/teacher/teacher-grid"));
const TeacherList = React.lazy(() => import("../peoples/teacher/teacher-list"));
const TeacherForm = React.lazy(() => import("../peoples/teacher/teacherForm"));
const AttendanceReport = React.lazy(() => import("../report/attendance-report/attendanceReport"));
const DailyAttendance = React.lazy(() => import("../report/attendance-report/dailyAttendance"));
const StaffDayWise = React.lazy(() => import("../report/attendance-report/staffDayWise"));
const StaffReport = React.lazy(() => import("../report/attendance-report/staffReport"));
const StudentAttendanceType = React.lazy(() => import("../report/attendance-report/studentAttendanceType"));
const StudentDayWise = React.lazy(() => import("../report/attendance-report/studentDayWise"));
const TeacherDayWise = React.lazy(() => import("../report/attendance-report/teacherDayWise"));
const TeacherReport = React.lazy(() => import("../report/attendance-report/teacherReport"));
const ClassReport = React.lazy(() => import("../report/class-report/classReport"));
const FeesReport = React.lazy(() => import("../report/fees-report/feesReport"));
const GradeReport = React.lazy(() => import("../report/grade-report/gradeReport"));
const LeaveReport = React.lazy(() => import("../report/leave-report/leaveReport"));
const StudentReport = React.lazy(() => import("../report/student-report/studentReport"));
const Religion = React.lazy(() => import("../settings/academicSettings/religion"));
const SchoolSettings = React.lazy(() => import("../settings/academicSettings/schoolSettings"));
const CustomFields = React.lazy(() => import("../settings/appSettings/customFields"));
const InvoiceSettings = React.lazy(() => import("../settings/appSettings/invoiceSettings"));
const PaymentGateways = React.lazy(() => import("../settings/financialSettings/paymentGateways"));
const TaxRates = React.lazy(() => import("../settings/financialSettings/taxRates"));
const ConnectedApps = React.lazy(() => import("../settings/generalSettings/connectedApps"));
const Notificationssettings = React.lazy(() => import("../settings/generalSettings/notifications"));
const Profilesettings = React.lazy(() => import("../settings/generalSettings/profile"));
const Securitysettings = React.lazy(() => import("../settings/generalSettings/security"));
const BanIpAddress = React.lazy(() => import("../settings/otherSettings/banIpaddress"));
const Emailtemplates = React.lazy(() => import("../settings/systemSettings/email-templates"));
const EmailSettings = React.lazy(() => import("../settings/systemSettings/emailSettings"));
const GdprCookies = React.lazy(() => import("../settings/systemSettings/gdprCookies"));
const OtpSettings = React.lazy(() => import("../settings/systemSettings/otp-settings"));
const SmsSettings = React.lazy(() => import("../settings/systemSettings/smsSettings"));
const CompanySettings = React.lazy(() => import("../settings/websiteSettings/companySettings"));
const Languagesettings = React.lazy(() => import("../settings/websiteSettings/language"));
const Localization = React.lazy(() => import("../settings/websiteSettings/localization"));
const Preference = React.lazy(() => import("../settings/websiteSettings/preference"));
const Prefixes = React.lazy(() => import("../settings/websiteSettings/prefixes"));
const Socialauthentication = React.lazy(() => import("../settings/websiteSettings/socialAuthentication"));
const ContactMessages = React.lazy(() => import("../support/contactMessages"));
const TicketDetails = React.lazy(() => import("../support/ticket-details"));
const TicketGrid = React.lazy(() => import("../support/ticket-grid"));
const Tickets = React.lazy(() => import("../support/tickets"));
const ClipBoard = React.lazy(() => import("../uiInterface/advanced-ui/clipboard"));
const Scrollbar = React.lazy(() => import("../uiInterface/advanced-ui/uiscrollbar"));
const AlertUi = React.lazy(() => import("../uiInterface/base-ui/alert-ui"));
const Badges = React.lazy(() => import("../uiInterface/base-ui/badges"));
const Buttons = React.lazy(() => import("../uiInterface/base-ui/buttons"));
const ButtonsGroup = React.lazy(() => import("../uiInterface/base-ui/buttonsgroup"));
const Cards = React.lazy(() => import("../uiInterface/base-ui/cards"));
const Dropdowns = React.lazy(() => import("../uiInterface/base-ui/dropdowns"));
const Images = React.lazy(() => import("../uiInterface/base-ui/images"));
const Lightboxes = React.lazy(() => import("../uiInterface/base-ui/lightbox"));
const Modals = React.lazy(() => import("../uiInterface/base-ui/modals"));
const NavTabs = React.lazy(() => import("../uiInterface/base-ui/navtabs"));
const Popovers = React.lazy(() => import("../uiInterface/base-ui/popover"));
const Tooltips = React.lazy(() => import("../uiInterface/base-ui/tooltips"));
const Apexchart = React.lazy(() => import("../uiInterface/charts/apexcharts"));
const BasicInputs = React.lazy(() => import("../uiInterface/forms/formelements/basic-inputs"));
const CheckboxRadios = React.lazy(() => import("../uiInterface/forms/formelements/checkbox-radios"));
const FileUpload = React.lazy(() => import("../uiInterface/forms/formelements/fileupload"));
const FormMask = React.lazy(() => import("../uiInterface/forms/formelements/form-mask"));
const FormWizard = React.lazy(() => import("../uiInterface/forms/formelements/form-wizard"));
const GridGutters = React.lazy(() => import("../uiInterface/forms/formelements/grid-gutters"));
const FormHorizontal = React.lazy(() => import("../uiInterface/forms/formelements/layouts/form-horizontal"));
const FormSelect2 = React.lazy(() => import("../uiInterface/forms/formelements/layouts/form-select2"));
const FormValidation = React.lazy(() => import("../uiInterface/forms/formelements/layouts/form-validation"));
const FormVertical = React.lazy(() => import("../uiInterface/forms/formelements/layouts/form-vertical"));
const FontawesomeIcons = React.lazy(() => import("../uiInterface/icons/fontawesome"));
const IonicIcons = React.lazy(() => import("../uiInterface/icons/ionicicons"));
const MaterialIcons = React.lazy(() => import("../uiInterface/icons/materialicon"));
const PE7Icons = React.lazy(() => import("../uiInterface/icons/pe7icons"));
const ThemifyIcons = React.lazy(() => import("../uiInterface/icons/themify"));
const TypiconIcons = React.lazy(() => import("../uiInterface/icons/typicons"));
const WeatherIcons = React.lazy(() => import("../uiInterface/icons/weathericons"));
const DataTables = React.lazy(() => import("../uiInterface/table/data-tables"));
const TablesBasic = React.lazy(() => import("../uiInterface/table/tables-basic"));
const DeleteRequest = React.lazy(() => import("../userManagement/deleteRequest"));
const Manageusers = React.lazy(() => import("../userManagement/manageusers"));
const Permission = React.lazy(() => import("../userManagement/permission"));
const RolesPermissions = React.lazy(() => import("../userManagement/rolesPermissions"));
const Accordion = React.lazy(() => import("../uiInterface/base-ui/accordion"));
const Avatar = React.lazy(() => import("../uiInterface/base-ui/avatar"));
const Breadcrumb = React.lazy(() => import("../uiInterface/base-ui/breadcrumb"));
const Carousel = React.lazy(() => import("../uiInterface/base-ui/carousel"));
const Offcanvas = React.lazy(() => import("../uiInterface/base-ui/offcanvas"));
const Pagination = React.lazy(() => import("../uiInterface/base-ui/pagination"));
const Progress = React.lazy(() => import("../uiInterface/base-ui/progress"));
const Spinner = React.lazy(() => import("../uiInterface/base-ui/spinner"));
const Typography = React.lazy(() => import("../uiInterface/base-ui/typography"));
const InputGroup = React.lazy(() => import("../uiInterface/forms/formelements/input-group"));
const FormSelect = React.lazy(() => import("../uiInterface/forms/formelements/form-select"));
const Placeholder = React.lazy(() => import("../uiInterface/base-ui/placeholder"));
const StudentGrid = React.lazy(() => import("../peoples/students/student-grid/index.tsx"));
const Storage = React.lazy(() => import("../settings/otherSettings/storage.tsx"));
const TeacherAttendance = React.lazy(() => import("../hrm/attendance/teacher-attendance.tsx"));

const routes = all_routes;

export const publicRoutes = [
  {
    path: "/",
    name: "Root",
    element: <Navigate to="/login" />,
    route: Route,
  },
  {
    path: routes.adminDashboard,
    element: <AdminDashboard />,
    route: Route,
  },
  {
    path: routes.teacherDashboard,
    element: <TeacherDashboard />,
    route: Route,
  },
  {
    path: routes.studentDashboard,
    element: <StudentDasboard />,
    route: Route,
  },
  {
    path: routes.parentDashboard,
    element: <ParentDashboard />,
    route: Route,
  },
  {
    path: routes.audioCall,
    element: <AudioCall />,
    route: Route,
  },
  {
    path: routes.callHistory,
    element: <CallHistory />,
    route: Route,
  },
  {
    path: routes.callHistory,
    element: <CallHistory />,
    route: Route,
  },

  {
    path: routes.connectedApps,
    element: <ConnectedApps />,
    route: Route,
  },
  {
    path: routes.countries,
    element: <Countries />,
    route: Route,
  },
  {
    path: routes.blankPage,
    element: <BlankPage />,
    route: Route,
  },
  {
    path: routes.calendar,
    element: <Calendar />,
    route: Route,
  },

  {
    path: routes.membershipplan,
    element: <Membershipplan />,
  },
  {
    path: routes.membershipAddon,
    element: <MembershipAddon />,
  },
  {
    path: routes.membershipTransaction,
    element: <MembershipTransaction />,
  },
  {
    path: routes.notes,
    element: <Notes />,
  },
  {
    path: routes.countries,
    element: <Countries />,
    route: Route,
  },
  {
    path: routes.customFields,
    element: <CustomFields />,
    route: Route,
  },
  // {
  //   path: routes.dataTables,
  //   element: <DataTable />,
  //   route: Route,
  // },
  // {
  //   path: routes.tablesBasic,
  //   element: <BasicTable />,
  //   route: Route,
  // },

  {
    path: routes.deleteRequest,
    element: <DeleteRequest />,
    route: Route,
  },
  {
    path: routes.cities,
    element: <Cities />,
    route: Route,
  },

  {
    path: routes.accordion,
    element: <Accordion />,
    route: Route,
  },
  {
    path: routes.avatar,
    element: <Avatar />,
    route: Route,
  },
  {
    path: routes.badges,
    element: <Badges />,
    route: Route,
  },
  
  {
    path: routes.breadcrums,
    element: <Breadcrumb />,
    route: Route,
  },
  {
    path: routes.button,
    element: <Buttons />,
    route: Route,
  },
  {
    path: routes.buttonGroup,
    element: <ButtonsGroup />,
    route: Route,
  },
  {
    path: routes.cards,
    element: <Cards />,
    route: Route,
  },
  {
    path: routes.carousel,
    element: <Carousel />,
    route: Route,
  },

  {
    path: routes.dropdowns,
    element: <Dropdowns />,
    route: Route,
  },
  // {
  //   path: routes.grid,
  //   element: <Grid />,
  //   route: Route,
  // },
  {
    path: routes.images,
    element: <Images />,
    route: Route,
  },
  {
    path: routes.lightbox,
    element: <Lightboxes />,
    route: Route,
  },
  {
    path: routes.modals,
    element: <Modals />,
    route: Route,
  },
  {
    path: routes.navTabs,
    element: <NavTabs />,
    route: Route,
  },
  {
    path: routes.offcanvas,
    element: <Offcanvas />,
    route: Route,
  },
  {
    path: routes.pagination,
    element: <Pagination />,
    route: Route,
  },
  {
    path: routes.popover,
    element: <Popovers />,
    route: Route,
  },
  {
    path: routes.progress,
    element: <Progress />,
    route: Route,
  },
  {
    path: routes.spinner,
    element: <Spinner />,
    route: Route,
  },

  {
    path: routes.typography,
    element: <Typography />,
    route: Route,
  },
  {
    path: routes.banIpAddress,
    element: <BanIpAddress />,
    route: Route,
  },
  // {
  //   path: routes.localization,
  //   element: <Localization />,
  //   route: Route,
  // },
  {
    path: routes.preference,
    element: <Preference />,
    route: Route,
  },
  {
    path: routes.todo,
    element: <Todo />,
    route: Route,
  },
  {
    path: routes.email,
    element: <Email />,
    route: Route,
  },
  {
    path: routes.videoCall,
    element: <Videocall />,
    route: Route,
  },
  {
    path: routes.chat,
    element: <Chat />,
    route: Route,
  },
  {
    path: routes.pages,
    element: <Pages />,
    route: Route,
  },

  {
    path: routes.fileManager,
    element: <FileManager />,
    route: Route,
  },
  {
    path: routes.faq,
    element: <Faq />,
    route: Route,
  },

  {
    path: routes.states,
    element: <States />,
    route: Route,
  },
  {
    path: routes.testimonials,
    element: <Testimonials />,
    route: Route,
  },
  {
    path: routes.clipboard,
    element: <ClipBoard />,
    route: Route,
  },
  {
    path: routes.scrollBar,
    element: <Scrollbar />,
    route: Route,
  },
  {
    path: routes.apexChat,
    element: <Apexchart />,
    route: Route,
  },
 
  {
    path: routes.fantawesome,
    element: <FontawesomeIcons />,
    route: Route,
  },
  {
    path: routes.fantawesome,
    element: <FontawesomeIcons />,
    route: Route,
  },
  {
    path: routes.materialIcon,
    element: <MaterialIcons />,
    route: Route,
  },
  {
    path: routes.pe7icon,
    element: <PE7Icons />,
    route: Route,
  },
 
  {
    path: routes.themifyIcon,
    element: <ThemifyIcons />,
    route: Route,
  },
  {
    path: routes.typicon,
    element: <TypiconIcons />,
    route: Route,
  },
  {
    path: routes.basicInput,
    element: <BasicInputs />,
    route: Route,
  },
  {
    path: routes.weatherIcon,
    element: <WeatherIcons />,
    route: Route,
  },
  {
    path: routes.checkboxandRadion,
    element: <CheckboxRadios />,
    route: Route,
  },
  {
    path: routes.inputGroup,
    element: <InputGroup />,
    route: Route,
  },
  {
    path: routes.gridandGutters,
    element: <GridGutters />,
    route: Route,
  },
  {
    path: routes.formSelect,
    element: <FormSelect />,
    route: Route,
  },
  {
    path: routes.formMask,
    element: <FormMask />,
    route: Route,
  },
  {
    path: routes.fileUpload,
    element: <FileUpload />,
    route: Route,
  },
  {
    path: routes.horizontalForm,
    element: <FormHorizontal />,
    route: Route,
  },
  {
    path: routes.verticalForm,
    element: <FormVertical />,
    route: Route,
  },

  {
    path: routes.formValidation,
    element: <FormValidation />,
    route: Route,
  },
  {
    path: routes.reactSelect,
    element: <FormSelect2 />,
    route: Route,
  },
  {
    path: routes.formWizard,
    element: <FormWizard />,
    route: Route,
  },
  {
    path: routes.dataTable,
    element: <DataTables />,
    route: Route,
  },
  {
    path: routes.tableBasic,
    element: <TablesBasic />,
    route: Route,
  },
  {
    path: routes.iconicIcon,
    element: <IonicIcons />,
    route: Route,
  },


  {
    path: routes.placeholder,
    element: <Placeholder />,
    route: Route,
  },

  {
    path: routes.alert,
    element: <AlertUi />,
    route: Route,
  },
  {
    path: routes.tooltip,
    element: <Tooltips />,
    route: Route,
  },

  // Peoples Module
  {
    path: routes.studentGrid,
    element: <StudentGrid/>,
    route: Route,
  },
  {
    path: routes.studentList,
    element: <StudentList />,
       route: Route,
  },
  {
    path: routes.addStudent,
    element: <AddStudent />,
       route: Route,
  },
  {
    path: routes.editStudent,
    element: <AddStudent />,
       route: Route,
  },
  {
    path: routes.studentLibrary,
    element: <StudentLibrary />,
       route: Route,
  },
  {
    path: routes.studentDetail,
    element: <StudentDetails />,
       route: Route,
  },
  {
    path: routes.studentFees,
    element: <StudentFees />,
       route: Route,
  },
  {
    path: routes.studentLeaves,
    element: <StudentLeaves />,
       route: Route,
  },
  {
    path: routes.studentResult,
    element: <StudentResult />,
       route: Route,
  },
  {
    path: routes.studentTimeTable,
    element: <StudentTimeTable />,
       route: Route,
  },
  {
    path: routes.studentPromotion,
    element: <StudentPromotion />,
       route: Route,
  },
  {
    path: routes.AcademicReason,
    element: <AcademicReason />,
       route: Route,
  },
  {
    path: routes.classSyllabus,
    element: <ClassSyllabus />,
       route: Route,
  },
  {
    path: routes.classSubject,
    element: <ClassSubject />,
       route: Route,
  },
  {
    path: routes.classSection,
    element: <ClassSection />,
       route: Route,
  },
  {
    path: routes.classRoom,
    element: <ClassRoom />,
       route: Route,
    
  },
  {
    path: routes.classRoutine,
    element: <ClassRoutine />,
       route: Route,
    
  },
  {
    path: routes.sheduleClasses,
    element: <ScheduleClasses />,
       route: Route,
  },
  
  {
    path: routes.exam,
    element: <Exam />,
       route: Route,
  },
  {
    path: routes.examSchedule,
    element: <ExamSchedule />,
       route: Route,
    
  },
  {
    path: routes.grade,
    element: <Grade />,
       route: Route,
  },
  {
    path: routes.staff,
    element: <Staff />,
       route: Route,
  },
  {
    path: routes.departments,
    element: <Departments />,
       route: Route,
  },
  {
    path: routes.classes,
    element: <Classes />,
       route: Route,
  },
  {
    path: routes.classHomeWork,
    element: <ClassHomeWork />,
       route: Route,
  },
  {
    path: routes.examResult,
    element: <ExamResult />,
       route: Route,
  },
  {
    path: routes.examAttendance,
    element: <ExamAttendance />,
       route: Route,
  },
  {
    path: routes.teacherGrid,
    element: <TeacherGrid />,
       route: Route,
  },
  {
    path: routes.teacherList,
    element: <TeacherList />,
       route: Route,
  },
  {
    path: routes.addTeacher,
    element: <TeacherForm />,
       route: Route,
  },
  {
    path: routes.editTeacher,
    element: <TeacherForm />,
       route: Route,
  },
  {
    path: routes.teacherDetails,
    element: <TeacherDetails />,
       route: Route,
  },
  {
    path: routes.teachersRoutine,
    element: <TeachersRoutine />,
       route: Route,
  },
  {
    path: routes.teacherSalary,
    element: <TeacherSalary />,
       route: Route,
  },
  {
    path: routes.teacherLeaves,
    element: <TeacherLeave />,
       route: Route,
  },
  {
    path: routes.teacherLibrary,
    element: <TeacherLibrary />,
       route: Route,
  },
  {
    path: routes.parentGrid,
    element: <ParentGrid />,
       route: Route,
  },
  {
    path: routes.parentList,
    element: <ParentList />,
       route: Route,
  },
  {
    path: routes.classTimetable,
    element: <ClassTimetable />,
       route: Route,
  },
  {
    path: routes.payroll,
    element: <Payroll />,
       route: Route,
  },
  {
    path: routes.holidays,
    element: <Holiday />,
       route: Route,
  },
  {
    path: routes.designation,
    element: <Designation />,
       route: Route,
  },
  {
    path: routes.listLeaves,
    element: <ListLeaves />,
       route: Route,
  },
  {
    path: routes.staffDetails,
    element: <StaffDetails />,
       route: Route,
  },
  {
    path: routes.staffPayroll,
    element: <StaffPayRoll />,
       route: Route,
  },
  {
    path: routes.staffLeave,
    element: <StaffLeave />,
       route: Route,
  },

  {
    path: routes.layoutDefault,
    element: <AdminDashboard />,
       route: Route,
  },
  {
    path: routes.layoutMini,
    element: <AdminDashboard />,
       route: Route,
  },
  {
    path: routes.layoutRtl,
    element: <AdminDashboard />,
       route: Route,
  },
  {
    path: routes.layoutBox,
    element: <AdminDashboard />,
       route: Route,
  },
  {
    path: routes.layoutDark,
    element: <AdminDashboard />,
       route: Route,
  },
  {
    path: routes.guardiansGrid,
    element: <GuardianGrid />,
       route: Route,
  },
  {
    path: routes.guardiansList,
    element: <GuardianList />,
       route: Route,
  },
  {
    path: routes.feesGroup,
    element: <FeesGroup />,
       route: Route,
  },
  {
    path: routes.feesType,
    element: <FeesTypes />,
       route: Route,
  },
  {
    path: routes.feesMaster,
    element: <FeesMaster />,
       route: Route,
  },
  {
    path: routes.feesAssign,
    element: <FeesAssign />,
       route: Route,
  },
  {
    path: routes.collectFees,
    element: <CollectFees />,
       route: Route,
  },
  {
    path: routes.libraryMembers,
    element: <LibraryMember />,
       route: Route,
  },
  {
    path: routes.libraryBooks,
    element: <Books />,
       route: Route,
  },
  {
    path: routes.libraryIssueBook,
    element: <IssueBook />,
       route: Route,
  },
  {
    path: routes.libraryReturn,
    element: <ReturnBook />,
       route: Route,
  },
  {
    path: routes.sportsList,
    element: <SportsList />,
       route: Route,
  },
  {
    path: routes.playerList,
    element: <PlayersList />,
       route: Route,
  },
  {
    path: routes.hostelRoom,
    element: <HostelRooms />,
       route: Route,
  },
  {
    path: routes.hostelType,
    element: <HostelType />,
       route: Route,
  },
  {
    path: routes.hostelList,
    element: <HostelList />,
       route: Route,
  },
  {
    path: routes.transportRoutes,
    element: <TransportRoutes />,
       route: Route,
  },
  {
    path: routes.transportAssignVehicle,
    element: <TransportAssignVehicle />,
       route: Route,
  },
  {
    path: routes.transportPickupPoints,
    element: <TransportPickupPoints />,
       route: Route,
  },
  {
    path: routes.transportVehicleDrivers,
    element: <TransportVehicleDrivers />,
       route: Route,
  },
  {
    path: routes.transportVehicle,
    element: <TransportVehicle />,
       route: Route,
  },
  {
    path: routes.approveRequest,
    element: <ApproveRequest />,
       route: Route,
  },
  {
    path: routes.studentAttendance,
    element: <StudentAttendance />,
       route: Route,
  },
  {
    path: routes.teacherAttendance,
    element: <TeacherAttendance />,
       route: Route,
  },


  {
    path: routes.staffAttendance,
    element: <StaffAttendance />,
       route: Route,
  },
  {
    path: routes.staffsAttendance,
    element: <StaffsAttendance />,
       route: Route,
  },
  {
    path: routes.addStaff,
    element: <AddStaff />,
       route: Route,
  },
  {
    path: routes.editStaff,
    element: <EditStaff />,
       route: Route,
  },

  {
    path: routes.accountsIncome,
    element: <AccountsIncome />,
       route: Route,
  },
  {
    path: routes.accountsInvoices,
    element: <AccountsInvoices />,
       route: Route,
  },
  {
    path: routes.accountsTransactions,
    element: <AccountsTransactions />,
       route: Route,
  },
  {
    path: routes.addInvoice,
    element: <AddInvoice />,
       route: Route,
  },
  {
    path: routes.editInvoice,
    element: <EditInvoice />,
       route: Route,
  },
  {
    path: routes.guardiansList,
    element: <GuardianList />,
       route: Route,
  },
  {
    path: routes.expense,
    element: <Expense />,
       route: Route,
  },
  {
    path: routes.expenseCategory,
    element: <ExpensesCategory />,
       route: Route,
  },
  {
    path: routes.invoice,
    element: <Invoice />,
       route: Route,
  },
  {
    path: routes.events,
    element: <Events />,
       route: Route,
  },
  {
    path: routes.noticeBoard,
    element: <NoticeBoard />,
       route: Route,
  },

  //Settings

  {
    path: routes.profilesettings,
    element: <Profilesettings />,
       route: Route,
  },
  {
    path: routes.securitysettings,
    element: <Securitysettings />,
       route: Route,
  },
  {
    path: routes.notificationssettings,
    element: <Notificationssettings />,
       route: Route,
  },
  {
    path: routes.connectedApps,
    element: <ConnectedApps />,
       route: Route,
  },
  {
    path: routes.companySettings,
    element: <CompanySettings />,
       route: Route,
  },
  {
    path: routes.localization,
    element: <Localization />,
       route: Route,
  },
  {
    path: routes.prefixes,
    element: <Prefixes />,
       route: Route,
  },
  {
    path: routes.socialAuthentication,
    element: <Socialauthentication />,
       route: Route,
  },
  {
    path: routes.language,
    element: <Languagesettings />,
       route: Route,
  },
  {
    path: routes.invoiceSettings,
    element: <InvoiceSettings />,
       route: Route,
  },
  {
    path: routes.customFields,
    element: <CustomFields />,
       route: Route,
  },
  {
    path: routes.emailSettings,
    element: <EmailSettings />,
       route: Route,
  },
  {
    path: routes.emailTemplates,
    element: <Emailtemplates />,
       route: Route,
  },
  {
    path: routes.smsSettings,
    element: <SmsSettings />,
       route: Route,
  },
  {
    path: routes.optSettings,
    element: <OtpSettings />,
       route: Route,
  },
  {
    path: routes.gdprCookies,
    element: <GdprCookies />,
       route: Route,
  },

  {
    path: routes.paymentGateways,
    element: <PaymentGateways />,
       route: Route,
  },
  {
    path: routes.taxRates,
    element: <TaxRates />,
       route: Route,
  },
  {
    path: routes.schoolSettings,
    element: <SchoolSettings />,
       route: Route,
  },
  {
    path: routes.religion,
    element: <Religion />,
       route: Route,
  },
  {
    path: routes.storage,
    element: <Storage />,
       route: Route,
  },
  
  {
    path: routes.rolesPermissions,
    element: <RolesPermissions />,
       route: Route,
  },
  {
    path: routes.permissions,
    element: <Permission />,
       route: Route,
  },
  {
    path: routes.manageusers,
    element: <Manageusers />,
       route: Route,
  },
  {
    path: routes.allBlogs,
    element: <AllBlogs />,
       route: Route,
  },
  {
    path: routes.blogCategories,
    element: <BlogCategories />,
       route: Route,
  },
  {
    path: routes.blogComments,
    element: <BlogComments />,
       route: Route,
  },
  {
    path: routes.blogTags,
    element: <BlogTags />,
       route: Route,
  },
  {
    path: routes.tickets,
    element: <Tickets />,
       route: Route,
  },
  {
    path: routes.ticketGrid,
    element: <TicketGrid />,
       route: Route,
  },
  {
    path: routes.ticketDetails,
    element: <TicketDetails />,
       route: Route,
  },
  {
    path: routes.feesReport,
    element: <FeesReport />,
       route: Route,
  },
  {
    path: routes.leaveReport,
    element: <LeaveReport />,
       route: Route,
  },
  {
    path: routes.gradeReport,
    element: <GradeReport />,
       route: Route,
  },
  {
    path: routes.studentReport,
    element: <StudentReport />,
       route: Route,
  },
  {
    path: routes.classReport,
    element: <ClassReport />,
       route: Route,
  },
  {
    path: routes.attendanceReport,
    element: <AttendanceReport />,
       route: Route,
  },
  {
    path: routes.studentAttendanceType,
    element: <StudentAttendanceType />,
       route: Route,
  },
  {
    path: routes.dailyAttendance,
    element: <DailyAttendance />,
       route: Route,
  },
  {
    path: routes.studentDayWise,
    element: <StudentDayWise />,
       route: Route,
  },
  {
    path: routes.teacherDayWise,
    element: <TeacherDayWise />,
       route: Route,
  },
  {
    path: routes.staffDayWise,
    element: <StaffDayWise />,
       route: Route,
  },
  {
    path: routes.teacherReport,
    element: <TeacherReport />,
       route: Route,
  },
  {
    path: routes.staffReport,
    element: <StaffReport />,
       route: Route,
  },
  {
    path: routes.contactMessages,
    element: <ContactMessages />,
       route: Route,
  },
  {
    path: routes.events,
    element: <Events />,
       route: Route,
  },
  {
    path: routes.profile,
    element: <Profile />,
       route: Route,
  },
  {
    path: routes.activity,
    element: <NotificationActivities />,
       route: Route,
  },
  {
    path: routes.uiCollapse,
    element: <Collapse />,
       route: Route,
  },
  {
    path: routes.uiLinks,
    element: <Links />,
       route: Route,
  },
  {
    path: routes.uiListGroup,
    element: <ListGroup />,
       route: Route,
  },
  {
    path: routes.dragandDrop,
    element: < DragDrop/>,
       route: Route,
  },
];

export const authRoutes = [
  {
    path: routes.comingSoon,
    element: <ComingSoon />,
    route: Route,
  },
  {
    path: routes.login,
    element: <Login />,
    route: Route,
  },
  {
    path: routes.login2,
    element: <Login2 />,
    route: Route,
  },
  {
    path: routes.login3,
    element: <Login3 />,
    route: Route,
  },
  {
    path: routes.register,
    element: <Register />,
    route: Route,
  },
  {
    path: routes.twoStepVerification,
    element: <TwoStepVerification />,
    route: Route,
  },
  {
    path: routes.twoStepVerification2,
    element: <TwoStepVerification2 />,
    route: Route,
  },
  {
    path: routes.twoStepVerification3,
    element: <TwoStepVerification3 />,
    route: Route,
  },
  {
    path: routes.emailVerification,
    element: <EmailVerification />,
    route: Route,
  },
  {
    path: routes.emailVerification2,
    element: <EmailVerification2 />,
    route: Route,
  },
  {
    path: routes.emailVerification3,
    element: <EmailVerification3 />,
    route: Route,
  },
  {
    path: routes.register,
    element: <Register />,
    route: Route,
  },
  {
    path: routes.register2,
    element: <Register2 />,
    route: Route,
  },
  {
    path: routes.register3,
    element: <Register3 />,
    route: Route,
  },
  {
    path: routes.resetPassword,
    element: <ResetPassword />,
    route: Route,
  },
  {
    path: routes.resetPassword2,
    element: <ResetPassword2 />,
    route: Route,
  },
  {
    path: routes.resetPassword3,
    element: <ResetPassword3 />,
    route: Route,
  },
  {
    path: routes.forgotPassword,
    element: <ForgotPassword />,
    route: Route,
  },
  {
    path: routes.forgotPassword2,
    element: <ForgotPassword2 />,
    route: Route,
  },
  {
    path: routes.forgotPassword3,
    element: <ForgotPassword3 />,
    route: Route,
  },
  {
    path: routes.error404,
    element: <Error404 />,
    route: Route,
  },
  {
    path: routes.error500,
    element: <Error500 />,
    route: Route,
  },
  {
    path: routes.underMaintenance,
    element: <UnderMaintenance />,
    route: Route,
  },
  {
    path: routes.lockScreen,
    element: <LockScreen />,
  },
  {
    path: routes.resetPasswordSuccess,
    element: <ResetPasswordSuccess />,
  },
  {
    path: routes.resetPasswordSuccess2,
    element: <ResetPasswordSuccess2 />,
  },
  {
    path: routes.resetPasswordSuccess3,
    element: <ResetPasswordSuccess3 />,
  },
];
