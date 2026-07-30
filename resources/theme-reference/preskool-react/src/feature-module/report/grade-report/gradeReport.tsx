
import { Link } from "react-router-dom";
import Table from "../../../core/common/dataTable/index";
import { grade_report_data } from "../../../core/data/json/grade_report_data";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { all_routes } from "../../router/all_routes";
import type { TableData } from "../../../core/data/interface";
import PredefinedDateRanges from "../../../core/common/datePicker";
import CommonSelect from "../../../core/common/commonSelect";
import {
  classes,
  examtwo,
  sections,
} from "../../../core/common/selectoption/selectoption";
import TooltipOption from "../../../core/common/tooltipOption";

const GradeReport = () => {
  const routes = all_routes;
  const data = grade_report_data;
  const columns = [
    {
      title: "Admission No",
      dataIndex: "admissionNo",
      key: "admissionNo",
      sorter: (a: TableData, b: TableData) =>
        a.admissionNo.length - b.admissionNo.length,
      render: (text: any) => (
        <Link to="#" className="link-primary">
          {text}
        </Link>
      ),
    },
    {
      title: "Student Name",
      dataIndex: "studentName",
      key: "studentName",
      sorter: (a: TableData, b: TableData) =>
        a.studentName.length - b.studentName.length,
      render: (text: any, record: any) => (
        <div className="d-flex align-items-center">
          <Link to={routes.studentDetail} className="avatar avatar-md">
            <ImageWithBasePath
              src={record.avatar}
              className="img-fluid rounded-circle"
              alt="img"
            />
          </Link>
          <div className="ms-2">
            <p className="text-dark mb-0">
              <Link to={routes.studentDetail}>{text}</Link>
            </p>
            <span className="fs-12">Roll No : {record.rollNo}</span>
          </div>
        </div>
      ),
    },
    {
      title: "English",
      dataIndex: "english",
      key: "english",
      sorter: (a: TableData, b: TableData) =>
        a.english.length - b.english.length,
    },
    {
      title: "Spanish",
      dataIndex: "spanish",
      key: "spanish",
      sorter: (a: TableData, b: TableData) =>
        a.spanish.length - b.spanish.length,
    },
    {
      title: "Physics",
      dataIndex: "physics",
      key: "physics",
      sorter: (a: TableData, b: TableData) =>
        a.physics.length - b.physics.length,
    },
    {
      title: "Chemistry",
      dataIndex: "chemistry",
      key: "chemistry",
      sorter: (a: TableData, b: TableData) =>
        a.chemistry.length - b.chemistry.length,
    },
    {
      title: "Maths",
      dataIndex: "maths",
      key: "maths",
      sorter: (a: TableData, b: TableData) => a.maths.length - b.maths.length,
    },
    {
      title: "Computer",
      dataIndex: "computer",
      key: "computer",
      sorter: (a: TableData, b: TableData) =>
        a.computer.length - b.computer.length,
    },
    {
      title: "Env Science",
      dataIndex: "envScience",
      key: "envScience",
      sorter: (a: TableData, b: TableData) =>
        a.envScience.length - b.envScience.length,
    },
    {
      title: "Total",
      dataIndex: "total",
      key: "total",
      sorter: (a: TableData, b: TableData) => a.total.length - b.total.length,
    },
    {
      title: "Percent(%)",
      dataIndex: "percent",
      key: "percent",
      sorter: (a: TableData, b: TableData) =>
        a.percent.length - b.percent.length,
    },
    {
      title: "Grade",
      dataIndex: "grade",
      key: "grade",
      sorter: (a: TableData, b: TableData) => a.grade.length - b.grade.length,
      render: (text: any, record: any) => (
        <span className={record.textColor ? `text-${record.textColor}` : ""}>
          {text}
        </span>
      ),
    },
  ];

  return (
    <div>
      {" "}
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Grade Report</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Report</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Grade Report
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
              <h4 className="mb-3">Grade Report List</h4>
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
                    <form>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <h4>Filter</h4>
                      </div>
                      <div className="p-3 border-bottom">
                        <div className="row">
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Class</label>
                              <CommonSelect
                                className="select"
                                options={classes}
                                defaultValue={classes[0]}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Section</label>
                              <CommonSelect
                                className="select"
                                options={sections}
                                defaultValue={sections[0]}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-0">
                              <label className="form-label">Exam Type</label>
                              <CommonSelect
                                className="select"
                                options={examtwo}
                                defaultValue={examtwo[0]}
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
              <Table dataSource={data} columns={columns} Selection={true} />
              {/* /Student List */}
            </div>
          </div>
          {/* /Student List */}
        </div>
      </div>
      {/* /Page Wrapper */}
    </div>
  );
};

export default GradeReport;
