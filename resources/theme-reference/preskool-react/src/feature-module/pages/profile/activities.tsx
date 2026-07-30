
import { Link } from 'react-router-dom'
import ImageWithBasePath from '../../../core/common/imageWithBasePath'

const NotificationActivities = () => {
  return (
    <div className="page-wrapper">
  <div className="content">
    {/* Activities */}
    <div className="card">
      <div className="card-header pb-1">
        <div className="d-flex align-items-center justify-content-between flex-wrap">
          <div className="mb-3">
            <h4>Notifications</h4>
          </div>
          <div className="d-flex align-items-center mb-3">
            <Link to="#" className="btn btn-light me-2">
              <i className="ti ti-check me-2" />
              Mark all as read
            </Link>
            <Link to="#" className="btn btn-danger">
              <i className="ti ti-trash me-2" />
              Delete all
            </Link>
          </div>
        </div>
      </div>
      <div className="card-body pb-1">
        <div className="d-block">
          <div className="d-flex align-items-center justify-content-between flex-wrap shadow-sm p-3 pb-0 noti-hover border rounded mb-3">
            <div className="d-flex align-items-start flex-fill">
              <Link to="#" className="avatar avatar-lg flex-shrink-0 me-2 mb-2">
                <ImageWithBasePath
                  alt="Lesley Grauer"
                  src="assets/img/profiles/avatar-01.jpg"
                  className=" img-fluid"
                />
              </Link>
              <div className="mb-3">
                <p className="mb-0 text-dark  fw-medium">
                  Sylvia added appointment on 02:00 PM
                </p>
                <span>4 mins ago</span>
                <div className="d-flex align-items-center mt-1">
                  <Link
                    to="#"
                    className="btn btn-light btn-sm me-2"
                  >
                    Decline
                  </Link>
                  <Link
                    to="#"
                    className="btn btn-primary btn-sm"
                  >
                    Accept
                  </Link>
                </div>
              </div>
            </div>
            <div className="noti-delete mb-3">
              <Link
                to="#"
                className="btn btn-danger btn-sm text-white"
              >
                Delete
              </Link>
            </div>
          </div>
          <div className="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
            <div className="d-flex align-items-start flex-fill">
              <Link to="#" className="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                <ImageWithBasePath
                  alt="Lesley Grauer"
                  src="assets/img/profiles/avatar-02.jpg"
                  className=" img-fluid"
                />
              </Link>
              <div className="mb-3">
                <p className="mb-0 text-dark  fw-medium">
                  Shawn performance in Math is below the threshold.
                </p>
                <span>6 mins ago</span>
              </div>
            </div>
            <div className="noti-delete mb-3">
              <Link
                to="#"
                className="btn btn-danger btn-sm text-white"
              >
                Delete
              </Link>
            </div>
          </div>
          <div className="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
            <div className="d-flex align-items-start flex-fill">
              <Link to="#" className="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                <ImageWithBasePath
                  alt="Lesley Grauer"
                  src="assets/img/profiles/avatar-04.jpg"
                  className=" img-fluid"
                />
              </Link>
              <div className="mb-3">
                <p className="mb-0 text-dark  fw-medium">
                  A new teacher record for John
                </p>
                <span>09:45 am</span>
              </div>
            </div>
            <div className="noti-delete mb-3">
              <Link
                to="#"
                className="btn btn-danger btn-sm text-white"
              >
                Delete
              </Link>
            </div>
          </div>
          <div className="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
            <div className="d-flex align-items-start flex-fill">
              <Link to="#" className="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                <ImageWithBasePath
                  alt="Lesley Grauer"
                  src="assets/img/profiles/avatar-03.jpg"
                  className=" img-fluid"
                />
              </Link>
              <div className="mb-3">
                <p className="mb-0 text-dark fw-medium">
                  New student record George is created by Teressa
                </p>
                <span>2 hrs ago</span>
              </div>
            </div>
            <div className="noti-delete mb-3">
              <Link
                to="#"
                className="btn btn-danger btn-sm text-white"
              >
                Delete
              </Link>
            </div>
          </div>
          <div className="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
            <div className="d-flex align-items-start flex-fill">
              <Link to="#" className="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                <ImageWithBasePath
                  alt="Lesley Grauer"
                  src="assets/img/profiles/avatar-27.jpg"
                  className=" img-fluid"
                />
              </Link>
              <div className="mb-3">
                <p className="mb-0 text-dark  fw-medium">
                  New staff record is created
                </p>
                <span>10 mins ago</span>
              </div>
            </div>
            <div className="noti-delete mb-3">
              <Link
                to="#"
                className="btn btn-danger btn-sm text-white"
              >
                Delete
              </Link>
            </div>
          </div>
          <div className="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
            <div className="d-flex align-items-start flex-fill">
              <Link to="#" className="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                <ImageWithBasePath
                  alt="Lesley Grauer"
                  src="assets/img/profiles/avatar-10.jpg"
                  className=" img-fluid"
                />
              </Link>
              <div className="mb-3">
                <p className="mb-0 text-dark  fw-medium">
                  Exam time table added
                </p>
                <span>1 hr ago</span>
              </div>
            </div>
            <div className="noti-delete mb-3">
              <Link
                to="#"
                className="btn btn-danger btn-sm text-white"
              >
                Delete
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
    {/* /Activities */}
  </div>
</div>

  )
}

export default NotificationActivities