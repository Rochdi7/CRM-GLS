
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import CommonSelect from "../../../core/common/commonSelect";
import Table from "../../../core/common/dataTable/index";
import {
  codeLanguage,
  languageOptions,
} from "../../../core/common/selectoption/selectoption";
import type { TableData } from "../../../core/data/interface";
import { languageSet } from "../../../core/data/json/language";
import PredefinedDateRanges from "../../../core/common/datePicker";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const Languagesettings = () => {
  const routes = all_routes;
  const data = languageSet;
  const columns = [
    {
      title: "Language",
      dataIndex: "language",
      sorter: (a: TableData, b: TableData) =>
        a.language.length - b.language.length,
    },
    {
      title: "Code",
      dataIndex: "code",
      sorter: (a: TableData, b: TableData) => a.code.length - b.code.length,
    },
    {
      title: "RTL",
      dataIndex: "rtl",
      render: () => (
        <>
          <div className="form-check form-switch">
            <input
              className="form-check-input"
              type="checkbox"
              role="switch"
              id="switch-sm"
            />
          </div>
        </>
      ),

      sorter: (a: TableData, b: TableData) => a.rtl.length - b.rtl.length,
    },
    {
      title: "Status",
      dataIndex: "status",
      render: () => (
        <>
          <span className="badge badge-soft-success d-inline-flex align-items-center">
            <i className="ti ti-circle-filled fs-5 me-1"></i>Active
          </span>
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.status.length - b.status.length,
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
                <Link className="dropdown-item rounded-1" to="#">
                  <i className="ti ti-language me-2" />
                  Make as Default
                </Link>
              </li>
              <li>
                <Link
                  className="dropdown-item rounded-1"
                  to="#"
                  data-bs-toggle="modal"
                  data-bs-target="#edit_language"
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
                  data-bs-target="#language_setup"
                >
                  <i className="ti ti-settings me-2" />
                  Set up
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
      <div className="page-wrapper">
        <div className="content bg-white">
          <div className="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Website Settings</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to="index">Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Settings</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Website Settings
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
                  to={routes.companySettings}
                  className="d-block rounded p-2"
                >
                  Company Settings
                </Link>
                <Link to={routes.localization} className="d-block rounded p-2">
                  Localization
                </Link>
                <Link to={routes.prefixes} className="d-block rounded p-2">
                  Prefixes
                </Link>
                <Link to={routes.preference} className="d-block rounded p-2">
                  Preferences
                </Link>
                <Link
                  to={routes.socialAuthentication}
                  className="d-block rounded p-2"
                >
                  Social Authentication
                </Link>
                <Link
                  to={routes.language}
                  className="d-block rounded p-2 active"
                >
                  Language
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="border-start ps-3">
                <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                  <div className="mb-3">
                    <h5 className="mb-1">Language</h5>
                    <p>Personalize your Language settings of your website</p>
                  </div>
                  <div className="mb-3">
                    <Link
                      to="#"
                      className="btn btn-light me-2"
                      data-bs-toggle="modal"
                      data-bs-target="#import_file"
                    >
                      <i className="ti ti-download me-2" />
                      Import
                    </Link>
                    <Link
                      to="#"
                      className="btn btn-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#add_language"
                    >
                      <i className="ti ti-square-rounded-plus-filled me-2" />
                      Add Language
                    </Link>
                  </div>
                </div>
                {/* Students List */}
                <div className="card">
                  <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    <h4 className="mb-3">Language</h4>
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
                                <div className="col-md-12">
                                  <div className="mb-3">
                                    <label className="form-label">
                                      Language
                                    </label>
                                    <CommonSelect
                                      className="select"
                                      options={languageOptions}
                                    />
                                  </div>
                                </div>
                                <div className="col-md-12">
                                  <div className="mb-3">
                                    <label className="form-label">Code</label>
                                    <CommonSelect
                                      className="select"
                                      options={codeLanguage}
                                    />
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div className="p-3 d-flex align-items-center justify-content-end">
                              <Link to="#" className="btn btn-light me-2">
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
                            <Link to="#" className="dropdown-item rounded-1">
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
                    {/* Student List */}
                    <Table
                      columns={columns}
                      dataSource={data}
                      Selection={true}
                    />
                    {/* /Student List */}
                  </div>
                </div>
                {/* /Students List */}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Languagesettings;
