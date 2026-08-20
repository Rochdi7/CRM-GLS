
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const SchoolSettings = () => {
  const route = all_routes;
  return (
    <div>
      <div className="page-wrapper">
        <div className="content bg-white">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Academic Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Academic Settings
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
                  to={route.schoolSettings}
                  className="d-block rounded active p-2"
                >
                  School Settings
                </Link>
                <Link to={route.religion} className="d-block rounded p-2">
                  Religion
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">School Settings</h5>
                      <p>School Settings Configuration</p>
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
                        <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                          <div className="row align-items-center flex-fill">
                            <div className="col-xxl-8 col-lg-6">
                              <div className="mb-3">
                                <h6>School Name</h6>
                                <p>Shows name of your school</p>
                              </div>
                            </div>
                            <div className="col-xxl-4 col-lg-6">
                              <div className="mb-3">
                                <input
                                  type="text"
                                  className="form-control"
                                  placeholder="Enter School Name"
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                          <div className="row align-items-center flex-fill">
                            <div className="col-xxl-8 col-lg-6">
                              <div className="mb-3">
                                <h6>Phone Number</h6>
                                <p>Shows phone number of your school</p>
                              </div>
                            </div>
                            <div className="col-xxl-4 col-lg-6">
                              <div className="mb-3">
                                <input
                                  type="text"
                                  className="form-control"
                                  placeholder="Enter Phone Number"
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                          <div className="row align-items-center flex-fill">
                            <div className="col-xxl-8 col-lg-6">
                              <div className="mb-3">
                                <h6>Email</h6>
                                <p>Shows email of your school</p>
                              </div>
                            </div>
                            <div className="col-xxl-4 col-lg-6">
                              <div className="mb-3">
                                <input
                                  type="email"
                                  className="form-control"
                                  placeholder="Enter Email"
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                          <div className="row align-items-center flex-fill">
                            <div className="col-xxl-8 col-lg-6">
                              <div className="mb-3">
                                <h6>Fax</h6>
                                <p>Shows fax of your school</p>
                              </div>
                            </div>
                            <div className="col-xxl-4 col-lg-6">
                              <div className="mb-3">
                                <input
                                  type="text"
                                  className="form-control"
                                  placeholder="Enter Fax"
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                          <div className="row align-items-center flex-fill">
                            <div className="col-xxl-8 col-lg-6">
                              <div className="mb-3">
                                <h6>Address</h6>
                                <p>Shows address of your school</p>
                              </div>
                            </div>
                            <div className="col-xxl-4 col-lg-6">
                              <div className="mb-3">
                                <textarea
                                  rows={4}
                                  className="form-control"
                                  placeholder="Add Comment"
                                  defaultValue={""}
                                />
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

export default SchoolSettings;
