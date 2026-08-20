import { useState } from "react";
import { Link } from "react-router-dom";
import { all_routes } from "../router/all_routes";
import TooltipOption from "../../core/common/tooltipOption";
import Select from "react-select";
import ImageWithBasePath from "../../core/common/imageWithBasePath";
import Slider from "react-slick";
const FileManager = () => {
  const [isOpen, setOpen] = useState(false);

  const routes = all_routes;
  const optionsBulk = [
    { value: "bulkActions", label: "Bulk Actions" },
    { value: "deleteMarked", label: "Delete Marked" },
    { value: "unmarkAll", label: "Unmark All" },
    { value: "markAll", label: "Mark All" },
  ];
  const optionsRecent = [
    { value: "recent", label: "Recent" },
    { value: "lastModified", label: "Last Modified" },
    { value: "lastModifiedByMe", label: "Last Modified by me" },
  ];
    const folderSlider = {
    loop: true,
    margin: 15,
    items: 3,
    nav: true,
    dots: false,
    autoplay: false,
    slidesToShow: 3,
    speed: 500,
    responsive: [
      {
        breakpoint: 1400,
        settings: {
          slidesToShow: 3,
        },
      },
      {
        breakpoint: 1300,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 1200,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 800,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 776,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 567,
        settings: {
          slidesToShow: 1,
        },
      },
    ],
  };

  const fileSlider = {
    loop: true,
    margin: 15,
    items: 3,
    nav: true,
    dots: false,
    autoplay: false,
    slidesToShow: 3,
    speed: 500,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 3,
        },
      },
      {
        breakpoint: 800,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 776,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 567,
        settings: {
          slidesToShow: 1,
        },
      },
    ],
  };
  return (
    <div className={`page-wrapper `}>
      <div className="content pb-4">
        <div className="d-md-flex d-block align-items-center justify-content-between mb-3 pb-3 border-bottom position-relative">
          <div className="my-auto mb-2">
            <h3 className="page-title mb-1">File Manager</h3>
            <nav>
              <ol className="breadcrumb mb-0">
                <li className="breadcrumb-item">
                  <Link to={routes.adminDashboard}>Dashboard</Link>
                </li>
                <li className="breadcrumb-item">Application</li>
                <li className="breadcrumb-item active" aria-current="page">
                  File Manager
                </li>
              </ol>
            </nav>
          </div>
          <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <TooltipOption />
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
            className={`col-lg-3 col-md-12 sidebars-right theiaStickySidebar section-bulk-widget  ${
              isOpen && "section-notes-dashboard"
            }`}
          >
            <div className="stickybar">
              <aside className="border bg-white rounded-3 p-4 mb-3 mt-4 custom-sticky">
                <h5 className="d-flex align-items-center border-bottom mb-3 pb-3">
                  <span className="me-2 d-flex align-items-center0">
                    <i className="ti ti-folder fs-20" />
                  </span>
                  Files
                </h5>
                <Link
                  to="#"
                  className="d-flex align-items-center justify-content-center btn btn-primary mb-3 btn-icon w-100"
                >
                  <i className="ti ti-square-rounded-plus me-2" /> New
                </Link>
                <div className="d-flex flex-column active-dark-btn mb-3">
                  <Link
                    to="file-manager"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 active mb-1"
                  >
                    <i className="ti ti-file-text me-2" />
                    My Files
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-star me-2" />
                    Google Drive
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-send me-2" />
                    Dropbox
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-file-alert me-2" />
                    Shared With Me
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-file-analytics me-2" />
                    Document
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-clock me-2" />
                    Recent
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-stars me-2" />
                    Favourites
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-shield me-2" />
                    Archived
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-trash me-2" />
                    Deleted
                  </Link>
                  <Link
                    to="#"
                    className="rounded p-2 d-flex align-items-center fw-medium fs-15 mb-1"
                  >
                    <i className="ti ti-settings me-2" />
                    Settings
                  </Link>
                </div>
                <div className="d-flex align-items-center justify-content-between">
                  <div className="d-flex align-items-center">
                    <h6>Storage</h6>
                  </div>
                  <span>70%</span>
                </div>
                <div className="progress progress-sm my-2">
                  <div
                    className="progress-bar bg-danger rounded"
                    role="progressbar"
                    style={{ width: "75%" }}
                    aria-valuenow={75}
                    aria-valuemin={0}
                    aria-valuemax={100}
                  />
                </div>
                <span>78.5 GB of 1 TB Free Used</span>
              </aside>
            </div>
          </div>

          <div
            className={`col-lg-9 budget-role-notes  ${
              isOpen && "budgeted-role-notes"
            }`}
          >
            <div className="row">
              <div className="col-12">
                <div className="section-bulk-wrap">
                  <div className="bg-white rounded-3 d-flex align-items-center justify-content-between flex-wrap my-4 p-3 pb-0">
                    <div className="d-flex align-items-center mb-3">
                      <div className="me-3">
                        <Select
                          className="select"
                          options={optionsBulk}
                          classNamePrefix="react-select"
                        />
                      </div>
                      <Link to="#" className="btn btn-light">
                        Apply
                      </Link>
                    </div>
                    <div className="form-sort mb-3">
                      <i className="ti ti-filter feather-filter info-img ms-0" />
                      <Select
                        className="select"
                        options={optionsRecent}
                        classNamePrefix="react-select"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Overview */}
            <div className="d-block">
              <h4 className="mb-3">Overview</h4>
              <div className="row g-3">
                <div className="col-sm-6 col-md-3">
                  <div className="mb-3">
                    <Link
                      to="#"
                      className="d-flex align-items-center justify-content-center bg-light-orange p-3 rounded-top"
                    >
                      <span className="d-flex align-items-center justify-content-center p-4">
                        <ImageWithBasePath src="assets/img/icons/folder.svg" alt="Folder" />
                      </span>
                    </Link>
                    <div className="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                      <h5>
                        <Link to="#">Folders</Link>
                      </h5>
                      <span className="text-muted">300 Files</span>
                    </div>
                  </div>
                </div>
                <div className="col-sm-6 col-md-3">
                  <div className="mb-3">
                    <Link
                      to="#"
                      className="d-flex align-items-center justify-content-center bg-light-red p-3 rounded-top"
                    >
                      <span className="d-flex align-items-center justify-content-center p-4">
                        <ImageWithBasePath src="assets/img/icons/pdf-02.svg" alt="Folder" />
                      </span>
                    </Link>
                    <div className="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                      <h6>
                        <Link to="#">PDF</Link>
                      </h6>
                      <span>50 Files</span>
                    </div>
                  </div>
                </div>
                <div className="col-sm-6 col-md-3">
                  <div className="mb-3">
                    <Link
                      to="#"
                      className="d-flex align-items-center justify-content-center bg-light-green p-3 rounded-top"
                    >
                      <span className="d-flex align-items-center justify-content-center p-4">
                        <ImageWithBasePath src="assets/img/icons/image.svg" alt="Folder" />
                      </span>
                    </Link>
                    <div className="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                      <h6>
                        <Link to="#">Images</Link>
                      </h6>
                      <span>240 Files</span>
                    </div>
                  </div>
                </div>
                <div className="col-sm-6 col-md-3">
                  <div className="mb-3">
                    <Link
                      to="#"
                      className="d-flex align-items-center justify-content-center bg-light-red p-3 rounded-top"
                    >
                      <span className="d-flex align-items-center justify-content-center p-4">
                        <ImageWithBasePath src="assets/img/icons/video.svg" alt="Folder" />
                      </span>
                    </Link>
                    <div className="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                      <h6>
                        <Link to="#">Videos</Link>
                      </h6>
                      <span>30 Files</span>
                    </div>
                  </div>
                </div>
                <div className="col-sm-6 col-md-3">
                  <div className="mb-3">
                    <Link
                      to="#"
                      className="d-flex align-items-center justify-content-center bg-light-orange p-3 rounded-top"
                    >
                      <span className="d-flex align-items-center justify-content-center p-4">
                        <ImageWithBasePath src="assets/img/icons/audio.svg" alt="Folder" />
                      </span>
                    </Link>
                    <div className="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                      <h6>
                        <Link to="#">Audios</Link>
                      </h6>
                      <span>100 Files</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Overview */}
            {/* Filemanager Items */}
            <div className="d-block">
              <div className="mb-4 pb-4 border-bottom">
                <div className="d-flex align-items-center mb-3">
                  <h4>Folders</h4>
                  <div className="owl-nav slide-nav6 text-end nav-control ms-3" />
                </div>
                <Slider
            {...folderSlider} className="owl-carousel folders-carousel owl-theme">
                  <div className="p-3 border p-3 bg-white rounded">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/folder.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">Project Details</Link>
                        </h5>
                      </div>
                      <div className="dropdown">
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                          className="dropset"
                        >
                          <i className="fa fa-ellipsis-v" />
                        </Link>
                        <ul className="dropdown-menu">
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Details
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Share
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Copy
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Move
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Download
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Rename
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Archeived
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start my-3">
                      <p className="text-primary mb-0 me-2 pe-1">
                        Project plan
                      </p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        27 MB
                      </span>
                      <span className="d-flex align-items-center fw-semibold">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        208 Files
                      </span>
                    </div>
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="avatar-list-stacked avatar-group-sm">
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 1"
                            data-bs-original-title="Member 1"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-02.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 2"
                            data-bs-original-title="Member 2"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-01.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="fw-semibold mt-2">
                          <Link
                            className="text-success ms-3"
                            to="#"
                          >
                            2 Members
                          </Link>
                        </span>
                      </div>
                      <Link to="#">
                        <i className="ti ti-star fs-16" />
                      </Link>
                    </div>
                  </div>
                  <div className="p-3 border p-3 bg-white rounded">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/folder.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">Website Backup</Link>
                        </h5>
                      </div>
                      <div className="dropdown">
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                          className="dropset"
                        >
                          <i className="fa fa-ellipsis-v" />
                        </Link>
                        <ul className="dropdown-menu">
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Details
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Share
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Copy
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Move
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Download
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Rename
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Archeived
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start my-3">
                      <p className="text-primary mb-0 me-2 pe-1">Important</p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        600 MB
                      </span>
                      <span className="d-flex align-items-center fw-semibold">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        48 Files
                      </span>
                    </div>
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="avatar-list-stacked avatar-group-sm">
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 1"
                            data-bs-original-title="Member 1"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-11.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 2"
                            data-bs-original-title="Member 2"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-12.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 2"
                            data-bs-original-title="Member 3"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-03.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 2"
                            data-bs-original-title="Member 3"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-04.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="fw-semibold mt-2">
                          <Link
                            className="text-success ms-3"
                            to="#"
                          >
                            4 Members
                          </Link>
                        </span>
                      </div>
                      <Link to="#">
                        <i className="ti ti-star fs-16" />
                      </Link>
                    </div>
                  </div>
                  <div className="p-3 border p-3 bg-white rounded">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/folder.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">Number_Proj Photos</Link>
                        </h5>
                      </div>
                      <div className="dropdown">
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                          className="dropset"
                        >
                          <i className="fa fa-ellipsis-v" />
                        </Link>
                        <ul className="dropdown-menu">
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Details
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Share
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Copy
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Move
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Download
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Rename
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Archeived
                            </Link>
                          </li>
                          <li>
                            <Link
                              to="#"
                              className="dropdown-item"
                            >
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start my-3">
                      <p className="text-primary mb-0 me-2 pe-1">
                        Project plan
                      </p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        150 MB
                      </span>
                      <span className="d-flex align-items-center fw-semibold">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        208 Files
                      </span>
                    </div>
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="avatar-list-stacked avatar-group-sm">
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 1"
                            data-bs-original-title="Member 1"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-05.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 2"
                            data-bs-original-title="Member 2"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-10.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="avatar border-0">
                          <Link
                            to="#"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            aria-label="Member 2"
                            data-bs-original-title="Member 3"
                          >
                            <ImageWithBasePath
                              src="assets/img/profiles/avatar-07.jpg"
                              className="rounded-circle"
                              alt="Avatar"
                            />
                          </Link>
                        </span>
                        <span className="fw-semibold mt-2">
                          <Link
                            className="text-success ms-3"
                            to="#"
                          >
                            + 3 Members
                          </Link>
                        </span>
                      </div>
                      <Link to="#">
                        <i className="ti ti-star fs-16" />
                      </Link>
                    </div>
                  </div>
                </Slider>
              </div>
              <div className="mb-4 pb-4 border-bottom">
                <div className="d-flex align-items-center mb-3">
                  <h4>Files</h4>
                  <div className="owl-nav slide-nav7 text-end nav-control ms-3" />
                </div>
                 <Slider
            {...fileSlider} className="owl-carousel files-carousel owl-theme">
                  <div className="border rounded-3 bg-white p-3">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/pdf-02.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">hsa.pdf</Link>
                        </h5>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#">
                          <i className="fa fa-star me-2" />
                        </Link>
                        <div className="dropdown">
                          <Link
                            to="#"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            className="dropset"
                          >
                            <i className="fa fa-ellipsis-v" />
                          </Link>
                          <ul className="dropdown-menu">
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Details
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Share
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Copy
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Move
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Download
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Rename
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Archeived
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Delete
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start mt-3">
                      <p className="text-primary mb-0 me-2">
                        Last edited 14 Jul
                      </p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        150 MB
                      </span>
                    </div>
                  </div>
                  <div className="border rounded-3 bg-white p-3">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/pdf-02.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">Haird.pdf</Link>
                        </h5>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#">
                          <i className="fa fa-star me-2" />
                        </Link>
                        <div className="dropdown">
                          <Link
                            to="#"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            className="dropset"
                          >
                            <i className="fa fa-ellipsis-v" />
                          </Link>
                          <ul className="dropdown-menu">
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Details
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Share
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Copy
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Move
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Download
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Rename
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Archeived
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Delete
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start mt-3">
                      <p className="text-primary mb-0 me-2">
                        Last edited 15 Jul
                      </p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        200 MB
                      </span>
                    </div>
                  </div>
                  <div className="border rounded-3 bg-white p-3">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/xls.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">Estimation.xls</Link>
                        </h5>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#">
                          <i className="fa fa-star me-2" />
                        </Link>
                        <div className="dropdown">
                          <Link
                            to="#"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            className="dropset"
                          >
                            <i className="fa fa-ellipsis-v" />
                          </Link>
                          <ul className="dropdown-menu">
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Details
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Share
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Copy
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Move
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Download
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Rename
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Archeived
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Delete
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start mt-3">
                      <p className="text-primary mb-0 me-2">
                        Last edited 16 Jul
                      </p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        500 KB
                      </span>
                    </div>
                  </div>
                  <div className="border rounded-3 bg-white p-3">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/pdf-02.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">Haird.pdf</Link>
                        </h5>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#">
                          <i className="fa fa-star me-2" />
                        </Link>
                        <div className="dropdown">
                          <Link
                            to="#"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            className="dropset"
                          >
                            <i className="fa fa-ellipsis-v" />
                          </Link>
                          <ul className="dropdown-menu">
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Details
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Share
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Copy
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Move
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Download
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Rename
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Archeived
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Delete
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start mt-3">
                      <p className="text-primary mb-0 me-2">
                        Last edited 15 Jul
                      </p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        200 MB
                      </span>
                    </div>
                  </div>
                  <div className="border rounded-3 bg-white p-3">
                    <div className="d-flex align-items-center justify-content-between">
                      <div className="d-flex align-items-center">
                        <ImageWithBasePath
                          src="assets/img/icons/xls.svg"
                          alt="Folder"
                          className="me-2"
                        />
                        <h5 className="text-nowrap">
                          <Link to="#">Estimation.xls</Link>
                        </h5>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#">
                          <i className="fa fa-star me-2" />
                        </Link>
                        <div className="dropdown">
                          <Link
                            to="#"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            className="dropset"
                          >
                            <i className="fa fa-ellipsis-v" />
                          </Link>
                          <ul className="dropdown-menu">
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Details
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Share
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Copy
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Move
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Download
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Rename
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Archeived
                              </Link>
                            </li>
                            <li>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                Delete
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="d-flex align-items-center justify-content-start mt-3">
                      <p className="text-primary mb-0 me-2">
                        Last edited 16 Jul
                      </p>
                      <span className="d-flex align-items-center fw-semibold me-2">
                        <i className="ti ti-circle-filled fs-5 me-2" />
                        500 KB
                      </span>
                    </div>
                  </div>
                </Slider>
              </div>
              <div className="mb-4 pb-4 border-bottom">
                <div className="d-flex align-items-center mb-3">
                  <h4>Videos</h4>
                  <div className="owl-nav slide-nav8 text-end nav-control ms-3" />
                </div>
            <div className="owl-carousel video-section d-flex gap-3">
            <div className="items">
              <div className="position-relative">
                <video
                  width={100}
                  height={100}
                  className="js-player w-100"
                  crossOrigin=""
                  playsInline={true}
                  poster="/react/assets/img/file-manager/video1.jpg"
                >
                  <source
                    src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4"
                    type="video/mp4"
                  />
                </video>
                <Link to="#" className="position-absolute play-group">
                  <span className="play-btn-video">
                    <i className="ti ti-player-play-filled" />
                  </span>
                  {/* <span className="pause-btn-video">
                    <i className="ti ti-player-pause" />
                  </span> */}
                </Link>
              </div>
              <div className="bg-white p-3 rounded-bottom">
                <div className="d-flex align-items-center justify-content-between">
                  <h6>
                    <Link to="#">Demo_dw</Link>
                  </h6>
                  <div className="d-flex align-items-center">
                    <Link to="#" className="d-flex align-items-center">
                      <i data-feather="star" className="feather-16 me-2" />
                    </Link>
                    <div className="dropdown">
                      <Link
                        to="#"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        className="dropset"
                      >
                        <i className="fa fa-ellipsis-v" />
                      </Link>
                      <ul className="dropdown-menu">
                        <li>
                          <Link to="#" className="dropdown-item">
                            Details
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Share
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Copy
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Move
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Download
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Rename
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Archeived
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Delete
                          </Link>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div className="d-flex align-items-center justify-content-start mt-3">
                  <p className="text-primary mb-0 me-2">Last edited 14 Jul</p>
                  <span className="d-flex align-items-center fw-semibold me-2">
                    <i className="ti ti-circle-filled fs-5 me-2" />
                    150 MB
                  </span>
                </div>
              </div>
            </div>
            <div className="items">
              <div className="position-relative">
                <video
                  className="js-player w-100"
                  crossOrigin=""
                  playsInline={true}
                  poster="/react/assets/img/file-manager/video2.jpg"
                >
                  <source
                    src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4"
                    type="video/mp4"
                  />
                </video>
                <Link to="#" className="position-absolute play-group">
                  <span className="play-btn-video">
                    <i className="ti ti-player-play-filled" />
                  </span>
                  {/* <span className="pause-btn-video">
                    <i className="ti ti-player-pause" />
                  </span> */}
                </Link>
              </div>
              <div className="bg-white p-3 rounded-bottom">
                <div className="d-flex align-items-center justify-content-between">
                  <h6>
                    <Link to="#">Android_bike.mp4</Link>
                  </h6>
                  <div className="d-flex align-items-center">
                    <Link to="#" className="d-flex align-items-center">
                      <i data-feather="star" className="feather-16 me-2" />
                    </Link>
                    <div className="dropdown">
                      <Link
                        to="#"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        className="dropset"
                      >
                        <i className="fa fa-ellipsis-v" />
                      </Link>
                      <ul className="dropdown-menu">
                        <li>
                          <Link to="#" className="dropdown-item">
                            Details
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Share
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Copy
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Move
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Download
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Rename
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Archeived
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Delete
                          </Link>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div className="d-flex align-items-center justify-content-start mt-3">
                  <p className="text-primary mb-0 me-2">Last edited 15 Jul</p>
                  <span className="d-flex align-items-center fw-semibold me-2">
                    <i className="ti ti-circle-filled fs-5 me-2" />
                    50 MB
                  </span>
                </div>
              </div>
            </div>
            <div className="items">
              <div className="position-relative">
                <video
                  className="js-player w-100"
                  crossOrigin=""
                  playsInline={true}
                  poster="/react/assets/img/file-manager/video3.jpg"
                >
                  <source
                    src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4"
                    type="video/mp4"
                  />
                </video>
                <Link to="#" className="position-absolute play-group">
                  <span className="play-btn-video">
                    <i className="ti ti-player-play-filled" />
                  </span>
                  {/* <span className="pause-btn-video">
                    <i className="ti ti-player-pause" />
                  </span> */}
                </Link>
              </div>
              <div className="bg-white rounded-bottom p-3">
                <div className="d-flex align-items-center justify-content-between">
                  <h6>
                    <Link to="#">Demoparticles.mp4</Link>
                  </h6>
                  <div className="d-flex align-items-center">
                    <Link to="#" className="d-flex align-items-center">
                      <i data-feather="star" className="feather-16 me-2" />
                    </Link>
                    <div className="dropdown">
                      <Link
                        to="#"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        className="dropset"
                      >
                        <i className="fa fa-ellipsis-v" />
                      </Link>
                      <ul className="dropdown-menu">
                        <li>
                          <Link to="#" className="dropdown-item">
                            Details
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Share
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Copy
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Move
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Download
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Rename
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Archeived
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item">
                            Delete
                          </Link>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div className="d-flex align-items-center justify-content-start mt-3">
                  <p className="text-primary mb-0 me-2">Last edited 16 Jul</p>
                  <span className="d-flex align-items-center fw-semibold me-2">
                    <i className="ti ti-circle-filled fs-5 me-2" />
                    250 MB
                  </span>
                </div>
              </div>
            </div>
          </div>
              </div>
            </div>
            {/* /Filemanager Items */}
            {/* /Filemanager List */}
            <div className="card">
              <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 className="mb-3">All Files</h4>
                <div className="d-flex align-items-center flex-wrap">
                  <div className="input-icon-start mb-3 me-2 position-relative">
                    <span className="icon-addon">
                      <i className="ti ti-calendar" />
                    </span>
                    <input
                      type="text"
                      className="form-control date-range bookingrange"
                      placeholder="Select"
                      defaultValue="Academic Year : 2024 / 2025"
                    />
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
                        <Link
                          to="#"
                          className="dropdown-item rounded-1"
                        >
                          Ascending
                        </Link>
                      </li>
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1"
                        >
                          Descending
                        </Link>
                      </li>
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1"
                        >
                          Recently Viewed
                        </Link>
                      </li>
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1"
                        >
                          Recently Added
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div className="card-body p-0 py-3">
                {/* Student List */}
                <div className="custom-datatable-filter table-responsive">
                  <table className="table datatable">
                    <thead className="thead-light">
                      <tr>
                        <th className="no-sort">
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                              id="select-all"
                            />
                          </div>
                        </th>
                        <th>Name</th>
                        <th>Last Modified</th>
                        <th>Size</th>
                        <th>Owned Memebr</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                          </div>
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/icons/folder.svg"
                                className="img-fluid"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Digimed</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>22 Apr 2024</td>
                        <td>200 MB</td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/students/student-01.jpg"
                                className="img-fluid rounded-circle"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Janet</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>
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
                                  <i className="ti ti-trash me-2" />
                                  Permanent Delete
                                </Link>
                              </li>
                              <li>
                                <Link className="dropdown-item rounded-1" to="#">
                                  <i className="ti ti-edit-circle me-2" />
                                  Restore File
                                </Link>
                              </li>
                            </ul>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                          </div>
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/icons/xls.svg"
                                className="img-fluid"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Estimation</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>23 Apr 2024</td>
                        <td>100 MB</td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-01.jpg"
                                className="img-fluid rounded-circle"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Henriques</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>
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
                                  <i className="ti ti-trash me-2" />
                                  Permanent Delete
                                </Link>
                              </li>
                              <li>
                                <Link className="dropdown-item rounded-1" to="#">
                                  <i className="ti ti-edit-circle me-2" />
                                  Restore File
                                </Link>
                              </li>
                            </ul>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                          </div>
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/icons/pdf-02.svg"
                                className="img-fluid"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Intro.pdf</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>24 Apr 2024</td>
                        <td>150 MB</td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-02.jpg"
                                className="img-fluid rounded-circle"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Nolan Harris</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>
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
                                  <i className="ti ti-trash me-2" />
                                  Permanent Delete
                                </Link>
                              </li>
                              <li>
                                <Link className="dropdown-item rounded-1" to="#">
                                  <i className="ti ti-edit-circle me-2" />
                                  Restore File
                                </Link>
                              </li>
                            </ul>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                          </div>
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/icons/video.svg"
                                className="img-fluid"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Demoworrking.mp4</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>25 Apr 2024</td>
                        <td>250 MB</td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-03.jpg"
                                className="img-fluid rounded-circle"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">Sarah</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>
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
                                  <i className="ti ti-trash me-2" />
                                  Permanent Delete
                                </Link>
                              </li>
                              <li>
                                <Link className="dropdown-item rounded-1" to="#">
                                  <i className="ti ti-edit-circle me-2" />
                                  Restore File
                                </Link>
                              </li>
                            </ul>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div className="form-check form-check-md">
                            <input
                              className="form-check-input"
                              type="checkbox"
                            />
                          </div>
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/icons/audio.svg"
                                className="img-fluid"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">voice.mp3</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>26 Apr 2024</td>
                        <td>180 MB</td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="avatar avatar-md">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-04.jpg"
                                className="img-fluid rounded-circle"
                                alt="img"
                              />
                            </Link>
                            <div className="ms-2">
                              <p className="text-dark mb-0">
                                <Link to="#">William</Link>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>
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
                                  <i className="ti ti-trash me-2" />
                                  Permanent Delete
                                </Link>
                              </li>
                              <li>
                                <Link className="dropdown-item rounded-1" to="#">
                                  <i className="ti ti-edit-circle me-2" />
                                  Restore File
                                </Link>
                              </li>
                            </ul>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                {/* /Student List */}
              </div>
            </div>
            {/* /Filemanager List */}
          </div>
        </div>
        {/* <FileModal /> */}
      </div>
    </div>
  );
};

export default FileManager;
