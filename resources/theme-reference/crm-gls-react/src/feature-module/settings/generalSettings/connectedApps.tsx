
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const ConnectedApps = () => {
  const routes = all_routes;
  return (
    <div>
      <div className="page-wrapper">
        <div className="content bg-white">
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
                  className="d-block rounded p-2"
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
                <Link
                  to={routes.connectedApps}
                  className="d-block rounded active p-2"
                >
                  Connected Apps
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="flex-fill border-start ps-3">
                <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3 pt-3">
                  <div className="mb-3">
                    <h5>Connected Apps</h5>
                    <p>Upload your photo &amp; personal details here</p>
                  </div>
                </div>
                <div className="d-block">
                  <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded bg-white">
                    <div className="">
                      <div className="d-flex align-items-center">
                        <span className="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center">
                          <ImageWithBasePath
                            src="assets/img/icons/connected-app-01.svg"
                            alt="Img"
                          />
                        </span>
                        <div className="mb-3">
                          <h6>Slack</h6>
                          <p>
                            Team communication platform with channels for group
                            discussions and direct messaging.
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#connect_app"
                      >
                        Connect
                      </Link>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded bg-white">
                    <div className="">
                      <div className="d-flex align-items-center">
                        <span className="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center">
                          <ImageWithBasePath
                            src="assets/img/icons/connected-app-02.svg"
                            alt="Img"
                          />
                        </span>
                        <div className="mb-3">
                          <h6>Google Calendar</h6>
                          <p>
                            Google Calendar is a web-based scheduling tool that
                            allows users to create, manage, and share events.
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#connect_app_two"
                      >
                        Connect
                      </Link>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded bg-white">
                    <div className="">
                      <div className="d-flex align-items-center">
                        <span className="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center">
                          <ImageWithBasePath
                            src="assets/img/icons/connected-app-03.svg"
                            alt="Img"
                          />
                        </span>
                        <div className="mb-3">
                          <h6>Gmail</h6>
                          <p>
                            Gmail is a free email service by Google that offers
                            robust spam protection &amp; 15GB of storage.
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#connect_app_three"
                      >
                        Connect
                      </Link>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border mb-0 p-3 pb-0 rounded bg-white">
                    <div className="">
                      <div className="d-flex align-items-center">
                        <span className="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center">
                          <ImageWithBasePath
                            src="assets/img/icons/connected-app-04.svg"
                            alt="Img"
                          />
                        </span>
                        <div className="mb-3">
                          <h6>Github</h6>
                          <p>
                            GitHub is a web-based platform for version control
                            and collaboration, allowing developers to host and
                            review code
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#connect_app_four"
                      >
                        Connect
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <>
        {/* Connect App */}
        <div className="modal fade" id="connect_app">
          <div className="modal-dialog modal-dialog-centered  connected-app-modal">
            <div className="modal-content">
              <div className="modal-body">
                <div className="d-flex align-items-center justify-content-center">
                  <span className="modal-logo-small me-3">
                    <ImageWithBasePath
                      src="assets/img/icons/modal-small-logo.svg"
                      alt="Img"
                    />
                  </span>
                  <div className="connected-design">
                    <span />
                    <span />
                  </div>
                  <span>
                    <ImageWithBasePath
                      src="assets/img/icons/connected-app-01.svg"
                      alt="Img"
                    />
                  </span>
                </div>
                <div className="border-bottom pb-3 mb-3">
                  <h5 className="mb-2">Connect with Slack</h5>
                  <p>Confirm requirements to connect with Slack</p>
                </div>
                <div>
                  <h5 className="mb-2">Slack requires :</h5>
                  <ul>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      New Slack Workspace for communication
                    </li>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Slack API to interact with Slack
                    </li>
                    <li className="d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Implement OAuth 2.0 for authorization
                    </li>
                  </ul>
                </div>
              </div>
              <div className="modal-footer">
                <form className="w-100">
                  <div className="d-flex align-items-center mb-3">
                    <input type="text" className="form-control flex-fill" />
                    <Link
                      to="#"
                      className="border rounded d-flex align-items-center justify-content-center p-2 ms-3"
                    >
                      <i className="ti ti-copy" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center">
                    <button
                      type="button"
                      className="btn btn-light me-3 flex-fill"
                    >
                      Cancel
                    </button>
                    <button type="submit" className="btn btn-primary flex-fill">
                      Connect
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        {/* /Connect App */}
        {/* Connect App */}
        <div className="modal fade" id="connect_app_two">
          <div className="modal-dialog modal-dialog-centered  connected-app-modal">
            <div className="modal-content">
              <div className="modal-body">
                <div className="d-flex align-items-center justify-content-center">
                  <span className="modal-logo-small me-3">
                    <ImageWithBasePath
                      src="assets/img/icons/modal-small-logo.svg"
                      alt="Img"
                    />
                  </span>
                  <div className="connected-design">
                    <span />
                    <span />
                  </div>
                  <span>
                    <ImageWithBasePath
                      src="assets/img/icons/connected-app-02.svg"
                      alt="Img"
                    />
                  </span>
                </div>
                <div className="border-bottom pb-3 mb-3">
                  <h5 className="mb-2">Connect with Google Calendar</h5>
                  <p>Confirm requirements to connect with Google Calendar</p>
                </div>
                <div>
                  <h5 className="mb-2">Slack requires :</h5>
                  <ul>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-16" />
                      </span>
                      New Slack Workspace for communication
                    </li>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Slack API to interact with Slack
                    </li>
                    <li className="d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Implement OAuth 2.0 for authorization
                    </li>
                  </ul>
                </div>
              </div>
              <div className="modal-footer">
                <form className="w-100">
                  <div className="d-flex align-items-center mb-3">
                    <input type="text" className="form-control flex-fill" />
                    <Link
                      to="#"
                      className="border rounded d-flex align-items-center justify-content-center p-2 ms-3"
                    >
                      <i className="ti ti-copy" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center">
                    <button
                      type="button"
                      className="btn btn-light me-3 flex-fill"
                    >
                      Cancel
                    </button>
                    <button type="submit" className="btn btn-primary flex-fill">
                      Connect
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        {/* /Connect App */}
        {/* Connect App */}
        <div className="modal fade" id="connect_app_three">
          <div className="modal-dialog modal-dialog-centered  connected-app-modal">
            <div className="modal-content">
              <div className="modal-body">
                <div className="d-flex align-items-center justify-content-center">
                  <span className="modal-logo-small me-3">
                    <ImageWithBasePath
                      src="assets/img/icons/modal-small-logo.svg"
                      alt="Img"
                    />
                  </span>
                  <div className="connected-design">
                    <span />
                    <span />
                  </div>
                  <span>
                    <ImageWithBasePath
                      src="assets/img/icons/connected-app-03.svg"
                      alt="Img"
                    />
                  </span>
                </div>
                <div className="border-bottom pb-3 mb-3">
                  <h5 className="mb-2">Connect with Email</h5>
                  <p>Confirm requirements to connect with email</p>
                </div>
                <div>
                  <h5 className="mb-2">Slack requires :</h5>
                  <ul>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-16" />
                      </span>
                      New Slack Workspace for communication
                    </li>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Slack API to interact with Slack
                    </li>
                    <li className="d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Implement OAuth 2.0 for authorization
                    </li>
                  </ul>
                </div>
              </div>
              <div className="modal-footer">
                <form className="w-100">
                  <div className="d-flex align-items-center mb-3">
                    <input type="text" className="form-control flex-fill" />
                    <Link
                      to="#"
                      className="border rounded d-flex align-items-center justify-content-center p-2 ms-3"
                    >
                      <i className="ti ti-copy" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center">
                    <button
                      type="button"
                      className="btn btn-light me-3 flex-fill"
                    >
                      Cancel
                    </button>
                    <button type="submit" className="btn btn-primary flex-fill">
                      Connect
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        {/* /Connect App */}
        {/* Connect App */}
        <div className="modal fade" id="connect_app_four">
          <div className="modal-dialog modal-dialog-centered  connected-app-modal">
            <div className="modal-content">
              <div className="modal-body">
                <div className="d-flex align-items-center justify-content-center">
                  <span className="modal-logo-small me-3">
                    <ImageWithBasePath
                      src="assets/img/icons/modal-small-logo.svg"
                      alt="Img"
                    />
                  </span>
                  <div className="connected-design">
                    <span />
                    <span />
                  </div>
                  <span>
                    <ImageWithBasePath
                      src="assets/img/icons/connected-app-04.svg"
                      alt="Img"
                    />
                  </span>
                </div>
                <div className="border-bottom pb-3 mb-3">
                  <h5 className="mb-2">Connect with Github</h5>
                  <p>Confirm requirements to connect with Github</p>
                </div>
                <div>
                  <h5 className="mb-2">Slack requires :</h5>
                  <ul>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-16" />
                      </span>
                      New Slack Workspace for communication
                    </li>
                    <li className="mb-2 d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Slack API to interact with Slack
                    </li>
                    <li className="d-flex align-items-center">
                      <span className="avatar avatar-xs rounded-circle bg-success border-0 me-2">
                        <i className="ti ti-check fs-14" />
                      </span>
                      Implement OAuth 2.0 for authorization
                    </li>
                  </ul>
                </div>
              </div>
              <div className="modal-footer">
                <form className="w-100">
                  <div className="d-flex align-items-center mb-3">
                    <input type="text" className="form-control flex-fill" />
                    <Link
                      to="#"
                      className="border rounded d-flex align-items-center justify-content-center p-2 ms-3"
                    >
                      <i className="ti ti-copy" />
                    </Link>
                  </div>
                  <div className="d-flex align-items-center">
                    <button
                      type="button"
                      className="btn btn-light me-3 flex-fill"
                    >
                      Cancel
                    </button>
                    <button type="submit" className="btn btn-primary flex-fill">
                      Connect
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        {/* /Connect App */}
      </>
    </div>
  );
};

export default ConnectedApps;
