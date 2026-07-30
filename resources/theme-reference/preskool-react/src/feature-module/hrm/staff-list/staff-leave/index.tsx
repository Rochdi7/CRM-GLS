
import ImageWithBasePath from "../../../../core/common/imageWithBasePath";
import Table from "../../../../core/common/dataTable/index";
import { staffleave } from "../../../../core/data/json/staff-leave";
import type { TableData } from "../../../../core/data/interface";
import { Link } from "react-router-dom";
import { all_routes } from "../../../router/all_routes";
import CommonSelect from "../../../../core/common/commonSelect";
import { leaveType } from "../../../../core/common/selectoption/selectoption";
import { DatePicker } from "antd";

const StaffLeave = () => {
  const data = staffleave;
  const routes = all_routes;
  const getModalContainer = () => {
    const modalElement = document.getElementById('modal-datepicker');
    return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
  };
   const getModalContainer2 = () => {
    const modalElement = document.getElementById('modal-datepicker2');
    return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
  };
  const columns = [
    {
      title: "Leave Type",
      dataIndex: "leaveType",
      sorter: (a: TableData, b: TableData) =>
        a.leaveType.length - b.leaveType.length,
    },
    {
      title: "Leave Date",
      dataIndex: "leaveDate",
      sorter: (a: TableData, b: TableData) =>
        a.leaveDate.length - b.leaveDate.length,
    },
    {
      title: "No Of Days",
      dataIndex: "noOfDays",
      sorter: (a: TableData, b: TableData) =>
        a.noOfDays.length - b.noOfDays.length,
    },
    {
      title: "AppliedOn",
      dataIndex: "appliedOn",
      sorter: (a: TableData, b: TableData) =>
        a.appliedOn.length - b.appliedOn.length,
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
          ) : (
            <span className="badge badge-soft-pending d-inline-flex align-items-center">
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
          <div className="d-flex align-items-center">
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
                    to={routes.editStaff}
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
        </>
      ),
    },
  ];
  return (
    <div>
      <>
        {/* Page Wrapper */}
        <div className="page-wrapper">
          <div className="content">
            <div className="row">
              {/* Page Header */}
              <div className="col-md-12">
                <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
                  <div className="my-auto mb-2">
                    <h3 className="page-title mb-1">Staff Details</h3>
                    <nav>
                      <ol className="breadcrumb mb-0">
                        <li className="breadcrumb-item">
                          <Link to={routes.adminDashboard}>Dashboard</Link>
                        </li>
                        <li className="breadcrumb-item">
                          <Link to={routes.studentList}>HRM</Link>
                        </li>
                        <li
                          className="breadcrumb-item active"
                          aria-current="page"
                        >
                          Staff Details
                        </li>
                      </ol>
                    </nav>
                  </div>
                  <div className="d-flex my-xl-auto right-content align-items-center  flex-wrap">
                    <Link
                      to={routes.editStaff}
                      className="btn btn-primary d-flex align-items-center mb-2"
                    >
                      <i className="ti ti-edit-circle me-2" />
                      Edit Staff
                    </Link>
                  </div>
                </div>
              </div>
              {/* /Page Header */}
              <div className="col-xxl-3 col-lg-4 theiaStickySidebar">
                <div className="stickybar">
                <div className="card border-white">
                  <div className="card-header">
                    <div className="d-flex align-items-center  row-gap-3">
                      <div className="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                        <ImageWithBasePath
                          src="assets/img/profiles/avatar-27.jpg"
                          className="img-fluid"
                          alt="img"
                        />
                      </div>
                      <div>
                        <span className="badge badge-soft-success d-inline-flex align-items-center mb-1">
                          <i className="ti ti-circle-filled fs-5 me-1" />
                          Active
                        </span>
                        <h5 className="mb-1">Kevin Larry</h5>
                        <p className="text-primary m-0">AD1256589</p>
                        <p className="p-0">Joined On : 10 Mar 2024</p>
                      </div>
                    </div>
                  </div>
                  <div className="card-body">
                    <h5 className="mb-3">Basic Information</h5>
                    <dl className="row mb-0">
                      <dt className="col-6 fw-medium text-dark mb-3">
                        Staff ID
                      </dt>
                      <dd className="col-6  mb-3">35013</dd>
                      <dt className="col-6 fw-medium text-dark mb-3">Gender</dt>
                      <dd className="col-6  mb-3">Male</dd>
                      <dt className="col-6 fw-medium text-dark mb-3">
                        Designation
                      </dt>
                      <dd className="col-6  mb-3">25 Jan 2008</dd>
                      <dt className="col-6 fw-medium text-dark mb-3">
                        Department
                      </dt>
                      <dd className="col-6  mb-3">Technical Lead</dd>
                      <dt className="col-6 fw-medium text-dark mb-3">
                        Date Of Birth
                      </dt>
                      <dd className="col-6  mb-3">Admin</dd>
                      <dt className="col-6 fw-medium text-dark mb-3">
                        Blood Group
                      </dt>
                      <dd className="col-6  mb-3">15 Aug 1987</dd>
                      <dt className="col-6 fw-medium text-dark mb-3">
                        Blood Group
                      </dt>
                      <dd className="col-6  mb-3">O+</dd>
                      <dt className="col-6 fw-medium text-dark mb-3">
                        Mother tongue
                      </dt>
                      <dd className="col-6  mb-3">English</dd>
                      <dt className="col-6 fw-medium text-dark mb-0">
                        Language
                      </dt>
                      <dd className="col-6 text-dark mb-0">
                        <span className="badge badge-light text-dark me-2">
                          English
                        </span>
                        <span className="badge badge-light text-dark">
                          Spanish
                        </span>
                      </dd>
                    </dl>
                  </div>
                </div>
                <div className="card border-white">
                  <div className="card-body">
                    <h5 className="mb-3">Primary Contact Info</h5>
                    <div className="d-flex align-items-center mb-3">
                      <span className="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default">
                        <i className="ti ti-phone" />
                      </span>
                      <div>
                        <span className="fs-12 mb-1 fw-medium text-dark ">
                          Phone Number
                        </span>
                        <p>+1 46548 84498</p>
                      </div>
                    </div>
                    <div className="d-flex align-items-center">
                      <span className="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default">
                        <i className="ti ti-mail" />
                      </span>
                      <div>
                        <span className="fs-12 mb-1 fw-medium text-dark ">
                          Email Address
                        </span>
                        <p>jan@example.com</p>
                      </div>
                    </div>
                  </div>
                </div>
                </div>
              </div>
              <div className="col-xxl-9 col-lg-8">
                <div className="row">
                  <div className="col-md-12">
                    <ul className="nav nav-tabs nav-tabs-bottom mb-4">
                      <li>
                        <Link
                          to={routes.staffDetails}
                          className="nav-link"
                        >
                          <i className="ti ti-info-square-rounded me-2" />
                          Basic Details
                        </Link>
                      </li>
                      <li>
                        <Link to={routes.staffPayroll} className="nav-link ">
                          <i className="ti ti-file-dollar me-2" />
                          Payroll
                        </Link>
                      </li>
                      <li>
                        <Link
                          to={routes.staffLeave}
                          className="nav-link active"
                        >
                          <i className="ti ti-calendar-due me-2" />
                          Leaves
                        </Link>
                      </li>
                      <li>
                        <Link to={routes.staffsAttendance} className="nav-link">
                          <i className="ti ti-calendar-due me-2" />
                          Attendance
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
                <div className="tab-content">
                  <div
                    className="tab-pane fade show active"
                    id="pills-leave"
                    role="tabpanel"
                    aria-labelledby="pills-leave"
                  >
                    <div className="row gx-3">
                      <div className="col-lg-6 col-xxl-3 d-flex">
                        <div className="card flex-fill">
                          <div className="card-body">
                            <h5 className="mb-2">Medical Leave (10)</h5>
                            <div className="d-flex align-items-center flex-wrap">
                              <p className="border-end pe-2 me-2 mb-0">
                                Used : 5
                              </p>
                              <p className="mb-0">Available : 5</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="col-lg-6 col-xxl-3 d-flex">
                        <div className="card flex-fill">
                          <div className="card-body">
                            <h5 className="mb-2">Casual Leave (12)</h5>
                            <div className="d-flex align-items-center flex-wrap">
                              <p className="border-end pe-2 me-2 mb-0">
                                Used : 1
                              </p>
                              <p className="mb-0">Available : 11</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="col-lg-6 col-xxl-3 d-flex">
                        <div className="card flex-fill">
                          <div className="card-body">
                            <h5 className="mb-2">Maternity Leave (10)</h5>
                            <div className="d-flex align-items-center flex-wrap">
                              <p className="border-end pe-2 me-2 mb-0">
                                Used : 0
                              </p>
                              <p className="mb-0">Available : 10</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="col-lg-6 col-xxl-3 d-flex">
                        <div className="card flex-fill">
                          <div className="card-body">
                            <h5 className="mb-2">Paternity Leave (0)</h5>
                            <div className="d-flex align-items-center flex-wrap">
                              <p className="border-end pe-2 me-2 mb-0">
                                Used : 0
                              </p>
                              <p className="mb-0">Available : 0</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="card">
                      {/* Leaves List */}
                      <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                        <h4 className="mb-3">Leaves</h4>
                        <Link
                          to="#"
                          data-bs-target="#apply_leave"
                          data-bs-toggle="modal"
                          className="btn btn-primary d-inline-flex align-items-center mb-3"
                        >
                          <i className="ti ti-calendar-event me-2" />
                          Apply Leave
                        </Link>
                      </div>
                      <div className="card-body p-0 py-3">
                        <Table
                          columns={columns}
                          dataSource={data}
                          Selection={true}
                        />
                      </div>
                      {/* /Leaves List */}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        {/* /Page Wrapper */}
        {/* Apply Leave */}
        <div className="modal fade" id="apply_leave">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Apply Leave</h4>
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
                      <div className="mb-4">
                        <label className="form-label">Leave Date</label>
                        <div className="date-pic">
                        <DatePicker
                                className="form-control datetimepicker"
                                format={{
                                    format: "DD-MM-YYYY",
                                    type: "mask",
                                }}
                                getPopupContainer={getModalContainer}
                                defaultValue=""
                                placeholder="16 May 2024"
                                />
                          <span className="cal-icon">
                            <i className="ti ti-calendar" />
                          </span>
                        </div>
                      </div>
                      <div className="mb-4">
                        <label className="form-label">Leave Type</label>
                       
                        <CommonSelect
                                  className="select"
                                  options={leaveType}
                                />
                      </div>
                      <div className="mb-4">
                        <label className="form-label">Leave From date</label>
                        <div className="date-pic">
                        <DatePicker
                                className="form-control datetimepicker"
                                format={{
                                    format: "DD-MM-YYYY",
                                    type: "mask",
                                }}
                                getPopupContainer={getModalContainer2}
                                defaultValue=""
                                placeholder="16 May 2024"
                                />
                          <span className="cal-icon">
                            <i className="ti ti-calendar" />
                          </span>
                        </div>
                      </div>
                      <div className="mb-4">
                        <label className="form-label">Leave to Date</label>
                        <div className="date-pic">
                        <DatePicker
                                className="form-control datetimepicker"
                                format={{
                                    format: "DD-MM-YYYY",
                                    type: "mask",
                                }}
                                getPopupContainer={getModalContainer}
                                defaultValue=""
                                placeholder="16 May 2024"
                                />
                          <span className="cal-icon">
                            <i className="ti ti-calendar" />
                          </span>
                        </div>
                      </div>
                      <div className="mb-4">
                        <label className="form-label">Leave Days</label>
                        <div className="d-flex align-items-center check-radio-group">
                          <label className="custom-radio">
                            {/* <input type="radio" name="radio" defaultChecked="" /> */}
                            <span className="checkmark" />
                            Full day
                          </label>
                          <label className="custom-radio">
                            <input type="radio" name="radio" />
                            <span className="checkmark" />
                            First Half
                          </label>
                          <label className="custom-radio">
                            <input type="radio" name="radio" />
                            <span className="checkmark" />
                            Second Half
                          </label>
                        </div>
                      </div>
                      <div className="mb-4">
                        <label className="form-label">No of Days</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Reason</label>
                        <input type="text" className="form-control" />
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
                  <Link to="#" className="btn btn-primary"  data-bs-dismiss="modal"> 
                    Apply Leave
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Apply Leave */}
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
    </div>
  );
};

export default StaffLeave;
