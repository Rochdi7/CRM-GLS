
import ImageWithBasePath from "../../../../core/common/imageWithBasePath";
import { Link } from "react-router-dom";
import { all_routes } from "../../../router/all_routes";

const StaffDetails = () => {
  const routes = all_routes
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
              <div className="stickybar pb-4">
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
                <div className="card border-white mb-0">
                  <div className="card-body">
                    <h5 className="mb-3">Primary Contact Info</h5>
                    <div className="d-flex align-items-center mb-3">
                      <span className="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default">
                        <i className="ti ti-phone" />
                      </span>
                      <div>
                        <span className="mb-1 fw-medium text-dark ">
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
                        <span className="mb-1 fw-medium text-dark ">
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
                          className="nav-link active"
                        >
                          <i className="ti ti-info-square-rounded me-2" />
                          Basic Details
                        </Link>
                      </li>
                      <li>
                        <Link to={routes.staffPayroll} className="nav-link">
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
                        <Link to={routes.staffsAttendance} className="nav-link">
                          <i className="ti ti-calendar-due me-2" />
                          Attendance
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
                <div className="row">
                  {/* Address */}
                  <div className="col-xxl-6 d-flex">
                    <div className="card flex-fill">
                      <div className="card-header">
                        <h5>Address</h5>
                      </div>
                      <div className="card-body">
                        <div className="d-flex align-items-center mb-3">
                          <span className="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default">
                            <i className="ti ti-map-pin-up" />
                          </span>
                          <div>
                            <p className="mb-1 fw-medium text-dark ">
                              Current Address
                            </p>
                            <p>3495 Red Hawk Road, Buffalo Lake, MN 55314</p>
                          </div>
                        </div>
                        <div className="d-flex align-items-center">
                          <span className="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default">
                            <i className="ti ti-map-pins" />
                          </span>
                          <div>
                            <p className="mb-1 fw-medium text-dark ">
                              Permanent Address
                            </p>
                            <p>3495 Red Hawk Road, Buffalo Lake, MN 55314</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  {/* /Address */}
                  {/* Documents */}
                  <div className="col-xxl-6 d-flex">
                    <div className="card flex-fill">
                      <div className="card-header">
                        <h5>Documents</h5>
                      </div>
                      <div className="card-body">
                        <div className="bg-light-300 border rounded d-flex align-items-center justify-content-between mb-3 p-2">
                          <div className="d-flex align-items-center overflow-hidden">
                            <span className="avatar avatar-md bg-white rounded flex-shrink-0 text-default">
                              <i className="ti ti-pdf fs-15" />
                            </span>
                            <div className="ms-2">
                              <p className="text-truncate fw-medium text-dark ">
                                Resume.pdf
                              </p>
                            </div>
                          </div>
                          <Link to="#" className="btn btn-dark btn-icon btn-sm">
                            <i className="ti ti-download" />
                          </Link>
                        </div>
                        <div className="bg-light-300 border rounded d-flex align-items-center justify-content-between p-2">
                          <div className="d-flex align-items-center overflow-hidden">
                            <span className="avatar avatar-md bg-white rounded flex-shrink-0 text-default">
                              <i className="ti ti-pdf fs-15" />
                            </span>
                            <div className="ms-2">
                              <p className="text-truncate fw-medium text-dark ">
                                Joining Letter.pdf
                              </p>
                            </div>
                          </div>
                          <Link to="#" className="btn btn-dark btn-icon btn-sm">
                            <i className="ti ti-download" />
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                  {/* /Documents */}
                  {/* Bank Details */}
                  <div className="col-xxl-12 d-flex">
                    <div className="card flex-fill">
                      <div className="card-header">
                        <h5>Bank Details</h5>
                      </div>
                      <div className="card-body pb-1">
                        <div className="row">
                          <div className="col-md-4">
                            <div className="mb-3">
                              <p className="mb-1 fw-medium text-dark ">
                                Account Name
                              </p>
                              <p>Bank of America</p>
                            </div>
                          </div>
                          <div className="col-md-4">
                            <div className="mb-3">
                              <p className="mb-1 fw-medium text-dark ">
                                Account Number
                              </p>
                              <p>178849035684</p>
                            </div>
                          </div>
                          <div className="col-md-4">
                            <div className="mb-3">
                              <p className="mb-1 fw-medium text-dark ">
                                Bank Name
                              </p>
                              <p>Bank of America</p>
                            </div>
                          </div>
                          <div className="col-md-4">
                            <div className="mb-3">
                              <p className="mb-1 fw-medium text-dark ">
                                Branch
                              </p>
                              <p>Cincinnati</p>
                            </div>
                          </div>
                          <div className="col-md-4">
                            <div className="mb-3">
                              <p className="mb-1 fw-medium text-dark ">IFSC</p>
                              <p>BOA83209832</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  {/* /Bank Details */}
                  {/* Other Info */}
                  <div className="col-xxl-12">
                    <div className="card">
                      <div className="card-header">
                        <h5>Other Info</h5>
                      </div>
                      <div className="card-body">
                        <p>
                          Depending on the specific needs of your organization
                          or system, additional information may be collected or
                          tracked. It's important to ensure that any data
                          collected complies with privacy regulations and
                          policies to protect students' sensitive information.
                        </p>
                      </div>
                    </div>
                  </div>
                  {/* /Other Info */}
                </div>
              </div>
            </div>
          </div>
        </div>
        {/* /Page Wrapper */}
        {/* Login Details */}
        <div className="modal fade" id="login_detail">
          <div className="modal-dialog modal-dialog-centered  modal-lg">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Login Details</h4>
                <button
                  type="button"
                  className="btn-close custom-btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x" />
                </button>
              </div>
              <div className="modal-body">
                <div className="student-detail-info">
                  <span className="student-img">
                    <ImageWithBasePath
                      src="assets/img/teachers/teacher-01.jpg"
                      alt="img"
                    />
                  </span>
                  <div className="name-info">
                    <h6>
                      Teresa <span>III, A</span>
                    </h6>
                  </div>
                </div>
                <div className="table-responsive custom-table no-datatable_length">
                  <table className="table datanew">
                    <thead className="thead-light">
                      <tr>
                        <th>User Type</th>
                        <th>User Name</th>
                        <th>Password </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Teacher</td>
                        <td>teacher20</td>
                        <td>teacher@53</td>
                      </tr>
                      <tr>
                        <td>Parent</td>
                        <td>parent53</td>
                        <td>parent@53</td>
                      </tr>
                    </tbody>
                  </table>
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
              </div>
            </div>
          </div>
        </div>
        {/* /Login Details */}
      </>
    </div>
  );
};

export default StaffDetails;
