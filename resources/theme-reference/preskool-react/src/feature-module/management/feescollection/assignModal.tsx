import { useRef} from 'react'
import { Link } from 'react-router-dom'
import ImageWithBasePath from '../../../core/common/imageWithBasePath'
import { all_routes } from '../../router/all_routes'
import { allClass, cast, feeGroup, feesTypes, gender } from '../../../core/common/selectoption/selectoption'
import CommonSelect from '../../../core/common/commonSelect'
import { Modal } from "react-bootstrap";

const AssignModal = ({addModal,setAddModal,editModal,setEditModal}:any) => {
    const routes = all_routes
    // const [show, setShow] = useState(false);
    const dropdownMenuRef = useRef<HTMLDivElement | null>(null);
    const dropdownMenuRef2 = useRef<HTMLDivElement | null>(null);
    const handleApplyClick = () => {
      if (dropdownMenuRef.current) {
        dropdownMenuRef.current.classList.remove("show");
      }
      if (dropdownMenuRef2.current) {
        dropdownMenuRef2.current.classList.remove("show");
      }
    };
    const handleClose = () => {
      setAddModal(false);
      setEditModal(false);
    };
  return (
    <>
    <>
  {/* Add Fees Assign */}
  <Modal show={addModal} onHide={handleClose} centered size="xl">
  <div className="modal-header">
          <h4 className="modal-title">Assign New Fees</h4>
          <button
            type="button"
            className="btn-close custom-btn-close"
            onClick={()=>setAddModal(false)}
            aria-label="Close"
          >
            <i className="ti ti-x" />
          </button>
        </div>
        <div className="modal-body">
          {/* Table Filter */}
          <div className="table-filter-head">
            <div className="filter-head-left">
              <h5>Search Criteria</h5>
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
                <div className="dropdown-menu drop-width" ref={dropdownMenuRef}>
                  <form >
                    <div className="d-flex align-items-center border-bottom p-3">
                      <h4>Filter</h4>
                    </div>
                    <div className="p-3 pb-0 border-bottom">
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Fees Group</label>
                            <CommonSelect
                                className="select"
                                options={feeGroup}
                                defaultValue={feeGroup[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Fees Type</label>
                            <CommonSelect
                                className="select"
                                options={feesTypes}
                                defaultValue={feesTypes[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Class</label>
                            <CommonSelect
                                className="select"
                                options={allClass}
                                defaultValue={allClass[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Gender</label>
                            <CommonSelect
                                className="select"
                                options={gender}
                                defaultValue={gender[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">
                              Student Category
                            </label>
                            <CommonSelect
                                className="select"
                                options={cast}
                                defaultValue={cast[0]}
                              />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="p-3 d-flex align-items-center justify-content-end">
                      <Link to="#" className="btn btn-light me-3">
                        Reset
                      </Link>
                      <Link to="#" className="btn btn-primary" onClick={handleApplyClick}>
                        Apply
                      </Link>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            {/* /Table Filter */}
          </div>
          {/* /Filter Section */}
          <div className="modal-card-table">
            <div className="modal-table-head">
              <h4>List of Fees type</h4>
            </div>
            <div className="table-responsive custom-table no-datatable_length">
              <table className="table datanew">
                <thead className="thead-light">
                  <tr>
                    <th className="no-sort"></th>
                    <th className="no-sort">Fees Type</th>
                    <th className="no-sort">Fees Type</th>
                    <th className="no-sort">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>Admission Fees</td>
                    <td>Admission-Fees</td>
                    <td>5000</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>Apr-Mar</td>
                    <td>Apr-Mar</td>
                    <td>666</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>Bus Fees</td>
                    <td>Bus Fees</td>
                    <td>400</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>1st Installment Fees</td>
                    <td>1st Installment Fees</td>
                    <td>2645</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>2nd Installment Fees</td>
                    <td>2nd Installment Fees</td>
                    <td>7898</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>3rd Installment Fees</td>
                    <td>3rd Installment Fees</td>
                    <td>4785</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div className="modal-card-table">
            <div className="modal-table-head">
              <h4>Student Details</h4>
            </div>
            <div className="table-responsive custom-table no-datatable_length">
              <table className="table datanew">
                <thead className="thead-light">
                  <tr>
                    <th className="no-sort">
                      <label className="checkboxs">
                        <input type="checkbox" id="select-all2" />
                        <span className="checkmarks" />
                      </label>
                    </th>
                    <th className="no-sort">Admission Number</th>
                    <th className="no-sort">Student</th>
                    <th className="no-sort">Class</th>
                    <th className="no-sort">Section</th>
                    <th className="no-sort">Gender</th>
                    <th className="no-sort">Student Category</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892430
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-01.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Janet</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>II</td>
                    <td>B</td>
                    <td>Female</td>
                    <td>MBC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892429
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-06.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Ralph</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>III</td>
                    <td>B</td>
                    <td>Male</td>
                    <td>BC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892428
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-07.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Julie</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>V</td>
                    <td>A</td>
                    <td>Female</td>
                    <td>BC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892427
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-08.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Ryan</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>VI</td>
                    <td>A</td>
                    <td>Male</td>
                    <td>MBC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892426
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-09.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Susan</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>VIII</td>
                    <td>B</td>
                    <td>Female</td>
                    <td>BC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892425
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-12.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Richard</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>VII</td>
                    <td>B</td>
                    <td>Male</td>
                    <td>MBC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892424
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-11.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Veronica</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>IX</td>
                    <td>A</td>
                    <td>Female</td>
                    <td>BC</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div className="student-pomote-note d-flex mb-0">
            <span className="info-icon">
              <i className="ti ti-info-circle" />
            </span>
            <p> Selected 50 Fees Group, 350 Students</p>
          </div>
        </div>
        <div className="modal-footer">
          <Link to="#" onClick={handleClose} className="btn btn-light me-2" data-bs-dismiss="modal">
            Cancel
          </Link>
          <Link to={routes.feesAssign} onClick={handleClose} className="btn btn-primary">
            Add Fees
          </Link>
        </div>
  </Modal>
  {/* <div className="modal fade" id="add_fees_assign">
    <div className="modal-dialog modal-dialog-centered  modal-xl">
      <div className="modal-content">
        
      </div>
    </div>
  </div> */}
  {/* Add Fees Assign */}
  {/* Edit Fees Assign */}
  <Modal show={editModal} onHide={handleClose} centered size="xl">
  <div className="modal-header">
          <h4 className="modal-title">Edit Fees</h4>
          <button
            type="button"
            className="btn-close custom-btn-close"
            onClick={handleClose}
            aria-label="Close"
          >
            <i className="ti ti-x" />
          </button>
        </div>
        <div className="modal-body">
          {/* Table Filter */}
          <div className="table-filter-head">
            <div className="filter-head-left">
              <h5>Search Criteria</h5>
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
                <div className="dropdown-menu drop-width" ref={dropdownMenuRef2}>
                <form >
                    <div className="d-flex align-items-center border-bottom p-3">
                      <h4>Filter</h4>
                    </div>
                    <div className="p-3 pb-0 border-bottom">
                      <div className="row">
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Fees Group</label>
                            <CommonSelect
                                className="select"
                                options={feeGroup}
                                defaultValue={feeGroup[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Fees Type</label>
                            <CommonSelect
                                className="select"
                                options={feesTypes}
                                defaultValue={feesTypes[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Class</label>
                            <CommonSelect
                                className="select"
                                options={allClass}
                                defaultValue={allClass[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-6">
                          <div className="mb-3">
                            <label className="form-label">Gender</label>
                            <CommonSelect
                                className="select"
                                options={gender}
                                defaultValue={gender[0]}
                              />
                          </div>
                        </div>
                        <div className="col-md-12">
                          <div className="mb-3">
                            <label className="form-label">
                              Student Category
                            </label>
                            <CommonSelect
                                className="select"
                                options={cast}
                                defaultValue={cast[0]}
                              />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="p-3 d-flex align-items-center justify-content-end">
                      <Link to="#" className="btn btn-light me-3">
                        Reset
                      </Link>
                      <Link to="#" className="btn btn-primary" onClick={handleApplyClick}>
                        Apply
                      </Link>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            {/* /Table Filter */}
          </div>
          {/* /Filter Section */}
          <div className="modal-card-table">
            <div className="modal-table-head">
              <h4>List of Fees type</h4>
            </div>
            <div className="table-responsive custom-table no-datatable_length">
              <table className="table datanew">
                <thead className="thead-light">
                  <tr>
                    <th className="no-sort"></th>
                    <th className="no-sort">Fees Type</th>
                    <th className="no-sort">Fees Type</th>
                    <th className="no-sort">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>Admission Fees</td>
                    <td>Admission-Fees</td>
                    <td>5000</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>Apr-Mar</td>
                    <td>Apr-Mar</td>
                    <td>666</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>Bus Fees</td>
                    <td>Bus Fees</td>
                    <td>400</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" defaultChecked />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>1st Installment Fees</td>
                    <td>1st Installment Fees</td>
                    <td>2645</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" defaultChecked />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>2nd Installment Fees</td>
                    <td>2nd Installment Fees</td>
                    <td>7898</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>3rd Installment Fees</td>
                    <td>3rd Installment Fees</td>
                    <td>4785</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div className="modal-card-table">
            <div className="modal-table-head">
              <h4>Student Details</h4>
            </div>
            <div className="table-responsive custom-table no-datatable_length">
              <table className="table datanew">
                <thead className="thead-light">
                  <tr>
                    <th className="no-sort">
                      <label className="checkboxs">
                        <input type="checkbox" id="select-all3" />
                        <span className="checkmarks" />
                      </label>
                    </th>
                    <th className="no-sort">Admission Number</th>
                    <th className="no-sort">Student</th>
                    <th className="no-sort">Class</th>
                    <th className="no-sort">Section</th>
                    <th className="no-sort">Gender</th>
                    <th className="no-sort">Student Category</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892430
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-01.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Janet</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>II</td>
                    <td>B</td>
                    <td>Female</td>
                    <td>MBC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892429
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-06.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Ralph</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>III</td>
                    <td>B</td>
                    <td>Male</td>
                    <td>BC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892428
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-07.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Julie</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>V</td>
                    <td>A</td>
                    <td>Female</td>
                    <td>BC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892427
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-08.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Ryan</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>VI</td>
                    <td>A</td>
                    <td>Male</td>
                    <td>MBC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892426
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-09.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Susan</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>VIII</td>
                    <td>B</td>
                    <td>Female</td>
                    <td>BC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892425
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-12.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Richard</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>VII</td>
                    <td>B</td>
                    <td>Male</td>
                    <td>MBC</td>
                  </tr>
                  <tr>
                    <td>
                      <label className="checkboxs">
                        <input type="checkbox" />
                        <span className="checkmarks" />
                      </label>
                    </td>
                    <td>
                      <Link to="#" className="text-primary">
                        AD9892424
                      </Link>
                    </td>
                    <td>
                      <div className="d-flex align-items-center">
                        <Link
                          to={routes.studentDetail}
                          className="avatar avatar-md"
                        >
                          <ImageWithBasePath
                            src="assets/img/students/student-11.jpg"
                            className="img-fluid rounded-circle"
                            alt="img"
                          />
                        </Link>
                        <div className="ms-2">
                          <p className="text-dark mb-0">
                            <Link to={routes.studentDetail}>Veronica</Link>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>IX</td>
                    <td>A</td>
                    <td>Female</td>
                    <td>BC</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div className="student-pomote-note d-flex mb-0">
            <span className="info-icon">
              <i className="ti ti-info-circle" />
            </span>
            <p> Selected 50 Fees Group, 350 Students</p>
          </div>
        </div>
        <div className="modal-footer">
          <Link to="#" onClick={handleClose} className="btn btn-light me-2" data-bs-dismiss="modal">
            Cancel
          </Link>
          <Link to={routes.feesAssign} onClick={handleClose} className="btn btn-primary">
            Save Changes
          </Link>
        </div>
  </Modal>
  {/* <div className="modal fade" id="edit_fees_assign">
    <div className="modal-dialog modal-dialog-centered  modal-xl">
      <div className="modal-content">
        
      </div>
    </div>
  </div> */}
  {/* Edit Fees Assign */}
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
  )
}

export default AssignModal