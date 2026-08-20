
import { Link } from "react-router-dom";
import { driverName, PickupPoint2, routesList, VehicleNumber } from "../../../core/common/selectoption/selectoption";
import CommonSelect from "../../../core/common/commonSelect";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import dayjs from "dayjs";
import { DatePicker } from "antd";

const TransportModal = () => {
    const today = new Date()
    const year = today.getFullYear()
    const month = String(today.getMonth() + 1).padStart(2, '0') // Month is zero-based, so we add 1
    const day = String(today.getDate()).padStart(2, '0')
    const formattedDate = `${month}-${day}-${year}`
    const defaultValue = dayjs(formattedDate);
    const getModalContainer = () => {
     const modalElement = document.getElementById('modal-datepicker');
     return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
   };
    const getModalContainer2 = () => {
     const modalElement = document.getElementById('modal-datepicker2');
     return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
   };
  return (
    <>
      <>
        {/* Add Route */}
        <div className="modal fade" id="add_routes">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Route</h4>
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
                        <label className="form-label">Route Name</label>
                        <input type="text" className="form-control" />
                      </div>
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
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Add Route
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add Route*/}
        {/* Edit Route */}
        <div className="modal fade" id="edit_routes">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Route</h4>
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
                        <label className="form-label">Route Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Route Name"
                          defaultValue="Seattle"
                        />
                      </div>
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
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Edit Route */}
      </>
      <>
        {/* Add Assign New Vehicle */}
        <div className="modal fade" id="add_assign_vehicle">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Assign New Vehicle</h4>
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
                        <label className="form-label">Select Route</label>

                        <CommonSelect
                          className="select"
                          options={routesList}
                          defaultValue={undefined}
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">
                          Select Pickup Point
                        </label>
                        <CommonSelect
                          className="select"
                          options={PickupPoint2}
                          defaultValue={undefined}
                        />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Select Vehicle</label>
                        <CommonSelect
                          className="select"
                          options={VehicleNumber}
                          defaultValue={undefined}
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
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Assign Now
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add Assign New Vehicle */}
        {/* Edit Assign New Vehicle */}
        <div className="modal fade" id="edit_assign_vehicle">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Assign Vehicle</h4>
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
                        <label className="form-label">Select Route</label>
                        <CommonSelect
                          className="select"
                          options={routesList}
                          defaultValue={routesList[0]}
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">
                          Select Pickup Point
                        </label>
                        <CommonSelect
                          className="select"
                          options={PickupPoint2}
                          defaultValue={PickupPoint2[0]}
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Select Vehicle</label>
                        <CommonSelect
                          className="select"
                          options={VehicleNumber}
                          defaultValue={VehicleNumber[0]}
                        />
                      </div>
                      <div className="assigned-driver">
                        <h6>Assigned Driver</h6>
                        <div className="assigned-driver-info">
                          <span className="driver-img">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-01.jpg"
                              alt="Img"
                            />
                          </span>
                          <div>
                            <h5>Thomas</h5>
                            <span>+1 64044 748904</span>
                          </div>
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
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Assign Now
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Edit Assign New Vehicle */}
      </>
      <>
        {/* Add Pickup */}
        <div className="modal fade" id="add_pickup">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Pickup Point</h4>
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
                        <label className="form-label">Pickup Point</label>
                        <input type="text" className="form-control" />
                      </div>
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
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Add Pickup Point
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add Pickup */}
        {/* Edit Pickup */}
        <div className="modal fade" id="edit_pickup">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Pickup Point</h4>
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
                        <label className="form-label">Pickup Point</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Pickup Point"
                          defaultValue="2603 Wood Duck Drive Marquette, MI"
                        />
                      </div>
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
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Edit Pickup */}
      </>
      <>
        {/* Add Driver */}
        <div className="modal fade" id="add_driver">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add New Driver</h4>
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
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Phone Number</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">
                          Driving License Number
                        </label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Driving License Number"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Address</label>
                        <input type="text" className="form-control" />
                      </div>
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
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Add Driver
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add Driver */}
        {/* Edit Driver */}
        <div className="modal fade" id="edit_driver">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Driver</h4>
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
                          defaultValue="Thomas"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Phone Number</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Phone Number"
                          defaultValue="+1 64044 74890"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">
                          Driving License Number
                        </label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Driving License Number"
                          defaultValue="LC7899456689"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Address</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Address"
                          defaultValue="2233 Wood Street, Slidell, LA"
                        />
                      </div>
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
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Edit Driver */}
      </>
      <>
        {/* Add New Vehicle */}
        <div className="modal fade"  id="add_vehicle">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add New Vehicle</h4>
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
                <div className="modal-body" id='modal-datepicker'>
                  <div className="row">
                    <div className="col-md-12">
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Vehicle No</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Vehicle Model</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Made of Year</label>
                            <div className="date-pic">
                            <DatePicker
                                className="form-control datetimepicker"
                                format={{
                                    format: "DD-MM-YYYY",
                                    type: "mask",
                                }}
                                getPopupContainer={getModalContainer}
                                defaultValue=""
                                placeholder="16 May 2024"
                                />
                              <span className="cal-icon">
                                <i className="ti ti-calendar" />
                              </span>
                            </div>
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">
                              Registration No
                            </label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Chassis No</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Chassis No"
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Seat Capacity</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">GPS Tracking ID</label>
                        <input type="text" className="form-control" />
                      </div>
                      <hr />
                      <div className="mb-3">
                        <h4>Driver details</h4>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Select Driver</label>
                        <CommonSelect
                          className="select"
                          options={driverName}
                          defaultValue={undefined}
                        />
                      </div>
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Driver License</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">
                              Driver Contact No
                            </label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Driver Address</label>
                      <input type="text" className="form-control" />
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
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Add New Vehicle
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add New Vehicle */}
        {/* Edit New Vehicle */}
        <div className="modal fade" id="edit_vehicle">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header align-items-center">
                <div className="d-flex align-items-center">
                  <h4 className="modal-title">Edit Vehicle</h4>
                  <span className="badge badge-soft-primary ms-2">
                    ID : BB0482
                  </span>
                </div>
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
                <div className="modal-body" id='modal-datepicker2'>
                  <div className="row">
                    <div className="col-md-12">
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Vehicle No</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Vehicle No"
                              defaultValue={8930}
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Vehicle Model</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Vehicle Model"
                              defaultValue="Scania"
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Made of Year</label>
                            <div className="date-pic">
                            <DatePicker
                      className="form-control datetimepicker"
                      format={{
                        format: "DD-MM-YYYY",
                        type: "mask",
                      }}
                      getPopupContainer={getModalContainer2}
                      defaultValue={defaultValue}
                      placeholder="16 May 2024"
                    />
                              <span className="cal-icon">
                                <i className="ti ti-calendar" />
                              </span>
                            </div>
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">
                              Registration No
                            </label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Registration No"
                              defaultValue="US1A3545"
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Chassis No</label>
                            <input
                              type="text"
                              className="form-control"
                              defaultValue={32546665456}
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Seat Capacity</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Seat Capacity"
                            />
                          </div>
                        </div>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">GPS Tracking ID</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter GPS Tracking ID"
                          defaultValue="GPS7899456689"
                        />
                      </div>
                      <hr />
                      <div className="mb-3">
                        <h4>Driver details</h4>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Select Driver</label>
                        <CommonSelect
                          className="select"
                          options={driverName}
                          defaultValue={driverName[1]}
                        />
                      </div>
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Driver License</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Driver License"
                              defaultValue="LC7899456689"
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">
                              Driver Contact No
                            </label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Driver Contact No"
                              defaultValue="+1 64044 74890"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Driver Address</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Driver Address"
                        defaultValue="2233 Wood Street, Slidell, LA"
                      />
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
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Save Vehicle
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Edit New Vehicle */}
        {/* Live Track */}
        <div className="modal fade" id="live_track">
          <div className="modal-dialog modal-dialog-centered  modal-xl">
            <div className="modal-content">
              <div className="modal-header align-items-center">
                <div className="d-flex align-items-center">
                  <h4 className="modal-title">Live Tracking Vehicle</h4>
                  <span className="badge badge-soft-primary ms-2">
                    GPS Tracking ID : GPS7899456689
                  </span>
                </div>
                <button
                  type="button"
                  className="btn-close custom-btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x" />
                </button>
              </div>
              <div className="modal-body mb-4">
                <ul className="book-taker-info live-track-info justify-content-between">
                  <li>
                    <span>Vehicle No</span>
                    <h6>8930</h6>
                  </li>
                  <li>
                    <span>Vehicle Model</span>
                    <h6>Scania</h6>
                  </li>
                  <li>
                    <span>Driver</span>
                    <h6>Thomas</h6>
                  </li>
                  <li>
                    <span>Driver Contact No</span>
                    <h6>+1 45644 54784</h6>
                  </li>
                </ul>
                <div className="live-track-map w-100">
                  <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3321.6088932774796!2d-117.8132203247921!3d33.64138153931407!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80dcddf599c1986f%3A0x6826f6868b4f8e35!2sHillcrest%2C%20Irvine%2C%20CA%2092603%2C%20USA!5e0!3m2!1sen!2sin!4v1706772657955!5m2!1sen!2sin"
                    allowFullScreen
                    loading="lazy"
                    referrerPolicy="no-referrer-when-downgrade"
                  />
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
                <Link
                  to="#"
                  data-bs-dismiss="modal"
                  className="btn btn-primary"
                >
                  Reset to Live Location
                </Link>
              </div>
            </div>
          </div>
        </div>
        {/* Live Track */}
      </>

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
                  <Link
                    to="#"
                    className="btn btn-danger"
                    data-bs-dismiss="modal"
                  >
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
  );
};

export default TransportModal;
