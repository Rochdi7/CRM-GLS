import  { useRef } from 'react'
import { Link } from 'react-router-dom'
import { all_routes } from '../../../router/all_routes'
import ImageWithBasePath from '../../../../core/common/imageWithBasePath'
import PredefinedDateRanges from '../../../../core/common/datePicker'
import { allClass, names } from '../../../../core/common/selectoption/selectoption'
import TeacherModal from '../teacherModal'
import CommonSelect from '../../../../core/common/commonSelect'
import TooltipOption from '../../../../core/common/tooltipOption'

const TeacherGrid = () => {
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
          <h3 className="page-title mb-1">Teachers </h3>
          <nav>
            <ol className="breadcrumb mb-0">
              <li className="breadcrumb-item">
                <Link to={routes.adminDashboard}>Dashboard</Link>
              </li>
              <li className="breadcrumb-item">Peoples</li>
              <li className="breadcrumb-item active" aria-current="page">
                Teachers{" "}
              </li>
            </ol>
          </nav>
        </div>
        <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <TooltipOption />

          <div className="mb-2">
            <Link
              to={routes.addTeacher}
              className="btn btn-primary d-flex align-items-center"
            >
              <i className="ti ti-square-rounded-plus me-2" />
              Add Teacher
            </Link>
          </div>
        </div>
      </div>
      {/* /Page Header */}
      <div className="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
        <h4 className="mb-3">Teachers Grid</h4>
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
              <form >
                <div className="d-flex align-items-center border-bottom p-3">
                  <h4>Filter</h4>
                </div>
                <div className="p-3 pb-0 border-bottom">
                  <div className="row">
                    <div className="col-md-6">
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
                        <label className="form-label">Class</label>
                        <CommonSelect
                        className="select"
                        options={allClass}
                        defaultValue={allClass[0]}
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div className="p-3 d-flex align-items-center justify-content-end">
                  <Link to="#" className="btn btn-light me-3">
                    Reset
                  </Link>
                  <Link to={routes.teacherGrid} onClick={handleApplyClick} className="btn btn-primary">
                    Apply
                  </Link>
                </div>
              </form>
            </div>
          </div>
          <div className="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
            <Link
              to={routes.teacherList}
              className=" btn btn-icon btn-sm me-1 bg-light primary-hover"
            >
              <i className="ti ti-list-tree" />
            </Link>
            <Link
              to={routes.teacherGrid}
              className=" active btn btn-icon btn-sm  primary-hover"
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
      <div className="row">
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849127
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-01.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Teresa</Link>
                    </h6>
                    <p>III A</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">teresa@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 82392 37359</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Physics</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849126
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-02.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Daniel</Link>
                    </h6>
                    <p>II (A)</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Daniel@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 56752 86742</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Computer</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849125
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-03.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Hellana</Link>
                    </h6>
                    <p>VI (A)</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Hellana@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 23566 52683</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">English</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849124
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-04.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Erickson</Link>
                    </h6>
                    <p>VI (B) , V (A)</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Erickson@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 14259 85573</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Spanish</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849123
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-05.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Morgan</Link>
                    </h6>
                    <p>VIII</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Morgan@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 63204 35730</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Env Science</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849122
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-06.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Aaron</Link>
                    </h6>
                    <p>I (A)</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Aaron@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 26267 80542</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Chemistry</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849121
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-07.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Jacquelin</Link>
                    </h6>
                    <p>IV</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Jacquelin@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 77502 54845</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Maths</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849120
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-08.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Raul</Link>
                    </h6>
                    <p>IV</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Raul@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 67845 78784</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Biology</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849119
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-09.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Elizabeth</Link>
                    </h6>
                    <p>VII</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Elizabeth@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 23566 52683</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Finance</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849118
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-10.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Edward</Link>
                    </h6>
                    <p>IX (C) , X (C)</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Edward@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 14259 85573 </p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Economics</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849117
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-07.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Maria</Link>
                    </h6>
                    <p>IX (C) , X (C)</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Maria@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 97846 84518</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">Spanish</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        {/* Teacher Grid */}
        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex">
          <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between">
              <Link to={routes.teacherDetails} className="link-primary">
                T849116
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
                        to={routes.editTeacher}
                      >
                        <i className="ti ti-edit-circle me-2" />
                        Edit
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
                    to={routes.teacherDetails}
                    className="avatar avatar-lg flex-shrink-0"
                  >
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-07.jpg"
                      className="img-fluid rounded-circle"
                      alt="img"
                    />
                  </Link>
                  <div className="ms-2">
                    <h6 className="text-dark text-truncate mb-0">
                      <Link to={routes.teacherDetails}>Jacky</Link>
                    </h6>
                    <p>VI (A)</p>
                  </div>
                </div>
              </div>
              <div>
                <div className="mb-2">
                  <p className="mb-0">Email</p>
                  <p className="text-dark">Jacky@example.com</p>
                </div>
                <div>
                  <p className="mb-0">Phone</p>
                  <p className="text-dark">+1 98392 37378</p>
                </div>
              </div>
            </div>
            <div className="card-footer d-flex align-items-center justify-content-between">
              <span className="badge badge-soft-danger">English</span>
              <Link to={routes.teacherDetails} className="btn btn-light btn-sm">
                View Details
              </Link>
            </div>
          </div>
        </div>
        {/* /Teacher Grid */}
        <div className="text-center">
          <Link
            to="#"
            className="btn btn-primary d-inline-flex align-items-center"
          >
            <i className="ti ti-loader-3 me-2" />
            Load More
          </Link>
        </div>
      </div>
    </div>
  </div>
  {/* /Page Wrapper */}
  <TeacherModal/>
</>

  )
}

export default TeacherGrid