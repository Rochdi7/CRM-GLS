
import { Link, useNavigate } from "react-router-dom";
import CommonSelect from "../../core/common/commonSelect";
import {
  customerName,
  productName,
} from "../../core/common/selectoption/selectoption";
import { DatePicker } from "antd";
import { Editor } from 'primereact/editor';
import { useState } from "react";
import { all_routes } from "../router/all_routes";

const AddInvoice = () => {
  const [text1, setText1] = useState('');
  const [text2, setText2] = useState('');
  const routes = all_routes;
  const navigation = useNavigate();

  const navigationPath = () => {
    navigation(routes.accountsInvoices);
  };
  return (
    <div>
      {" "}
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content content-two">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Add Invoice</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to={routes.teacherList}>Finance &amp; Accounts</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Add Invoice
                  </li>
                </ol>
              </nav>
            </div>
          </div>
          {/* /Page Header */}
          <div className="row">
            <div className="col-md-12">
              <form>
                <div className="card">
                  <div className="card-body pb-0">
                    {/* Customer Logo */}
                    <div className="card">
                      <div className="card-header bg-light">
                        <div className="d-flex align-items-center">
                          <span className="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                            <i className="ti ti-user-check fs-16" />
                          </span>
                          <h4 className="text-dark">Company Logo</h4>
                        </div>
                      </div>
                      <div className="card-body pb-1">
                        <div className="profile-wrap mb-3">
                          <div className="frames bg-white">
                            <i className="ti ti-photo-plus" />
                          </div>
                          <div className="profile-upload">
                            <div className="profile-uploader d-flex align-items-center">
                              <div className="drag-upload-btn mb-3">
                                Upload
                                <input
                                  type="file"
                                  className="form-control image-sign"
                                  multiple
                                />
                              </div>
                              <Link to="#" className="btn btn-primary mb-3">
                                Remove
                              </Link>
                            </div>
                            <p className="fs-12">
                              Upload image size 4MB, Format JPG, PNG, SVG
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                    {/* /Customer Logo */}
                    {/* Customer Information */}
                    <div className="card">
                      <div className="card-header bg-light">
                        <div className="d-flex align-items-center">
                          <span className="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                            <i className="ti ti-user-check fs-16" />
                          </span>
                          <h4 className="text-dark">Customer Information</h4>
                        </div>
                      </div>
                      <div className="card-body pb-0">
                        <div className="info-section">
                          <div className="row">
                            <div className="col-lg-3 col-md-6">
                              <div className="mb-3">
                                <label className="form-label">
                                  Customer Name
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={customerName}
                                  defaultValue={customerName[0]}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3 col-md-6">
                              <div className="mb-3">
                                <label className="form-label">
                                  Invoice Number
                                </label>
                                <input type="text" className="form-control" />
                              </div>
                            </div>
                            <div className="col-lg-3 col-md-6">
                              <div className="mb-3">
                                <label className="form-label">
                                  Invoice Date
                                </label>
                                <div className="input-icon position-relative">
                                  <span className="input-icon-addon">
                                    <i className="ti ti-calendar" />
                                  </span>
                                  <DatePicker
                                    className="form-control datetimepicker"
                                    placeholder="Select Date"
                                  />
                                </div>
                              </div>
                            </div>
                            <div className="col-lg-3 col-md-6">
                              <div className="mb-3">
                                <label className="form-label">Due Date</label>
                                <div className="input-icon position-relative">
                                  <span className="input-icon-addon">
                                    <i className="ti ti-calendar" />
                                  </span>
                                  <DatePicker
                                    className="form-control datetimepicker"
                                    placeholder="Select Date"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    {/* /Customer Information */}
                    {/* Product Information */}
                    <div className="card">
                      <div className="card-header bg-light">
                        <div className="d-flex align-items-center">
                          <span className="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                            <i className="ti ti-shopping-cart-copy fs-16" />
                          </span>
                          <h4 className="text-dark">Product Information</h4>
                        </div>
                      </div>
                      <div className="card-body pb-0">
                        <div className="info-section">
                          <div className="row align-items-end">
                            <div className="col-lg-3 col-md-6">
                              <div className="mb-3">
                                <label className="form-label">
                                  Product Name
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={productName}
                                  defaultValue={productName[0]}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3 col-md-6">
                              <div className="mb-3">
                                <label className="form-label">Quantity</label>
                                <input
                                  type="number"
                                  className="form-control"
                                  placeholder="Enter Quantity"
                                />
                              </div>
                            </div>
                            <div className="col-lg-2">
                              <div className="mb-3">
                                <Link to="#" className="btn btn-primary">
                                  <i className="ti ti-plus me-2" />
                                  Add to Bill
                                </Link>
                              </div>
                            </div>
                            <div className="col-md-12">
                              <div className="invoice-product-table">
                                <div className="table-responsive invoice-table">
                                  <table className="table">
                                    <thead>
                                      <tr>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Discount</th>
                                        <th>Net Amount</th>
                                        <th>Action</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td>Uniform</td>
                                        <td>
                                          <input
                                            type="number"
                                            className="form-control"
                                          />
                                        </td>
                                        <td>
                                          <input
                                            type="text"
                                            className="form-control"
                                          />
                                        </td>
                                        <td>
                                          <input
                                            type="text"
                                            className="form-control"
                                            placeholder="%"
                                          />
                                        </td>
                                        <td>$0</td>
                                        <td>
                                          <Link
                                            to="#"
                                            className="delete-invoive-list"
                                            data-bs-toggle="modal"
                                            data-bs-target="#delete-modal"
                                          >
                                            <i className="ti ti-trash" />
                                          </Link>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Description</td>
                                        <td>
                                          <input
                                            type="number"
                                            className="form-control"
                                          />
                                        </td>
                                        <td>
                                          <input
                                            type="text"
                                            className="form-control"
                                          />
                                        </td>
                                        <td>
                                          <input
                                            type="text"
                                            className="form-control"
                                            placeholder="%"
                                          />
                                        </td>
                                        <td>$0</td>
                                        <td>
                                          <Link
                                            to="#"
                                            className="delete-invoive-list"
                                            data-bs-toggle="modal"
                                            data-bs-target="#delete-modal"
                                          >
                                            <i className="ti ti-trash" />
                                          </Link>
                                        </td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    {/* /Product Information */}
                    <div className="invoice-info">
                      <div className="row">
                        <div className="col-xxl-9 col-lg-8">
                          <div className="row">
                            <div className="col-md-12">
                              <div className="mb-3">
                                <label className="form-label">Notes</label>
                                <Editor value={text1} onTextChange={(e) => setText1(e.htmlValue ?? '')} style={{ height: '130px' }} />
                              </div>
                              <div className="mb-3">
                                <label className="form-label">
                                  Terms &amp; Conditions
                                </label>
                                <Editor value={text2} onTextChange={(e) => setText2(e.htmlValue ?? '')} style={{ height: '130px' }} />
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="col-xxl-3 col-lg-4">
                          <div className="card invoice-amount-details">
                            <ul>
                              <li>
                                <span>Subtotal</span>
                                <h6>$00.00</h6>
                              </li>
                              <li>
                                <span>Discount</span>
                                <h6>$00.00</h6>
                              </li>
                              <li>
                                <span>Tax</span>
                                <h6>$00.00</h6>
                              </li>
                              <li>
                                <h5>Total</h5>
                                <h5>$00.00</h5>
                              </li>
                            </ul>
                            <div className="mb-3">
                              <label className="form-label">
                                Signature Name
                              </label>
                              <input type="text" className="form-control" />
                            </div>
                            <div className="input-block service-upload service-upload-info mb-0">
                              <span>
                                <i className="ti ti-upload me-2" />
                                Upload Signature
                              </span>
                              <input type="file" multiple id="image_sign" />
                              <div id="frames" />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="text-end">
                  <button type="button" className="btn btn-light me-3">
                    Cancel
                  </button>
                  <button type="submit" className="btn btn-primary" onClick={navigationPath}>
                    Add Invoice
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
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
    </div>
  );
};

export default AddInvoice;
