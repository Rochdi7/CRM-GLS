import { DatePicker } from "antd";

import { Link } from "react-router-dom";
import PredefinedDateRanges from "../../core/common/datePicker";
import CommonSelect from "../../core/common/commonSelect";
import {
  messageTo,
  transactionDate,
} from "../../core/common/selectoption/selectoption";
import { all_routes } from "../router/all_routes";
import TooltipOption from "../../core/common/tooltipOption";

const NoticeBoard = () => {
  const routes = all_routes;
  return (
    <>
      {" "}
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content content-two">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Notice Board</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Announcement</li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Notice Board
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
                  data-bs-target="#add_message"
                  className="btn btn-primary d-flex align-items-center"
                >
                  <i className="ti ti-square-rounded-plus me-2" />
                  Add Message
                </Link>
              </div>
            </div>
          </div>
          {/* /Page Header */}
          <div className="d-flex align-items-center justify-content-end flex-wrap mb-2">
            <div className="form-check me-2 mb-3">
              <input className="form-check-input" type="checkbox" />
              <span className="checkmarks">Mark &amp; Delete All</span>
            </div>
            <div className="d-flex align-items-center flex-wrap">
              <div className="input-icon-start mb-3 me-2 position-relative">
                <PredefinedDateRanges />
              </div>
              <div className="dropdown mb-3">
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
                  <form>
                    <div className="d-flex align-items-center border-bottom p-3">
                      <h4>Filter</h4>
                    </div>
                    <div className="p-3 border-bottom pb-0">
                      <div className="row">
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">Message to</label>
                            <CommonSelect
                              className="select"
                              options={messageTo}
                              defaultValue={messageTo[0]}
                            />
                          </div>
                        </div>
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">Added Date</label>
                            <CommonSelect
                              className="select"
                              options={transactionDate}
                              defaultValue={transactionDate[0]}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="p-3 d-flex align-items-center justify-content-end">
                      <Link to="#" className="btn btn-light me-3">
                        Reset
                      </Link>
                      <button type="submit" className="btn btn-primary">
                        Apply
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      Classes Preparation
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 24 May 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      Fees Reminder
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 12 May 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      Parents Teacher Meeting
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 10 May 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      New Academic Session For Admission (2024-25)
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 28 Apr 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      Staff Meeting
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 23 Apr 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      World Environment Day Program.....!!!
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 21 Apr 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      New Syllabus Instructions
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 11 Mar 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      Exam Preparation Notification!
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 18 Mar 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      Gandhi Jayanti Programmed
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 16 Feb 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          {/* Notice Board List */}
          <div className="card board-hover mb-3">
            <div className="card-body d-md-flex align-items-center justify-content-between pb-1">
              <div className="d-flex align-items-center mb-3">
                <div className="form-check form-check-md me-2">
                  <input className="form-check-input" type="checkbox" />
                </div>
                <span className="bg-soft-primary text-primary avatar avatar-md me-2 br-5 flex-shrink-0">
                  <i className="ti ti-notification fs-16" />
                </span>
                <div>
                  <h6 className="mb-1 fw-semibold">
                    <Link
                      to="#"
                      data-bs-toggle="modal"
                      data-bs-target="#view_details"
                    >
                      Republic Day Celebration
                    </Link>
                  </h6>
                  <p>
                    <i className="ti ti-calendar me-1" />
                    Added on : 24 Jan 2024
                  </p>
                </div>
              </div>
              <div className="d-flex align-items-center board-action mb-3">
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_message"
                  className="text-primary border rounded p-1 badge me-1 primary-btn-hover"
                >
                  <i className="ti ti-edit-circle fs-16" />
                </Link>
                <Link
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                  className="text-danger border rounded p-1 badge danger-btn-hover"
                >
                  <i className="ti ti-trash-x fs-16" />
                </Link>
              </div>
            </div>
          </div>
          {/* Notice Board List */}
          <div className="text-center">
            <Link to="#" className="btn btn-primary">
              <i className="ti ti-loader-3 me-2" />
              Load More
            </Link>
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
      {/* Add Messase */}
      <div className="modal fade" id="add_message">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">New Message</h4>
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
                      <label className="form-label">Notice Date</label>
                      <div className="date-pic">
                        <DatePicker
                          className="form-control datetimepicker"
                          placeholder="Select Date"
                        />
                        <span className="cal-icon">
                          <i className="ti ti-calendar" />
                        </span>
                      </div>
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Publish On</label>
                      <div className="date-pic">
                        <DatePicker
                          className="form-control datetimepicker"
                          placeholder="Select Date"
                        />
                        <span className="cal-icon">
                          <i className="ti ti-calendar" />
                        </span>
                      </div>
                    </div>
                    <div className="mb-3">
                      <div className="bg-light p-3 pb-2 rounded">
                        <div className="mb-3">
                          <label className="form-label">Attachment</label>
                          <p>Upload size of 4MB, Accepted Format PDF</p>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                          <div className="btn btn-primary drag-upload-btn mb-2 me-2">
                            <i className="ti ti-file-upload me-1" />
                            Upload
                            <input
                              type="file"
                              className="form-control image_sign"
                              multiple
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Message</label>
                      <textarea
                        className="form-control"
                        rows={4}
                        defaultValue={""}
                      />
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Message To</label>
                      <div className="row">
                        <div className="col-md-6">
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Student
                          </label>
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Parent
                          </label>
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Admin
                          </label>
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Teacher
                          </label>
                        </div>
                        <div className="col-md-6">
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Accountant
                          </label>
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Librarian
                          </label>
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Receptionist
                          </label>
                          <label className="checkboxs mb-1">
                            <input type="checkbox" />
                            <span className="checkmarks" />
                            Super Admin
                          </label>
                        </div>
                      </div>
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
                <button type="submit" className="btn btn-primary">
                  Add New Mesaage
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Add Messase */}
      {/* Edit Messase */}
      <div className="modal fade" id="edit_message">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Edit Message</h4>
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
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Title"
                        defaultValue="Fees Reminder"
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Notice Date</label>
                      <div className="date-pic">
                      <DatePicker
                          className="form-control datetimepicker"
                          placeholder="Select Date"
                        />
                        <span className="cal-icon">
                          <i className="ti ti-calendar" />
                        </span>
                      </div>
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Publish On</label>
                      <div className="date-pic">
                      <DatePicker
                          className="form-control datetimepicker"
                          placeholder="Select Date"
                        />
                        <span className="cal-icon">
                          <i className="ti ti-calendar" />
                        </span>
                      </div>
                    </div>
                    <div className="mb-3">
                      <div className="bg-light p-3 pb-2 rounded">
                        <div className="mb-3">
                          <label className="form-label">Attachment</label>
                          <p>Upload size of 4MB, Accepted Format PDF</p>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                          <div className="btn btn-primary drag-upload-btn mb-2 me-2">
                            <i className="ti ti-file-upload me-1" />
                            Upload
                            <input
                              type="file"
                              className="form-control image_sign"
                              multiple
                            />
                          </div>
                          <p className="mb-2">Fees_Structure.pdf</p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Message</label>
                      <textarea
                        className="form-control"
                        rows={4}
                        placeholder="Add Comment"
                        defaultValue={
                          "Please clear the outstanding dues for the school fees on the urgent basis."
                        }
                      />
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Message To</label>
                      <div className="row">
                        <div className="col-md-6">
                          <div className="form-check form-check-md mb-1">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Student</span>
                          </div>
                          <div className="form-check form-check-md mb-1">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Parent</span>
                          </div>
                          <div className="form-check form-check-md mb-1">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Admin</span>
                          </div>
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Teacher</span>
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="form-check form-check-md mb-1">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Accountant</span>
                          </div>
                          <div className="form-check form-check-md mb-1">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Librarian</span>
                          </div>
                          <div className="form-check form-check-md mb-1">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Receptionist</span>
                          </div>
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                            <span>Super Admin</span>
                          </div>
                        </div>
                      </div>
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
                <button type="submit" className="btn btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Edit Messase */}
      {/* View Details */}
      <div className="modal fade" id="view_details">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Fees Reminder</h4>
              <button
                type="button"
                className="btn-close custom-btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              >
                <i className="ti ti-x" />
              </button>
            </div>
            <div className="modal-body pb-0">
              <div className="mb-3">
                <p className="mb-1">Dear parents,</p>
                <p>
                  Please clear the outstanding dues for the school fees on the
                  urgent basis.
                </p>
              </div>
              <div className="row">
                <div className="col-md-6">
                  <div className="mb-3">
                    <label className="form-label">Notice Date</label>
                    <p className="d-flex align-items-center">
                      <i className="ti ti-calendar me-1" />
                      15 May 2024
                    </p>
                  </div>
                </div>
                <div className="col-md-6">
                  <div className="mb-3">
                    <label className="form-label">Publish On</label>
                    <p className="d-flex align-items-center">
                      <i className="ti ti-calendar me-1" />
                      21 May 2024
                    </p>
                  </div>
                </div>
              </div>
              <div className="mb-3">
                <div className="bg-light p-3 pb-2 rounded">
                  <div className="mb-0">
                    <label className="form-label">Attachment</label>
                    <p className="text-primary">Fees_Structure.pdf</p>
                  </div>
                </div>
              </div>
              <div className="mb-3">
                <label className="form-label d-block">Message To</label>
                <span className="badge badge-soft-primary me-2">Student</span>
                <span className="badge badge-soft-primary">Parent</span>
              </div>
              <div className="border-top pt-3">
                <div className="d-flex align-items-center flex-wrap">
                  <div className="d-flex align-items-center me-4 mb-3">
                    <span className="avatar avatar-sm bg-light me-1">
                      <i className="ti ti-calendar text-default fs-14" />
                    </span>
                    Added on: 28 Apr 2024
                  </div>
                  <div className="d-flex align-items-center mb-3">
                    <span className="avatar avatar-sm bg-light me-1">
                      <i className="ti ti-user-edit text-default fs-14" />
                    </span>
                    Added By : Daniel
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        {/* /View Details */}
      </div>
      {/* /Main Wrapper */}
      {/* Delete Modal */}
      <div className="modal fade" id="delete-modal">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <form>
              <div className="modal-body text-center">
                <span className="delete-icon">
                  <i className="ti ti-trash-x" />
                </span>
                <h4>Confirm Deletion</h4>
                <p>
                  You want to delete all the marked items, this cant be undone
                  once you delete.
                </p>
                <div className="d-flex justify-content-center">
                  <Link
                    to="#"
                    className="btn btn-light me-3"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <button type="submit" className="btn btn-danger">
                    Yes, Delete
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Delete Modal */}
    </>
  );
};

export default NoticeBoard;
