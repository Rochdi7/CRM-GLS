
import { Link } from "react-router-dom";
import Select from "react-select";
import DefaultEditor from "react-simple-wysiwyg";
import { DatePicker } from "antd";
import React from "react";

const NotesModal = () => {
  const [values, setValue] = React.useState();

  function onChange(e: any) {
    setValue(e.target.value);
  }
  const [values2, setValue2] = React.useState();

  function onChange2(e: any) {
    setValue2(e.target.value);
  }

  const optionsChoose = [
    { value: "Choose", label: "Choose" },
    { value: "Recent1", label: "Recent1" },
    { value: "Recent2", label: "Recent2" },
  ];

  const optionsSelect = [
    { value: "Select", label: "Select" },
    { value: "Recent1", label: "Recent1" },
    { value: "Recent2", label: "Recent2" },
  ];

  const optionsOnHold = [{ value: "Onhold", label: "Onhold" }];

  const optionsPriority = [
    { value: "High", label: "High" },
    { value: "Medium", label: "Medium" },
    { value: "Low", label: "Low" },
  ];
  return (
    <>
      {/* Add Note */}
      <div className="modal fade" id="note-units">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Notes</h4>
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
                  <div className="col-12">
                    <div className="mb-3">
                      <label className="form-label">Todo Title</label>
                      <input type="text" className="form-control" />
                    </div>
                  </div>
                  <div className="col-12">
                    <div className="mb-3">
                      <label className="form-label">Assignee</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsChoose}
                        placeholder="Choose"
                      />
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="mb-3">
                      <label className="form-label">Tag</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsSelect}
                        placeholder="Select"
                      />
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="mb-3">
                      <label className="form-label">Priority</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsSelect}
                        placeholder="Select"
                      />
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="input-blocks todo-calendar">
                      <label className="form-label">Due Date</label>
                      <div className="input-groupicon calender-input">
                        <DatePicker
                          className="form-control datetimepicker"
                          placeholder="Select Date"
                        />
                      </div>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="mb-3">
                      <label className="form-label">Status</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsSelect}
                        placeholder="Select"
                      />
                    </div>
                  </div>
                  <div className="col-lg-12">
                    <div className="mb-0 summer-description-box notes-summernote">
                      <label className="form-label">Descriptions</label>
                      <DefaultEditor
                        id="summernote"
                        value={values2}
                        onChange={onChange2}
                      />
                      <p>Maximum 60 Characters</p>
                    </div>
                  </div>
                </div>
              </div>
              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-light me-2"
                  data-bs-dismiss="modal"
                >
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary">
                  Submit
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Add Note */}
      {/* Edit Note */}
      <div className="modal fade" id="edit-note-units">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Edit Notes</h4>
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
                  <div className="col-12">
                    <div className="mb-3">
                      <label className="form-label">Todo Title</label>
                      <input
                        type="text"
                        className="form-control"
                        defaultValue="Team meet at Starbucks"
                      />
                    </div>
                  </div>
                  <div className="col-12">
                    <div className="mb-3">
                      <label className="form-label">Assignee</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsSelect}
                        placeholder="Select"
                      />
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="mb-3">
                      <label className="form-label">Tag</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsOnHold}
                        placeholder="Onhold"
                      />
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="mb-3">
                      <label className="form-label">Priority</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsPriority}
                        placeholder="Priority"
                      />
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="input-blocks todo-calendar">
                      <label className="form-label">Due Date</label>
                      <div className="input-groupicon calender-input">
                        <DatePicker
                          className="form-control datetimepicker"
                          placeholder="Select Date"
                        />
                      </div>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="mb-3">
                      <label className="form-label">Status</label>
                      <Select classNamePrefix="react-select"
                        className="select"
                        options={optionsChoose}
                        placeholder="Choose"
                      />
                    </div>
                  </div>
                  <div className="col-lg-12">
                    <div className="mb-0 summer-description-box notes-summernote">
                      <label className="form-label">Descriptions</label>
                      {/* <div id="summernote" /> */}
                      <DefaultEditor
                        id="summernote"
                        value={values}
                        onChange={onChange}
                      />
                      <p>Maximum 60 Characters</p>
                    </div>
                  </div>
                </div>
              </div>
              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-light me-2"
                  data-bs-dismiss="modal"
                >
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Edit Note */}
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
      {/* View Note */}
      <div className="modal fade" id="view-note-units">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="page-wrapper-new p-0">
              <div className="content">
                <div className="modal-header">
                  <div className="d-flex align-items-center">
                    <h4 className="modal-title me-3">Notes</h4>
                    <p className="text-info">Personal</p>
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
                <div className="modal-body">
                  <div className="row">
                    <div className="col-12">
                      <div>
                        <h4 className="mb-2">
                          Meet Lisa to discuss project details
                        </h4>
                        <p>
                          Hiking is a long, vigorous walk, usually on trails or
                          footpaths in the countryside. Walking for pleasure
                          developed in Europe during the eighteenth century.
                          Religious pilgrimages have existed much longer but
                          they involve walking long distances for a spiritual
                          purpose associated with specific religions and also we
                          achieve inner peace while we hike at a local park.
                        </p>
                        <p className="badge bg-outline-danger d-inline-flex align-items-center mb-0">
                          <i className="fas fa-circle fs-6 me-1" /> High
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-danger"
                    data-bs-dismiss="modal"
                  >
                    Close
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* /View Note */}
    </>
  );
};

export default NotesModal;
