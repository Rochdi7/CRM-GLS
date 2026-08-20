
import { Link, useNavigate } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";


const ForgotPassword2 = () => {
  const routes = all_routes;
  const navigation = useNavigate();

  const navigationPath = () => {
    navigation(routes.resetPassword2);
  };
  
  return (
    <>
      <div className="container-fuild">
        <div className="login-wrapper w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
          <div className="row">
            <div className="col-lg-6">
              <div className="d-lg-flex align-items-center justify-content-center bg-light-300 d-lg-block d-none flex-wrap vh-100 overflowy-auto bg-01">
                <div>
                  <ImageWithBasePath
                    src="assets/img/authentication/authentication-07.svg"
                    alt="Img"
                  />
                </div>
              </div>
            </div>
            <div className="col-lg-6 col-md-12 col-sm-12">
              <div className="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                <div className="col-md-8 mx-auto p-4">
                  <form>
                    <div>
                      <div className=" mx-auto mb-5 text-center">
                        <ImageWithBasePath
                          src="assets/img/authentication/authentication-logo.svg"
                          className="img-fluid"
                          alt="Logo"
                        />
                      </div>
                      <div className="card">
                        <div className="card-body p-4">
                          <div className=" mb-4">
                            <h2 className="mb-2">Forgot Password?</h2>
                            <p className="mb-0">
                              If you forgot your password, well, then we’ll
                              email you instructions to reset your password.
                            </p>
                          </div>
                          <div className="mb-3 ">
                            <label className="form-label">Email Address</label>
                            <div className="input-icon mb-3 position-relative">
                              <span className="input-icon-addon">
                                <i className="ti ti-mail" />
                              </span>
                              <input
                                type="text"
                                
                                className="form-control"
                              />
                            </div>
                          </div>
                          <div className="mb-3">
                            <button onClick={navigationPath}
                              type="submit"
                              className="btn btn-primary w-100"
                            >
                              Sign In
                            </button>
                          </div>
                          <div className="text-center">
                            <h6 className="fw-normal text-dark mb-0">
                              Return to{" "}
                              <Link to={routes.login2} className="hover-a ">
                                {" "}
                                Login
                              </Link>
                            </h6>
                          </div>
                        </div>
                      </div>
                      <div className="mt-5 text-center">
                        <p className="mb-0 ">Copyright © 2024 - Preskool</p>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default ForgotPassword2;
