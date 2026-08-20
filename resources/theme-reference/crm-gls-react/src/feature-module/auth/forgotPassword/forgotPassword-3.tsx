
import { Link, useNavigate } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";

const ForgotPassword3 = () => {
  const routes = all_routes;
  const navigation = useNavigate();

  const navigationPath = () => {
    navigation(routes.resetPassword3);
  };
  
  return (
    <>
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-md-5">
            <form>
              <div className="d-flex flex-column justify-content-between vh-100">
                <div className=" mx-auto p-4 text-center">
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
                        If you forgot your password, well, then we’ll email you
                        instructions to reset your password.
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
                      <button type="submit" className="btn btn-primary w-100" onClick={navigationPath}>
                        Sign In
                      </button>
                    </div>
                    <div className="text-center">
                      <h6 className="fw-normal text-dark mb-0">
                        Return to{" "}
                        <Link to={routes.login3} className="hover-a ">
                          {" "}
                          Login
                        </Link>
                      </h6>
                    </div>
                  </div>
                </div>
                <div className="p-4 text-center">
                  <p className="mb-0 ">Copyright © 2024 - Preskool</p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </>
  );
};

export default ForgotPassword3;
