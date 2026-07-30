import  { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
type PasswordField = "oldPassword" | "newPassword" | "confirmPassword";

const ResetPassword3 = () => {
  const routes = all_routes;
  const navigation = useNavigate();

  const navigationPath = () => {
    navigation(routes.resetPasswordSuccess3);
  };
  const [passwordVisibility, setPasswordVisibility] = useState({
    oldPassword: false,
    newPassword: false,
    confirmPassword: false,
  });

  const togglePasswordVisibility = (field: PasswordField) => {
    setPasswordVisibility((prevState) => ({
      ...prevState,
      [field]: !prevState[field],
    }));
  };
  return (
    <>
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-md-5 mx-auto">
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
                  <div className="card-body p-4 pb-3">
                    <div className=" mb-4">
                      <h2 className="mb-2">Reset Password?</h2>
                      <p className="mb-0">
                        Enter New Password &amp; Confirm Password to get inside
                      </p>
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Old Password</label>
                      <div className="pass-group">
                        <input
                          type={
                            passwordVisibility.oldPassword ? "text" : "password"
                          }
                          className="pass-input form-control"
                        />
                        <span
                          className={`ti toggle-passwords ${
                            passwordVisibility.oldPassword
                              ? "ti-eye"
                              : "ti-eye-off"
                          }`}
                          onClick={() =>
                            togglePasswordVisibility("oldPassword")
                          }
                        ></span>
                      </div>
                    </div>
                    <div className="mb-3">
                      <label className="form-label">New Password</label>
                      <div className="pass-group">
                        <input
                          type={
                            passwordVisibility.newPassword ? "text" : "password"
                          }
                          className="pass-input form-control"
                        />
                        <span
                          className={`ti toggle-passwords ${
                            passwordVisibility.newPassword
                              ? "ti-eye"
                              : "ti-eye-off"
                          }`}
                          onClick={() =>
                            togglePasswordVisibility("newPassword")
                          }
                        ></span>
                      </div>
                    </div>
                    <div className="mb-3">
                      <label className="form-label ">
                        New Confirm Password
                      </label>
                      <div className="pass-group">
                        <input
                          type={
                            passwordVisibility.confirmPassword
                              ? "text"
                              : "password"
                          }
                          className="pass-input form-control"
                        />
                        <span
                          className={`ti toggle-passwords ${
                            passwordVisibility.confirmPassword
                              ? "ti-eye"
                              : "ti-eye-off"
                          }`}
                          onClick={() =>
                            togglePasswordVisibility("confirmPassword")
                          }
                        ></span>
                      </div>
                    </div>
                    <div className="mb-3">
                      <button
                        type="submit"
                        className="btn btn-primary w-100"
                        onClick={navigationPath}
                      >
                        Change Password
                      </button>
                    </div>
                    <div className="text-center">
                      <h6 className="fw-normal text-dark mb-0">
                        Return to
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

export default ResetPassword3;
