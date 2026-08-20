/* eslint-disable */
import { useRef } from "react";
import { all_routes } from "../../router/all_routes";
import { studentreport } from "../../../core/data/json/student_report";
import Table from "../../../core/common/dataTable/index";
import { Link } from "react-router-dom";
import type { TableData } from "../../../core/data/interface";
import TooltipOption from "../../../core/common/tooltipOption";
import PredefinedDateRanges from "../../../core/common/datePicker";
import CommonSelect from "../../../core/common/commonSelect";
import {
  classSection,
  classSylabus,
  gender,
  status,
  studentName,
} from "../../../core/common/selectoption/selectoption";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";

const StudentReport = () => {
  const data = studentreport;
  const routes = all_routes;
  const columns = [
    {
      title: "Admission No",
      dataIndex: "admissionNo",
      render: ( record: any) => (
        <>
          <Link to="#" className="link-primary">
            {record.admissionNo}
          </Link>
        </>
      ),
      sorter: (a: TableData, b: TableData) =>
        a.admissionNo.length - b.admissionNo.length,
    },

    {
      title: "Roll No",
      dataIndex: "rollNo",
      sorter: (a: TableData, b: TableData) => a.rollNo.length - b.rollNo.length,
    },
    {
      title: "Name",
      dataIndex: "name",
      render: (_text:any, record: any) => (
        <>
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
                <Link to="#">{record.name}</Link>
              </p>
            </div>
          </div>
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.name.length - b.name.length,
    },
    {
      title: "Class",
      dataIndex: "class",
      sorter: (a: TableData, b: TableData) => a.class.length - b.class.length,
    },
    {
      title: "Section",
      dataIndex: "section",
      sorter: (a: TableData, b: TableData) =>
        a.section.length - b.section.length,
    },
    {
      title: "Gender",
      dataIndex: "gender",
      sorter: (a: TableData, b: TableData) => a.gender.length - b.gender.length,
    },
    {
      title: "Parent",
      dataIndex: "parent",
      render: (_text:any, record: any) => (
        <>
          <div className="d-flex align-items-center">
            <Link to="#" className="avatar avatar-md">
              <ImageWithBasePath
                src={record.parentimg}
                className="img-fluid rounded-circle"
                alt="img"
              />
            </Link>
            <div className="ms-2">
              <p className="text-dark mb-0">
                <Link to="#">{record.parent}</Link>
              </p>
            </div>
          </div>
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.parent.length - b.parent.length,
    },
    {
      title: "Date Of Join",
      dataIndex: "dateOfJoin",
      sorter: (a: TableData, b: TableData) =>
        a.dateOfJoin.length - b.dateOfJoin.length,
    },
    {
      title: "DOB",
      dataIndex: "dob",
      sorter: (a: TableData, b: TableData) => a.dob.length - b.dob.length,
    },
    {
      title: "Status",
      dataIndex: "status",
      render: (text: string) => (
        <>
          {text === "Active" ? (
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
                <h3 className="page-title mb-1">Student Report</h3>
                <nav>
                  <ol className="breadcrumb mb-0">
                    <li className="breadcrumb-item">
                      <Link to={routes.adminDashboard}>Dashboard</Link>
                    </li>
                    <li className="breadcrumb-item">
                      <Link to="#">Report</Link>
                    </li>
                    <li className="breadcrumb-item active" aria-current="page">
                      Student Report
                    </li>
                  </ol>
                </nav>
              </div>
              <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <TooltipOption />
              </div>
            </div>
            {/* /Page Header */}
            {/* Student List */}
            <div className="card">
              <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 className="mb-3">Student Report List</h4>
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
                                  options={classSylabus}
                                />
                              </div>
                            </div>
                            <div className="col-md-6">
                              <div className="mb-3">
                                <label className="form-label">Section</label>
                                <CommonSelect
                                  className="select"
                                  options={classSection}
                                />
                              </div>
                            </div>
                            <div className="col-md-12">
                              <div className="mb-3">
                                <label className="form-label">Name</label>
                                <CommonSelect
                                  className="select"
                                  options={studentName}
                                />
                              </div>
                            </div>
                            <div className="col-md-6">
                              <div className="mb-3">
                                <label className="form-label">Gender</label>
                                <CommonSelect
                                  className="select"
                                  options={gender}
                                />
                              </div>
                            </div>
                            <div className="col-md-6">
                              <div className="mb-3">
                                <label className="form-label">Status</label>
                                <CommonSelect
                                  className="select"
                                  options={status}
                                />
                              </div>
                            </div>
                            <div className="col-md-12">
                              <div className="mb-0">
                                <label className="form-label">
                                  Date of Join
                                </label>
                                <input type="date" className="form-control" />
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
                <Table columns={columns} dataSource={data} Selection={true} />
                {/* /Student List */}
              </div>
            </div>
            {/* /Student List */}
          </div>
        </div>
        {/* /Page Wrapper */}
      </>
    </div>
  );
};

export default StudentReport;
