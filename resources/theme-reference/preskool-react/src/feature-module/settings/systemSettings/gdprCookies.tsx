
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import CommonSelect from "../../../core/common/commonSelect";
import { gdprCookies } from "../../../core/common/selectoption/selectoption";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const GdprCookies = () => {
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
                <Link to={routes.optSettings} className="d-block rounded p-2">
                  OTP
                </Link>
                <Link
                  to={routes.gdprCookies}
                  className="d-block rounded active p-2"
                >
                  GDPR Cookies
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="flex-fill border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">GDPR Cookies</h5>
                      <p>GDPR Cookies</p>
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
                        <div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>Cookies Content Text</h6>
                                  <p>You can configure the text here</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <textarea
                                    rows={3}
                                    className="form-control"
                                    defaultValue={""}
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>Cookies Position</h6>
                                  <p>You can configure the position</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <CommonSelect
                                    className="select"
                                    options={gdprCookies}
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>Agree Button Text</h6>
                                  <p>You can configure the text here</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Agree"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>Decline Button Text</h6>
                                  <p>You can configure the text here</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Decline"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>Show Decline Button</h6>
                                  <p>To display decline button</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <div className="status-toggle modal-status">
                                    <input
                                      type="checkbox"
                                      id="user2"
                                      className="check"
                                    />
                                    <label
                                      htmlFor="user2"
                                      className="checktoggle"
                                    >
                                      {" "}
                                    </label>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                            <div className="row align-items-center flex-fill">
                              <div className="col-xxl-8 col-lg-6">
                                <div className="mb-3">
                                  <h6>Lin for Cookies Page</h6>
                                  <p>You can configure the link here</p>
                                </div>
                              </div>
                              <div className="col-xxl-4 col-lg-6">
                                <div className="mb-3">
                                  <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Decline"
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

export default GdprCookies;
