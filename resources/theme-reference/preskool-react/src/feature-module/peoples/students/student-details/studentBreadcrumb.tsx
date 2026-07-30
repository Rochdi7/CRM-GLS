
import { all_routes } from '../../../router/all_routes'
import { Link } from 'react-router-dom'

const StudentBreadcrumb = () => {
    const routes = all_routes
  return (
    <div className="col-md-12">
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Student Details</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to={routes.studentList}>Student</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Student Details
                  </li>
                </ol>
              </nav>
            </div>
            <div className="d-flex my-xl-auto right-content align-items-center  flex-wrap">
              <Link
                to="#"
                className="btn btn-light me-2 mb-2"
                data-bs-toggle="modal"
                data-bs-target="#login_detail"
              >
                <i className="ti ti-lock me-2" />
                Login Details
              </Link>
              <Link
                to={routes.editStudent}
                className="btn btn-primary d-flex align-items-center mb-2"
              >
                <i className="ti ti-edit-circle me-2" />
                Edit Student
              </Link>
            </div>
          </div>
        </div>
  )
}

export default StudentBreadcrumb