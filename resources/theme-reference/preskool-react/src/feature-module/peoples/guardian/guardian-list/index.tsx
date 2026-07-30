import  { useRef, useState } from "react";
import ImageWithBasePath from "../../../../core/common/imageWithBasePath";
import PredefinedDateRanges from "../../../../core/common/datePicker";
import { Link } from "react-router-dom";
import { all_routes } from "../../../router/all_routes";
import {
    allClass,
  names,
  parent,
  status,
} from "../../../../core/common/selectoption/selectoption";
import CommonSelect from "../../../../core/common/commonSelect";
import { Modal } from "react-bootstrap";
import GuardianModal from "../guardianModal";
import type { TableData } from "../../../../core/data/interface";
import Table from "../../../../core/common/dataTable/index";
import { guardianListData } from "../../../../core/data/json/guardianList";
import TooltipOption from "../../../../core/common/tooltipOption";

const GuardianList = () => {
  const [show, setShow] = useState(false);
  const routes = all_routes;
  const dropdownMenuRef = useRef<HTMLDivElement | null>(null);

  const handleApplyClick = () => {
    if (dropdownMenuRef.current) {
      dropdownMenuRef.current.classList.remove("show");
    }
  };
  const handleClose = () => {
    setShow(false);
  };
  const data = guardianListData;
  const columns = [
    {
      title: "ID",
      dataIndex: "id",
      render: (text: string) => (
        <Link to="#" onClick={() => setShow(true)} className="link-primary">
          {text}
        </Link>
      ),
      sorter: (a: TableData, b: TableData) => a.id.length - b.id.length,
    },
    {
      title: "Guardian Name",
      dataIndex: "name",
      render: (text: string, record: any) => (
        <div className="d-flex align-items-center">
          <Link
            to="#"
            className="avatar avatar-md"
            onClick={() => setShow(true)}
          >
            <ImageWithBasePath
              src={record.GuardianImage}
              className="img-fluid rounded-circle"
              alt="img"
            />
          </Link>
          <div className="ms-2">
            <p className="text-dark mb-0">
              <Link to="#" onClick={() => setShow(true)}>
                {text}
              </Link>
            </p>
            <span className="fs-12">{record.Addedon}</span>
          </div>
        </div>
      ),
      sorter: (a: TableData, b: TableData) => a.name.length - b.name.length,
    },
    {
      title: "Child",
      dataIndex: "Child",
      render: (text: string, record: any) => (
        <div className="d-flex align-items-center">
          <Link to={routes.studentDetail} className="avatar avatar-md">
            <ImageWithBasePath
              src={record.ChildImage}
              className="img-fluid rounded-circle"
              alt="img"
            />
          </Link>
          <div className="ms-2">
            <p className="text-dark mb-0">
              <Link to="#">{text}</Link>
            </p>
            <span className="fs-12">{record.class}</span>
          </div>
        </div>
      ),
      sorter: (a: TableData, b: TableData) => a.Child.length - b.Child.length,
    },
    {
      title: "Phone",
      dataIndex: "phone",
      sorter: (a: TableData, b: TableData) => a.phone.length - b.phone.length,
    },
    {
      title: "Email",
      dataIndex: "email",
      sorter: (a: TableData, b: TableData) => a.email.length - b.email.length,
    },
    {
      title: "Action",
      dataIndex: "action",
      render: () => (
        <>
          <div className="d-flex align-items-center">
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
                  <Link
                    className="dropdown-item rounded-1"
                    to="#"
                    onClick={() => setShow(true)}
                  >
                    <i className="ti ti-menu me-2" />
                    View Guardian
                  </Link>
                </li>
                <li>
                  <Link
                    className="dropdown-item rounded-1"
                    to="#"
                    data-bs-toggle="modal"
                    data-bs-target="#edit_guardian"
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
                    data-bs-target="#delete-modal"
                  >
                    <i className="ti ti-trash-x me-2" />
                    Delete
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </>
      ),
    },
  ];
  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content content-two">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Guardian</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Peoples</li>
                  <li className="breadcrumb-item active" aria-current="page">
                  Guardian  
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
                  data-bs-target="#add_guardian"
                >
                  <i className="ti ti-square-rounded-plus me-2" />
                  Add Guardian
                </Link>
              </div>
            </div>
          </div>
          {/* /Page Header */}
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
              <h4 className="mb-3">Guardian List</h4>
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
                  <div
                    className="dropdown-menu drop-width"
                    ref={dropdownMenuRef}
                  >
                    <form>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <h4>Filter</h4>
                      </div>
                      <div className="p-3 pb-0 border-bottom">
                        <div className="row">
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Guardian</label>
                              <CommonSelect
                                className="select"
                                options={parent}
                                defaultValue={parent[0]}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Child</label>
                              <CommonSelect
                                className="select"
                                options={names}
                                defaultValue={names[0]}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">Class</label>
                            <CommonSelect
                              className="select"
                              options={allClass}
                              defaultValue={allClass[0]}
                            />
                          </div>
                        </div>
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">Status</label>
                            <CommonSelect
                              className="select"
                              options={status}
                              defaultValue={status[0]}
                            />
                          </div>
                        </div>
                        </div>
                      </div>
                      <div className="p-3 d-flex align-items-center justify-content-end">
                        <Link to="#" className="btn btn-light me-3">
                          Reset
                        </Link>
                        <Link
                          to="#"
                          className="btn btn-primary"
                          onClick={handleApplyClick}
                        >
                          Apply
                        </Link>
                      </div>
                    </form>
                  </div>
                </div>
                <div className="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
                  <Link
                    to={routes.guardiansList}
                    className="active btn btn-icon btn-sm me-1 primary-hover"
                  >
                    <i className="ti ti-list-tree" />
                  </Link>
                  <Link
                    to={routes.guardiansGrid}
                    className="btn btn-icon btn-sm bg-light primary-hover"
                  >
                    <i className="ti ti-grid-dots" />
                  </Link>
                </div>
                <div className="dropdown mb-3">
                  <Link
                    to="#"
                    className="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown"
                  >
                    <i className="ti ti-sort-ascending-2 me-2" />
                    Sort by A-Z{" "}
                  </Link>
                  <ul className="dropdown-menu p-3">
                    <li>
                      <Link to="#" className="dropdown-item rounded-1 active">
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
              <Table dataSource={data} columns={columns} Selection={true} />
              
              {/* /Student List */}
            </div>
          </div>

        </div>
      </div>
      {/* /Page Wrapper */}
      <GuardianModal />

      <Modal show={show} onHide={handleClose} centered size="lg">
        <div className="modal-header">
          <h4 className="modal-title">View Details</h4>
          <button
            type="button"
            className="btn-close custom-btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
            onClick={handleClose}
          >
            <i className="ti ti-x" />
          </button>
        </div>
        <div className="modal-body mb-0">
          <div className="parent-wrap">
            <div className="row align-items-center">
              <div className="col-lg-6">
                <div className="d-flex align-items-center mb-3">
                  <span className="avatar avatar-xl me-2">
                    <ImageWithBasePath
                      src="assets/img/guardians/guardian-01.jpg"
                      alt="img"
                    />
                  </span>
                  <div className="parent-name">
                    <h5 className="mb-1">William</h5>
                    <p>Added on 25 Mar 2024</p>
                  </div>
                </div>
              </div>
              <div className="col-lg-6">
                <ul className="d-flex align-items-center">
                  <li className="mb-3 me-5">
                    <p className="mb-1">Email</p>
                    <h6 className="fw-normal">wil@example.com</h6>
                  </li>
                  <li className="mb-3">
                    <p className="mb-1">Phone</p>
                    <h6 className="fw-normal">+1 82392 37359</h6>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <h5 className="mb-3">Children Details</h5>
          <div className="border rounded p-4 pb-1 mb-3">
            <div className="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
              <span className="link-primary mb-2">AD9892434</span>
              <span className="badge badge-soft-success badge-md mb-2">
                <i className="ti ti-circle-filled me-2" />
                Active
              </span>
            </div>
            <div className="d-flex align-items-center justify-content-between flex-wrap">
              <div className="d-flex align-items-center mb-3">
                <Link to={routes.studentDetail} className="avatar">
                  <ImageWithBasePath
                    src="assets/img/students/student-01.jpg"
                    className="img-fluid rounded-circle"
                    alt="img"
                  />
                </Link>
                <div className="ms-2">
                  <p className="mb-0">
                    <Link to={routes.studentDetail}>Janet</Link>
                  </p>
                  <span>III, A</span>
                </div>
              </div>
              <ul className="d-flex align-items-center flex-wrap">
                <li className="mb-3 me-4">
                  <p className="mb-1">Roll No</p>
                  <h6 className="fw-normal">35010</h6>
                </li>
                <li className="mb-3 me-4">
                  <p className="mb-1">Gender</p>
                  <h6 className="fw-normal">Female</h6>
                </li>
                <li className="mb-3">
                  <p className="mb-1">Date of Joined</p>
                  <h6 className="fw-normal">22 Mar 2018</h6>
                </li>
              </ul>
              <div className="d-flex align-items-center">
                <Link to="#" className="btn btn-light mb-3 me-3">
                  Add Fees
                </Link>
                <Link
                  to={routes.studentDetail}
                  className="btn btn-primary mb-3"
                >
                  View Details
                </Link>
              </div>
            </div>
          </div>
          <div className="border rounded p-4 pb-1 mb-3">
            <div className="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
              <span className="link-primary mb-2">AD9892433</span>
              <span className="badge badge-soft-success badge-md mb-2">
                <i className="ti ti-circle-filled me-2" />
                Active
              </span>
            </div>
            <div className="d-flex align-items-center justify-content-between flex-wrap">
              <div className="d-flex align-items-center mb-3">
                <Link to={routes.studentDetail} className="avatar">
                  <ImageWithBasePath
                    src="assets/img/students/student-02.jpg"
                    className="img-fluid rounded-circle"
                    alt="img"
                  />
                </Link>
                <div className="ms-2">
                  <p className="mb-0">
                    <Link to={routes.studentDetail}>Joann</Link>
                  </p>
                  <span>IV, B</span>
                </div>
              </div>
              <ul className="d-flex align-items-center flex-wrap">
                <li className="mb-3 me-4">
                  <p className="mb-1">Roll No</p>
                  <h6 className="fw-normal">35012</h6>
                </li>
                <li className="mb-3 me-4">
                  <p className="mb-1">Gender</p>
                  <h6 className="fw-normal">Male</h6>
                </li>
                <li className="mb-3">
                  <p className="mb-1">Date of Joined</p>
                  <h6 className="fw-normal">18 Mar 2018</h6>
                </li>
              </ul>
              <div className="d-flex align-items-center">
                <Link to="#" className="btn btn-light mb-3 me-3">
                  Add Fees
                </Link>
                <Link
                  to={routes.studentDetail}
                  className="btn btn-primary mb-3"
                >
                  View Details
                </Link>
              </div>
            </div>
          </div>
        </div>
      </Modal>
    </>
  );
};

export default GuardianList;
