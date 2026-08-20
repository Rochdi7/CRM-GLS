
import { Link } from "react-router-dom";
import Table from "../../core/common/dataTable/index";
import { accounts_income_data } from "../../core/data/json/accounts_income_data";
import type { TableData } from "../../core/data/interface";
import PredefinedDateRanges from "../../core/common/datePicker";
import CommonSelect from "../../core/common/commonSelect";
import {
  incomeName,
  invoiceNumber,
  paymentMethod,
  source,
} from "../../core/common/selectoption/selectoption";
import { DatePicker } from "antd";
import { all_routes } from "../router/all_routes";
import TooltipOption from "../../core/common/tooltipOption";
import ImageWithBasePath from "../../core/common/imageWithBasePath";

const AccountsIncome = () => {
  const routes = all_routes;
  const data = accounts_income_data;

  const columns = [
    {
      title: "ID",
      dataIndex: "id",
      render: (text: any) => (
        <Link
          to="#"
          className="link-primary"
          data-bs-toggle="modal"
          data-bs-target="#view_invoice"
        >
          {text}
        </Link>
      ),
      sorter: (a: TableData, b: TableData) => a.id.length - b.id.length,
    },
    {
      title: "Income Name",
      dataIndex: "incomeName",
      sorter: (a: TableData, b: TableData) =>
        a.incomeName.length - b.incomeName.length,
    },
    {
      title: "Description",
      dataIndex: "description",
      sorter: (a: TableData, b: TableData) =>
        a.description.length - b.description.length,
    },
    {
      title: "Source",
      dataIndex: "source",
      sorter: (a: TableData, b: TableData) => a.source.length - b.source.length,
    },
    {
      title: "Date",
      dataIndex: "date",
      sorter: (a: TableData, b: TableData) => a.date.length - b.date.length,
    },
    {
      title: "Amount",
      dataIndex: "amount",
      sorter: (a: TableData, b: TableData) => a.amount.length - b.amount.length,
    },
    {
      title: "Invoice No",
      dataIndex: "invoiceNo",
      sorter: (a: TableData, b: TableData) =>
        a.invoiceNo.length - b.invoiceNo.length,
      render: (text: any) => (
        <Link
          to="#"
          className="link-primary"
          data-bs-toggle="modal"
          data-bs-target="#view_invoice"
        >
          {text}
        </Link>
      ),
    },
    {
      title: "Payment Method",
      dataIndex: "paymentMethod",
      sorter: (a: TableData, b: TableData) =>
        a.paymentMethod.length - b.paymentMethod.length,
    },
    {
      title: "Action",
      dataIndex: "action",
      render: () => (
        <>
          <div className="dropdown">
            <Link
              to="#"
              className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <i className="ti ti-dots-vertical fs-14" />
            </Link>
            <ul className="dropdown-menu dropdown-menu-right p-3">
              <li>
                <Link
                  className="dropdown-item rounded-1"
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_income"
                >
                  <i className="ti ti-edit-circle me-2" />
                  Edit
                </Link>
              </li>
              <li>
                <Link
                  className="dropdown-item rounded-1"
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#delete-modal"
                >
                  <i className="ti ti-trash-x me-2" />
                  Delete
                </Link>
              </li>
            </ul>
          </div>
        </>
      ),
    },
  ];

  return (
    <div>
      {" "}
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Income</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Finance &amp; Accounts</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Income
                  </li>
                </ol>
              </nav>
            </div>
            <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
              <TooltipOption />
              <div className="mb-2">
                <Link
                  to="#"
                  className="btn btn-primary d-flex align-items-center"
                  data-bs-toggle="modal"
                  data-bs-target="#add_income"
                >
                  <i className="ti ti-square-rounded-plus me-2" />
                  Add Income
                </Link>
              </div>
            </div>
          </div>
          {/* /Page Header */}
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
              <h4 className="mb-3">Income List</h4>
              <div className="d-flex align-items-center flex-wrap">
                <div className="input-icon-start mb-3 me-2 position-relative">
                  <PredefinedDateRanges />
                </div>
                <div className="dropdown mb-3 me-2">
                  <Link
                    to="#"
                    className="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                  >
                    <i className="ti ti-filter me-2" />
                    Filter
                  </Link>
                  <div className="dropdown-menu drop-width">
                    <form>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <h4>Filter</h4>
                      </div>
                      <div className="p-3 pb-0 border-bottom">
                        <div className="row">
                          <div className="col-md-6">
                            <div className="mb-3">
                              <label className="form-label">Income Name</label>
                              <CommonSelect
                                className="select"
                                options={incomeName}
                                defaultValue={incomeName[0]}
                              />
                            </div>
                          </div>
                          <div className="col-md-6">
                            <div className="mb-3">
                              <label className="form-label">Source</label>
                              <CommonSelect
                                className="select"
                                options={source}
                                defaultValue={source[0]}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">
                                Invoice Number
                              </label>
                              <CommonSelect
                                className="select"
                                options={invoiceNumber}
                                defaultValue={invoiceNumber[0]}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="p-3 d-flex align-items-center justify-content-end">
                        <Link to="#" className="btn btn-light me-3">
                          Reset
                        </Link>
                        <button type="submit" className="btn btn-primary">
                          Apply
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
                <div className="dropdown mb-3">
                  <Link
                    to="#"
                    className="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown"
                  >
                    <i className="ti ti-sort-ascending-2 me-2" />
                    Sort by A-Z
                  </Link>
                  <ul className="dropdown-menu p-3">
                    <li>
                      <Link to="#" className="dropdown-item rounded-1 active">
                        Ascending
                      </Link>
                    </li>
                    <li>
                      <Link to="#" className="dropdown-item rounded-1">
                        Descending
                      </Link>
                    </li>
                    <li>
                      <Link to="#" className="dropdown-item rounded-1">
                        Recently Viewed
                      </Link>
                    </li>
                    <li>
                      <Link to="#" className="dropdown-item rounded-1">
                        Recently Added
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body p-0 py-3">
              {/* Income List */}
              <Table dataSource={data} columns={columns} Selection={true} />
              {/* /Income List */}
            </div>
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
      {/* Add Income */}
      <div className="modal fade" id="add_income">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Income</h4>
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
                      <label className="form-label">Income Name</label>
                      <input type="text" className="form-control" />
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="mb-3">
                      <label className="form-label">Source</label>
                      <input type="text" className="form-control" />
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Date of Birth</label>
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
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Amount</label>
                      <input type="text" className="form-control" />
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Invoice No</label>
                      <input type="text" className="form-control" />
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Payment Method</label>
                      <CommonSelect
                        className="select"
                        options={paymentMethod}
                        defaultValue={paymentMethod[0]}
                      />
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="mb-0">
                      <label className="form-label">Description</label>
                      <textarea
                        rows={4}
                        className="form-control"
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
                <button type="submit" className="btn btn-primary">
                  Add Income
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Add Income */}
      {/* Edit Income */}
      <div className="modal fade" id="edit_income">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Edit Income</h4>
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
                      <label className="form-label">Income Name</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Income Name"
                        defaultValue="April Month Fees"
                      />
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="mb-3">
                      <label className="form-label">Source</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Source"
                        defaultValue="Tuition Fees"
                      />
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Date of Birth</label>
                      <DatePicker
                        className="form-control datetimepicker"
                        placeholder="Select Date"
                      />
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Amount</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Amount"
                        defaultValue="$15,000"
                      />
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Invoice No</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Invoice No"
                        defaultValue="INV681537"
                      />
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Payment Method</label>
                      <CommonSelect
                        className="select"
                        options={paymentMethod}
                        defaultValue={paymentMethod[0]}
                      />
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="mb-0">
                      <label className="form-label">Description</label>
                      <textarea
                        rows={4}
                        className="form-control"
                        placeholder="text"
                        defaultValue={"Tuition for Term 1, Class II"}
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
                <button type="submit" className="btn btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Edit Income */}
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
      {/* View Modal */}
      <div className="modal fade" id="view_invoice">
        <div className="modal-dialog modal-dialog-centered  modal-xl invoice-modal">
          <div className="modal-content">
            <div className="modal-wrapper">
              <div className="invoice-popup-head d-flex align-items-center justify-content-between mb-4">
                <span>
                  <ImageWithBasePath src="assets/img/logo.svg" alt="Img" />
                </span>
                <div className="popup-title">
                  <h2>UNIVERSITY NAME</h2>
                  <p>Original For Recipient</p>
                </div>
              </div>
              <div className="tax-info mb-2">
                <div className="mb-4 text-center">
                  <h1>Tax Invoice</h1>
                </div>
                <div className="row">
                  <div className="col-lg-4">
                    <div className="tax-invoice-info d-flex align-items-center justify-content-between">
                      <h5>Student Name :</h5>
                      <h6>Walter Roberson</h6>
                    </div>
                  </div>
                  <div className="col-lg-4">
                    <div className="tax-invoice-info d-flex align-items-center justify-content-between">
                      <h5>Student ID :</h5>
                      <h6>DD465123</h6>
                    </div>
                  </div>
                  <div className="col-lg-4">
                    <div className="tax-invoice-info d-flex align-items-center justify-content-between">
                      <h5>Term :</h5>
                      <h6>Term 1</h6>
                    </div>
                  </div>
                  <div className="col-lg-4">
                    <div className="tax-invoice-info d-flex align-items-center justify-content-between">
                      <h5>Invoice No :</h5>
                      <h6>INV681531</h6>
                    </div>
                  </div>
                  <div className="col-lg-4">
                    <div className="tax-invoice-info d-flex align-items-center justify-content-between">
                      <h5>Invoice Date :</h5>
                      <h6>24 Apr 2024</h6>
                    </div>
                  </div>
                  <div className="col-lg-4">
                    <div className="tax-invoice-info d-flex align-items-center justify-content-between">
                      <h5>Due Date :</h5>
                      <h6>30 Apr 2024</h6>
                    </div>
                  </div>
                </div>
                <div className="mb-4">
                  <h6 className="mb-1">Bill To :</h6>
                  <p>
                    <span className="text-dark">Walter Roberson</span> <br />
                    299 Star Trek Drive, Panama City, Florida, 32405, USA.{" "}
                    <br />
                    walter@gmail.com <br />
                    +45 5421 4523
                  </p>
                </div>
                <div className="invoice-product-table">
                  <div className="table-responsive invoice-table">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Description</th>
                          <th>Due Date</th>
                          <th>Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>Semester Fees</td>
                          <td>25 Apr 2024</td>
                          <td>$5,000</td>
                        </tr>
                        <tr>
                          <td>Exam Fees</td>
                          <td>25 Apr 2024</td>
                          <td>$1000</td>
                        </tr>
                        <tr>
                          <td>Transport Fees</td>
                          <td>25 Apr 2024</td>
                          <td>$4,000</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div className="row">
                  <div className="col-lg-6">
                    <div className="mb-3">
                      <h5 className="mb-1">Important Note: </h5>
                      <p className="text-dark mb-0">
                        Delivery dates are not guaranteed and Seller has
                      </p>
                      <p className="text-dark">
                        no liability for damages that may be incurred due to any
                        delay. has
                      </p>
                    </div>
                    <div>
                      <h5 className="mb-1">Total amount ( in words):</h5>
                      <p className="text-dark fw-medium">
                        USD Ten Thousand One Hundred Sixty Five Only
                      </p>
                    </div>
                  </div>
                  <div className="col-lg-6">
                    <div className="total-amount-tax">
                      <ul>
                        <li className="fw-medium text-dark">Subtotal</li>
                        <li className="fw-medium text-dark">Discount 0%</li>
                        <li className="fw-medium text-dark">IGST 18.0%</li>
                      </ul>
                      <ul>
                        <li>$10,000.00</li>
                        <li>+ $0.00</li>
                        <li>$10,000.00</li>
                      </ul>
                    </div>
                    <div className="total-amount-tax mb-3">
                      <ul className="total-amount">
                        <li className="text-dark">Amount Payable</li>
                      </ul>
                      <ul className="total-amount">
                        <li className="text-dark">$10,165.00</li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div className="payment-info">
                  <div className="row align-items-center">
                    <div className="col-lg-6 mb-4 pt-4">
                      <h5 className="mb-2">Payment Info:</h5>
                      <p className="mb-1">
                        Debit Card :{" "}
                        <span className="fw-medium text-dark">
                          465 *************645
                        </span>
                      </p>
                      <p className="mb-0">
                        Amount :{" "}
                        <span className="fw-medium text-dark">$10,165</span>
                      </p>
                    </div>
                    <div className="col-lg-6 text-end mb-4 pt-4 ">
                      <h6 className="mb-2">For Dreamguys</h6>
                      <ImageWithBasePath src="assets/img/icons/signature.svg" alt="Img" />
                    </div>
                  </div>
                </div>
                <div className="border-bottom text-center pt-4 pb-4">
                  <span className="text-dark fw-medium">
                    Terms &amp; Conditions :{" "}
                  </span>
                  <p>
                    Here we can write a additional notes for the client to get a
                    better understanding of this invoice.
                  </p>
                </div>
                <p className="text-center pt-3">Thanks for your Business</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* /View Modal */}
    </div>
  );
};

export default AccountsIncome;
