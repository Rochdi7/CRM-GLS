
import {
  bedcount,
  hostelType,
  roomtype,
} from "../../../core/common/selectoption/selectoption";
import CommonSelect from "../../../core/common/commonSelect";
import { Link } from "react-router-dom";

const HostelModal = () => {
  return (
    <>
      <>
        {/* Add Hostel Rooms */}
        <div className="modal fade" id="add_hostel_rooms">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Hostel Room</h4>
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
                        <label className="form-label">Room No</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Hostel Name</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Room Type</label>
                        <CommonSelect
                          className="select"
                          options={roomtype}
                          defaultValue={undefined}
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">No of Bed</label>
                        <CommonSelect
                          className="select"
                          options={bedcount}
                          defaultValue={undefined}
                        />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Cost per Bed</label>
                        <input type="text" className="form-control" />
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
                    Add Hostel Room
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add Hostel Rooms */}
        {/* Edit Hostel Room */}
        <div className="modal fade" id="edit_hostel_rooms">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Hostel Room</h4>
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
                        <label className="form-label">Room No</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Room No"
                          defaultValue="A1"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Hostel Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Hostel Name"
                          defaultValue="Phoenix Residence"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Room Type</label>
                        <CommonSelect
                          className="select"
                          options={roomtype}
                          defaultValue={roomtype[0]}
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">No of Bed</label>
                        <CommonSelect
                          className="select"
                          options={bedcount}
                          defaultValue={bedcount[0]}
                        />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Cost per Bed</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Cost per Bed"
                          defaultValue="$200"
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
                    Save Changes
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Edit Hostel Room */}
      </>

      <>
        {/* Add Room Type*/}
        <div className="modal fade" id="add_hostel_room_type">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Room Type</h4>
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
                        <label className="form-label">Room Type</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Cost per Bed</label>
                        <textarea
                          className="form-control"
                          rows={4}
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
                  <Link
                    to="#"
                    data-bs-dismiss="modal"
                    className="btn btn-primary"
                  >
                    Add Room Type
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add Room Type */}
        {/* Edit Room Type */}
        <div className="modal fade" id="edit_hostel_room_type">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Room Type</h4>
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
                        <label className="form-label">Room Type</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Room Type"
                          defaultValue="Two Bed"
                        />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Cost per Bed</label>
                        <textarea
                          className="form-control"
                          placeholder="text"
                          rows={4}
                          defaultValue={
                            "Enjoy serene solitude in our one-bed room, your tranquil retreat for focused studying"
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
        {/* Edit Room Type */}
      </>

      {/* Add Hostel */}
      <div className="modal fade" id="add_hostel">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Hostel</h4>
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
                      <label className="form-label">Hostel Name</label>
                      <input type="text" className="form-control" />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Hostel Type</label>
                      <CommonSelect
                        className="select"
                        options={hostelType}
                        defaultValue={undefined}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Intake</label>
                      <input type="text" className="form-control" />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Address</label>
                      <input type="text" className="form-control" />
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Description</label>
                      <textarea
                        className="form-control"
                        rows={4}
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
                <Link
                  to="#"
                  data-bs-dismiss="modal"
                  className="btn btn-primary"
                >
                  Add Hostel
                </Link>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Add Hostel */}
      {/* Edit Hostel */}
      <div className="modal fade" id="edit_hostel">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Edit Hostel</h4>
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
                      <label className="form-label">Hostel Name</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Hostel Name"
                        value={"Phoenix Residence"}
                      />
                    </div>
                    <div className="mb-4">
                      <label className="form-label">Hostel Type</label>
                      <CommonSelect
                        className="select"
                        options={hostelType}
                        defaultValue={hostelType[0]}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Intake</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Intake"
                        defaultValue={150}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Address</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Address"
                        defaultValue="25 Crowfield Road, Phoenix	1"
                      />
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Description</label>
                      <textarea
                        className="form-control"
                        rows={4}
                        defaultValue={"Rising to nurture young minds"}
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
                  Save Changes
                </Link>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Edit Hostel */}
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

export default HostelModal;
