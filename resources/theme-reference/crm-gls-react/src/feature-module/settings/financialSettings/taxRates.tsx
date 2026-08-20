
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const routes = all_routes;
const TaxRates = () => {
  return (
    <div>
      <div className="page-wrapper">
        <div className="content bg-white">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Financial Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Financial Settings
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
                  to={routes.paymentGateways}
                  className="d-block rounded p-2"
                >
                  Payment Gateway
                </Link>
                <Link
                  to={routes.taxRates}
                  className="d-block rounded active p-2"
                >
                  Tax Rates
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Tax Rates</h5>
                      <p>Tax Rates Configuration</p>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-light bg-white btn-icon me-2"
                        data-bs-toggle="modal"
                        data-bs-target="#add_tax_rate"
                      >
                        <i className="ti ti-plus" />
                      </Link>
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
                        <div className="col-xxl-4 col-lg-6">
                          <div className="mb-3">
                            <label className="form-label">VAT</label>
                            <div className="position-relative">
                              <input
                                type="text"
                                className="form-control pe-5"
                                defaultValue="16%"
                              />
                              <div className="d-flex align-items-center position-absolute end-0 top-0 me-2 mt-2">
                                <Link
                                  to="#"
                                  data-bs-toggle="modal"
                                  data-bs-target="#edit_tax_rate"
                                >
                                  <i className="ti ti-edit me-2" />
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
                          </div>
                        </div>
                        <div className="col-xxl-4 col-lg-6">
                          <div className="mb-3">
                            <label className="form-label">GST</label>
                            <div className="position-relative">
                              <input
                                type="text"
                                className="form-control"
                                defaultValue="14%"
                              />
                              <div className="d-flex align-items-center position-absolute end-0 top-0 me-2 mt-2">
                                <Link
                                  to="#"
                                  data-bs-toggle="modal"
                                  data-bs-target="#edit_tax_rate"
                                >
                                  <i className="ti ti-edit me-2" />
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
                          </div>
                        </div>
                        <div className="col-xxl-4 col-lg-6">
                          <div className="mb-3">
                            <label className="form-label">HST</label>
                            <div className="position-relative">
                              <input
                                type="text"
                                className="form-control"
                                defaultValue="12%"
                              />
                              <div className="d-flex align-items-center position-absolute end-0 top-0 me-2 mt-2">
                                <Link
                                  to="#"
                                  data-bs-toggle="modal"
                                  data-bs-target="#edit_tax_rate"
                                >
                                  <i className="ti ti-edit me-2" />
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
        {/* Add Tax Rate */}
        <div className="modal fade" id="add_tax_rate">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Tax Rate</h4>
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
                        <label className="form-label">Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Name"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Tax Rate %</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Tax Rate"
                        />
                      </div>
                      <div className="modal-satus-toggle d-flex align-items-center justify-content-between">
                        <div className="status-title">
                          <h5>Status</h5>
                          <p>Change the Status by toggle </p>
                        </div>
                        <div className="status-toggle modal-status">
                          <input type="checkbox" id="user1" className="check" />
                          <label htmlFor="user1" className="checktoggle">
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
                    Add Tax Rate
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Add Tax Rate */}
        {/* Edit Tax Rate */}
        <div className="modal fade" id="edit_tax_rate">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Tax Rate</h4>
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
                        <label className="form-label">Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Name"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Tax Rate %</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Tax Rate"
                          defaultValue="5%"
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
                            id="user2"
                            className="check"
                            defaultChecked
                          />
                          <label htmlFor="user2" className="checktoggle">
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
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Edit Tax Rate */}
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
                      className="btn btn-light me-2"
                      data-bs-dismiss="modal"
                    >
                      Cancel
                    </Link>
                    <button type="submit" className="btn btn-danger">
                      Yes, Delete
                    </button>
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

export default TaxRates;
