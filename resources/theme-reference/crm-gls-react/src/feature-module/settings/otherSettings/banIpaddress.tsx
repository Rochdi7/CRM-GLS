
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const routes = all_routes;
const BanIpAddress = () => {
  return (
    <div>
      <div className="page-wrapper">
        <div className="content bg-white">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Other Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Other Settings
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
                  to={routes.schoolSettings}
                  className="d-block rounded p-2"
                >
                  Storage
                </Link>
                <Link
                  to={routes.banIpAddress}
                  className="d-block active rounded p-2"
                >
                  Ban IP Address
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Ban IP Address</h5>
                      <p>Ban IP Address Configuration</p>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-light bg-white btn-icon me-2"
                        data-bs-toggle="modal"
                        data-bs-target="#add_ban_ip"
                      >
                        <i className="ti ti-plus" />
                      </Link>
                    </div>
                  </div>
                  <div className="row">
                    <div className="col-xxl-4 col-md-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header p-3 d-flex justify-content-between align-items-center">
                          <div className="d-flex align-items-center">
                            <span className="avatar avatar-sm rounded p-1 text-danger bg-soft-danger me-2">
                              <i className="ti ti-ban" />
                            </span>
                            <h6>198.120.16.01</h6>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="me-2"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_ban_ip"
                            >
                              <i className="ti ti-edit" />
                            </Link>
                            <Link
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash" />
                            </Link>
                          </div>
                        </div>
                        <div className="card-body p-3">
                          <div className="d-flex align-items-center">
                            <span className="flex-shrink-0 d-block me-2">
                              <i className="ti ti-info-square-rounded" />
                            </span>
                            <p>
                              Temporarily block to protect user accounts from
                              internet fraudsters.
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-md-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header p-3 d-flex justify-content-between align-items-center">
                          <div className="d-flex align-items-center">
                            <span className="avatar avatar-sm rounded p-1 text-danger bg-soft-danger me-2">
                              <i className="ti ti-ban" />
                            </span>
                            <h6>198.160.11.20</h6>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="me-2"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_ban_ip"
                            >
                              <i className="ti ti-edit" />
                            </Link>
                            <Link
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash" />
                            </Link>
                          </div>
                        </div>
                        <div className="card-body p-3">
                          <div className="d-flex align-items-center">
                            <span className="flex-shrink-0 d-block me-2">
                              <i className="ti ti-info-square-rounded" />
                            </span>
                            <p>
                              Unauthorized access attempts, or other signs of a
                              potential security
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-md-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header p-3 d-flex justify-content-between align-items-center">
                          <div className="d-flex align-items-center">
                            <span className="avatar avatar-sm rounded p-1 text-danger bg-soft-danger me-2">
                              <i className="ti ti-ban" />
                            </span>
                            <h6>198.123.10.2</h6>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="me-2"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_ban_ip"
                            >
                              <i className="ti ti-edit" />
                            </Link>
                            <Link
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash" />
                            </Link>
                          </div>
                        </div>
                        <div className="card-body p-3">
                          <div className="d-flex align-items-center">
                            <span className="flex-shrink-0 d-block me-2">
                              <i className="ti ti-info-square-rounded" />
                            </span>
                            <p>
                              Trying to access restricted areas of the admin
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-md-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header p-3 d-flex justify-content-between align-items-center">
                          <div className="d-flex align-items-center">
                            <span className="avatar avatar-sm rounded p-1 text-danger bg-soft-danger me-2">
                              <i className="ti ti-ban" />
                            </span>
                            <h6>198.110.01.05</h6>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="me-2"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_ban_ip"
                            >
                              <i className="ti ti-edit" />
                            </Link>
                            <Link
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash" />
                            </Link>
                          </div>
                        </div>
                        <div className="card-body p-3">
                          <div className="d-flex align-items-center">
                            <span className="flex-shrink-0 d-block me-2">
                              <i className="ti ti-info-square-rounded" />
                            </span>
                            <p>
                              Found downloading or uploading inappropriate
                              content
                            </p>
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
        {/* Add Ban Ip */}
        <div className="modal fade" id="add_ban_ip">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Ban IP Address</h4>
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
                        <label className="col-form-label">IP Address</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter IP Address"
                        />
                      </div>
                      <div className="mb-0">
                        <label className="col-form-label">Reason</label>
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
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link to="#" className="btn btn-primary"data-bs-dismiss="modal" >
                    Add IP Address
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Add Ban Ip */}
        {/* Edit Ban Ip */}
        <div className="modal fade" id="edit_ban_ip">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Ban IP Address</h4>
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
                        <label className="col-form-label">IP Address</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter IP Address"
                          defaultValue="198.120.16.01"
                        />
                      </div>
                      <div className="mb-0">
                        <label className="col-form-label">Reason</label>
                        <textarea
                          rows={4}
                          className="form-control"
                          placeholder="Add Comment"
                          defaultValue={
                            "Temporarily block to protect user accounts from internet fraudsters."
                          }
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
                  <Link to="#" className="btn btn-primary" data-bs-dismiss="modal">
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Edit Ban Ip */}
        {/* Delete Modal */}
        <div className="modal fade" id="delete-modal">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <form>
                <div className="modal-body text-center">
                  <span className="delete-icon">
                    <i className="ti ti-trash-x" />
                  </span>
                  <h4>Confirm Deletion</h4>
                  <p>
                    You want to delete all the marked items, this cant be undone
                    once you delete.
                  </p>
                  <div className="d-flex justify-content-center">
                    <Link
                      to="#"
                      className="btn btn-light me-3"
                      data-bs-dismiss="modal"
                    >
                      Cancel
                    </Link>
                    <Link to="#" className="btn btn-danger">
                      Yes, Delete
                    </Link>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Delete Modal */}
      </>
    </div>
  );
};

export default BanIpAddress;
