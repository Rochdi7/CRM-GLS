import  { useState } from "react";
import { Link } from "react-router-dom";
import NotesContent from "./notesContent";
import NotesModal from "./notesModal";
import { all_routes } from "../router/all_routes";
import TooltipOption from "../../core/common/tooltipOption";

const Notes = () => {
  const [isOpen, setOpen] = useState(false);
  const routes = all_routes;
  return (
    <>
      <div className="page-wrapper">
        <div className="content pb-4">
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3 pb-3 border-bottom position-relative">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Notes</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Application</li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Notes
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
                  data-bs-target="#note-units"
                >
                  <i className="ti ti-square-rounded-plus me-2" />
                  Add Notes
                </Link>
              </div>
            </div>
            <Link
              id="toggle_btn2"
              className="notes-tog position-absolute start-0 avatar avatar-sm rounded-circle bg-primary text-white"
              to="#"
              onClick={() => setOpen(!isOpen)}
            >
              <i className="fas fa-chevron-left" />
            </Link>
          </div>
          <div className="row">
            <div
              className={`col-xl-3 col-md-12 sidebars-right theiaStickySidebar section-bulk-widget  ${
                isOpen && "section-notes-dashboard"
              }`}
            >
              <div className="stickybar">
                <div className="border rounded-3 mt-4 bg-white p-3">
                  <div className="mb-3 pb-3 border-bottom">
                    <h4 className="d-flex align-items-center">
                      <i className="ti ti-file-text me-2" />
                      Todo List
                    </h4>
                  </div>
                  <div className="border-bottom pb-3 ">
                    <div
                      className="nav flex-column nav-pills"
                      id="v-pills-tab"
                      role="tablist"
                      aria-orientation="vertical"
                    >
                      <button
                        className="d-flex text-start align-items-center fw-semibold fs-15 nav-link active mb-1"
                        id="v-pills-profile-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#v-pills-profile"
                        type="button"
                        role="tab"
                        aria-controls="v-pills-profile"
                        aria-selected="true"
                      >
                        <i className="ti ti-inbox me-2" />
                        All Notes<span className="ms-2">1</span>
                      </button>
                      <button
                        className="d-flex text-start align-items-center fw-semibold fs-15 nav-link mb-1"
                        id="v-pills-messages-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#v-pills-messages"
                        type="button"
                        role="tab"
                        aria-controls="v-pills-messages"
                        aria-selected="false"
                      >
                        <i className="ti ti-star me-2" />
                        Important
                      </button>
                      <button
                        className="d-flex text-start align-items-center fw-semibold fs-15 nav-link mb-0"
                        id="v-pills-settings-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#v-pills-settings"
                        type="button"
                        role="tab"
                        aria-controls="v-pills-settings"
                        aria-selected="false"
                      >
                        <i className="ti ti-trash me-2" />
                        Trash
                      </button>
                    </div>
                  </div>
                  <div className="mt-3">
                    <div className="border-bottom px-2 pb-3 mb-3">
                      <h5 className="mb-2">Tags</h5>
                      <div className="d-flex flex-column mt-2">
                        <Link to="#" className="text-info mb-2">
                          <span className="text-info me-2">
                            <i className="fas fa-square square-rotate fs-10" />
                          </span>
                          Pending
                        </Link>
                        <Link to="#" className="text-danger mb-2">
                          <span className="text-danger me-2">
                            <i className="fas fa-square square-rotate fs-10" />
                          </span>
                          Onhold
                        </Link>
                        <Link to="#" className="text-warning mb-2">
                          <span className="text-warning me-2">
                            <i className="fas fa-square square-rotate fs-10" />
                          </span>
                          Inprogress
                        </Link>
                        <Link to="#" className="text-success">
                          <span className="text-success me-2">
                            <i className="fas fa-square square-rotate fs-10" />
                          </span>
                          Done
                        </Link>
                      </div>
                    </div>
                    <div className="px-2">
                      <h5 className="mb-2">Priority</h5>
                      <div className="d-flex flex-column mt-2">
                        <Link to="#" className="text-warning mb-2">
                          <span className="text-warning me-2">
                            <i className="fas fa-square square-rotate fs-10" />
                          </span>
                          Medium
                        </Link>
                        <Link to="#" className="text-success mb-2">
                          <span className="text-success me-2">
                            <i className="fas fa-square square-rotate fs-10" />
                          </span>
                          High
                        </Link>
                        <Link to="#" className="text-danger">
                          <span className="text-danger me-2">
                            <i className="fas fa-square square-rotate fs-10" />
                          </span>
                          Low
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div
              className={`col-xl-9 budget-role-notes  ${
                isOpen && "budgeted-role-notes"
              }`}
            >
              <NotesContent />
            </div>
          </div>
        </div>
      </div>
      <NotesModal />
    </>
  );
};

export default Notes;
