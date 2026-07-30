
import { Link } from "react-router-dom";
import { all_routes } from "../../../router/all_routes";
import StudentModals from "../studentModals";
import StudentSidebar from "./studentSidebar";
import StudentBreadcrumb from "./studentBreadcrumb";
import Table from "../../../../core/common/dataTable/index";
import type { TableData } from "../../../../core/data/interface";
import { leaveData } from "../../../../core/data/json/leaveData";
import { Attendance } from "../../../../core/data/json/attendance";

const StudentLeaves = () => {
  const routes = all_routes;
  const data = leaveData;
  const data2 = Attendance;
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
      title: "No of Days",
      dataIndex: "noOfDays",
      sorter: (a: TableData, b: TableData) =>
        parseFloat(a.noOfDays) - parseFloat(b.noOfDays),
    },
    {
      title: "Applied On",
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
            <span className="badge badge-soft-danger d-inline-flex align-items-center">
              <i className="ti ti-circle-filled fs-5 me-1"></i>
              {text}
            </span>
          )}
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.status.length - b.status.length,
    },
  ];
  const columns2 = [
    {
      title: "Date | Month",
      dataIndex: "date",
      sorter: (a: TableData, b: TableData) => a.date.length - b.date.length,
    },
    {
      title: "Jan",
      dataIndex: "jan",
      render: (text: string) => (
        <>
          {text === "1" ? (
            <span className="attendance-range bg-success"></span>
          ) : text === "2" ? (
            <span className="attendance-range bg-pending"></span>
          ) : text === "3" ? (
            <span className="attendance-range bg-dark"></span>
          ) : text === "4" ? (
            <span className="attendance-range bg-danger"></span>
          ) : (
            <span className="attendance-range bg-info"></span>
          )}
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.jan.length - b.jan.length,
    },
    {
        title: "feb",
        dataIndex: "feb",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.feb.length - b.feb.length,
      },
      {
        title: "mar",
        dataIndex: "mar",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.mar.length - b.mar.length,
      },
      {
        title: "apr",
        dataIndex: "apr",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.apr.length - b.apr.length,
      },
      {
        title: "may",
        dataIndex: "may",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.may.length - b.may.length,
      },
      {
        title: "jun",
        dataIndex: "jun",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.jun.length - b.jun.length,
      },
      {
        title: "jul",
        dataIndex: "jul",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.jul.length - b.jul.length,
      },
      {
        title: "aug",
        dataIndex: "aug",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.aug.length - b.aug.length,
      },
      {
        title: "sep",
        dataIndex: "sep",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.sep.length - b.sep.length,
      },
      {
        title: "oct",
        dataIndex: "oct",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.oct.length - b.oct.length,
      },
      {
        title: "nov",
        dataIndex: "nov",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.nov.length - b.nov.length,
      },
      {
        title: "dec",
        dataIndex: "dec",
        render: (text: string) => (
          <>
            {text === "1" ? (
              <span className="attendance-range bg-success"></span>
            ) : text === "2" ? (
              <span className="attendance-range bg-pending"></span>
            ) : text === "3" ? (
              <span className="attendance-range bg-dark"></span>
            ) : text === "4" ? (
              <span className="attendance-range bg-danger"></span>
            ) : (
              <span className="attendance-range bg-info"></span>
            )}
          </>
        ),
        sorter: (a: TableData, b: TableData) => a.dec.length - b.dec.length,
      },
  ];
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
                      <Link to={routes.studentTimeTable} className="nav-link">
                        <i className="ti ti-table-options me-2" />
                        Time Table
                      </Link>
                    </li>
                    <li>
                      <Link
                        to={routes.studentLeaves}
                        className="nav-link active"
                      >
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
                  {/* Leave Nav*/}
                  <div className="card">
                    <div className="card-body pb-1">
                      <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill">
                        <li className="me-3 mb-3">
                          <Link
                            to="#"
                            className="nav-link active rounded fs-12 fw-semibold"
                            data-bs-toggle="tab"
                            data-bs-target="#leave"
                          >
                            Leaves
                          </Link>
                        </li>
                        <li className="mb-3">
                          <Link
                            to="#"
                            className="nav-link rounded fs-12 fw-semibold"
                            data-bs-toggle="tab"
                            data-bs-target="#attendance"
                          >
                            Attendance
                          </Link>
                        </li>
                      </ul>
                    </div>
                  </div>
                  {/* /Leave Nav*/}
                  <div className="tab-content">
                    {/* Leave */}
                    <div className="tab-pane fade show active" id="leave">
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
                        {/* Leaves List */}
                        <div className="card-body p-0 py-3">
                          <Table
                            dataSource={data}
                            columns={columns}
                            Selection={false}
                          />
                        </div>
                        {/* /Leaves List */}
                      </div>
                    </div>
                    {/* /Leave */}
                    {/* Attendance */}
                    <div className="tab-pane fade" id="attendance">
                      <div className="card">
                        <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-1">
                          <h4 className="mb-3">Attendance</h4>
                          <div className="d-flex align-items-center flex-wrap">
                            <div className="d-flex align-items-center flex-wrap me-3">
                              <p className="text-dark mb-3 me-2">
                                Last Updated on : 25 May 2024
                              </p>
                              <Link
                                to="#"
                                className="btn btn-primary btn-icon btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0 mb-3"
                              >
                                <i className="ti ti-refresh-dot" />
                              </Link>
                            </div>
                            <div className="dropdown mb-3">
                              <Link
                                to="#"
                                className="btn btn-outline-light bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                              >
                                <i className="ti ti-calendar-due me-2" />
                                Year : 2024 / 2025
                              </Link>
                              <ul className="dropdown-menu p-3">
                                <li>
                                  <Link
                                    to="#"
                                    className="dropdown-item rounded-1"
                                  >
                                    Year : 2024 / 2025
                                  </Link>
                                </li>
                                <li>
                                  <Link
                                    to="#"
                                    className="dropdown-item rounded-1"
                                  >
                                    Year : 2023 / 2024
                                  </Link>
                                </li>
                                <li>
                                  <Link
                                    to="#"
                                    className="dropdown-item rounded-1"
                                  >
                                    Year : 2022 / 2023
                                  </Link>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pb-1">
                          <div className="row">
                            {/* Total Present */}
                            <div className="col-md-6 col-xxl-3 d-flex">
                              <div className="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                <span className="avatar avatar-lg bg-primary-transparent rounded me-2 flex-shrink-0 text-primary">
                                  <i className="ti ti-user-check fs-24" />
                                </span>
                                <div className="ms-2">
                                  <p className="mb-1">Present</p>
                                  <h5>265</h5>
                                </div>
                              </div>
                            </div>
                            {/* /Total Present */}
                            {/* Total Absent */}
                            <div className="col-md-6 col-xxl-3 d-flex">
                              <div className="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                <span className="avatar avatar-lg bg-danger-transparent rounded me-2 flex-shrink-0 text-danger">
                                  <i className="ti ti-user-check fs-24" />
                                </span>
                                <div className="ms-2">
                                  <p className="mb-1">Absent</p>
                                  <h5>05</h5>
                                </div>
                              </div>
                            </div>
                            {/* /Total Absent */}
                            {/* Half Day */}
                            <div className="col-md-6 col-xxl-3 d-flex">
                              <div className="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                <span className="avatar avatar-lg bg-info-transparent rounded me-2 flex-shrink-0 text-info">
                                  <i className="ti ti-user-check fs-24" />
                                </span>
                                <div className="ms-2">
                                  <p className="mb-1">Half Day</p>
                                  <h5>01</h5>
                                </div>
                              </div>
                            </div>
                            {/* /Half Day */}
                            {/* Late to School*/}
                            <div className="col-md-6 col-xxl-3 d-flex">
                              <div className="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                <span className="avatar avatar-lg bg-warning-transparent rounded me-2 flex-shrink-0 text-warning">
                                  <i className="ti ti-user-check fs-24" />
                                </span>
                                <div className="ms-2">
                                  <p className="mb-1">Late</p>
                                  <h5>12</h5>
                                </div>
                              </div>
                            </div>
                            {/* /Late to School*/}
                          </div>
                        </div>
                      </div>
                      <div className="card">
                        <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-1">
                          <h4 className="mb-3">Leave &amp; Attendance</h4>
                          <div className="d-flex align-items-center flex-wrap">
                            <div className="dropdown mb-3 me-3">
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
                            <div className="dropdown mb-3">
                              <Link
                                to="#"
                                className="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"
                                data-bs-toggle="dropdown"
                              >
                                <i className="ti ti-file-export me-2" />
                                Export
                              </Link>
                              <ul className="dropdown-menu  dropdown-menu-end p-3">
                                <li>
                                  <Link
                                    to="#"
                                    className="dropdown-item rounded-1"
                                  >
                                    <i className="ti ti-file-type-pdf me-2" />
                                    Export as PDF
                                  </Link>
                                </li>
                                <li>
                                  <Link
                                    to="#"
                                    className="dropdown-item rounded-1"
                                  >
                                    <i className="ti ti-file-type-xls me-2" />
                                    Export as Excel{" "}
                                  </Link>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>
                        <div className="card-body p-0 py-3">
                          <div className="px-3">
                            <div className="d-flex align-items-center flex-wrap">
                              <div className="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                <span className="avatar avatar-sm bg-success rounded me-2 flex-shrink-0 ">
                                  <i className="ti ti-checks" />
                                </span>
                                <p className="text-dark">Present</p>
                              </div>
                              <div className="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                <span className="avatar avatar-sm bg-danger rounded me-2 flex-shrink-0 ">
                                  <i className="ti ti-x" />
                                </span>
                                <p className="text-dark">Absent</p>
                              </div>
                              <div className="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                <span className="avatar avatar-sm bg-pending rounded me-2 flex-shrink-0 ">
                                  <i className="ti ti-clock-x" />
                                </span>
                                <p className="text-dark">Late</p>
                              </div>
                              <div className="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                <span className="avatar avatar-sm bg-dark rounded me-2 flex-shrink-0 ">
                                  <i className="ti ti-calendar-event" />
                                </span>
                                <p className="text-dark">Halfday</p>
                              </div>
                              <div className="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                <span className="avatar avatar-sm bg-info rounded me-2 flex-shrink-0 ">
                                  <i className="ti ti-calendar-event" />
                                </span>
                                <p className="text-dark">Holiday</p>
                              </div>
                            </div>
                          </div>
                          {/* Attendance List */}
                         
                          <Table
                            dataSource={data2}
                            columns={columns2}
                            Selection={false}
                          />
                          {/* /Attendance List */}
                        </div>
                      </div>
                    </div>
                    {/* /Attendance */}
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
  );
};

export default StudentLeaves;
