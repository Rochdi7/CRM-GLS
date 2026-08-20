
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";

const ResetPasswordSuccess = () => {
  const routes = all_routes;
  return (
    <>
      {" "}
      <div className="container-fluid">
        <div className="login-wrapper w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
          <div className="row">
            <div className="col-lg-6">
              <div className="login-background position-relative d-lg-flex align-items-center justify-content-center d-lg-block d-none flex-wrap vh-100 overflowy-auto">
                <div>
                  <ImageWithBasePath
                    src="assets/img/authentication/authentication-02.jpg"
                    alt="Img"
                  />
                </div>
                <div className="authen-overlay-item  w-100 p-4">
                  <h4 className="text-white mb-3">
                    What's New on Preskool !!!
                  </h4>
                  <div className="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                    <div>
                      <h6>Summer Vacation Holiday Homework</h6>
                      <p className="mb-0 text-truncate">
                        The school will remain closed from April 20th to June...
                      </p>
                    </div>
                    <Link to="#">
                      <i className="ti ti-chevrons-right" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                    <div>
                      <h6>New Academic Session Admission Start(2024-25)</h6>
                      <p className="mb-0 text-truncate">
                        An academic term is a portion of an academic year, the
                        time ....
                      </p>
                    </div>
                    <Link to="#">
                      <i className="ti ti-chevrons-right" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                    <div>
                      <h6>Date sheet Final Exam Nursery to Sr.Kg</h6>
                      <p className="mb-0 text-truncate">
                        Dear Parents, As the final examination for the session
                        2024-25 is ...
                      </p>
                    </div>
                    <Link to="#">
                      <i className="ti ti-chevrons-right" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                    <div>
                      <h6>Annual Day Function</h6>
                      <p className="mb-0 text-truncate">
                        Annual functions provide a platform for students to
                        showcase their...
                      </p>
                    </div>
                    <Link to="#">
                      <i className="ti ti-chevrons-right" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center flex-row mb-0 justify-content-between p-3 br-5 gap-3 card">
                    <div>
                      <h6>Summer Vacation Holiday Homework</h6>
                      <p className="mb-0 text-truncate">
                        The school will remain closed from April 20th to June
                        15th for summer...
                      </p>
                    </div>
                    <Link to="#">
                      <i className="ti ti-chevrons-right" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
            <div className="col-lg-6 col-md-12 col-sm-12">
              <div className="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                <div className="col-md-8 mx-auto p-4">
                  <div className=" mx-auto mb-5 text-center">
                    <ImageWithBasePath
                      src="assets/img/authentication/authentication-logo.svg"
                      className="img-fluid"
                      alt="Logo"
                    />
                  </div>
                  <div className="card">
                    <div className="card-body text-center">
                      <span className="avatar avatar-lg rounded-circle bg-success mb-3">
                        <i className="ti ti-check fs-24" />
                      </span>
                      <h3 className="mb-2">Success</h3>
                      <p>Your Password Reset Successfully</p>
                      <Link to={routes.login} className="btn btn-primary w-100">
                        Back to Login
                      </Link>
                    </div>
                  </div>
                  <div className="mt-5 text-center">
                    <p className="mb-0 ">Copyright © 2024 - Preskool</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default ResetPasswordSuccess;
