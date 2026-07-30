import  { useState } from "react";
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";
type PasswordField =
  | "oldPassword"
  | "newPassword"
  | "confirmPassword"
  | "currentPassword";

const Profile = () => {
  const route = all_routes;
  const [passwordVisibility, setPasswordVisibility] = useState({
    oldPassword: false,
    newPassword: false,
    confirmPassword: false,
    currentPassword: false,
  });

  const togglePasswordVisibility = (field: PasswordField) => {
    setPasswordVisibility((prevState) => ({
      ...prevState,
      [field]: !prevState[field],
    }));
  };
  return (
    <div>
      <>
        {/* Page Wrapper */}
        <div className="page-wrapper">
          <div className="content">
            <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
              <div className="my-auto mb-2">
                <h3 className="page-title mb-1">Profile</h3>
                <nav>
                  <ol className="breadcrumb mb-0">
                    <li className="breadcrumb-item">
                      <Link to={route.adminDashboard}>Dashboard</Link>
                    </li>
                    <li className="breadcrumb-item">
                      <Link to="#">Settings</Link>
                    </li>
                    <li className="breadcrumb-item active" aria-current="page">
                      Profile
                    </li>
                  </ol>
                </nav>
              </div>
              <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div className="pe-1 mb-2">
                  <OverlayTrigger
                    placement="top"
                    overlay={<Tooltip id="tooltip-top">Refresh</Tooltip>}
                  >
                    <Link
                      to="#"
                      className="btn btn-outline-light bg-white btn-icon me-1"
                    >
                      <i className="ti ti-refresh" />
                    </Link>
                  </OverlayTrigger>
                </div>
              </div>
            </div>
            <div className="d-md-flex d-block mt-3">
              <div className="settings-right-sidebar me-md-3 border-0">
                <div className="card">
                  <div className="card-header">
                    <h5>Personal Information</h5>
                  </div>
                  <div className="card-body ">
                    <div className="settings-profile-upload">
                      <span className="profile-pic">
                        <ImageWithBasePath
                          src="assets/img/profiles/avatar-27.jpg"
                          alt="Profile"
                        />
                      </span>
                      <div className="title-upload">
                        <h5>Edit Your Photo</h5>
                        <Link to="#" className="me-2">
                          Delete{" "}
                        </Link>
                        <Link to="#" className="text-primary">
                          Update
                        </Link>
                      </div>
                    </div>
                    <div className="profile-uploader profile-uploader-two mb-0">
                      <span className="upload-icon">
                        <i className="ti ti-upload" />
                      </span>
                      <div className="drag-upload-btn bg-transparent me-0 border-0">
                        <p className="upload-btn">
                          <span>Click to Upload</span> or drag and drop
                        </p>
                        <h6>JPG or PNG</h6>
                        <h6>(Max 450 x 450 px)</h6>
                      </div>
                      <input
                        type="file"
                        className="form-control"
                        multiple
                        id="image_sign"
                      />
                      <div id="frames" />
                    </div>
                  </div>
                </div>
              </div>
              <div className="flex-fill ps-0 border-0">
                <form>
                  <div className="d-md-flex">
                    <div className="flex-fill">
                      <div className="card">
                        <div className="card-header d-flex justify-content-between align-items-center">
                          <h5>Personal Information</h5>
                          <Link
                            to="#"
                            className="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#edit_personal_information"
                          >
                            <i className="ti ti-edit me-2" />
                            Edit
                          </Link>
                        </div>
                        <div className="card-body pb-0">
                          <div className="d-block d-xl-flex">
                            <div className="mb-3 flex-fill me-xl-3 me-0">
                              <label className="form-label">First Name</label>
                              <input
                                type="text"
                                className="form-control"
                                placeholder="Enter First Name"
                              />
                            </div>
                            <div className="mb-3 flex-fill">
                              <label className="form-label">Last Name</label>
                              <input
                                type="text"
                                className="form-control"
                                placeholder="Enter Last Name"
                              />
                            </div>
                          </div>
                          <div className="mb-3">
                            <label className="form-label">Email Address</label>
                            <input
                              type="email"
                              className="form-control"
                              placeholder="Enter Email"
                            />
                          </div>
                          <div className="d-block d-xl-flex">
                            <div className="mb-3 flex-fill me-xl-3 me-0">
                              <label className="form-label">User Name</label>
                              <input
                                type="email"
                                className="form-control"
                                placeholder="Enter User Name"
                              />
                            </div>
                            <div className="mb-3 flex-fill">
                              <label className="form-label">Phone Number</label>
                              <input
                                type="email"
                                className="form-control"
                                placeholder="Enter Phone Number"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="card">
                        <div className="card-header d-flex justify-content-between align-items-center">
                          <h5>Address Information</h5>
                          <Link
                            to="#"
                            className="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#edit_address_information"
                          >
                            <i className="ti ti-edit me-2" />
                            Edit
                          </Link>
                        </div>
                        <div className="card-body pb-0">
                          <div className="mb-3">
                            <label className="form-label">Address</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Address"
                            />
                          </div>
                          <div className="d-block d-xl-flex">
                            <div className="mb-3 flex-fill me-xl-3 me-0">
                              <label className="form-label">Country</label>
                              <input
                                type="text"
                                className="form-control"
                                placeholder="Enter Country"
                              />
                            </div>
                            <div className="mb-3 flex-fill">
                              <label className="form-label">
                                State / Province
                              </label>
                              <input
                                type="email"
                                className="form-control"
                                placeholder="Enter State"
                              />
                            </div>
                          </div>
                          <div className="d-block d-xl-flex">
                            <div className="mb-3 flex-fill me-xl-3 me-0">
                              <label className="form-label">City</label>
                              <input
                                type="email"
                                className="form-control"
                                placeholder="City"
                              />
                            </div>
                            <div className="mb-3 flex-fill">
                              <label className="form-label">Postal Code</label>
                              <input
                                type="email"
                                className="form-control"
                                placeholder="Enter Postal Code"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="card">
                        <div className="card-header d-flex justify-content-between align-items-center">
                          <h5>Password</h5>
                          <Link
                            to="#"
                            className="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#change_password"
                          >
                            Change
                          </Link>
                        </div>
                        <div className="card-body pb-0">
                          <div className="mb-3 mb-3">
                            <label className="form-label">
                              Current Password
                            </label>
                            <div className="pass-group d-flex">
                              <input
                                type={
                                  passwordVisibility.currentPassword
                                    ? "text"
                                    : "password"
                                }
                                className="pass-input form-control"
                              />
                              <span
                                className={`ti toggle-passwords ${
                                  passwordVisibility.currentPassword
                                    ? "ti-eye"
                                    : "ti-eye-off"
                                }`}
                                onClick={() =>
                                  togglePasswordVisibility("currentPassword")
                                }
                              ></span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        {/* /Page Wrapper */}
        {/* Edit Profile */}
        <div className="modal fade" id="edit_personal_information">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Personal Information</h4>
                <button
                  type="button"
                  className="btn-close custom-btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x" />
                </button>
              </div>
              <form>
                <div className="modal-body">
                  <div className="row">
                    <div className="col-md-12">
                      <div className="mb-3">
                        <label className="form-label">First Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter First Name"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Last Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Last Name"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">User Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter User Name"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Email</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Email"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Phone Number</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Phone Number"
                        />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Bio</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Bio"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    className="btn btn-primary"
                    data-bs-dismiss="modal"
                  >
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Edit Profile */}
        {/* Edit Profile */}
        <div className="modal fade" id="edit_address_information">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Address Information</h4>
                <button
                  type="button"
                  className="btn-close custom-btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x" />
                </button>
              </div>
              <form>
                <div className="modal-body">
                  <div className="row">
                    <div className="col-md-12">
                      <div className="mb-3">
                        <label className="form-label">Address</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Address"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Country</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Country"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">State/Province</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter State/Province"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">City</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter City"
                        />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Postal Code</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Postal Code"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    className="btn btn-primary"
                    data-bs-dismiss="modal"
                  >
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Edit Profile */}
        {/* Change Password */}
        <div className="modal fade" id="change_password">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Change Password</h4>
                <button
                  type="button"
                  className="btn-close custom-btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x" />
                </button>
              </div>
              <form>
                <div className="modal-body">
                  <div className="row">
                    <div className="col-md-12">
                      <div className="mb-3">
                        <label className="form-label">Current Password</label>
                        <div className="pass-group d-flex">
                          <input
                            type={
                              passwordVisibility.oldPassword
                                ? "text"
                                : "password"
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
                        <div className="pass-group d-flex">
                          <input
                            type={
                              passwordVisibility.newPassword
                                ? "text"
                                : "password"
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
                      <div className="mb-0">
                        <label className="form-label">Confirm Password</label>
                        <div className="pass-group d-flex">
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
                    </div>
                  </div>
                </div>
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    className="btn btn-primary"
                    data-bs-dismiss="modal"
                  >
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Change Password */}
      </>
    </div>
  );
};

export default Profile;
