import { useRef } from "react";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import TooltipOption from "../../../core/common/tooltipOption";
import PredefinedDateRanges from "../../../core/common/datePicker";
import CommonSelect from "../../../core/common/commonSelect";
import Table from "../../../core/common/dataTable/index";
import {

  date,

} from "../../../core/common/selectoption/selectoption";

import type { TableData } from "../../../core/data/interface";
import { dailyAttendanceData } from "../../../core/data/json/dailyAttendanceData";

const DailyAttendance = () => {
  const routes = all_routes;
 
  const dropdownMenuRef = useRef<HTMLDivElement | null>(null);
  const handleApplyClick = () => {
    if (dropdownMenuRef.current) {
      dropdownMenuRef.current.classList.remove("show");
    }
  };
  const data = dailyAttendanceData;

  const columns = [
    {
      title: "Class",
      dataIndex: "class",
      sorter: (a: TableData, b: TableData) => a.class.length - b.class.length,
    },
    {
      title: " Section",
      dataIndex: "section",
      sorter: (a: TableData, b: TableData) => a.section.length - b.section.length,
    },
    {
      title: " Total Present",
      dataIndex: "present",
      sorter: (a: TableData, b: TableData) => a.present.length - b.present.length,
    },
    {
      title: " Total Absent",
      dataIndex: "present",
      sorter: (a: TableData, b: TableData) => a.absent.length - b.absent.length,
    },
    {
      title: " Present %",
      dataIndex: "percentange",
      sorter: (a: TableData, b: TableData) => a.percentange.length - b.percentange.length,
    },
    {
      title: " Absent %",
      dataIndex: "percentange",
      sorter: (a: TableData, b: TableData) => a.absentPercentange.length - b.absentPercentange.length,
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
              <h3 className="page-title mb-1">Daily Attendance</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Report</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                  Daily Attendance
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
                  <Link to={routes.attendanceReport}>Attendance Report</Link>
                </li>
                <li>
                  <Link to={routes.studentAttendanceType} >
                    Students Attendance Type
                  </Link>
                </li>
                <li>
                  <Link to={routes.dailyAttendance} className="active">Daily Attendance</Link>
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
         
          {/* Attendance List */}
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
              <h4 className="mb-3">Daily Attendance</h4>
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
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Attendance Date</label>

                              <CommonSelect
                                className="select"
                                options={date}
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

export default DailyAttendance;
