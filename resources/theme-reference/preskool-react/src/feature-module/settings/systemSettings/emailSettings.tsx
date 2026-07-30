
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const routes = all_routes;
const EmailSettings = () => {
  return (
    <div>
      <div className="page-wrapper">
        <div className="content bg-white">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">System Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    System Settings
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
                  to={routes.emailSettings}
                  className="d-block rounded active p-2"
                >
                  Email Settings
                </Link>
                <Link
                  to={routes.emailTemplates}
                  className="d-block rounded p-2"
                >
                  Email Templates
                </Link>
                <Link to={routes.smsSettings} className="d-block rounded p-2">
                  SMS Settings
                </Link>
                <Link to={routes.optSettings} className="d-block rounded p-2">
                  OTP
                </Link>
                <Link to={routes.gdprCookies} className="d-block rounded p-2">
                  GDPR Cookies
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="flex-fill border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Email Settings</h5>
                      <p>Email Settings Configuration</p>
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
                  <div className="d-md-flex">
                    <div className="flex-fill">
                      <div className="row">
                        <div className="col-xxl-4 col-xl-6">
                          <div className="card">
                            <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                              <div className="d-flex align-items-center">
                                <span className="avatar avatar-lg p-2 rounded bg-gray flex-shrink-0 me-2">
                                  <ImageWithBasePath
                                    src="assets/img/icons/php-icon.svg"
                                    alt="Img"
                                  />
                                </span>
                                <h6>PHP Mailer</h6>
                              </div>
                              <span className="badge bg-transparent-success text-success">
                                Connected
                              </span>
                            </div>
                            <div className="card-body pt-0">
                              <p>
                                Used to send emails safely and easily via PHP
                                code from a web server.
                              </p>
                            </div>
                            <div className="card-footer d-flex justify-content-between align-items-center">
                              <div>
                                <Link
                                  to="#"
                                  className="btn btn-outline-light"
                                  data-bs-toggle="modal"
                                  data-bs-target="#connect_php"
                                >
                                  <i className="ti ti-tool me-2" />
                                  View Integration
                                </Link>
                              </div>
                              <div className="status-toggle modal-status">
                                <input
                                  type="checkbox"
                                  id="user1"
                                  className="check"
                                />
                                <label htmlFor="user1" className="checktoggle">
                                  {" "}
                                </label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="col-xxl-4 col-xl-6">
                          <div className="card">
                            <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                              <div className="d-flex align-items-center">
                                <span className="avatar avatar-lg p-2 rounded bg-gray flex-shrink-0 me-2">
                                  <ImageWithBasePath
                                    src="assets/img/icons/smtp-icon.svg"
                                    alt="Img"
                                  />
                                </span>
                                <h6>SMTP</h6>
                              </div>
                              <span className="badge bg-transparent-dark text-dark">
                                Not Connected
                              </span>
                            </div>
                            <div className="card-body pt-0">
                              <p>
                                SMTP is used to send, relay or forward messages
                                from a mail client.
                              </p>
                            </div>
                            <div className="card-footer d-flex justify-content-between align-items-center">
                              <div>
                                <Link
                                  to="#"
                                  className="btn btn-outline-light"
                                  data-bs-toggle="modal"
                                  data-bs-target="#connect_smtp"
                                >
                                  <i className="ti ti-tool me-2" />
                                  View Integration
                                </Link>
                              </div>
                              <div className="status-toggle modal-status">
                                <input
                                  type="checkbox"
                                  id="user2"
                                  className="check"
                                />
                                <label htmlFor="user2" className="checktoggle">
                                  {" "}
                                </label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="col-xxl-4 col-xl-6">
                          <div className="card">
                            <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                              <div className="d-flex align-items-center">
                                <span className="avatar avatar-lg p-2 rounded bg-gray flex-shrink-0 me-2">
                                  <ImageWithBasePath
                                    src="assets/img/icons/google-icon-02.svg"
                                    alt="Img"
                                  />
                                </span>
                                <h6>Google</h6>
                              </div>
                              <span className="badge bg-transparent-dark text-dark">
                                Not Connected
                              </span>
                            </div>
                            <div className="card-body pt-0">
                              <p>
                                Cloud-based email marketing tool that assists
                                marketers and developers .
                              </p>
                            </div>
                            <div className="card-footer d-flex justify-content-between align-items-center">
                              <div>
                                <Link
                                  to="#"
                                  className="btn btn-outline-light"
                                  data-bs-toggle="modal"
                                  data-bs-target="#connect_test_mail"
                                >
                                  <i className="ti ti-tool me-2" />
                                  View Integration
                                </Link>
                              </div>
                              <div className="status-toggle modal-status">
                                <input
                                  type="checkbox"
                                  id="user3"
                                  className="check"
                                />
                                <label htmlFor="user3" className="checktoggle">
                                  {" "}
                                </label>
                              </div>
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
      </div>
      <>
        {/* Connect Php */}
        <div className="modal fade" id="connect_php">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">PHP Mailer</h4>
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
                        <label className="form-label">From Email Address</label>
                        <input
                          type="email"
                          className="form-control"
                          placeholder="Enter Email"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Email Password</label>
                        <input
                          type="password"
                          className="form-control"
                          placeholder="Enter Password"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">From Email Name</label>
                        <input
                          type="password"
                          className="form-control"
                          placeholder="Enter Email Name"
                        />
                      </div>
                      <div className="modal-satus-toggle d-flex align-items-center justify-content-between">
                        <div className="status-title">
                          <h5>Status</h5>
                          <p>Change the Status by toggle </p>
                        </div>
                        <div className="status-toggle modal-status">
                          <input
                            type="checkbox"
                            id="user4"
                            className="check"
                            defaultChecked
                          />
                          <label htmlFor="user4" className="checktoggle">
                            {" "}
                          </label>
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
                  <button type="submit" className="btn btn-primary">
                    Save
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Connect Php */}
        {/* Connect Smtp */}
        <div className="modal fade" id="connect_smtp">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">SMTP</h4>
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
                        <label className="form-label">From Email Address</label>
                        <input
                          type="email"
                          className="form-control"
                          placeholder="Enter Email"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Email Password</label>
                        <input
                          type="password"
                          className="form-control"
                          placeholder="Enter Password"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Email Host</label>
                        <input
                          type="password"
                          className="form-control"
                          placeholder="Enter Email Host"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Port</label>
                        <input
                          type="password"
                          className="form-control"
                          placeholder="Enter Port"
                        />
                      </div>
                      <div className="modal-satus-toggle d-flex align-items-center justify-content-between">
                        <div className="status-title">
                          <h5>Status</h5>
                          <p>Change the Status by toggle </p>
                        </div>
                        <div className="status-toggle modal-status">
                          <input
                            type="checkbox"
                            id="user5"
                            className="check"
                            defaultChecked
                          />
                          <label htmlFor="user5" className="checktoggle">
                            {" "}
                          </label>
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
                  <button type="submit" className="btn btn-primary">
                    Save
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Connect Smtp */}
        {/* Connect Test Mail */}
        <div className="modal fade" id="connect_test_mail">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Google</h4>
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
                      <div className="mb-0">
                        <label className="form-label">From Email Address</label>
                        <input
                          type="email"
                          className="form-control"
                          placeholder="Enter Email"
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
                  <button type="submit" className="btn btn-primary">
                    Submit
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Connect Test Mail */}
      </>
    </div>
  );
};

export default EmailSettings;
