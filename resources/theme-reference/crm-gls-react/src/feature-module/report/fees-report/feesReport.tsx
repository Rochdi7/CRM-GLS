
import { Link } from "react-router-dom";
import CommonSelect from "../../../core/common/commonSelect";
import {
  classes,
  sections,
  studentName,
} from "../../../core/common/selectoption/selectoption";
import TooltipOption from "../../../core/common/tooltipOption";
import PredefinedDateRanges from "../../../core/common/datePicker";
import Table from "../../../core/common/dataTable/index";
import { fees_report_data } from "../../../core/data/json/fees_report_data";
import type { TableData } from "../../../core/data/interface";
import { all_routes } from "../../router/all_routes";

const FeesReport = () => {
  const data = fees_report_data;
  const routes = all_routes; 

  const columns = [
    {
      title: "Fees Group",
      dataIndex: "feesGroup",
      key: "feesGroup",
      sorter: (a: TableData, b: TableData) =>
        a.feesGroup.length - b.feesGroup.length,
      render: (text: any, record: any) => (
        <p className="link-primary">
          {text}
          <span className="d-block">{record.feesDescription}</span>
        </p>
      ),
    },
    {
      title: "Fees Code",
      dataIndex: "feesCode",
      key: "feesCode",
      sorter: (a: TableData, b: TableData) =>
        a.feesCode.length - b.feesCode.length,
    },
    {
      title: "Due Date",
      dataIndex: "dueDate",
      key: "dueDate",
      sorter: (a: TableData, b: TableData) =>
        a.dueDate.length - b.dueDate.length,
    },
    {
      title: "Amount $",
      dataIndex: "amount",
      key: "amount",
      sorter: (a: TableData, b: TableData) => a.amount.length - b.amount.length,
    },
    {
      title: "Status",
      dataIndex: "status",
      key: "status",
      sorter: (a: TableData, b: TableData) => a.status.length - b.status.length,
      render: (status: any) =>
        status ? (
          <span className="badge badge-soft-success d-inline-flex align-items-center">
            <i className="ti ti-circle-filled fs-5 me-1" />
            {status}
          </span>
        ) : (
          <></>
        ),
    },
    {
      title: "Ref ID",
      dataIndex: "refId",
      key: "refId",
      sorter: (a: TableData, b: TableData) => a.refId.length - b.refId.length,
    },
    {
      title: "Mode",
      dataIndex: "mode",
      key: "mode",
      sorter: (a: TableData, b: TableData) => a.mode.length - b.mode.length,
    },
    {
      title: "Date Paid",
      dataIndex: "datePaid",
      key: "datePaid",
      sorter: (a: TableData, b: TableData) =>
        a.datePaid.length - b.datePaid.length,
    },
    {
      title: "Discount ($)",
      dataIndex: "discount",
      key: "discount",
      sorter: (a: TableData, b: TableData) =>
        a.discount.length - b.discount.length,
    },
    {
      title: "Fine ($)",
      dataIndex: "fine",
      key: "fine",
      sorter: (a: TableData, b: TableData) => a.fine.length - b.fine.length,
    },
    {
      title: "Balance ($)",
      dataIndex: "balance",
      key: "balance",
      sorter: (a: TableData, b: TableData) =>
        a.balance.length - b.balance.length,
    },
  ];

  return (
    <div>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Fees Report</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Report</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Fees Report
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
              <h4 className="mb-3">Fees Report List</h4>
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
                              <label className="form-label">Students</label>
                              <CommonSelect
                                className="select"
                                options={studentName}
                                defaultValue={studentName[0]}
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
                      <Link to="#" className="dropdown-item rounded-1">
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

export default FeesReport;
