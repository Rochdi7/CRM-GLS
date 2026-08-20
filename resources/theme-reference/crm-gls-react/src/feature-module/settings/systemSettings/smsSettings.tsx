
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const SmsSettings = () => {
  const routes = all_routes;
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
                <Link to={routes.emailSettings} className="d-block rounded p-2">
                  Email Settings
                </Link>
                <Link
                  to={routes.emailTemplates}
                  className="d-block rounded p-2"
                >
                  Email Templates
                </Link>
                <Link
                  to={routes.smsSettings}
                  className="d-block rounded active p-2"
                >
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
                <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                  <div className="mb-3">
                    <h5 className="mb-1">SMS Settings</h5>
                    <p>SMS Settings Configuration</p>
                  </div>
                </div>
                <div className="card">
                  <div className="card-body p-3 pb-0">
                    <div className="row">
                      <div className="col-xxl-4 col-md-6">
                        <div className="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
                          <span className="d-block">
                            <ImageWithBasePath
                              src="assets/img/icons/sms-icon-01.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
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
                            <Link
                              to="#"
                              className="btn btn-outline-light bg-white btn-icon ms-2"
                              data-bs-toggle="modal"
                              data-bs-target="#connect_sms"
                            >
                              <i className="ti ti-settings-cog" />
                            </Link>
                          </div>
                        </div>
                      </div>
                      <div className="col-xxl-4 col-md-6">
                        <div className="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
                          <span className="d-block">
                            <ImageWithBasePath
                              src="assets/img/icons/sms-icon-02.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
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
                            <Link
                              to="#"
                              className="btn btn-outline-light bg-white btn-icon ms-2"
                              data-bs-toggle="modal"
                              data-bs-target="#connect_sms"
                            >
                              <i className="ti ti-settings-cog" />
                            </Link>
                          </div>
                        </div>
                      </div>
                      <div className="col-xxl-4 col-md-6">
                        <div className="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
                          <span className="d-block">
                            <ImageWithBasePath
                              src="assets/img/icons/sms-icon-03.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
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
                            <Link
                              to="#"
                              className="btn btn-outline-light bg-white btn-icon ms-2"
                              data-bs-toggle="modal"
                              data-bs-target="#connect_sms"
                            >
                              <i className="ti ti-settings-cog" />
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <>
        {/* Connect Sms */}
        <div className="modal fade" id="connect_sms">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Nexmo</h4>
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
                        <label className="form-label">API Key</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter API Key"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">API Secret Key</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter API Secret Key"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Sender ID</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Sender ID"
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
                    Submit
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Connect Sms */}
      </>
    </div>
  );
};

export default SmsSettings;
