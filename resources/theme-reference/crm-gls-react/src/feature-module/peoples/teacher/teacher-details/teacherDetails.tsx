
import TeacherModal from '../teacherModal'
import { Link } from 'react-router-dom'
import { all_routes } from '../../../router/all_routes'
import TeacherSidebar from './teacherSidebar'
import TeacherBreadcrumb from './teacherBreadcrumb'

const TeacherDetails = () => {
    const routes = all_routes
  return (
    <>
  {/* Page Wrapper */}
  <div className="page-wrapper">
    <div className="content">
      <div className="row">
        {/* Page Header */}
        <TeacherBreadcrumb />
        {/* /Page Header */}
        {/* Student Information */}
        <TeacherSidebar />
        {/* /Student Information */}
        <div className="col-xxl-9 col-xl-8">
          <div className="row">
            <div className="col-md-12">
              {/* List */}
              <ul className="nav nav-tabs nav-tabs-bottom mb-4">
                <li>
                  <Link to={routes.teacherDetails} className="nav-link active">
                    <i className="ti ti-school me-2" />
                    Teacher Details
                  </Link>
                </li>
                <li>
                  <Link to={routes.teachersRoutine} className="nav-link">
                    <i className="ti ti-table-options me-2" />
                    Routine
                  </Link>
                </li>
                <li>
                  <Link to={routes.teacherLeaves} className="nav-link">
                    <i className="ti ti-calendar-due me-2" />
                    Leave &amp; Attendance
                  </Link>
                </li>
                <li>
                  <Link to={routes.teacherSalary} className="nav-link">
                    <i className="ti ti-report-money me-2" />
                    Salary
                  </Link>
                </li>
                <li>
                  <Link to={routes.teacherLibrary} className="nav-link">
                    <i className="ti ti-bookmark-edit me-2" />
                    Library
                  </Link>
                </li>
              </ul>
              {/* /List */}
              {/* Parents Information */}
              <div className="card">
                <div className="card-header">
                  <h5>Profile Details</h5>
                </div>
                <div className="card-body">
                  <div className="border rounded p-3 pb-0">
                    <div className="row">
                      <div className="col-sm-6 col-lg-4">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">
                            Father’s Name
                          </p>
                          <p>Francis Saviour</p>
                        </div>
                      </div>
                      <div className="col-sm-6 col-lg-4">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">
                            Mother Name
                          </p>
                          <p>Stella Bruce</p>
                        </div>
                      </div>
                      <div className="col-sm-6 col-lg-4">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">DOB</p>
                          <p>25 Jan 1992</p>
                        </div>
                      </div>
                      <div className="col-sm-6 col-lg-4">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">
                            Martial Status
                          </p>
                          <p>Single</p>
                        </div>
                      </div>
                      <div className="col-sm-6 col-lg-4">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">
                            Qualification
                          </p>
                          <p>MBA</p>
                        </div>
                      </div>
                      <div className="col-sm-6 col-lg-4">
                        <div className="mb-3">
                          <p className="text-dark fw-medium mb-1">Experience</p>
                          <p>2 Years</p>
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
                        <p className="text-truncate fw-medium text-dark">
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
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">
                          Previous School Name
                        </p>
                        <p>Oxford Matriculation, USA</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">
                          School Address
                        </p>
                        <p>1852 Barnes Avenue, Cincinnati, OH 45202</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Phone Number</p>
                        <p>+1 35676 45556</p>
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
                        <p className="mb-1 text-dark fw-medium">Bank Name</p>
                        <p>Bank of America</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Branch</p>
                        <p>Cincinnati</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">IFSC</p>
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
                  <h5>Work Details</h5>
                </div>
                <div className="card-body pb-1">
                  <div className="row">
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">
                          Contract Type
                        </p>
                        <p>Permanent</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Shift</p>
                        <p>Morning</p>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">
                          Work Location
                        </p>
                        <p>2nd Floor</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Medical History */}
            {/* Medical History */}
            <div className="col-xxl-12 d-flex">
              <div className="card flex-fill">
                <div className="card-header">
                  <h5>Social Media</h5>
                </div>
                <div className="card-body pb-1">
                  <div className="row row-cols-xxl-5 row-cols-xl-3">
                    <div className="col">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Facebook</p>
                        <p>www.facebook.com</p>
                      </div>
                    </div>
                    <div className="col">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Twitter</p>
                        <p>www.twitter.com</p>
                      </div>
                    </div>
                    <div className="col">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Linkedin</p>
                        <p>www.linkedin.com</p>
                      </div>
                    </div>
                    <div className="col">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Youtube</p>
                        <p>www.youtube.com</p>
                      </div>
                    </div>
                    <div className="col">
                      <div className="mb-3">
                        <p className="mb-1 text-dark fw-medium">Instagram</p>
                        <p>www.instagram.com</p>
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
  <TeacherModal />
</>

  )
}

export default TeacherDetails