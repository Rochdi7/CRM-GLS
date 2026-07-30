
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const routes = all_routes;
const PaymentGateways = () => {
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
                  className="d-block rounded p-2 active"
                >
                  Payment Gateway
                </Link>
                <Link to={routes.taxRates} className="d-block rounded p-2">
                  Tax Rates
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Payment Gateways</h5>
                      <p>Payments Settings Configuration</p>
                    </div>
                    <div className="mb-3">
                      <Link
                        to={routes.email}
                        className="btn btn-outline-light bg-white btn-icon me-2"
                      >
                        <i className="ti ti-mail-share" />
                      </Link>
                      <button className="btn btn-light me-2" type="button">
                        Cancel
                      </button>
                      <button className="btn btn-primary" type="submit">
                        Save
                      </button>
                    </div>
                  </div>
                  <div className="row">
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-01.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-success text-success me-2">
                              Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user1"
                                className="check"
                                defaultChecked
                              />
                              <label htmlFor="user1" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            PayPal is the faster, safer way to send and receive
                            money or make an online payment.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            View Integration
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-02.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user2"
                                className="check"
                              />
                              <label htmlFor="user2" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            APIs to accept credit cards, manage subscriptions,
                            send money.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-03.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user3"
                                className="check"
                              />
                              <label htmlFor="user3" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Braintree offers more fraud protection and security
                            features.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-04.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user4"
                                className="check"
                              />
                              <label htmlFor="user4" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Works stably and reliably and features are valuable
                            and necessary for any software.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-05.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-success text-success me-2">
                              Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user5"
                                className="check"
                                defaultChecked
                              />
                              <label htmlFor="user5" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Razorpay is a comprehensive payment gateway and
                            financial solutions provider in India.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            View Integration
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-06.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user6"
                                className="check"
                              />
                              <label htmlFor="user6" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Allows send international money transfers and
                            payments quickly with low fees.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-07.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user7"
                                className="check"
                              />
                              <label htmlFor="user7" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Replaces your physical cards and cash with an
                            easier, safer, more private and secure{" "}
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-08.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user8"
                                className="check"
                              />
                              <label htmlFor="user8" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Fast, Low-Cost Solution for your International
                            Business.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-09.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-success text-success me-2">
                              Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user9"
                                className="check"
                                defaultChecked
                              />
                              <label htmlFor="user9" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Provide payment solution to individuals to make
                            payments using credit card.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            View Integration
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-10.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user10"
                                className="check"
                              />
                              <label htmlFor="user10" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Paytm stands for Pay through mobile and it is
                            India's largest mobile payments.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-11.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-success text-success me-2">
                              Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user11"
                                className="check"
                                defaultChecked
                              />
                              <label htmlFor="user11" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Online payment platform that enables to send and
                            receive money via emails.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            View Integration
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-12.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user12"
                                className="check"
                              />
                              <label htmlFor="user12" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Midtrans provides the maximum number of payment
                            methods across payment gateways.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-13.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-success text-success me-2">
                              Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user13"
                                className="check"
                                defaultChecked
                              />
                              <label htmlFor="user13" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            PyTorch is a network through which your customers
                            transfer funds to you.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            View Integration
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-14.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user14"
                                className="check"
                              />
                              <label htmlFor="user14" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Direct transfer of funds from one bank account into
                            another.
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="col-xxl-4 col-xl-6 d-flex">
                      <div className="card flex-fill">
                        <div className="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
                          <span className="d-inline-flex align-items-center justify-content-center border rounded p-2">
                            <ImageWithBasePath
                              src="assets/img/payment-gateway/payment-gateway-15.svg"
                              alt="Img"
                            />
                          </span>
                          <div className="d-flex align-items-center">
                            <span className="badge bg-transparent-dark text-dark me-2">
                              Not Connected
                            </span>
                            <div className="status-toggle modal-status">
                              <input
                                type="checkbox"
                                id="user15"
                                className="check"
                              />
                              <label htmlFor="user15" className="checktoggle">
                                {" "}
                              </label>
                            </div>
                          </div>
                        </div>
                        <div className="card-body pt-0">
                          <p>
                            Indicating that goods must be paid for at the time
                            of delivery.{" "}
                          </p>
                        </div>
                        <div className="card-footer">
                          <Link
                            to="#"
                            className="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#connect_payment"
                          >
                            <i className="ti ti-tool me-2" />
                            Connect Now
                          </Link>
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
        {/* Add Payment Gateway */}
        <div className="modal fade" id="connect_payment">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Paypal</h4>
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
                        <label className="form-label">Email Address</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Email Address"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">API Key</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter API Key"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Secret Key</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Secret Key"
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
                            id="user16"
                            className="check"
                            defaultChecked
                          />
                          <label htmlFor="user16" className="checktoggle">
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
                    Submit
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Add Payment Gateway */}
      </>
    </div>
  );
};

export default PaymentGateways;
