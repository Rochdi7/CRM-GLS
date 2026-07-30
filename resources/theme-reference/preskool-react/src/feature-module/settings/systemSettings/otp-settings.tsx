
import { Link } from "react-router-dom";
import CommonSelect from "../../../core/common/commonSelect";
import {
  otpSet,
  otpSetMin,
  otpSetNum,
} from "../../../core/common/selectoption/selectoption";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const OtpSettings = () => {
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
                <Link to={routes.smsSettings} className="d-block rounded p-2">
                  SMS Settings
                </Link>
                <Link
                  to={routes.optSettings}
                  className="d-block rounded active p-2"
                >
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
                      <h5 className="mb-1">OTP</h5>
                      <p>OTP configuration</p>
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
                    <div className="row flex-fill">
                      <div className="col-xl-10">
                        <div className="d-bock">
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>OTP Type</h6>
                                  <p>You can configure the type</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <CommonSelect
                                    className="select"
                                    options={otpSet}
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>OTP Digit Limit</h6>
                                  <p>Select size of the format</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <CommonSelect
                                    className="select"
                                    options={otpSetNum}
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>OTP Expire Time</h6>
                                  <p>Select Expire time of OTP</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <CommonSelect
                                    className="select"
                                    options={otpSetMin}
                                  />
                                </div>
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
    </div>
  );
};

export default OtpSettings;
