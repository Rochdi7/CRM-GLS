import  { useState } from 'react'
import { all_routes } from '../../router/all_routes'
import { Link } from 'react-router-dom'
import Select from "react-select";
import { DatePicker, TimePicker } from 'antd';
const AdminDashboardModal = () => {
    const [activeContent, setActiveContent] = useState('');
    const routes = all_routes
    const handleContentChange = (event:any) => {
        setActiveContent(event.target.value);
      };
    const eventoption = [
        { value: "Select", label: "Select" },
        { value: "Celebration", label: "Celebration" },
        { value: "Training", label: "Training" },
        { value: "Meeting", label: "Meeting" },
        { value: "Holidays", label: "Holidays" },
      ];
    const allTeacher = [
        { value: "All Teacher", label: "All Teacher" },
        { value: "James", label: "James" },
        { value: "Joseph", label: "Joseph" },
        { value: "Mori", label: "Mori" },
        { value: "Erickson", label: "Erickson" },
      ];
    const allSection = [
        { value: "All Sections", label: "All Sections" },
        { value: "A", label: "A" },
        { value: "B", label: "B" },
      ];
    const allClass = [
        { value: "All Classes", label: "All Classes" },
        { value: "I", label: "I" },
        { value: "II", label: "II" },
        { value: "III", label: "III" },
        { value: "IV", label: "IV" },
      ];
    const allClassRoom = [
        { value: "101", label: "101" },
        { value: "102", label: "102" },
        { value: "103", label: "103" },
        { value: "104", label: "104" },
        { value: "105", label: "105" },
      ];
    const allDay = [
        { value: "Monday", label: "Monday" },
        { value: "Tuesday", label: "Tuesday" },
        { value: "Wednesday", label: "Wednesday" },
        { value: "Thursday", label: "Thursday" },
        { value: "Friday", label: "Friday" },
        { value: "Saturday", label: "Saturday" },
        { value: "Sunday", label: "Sunday" },
      ];
      const getModalContainer = () => {
        const modalElement = document.getElementById('modal-datepicker');
        return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
      };
      const getModalContainer2 = () => {
        const modalElement = document.getElementById('modal_datepicker');
        return modalElement ? modalElement : document.body; // Fallback to document.body if modalElement is null
      };
  return (
    <>
    {/* Add Class Routine */}
    <div className="modal fade" id="add_class_routine">
        <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content">
            <div className="modal-wrapper">
            <div className="modal-header">
                <h4 className="modal-title">Add Class Routine</h4>
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
                <div  id='modal_datepicker' className="modal-body">
                <div className="row">
                    <div className="col-md-12">
                    <div className="mb-3">
                        <label className="form-label">Teacher</label>
                    
                        <Select classNamePrefix="react-select"
                        className="select"
                        options={allTeacher}
                        defaultValue={allTeacher[0]}
                        />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Class</label>
                        <Select classNamePrefix="react-select"
                className="select"
                options={allClass}
                defaultValue={allClass[0]}
                />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Section</label>
                        <Select classNamePrefix="react-select"
                className="select"
                options={allSection}
                defaultValue={allSection[0]}
                />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Day</label>
                        <Select classNamePrefix="react-select"
                className="select"
                options={allDay}
                defaultValue={allDay[0]}
                />
                    </div>
                    <div className="row">
                        <div className="col-md-6">
                        <div className="mb-3">
                            <label className="form-label">Start Time</label>
                            <div className="date-pic">
                            <TimePicker getPopupContainer={getModalContainer2}  use12Hours placeholder="Choose" format="h:mm A" className="form-control timepicker" />
                            <span className="cal-icon">
                                <i className="ti ti-clock" />
                            </span>
                            </div>
                        </div>
                        </div>
                        <div className="col-md-6">
                        <div className="mb-3">
                            <label className="form-label">End Time</label>
                            <div className="date-pic">
                            <TimePicker getPopupContainer={getModalContainer2}  use12Hours placeholder="Choose" format="h:mm A" className="form-control timepicker" />
                            <span className="cal-icon">
                                <i className="ti ti-clock" />
                            </span>
                            </div>
                        </div>
                        </div>
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Class Room</label>
                        <Select classNamePrefix="react-select"
                        className="select"
                        options={allClassRoom}
                        defaultValue={allClassRoom[0]}
                        />
                    </div>
                    <div className="modal-satus-toggle d-flex align-items-center justify-content-between">
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
                <Link data-bs-dismiss="modal" to={routes.adminDashboard} className="btn btn-primary">
                    Add Class Routine
                </Link>
                </div>
            </form>
            </div>
        </div>
        </div>
    </div>
    {/* /Add Class Routine */}
    {/* Add Event */}
    <div className="modal fade" id="add_event">
        <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content">
            <div className="modal-header">
            <h4 className="modal-title">New Event</h4>
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
            <div  id='modal-datepicker' className="modal-body">
                <div className="row">
                <div className="col-md-12">
                    <div>
                    <label className="form-label">Event For</label>
                    <div className="d-flex align-items-center flex-wrap">
                        <div className="form-check me-3 mb-3">
                        <input
                            className="form-check-input"
                            type="radio"
                            name="event"
                            id="all"
                            value=""
                            checked={activeContent === ''}
                            onChange={handleContentChange}
                        />
                        <label className="form-check-label" htmlFor="all">
                            All
                        </label>
                        </div>
                        <div className="form-check me-3 mb-3">
                        <input
                            className="form-check-input"
                            type="radio"
                            name="event"
                            id="all-student"
                            value="all-student"
                            onChange={handleContentChange}
                        />
                        <label className="form-check-label" htmlFor="all-student">
                            Students
                        </label>
                        </div>
                        <div className="form-check me-3 mb-3">
                        <input
                            className="form-check-input"
                            type="radio"
                            name="event"
                            id="all-staffs"
                            value="all-staffs" 
                            onChange={handleContentChange}
                        />
                        <label className="form-check-label" htmlFor="all-staffs">
                            Staffs
                        </label>
                        </div>
                    </div>
                    </div>
                    <div className={`all-content ${activeContent === 'all-student' ? 'active' : ''}`} id="all-student">
                    <div className="mb-3">
                        <label className="form-label">Classes</label>
                        <Select classNamePrefix="react-select"
                        className="select"
                        options={allClass}
                        defaultValue={allClass[0]}
                        />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Sections</label>
                        <Select classNamePrefix="react-select"
                        className="select"
                        options={allSection}
                        defaultValue={allSection[0]}
                        />
                    </div>
                    </div>
                    <div className={`all-content ${activeContent === 'all-staffs' ? 'active' : ''}`} id="all-staffs">
                    <div className="mb-3">
                        <div className="bg-light-500 p-3 pb-2 rounded">
                        <label className="form-label">Role</label>
                        <div className="row">
                            <div className="col-md-6">
                            <div className="form-check form-check-sm mb-2">
                                <input
                                className="form-check-input"
                                type="checkbox"
                                />
                                Admin
                            </div>
                            <div className="form-check form-check-sm mb-2">
                                <input
                                className="form-check-input"
                                type="checkbox"
                                defaultChecked
                                />
                                Teacher
                            </div>
                            <div className="form-check form-check-sm mb-2">
                                <input
                                className="form-check-input"
                                type="checkbox"
                                />
                                Driver
                            </div>
                            </div>
                            <div className="col-md-6">
                            <div className="form-check form-check-sm mb-2">
                                <input
                                className="form-check-input"
                                type="checkbox"
                                />
                                Accountant
                            </div>
                            <div className="form-check form-check-sm mb-2">
                                <input
                                className="form-check-input"
                                type="checkbox"
                                />
                                Librarian
                            </div>
                            <div className="form-check form-check-sm mb-2">
                                <input
                                className="form-check-input"
                                type="checkbox"
                                />
                                Receptionist
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>
                    <div className="mb-3">
                        <label className="form-label">All Teachers</label>
                        {/* <select classNamePrefix="react-select" className="select">
                        <option>Select</option>
                        <option>I</option>
                        <option>II</option>
                        <option>III</option>
                        <option>IV</option>
                        </select> */}
                        <Select classNamePrefix="react-select"
                        className="select"
                        options={allTeacher}
                        defaultValue={allTeacher[0]}
                        />
                    </div>
                    </div>
                </div>
                <div className="mb-3">
                    <label className="form-label">Event Title</label>
                    <input
                    type="text"
                    className="form-control"
                    placeholder="Enter Title"
                    />
                </div>
                <div className="mb-3">
                    <label className="form-label">Event Category</label>
                    
                    <Select classNamePrefix="react-select"
                className="select"
                options={eventoption}
                defaultValue={eventoption[0]}
                />
                </div>
                <div className="col-md-6">
                    <div className="mb-3">
                    <label className="form-label">Start Date</label>
                    <div  className="date-pic">
                        <DatePicker getPopupContainer={getModalContainer} className="form-control datetimepicker"  placeholder="15 May 2024" />
                        <span className="cal-icon">
                        <i className="ti ti-calendar" />
                        </span>
                    </div>
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="mb-3">
                    <label className="form-label">End Date</label>
                    <div className="date-pic">
                    <DatePicker getPopupContainer={getModalContainer} className="form-control datetimepicker"  placeholder="15 May 2024" />
                        <span className="cal-icon">
                        <i className="ti ti-calendar" />
                        </span>
                    </div>
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="mb-3">
                    <label className="form-label">Start Time</label>
                    <div className="date-pic">
                        <TimePicker getPopupContainer={getModalContainer}  use12Hours placeholder="09:10 AM" format="h:mm A" className="form-control timepicker" />
                        <span className="cal-icon">
                        <i className="ti ti-clock" />
                        </span>
                    </div>
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="mb-3">
                    <label className="form-label">End Time</label>
                    <div className="date-pic">
                    <TimePicker getPopupContainer={getModalContainer}  use12Hours placeholder="09:10 AM" format="h:mm A" className="form-control timepicker" />
                        <span className="cal-icon">
                        <i className="ti ti-clock" />
                        </span>
                    </div>
                    </div>
                </div>
                <div className="col-md-12">
                    <div className="mb-3">
                    <div className="bg-light p-3 pb-2 rounded">
                        <div className="mb-3">
                        <label className="form-label">Attachment</label>
                        <p>Upload size of 4MB, Accepted Format PDF</p>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                        <div className="btn btn-primary drag-upload-btn mb-2 me-2">
                            <i className="ti ti-file-upload me-1" />
                            Upload
                            <input
                            type="file"
                            className="form-control image_sign"
                            multiple
                            />
                        </div>
                        <p className="mb-2">Fees_Structure.pdf</p>
                        </div>
                    </div>
                    </div>
                    <div className="mb-0">
                    <label className="form-label">Message</label>
                    <textarea
                        className="form-control"
                        rows={4}
                        defaultValue={
                        "Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue"
                        }
                    />
                    </div>
                </div>
                </div>
            </div>
            <div className="modal-footer">
                <Link to="#" className="btn btn-light me-2" data-bs-dismiss="modal">
                Cancel
                </Link>
                <Link to={routes.adminDashboard} data-bs-dismiss="modal" className="btn btn-primary">
                Save Changes
                </Link>
            </div>
            </form>
        </div>
        </div>
    </div>
    {/* /Add Event */}
    </>

  )
}

export default AdminDashboardModal