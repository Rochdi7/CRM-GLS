import ImageWithBasePath from "../../core/common/imageWithBasePath";
import { Link } from "react-router-dom";
import { all_routes } from "../router/all_routes";
import CommonSelect from "../../core/common/commonSelect";
import {
  assigned,
  internetCategory,
  markAs,
  priorityList,
  staffName,
  statusOption,
  ticketDate,
} from "../../core/common/selectoption/selectoption";
import PredefinedDateRanges from "../../core/common/datePicker";
import TicketsSidebar from "./tickets-sidebar";
import TooltipOption from "../../core/common/tooltipOption";

const Tickets = () => {
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
                              options={assigned}
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
                  className="active btn btn-icon btn-sm me-1 primary-hover"
                >
                  <i className="ti ti-list-tree" />
                </Link>
                <Link
                  to={route.ticketGrid}
                  className="btn btn-icon btn-sm bg-light primary-hover"
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
                            <span className="text-dark ms-1"> Daniel</span>
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

      <>
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
              <form>
                <div className="modal-body">
                  <div className="row">
                    <div className="col-md-12">
                      <div className="mb-3">
                        <label className="form-label">Title</label>
                        <input type="text" className="form-control" />
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
                        <input type="text" className="form-control" />
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
                        <textarea className="form-control" defaultValue={""} />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Priority</label>
                        <CommonSelect
                          className="select"
                          options={priorityList}
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
    </>
  );
};

export default Tickets;
