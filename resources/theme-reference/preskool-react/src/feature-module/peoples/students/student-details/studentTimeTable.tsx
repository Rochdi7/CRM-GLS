
import { Link } from 'react-router-dom'
import ImageWithBasePath from '../../../../core/common/imageWithBasePath'
import { all_routes } from '../../../router/all_routes'
import StudentModals from '../studentModals'
import StudentSidebar from './studentSidebar'
import StudentBreadcrumb from './studentBreadcrumb'

const StudentTimeTable = () => {
    const routes = all_routes
  return (
    <>
  {/* Page Wrapper */}
  <div className="page-wrapper">
    <div className="content">
      <div className="row">
        {/* Page Header */}
        <StudentBreadcrumb />
        {/* /Page Header */}
      </div>
      <div className="row">
        {/* Student Information */}
        <StudentSidebar />
        {/* /Student Information */}
        <div className="col-xxl-9 col-xl-8">
        <div className="row">
            <div className="col-md-12">
                {/* List */}
                <ul className="nav nav-tabs nav-tabs-bottom mb-4">
                <li>
                  <Link to={routes.studentDetail} className="nav-link">
                    <i className="ti ti-school me-2" />
                    Student Details
                  </Link>
                </li>
                <li>
                  <Link to={routes.studentTimeTable} className="nav-link active">
                    <i className="ti ti-table-options me-2" />
                    Time Table
                  </Link>
                </li>
                <li>
                  <Link to={routes.studentLeaves} className="nav-link">
                    <i className="ti ti-calendar-due me-2" />
                    Leave &amp; Attendance
                  </Link>
                </li>
                <li>
                  <Link to={routes.studentFees} className="nav-link">
                    <i className="ti ti-report-money me-2" />
                    Fees
                  </Link>
                </li>
                <li>
                  <Link to={routes.studentResult} className="nav-link">
                    <i className="ti ti-bookmark-edit me-2" />
                    Exam &amp; Results
                  </Link>
                </li>
                <li>
                  <Link to={routes.studentLibrary} className="nav-link">
                    <i className="ti ti-books me-2" />
                    Library
                  </Link>
                </li>
              </ul>
                {/* /List */}
                <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    <h4 className="mb-3">Exams &amp; Results</h4>
                    <div className="d-flex align-items-center flex-wrap">
                    <div className="dropdown mb-3">
                        <Link
                        to="#"
                        className="btn btn-outline-light border-white bg-white dropdown-toggle shadow-md"
                        data-bs-toggle="dropdown"
                        >
                        <i className="ti ti-calendar-due me-2" />
                        This Year
                        </Link>
                        <ul className="dropdown-menu p-3">
                        <li>
                            <Link
                            to="#"
                            className="dropdown-item rounded-1"
                            >
                            This Year
                            </Link>
                        </li>
                        <li>
                            <Link
                            to="#"
                            className="dropdown-item rounded-1"
                            >
                            This Month
                            </Link>
                        </li>
                        <li>
                            <Link
                            to="#"
                            className="dropdown-item rounded-1"
                            >
                            This Week
                            </Link>
                        </li>
                        </ul>
                    </div>
                    </div>
                </div>
                <div className="card-body pb-0">
                    <div className="d-flex flex-nowrap overflow-auto">
                    <div className="d-flex flex-column me-4 flex-fill">
                        <div className="mb-3">
                        <h6>Monday</h6>
                        </div>
                        <div className="bg-transparent-danger rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:00 - 09:45 AM
                        </p>
                        <p className="text-dark">Subject : Maths</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-07.jpg" alt="Img" />
                            </span>
                            Jacquelin
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-primary rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:45 - 10:30 AM
                        </p>
                        <p className="text-dark">Subject : English</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Hellana
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-success rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            10:45 - 11:30 AM
                        </p>
                        <p className="text-dark">Subject : Computer</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-02.jpg" alt="Img" />
                            </span>
                            Daniel
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-pending rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            11:30 - 12:15 PM
                        </p>
                        <p className="text-dark">Subject : Spanish</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Erickson
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-info rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            01:30 - 02:15 PM
                        </p>
                        <p className="text-dark">Subject : Science</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-05.jpg" alt="Img" />
                            </span>
                            Morgan
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-light rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            02:15 - 03:00 PM
                        </p>
                        <p className="text-dark">Subject : Chemistry</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-06.jpg" alt="Img" />
                            </span>
                            Aaron
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-warning rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            03:15 - 04:00 PM
                        </p>
                        <p className="text-dark">Subject : Physics</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-01.jpg" alt="Img" />
                            </span>
                            Teresa
                            </Link>
                        </div>
                        </div>
                    </div>
                    <div className="d-flex flex-column me-4 flex-fill">
                        <div className="mb-3">
                        <h6>Tuesday</h6>
                        </div>
                        <div className="bg-transparent-pending rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:00 - 09:45 AM
                        </p>
                        <p className="text-dark">Subject : Spanish</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Erickson
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-warning rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:45 - 10:30 AM
                        </p>
                        <p className="text-dark">Subject : Physics</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-01.jpg" alt="Img" />
                            </span>
                            Teresa
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-light rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            10:45 - 11:30 AM
                        </p>
                        <p className="text-dark">Subject : Chemistry</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-06.jpg" alt="Img" />
                            </span>
                            Aaron
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-danger rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            11:30 - 12:15 PM
                        </p>
                        <p className="text-dark">Subject : Maths</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-07.jpg" alt="Img" />
                            </span>
                            Jacquelin
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-success rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            01:30 - 02:15 PM
                        </p>
                        <p className="text-dark">Subject : Computer</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-02.jpg" alt="Img" />
                            </span>
                            Daniel
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-primary rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            02:15 - 03:00 PM
                        </p>
                        <p className="text-dark">Subject : English</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Hellana
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-info rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            03:15 - 04:00 PM
                        </p>
                        <p className="text-dark">Subject : Science</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-05.jpg" alt="Img" />
                            </span>
                            Morgan
                            </Link>
                        </div>
                        </div>
                    </div>
                    <div className="d-flex flex-column me-4 flex-fill">
                        <div className="mb-3">
                        <h6>Wednesday</h6>
                        </div>
                        <div className="bg-transparent-success rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:00 - 09:45 AM
                        </p>
                        <p className="text-dark">Subject : Computer</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-02.jpg" alt="Img" />
                            </span>
                            Daniel
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-info rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:45 - 10:30 AM
                        </p>
                        <p className="text-dark">Subject : Science</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-05.jpg" alt="Img" />
                            </span>
                            Morgan
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-danger rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            10:45 - 11:30 AM
                        </p>
                        <p className="text-dark">Subject : Maths</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-07.jpg" alt="Img" />
                            </span>
                            Jacquelin
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-light rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            11:30 - 12:15 PM
                        </p>
                        <p className="text-dark">Subject : Chemistry</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-06.jpg" alt="Img" />
                            </span>
                            Aaron
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-warning rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            01:30 - 02:15 PM
                        </p>
                        <p className="text-dark">Subject : Physics</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-01.jpg" alt="Img" />
                            </span>
                            Teresa
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-primary rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            02:15 - 03:00 PM
                        </p>
                        <p className="text-dark">Subject : English</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Hellana
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-pending rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            03:15 - 04:00 PM
                        </p>
                        <p className="text-dark">Subject : Spanish</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Erickson
                            </Link>
                        </div>
                        </div>
                    </div>
                    <div className="d-flex flex-column me-4 flex-fill">
                        <div className="mb-3">
                        <h6>Thursday</h6>
                        </div>
                        <div className="bg-transparent-warning rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:00 - 09:45 AM
                        </p>
                        <p className="text-dark">Subject : Physics</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-01.jpg" alt="Img" />
                            </span>
                            Teresa
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-success rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:45 - 10:30 AM
                        </p>
                        <p className="text-dark">Subject : Computer</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-02.jpg" alt="Img" />
                            </span>
                            Daniel
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-primary rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            10:45 - 11:30 AM
                        </p>
                        <p className="text-dark">Subject : English</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Hellana
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-info rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            11:30 - 12:15 PM
                        </p>
                        <p className="text-dark">Subject : Science</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-05.jpg" alt="Img" />
                            </span>
                            Morgan
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-pending rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            01:30 - 02:15 PM
                        </p>
                        <p className="text-dark">Subject : Spanish</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Erickson
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-light rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            02:15 - 03:00 PM
                        </p>
                        <p className="text-dark">Subject : Chemistry</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-06.jpg" alt="Img" />
                            </span>
                            Aaron
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-danger rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            03:15 - 04:00 PM
                        </p>
                        <p className="text-dark">Subject : Maths</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-07.jpg" alt="Img" />
                            </span>
                            Jacquelin
                            </Link>
                        </div>
                        </div>
                    </div>
                    <div className="d-flex flex-column me-4 flex-fill">
                        <div className="mb-3">
                        <h6>Friday</h6>
                        </div>
                        <div className="bg-transparent-primary rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:00 - 09:45 AM
                        </p>
                        <p className="text-dark">Subject : English</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Hellana
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-pending rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:45 - 10:30 AM
                        </p>
                        <p className="text-dark">Subject : Spanish</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Erickson
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-warning rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            10:45 - 11:30 AM
                        </p>
                        <p className="text-dark">Subject : Physics</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-01.jpg" alt="Img" />
                            </span>
                            Teresa
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-light rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            11:30 - 12:15 PM
                        </p>
                        <p className="text-dark">Subject : Chemistry</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-06.jpg" alt="Img" />
                            </span>
                            Aaron
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-danger rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            01:30 - 02:15 PM
                        </p>
                        <p className="text-dark">Subject : Maths</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-07.jpg" alt="Img" />
                            </span>
                            Jacquelin
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-success rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            02:15 - 3:00 PM
                        </p>
                        <p className="text-dark">Subject : Computer</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-02.jpg" alt="Img" />
                            </span>
                            Daniel
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-info rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            03:15 - 04:00 PM
                        </p>
                        <p className="text-dark">Subject : Science</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-05.jpg" alt="Img" />
                            </span>
                            Morgan
                            </Link>
                        </div>
                        </div>
                    </div>
                    <div className="d-flex flex-column flex-fill">
                        <div className="mb-3">
                        <h6>Saturday</h6>
                        </div>
                        <div className="bg-transparent-primary rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:00 - 09:45 AM
                        </p>
                        <p className="text-dark">Subject : English</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Hellana
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-pending rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            09:45 - 10:30 AM
                        </p>
                        <p className="text-dark">Subject : Spanish</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-03.jpg" alt="Img" />
                            </span>
                            Erickson
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-warning rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            10:45 - 11:30 AM
                        </p>
                        <p className="text-dark">Subject : Physics</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-01.jpg" alt="Img" />
                            </span>
                            Teresa
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-light rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            11:30 - 12:15 PM
                        </p>
                        <p className="text-dark">Subject : Chemistry</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-06.jpg" alt="Img" />
                            </span>
                            Aaron
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-danger rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            01:30 - 02:15 PM
                        </p>
                        <p className="text-dark">Subject : Maths</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-07.jpg" alt="Img" />
                            </span>
                            Jacquelin
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-success rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            02:15 - 3:00 PM
                        </p>
                        <p className="text-dark">Subject : Computer</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-02.jpg" alt="Img" />
                            </span>
                            Daniel
                            </Link>
                        </div>
                        </div>
                        <div className="bg-transparent-info rounded p-3 mb-4">
                        <p className="d-flex align-items-center text-nowrap mb-1">
                            <i className="ti ti-clock me-1" />
                            03:15 - 04:00 PM
                        </p>
                        <p className="text-dark">Subject : Science</p>
                        <div className="bg-white rounded p-1 mt-3">
                            <Link
                            to={routes.teacherDetails}
                            className="text-muted d-flex align-items-center"
                            >
                            <span className="avatar avatar-sm me-2">
                                <ImageWithBasePath src="assets/img/teachers/teacher-05.jpg" alt="Img" />
                            </span>
                            Morgan
                            </Link>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
                <div className="card-footer border-0 pb-0">
                    <div className="row">
                    <div className="col-lg-4 col-xxl-4 col-xl-4 d-flex">
                        <div className="card flex-fill">
                        <div className="card-body">
                            <span className="bg-primary badge badge-sm mb-2">
                            Morning Break
                            </span>
                            <p className="text-dark">
                            <i className="ti ti-clock me-1" />
                            10:30 to 10 :45 AM
                            </p>
                        </div>
                        </div>
                    </div>
                    <div className="col-lg-4 col-xxl-3 d-flex">
                        <div className="card flex-fill">
                        <div className="card-body">
                            <span className="bg-warning badge badge-sm mb-2">Lunch</span>
                            <p className="text-dark">
                            <i className="ti ti-clock me-1" />
                            10:30 to 10 :45 AM
                            </p>
                        </div>
                        </div>
                    </div>
                    <div className="col-lg-4 col-xxl-3 d-flex">
                        <div className="card flex-fill">
                        <div className="card-body">
                            <span className="bg-info badge badge-sm mb-2">
                            Evening Break
                            </span>
                            <p className="text-dark">
                            <i className="ti ti-clock me-1" />
                            03:30 PM to 03:45 PM
                            </p>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
                </div>
            </div>
        </div>

        </div>
      </div>
    </div>
  </div>
  {/* /Page Wrapper */}
  <StudentModals />
</>

  )
}

export default StudentTimeTable