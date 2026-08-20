
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../core/common/imageWithBasePath";

const FileModal = () => {
  return (
    <>
      <div>
        {/* Upload File */}
        <div
          className="modal fade modal-default file-manager-modal upload-modal custom-modal"
          id="upload-file"
          aria-labelledby="upload-file"
        >
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header p-4">
                <h5>Upload File</h5>
                <button
                  className="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x"></i>
                </button>
              </div>
              <div className="modal-body p-4">
                <div className="drag-drop text-center mb-4">
                  <div className="upload">
                    <Link to="#">
                      <ImageWithBasePath
                        src="assets/img/icons/drag-drop.svg"
                        alt=""
                      />
                    </Link>
                    <p>
                      Drag and drop a <Link to="#">file to upload</Link>
                    </p>
                  </div>
                  <input type="file" multiple />
                </div>
                <div className="d-flex align-items-center justify-content-between">
                  <p>3 of 1 files Uploaded</p>
                  <span>70%</span>
                </div>
                <div className="progress mt-2 mb-4">
                  <div
                    className="progress-bar progress-bar bg-success"
                    role="progressbar"
                    style={{ width: "75%" }}
                    aria-valuenow={75}
                    aria-valuemin={0}
                    aria-valuemax={100}
                  />
                </div>
                <ul>
                  <li className="d-flex align-items-center justify-content-between">
                    <div className="d-flex align-items-center w-85">
                      <ImageWithBasePath
                        src="assets/img/icons/folder.svg"
                        alt="Folder"
                        className="me-2"
                      />
                      <div className="flex-fill">
                        <h6>
                          <Link to="#">
                            latest-version.zip
                            <i
                              data-feather="check-circle"
                              className="ms-2 feather-16"
                            />
                          </Link>
                        </h6>
                        <span>616 MB</span>
                      </div>
                    </div>
                    <Link to="#" className="text-danger text-right">
                      <i data-feather="trash-2" className="feather-16" />
                    </Link>
                  </li>
                  <li className="d-flex align-items-center justify-content-between">
                    <div className="d-flex align-items-center w-85">
                      <ImageWithBasePath
                        src="assets/img/icons/xls.svg"
                        alt="Folder"
                        className="me-2"
                      />
                      <div className="flex-fill">
                        <h6>
                          <Link to="#">Update work history.xls</Link>
                        </h6>
                        <span>616 MB</span>
                        <div className="progress mt-2">
                          <div
                            className="progress-bar bg-danger"
                            role="progressbar"
                            style={{ width: "75%" }}
                            aria-valuenow={75}
                            aria-valuemin={0}
                            aria-valuemax={100}
                          />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex align-items-center">
                      <Link
                        to="#"
                        className="text-danger me-2 d-flex align-items-center"
                      >
                        <i data-feather="trash-2" className="feather-16" />
                      </Link>
                      <Link
                        to="#"
                        className="text-default d-flex align-items-center"
                      >
                        <i data-feather="pause-circle" className="feather-16" />
                      </Link>
                    </div>
                  </li>
                  <li className="d-flex align-items-center justify-content-between">
                    <div className="d-flex align-items-center w-85">
                      <ImageWithBasePath
                        src="assets/img/icons/zip.svg"
                        alt="Folder"
                        className="me-2"
                      />
                      <div className="flex-fill">
                        <h6>
                          <Link to="#">Updated Project.zip</Link>
                        </h6>
                        <span>616 MB</span>
                        <div className="progress mt-2">
                          <div
                            className="progress-bar"
                            role="progressbar"
                            aria-valuenow={0}
                            aria-valuemin={0}
                            aria-valuemax={100}
                          />
                        </div>
                      </div>
                    </div>
                    <div className="d-flex align-items-center">
                      <Link
                        to="#"
                        className="text-danger me-2 d-flex align-items-center"
                      >
                        <i data-feather="trash-2" className="feather-16" />
                      </Link>
                      <Link
                        to="#"
                        className="text-default d-flex align-items-center"
                      >
                        <i data-feather="play-circle" className="feather-16" />
                      </Link>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        {/* /Upload File */}
        {/* Upload Folder */}
        <div
          className="modal fade modal-default file-manager-modal upload-modal custom-modal"
          id="upload-folder"
          aria-labelledby="upload-folder"
        >
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header p-4">
                <h5>Upload File</h5>
                <button
                  className="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x"></i>
                </button>
              </div>
              <div className="modal-body p-4">
                <div className="drag-drop text-center mb-4">
                  <div className="upload">
                    <Link to="#">
                      <ImageWithBasePath
                        src="assets/img/icons/drag-drop.svg"
                        alt=""
                      />
                    </Link>
                    <p>
                      Drag and drop a <Link to="#">file to upload</Link>
                    </p>
                  </div>
                  <input type="file" multiple />
                </div>
                <div className="d-flex align-items-center justify-content-between">
                  <p>3 of 3 files Uploaded</p>
                  <span>100%</span>
                </div>
                <div className="progress mt-2 mb-4">
                  <div
                    className="progress-bar progress-bar bg-success"
                    role="progressbar"
                    style={{ width: "100%" }}
                    aria-valuenow={100}
                    aria-valuemin={0}
                    aria-valuemax={100}
                  />
                </div>
                <ul>
                  <li className="d-flex align-items-center justify-content-between">
                    <div className="d-flex align-items-center w-85">
                      <ImageWithBasePath
                        src="assets/img/icons/folder.svg"
                        alt="Folder"
                        className="me-2"
                      />
                      <div className="flex-fill">
                        <h6>
                          <Link to="#">
                            latest-version
                            <i
                              data-feather="check-circle"
                              className="ms-2 feather-16"
                            />
                          </Link>
                        </h6>
                        <span>616 MB</span>
                      </div>
                    </div>
                    <Link to="#" className="text-danger text-right">
                      <i data-feather="trash-2" className="feather-16" />
                    </Link>
                  </li>
                  <li className="d-flex align-items-center justify-content-between">
                    <div className="d-flex align-items-center w-85">
                      <ImageWithBasePath
                        src="assets/img/icons/xls.svg"
                        alt="Folder"
                        className="me-2"
                      />
                      <div className="flex-fill">
                        <h6>
                          <Link to="#">
                            Update work history.xls
                            <i data-feather="trash-2" className="feather-16" />
                          </Link>
                        </h6>
                        <span>16 MB</span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center">
                      <Link to="#" className="text-danger me-2">
                        <i data-feather="trash-2" className="feather-16" />
                      </Link>
                      <Link to="#" className="text-default">
                        <i data-feather="pause-circle" className="feather-16" />
                      </Link>
                    </div>
                  </li>
                  <li className="d-flex align-items-center justify-content-between">
                    <div className="d-flex align-items-center w-85">
                      <ImageWithBasePath
                        src="assets/img/icons/zip.svg"
                        alt="Folder"
                        className="me-2"
                      />
                      <div className="flex-fill">
                        <h6>
                          <Link to="#">
                            updated project.zip
                            <i data-feather="trash-2" className="feather-16" />
                          </Link>
                        </h6>
                        <span>14 MB</span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center">
                      <Link to="#" className="text-danger me-2">
                        <i data-feather="trash-2" className="feather-16" />
                      </Link>
                      <Link to="#" className="text-default">
                        <i data-feather="play-circle" className="feather-16" />
                      </Link>
                    </div>
                  </li>
                </ul>
              </div>
              <div className="modal-footer d-sm-flex justify-content-end">
                <button
                  type="button"
                  className="btn btn-secondary"
                  data-bs-dismiss="modal"
                >
                  Clear
                </button>
                <button type="button" className="btn btn-primary">
                  Upload
                </button>
              </div>
            </div>
          </div>
        </div>
        {/* /Upload Folder */}
        {/* Upload Folder */}
        <div
          className="modal fade modal-default file-manager-modal upload-message"
          id="upload-message"
          aria-labelledby="upload-message"
        >
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header p-4">
                <h5>Upload File</h5>
                <button
                  className="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x"></i>
                </button>
              </div>
              <div className="modal-body p-4">
                <div className="d-flex align-items-center justify-content-between">
                  <p>3 of 3 files Uploaded</p>
                  <span>100%</span>
                </div>
                <div className="progress mt-2 mb-4">
                  <div
                    className="progress-bar progress-bar bg-success"
                    role="progressbar"
                    style={{ width: "100%" }}
                    aria-valuenow={100}
                    aria-valuemin={0}
                    aria-valuemax={100}
                  />
                </div>
              </div>
              <div className="modal-footer d-sm-flex justify-content-end">
                <button
                  type="button"
                  className="btn btn-secondary"
                  data-bs-dismiss="modal"
                >
                  Clear
                </button>
                <button type="button" className="btn btn-primary">
                  Upload
                </button>
              </div>
            </div>
          </div>
        </div>
        {/* /Upload Folder */}
        {/* Create Folder */}
        <div
          className="modal fade modal-default file-manager-modal"
          id="create-folder"
          aria-labelledby="create-folder"
        >
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header p-4">
                <h5>Create Folder</h5>
                <button
                  className="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x"></i>
                </button>
              </div>
              <div className="modal-body p-4">
                <div className="row">
                  <div className="col-12">
                    <label className="form-label">Folder Name</label>
                    <input type="text" className="form-control" />
                  </div>
                </div>
                <div className="modal-footer d-sm-flex justify-content-end">
                  <button
                    type="button"
                    className="btn btn-secondary me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </button>
                  <button type="button" className="btn btn-primary">
                    Create Folder
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        {/* /Create Folder */}
      </div>
    </>
  );
};

export default FileModal;
