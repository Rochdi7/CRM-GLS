
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const Notificationssettings = () => {
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
                  className="d-block rounded active p-2"
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
                <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3 pt-3">
                  <div className="mb-3">
                    <h5>Notifications</h5>
                    <p>
                      Get notification what happening right now, you can turn
                      off at any time
                    </p>
                  </div>
                </div>
                <div className="d-block">
                  <div className="card border-0 p-3 pb-0 mb-3 rounded">
                    <div className="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                      <div className="mb-3">
                        <h6>Email Notifications</h6>
                        <p>
                          Substance can send you email notifications for any new
                          direct messages
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-check-md">
                          <input className="form-check-input" type="checkbox" />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                      <div className="mb-3">
                        <h6>News and Update Settings</h6>
                        <p>
                          The latest news about latest features and softaware
                          update settings
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-check-md">
                          <input className="form-check-input" type="checkbox" />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                      <div className="mb-3">
                        <h6>Tips &amp; Tutorials</h6>
                        <p>
                          Tips &amp; Tricks in order to improve your performance
                          efficiency{" "}
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-check-md">
                          <input className="form-check-input" type="checkbox" />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center flex-wrap mb-0">
                      <div className="mb-3">
                        <h6>Offers &amp; Promotions</h6>
                        <p>
                          Promotion about package prices and its latest discouts
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-check-md">
                          <input className="form-check-input" type="checkbox" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="card border-0 p-3 pb-0 mb-3 rounded">
                    <div className="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                      <div className="mb-3">
                        <h6>More Activity</h6>
                        <p>
                          Substance can send you email notifications for any new
                          direct messages
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-switch">
                          <input
                            className="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="switch-sm"
                          />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                      <div className="mb-3">
                        <h6>All Remainders &amp; Activity</h6>
                        <p>
                          Notify all system activats and remainders that have
                          been created
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-check-md">
                          <input className="form-check-input" type="checkbox" />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                      <div className="mb-3">
                        <h6>Activity Only</h6>
                        <p>
                          Ony notify latest activity updates about increasing or
                          decreasing data
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-check-md">
                          <input className="form-check-input" type="checkbox" />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center flex-wrap mb-0">
                      <div className="mb-3">
                        <h6>Important Remainders Only</h6>
                        <p>
                          Only notify all the remainders that have been made
                        </p>
                      </div>
                      <div className="mb-3">
                        <div className="form-check form-check-md">
                          <input className="form-check-input" type="checkbox" />
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
  );
};

export default Notificationssettings;
