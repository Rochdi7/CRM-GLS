
import { Link } from 'react-router-dom'
import ImageWithBasePath from '../../../../core/common/imageWithBasePath'
import { all_routes } from '../../../router/all_routes'
import StudentModals from '../studentModals'
import StudentSidebar from './studentSidebar'
import StudentBreadcrumb from './studentBreadcrumb'

const StudentDetails = () => {
    const routes = all_routes
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
                  <Link to={routes.studentDetail} className="nav-link active">
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
                  <Link to={routes.studentLeaves} className="nav-link">
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
              {/* Parents Information */}
              <div className="card">
                <div className="card-header">
                  <h5>Parents Information</h5>
                </div>
                <div className="card-body">
                  <div className="border rounded p-3 pb-0 mb-3">
                    <div className="row">
                      <div className="col-sm-6 col-lg-4">
                        <div className="d-flex align-items-center mb-3">
                          <span className="avatar avatar-lg flex-shrink-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-13.jpg"
                              className="img-fluid rounded"
                              alt="img"
                            />
                          </span>
                          <div className="ms-2 overflow-hidden">
                            <h6 className="text-truncate">Jerald Vicinius</h6>
                            <p className="text-primary">Father</p>
                          </div>
                        </div>
                      </div>
                      <div className="col-sm-6 col-lg-4">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">Phone</p>
                          <p>+1 45545 46464</p>
                        </div>
                      </div>
                      <div className="col-sm-6 col-lg-4">
                        <div className="d-flex align-items-center justify-content-between">
                          <div className="mb-3 overflow-hidden me-3">
                            <p className="text-dark fw-medium mb-1">Email</p>
                            <p className="text-truncate">jera@example.com</p>
                          </div>
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            aria-label="Print"
                            data-bs-original-title="Reset Password"
                            className="btn btn-dark btn-icon btn-sm mb-3"
                          >
                            <i className="ti ti-lock-x" />
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="border rounded p-3 pb-0 mb-3">
                    <div className="row">
                      <div className="col-lg-4 col-sm-6 ">
                        <div className="d-flex align-items-center mb-3">
                          <span className="avatar avatar-lg flex-shrink-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-14.jpg"
                              className="img-fluid rounded"
                              alt="img"
                            />
                          </span>
                          <div className="ms-2 overflow-hidden">
                            <h6 className="text-truncate">Roberta Webber</h6>
                            <p className="text-primary">Mother</p>
                          </div>
                        </div>
                      </div>
                      <div className="col-lg-4 col-sm-6 ">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">Phone</p>
                          <p>+1 46499 24357</p>
                        </div>
                      </div>
                      <div className="col-lg-4 col-sm-6">
                        <div className="d-flex align-items-center justify-content-between">
                          <div className="mb-3 overflow-hidden me-3">
                            <p className="text-dark fw-medium mb-1">Email</p>
                            <p className="text-truncate">robe@example.com</p>
                          </div>
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            aria-label="Print"
                            data-bs-original-title="Reset Password"
                            className="btn btn-dark btn-icon btn-sm mb-3"
                          >
                            <i className="ti ti-lock-x" />
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="border rounded p-3 pb-0">
                    <div className="row">
                      <div className="col-lg-4 col-sm-6">
                        <div className="d-flex align-items-center mb-3">
                          <span className="avatar avatar-lg flex-shrink-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-13.jpg"
                              className="img-fluid rounded"
                              alt="img"
                            />
                          </span>
                          <div className="ms-2 overflow-hidden">
                            <h6 className="text-truncate">Jerald Vicinius</h6>
                            <p className="text-primary">Gaurdian (Father)</p>
                          </div>
                        </div>
                      </div>
                      <div className="col-lg-4 col-sm-6">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">Phone</p>
                          <p>+1 45545 46464</p>
                        </div>
                      </div>
                      <div className="col-lg-4 col-sm-6">
                        <div className="d-flex align-items-center justify-content-between">
                          <div className="mb-3 overflow-hidden me-3">
                            <p className="text-dark fw-medium mb-1">Email</p>
                            <p className="text-truncate">jera@example.com</p>
                          </div>
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            aria-label="Print"
                            data-bs-original-title="Reset Password"
                            className="btn btn-dark btn-icon btn-sm mb-3"
                          >
                            <i className="ti ti-lock-x" />
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Parents Information */}
            </div>
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
                        <p className="text-truncate fw-medium text-dark">
                          BirthCertificate.pdf
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
                        <p className="text-truncate fw-medium text-dark">
                          Transfer Certificate.pdf
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
                      <p className="text-dark fw-medium mb-1">
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
                      <p className="text-dark fw-medium mb-1">
                        Permanent Address
                      </p>
                      <p>3495 Red Hawk Road, Buffalo Lake, MN 55314</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Address */}
            {/* Previous School Details */}
            <div className="col-xxl-12">
              <div className="card">
                <div className="card-header">
                  <h5>Previous School Details</h5>
                </div>
                <div className="card-body pb-1">
                  <div className="row">
                    <div className="col-md-6">
                      <div className="mb-3">
                        <p className="text-dark fw-medium mb-1">
                          Previous School Name
                        </p>
                        <p>Oxford Matriculation, USA</p>
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <p className="text-dark fw-medium mb-1">
                          School Address
                        </p>
                        <p>1852 Barnes Avenue, Cincinnati, OH 45202</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Previous School Details */}
            {/* Bank Details */}
            <div className="col-xxl-6 d-flex">
              <div className="card flex-fill">
                <div className="card-header">
                  <h5>Bank Details</h5>
                </div>
                <div className="card-body pb-1">
                  <div className="row">
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="text-dark fw-medium mb-1">Bank Name</p>
                        <p>Bank of America</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="text-dark fw-medium mb-1">Branch</p>
                        <p>Cincinnati</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="text-dark fw-medium mb-1">IFSC</p>
                        <p>BOA83209832</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Bank Details */}
            {/* Medical History */}
            <div className="col-xxl-6 d-flex">
              <div className="card flex-fill">
                <div className="card-header">
                  <h5>Medical History</h5>
                </div>
                <div className="card-body pb-1">
                  <div className="row">
                    <div className="col-md-6">
                      <div className="mb-3">
                        <p className="text-dark fw-medium mb-1">
                          Known Allergies
                        </p>
                        <span className="badge bg-light text-dark">Rashes</span>
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <p className="text-dark fw-medium mb-1">Medications</p>
                        <p>-</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Medical History */}
            {/* Other Info */}
            <div className="col-xxl-12">
              <div className="card">
                <div className="card-header">
                  <h5>Other Info</h5>
                </div>
                <div className="card-body">
                  <p>
                    Depending on the specific needs of your organization or
                    system, additional information may be collected or tracked.
                    It's important to ensure that any data collected complies
                    with privacy regulations and policies to protect students'
                    sensitive information.
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
  <StudentModals />
</>

  )
}

export default StudentDetails