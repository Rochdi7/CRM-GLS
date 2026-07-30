import { useState } from "react";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import CommonSelect from "../../../core/common/commonSelect";

import { OverlayTrigger, Tooltip } from "react-bootstrap";
import TagInput from "../../../core/common/Taginput";

const Localization = () => {
  const routes = all_routes;
  const [owner, setOwner] = useState<string[]>(["JPG", "GIF", "PNG"]);
  const handleTagsChange = (newTags: string[]) => {
    setOwner(newTags);
  };
  const languageOptions = [
    { value: "english", label: "English" },
    { value: "spanish", label: "Spanish" },
  ];
  const timezoneOptions = [
    { value: "utc5:30", label: "UTC 5:30" },
    { value: "utc+11:00", label: "(UTC+11:00) INR" },
  ];
  const dateOptions = [
    { value: "01 Jan 2024", label: "01 Jan 2024" },
    { value: "Jan 01 2024", label: "Jan 01 2024" },
    { value: "2024 Jan 01", label: "2024 Jan 01" },
  ];
  const timeFormatOptions = [
    { value: "12-hours", label: "12 Hours" },
    { value: "24-hours", label: "24 Hours" },
  ];
  const yearOptions = [
    { value: "2024", label: "2024" },
    { value: "2023", label: "2023" },
  ];
  const monthOptions = [
    { value: "January", label: "January" },
    { value: "February", label: "February" },
    { value: "March", label: "March" },
  ];
  const country = [
    { value: "India", label: "India" },
    { value: "United States Of America", label: "United States Of America" },
  ];
  const symbols = [
    { value: "$", label: "$" },
    { value: "€", label: "€" },
    { value: "€", label: "€" },
  ];
  const symbolsandvalue = [
    { value: "$100", label: "$100" },
    { value: "$400", label: "$400" },
  ];
  const dot = [
    { value: ".", label: "." },
    { value: ".", label: "." },
  ];
  const comma = [
    { value: ",", label: "," },
    { value: ",", label: "," },
  ];
  const permissionforcountry = [
    { value: "Allow All Countries", label: "Allow All Countries" },
    { value: "Deny All Countries", label: "Deny All Countries" },
  ];
  const mbSize = [
    { value: "5000 MB", label: "5000 MB" },
    { value: "2000 MB", label: "2000 MB" },
  ];

  return (
    <div>
      <div className="page-wrapper">
        <div className="content">
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
                <Link
                  to={routes.localization}
                  className="d-block rounded p-2 active"
                >
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
                <Link to={routes.language} className="d-block rounded p-2">
                  Language
                </Link>
              </div>
            </div>
            <div className="col-xxl-10 col-xl-9">
              <div className="border-start ps-3">
                <form>
                  <div className="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div className="mb-3">
                      <h5 className="mb-1">Localization</h5>
                      <p>Collection of settings for user environment</p>
                    </div>
                    <div className="mb-3">
                      <button className="btn btn-light me-2" type="button">
                        Cancel
                      </button>
                      <button className="btn btn-primary" type="submit">
                        Save
                      </button>
                    </div>
                  </div>
                  <div className="d-md-flex d-block">
                    <div className="flex-fill">
                      <div className="card">
                        <div className="card-header p-3">
                          <h5>Basic Information</h5>
                        </div>
                        <div className="card-body p-3 pb-0">
                          <div className="d-block d-xl-flex align-items-end">
                            <div className="mb-3 flex-fill me-xl-3 me-0">
                              <label className="form-label">Language</label>
                              <CommonSelect
                                className="select"
                                options={languageOptions}
                              />
                            </div>
                            <div className="mb-3 flex-fill">
                              <div className="d-flex align-items-center justify-content-between">
                                <div className="status-title">
                                  <h5>Language Switcher</h5>
                                  <p>To display in all pages</p>
                                </div>
                                <div className="form-check form-switch">
                                  <input
                                    className="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="switch-sm"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">Timezone</label>
                                <CommonSelect
                                  className="select"
                                  options={timezoneOptions}
                                />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0 pe-xl-0">
                              <div className="mb-3 flex-fill  me-xl-3 me-0">
                                <label className="form-label">
                                  Date Format
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={dateOptions}
                                />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">
                                  Time Format
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={timeFormatOptions}
                                />
                              </div>
                            </div>
                          </div>
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">
                                  Financial Year
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={yearOptions}
                                />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">
                                  Starting Month
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={monthOptions}
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="card">
                        <div className="card-header p-3">
                          <h5>Currency Settings</h5>
                        </div>
                        <div className="card-body p-3 pb-0">
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">Currency</label>
                                <CommonSelect
                                  className="select"
                                  options={country}
                                />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0 pe-xl-0">
                              <div className="mb-3 flex-fill  me-xl-3 me-0">
                                <label className="form-label">
                                  Currency Position
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={symbols}
                                />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">
                                  Time Format
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={symbolsandvalue}
                                />
                              </div>
                            </div>
                          </div>
                          <div className="row">
                            <div className="col-xl-4 pe-xl-0">
                              <div className="mb-3 flex-fill me-xl-3 me-0">
                                <label className="form-label">
                                  Decimal Seperator
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={dot}
                                />
                              </div>
                            </div>
                            <div className="col-xl-4 ps-xl-0">
                              <div className="mb-3 flex-fill">
                                <label className="form-label">
                                  Thousand Seperator
                                </label>
                                <CommonSelect
                                  className="select"
                                  options={comma}
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="card">
                        <div className="card-header p-3">
                          <h5>Country Settings</h5>
                        </div>
                        <div className="card-body p-3 pb-0">
                          <div className="row align-items-center">
                            <div className="col-xl-8">
                              <div className="mb-3 flex-fill">
                                <h6>Country Restriction</h6>
                                <p>Select restrcted countries</p>
                              </div>
                            </div>
                            <div className="col-xl-4">
                              <div className="mb-3 flex-fill">
                                <CommonSelect
                                  className="select"
                                  options={permissionforcountry}
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="card">
                        <div className="card-header p-3">
                          <h5>File Settings</h5>
                        </div>
                        <div className="card-body p-3 pb-0">
                          <div className="row align-items-center mb-2">
                            <div className="col-xl-8">
                              <div className="mb-3 flex-fill">
                                <h6>Allowed Files</h6>
                                <p>Select allowed files</p>
                              </div>
                            </div>
                            <div className="col-xl-4">
                              <div className="mb-3 flex-fill">
                                <TagInput
                                  initialTags={owner}
                                  onTagsChange={handleTagsChange}
                                />
                              </div>
                            </div>
                          </div>
                          <div className="row">
                            <div className="col-xl-8">
                              <div className="mb-3 flex-fill">
                                <h6>Maximum File Size</h6>
                                <p>Select max size of files</p>
                              </div>
                            </div>
                            <div className="col-xl-4">
                              <div className="mb-3 flex-fill">
                                <CommonSelect
                                  className="select"
                                  options={mbSize}
                                />
                              </div>
                            </div>
                          </div>
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
    </div>
  );
};

export default Localization;
