
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const routes = all_routes;
const Preference = () => {
  return (
    <div>
      <div className="page-wrapper">
        <div className="content bg-white">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Website Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Website Settings
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
                  to={routes.companySettings}
                  className="d-block rounded p-2"
                >
                  Company Settings
                </Link>
                <Link to={routes.localization} className="d-block rounded p-2">
                  Localization
                </Link>
                <Link to={routes.prefixes} className="d-block rounded p-2">
                  Prefixes
                </Link>
                <Link
                  to={routes.preference}
                  className="d-block rounded p-2 active"
                >
                  Preferences
                </Link>
                <Link
                  to={routes.socialAuthentication}
                  className="d-block rounded p-2"
                >
                  Social Authentication
                </Link>
                <Link to={routes.language} className="d-block rounded p-2">
                  Language
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Preferences</h5>
                      <p>Personalize your path and settings of your website</p>
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
                        <div className="card-body p-3 pb-0">
                          <div className="row">
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Students</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user1"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user1"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Teachers</h5>
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
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Guardians</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user3"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user3"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Parents</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user4"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user4"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Classes</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user5"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user5"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">
                                  Examinations
                                </h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user6"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user6"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">
                                  Fees Collection
                                </h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user7"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user7"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Library</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user8"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user8"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Sports</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user9"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user9"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Hostel</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user10"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user10"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Transport</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user11"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user11"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Reports</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user12"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user12"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Departent</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user13"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user13"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Designation</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user14"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user14"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Staffs</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user15"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user15"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
                                </div>
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="d-md-flex justify-content-between align-items-center border rounded bg-white p-3 mb-3">
                                <h5 className="fw-normal fs-15">Accounts</h5>
                                <div className="status-toggle modal-status">
                                  <input
                                    type="checkbox"
                                    id="user16"
                                    className="check"
                                  />
                                  <label
                                    htmlFor="user16"
                                    className="checktoggle"
                                  >
                                    {" "}
                                  </label>
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

export default Preference;
