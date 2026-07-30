
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";

const LibraryModal = () => {
  return (
    <>
      <>
        {/* Book Details */}
        <div className="modal fade" id="book_details">
          <div className="modal-dialog modal-dialog-centered  modal-lg">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">View Details</h4>
                <button
                  type="button"
                  className="btn-close custom-btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x" />
                </button>
              </div>
              <div className="modal-body">
                <div className="view-book">
                  <div className="view-book-title">
                    <h5>Issue Book Details</h5>
                  </div>
                  <div className="book-issue-details">
                    <div className="book-details-head">
                      <span className="text-primary">IB853629</span>
                      <h6>
                        <span>Issue Date :</span> 19 May 2024
                      </h6>
                    </div>
                    <ul className="book-taker-info">
                      <li>
                        <div className="d-flex align-items-center">
                          <span className="student-img">
                            <ImageWithBasePath
                              src="assets/img/students/student-01.jpg"
                              className="img-fluid rounded-circle"
                              alt="Img"
                            />
                          </span>
                          <h6>
                            Janet <br /> III, A
                          </h6>
                        </div>
                      </li>
                      <li>
                        <span>Roll No</span>
                        <h6>35010</h6>
                      </li>
                      <li>
                        <span>Book Name</span>
                        <h6>Echoes of Eternity</h6>
                      </li>
                      <li>
                        <span>Book No</span>
                        <h6>501</h6>
                      </li>
                      <li>
                        <span>Due Date</span>
                        <h6>19 May 2024</h6>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        {/* Book Details */}
      </>

      {/* Add Member */}
      <div className="modal fade" id="add_library_members">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Member</h4>
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
                      <label className="form-label">Card No</label>
                      <input type="text" className="form-control" />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Email</label>
                      <input type="text" className="form-control" />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Date of Join</label>
                      <input type="text" className="form-control" />
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Phone Number</label>
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
                  Add Member
                </Link>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* Add Member */}
      {/* Edit Member */}
      <div className="modal fade" id="edit_library_members">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Edit Member</h4>
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
                        defaultValue="James"
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Card No</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Card No"
                        defaultValue={501}
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Email</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Email"
                        defaultValue="james@example.com"
                      />
                    </div>
                    <div className="mb-3">
                      <label className="form-label">Date of Join</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Date of Join"
                        defaultValue="22 Apr 2024"
                      />
                    </div>
                    <div className="mb-0">
                      <label className="form-label">Phone Number</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Phone Number"
                        defaultValue="+1 78429 82414"
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
      {/* Edit Member */}

      <>
        {/* Add Book */}
        <div className="modal fade" id="add_library_book">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Book</h4>
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
                        <label className="form-label">Book Name</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Book No</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Rack No</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Publisher</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Author</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Subject</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Qty</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Available</label>
                            <input type="text" className="form-control" />
                          </div>
                        </div>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Price</label>
                        <input type="text" className="form-control" />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Post Date</label>
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
                    Add Book
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* Add Book */}
        {/* Edit Book */}
        <div className="modal fade" id="edit_library_book">
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Edit Book</h4>
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
                        <label className="form-label">Book Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Book Name"
                          defaultValue="Echoes of Eternity"
                        />
                      </div>
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Book No</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Book No"
                              defaultValue={501}
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Rack No</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Rack Name"
                              defaultValue={6550}
                            />
                          </div>
                        </div>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Publisher</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Publisher"
                          defaultValue="Aurora Press"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Author</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Author"
                          defaultValue=" Isabella Rivers"
                        />
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Subject</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Subject"
                          defaultValue="History"
                        />
                      </div>
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Qty</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Qty"
                              defaultValue={150}
                            />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Available</label>
                            <input
                              type="text"
                              className="form-control"
                              placeholder="Enter Available"
                              defaultValue={120}
                            />
                          </div>
                        </div>
                      </div>
                      <div className="mb-3">
                        <label className="form-label">Price</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Price"
                          defaultValue="$300"
                        />
                      </div>
                      <div className="mb-0">
                        <label className="form-label">Post Date</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter Post Date"
                          defaultValue="25 Apr 2024"
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
        {/* Edit Book */}
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

export default LibraryModal;
