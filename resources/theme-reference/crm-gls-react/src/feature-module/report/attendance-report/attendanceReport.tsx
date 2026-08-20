import { useRef } from "react";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import TooltipOption from "../../../core/common/tooltipOption";
import PredefinedDateRanges from "../../../core/common/datePicker";
import CommonSelect from "../../../core/common/commonSelect";
import Table from "../../../core/common/dataTable/index";
import {
  allClass,
  allSection,
  gender,
  names,
  status,
} from "../../../core/common/selectoption/selectoption";
import dayjs from "dayjs";
import { DatePicker } from "antd";
import type { TableData } from "../../../core/data/interface";
import { attendancereportData } from "../../../core/data/json/attendence_report";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";

const AttendanceReport = () => {
  const routes = all_routes;
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, "0"); // Month is zero-based, so we add 1
  const day = String(today.getDate()).padStart(2, "0");
  const formattedDate = `${month}-${day}-${year}`;
  const defaultValue = dayjs(formattedDate);
  const getModalContainer = () => {
    const modalElement = document.getElementById("modal-datepicker");
    return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
  };
  const dropdownMenuRef = useRef<HTMLDivElement | null>(null);
  const handleApplyClick = () => {
    if (dropdownMenuRef.current) {
      dropdownMenuRef.current.classList.remove("show");
    }
  };
  const data = attendancereportData;
  const renderTitle = (title1: any, title2: any) => {
    return (
      <>
        <div className="text-center">
          <span className="day-num d-block">{title1}</span>
          <span>{title2}</span>
        </div>
      </>
    );
  };
  const columns = [
    {
      title: "Student/Date",
      dataIndex: "name",
      render: (text: string, record: any) => (
        <div className="d-flex align-items-center">
          <Link to="#" className="avatar avatar-md">
            <ImageWithBasePath
              src={record.img}
              className="img-fluid rounded-circle"
              alt="img"
            />
          </Link>
          <div className="ms-2">
            <p className="text-dark mb-0">
              <Link to="#">{text}</Link>
            </p>
          </div>
        </div>
      ),
      sorter: (a: TableData, b: TableData) => a.name.length - b.name.length,
      fixed: "left",
    },
    {
      title: "%",
      dataIndex: "percentage",
      render: (text: string, record: any) => (
        <>
          <span className={record.percentClass}>{text}</span>
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.name.length - b.name.length,
    },
    {
      title: "P",
      dataIndex: "p",
    },
    {
      title: "L",
      dataIndex: "l",
    },
    {
      title: "A",
      dataIndex: "a",
    },
    {
      title: "H",
      dataIndex: "h",
    },
    {
      title: "F",
      dataIndex: "f",
    },
    {
      title: () => renderTitle("01", "M"),
      dataIndex: "m01",
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
    },
    {
      title: () => renderTitle("02", "T"),
      dataIndex: "t02",
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
    },
    {
      title: () => renderTitle("03", "W"),
      dataIndex: "w03",
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
    },
    {
      title: () => renderTitle("04", "T"),
      dataIndex: "t04",
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
    },
    {
      title: () => renderTitle("05", "F"),
      dataIndex: "f05",
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
    },
    {
      title: () => renderTitle("06", "S"),
      dataIndex: "s06",
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
    },
    {
      title: () => renderTitle("07", "S"),
      dataIndex: "s07",
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
    },
    {
      title: () => renderTitle("08", "M"),
      dataIndex: "m08",
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
    },
    {
      title: () => renderTitle("09", "T"),
      dataIndex: "t09",
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
    },
    {
      title: () => renderTitle("10", "W"),
      dataIndex: "w10",
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
    },
    {
      title: () => renderTitle("11", "T"),
      dataIndex: "t011",
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
    },
    {
      title: () => renderTitle("12", "F"),
      dataIndex: "f012",
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
    },
    {
      title: () => renderTitle("13", "S"),
      dataIndex: "s13",
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
    },
    {
      title: () => renderTitle("14", "S"),
      dataIndex: "s14",
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
    },
    {
      title: () => renderTitle("15", "M"),
      dataIndex: "m15",
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
    },
    {
      title: () => renderTitle("16", "T"),
      dataIndex: "t16",
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
    },
    {
      title: () => renderTitle("17", "W"),
      dataIndex: "w17",
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
    },
    {
      title: () => renderTitle("18", "T"),
      dataIndex: "t018",
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
    },
    {
      title: () => renderTitle("19", "F"),
      dataIndex: "f019",
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
    },
    {
      title: () => renderTitle("20", "S"),
      dataIndex: "s20",
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
    },
    {
      title: () => renderTitle("21", "S"),
      dataIndex: "s21",
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
    },
    {
      title: () => renderTitle("22", "M"),
      dataIndex: "m22",
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
    },
    {
      title: () => renderTitle("23", "T"),
      dataIndex: "t23",
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
    },
  ];
  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Attendance Report</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Report</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Attendance Report
                  </li>
                </ol>
              </nav>
            </div>
            <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
              <TooltipOption />
              <div className="dropdown me-2 mb-2">
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
                      <i className="ti ti-file-type-pdf me-1" />
                      Export as PDF
                    </Link>
                  </li>
                  <li>
                    <Link to="#" className="dropdown-item rounded-1">
                      <i className="ti ti-file-type-xls me-1" />
                      Export as Excel{" "}
                    </Link>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          {/* /Page Header */}
          {/* Filter Section */}
          <div className="filter-wrapper">
            {/* List Tab */}
            <div className="list-tab">
              <ul>
                <li>
                  <Link to={routes.attendanceReport} className="active">
                    Attendance Report
                  </Link>
                </li>
                <li>
                  <Link to={routes.studentAttendanceType}>
                    Students Attendance Type
                  </Link>
                </li>
                <li>
                  <Link to={routes.dailyAttendance}>Daily Attendance</Link>
                </li>
                <li>
                  <Link to={routes.studentDayWise}>Student Day Wise</Link>
                </li>
                <li>
                  <Link to={routes.teacherDayWise}>Teacher Day Wise</Link>
                </li>
                <li>
                  <Link to={routes.teacherReport}>Teacher Report</Link>
                </li>
                <li>
                  <Link to={routes.staffDayWise}>Staff Day Wise</Link>
                </li>
                <li>
                  <Link to={routes.staffReport}>Staff Report</Link>
                </li>
              </ul>
            </div>
            {/* /List Tab */}
          </div>
          {/* /Filter Section */}
          <div className="attendance-types page-header justify-content-end">
            <ul className="attendance-type-list">
              <li>
                <span className="attendance-icon bg-success">
                  <i className="ti ti-checks" />
                </span>
                Present
              </li>
              <li>
                <span className="attendance-icon bg-danger">
                  <i className="ti ti-x" />
                </span>
                Absent
              </li>
              <li>
                <span className="attendance-icon bg-pending">
                  <i className="ti ti-clock-x" />
                </span>
                Late
              </li>
              <li>
                <span className="attendance-icon bg-dark">
                  <i className="ti ti-calendar-event" />
                </span>
                Halfday
              </li>
              <li>
                <span className="attendance-icon bg-info">
                  <i className="ti ti-clock-up" />
                </span>
                Holiday
              </li>
            </ul>
          </div>
          {/* Attendance List */}
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
              <h4 className="mb-3">Attendance Report</h4>
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
                  <div
                    className="dropdown-menu drop-width"
                    ref={dropdownMenuRef}
                    id="modal-datepicker"
                  >
                    <form>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <h4>Filter</h4>
                      </div>
                      <div className="p-3 border-bottom">
                        <div className="row">
                          <div className="col-md-6">
                            <div className="mb-3">
                              <label className="form-label">Class</label>

                              <CommonSelect
                                className="select"
                                options={allClass}
                                defaultValue={undefined}
                              />
                            </div>
                          </div>
                          <div className="col-md-6">
                            <div className="mb-3">
                              <label className="form-label">Section</label>
                              <CommonSelect
                                className="select"
                                options={allSection}
                                defaultValue={undefined}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Name</label>
                              <CommonSelect
                                className="select"
                                options={names}
                                defaultValue={undefined}
                              />
                            </div>
                          </div>
                          <div className="col-md-6">
                            <div className="mb-3">
                              <label className="form-label">Gender</label>
                              <CommonSelect
                                className="select"
                                options={gender}
                                defaultValue={undefined}
                              />
                            </div>
                          </div>
                          <div className="col-md-6">
                            <div className="mb-3">
                              <label className="form-label">Status</label>
                              <CommonSelect
                                className="select"
                                options={status}
                                defaultValue={undefined}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-0">
                              <label className="form-label">Date of Join</label>
                              <div className="date-pic">
                                <DatePicker
                                  className="form-control datetimepicker"
                                  format={{
                                    format: "DD-MM-YYYY",
                                    type: "mask",
                                  }}
                                  getPopupContainer={getModalContainer}
                                  defaultValue={defaultValue}
                                  placeholder="16 May 2024"
                                />
                                <span className="cal-icon">
                                  <i className="ti ti-calendar" />
                                </span>
                              </div>
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
            <div className="card-body p-0 py-3">
              {/* Student List */}
              <Table dataSource={data} columns={columns} Selection={false} />
              {/* /Student List */}
            </div>
          </div>
          {/* /Attendance List */}
        </div>
      </div>
      {/* /Page Wrapper */}
    </>
  );
};

export default AttendanceReport;
