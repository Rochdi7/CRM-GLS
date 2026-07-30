
import { Link } from "react-router-dom";
import { all_routes } from "../../../router/all_routes";
import StudentModals from "../studentModals";
import StudentSidebar from "./studentSidebar";
import StudentBreadcrumb from "./studentBreadcrumb";


const StudentFees = () => {
  const routes = all_routes;
 
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
                      <Link to={routes.studentLeaves} className="nav-link ">
                        <i className="ti ti-calendar-due me-2" />
                        Leave &amp; Attendance
                      </Link>
                    </li>
                    <li>
                      <Link to={routes.studentFees} className="nav-link active">
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
                  <div className="card">
                    <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                      <h4 className="mb-3">Fees</h4>
                      <div className="d-flex align-items-center flex-wrap">
                        <div className="dropdown mb-3 me-2">
                          <Link
                            to=""
                            className="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                          >
                            <i className="ti ti-calendar-due me-2" />
                            Year : 2024 / 2025
                          </Link>
                          <ul className="dropdown-menu p-3">
                            <li>
                              <Link to="" className="dropdown-item rounded-1">
                                Year : 2024 / 2025
                              </Link>
                            </li>
                            <li>
                              <Link to="" className="dropdown-item rounded-1">
                                Year : 2023 / 2024
                              </Link>
                            </li>
                            <li>
                              <Link to="" className="dropdown-item rounded-1">
                                Year : 2022 / 2023
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="card-body p-0 py-3">
                      {/* Fees List */}
                      <div className="custom-datatable-filter table-responsive">
                        <table className="table datatable">
                          <thead className="thead-light">
                            <tr>
                              <th>Fees Group</th>
                              <th>Fees Code</th>
                              <th>Due Date</th>
                              <th>Amount $ </th>
                              <th>Status</th>
                              <th>Ref ID</th>
                              <th>Mode</th>
                              <th>Date Paid</th>
                              <th>Discount ($)</th>
                              <th>Fine ($)</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td>
                                <p className="text-primary fees-group">
                                  Class 1 General
                                  <span className="d-block">
                                    (Admission Fees)
                                  </span>
                                </p>
                              </td>
                              <td>admission-fees</td>
                              <td>25 Mar 2024</td>
                              <td>2000</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435454</td>
                              <td>Cash</td>
                              <td>25 Jan 2024</td>
                              <td>10%</td>
                              <td>200</td>
                            </tr>
                            <tr>
                              <td>
                                <p className="text-primary">
                                  Class 1 General{" "}
                                  <span className="d-block">
                                    {" "}
                                    (Mar month fees)
                                  </span>
                                </p>
                              </td>
                              <td>mar-month-fees</td>
                              <td>10 Apr 2024</td>
                              <td>2500</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435453</td>
                              <td>Cash</td>
                              <td>03 Apr 2024</td>
                              <td>10%</td>
                              <td>0</td>
                            </tr>
                            <tr>
                              <td>
                                <p className="text-primary">
                                  Class 1 General{" "}
                                  <span className="d-block">
                                    (Apr month Fees)
                                  </span>
                                </p>
                              </td>
                              <td>apr-month-fees</td>
                              <td>10 May 2024</td>
                              <td>2500</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435453</td>
                              <td>Cash</td>
                              <td>03 Apr 2024</td>
                              <td>10%</td>
                              <td>0</td>
                            </tr>
                            <tr>
                              <td>
                                <p className="text-primary">
                                  Class 1 General{" "}
                                  <span className="d-block">
                                    (May month Fees)
                                  </span>
                                </p>
                              </td>
                              <td>may-month-fees</td>
                              <td>10 Jun 2024</td>
                              <td>2500</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435451</td>
                              <td>Cash</td>
                              <td>02 Jun 2024</td>
                              <td>10%</td>
                              <td>200</td>
                            </tr>
                            <tr>
                              <td>
                                <p className="text-primary">
                                  Class 1 General{" "}
                                  <span className="d-block">
                                    (Jun month Fees)
                                  </span>
                                </p>
                              </td>
                              <td>jun-month-fees</td>
                              <td>10 Jul 2024</td>
                              <td>2500</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435450</td>
                              <td>Cash</td>
                              <td>05 Jul 2024</td>
                              <td>10%</td>
                              <td>200</td>
                            </tr>
                            <tr>
                              <td>
                                <p className="text-primary">
                                  Class 1 General{" "}
                                  <span className="d-block">
                                    (Jul month Fees)
                                  </span>
                                </p>
                              </td>
                              <td>jul-month-fees</td>
                              <td>10 Aug 2024</td>
                              <td>2500</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435449</td>
                              <td>Cash</td>
                              <td>01 Aug 2024</td>
                              <td>10%</td>
                              <td>200</td>
                            </tr>
                            <tr>
                              <td>
                                <p className="text-primary">
                                  Class 1 General{" "}
                                  <span className="d-block">
                                    (Dec month Fees)
                                  </span>
                                </p>
                              </td>
                              <td>dec-month-fees</td>
                              <td>10 Jan 2024</td>
                              <td>2500</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435443</td>
                              <td>Cash</td>
                              <td>05 Jan 2024</td>
                              <td>10%</td>
                              <td>0</td>
                            </tr>
                            <tr>
                              <td>
                                <p className="text-primary">
                                  Class 1 General{" "}
                                  <span className="d-block">
                                    (Jan month Fees)
                                  </span>
                                </p>
                              </td>
                              <td>jan-month-fees</td>
                              <td>10 Feb 2024</td>
                              <td>2000</td>
                              <td>
                                <span className="badge badge-soft-success d-inline-flex align-items-center">
                                  <i className="ti ti-circle-filled fs-5 me-1" />
                                  Paid
                                </span>
                              </td>
                              <td>#435443</td>
                              <td>Cash</td>
                              <td>01 Feb 2024</td>
                              <td>10%</td>
                              <td>200</td>
                            </tr>
                            <tr>
                              <td className="bg-dark">-</td>
                              <td className="bg-dark" />
                              <td className="bg-dark" />
                              <td className="bg-dark text-white">2000</td>
                              <td className="bg-dark" />
                              <td className="bg-dark" />
                              <td className="bg-dark" />
                              <td className="bg-dark" />
                              <td className="bg-dark text-white">200</td>
                              <td className="bg-dark text-white">200</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      {/* /Fees List */}
                    </div>
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

export default StudentFees;
