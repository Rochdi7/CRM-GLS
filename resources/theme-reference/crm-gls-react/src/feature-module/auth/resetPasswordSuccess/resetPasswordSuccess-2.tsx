
import { Link } from "react-router-dom";
import { all_routes } from '../../router/all_routes';
import ImageWithBasePath from '../../../core/common/imageWithBasePath';

const ResetPasswordSuccess2 = () => {
  const routes = all_routes;
  return (
    <><div className="container-fuild">
    <div className="login-wrapper w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
      <div className="row">
        <div className="col-lg-6">
          <div className="d-lg-flex align-items-center justify-content-center bg-light-300 d-lg-block d-none flex-wrap vh-100 overflowy-auto bg-01">
            <div>
              <ImageWithBasePath src="assets/img/authentication/authentication-09.svg" alt="Img" />
            </div>
          </div>
        </div>
        <div className="col-lg-6 col-md-12 col-sm-12">
          <div className="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
            <div className="col-md-8 mx-auto p-4">
              <div className=" mx-auto mb-5 text-center">
                <ImageWithBasePath src="assets/img/authentication/authentication-logo.svg" className="img-fluid" alt="Logo" />
              </div>
              <div className="card">
                <div className="card-body text-center">
                  <span className="avatar avatar-lg rounded-circle bg-success mb-3"><i className="ti ti-check fs-24" /></span>
                  <h3 className="mb-2">Success</h3>
                  <p>Your Password Reset Successfully</p>
                  <Link to={routes.login2} className="btn btn-primary w-100">Back to Login</Link>
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
  </div></>
  )
}

export default ResetPasswordSuccess2