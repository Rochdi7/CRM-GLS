import { useRef } from "react";
import { all_routes } from "../../../router/all_routes";
import { approveRequest } from "../../../../core/data/json/approve_request";
import { Link } from "react-router-dom";
import type { TableData } from "../../../../core/data/interface";
import Table from "../../../../core/common/dataTable/index";
import PredefinedDateRanges from "../../../../core/common/datePicker";
import CommonSelect from "../../../../core/common/commonSelect";
import { activeList, leaveType, MonthDate, Role } from "../../../../core/common/selectoption/selectoption";
import TooltipOption from "../../../../core/common/tooltipOption";

const ApproveRequest = () => {
  const routes = all_routes;
  const data = approveRequest;
  const columns = [
    {
      title: "Submitted By",
      dataIndex: "submittedBy",
      sorter: (a: TableData, b: TableData) =>
        a.submittedBy.length - b.submittedBy.length,
    },
    {
      title: "Leave Type",
      dataIndex: "leaveType",
      sorter: (a: TableData, b: TableData) =>
        a.leaveType.length - b.leaveType.length,
    },
    {
      title: "Role",
      dataIndex: "role",
      sorter: (a: TableData, b: TableData) => a.role.length - b.role.length,
    },
    {
      title: "Leave Date",
      dataIndex: "leaveDate",
      sorter: (a: TableData, b: TableData) =>
        a.leaveDate.length - b.leaveDate.length,
    },
    {
      title: "No of Days",
      dataIndex: "noofDays",
      sorter: (a: TableData, b: TableData) =>
        a.noofDays.length - b.noofDays.length,
    },
    {
      title: "Applied On",
      dataIndex: "appliedOn",
      sorter: (a: TableData, b: TableData) =>
        a.appliedOn.length - b.appliedOn.length,
    },
    {
      title: "Authority",
      dataIndex: "authority",
      sorter: (a: TableData, b: TableData) =>
        a.authority.length - b.authority.length,
    },

    {
      title: "Status",
      dataIndex: "status",
      render: (text: string) => (
        <>
          {text === "Approved" ? (
            <span className="badge badge-soft-success d-inline-flex align-items-center">
              <i className="ti ti-circle-filled fs-5 me-1"></i>
              {text}
            </span>
          ) : text === "Pending" ? (
            <span className="badge badge-soft-pending d-inline-flex align-items-center">
              <i className="ti ti-circle-filled fs-5 me-1"></i>
              {text}
            </span>
          ) : (
            <span className="badge badge-soft-danger d-inline-flex align-items-center">
              <i className="ti ti-circle-filled fs-5 me-1"></i>
              {text}
            </span>
          )}
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.status.length - b.status.length,
    },
    {
      title: "Action",
      dataIndex: "action",
      render: () => (
        <>
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
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#leave_request"
                >
                  <i className="ti ti-menu me-2" />
                  Leave Request
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
        </>
      ),
    },
  ];
  const dropdownMenuRef = useRef<HTMLDivElement | null>(null);
  const handleApplyClick = () => {
    if (dropdownMenuRef.current) {
      dropdownMenuRef.current.classList.remove("show");
    }
  };
  return (
    <div>
      <>
        {/* Page Wrapper */}
        <div className="page-wrapper">
          <div className="content">
            {/* Page Header */}
            <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
              <div className="my-auto mb-2">
                <h3 className="page-title mb-1">Approved Leave Request</h3>
                <nav>
                  <ol className="breadcrumb mb-0">
                    <li className="breadcrumb-item">
                      <Link to={routes.adminDashboard}>Dashboard</Link>
                    </li>
                    <li className="breadcrumb-item">
                      <Link to="#">HRM</Link>
                    </li>
                    <li className="breadcrumb-item active" aria-current="page">
                      Approved Leave Request
                    </li>
                  </ol>
                </nav>
              </div>
              <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
              <TooltipOption />
              </div>
            </div>
            {/* Page Header*/}
            {/* Filter Section */}
            <div className="card">
              <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 className="mb-3">Approved Leave Request List</h4>
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
                        <div className="p-3 border-bottom">
                          <div className="row">
                            <div className="col-md-6">
                              <div className="mb-3">
                                <label className="form-label">Leave Type</label>
                               
                                <CommonSelect
                                  className="select"
                                  options={leaveType}
                                />
                              </div>
                            </div>
                            <div className="col-md-6">
                              <div className="mb-3">
                                <label className="form-label">Role</label>
                                <CommonSelect
                                  className="select"
                                  options={Role}
                                />
                              </div>
                            </div>
                            <div className="col-md-6">
                              <div className="mb-0">
                                <label className="form-label">
                                  From - To Date
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={MonthDate}
                                />
                              </div>
                            </div>
                            <div className="col-md-6">
                              <div className="mb-0">
                                <label className="form-label">Status</label>
                                <CommonSelect
                                  className="select"
                                  options={activeList}
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
                            className="btn btn-primary"
                            onClick={handleApplyClick}
                          >
                            Apply
                          </Link>
                        </div>
                      </form>
                    </div>
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
              <div className="card-body p-0 py-3">
                {/* Approve List */}
                  <Table dataSource={data} columns={columns} Selection={true} />
                {/* /Approve List */}
              </div>
            </div>
          </div>
        </div>
        {/* /Page Wrapper */}
        {/* Leave Request */}
        <div className="modal fade" id="leave_request">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Leave Request</h4>
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
                  <div className="student-leave-info">
                    <ul>
                      <li>
                        <span>Submitted By</span>
                        <h6>James Deckar</h6>
                      </li>
                      <li>
                        <span>ID / Roll No</span>
                        <h6>9004</h6>
                      </li>
                      <li>
                        <span>Role</span>
                        <h6>Student</h6>
                      </li>
                      <li>
                        <span>Leave Type</span>
                        <h6>Medical Leave</h6>
                      </li>
                      <li>
                        <span>No of Days</span>
                        <h6>2</h6>
                      </li>
                      <li>
                        <span>Applied On</span>
                        <h6>04 May 2024</h6>
                      </li>
                      <li>
                        <span>Authoity</span>
                        <h6>Jacquelin</h6>
                      </li>
                      <li>
                        <span>Leave</span>
                        <h6>05 May 2024 - 07 may 2024</h6>
                      </li>
                    </ul>
                  </div>
                  <div className="mb-3 leave-reason">
                    <h6 className="mb-1">Reason</h6>
                    <span>Headache &amp; fever</span>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Approval Status</label>
                    <div className="d-flex align-items-center check-radio-group">
                      <label className="custom-radio">
                        <input type="radio" name="radio" checked />
                        <span className="checkmark" />
                        Pending
                      </label>
                      <label className="custom-radio">
                        <input type="radio" name="radio" />
                        <span className="checkmark" />
                        Approved
                      </label>
                      <label className="custom-radio">
                        <input type="radio" name="radio" />
                        <span className="checkmark" />
                        Disapproved
                      </label>
                    </div>
                  </div>
                  <div className="mb-0">
                    <label className="form-label">Note</label>
                    <textarea
                      className="form-control"
                      placeholder="Add Comment"
                      rows={4}
                      defaultValue={""}
                    />
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
                  <Link to="#" className="btn btn-primary" data-bs-dismiss="modal">
                    Submit
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Leave Request */}
        {/* Delete Modal */}
        <div className="modal fade" id="delete-modal">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <form >
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
                    <Link to="#" className="btn btn-danger" data-bs-dismiss="modal">
                      Yes, Delete
                    </Link>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Delete Modal */}
      </>
    </div>
  );
};

export default ApproveRequest;
