
import { useNavigate } from "react-router";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";

const TwoStepVerification3 = () => {
  const routes = all_routes;
  const navigation = useNavigate();

  const navigationPath = () => {
    navigation(routes.adminDashboard);
  };
  return (
    <>
      <div className="d-flex align-items-center justify-content-center vh-100">
        <div className="row justify-content-center">
          <div className="col-md-5 p-4">
            <form
              method="get"
              className="digit-group login-form-control"
              data-group-name="digits"
              data-autosubmit="false"
              autoComplete="off"
            >
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
                      <h2 className="mb-2">Login with your Email Address</h2>
                      <p className="mb-0">
                        We sent a verification code to your email. Enter the
                        code from the email in the field below
                      </p>
                    </div>
                    <div className="text-center">
                      <div className="d-flex align-items-center mb-3">
                        <input
                          type="text"
                          className="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold me-3"
                          id="digit-1"
                          name="digit-1"
                          data-next="digit-2"
                          maxLength={1}
                        />
                        <input
                          type="text"
                          className="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold me-3"
                          id="digit-2"
                          name="digit-2"
                          data-next="digit-3"
                          data-previous="digit-1"
                          maxLength={1}
                        />
                        <input
                          type="text"
                          className="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold me-3"
                          id="digit-3"
                          name="digit-3"
                          data-next="digit-4"
                          data-previous="digit-2"
                          maxLength={1}
                        />
                        <input
                          type="text"
                          className="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold"
                          id="digit-4"
                          name="digit-4"
                          data-next="digit-5"
                          data-previous="digit-3"
                          maxLength={1}
                        />
                      </div>
                      <div>
                        <div className="badge bg-soft-danger mb-3">
                          <p>Otp will expire in 09:59</p>
                        </div>
                        <div className="form-wrap mb-0">
                          <button onClick={navigationPath}
                            type="submit"
                            className="btn btn-primary w-100"
                          >
                            Verify My Account
                          </button>
                        </div>
                      </div>
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

export default TwoStepVerification3;
