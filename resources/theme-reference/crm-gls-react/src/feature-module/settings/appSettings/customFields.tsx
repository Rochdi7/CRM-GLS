
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import CommonSelect from "../../../core/common/commonSelect";
import {
  customeFields,
  editcustomeFields,
} from "../../../core/common/selectoption/selectoption";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const routes = all_routes;
const CustomFields = () => {
  return (
    <div>
      <div className="page-wrapper">
        <div className="content bg-white">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">App Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    App Settings
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
                  to={routes.invoiceSettings}
                  className="d-block rounded p-2"
                >
                  Invoice Settings
                </Link>
                <Link
                  to={routes.customFields}
                  className="d-block rounded p-2 active"
                >
                  Custom Fields
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="flex-fill border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Custom Fields</h5>
                      <p>Custom Fields configuration</p>
                    </div>
                    <div className="mb-3">
                      <Link
                        to="#"
                        className="btn btn-outline-light bg-white btn-icon me-2"
                        data-bs-toggle="modal"
                        data-bs-target="#add_custom_field"
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
                  <div className="d-md-flex d-block">
                    <div className="flex-fill">
                      <div className="card">
                        <div className="card-header d-flex align-items-center justify-content-between p-3">
                          <h5>Expense</h5>
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="btn btn-outline-light bg-white btn-icon me-2"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_custom_field"
                            >
                              <i className="ti ti-edit" />
                            </Link>
                            <Link
                              to="#"
                              className="btn btn-outline-light bg-white btn-icon"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash" />
                            </Link>
                          </div>
                        </div>
                        <div className="card-body p-3 pb-0">
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">Label</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0 pe-xl-0">
                              <div className="mb-3 flex-fill  me-xl-3 me-0">
                                <label className="form-label">Type</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">
                                  Default Value
                                </label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                          </div>
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">
                                  Required Value
                                </label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">Status</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="card">
                        <div className="card-header d-flex align-items-center justify-content-between p-3">
                          <h5>Transaction</h5>
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="btn btn-outline-light bg-white btn-icon me-2"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_custom_field"
                            >
                              <i className="ti ti-edit" />
                            </Link>
                            <Link
                              to="#"
                              className="btn btn-outline-light bg-white btn-icon"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash" />
                            </Link>
                          </div>
                        </div>
                        <div className="card-body p-3 pb-0">
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">Label</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0 pe-xl-0">
                              <div className="mb-3 flex-fill  me-xl-3 me-0">
                                <label className="form-label">Type</label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">
                                  Default Value
                                </label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                          </div>
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">
                                  Required Value
                                </label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">Status</label>
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
      <>
        {/* Add Custom Field */}
        <div className="modal fade" id="add_custom_field">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Custom Field</h4>
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
                        <label className="form-label">Choose Fields For</label>
                        <CommonSelect
                          className="select"
                          options={customeFields}
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Label</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Label"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Type</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Type"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Default Value</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Value"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Required</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="d-flex align-items-center justify-content-between">
                        <div className="status-title">
                          <h5>Status</h5>
                          <p>Change the Status by toggle </p>
                        </div>
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
                    Add Field
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Add Custom Field */}
        {/* Edit Custom Field */}
        <div className="modal fade" id="edit_custom_field">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Custom Field</h4>
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
                        <label className="form-label">Choose Fields For</label>
                        <CommonSelect
                          className="select"
                          options={editcustomeFields}
                          defaultValue={editcustomeFields[1]}
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Label</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Label"
                          defaultValue="Text"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Type</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Type"
                          defaultValue="Text"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Default Value</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Value"
                          defaultValue="Text"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Required</label>
                        <input
                          type="text"
                          className="form-control"
                          defaultValue="Text"
                        />
                      </div>
                      <div className="d-flex align-items-center justify-content-between">
                        <div className="status-title">
                          <h5>Status</h5>
                          <p>Change the Status by toggle </p>
                        </div>
                        <div className="form-check form-switch">
                          <input
                            className="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="switch-sm2"
                          />
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
        {/* /Edit Custom Field */}
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

export default CustomFields;
