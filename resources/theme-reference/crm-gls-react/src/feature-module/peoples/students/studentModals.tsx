
import ImageWithBasePath from '../../../core/common/imageWithBasePath'
import { Link } from 'react-router-dom'
import { all_routes } from '../../router/all_routes'
import { feeGroup, feesTypes, leaveType, paymentType } from '../../../core/common/selectoption/selectoption'
import { DatePicker } from 'antd'
import dayjs from "dayjs";
import CommonSelect from '../../../core/common/commonSelect'

const StudentModals = () => {
   const routes = all_routes
   const today = new Date()
   const year = today.getFullYear()
   const month = String(today.getMonth() + 1).padStart(2, '0') // Month is zero-based, so we add 1
   const day = String(today.getDate()).padStart(2, '0')
   const formattedDate = `${month}-${day}-${year}`
   const defaultValue = dayjs(formattedDate);
   const getModalContainer = () => {
    const modalElement = document.getElementById('modal-datepicker');
    return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
  };
  return (
    <>
  {/* Add Fees Collect */}
  <div className="modal fade" id="add_fees_collect">
    <div className="modal-dialog modal-dialog-centered  modal-lg">
      <div className="modal-content">
        <div className="modal-header">
          <div className="d-flex align-items-center">
            <h4 className="modal-title">Collect Fees</h4>
            <span className="badge badge-sm bg-primary ms-2">AD124556</span>
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
        <form>
          <div id='modal-datepicker' className="modal-body">
            <div className="bg-light-300 p-3 pb-0 rounded mb-4">
              <div className="row align-items-center">
                <div className="col-lg-3 col-md-6">
                  <div className="d-flex align-items-center mb-3">
                    <Link
                      to={routes.studentGrid}
                      className="avatar avatar-md me-2"
                    >
                      <ImageWithBasePath src="assets/img/students/student-01.jpg" alt="img" />
                    </Link>
                    <Link
                      to={routes.studentGrid}
                      className="d-flex flex-column"
                    >
                      <span className="text-dark">Janet</span>III, A
                    </Link>
                  </div>
                </div>
                <div className="col-lg-3 col-md-6">
                  <div className="mb-3">
                    <span className="fs-12 mb-1">Total Outstanding</span>
                    <p className="text-dark">2000</p>
                  </div>
                </div>
                <div className="col-lg-3 col-md-6">
                  <div className="mb-3">
                    <span className="fs-12 mb-1">Last Date</span>
                    <p className="text-dark">25 May 2024</p>
                  </div>
                </div>
                <div className="col-lg-3 col-md-6">
                  <div className="mb-3">
                    <span className="badge badge-soft-danger">
                      <i className="ti ti-circle-filled me-2" />
                      Unpaid
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div className="row">
              <div className="col-lg-6">
                <div className="mb-3">
                  <label className="form-label">Fees Group</label>
                  <CommonSelect
                    className="select"
                    options={feeGroup}
                    defaultValue={feeGroup[0]}
                    />
                </div>
              </div>
              <div className="col-lg-6">
                <div className="mb-3">
                  <label className="form-label">Fees Type</label>
                  <CommonSelect
                    className="select"
                    options={feesTypes}
                    defaultValue={feesTypes[0]}
                    />
                </div>
              </div>
              <div className="col-lg-6">
                <div className="mb-3">
                  <label className="form-label">Amount</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Enter Amout"
                  />
                </div>
              </div>
              <div className="col-lg-6">
                <div className="mb-3">
                  <label className="form-label">Collection Date</label>
                  <div className="date-pic">
                  <DatePicker
                      className="form-control datetimepicker"
                      format={{
                        format: "DD-MM-YYYY",
                        type: "mask",
                      }}
                      getPopupContainer={getModalContainer}
                      defaultValue={defaultValue}
                      placeholder="16 May 2024"
                    />
                    <span className="cal-icon">
                      <i className="ti ti-calendar" />
                    </span>
                  </div>
                </div>
              </div>
              <div className="col-lg-6">
                <div className="mb-3">
                  <label className="form-label">Payment Type</label>
                  <CommonSelect
                    className="select"
                    options={paymentType}
                    defaultValue={paymentType[0]}
                    />
                </div>
              </div>
              <div className="col-lg-6">
                <div className="mb-3">
                  <label className="form-label">Payment Reference No</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Enter Payment Reference No"
                  />
                </div>
              </div>
              <div className="col-lg-12">
                <div className="modal-satus-toggle d-flex align-items-center justify-content-between mb-3">
                  <div className="status-title">
                    <h5>Status</h5>
                    <p>Change the Status by toggle </p>
                  </div>
                  <div className="status-toggle modal-status">
                    <input type="checkbox" id="user1" className="check" />
                    <label htmlFor="user1" className="checktoggle">
                      {" "}
                    </label>
                  </div>
                </div>
              </div>
              <div className="col-lg-12">
                <div className="mb-0">
                  <label className="form-label">Notes</label>
                  <textarea
                    rows={4}
                    className="form-control"
                    placeholder="Add Notes"
                    defaultValue={""}
                  />
                </div>
              </div>
            </div>
          </div>
          <div className="modal-footer">
            <Link to="#" className="btn btn-light me-2" data-bs-dismiss="modal">
              Cancel
            </Link>
            <Link to="#" className="btn btn-primary" data-bs-dismiss="modal">
              Pay Fees
            </Link>
          </div>
        </form>
      </div>
    </div>
  </div>
  {/* Add Fees Collect */}
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
              You want to delete all the marked items, this cant be undone once
              you delete.
            </p>
            <div className="d-flex justify-content-center">
              <Link
                to="#"
                className="btn btn-light me-3"
                data-bs-dismiss="modal"
              >
                Cancel
              </Link>
              <Link to="#"  className="btn btn-danger" data-bs-dismiss="modal">
                Yes, Delete
              </Link>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  {/* /Delete Modal */}

  <>
  {/* Login Details */}
  <div className="modal fade" id="login_detail">
    <div className="modal-dialog modal-dialog-centered  modal-lg">
      <div className="modal-content">
        <div className="modal-header">
          <h4 className="modal-title">Login Details</h4>
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
          <div className="student-detail-info">
            <span className="student-img">
              <ImageWithBasePath src="assets/img/students/student-01.jpg" alt="Img" />
            </span>
            <div className="name-info">
              <h6>
                Janet <span>III, A</span>
              </h6>
            </div>
          </div>
          <div className="table-responsive custom-table no-datatable_length">
            <table className="table datanew">
              <thead className="thead-light">
                <tr>
                  <th>User Type</th>
                  <th>User Name</th>
                  <th>Password </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Parent</td>
                  <td>parent53</td>
                  <td>parent@53</td>
                </tr>
                <tr>
                  <td>Student</td>
                  <td>student20</td>
                  <td>stdt@53</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div className="modal-footer">
          <Link to="#" className="btn btn-light me-2" data-bs-dismiss="modal">
            Cancel
          </Link>
        </div>
      </div>
    </div>
  </div>
  {/* /Login Details */}
</>
<>
  {/* Apply Leave */}
  <div className="modal fade" id="apply_leave">
    <div className="modal-dialog modal-dialog-centered">
      <div className="modal-content">
        <div className="modal-header">
          <h4 className="modal-title">Apply Leave</h4>
          <button
            type="button"
            className="btn-close custom-btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          >
            <i className="ti ti-x" />
          </button>
        </div>
        <form >
          <div className="modal-body">
            <div className="row">
              <div className="col-md-12">
                <div className="mb-3">
                  <label className="form-label">Leave Date</label>
                  <div className="date-pic">
                  <DatePicker
                      className="form-control datetimepicker"
                      format={{
                        format: "DD-MM-YYYY",
                        type: "mask",
                      }}
                      getPopupContainer={getModalContainer}
                      defaultValue={defaultValue}
                      placeholder="16 May 2024"
                    />
                    <span className="cal-icon">
                      <i className="ti ti-calendar" />
                    </span>
                  </div>
                </div>
                <div className="mb-3">
                  <label className="form-label">Leave Type</label>
                  <CommonSelect
                    className="select"
                    options={leaveType}
                    defaultValue={leaveType[0]}
                    />
                </div>
                <div className="mb-3">
                  <label className="form-label">Leave From date</label>
                  <div className="date-pic">
                  <DatePicker
                      className="form-control datetimepicker"
                      format={{
                        format: "DD-MM-YYYY",
                        type: "mask",
                      }}
                      getPopupContainer={getModalContainer}
                      defaultValue={defaultValue}
                      placeholder="16 May 2024"
                    />
                    <span className="cal-icon">
                      <i className="ti ti-calendar" />
                    </span>
                  </div>
                </div>
                <div className="mb-3">
                  <label className="form-label">Leave to Date</label>
                  <div className="date-pic">
                  <DatePicker
                      className="form-control datetimepicker"
                      format={{
                        format: "DD-MM-YYYY",
                        type: "mask",
                      }}
                      getPopupContainer={getModalContainer}
                      defaultValue={defaultValue}
                      placeholder="16 May 2024"
                    />
                    <span className="cal-icon">
                      <i className="ti ti-calendar" />
                    </span>
                  </div>
                </div>
                <div className="mb-3">
                  <label className="form-label">Leave Days</label>
                  <div className="d-flex align-items-center check-radio-group">
                    <label className="custom-radio">
                      <input type="radio" name="radio" defaultChecked />
                      <span className="checkmark" />
                      Full day
                    </label>
                    <label className="custom-radio">
                      <input type="radio" name="radio" />
                      <span className="checkmark" />
                      First Half
                    </label>
                    <label className="custom-radio">
                      <input type="radio" name="radio" />
                      <span className="checkmark" />
                      Second Half
                    </label>
                  </div>
                </div>
                <div className="mb-3">
                  <label className="form-label">No of Days</label>
                  <input type="text" className="form-control" />
                </div>
                <div className="mb-0">
                  <label className="form-label">Reason</label>
                  <input type="text" className="form-control" />
                </div>
              </div>
            </div>
          </div>
          <div className="modal-footer">
            <Link to="#" className="btn btn-light me-2" data-bs-dismiss="modal">
              Cancel
            </Link>
            <Link to="#" data-bs-dismiss="modal" className="btn btn-primary">
              Apply Leave
            </Link>
          </div>
        </form>
      </div>
    </div>
  </div>
  {/* /Apply Leave */}
</>

</>

  )
}

export default StudentModals