import { Link } from "react-router-dom";
import {
  internetCategory,
  markAs,
  names,
  priority,
  priorityList,
  staffName,
  statusOption,
  ticketStatus,
} from "../../core/common/selectoption/selectoption";
import { all_routes } from "../router/all_routes";
import CommonSelect from "../../core/common/commonSelect";
import ImageWithBasePath from "../../core/common/imageWithBasePath";
import TooltipOption from "../../core/common/tooltipOption";

const TicketDetails = () => {
  const route = all_routes;

  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content pb-lg-4 pb-2">
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Tickets</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={route.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Support</li>
                  <li className="breadcrumb-item active" aria-current="page">
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
          <div className="row">
            {/* Tickets */}
            <div className="col-xl-8 col-xxl-9">
              {/* Ticket List */}
              <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between p-3 pb-0">
                  <h5 className="text-primary mb-3">IT Support</h5>
                  <div className="d-flex align-items-center">
                    <span className="badge bg-danger mb-3 me-3">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      High
                    </span>
                    <div className="mb-3">
                      <CommonSelect
                        className="select"
                        options={markAs}
                        defaultValue={markAs[1]}
                      />
                    </div>
                  </div>
                </div>
                <div className="card-body p-0">
                  <div className="ticket-information ticket-details">
                    <div className="d-flex align-items-center justify-content-between flex-wrap p-3 pb-0 border-bottom">
                      <div className="d-flex align-items-center flex-wrap">
                        <div className="mb-3">
                          <span className="badge bg-pending rounded-pill mb-1">
                            #TK0003
                          </span>
                          <div className="d-flex align-items-center mb-2">
                            <h5 className="fw-semibold me-2">Laptop Issue</h5>
                            <span className="badge bg-outline-danger d-flex align-items-center ms-1">
                              Open
                            </span>
                          </div>
                          <div className="d-flex align-items-center flex-wrap">
                            <p className="d-flex align-items-center mb-1 me-2">
                              <i className="ti ti-calendar-bolt me-1" />
                              Updated 22 hours ago
                            </p>
                            <p className="d-flex align-items-center mb-1">
                              <i className="ti ti-message-share me-1" />9
                              Comments
                            </p>
                          </div>
                        </div>
                      </div>
                      <div className="mb-3">
                        <Link to="#" className="btn btn-primary">
                          <i className="ti ti-reload me-1" />
                          Post a Reply
                        </Link>
                      </div>
                    </div>
                    <div className="border-bottom p-3">
                      <div>
                        <p className="mb-3">
                          For the past week, my laptop has been experiencing
                          intermittent freezing issues. The freezes occur
                          randomly, approximately 3-4 times a day, and last
                          about 30-60 seconds each time. During these freezes,
                          the cursor becomes unresponsive, and I am unable to
                          click on anything or use keyboard shortcuts. The issue
                          usually resolves itself, but it significantly disrupts
                          my work.
                        </p>
                        <ul className="list-styled-dotted">
                          <li className="ms-4 mb-3">
                            I first noticed the problem on February 1, 2024,
                            while using Google Meet for a video conference.
                            Since then, the issue has occurred during various
                            tasks, including browsing with Chrome, using
                            Microsoft Office applications, and even when the
                            laptop is idle.
                          </li>
                          <li className="ms-4">
                            Error messages: No specific error messages have
                            appeared, but the Task Manager (when accessible)
                            shows a spike in CPU usage to 100% during these
                            freezes.
                          </li>
                        </ul>
                      </div>
                      <div className="mt-4">
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
                            <p>
                              This issue disrupts meetings, delays task
                              completion, and affects my overall productivity.
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
                              <i className="ti ti-message-share me-1" />1
                              Comments
                            </p>
                          </div>
                        </div>
                      </div>
                      <div className="bg-light-300 rounded p-3">
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
                              Switch on the side panel &amp; update the OS,
                              Login in to the device manager and update the
                              password
                            </p>
                          </div>
                          <span className="badge bg-light">
                            Screenshot.png
                            <i className="ti ti-download ms-1" />
                          </span>
                          <div className="d-flex align-items-center mt-3">
                            <Link
                              to="#"
                              className="d-inline-flex align-items-center text-primary fw-medium"
                            >
                              <i className="ti ti-reload me-1" />
                              Reply
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="border-bottom p-3">
                      <div>
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
                              Check the System and Application logs in the Event
                              Viewer for warnings or errors that coincide with
                              the times the freezes occur.
                            </p>
                          </div>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-light me-2">
                              Screenshot.png
                              <i className="ti ti-download ms-1" />
                            </span>
                            <span className="badge bg-light me-2">
                              Screenshot.png
                              <i className="ti ti-download ms-1" />
                            </span>
                            <span className="badge bg-light me-2">
                              Screenshot.png
                              <i className="ti ti-download ms-1" />
                            </span>
                            <span className="badge bg-light">
                              Screenshot.png
                              <i className="ti ti-download ms-1" />
                            </span>
                          </div>
                          <div className="d-flex align-items-center mt-3">
                            <Link
                              to="#"
                              className="d-inline-flex align-items-center text-primary fw-medium"
                            >
                              <i className="ti ti-reload me-1" />
                              Reply
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="border-bottom p-3">
                      <div>
                        <div className="d-flex align-items-center mb-3">
                          <span className="avatar avatar-xl me-2 flex-shrink-0">
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-22.jpg"
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
                              Check for any pending updates and installing them
                              to see if it resolves the issue
                            </p>
                          </div>
                          <div className="d-flex align-items-center mt-3">
                            <Link
                              to="#"
                              className="d-inline-flex align-items-center text-primary fw-medium"
                            >
                              <i className="ti ti-reload me-1" />
                              Reply
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Ticket List */}
              <div className="load-more text-center">
                <Link to="#" className="btn btn-primary">
                  <i className="ti ti-loader-3" />
                  Load More
                </Link>
              </div>
            </div>
            {/* /Tickets */}
            {/* Ticket Details */}
            <div className="col-xl-4 col-xxl-3 theiaStickySidebar">
              <div className="stickybar">
                <div className="stickybar">
                  <div className="card">
                    <div className="card-header p-3">
                      <h4>Ticket Details</h4>
                    </div>
                    <div className="card-body p-0">
                      <div className="border-bottom p-3">
                        <div className="mb-3">
                          <label className="form-label">Change Priority</label>
                          <CommonSelect
                            className="select"
                            options={priority}
                            defaultValue={undefined}
                          />
                        </div>
                        <div className="mb-3">
                          <label className="form-label">Assign To</label>
                          <CommonSelect
                            className="select"
                            options={names}
                            defaultValue={undefined}
                          />
                        </div>
                        <div className="mb-0">
                          <label className="form-label">Ticket Status</label>
                          <CommonSelect
                            className="select"
                            options={ticketStatus}
                            defaultValue={undefined}
                          />
                        </div>
                      </div>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <span className="avatar avatar-md me-2 flex-shrink-0">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            className="rounded-circle"
                            alt="Img"
                          />
                        </span>
                        <div>
                          <span className="fs-12">User</span>
                          <p className="text-dark">Hellana</p>
                        </div>
                      </div>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <span className="avatar avatar-md me-2 flex-shrink-0">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-04.jpg"
                            className="rounded-circle"
                            alt="Img"
                          />
                        </span>
                        <div>
                          <span className="fs-12">Support Agent</span>
                          <p className="text-dark">Angio</p>
                        </div>
                      </div>
                      <div className="border-bottom p-3">
                        <span className="fs-12">Category</span>
                        <p className="text-dark">Repair &amp; Service</p>
                      </div>
                      <div className="border-bottom p-3">
                        <span className="fs-12">Email</span>
                        <p className="text-dark">Hellana@gmail.com</p>
                      </div>
                      <div className="p-3">
                        <span className="fs-12">Last Updated / Closed On</span>
                        <p className="text-dark">25 May 2024</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Ticket Details */}
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}

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
                      <label className="col-form-label">Title</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Title"
                      />
                    </div>
                    <div className="mb-3">
                      <label className="col-form-label">Event Category</label>
                      <CommonSelect
                        className="select"
                        options={internetCategory}
                        defaultValue={undefined}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="col-form-label">Subject</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Subject"
                      />
                    </div>
                    <div className="mb-3">
                      <label className="col-form-label">Assign To</label>
                      <CommonSelect
                        className="select"
                        options={staffName}
                        defaultValue={undefined}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="col-form-label">
                        Ticket Description
                      </label>
                      <textarea
                        className="form-control"
                        placeholder="Add Question"
                        defaultValue={""}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="col-form-label">Priority</label>
                      <CommonSelect
                        className="select"
                        options={priorityList}
                        defaultValue={undefined}
                      />
                    </div>
                    <div className="mb-0">
                      <label className="col-form-label">Status</label>
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

export default TicketDetails;
