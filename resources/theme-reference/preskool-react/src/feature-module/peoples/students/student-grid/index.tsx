import  { useRef } from 'react'
import { Link } from 'react-router-dom'
import { all_routes } from '../../../router/all_routes'
import ImageWithBasePath from '../../../../core/common/imageWithBasePath'
import { allClass, allSection, gender, names, status } from '../../../../core/common/selectoption/selectoption'
import StudentModals from '../studentModals'
import CommonSelect from '../../../../core/common/commonSelect'
import TooltipOption from '../../../../core/common/tooltipOption'
import PredefinedDateRanges from '../../../../core/common/datePicker'

const StudentGrid = () => {
    const routes = all_routes
    const dropdownMenuRef = useRef<HTMLDivElement | null>(null);

    const handleApplyClick = () => {
      if (dropdownMenuRef.current) {
        dropdownMenuRef.current.classList.remove('show');
      }
    };
  return (
    <>
  {/* Page Wrapper */}
  <div className="page-wrapper">
    <div className="content content-two">
      {/* Page Header */}
      <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
        <div className="my-auto mb-2">
          <h3 className="page-title mb-1">Students</h3>
          <nav>
            <ol className="breadcrumb mb-0">
              <li className="breadcrumb-item">
                <Link to={routes.adminDashboard}>Dashboard</Link>
              </li>
              <li className="breadcrumb-item">Peoples</li>
              <li className="breadcrumb-item active" aria-current="page">
                Students Grid
              </li>
            </ol>
          </nav>
        </div>
        <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <TooltipOption />

          <div className="mb-2">
            <Link
              to={routes.addStudent}
              className="btn btn-primary d-flex align-items-center"
            >
              <i className="ti ti-square-rounded-plus me-2" />
              Add Student
            </Link>
          </div>
        </div>
      </div>
      {/* /Page Header */}
      {/* Filter */}
      <div className="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
        <h4 className="mb-3">Students Grid</h4>
        <div className="d-flex align-items-center flex-wrap">
          <div className="input-icon-start mb-3 me-2 position-relative">
            <PredefinedDateRanges />
          </div>
          <div className="dropdown mb-3 me-2">
            <Link
              to="#"
              className="btn btn-outline-light bg-white dropdown-toggle"
              data-bs-toggle="dropdown"
              data-bs-auto-close="outside"
            >
              <i className="ti ti-filter me-2" />
              Filter
            </Link>
            <div className="dropdown-menu drop-width" ref={dropdownMenuRef}>
              <form>
                <div className="d-flex align-items-center border-bottom p-3">
                  <h4>Filter</h4>
                </div>
                <div className="p-3 pb-0 border-bottom">
                  <div className="row">
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label className="form-label">Class</label>
                        <CommonSelect
                        className="select"
                        options={allClass}
                        defaultValue={allClass[0]}
                        />
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label className="form-label">Section</label>
                        <CommonSelect
                        className="select"
                        options={allSection}
                        defaultValue={allSection[0]}
                        />
                      </div>
                    </div>
                    <div className="col-md-12">
                      <div className="mb-3">
                        <label className="form-label">Name</label>
                        <CommonSelect
                        className="select"
                        options={names}
                        defaultValue={names[0]}
                        />
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label className="form-label">Gender</label>
                        <CommonSelect
                        className="select"
                        options={gender}
                        defaultValue={gender[0]}
                        />
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label className="form-label">Status</label>
                        <CommonSelect
                        className="select"
                        options={status}
                        defaultValue={status[0]}
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div className="p-3 d-flex align-items-center justify-content-end">
                  <Link to="#" className="btn btn-light me-3">
                    Reset
                  </Link>
                  <Link to={routes.studentGrid} className="btn btn-primary" onClick={handleApplyClick}>
                    Apply
                  </Link>
                </div>
              </form>
            </div>
          </div>
          <div className="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
            <Link
              to={routes.studentList}
              className="btn btn-icon btn-sm me-1 bg-light primary-hover"
            >
              <i className="ti ti-list-tree" />
            </Link>
            <Link
              to={routes.studentGrid}
              className="active btn btn-icon btn-sm primary-hover"
            >
              <i className="ti ti-grid-dots" />
            </Link>
          </div>
          <div className="dropdown mb-3">
            <Link
              to="#"
              className="btn btn-outline-light bg-white dropdown-toggle"
              data-bs-toggle="dropdown"
            >
              <i className="ti ti-sort-ascending-2 me-2" />
              Sort by A-Z{" "}
            </Link>
            <ul className="dropdown-menu p-3">
              <li>
                <Link
                  to="#"
                  className="dropdown-item rounded-1 active"
                >
                  Ascending
                </Link>
              </li>
              <li>
                <Link
                  to="#"
                  className="dropdown-item rounded-1"
                >
                  Descending
                </Link>
              </li>
              <li>
                <Link
                  to="#"
                  className="dropdown-item rounded-1"
                >
                  Recently Viewed
                </Link>
              </li>
              <li>
                <Link
                  to="#"
                  className="dropdown-item rounded-1"
                >
                  Recently Added
                </Link>
              </li>
            </ul>
          </div>
        </div>
      </div>
      {/* /Filter */}
      <div className="row">
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892434
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-01.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h5 className="mb-0">
                      <Link to={routes.studentDetail}>Janet Daniel</Link>
                    </h5>
                    <p>III, A</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35013</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Female</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">10 Jan 2015</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892433
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-02.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Joann Michael</Link>
                    </h6>
                    <p>IV, B</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35012</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Male</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">19 Aug 2014</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892432
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-03.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Kathleen Dison</Link>
                    </h6>
                    <p>III, A</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35011</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Female</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">5 Dec 2017</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className=" col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892431
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-danger d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Inactive
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-04.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Lisa Gourley </Link>
                    </h6>
                    <p>II, B</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35010</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Female</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">13 May 2017</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892430
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-05.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Ralph Claudia</Link>
                    </h6>
                    <p>II, B</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35009</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Male</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">20 Jun 20215</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892429
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-06.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Ralph Claudia</Link>
                    </h6>
                    <p>II, B</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35008</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Male</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">20 Jun 20215</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892428
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-06.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Julie Scott</Link>
                    </h6>
                    <p>V, A</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35007</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Female</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">18 Jan 2023</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892427
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-09.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Susan Boswell</Link>
                    </h6>
                    <p>VIII, B</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35006</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Female</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">26 May 2020</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892426
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-08.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Richard Mayes</Link>
                    </h6>
                    <p>V, A</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35005</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Male</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">6 Oct 2022</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892425
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-12.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Richard Mayes</Link>
                    </h6>
                    <p>VII, B</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35004</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Male</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">6 Oct 2022</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892424
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-11.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Veronica Randle</Link>
                    </h6>
                    <p>IX, A</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35003</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Female</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">27 Dec 2009</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        {/* Student Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.studentDetail} className="link-primary">
                AD9892423
              </Link>
              <div className="d-flex align-items-center">
                <span className="badge badge-soft-success d-inline-flex align-items-center me-1">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Active
                </span>
                <div className="dropdown">
                  <Link
                    to="#"
                    className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="ti ti-dots-vertical fs-14" />
                  </Link>
                  <ul className="dropdown-menu dropdown-menu-right p-3">
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentDetail}
                      >
                        <i className="ti ti-menu me-2" />
                        View Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.editStudent}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to={routes.studentPromotion}
                      >
                        <i className="ti ti-arrow-ramp-right-2 me-2" />
                        Promote Student
                      </Link>
                    </li>
                    <li>
                      <Link
                        className="dropdown-item rounded-1"
                        to="#"
                        data-bs-toggle="modal"
                        data-bs-target="#delete-modal"
                      >
                        <i className="ti ti-trash-x me-2" />
                        Delete
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body">
              <div className="bg-light-300 rounded-2 p-3 mb-3">
                <div className="d-flex align-items-center">
                  <Link
                    to={routes.studentDetail}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/students/student-10.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="mb-0">
                      <Link to={routes.studentDetail}>Thomas Hunt</Link>
                    </h6>
                    <p>X, A</p>
                  </div>
                </div>
              </div>
              <div className="d-flex align-items-center justify-content-between gx-2">
                <div>
                  <p className="mb-0">Roll No</p>
                  <p className="text-dark">35002</p>
                </div>
                <div>
                  <p className="mb-0">Gender</p>
                  <p className="text-dark">Male</p>
                </div>
                <div>
                  <p className="mb-0">Joined On</p>
                  <p className="text-dark">11 Aug 2008</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <div className="d-flex align-items-center">
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-brand-hipchat" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"
                >
                  <i className="ti ti-phone" />
                </Link>
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"
                >
                  <i className="ti ti-mail" />
                </Link>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_fees_collect"
                className="btn btn-light btn-sm fw-semibold"
              >
                Add Fees
              </Link>
            </div>
          </div>
        </div>
        {/* /Student Grid */}
        <div className="col-md-12 text-center">
          <Link to="#" className="btn btn-primary">
            <i className="ti ti-loader-3 me-2" />
            Load More
          </Link>
        </div>
      </div>
    </div>
  </div>
  {/* /Page Wrapper */}
  <StudentModals />
</>

  )
}

export default StudentGrid