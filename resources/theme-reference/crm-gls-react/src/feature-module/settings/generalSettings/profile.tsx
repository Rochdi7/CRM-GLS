
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const Profilesettings = () => {
  const routes = all_routes;
  return (
    <div>
      <div className="page-wrapper">
        <div className="content">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">General Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    General Settings
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
          <div className="row">
            <div className="col-xxl-2 col-xl-3">
              <div className="pt-3 d-flex flex-column list-group mb-4">
                <Link
                  to={routes.profilesettings}
                  className="d-block rounded p-2 active"
                >
                  Profile Settings
                </Link>
                <Link
                  to={routes.securitysettings}
                  className="d-block rounded p-2"
                >
                  Security Settings
                </Link>
                <Link
                  to={routes.notificationssettings}
                  className="d-block rounded p-2"
                >
                  Notifications
                </Link>
                <Link to={routes.connectedApps} className="d-block rounded p-2">
                  Connected Apps
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="flex-fill border-start ps-3">
                <form action="profile-settings">
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Profile Settings</h5>
                      <p>Upload your photo &amp; personal details here</p>
                    </div>
                    <div className="mb-3">
                      <button className="btn btn-light me-2" type="button">
                        Cancel
                      </button>
                      <button className="btn btn-primary" type="submit">
                        Save
                      </button>
                    </div>
                  </div>
                  <div className="d-md-flex d-block">
                    <div className="flex-fill">
                      <div className="card">
                        <div className="card-header p-3">
                          <h5>Personal Information</h5>
                        </div>
                        <div className="card-body p-3 pb-0">
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
                        <div className="card-header p-3">
                          <h5>Address Information</h5>
                        </div>
                        <div className="card-body p-3 pb-0">
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
                    </div>
                    <div className="settings-right-sidebar ms-md-3">
                      <div className="card">
                        <div className="card-header p-3">
                          <h5>Personal Information</h5>
                        </div>
                        <div className="card-body p-3 pb-0">
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
                          <div className="profile-uploader profile-uploader-two">
                            <span className="upload-icon">
                              <i className="ti ti-upload" />
                            </span>
                            <div className="drag-upload-btn mb-0 border-0 pb-0 bg-transparent">
                              <p className="upload-btn">
                                <span>Click to Upload</span> or drag and drop
                              </p>
                              <h6>JPG or PNG</h6>
                              <h6>(Max 450 x 450 px)</h6>
                            </div>
                            <input
                              type="file"
                              className="form-control"
                              id="image_sign"
                              multiple
                            />
                            <div id="frames" />
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
      </div>
    </div>
  );
};

export default Profilesettings;
