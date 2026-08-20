
import { Link } from "react-router-dom";
import { all_routes } from "../router/all_routes";
import ImageWithBasePath from "../../core/common/imageWithBasePath";
import {
  internetCategory,
  markAs,
  priority,
  priorityList,
  staffName,
  statusOption,
  studentName,
  ticketDate,
} from "../../core/common/selectoption/selectoption";
import CommonSelect from "../../core/common/commonSelect";
import PredefinedDateRanges from "../../core/common/datePicker";
import TicketsSidebar from "./tickets-sidebar";
import TooltipOption from "../../core/common/tooltipOption";

const TicketGrid = () => {
  const route = all_routes;

  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content pb-lg-4 pb-2">
          <div className="row">
            {/* Page Header */}
            <div className="col-md-12">
              <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div className="my-auto mb-2">
                  <h3 className="page-title mb-1">Tickets</h3>
                  <nav>
                    <ol className="breadcrumb mb-0">
                      <li className="breadcrumb-item">
                        <Link to={route.adminDashboard}>Dashboard</Link>
                      </li>
                      <li className="breadcrumb-item">Support</li>
                      <li
                        className="breadcrumb-item active"
                        aria-current="page"
                      >
                        Tickets
                      </li>
                    </ol>
                  </nav>
                </div>
                <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
                  <TooltipOption />
                  <div className="mb-2">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#add_ticket"
                      className="btn btn-primary"
                    >
                      <i className="ti ti-square-rounded-plus me-2" />
                      Add New Ticket
                    </Link>
                  </div>
                </div>
              </div>
            </div>
            {/* /Page Header */}
          </div>
          <div className="d-flex align-items-center justify-content-between flex-wrap p-3 bg-white pb-0 mb-4">
            <div className="dropdown me-2 mb-3">
              <Link
                to="#"
                className="dropdown-toggle text-default fw-medium d-inline-flex align-items-center p-1 border-0 fs-18 fw-semibold"
                data-bs-toggle="dropdown"
              >
                All Tickets
              </Link>
              <ul className="dropdown-menu p-3">
                <li>
                  <Link to="#" className="dropdown-item rounded-1">
                    Open
                  </Link>
                </li>
                <li>
                  <Link to="#" className="dropdown-item rounded-1">
                    Inprogress
                  </Link>
                </li>
                <li>
                  <Link to="#" className="dropdown-item rounded-1">
                    Closed
                  </Link>
                </li>
                <li>
                  <Link to="#" className="dropdown-item rounded-1">
                    Reopened
                  </Link>
                </li>
              </ul>
            </div>
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
                <div className="dropdown-menu drop-width">
                  <form action="tickets">
                    <div className="d-flex align-items-center border-bottom p-3">
                      <h4>Filter</h4>
                    </div>
                    <div className="p-3 pb-0 border-bottom">
                      <div className="row">
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">Assigned to</label>
                            <CommonSelect
                              className="select"
                              options={studentName}
                              defaultValue={undefined}
                            />
                          </div>
                        </div>
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">Select Date</label>
                            <CommonSelect
                              className="select"
                              options={ticketDate}
                              defaultValue={undefined}
                            />
                          </div>
                        </div>
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">Status</label>
                            <CommonSelect
                              className="select"
                              options={priorityList}
                              defaultValue={undefined}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="p-3 d-flex align-items-center justify-content-end">
                      <Link to="#" className="btn btn-light me-3">
                        Reset
                      </Link>
                      <Link
                        to="#"
                        data-bs-dismiss="modal"
                        className="btn btn-primary"
                      >
                        Apply
                      </Link>
                    </div>
                  </form>
                </div>
              </div>
              <div className="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
                <Link
                  to={route.tickets}
                  className="btn btn-icon bg-light btn-sm me-1 primary-hover"
                >
                  <i className="ti ti-list-tree" />
                </Link>
                <Link
                  to={route.ticketGrid}
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
                  Sort by A-Z
                </Link>
                <ul className="dropdown-menu p-3">
                  <li>
                    <Link to="#" className="dropdown-item rounded-1 active">
                      Ascending
                    </Link>
                  </li>
                  <li>
                    <Link to="#" className="dropdown-item rounded-1">
                      Descending
                    </Link>
                  </li>
                  <li>
                    <Link to="#" className="dropdown-item rounded-1">
                      Recently Viewed
                    </Link>
                  </li>
                  <li>
                    <Link to="#" className="dropdown-item rounded-1">
                      Recently Added
                    </Link>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div className="row">
            {/* Tickets */}
            <div className="col-xl-8 col-xxl-9">
              {/* Ticket List */}
              <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
                  <h5 className="text-primary mb-3">IT Support</h5>
                  <div className="d-flex align-items-center mb-1">
                    <span className="badge badge-danger d-inline-flex align-items-center mb-2 me-4">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      High
                    </span>
                    <div className="mb-2">
                      <CommonSelect
                        className="select"
                        options={markAs}
                        defaultValue={markAs[1]}
                      />
                    </div>
                  </div>
                </div>
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="d-flex align-items-center flex-wrap">
                      <span className="avatar avatar-xxl me-2 mb-3">
                        <ImageWithBasePath
                          src="assets/img/students/student-11.jpg"
                          alt="img"
                        />
                      </span>
                      <div className="mb-3">
                        <span className="badge bg-pending rounded-pill mb-1">
                          #TK0003
                        </span>
                        <div className="d-flex align-items-center mb-2">
                          <h5 className="fw-semibold me-2">
                            <Link to={route.ticketDetails}>Laptop Issue</Link>
                          </h5>
                          <span className="badge bg-outline-danger d-flex align-items-center ms-1">
                            <i className="ti ti-circle-filled fs-5 me-1" />
                            Open
                          </span>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                          <p className="d-flex align-items-center me-2 mb-1">
                            <ImageWithBasePath
                              src="assets/img/teachers/teacher-02.jpg"
                              className="avatar avatar-xs rounded-circle me-2"
                              alt="img"
                            />
                            Assigned to
                            <span className="text-dark ms-1">Daniel</span>
                          </p>
                          <p className="d-flex align-items-center mb-1 me-2">
                            <i className="ti ti-calendar-bolt me-1" />
                            Updated 22 hours ago
                          </p>
                          <p className="d-flex align-items-center mb-1">
                            <i className="ti ti-message-share me-1" />9 Comments
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-danger d-flex align-items-center lh-1"
                      >
                        <i className="ti ti-trash-x me-1" />
                        Delete
                      </Link>
                    </div>
                  </div>
                  <div className="bg-light-300 p-3 rounded mb-3">
                    <p className="mb-1">
                      Last Comment from Support Agent
                      <span className="text-dark"> Angio</span>
                    </p>
                    <p className="mb-2">
                      Switch on the side panel &amp; update the OS, Login in to
                      the device manager and update the password
                    </p>
                    <Link
                      to="#"
                      className="d-inline-flex align-items-center text-primary fw-medium"
                      data-bs-toggle="offcanvas"
                      data-bs-target="#ticket-reply"
                    >
                      <i className="ti ti-reload me-1" />
                      Reply
                    </Link>
                  </div>
                </div>
              </div>
              {/* /Ticket List */}
              {/* Ticket List */}
              <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
                  <h5 className="text-primary mb-3">Maintenance</h5>
                  <div className="d-flex align-items-center mb-1">
                    <span className="badge badge-success d-inline-flex align-items-center mb-2 me-4">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      Low
                    </span>
                    <div className="mb-2">
                      <CommonSelect
                        className="select"
                        options={markAs}
                        defaultValue={markAs[0]}
                      />
                    </div>
                  </div>
                </div>
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="d-flex align-items-center flex-wrap">
                      <span className="avatar avatar-xxl me-2 mb-3">
                        <ImageWithBasePath
                          src="assets/img/students/student-01.jpg"
                          alt="img"
                        />
                      </span>
                      <div className="mb-3">
                        <span className="badge bg-pending rounded-pill mb-1">
                          #TK0004
                        </span>
                        <div className="d-flex align-items-center mb-2">
                          <h5 className="fw-semibold me-2">
                            <Link to={route.ticketDetails}>
                              Air Conditioning Problem in Classroom
                            </Link>
                          </h5>
                          <span className="badge bg-outline-warning d-flex align-items-center ms-1">
                            <i className="ti ti-circle-filled fs-5 me-1" />
                            InProgress
                          </span>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                          <p className="d-flex align-items-center me-2 mb-1">
                            <ImageWithBasePath
                              src="assets/img/teachers/teacher-01.jpg"
                              className="avatar avatar-xs rounded-circle me-2"
                              alt="img"
                            />
                            Assigned to
                            <span className="text-dark ms-1"> Teresa</span>
                          </p>
                          <p className="d-flex align-items-center mb-1 me-2">
                            <i className="ti ti-calendar-bolt me-1" />
                            Updated 20 hours ago
                          </p>
                          <p className="d-flex align-items-center mb-1">
                            <i className="ti ti-message-share me-1" />
                            15 Comments
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-danger d-flex align-items-center lh-1"
                      >
                        <i className="ti ti-trash-x me-1" />
                        Delete
                      </Link>
                    </div>
                  </div>
                  <div className="bg-light-300 p-3 rounded mb-3">
                    <p className="mb-1">
                      Last Comment from Support Agent
                      <span className="text-dark"> Angio</span>
                    </p>
                    <p className="mb-2">
                      Switch on the side panel &amp; update the OS, Login in to
                      the device manager and update the password
                    </p>
                    <Link
                      to="#"
                      className="d-inline-flex align-items-center text-primary fw-medium"
                      data-bs-toggle="offcanvas"
                      data-bs-target="#ticket-reply"
                    >
                      <i className="ti ti-reload me-1" />
                      Reply
                    </Link>
                  </div>
                </div>
              </div>
              {/* /Ticket List */}
              {/* Ticket List */}
              <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
                  <h5 className="text-primary mb-3">Transportation Issue</h5>
                  <div className="d-flex align-items-center mb-1">
                    <span className="badge badge-warning d-inline-flex align-items-center mb-2 me-4">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      Medium
                    </span>
                    <div className="mb-2">
                      <CommonSelect
                        className="select"
                        options={markAs}
                        defaultValue={markAs[1]}
                      />
                    </div>
                  </div>
                </div>
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="d-flex align-items-center flex-wrap">
                      <span className="avatar avatar-xxl me-2 mb-3">
                        <ImageWithBasePath
                          src="assets/img/students/student-05.jpg"
                          alt="img"
                        />
                      </span>
                      <div className="mb-3">
                        <span className="badge bg-pending rounded-pill mb-1">
                          #TK0003
                        </span>
                        <div className="d-flex align-items-center mb-2">
                          <h5 className="fw-semibold me-2">
                            <Link to={route.ticketDetails}>
                              School Bus Route Issue
                            </Link>
                          </h5>
                          <span className="badge bg-outline-success d-flex align-items-center ms-1">
                            <i className="ti ti-circle-filled fs-5 me-1" />
                            Closed
                          </span>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                          <p className="d-flex align-items-center me-2 mb-1">
                            <ImageWithBasePath
                              src="assets/img/teachers/teacher-06.jpg"
                              className="avatar avatar-xs rounded-circle me-2"
                              alt="img"
                            />
                            Assigned to
                            <span className="text-dark ms-1"> James</span>
                          </p>
                          <p className="d-flex align-items-center mb-1 me-2">
                            <i className="ti ti-calendar-bolt me-1" />
                            Updated 15 hours ago
                          </p>
                          <p className="d-flex align-items-center mb-1">
                            <i className="ti ti-message-share me-1" />
                            12 Comments
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-danger d-flex align-items-center lh-1"
                      >
                        <i className="ti ti-trash-x me-1" />
                        Delete
                      </Link>
                    </div>
                  </div>
                  <div className="bg-light-300 p-3 rounded mb-3">
                    <p className="mb-1">
                      Last Comment from Support Agent
                      <span className="text-dark"> Angio</span>
                    </p>
                    <p className="mb-2">
                      Switch on the side panel &amp; update the OS, Login in to
                      the device manager and update the password
                    </p>
                    <Link
                      to="#"
                      className="d-inline-flex align-items-center text-primary fw-medium"
                      data-bs-toggle="offcanvas"
                      data-bs-target="#ticket-reply"
                    >
                      <i className="ti ti-reload me-1" />
                      Reply
                    </Link>
                  </div>
                </div>
              </div>
              {/* /Ticket List */}
              {/* Ticket List */}
              <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
                  <h5 className="text-primary mb-3">Library</h5>
                  <div className="d-flex align-items-center mb-1">
                    <span className="badge badge-warning d-inline-flex align-items-center mb-2 me-4">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      Medium
                    </span>
                    <div className="mb-2">
                      <CommonSelect
                        className="select"
                        options={markAs}
                        defaultValue={markAs[1]}
                      />
                    </div>
                  </div>
                </div>
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="d-flex align-items-center flex-wrap">
                      <span className="avatar avatar-xxl me-2 mb-3">
                        <ImageWithBasePath
                          src="assets/img/students/student-09.jpg"
                          alt="img"
                        />
                      </span>
                      <div className="mb-3">
                        <span className="badge bg-pending rounded-pill mb-1">
                          #TK0002
                        </span>
                        <div className="d-flex align-items-center mb-2">
                          <h5 className="fw-semibold me-2">
                            <Link to={route.ticketDetails}>
                              Library Book Request
                            </Link>
                          </h5>
                          <span className="badge bg-outline-info d-flex align-items-center ms-1">
                            <i className="ti ti-circle-filled fs-5 me-1" />
                            Reopened
                          </span>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                          <p className="d-flex align-items-center me-2 mb-1">
                            <ImageWithBasePath
                              src="assets/img/teachers/teacher-09.jpg"
                              className="avatar avatar-xs rounded-circle me-2"
                              alt="img"
                            />
                            Assigned to
                            <span className="text-dark ms-1"> Reopened</span>
                          </p>
                          <p className="d-flex align-items-center mb-1 me-2">
                            <i className="ti ti-calendar-bolt me-1" />
                            Updated 05 hours ago
                          </p>
                          <p className="d-flex align-items-center mb-1">
                            <i className="ti ti-message-share me-1" />
                            13 Comments
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-danger d-flex align-items-center lh-1"
                      >
                        <i className="ti ti-trash-x me-1" />
                        Delete
                      </Link>
                    </div>
                  </div>
                  <div className="bg-light-300 p-3 rounded mb-3">
                    <p className="mb-1">
                      Last Comment from Support Agent
                      <span className="text-dark"> Angio</span>
                    </p>
                    <p className="mb-2">
                      Switch on the side panel &amp; update the OS, Login in to
                      the device manager and update the password
                    </p>
                    <Link
                      to="#"
                      className="d-inline-flex align-items-center text-primary fw-medium"
                      data-bs-toggle="offcanvas"
                      data-bs-target="#ticket-reply"
                    >
                      <i className="ti ti-reload me-1" />
                      Reply
                    </Link>
                  </div>
                </div>
              </div>
              {/* /Ticket List */}
              <div className="text-center mb-xl-0 mb-4">
                <Link to="#" className="btn btn-primary">
                  <i className="ti ti-loader-3 me-2" />
                  Load More
                </Link>
              </div>
            </div>
            {/* /Tickets */}
            {/* Categories List */}
            <TicketsSidebar />
            {/* /Categories List */}
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}

      {/* Ticket View */}
      <div
        className="offcanvas offcanvas-end custom-offcanvas"
        tabIndex={-1}
        id="ticket-reply"
      >
        <div className="offcanvas-header border-bottom d-flex align-items-center justify-content-between p-3">
          <div className="d-flex align-items-center">
            <h5 className="me-2 mb-2">Laptop Issue</h5>
            <div className="dropdown me-1 mb-2 ms-1">
              <Link
                to="#"
                className="dropdown-toggle  badge bg-outline-danger fs-12 text-danger d-inline-flex align-items-center p-1"
                data-bs-toggle="dropdown"
              >
                Open
              </Link>
              <ul className="dropdown-menu p-3">
                <li>
                  <Link to="#" className="dropdown-item rounded-1">
                    Open
                  </Link>
                </li>
                <li>
                  <Link to="#" className="dropdown-item rounded-1">
                    Closed
                  </Link>
                </li>
                <li>
                  <Link to="#" className="dropdown-item rounded-1">
                    Reopened
                  </Link>
                </li>
              </ul>
            </div>
          </div>
          <div className="d-flex align-items-center">
            <h5 className="text-primary me-2">IT Support</h5>
            <button
              type="button"
              className="close-btn"
              data-bs-dismiss="offcanvas"
              aria-label="Close"
            >
              <i className="ti ti-x" />
            </button>
          </div>
        </div>
        <div className="offcanvas-body p-0">
          <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom p-3 pb-0">
            <div className="d-block mb-3">
              <div className="d-flex align-items-center mb-2">
                <span className="badge bg-pending rounded-pill me-3">
                  #TK0003
                </span>
                <span className="badge badge-success d-inline-flex align-items-center">
                  <i className="ti ti-circle-filled fs-5 me-1" />
                  Low
                </span>
              </div>
              <div className="d-flex align-items-center flex-wrap">
                <p className="d-flex align-items-center me-2 mb-1">
                  <ImageWithBasePath
                    src="assets/img/teachers/teacher-01.jpg"
                    className="avatar avatar-xs rounded-circle me-2"
                    alt="img"
                  />
                  Assigned to <span className="text-dark ms-1"> Teresa</span>
                </p>
                <p className="d-flex align-items-center mb-1 me-2">
                  <i className="ti ti-calendar-bolt me-1" />
                  Updated 22 hours ago
                </p>
              </div>
            </div>
            <div className="mb-3">
              <CommonSelect
                className="select"
                options={markAs}
                defaultValue={markAs[1]}
              />
            </div>
          </div>
          <div className="border-bottom p-3 pb-0">
            <div className="d-flex">
              <span className="avatar avatar-xxl flex-shrink-0 me-4 mb-3">
                <ImageWithBasePath
                  src="assets/img/students/student-11.jpg"
                  alt="Img"
                />
              </span>
              <div>
                <div
                  className="alert bg-dark rounded d-flex align-items-center justify-content-between mb-3"
                  role="alert"
                >
                  <p className="mb-0">
                    Note!&nbsp;This ticket is closed. If you want to re-open it,
                    just post a reply below.
                  </p>
                  <button
                    type="button"
                    className="btn-close opacity-100 text-white p-0"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                  >
                    <span>
                      <i className="ti ti-x" />
                    </span>
                  </button>
                </div>
                <div className="mb-3">
                  <div className="summernote">
                    Write a new comment, send your team notification by typing @
                    followed by their name
                  </div>
                  <div className="d-flex align-items-center justify-content-between mt-3">
                    <Link to="#" className="btn btn-light">
                      <i className="ti ti-pin" />
                      Attachment
                    </Link>
                    <Link to="#" className="btn btn-primary">
                      Post Comment
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="border-bottom p-3 pb-0">
            <div className="d-flex align-items-center mb-3">
              <span className="avatar avatar-xl me-2 flex-shrink-0">
                <ImageWithBasePath
                  src="assets/img/students/student-11.jpg"
                  alt="Img"
                />
              </span>
              <div>
                <h6 className="mb-1">James Hendriques</h6>
                <p>
                  <i className="ti ti-calendar-bolt me-1" />
                  Updated 5 hours ago
                </p>
              </div>
            </div>
            <div>
              <div className="mb-3">
                <h5 className="mb-1">Impact on Work</h5>
                <p>
                  This issue disrupts meetings, delays task completion, and
                  affects my overall productivity.
                </p>
              </div>
              <span className="badge bg-light">
                Screenshot.png
                <i className="ti ti-download ms-1" />
              </span>
              <div className="d-flex align-items-center my-3">
                <Link
                  to="#"
                  className="d-inline-flex align-items-center text-primary fw-medium me-3"
                >
                  <i className="ti ti-reload me-1" />
                  Reply
                </Link>
                <p className="d-flex align-items-center">
                  <i className="ti ti-message-share me-1" />9 Comments
                </p>
              </div>
              <div className="mb-3">
                <div className="summernote">
                  Write a new comment, send your team notification by typing @
                  followed by their name
                </div>
                <div className="d-flex align-items-center justify-content-between mt-3">
                  <Link to="#" className="btn btn-light">
                    <i className="ti ti-pin" />
                    Attachment
                  </Link>
                  <Link to="#" className="btn btn-primary">
                    Post Comment
                  </Link>
                </div>
              </div>
            </div>
          </div>
          <div className="border-bottom p-3 pb-0">
            <div className="d-flex align-items-center mb-3">
              <span className="avatar avatar-xl me-2 flex-shrink-0">
                <ImageWithBasePath
                  src="assets/img/profiles/avatar-19.jpg"
                  alt="Img"
                />
              </span>
              <div>
                <h6 className="mb-1">Support Agent Angio</h6>
                <p>
                  <i className="ti ti-calendar-bolt me-1" />
                  Updated 5 hours ago
                </p>
              </div>
            </div>
            <div>
              <div className="mb-3">
                <p>
                  Switch on the side panel &amp; update the OS, Login in to the
                  device manager and update the password
                </p>
              </div>
              <div className="d-flex align-items-center my-3">
                <Link
                  to="#"
                  className="d-inline-flex align-items-center text-primary fw-medium me-3"
                >
                  <i className="ti ti-reload me-1" />
                  Reply
                </Link>
                <p className="d-flex align-items-center">
                  <i className="ti ti-message-share me-1" />5 Comments
                </p>
              </div>
            </div>
          </div>
          <div className="border-bottom p-3 pb-0">
            <div className="d-flex align-items-center mb-3">
              <span className="avatar avatar-xl me-2 flex-shrink-0">
                <ImageWithBasePath
                  src="assets/img/profiles/avatar-01.jpg"
                  alt="Img"
                />
              </span>
              <div>
                <h6 className="mb-1">Marilyn Siegle</h6>
                <p>
                  <i className="ti ti-calendar-bolt me-1" />
                  Updated 6 hours ago
                </p>
              </div>
            </div>
            <div>
              <div className="mb-3">
                <p>
                  Check the System and Application logs in the Event Viewer for
                  warnings or errors that coincide with the times the freezes
                  occur.
                </p>
              </div>
              <div className="d-flex align-items-center my-3">
                <Link
                  to="#"
                  className="d-inline-flex align-items-center text-primary fw-medium me-3"
                >
                  <i className="ti ti-reload me-1" />
                  Reply
                </Link>
                <p className="d-flex align-items-center">
                  <i className="ti ti-message-share me-1" />7 Comments
                </p>
              </div>
            </div>
          </div>
          <div className="p-3 pb-0">
            <div className="d-flex align-items-center mb-3">
              <span className="avatar avatar-xl me-2 flex-shrink-0">
                <ImageWithBasePath
                  src="assets/img/profiles/avatar-22.jpg"
                  alt="Img"
                />
              </span>
              <div>
                <h6 className="mb-1">Brian Foust</h6>
                <p>
                  <i className="ti ti-calendar-bolt me-1" />
                  Updated 8 hours ago
                </p>
              </div>
            </div>
            <div>
              <div className="mb-3">
                <p>
                  Check the System and Application logs in the Event Viewer for
                  warnings or errors that coincide with the times the freezes
                  occur.
                </p>
              </div>
              <div className="d-flex align-items-center my-3">
                <Link
                  to="#"
                  className="d-inline-flex align-items-center text-primary fw-medium me-3"
                >
                  <i className="ti ti-reload me-1" />
                  Reply
                </Link>
                <p className="d-flex align-items-center">
                  <i className="ti ti-message-share me-1" />9 Comments
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* /Ticket View */}
      {/* Add Ticket */}
      <div className="modal fade" id="add_ticket">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Ticket</h4>
              <button
                type="button"
                className="btn-close custom-btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              >
                <i className="ti ti-x" />
              </button>
            </div>
            <form >
              <div className="modal-body">
                <div className="row">
                  <div className="col-md-12">
                    <div className="mb-3">
                      <label className="form-label">Title</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Title"
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Event Category</label>
                      <CommonSelect
                        className="select"
                        options={internetCategory}
                        defaultValue={undefined}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Subject</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Subject"
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Assign To</label>
                      <CommonSelect
                        className="select"
                        options={staffName}
                        defaultValue={undefined}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Ticket Description</label>
                      <textarea
                        className="form-control"
                        placeholder="Add Question"
                        defaultValue={""}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Priority</label>
                      <CommonSelect
                        className="select"
                        options={priority}
                        defaultValue={undefined}
                      />
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Status</label>
                      <CommonSelect
                        className="select"
                        options={statusOption}
                        defaultValue={undefined}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="modal-footer">
                <Link
                  to="#"
                  className="btn btn-light me-2"
                  data-bs-dismiss="modal"
                >
                  Cancel
                </Link>
                <Link
                  to="#"
                  data-bs-dismiss="modal"
                  className="btn btn-primary"
                >
                  Add Ticket
                </Link>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Add Ticket */}
    </>
  );
};

export default TicketGrid;
