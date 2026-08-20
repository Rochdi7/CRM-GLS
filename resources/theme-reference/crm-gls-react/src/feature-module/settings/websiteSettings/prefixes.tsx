
import { all_routes } from "../../router/all_routes";
import { Link } from "react-router-dom";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const Prefixes = () => {
  const routes = all_routes;
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
                <Link
                  to={routes.prefixes}
                  className="d-block rounded p-2 active"
                >
                  Prefixes
                </Link>
                <Link to={routes.preference} className="d-block rounded p-2">
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
                      <h5 className="mb-1">Prefixes</h5>
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
                              <div className="mb-3">
                                <label className="form-label">Students</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Teachers</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Parents</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Guardians</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Subjects</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Class</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Sections</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Home Work</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Department</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">
                                  Designation
                                </label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">
                                  Notice Board
                                </label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Attendance</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Time Table</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Sports</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Syllabus</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xxl-3 col-xl-4 col-sm-6">
                              <div className="mb-3">
                                <label className="form-label">Class Room</label>
                                <input type="text" className="form-control" />
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

export default Prefixes;
