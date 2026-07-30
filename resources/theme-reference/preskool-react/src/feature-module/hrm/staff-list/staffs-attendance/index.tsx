
import ImageWithBasePath from "../../../../core/common/imageWithBasePath";
import { Link } from "react-router-dom";
import { all_routes } from "../../../router/all_routes";
import { staffsAttendance } from "../../../../core/data/json/staffs_attendance";
import type { TableData } from "../../../../core/data/interface";
import Table from "../../../../core/common/dataTable/index";

const StaffsAttendance = () => {
  const routes = all_routes;
  const data = staffsAttendance;
  const columns = [
    {
      title: "Date/Time",
      dataIndex: "rollNo",
      sorter: (a: any, b: any) => a.rollNo.length - b.rollNo.length,
    },

    {
      title: "Jun",
      dataIndex: "Jun",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-danger"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Jun.length - b.Jun.length,
    },
    {
      title: "Jul",
      dataIndex: "Jul",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-danger"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Jul.length - b.Jul.length,
    },
    {
      title: "Aug",
      dataIndex: "Aug",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-danger"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Aug.length - b.Aug.length,
    },
    {
      title: "Sep",
      dataIndex: "Sep",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-danger"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Sep.length - b.Sep.length,
    },
    {
      title: "Oct",
      dataIndex: "Oct",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-pending"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Oct.length - b.Oct.length,
    },
    {
      title: "Nov",
      dataIndex: "Nov",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-pending"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Nov.length - b.Nov.length,
    },
    {
      title: "Dec",
      dataIndex: "Dec",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-pending"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Dec.length - b.Dec.length,
    },
    {
      title: "Jan",
      dataIndex: "Jan",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-pending"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Jan.length - b.Jan.length,
    },
    {
      title: "Feb",
      dataIndex: "Feb",
      render: (text: string) => {
        if (text === "") {
          return null;
        }

        return (
          <>
            {text === "green" ? (
              <span className="attendance-range bg-success"></span>
            ) : (
              <span className="attendance-range bg-pending"></span>
            )}
          </>
        );
      },
      sorter: (a: TableData, b: TableData) => a.Feb.length - b.Feb.length,
    },
    {
      title: "Mar",
      dataIndex: "Mar",
      sorter: (a: TableData, b: TableData) => a.Mar.length - b.Mar.length,
    },
    {
      title: "Apr",
      dataIndex: "Apr",
      sorter: (a: TableData, b: TableData) => a.Apr.length - b.Apr.length,
    },
  ];
  return (
    <div>
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
          </div>
          <div className="row">
            {/* Staff Information */}
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
            {/* /Staff Information */}
            <div className="col-xxl-9 col-xl-8">
              <div className="row">
                <div className="col-md-12">
                  {/* List */}
                  <ul className="nav nav-tabs nav-tabs-bottom mb-4">
                    <li>
                      <Link to={routes.staffDetails} className="nav-link">
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
                      <Link to={routes.staffLeave} className="nav-link">
                        <i className="ti ti-calendar-due me-2" />
                        Leaves
                      </Link>
                    </li>
                    <li>
                      <Link
                        to={routes.staffsAttendance}
                        className="nav-link active"
                      >
                        <i className="ti ti-calendar-due me-2" />
                        Attendance
                      </Link>
                    </li>
                  </ul>
                  {/* /List */}
                  {/* Attendance */}
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
                              <Link to="#" className="dropdown-item rounded-1">
                                Year : 2024 / 2025
                              </Link>
                            </li>
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
                                Year : 2023 / 2024
                              </Link>
                            </li>
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
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
                      <h4 className="mb-3">Attendance</h4>
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
                              <Link to="#" className="dropdown-item rounded-1">
                                This Year
                              </Link>
                            </li>
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
                                This Month
                              </Link>
                            </li>
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
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
                              <Link to="#" className="dropdown-item rounded-1">
                                <i className="ti ti-file-type-pdf me-2" />
                                Export as PDF
                              </Link>
                            </li>
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
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
                      <Table columns={columns} dataSource={data} />
                      {/* /Attendance List */}
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
  );
};

export default StaffsAttendance;
