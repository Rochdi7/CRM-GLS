import  { useRef, useState } from "react";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import { Modal, OverlayTrigger, Tooltip } from "react-bootstrap";
import ImageWithBasePath from "../../core/common/imageWithBasePath";
import { Link } from "react-router-dom";
import CommonSelect from "../../core/common/commonSelect";
import {
  classes,
  eventCategory,
  sections,
} from "../../core/common/selectoption/selectoption";
import { DatePicker } from "antd";
import { all_routes } from "../router/all_routes";
import type { Dayjs } from "dayjs";
import dayjs from "dayjs";
import { TimePicker } from "antd";
interface EventDetails {
  title: string;
}

const Events = () => {
  const routes = all_routes;
  const [showAddEventModal, setShowAddEventModal] = useState(false);
  const [showEventDetailsModal, setShowEventDetailsModal] = useState(false);
  const [eventDetails, setEventDetails] = useState<EventDetails>({
    title: "",
  });
  const calendarRef = useRef(null);

  const handleDateClick = () => {
    setShowAddEventModal(true);
  };

  const handleEventClick = (info: any) => {
    setEventDetails({
      title: info.event.title,
    });
    setShowEventDetailsModal(true);
  };

  const handleAddEventClose = () => setShowAddEventModal(false);
  const handleEventDetailsClose = () => setShowEventDetailsModal(false);

  const onChange = (time: Dayjs, timeString: string | string[]) => {
    console.log(time, timeString);
  };

  const events = [
    {
      title: "Summer Vacation",
      backgroundColor: "#FDE9ED",
      start: new Date(Date.now() - 168000000).toISOString().slice(0, 10),
    },
    {
      title: "Parents, Teacher Meet",
      backgroundColor: "#E7F1FC",
      start: new Date(Date.now() + 338000000).toISOString().slice(0, 10),
    },
    {
      title: "Admission Camp",
      backgroundColor: "#E6F9FF",
      start: new Date(Date.now() - 338000000).toISOString().slice(0, 10),
    },
    {
      title: "Activity - Training",
      backgroundColor: "#E8F9E8",
      start: new Date(Date.now() + 68000000).toISOString().slice(0, 10),
    },
  ];
  return (
    <div>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="mb-1">Events</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Announcement</li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Events
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
              <div className="pe-1 mb-2">
                <OverlayTrigger
                  placement="top"
                  overlay={<Tooltip id="tooltip-top">Print</Tooltip>}
                >
                  <button
                    type="button"
                    className="btn btn-outline-light bg-white btn-icon me-1"
                  >
                    <i className="ti ti-printer" />
                  </button>
                </OverlayTrigger>
              </div>
              <div className="mb-2">
                <Link
                  to="#"
                  className="btn btn-light d-flex align-items-center"
                >
                  <i className="ti ti-calendar-up me-2" />
                  Sync with Google Calendar
                </Link>
              </div>
            </div>
          </div>
          {/* /Page Header */}
          <div className="row">
            {/* Event Calendar */}
            <div className="col-xl-8 col-xxl-9 theiaStickySidebar">
              <div className="stickybar">
                <div className="card">
                  <div className="card-body">
                    <FullCalendar
                      plugins={[
                        dayGridPlugin,
                        timeGridPlugin,
                        interactionPlugin,
                      ]}
                      initialView="dayGridMonth"
                      events={events}
                      headerToolbar={{
                        start: "title",
                        center: "dayGridMonth,dayGridWeek,dayGridDay",
                        end: "custombtn",
                      }}
                      customButtons={{
                        custombtn: {
                          text: "Add New Event",
                          click: handleDateClick,
                        },
                      }}
                      eventClick={handleEventClick}
                      ref={calendarRef}
                    />
                  </div>
                </div>
              </div>
            </div>
            {/* /Event Calendar */}
            {/* Event List */}
            <div className="col-xl-4 col-xxl-3 theiaStickySidebar">
              <div className="stickybar">
                <div className="d-flex align-items-center justify-content-between">
                  <h5 className="mb-3">Events</h5>
                  <div className="dropdown mb-3">
                    <Link
                      to="#"
                      className="btn btn-outline-light dropdown-toggle"
                      data-bs-toggle="dropdown"
                    >
                      All Category
                    </Link>
                    <ul className="dropdown-menu p-3">
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1 d-flex align-items-center"
                        >
                          <i className="ti ti-circle-filled fs-8 text-warning me-2" />
                          Celebration
                        </Link>
                      </li>
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1 d-flex align-items-center"
                        >
                          <i className="ti ti-circle-filled fs-8 text-success me-2" />
                          Training
                        </Link>
                      </li>
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1 d-flex align-items-center"
                        >
                          <i className="ti ti-circle-filled fs-8 text-info me-2" />
                          Meeting
                        </Link>
                      </li>
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1 d-flex align-items-center"
                        >
                          <i className="ti ti-circle-filled fs-8 text-danger me-2" />
                          Holidays
                        </Link>
                      </li>
                      <li>
                        <Link
                          to="#"
                          className="dropdown-item rounded-1 d-flex align-items-center"
                        >
                          <i className="ti ti-circle-filled fs-8 text-pending me-2" />
                          Camp
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
                {/* Event Item */}
                <div className="border-start border-info border-3 shadow-sm p-3 mb-3 bg-white">
                  <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <span className="avatar p-1 me-3 bg-primary-transparent flex-shrink-0">
                      <i className="ti ti-users-group text-info fs-20" />
                    </span>
                    <div className="flex-fill">
                      <h6 className="mb-1">Parents, Teacher Meet</h6>
                      <p className="fs-12">
                        <i className="ti ti-calendar me-1" />
                        15 July 2024
                      </p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between">
                    <p className="mb-0 fs-12">
                      <i className="ti ti-clock me-1" />
                      09:10AM - 10:50PM
                    </p>
                    <div className="avatar-list-stacked avatar-group-sm">
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-01.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-07.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-02.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                    </div>
                  </div>
                </div>
                {/* /Event Item */}
                {/* Event Item */}
                <div className="border-start border-danger border-3 shadow-sm p-3 mb-3 bg-white">
                  <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <span className="avatar p-1 me-2 bg-danger-transparent flex-shrink-0">
                      <i className="ti ti-vacuum-cleaner fs-24" />
                    </span>
                    <div className="flex-fill">
                      <h6 className="mb-1">Summer Vacation</h6>
                      <p className="fs-12">
                        <i className="ti ti-calendar me-1" />
                        07 July 2024 - 08 July 2024
                      </p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between">
                    <p className="fs-12 mb-0">
                      <i className="ti ti-clock me-1" />
                      09:10 AM - 10:50 PM
                    </p>
                    <div className="avatar-list-stacked avatar-group-sm">
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-11.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-13.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                    </div>
                  </div>
                </div>
                {/* /Event Item */}
                {/* Event Item */}
                <div className="border-start border-info border-3 shadow-sm p-3 mb-3 bg-white">
                  <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <span className="avatar p-1 me-2 bg-info-transparent flex-shrink-0">
                      <i className="ti ti-user-edit fs-20" />
                    </span>
                    <div className="flex-fill">
                      <h6 className="mb-1">Staff Meeting</h6>
                      <p className="fs-12">
                        <i className="ti ti-calendar me-1" />
                        10 July 2024
                      </p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between">
                    <p className="fs-12 mb-0">
                      <i className="ti ti-clock me-1" />
                      09:10AM - 10:50PM
                    </p>
                    <div className="avatar-list-stacked avatar-group-sm">
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-05.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-06.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-07.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                    </div>
                  </div>
                </div>
                {/* /Event Item */}
                {/* Event Item */}
                <div className="border-start border-secondary border-3 shadow-sm p-3 mb-3 bg-white">
                  <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <span className="avatar p-1 me-2 bg-secondary-transparent flex-shrink-0">
                      <i className="ti ti-campfire fs-20" />
                    </span>
                    <div className="flex-fill">
                      <h6 className="mb-1">Admission Camp</h6>
                      <p className="fs-12">
                        <i className="ti ti-calendar me-1" />
                        10 July 2024
                      </p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between">
                    <p className="fs-12 mb-0">
                      <i className="ti ti-clock me-1" />
                      09:10AM - 10:50PM
                    </p>
                    <div className="avatar-list-stacked avatar-group-sm">
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-04.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-05.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/parents/parent-10.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                    </div>
                  </div>
                </div>
                {/* /Event Item */}
                {/* Event Item */}
                <div className="border-start border-success border-3 shadow-sm p-3 mb-4 bg-white">
                  <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <span className="avatar p-1 me-2 bg-success-transparent flex-shrink-0">
                      <i className="ti ti-clipboard-heart fs-20" />
                    </span>
                    <div className="flex-fill">
                      <h6 className="mb-1">Activity Training</h6>
                      <p className="fs-12">
                        <i className="ti ti-calendar me-1" />
                        26 July 2024
                      </p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between">
                    <p className="fs-12 mb-0">
                      <i className="ti ti-clock me-1" />
                      09:10AM - 10:50PM
                    </p>
                    <div className="avatar-list-stacked avatar-group-sm">
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/teachers/teacher-02.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/teachers/teacher-05.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                      <span className="avatar border-0">
                        <ImageWithBasePath
                          src="assets/img/teachers/teacher-06.jpg"
                          className="rounded"
                          alt="img"
                        />
                      </span>
                    </div>
                  </div>
                </div>
                {/* /Event Item */}
              </div>
              {/* /Event List */}
            </div>
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
      {/* Add Event */}
      <Modal show={showAddEventModal} onHide={handleAddEventClose}>
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
          <div className="modal-body">
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
                        defaultChecked
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
                        id="students"
                      />
                      <label className="form-check-label" htmlFor="students">
                        Students
                      </label>
                    </div>
                    <div className="form-check me-3 mb-3">
                      <input
                        className="form-check-input"
                        type="radio"
                        name="event"
                        id="staffs"
                      />
                      <label className="form-check-label" htmlFor="staffs">
                        Staffs
                      </label>
                    </div>
                  </div>
                </div>
                <div className="all-content" id="all-student">
                  <div className="mb-3">
                    <label className="form-label">Classes</label>
                    <CommonSelect
                      className="select"
                      options={classes}
                      defaultValue={classes[0]}
                    />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Sections</label>
                    <CommonSelect
                      className="select"
                      options={sections}
                      defaultValue={sections[0]}
                    />
                  </div>
                </div>
                <div className="all-content" id="all-staffs">
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
                    <select className="select">
                      <option>Select</option>
                      <option>I</option>
                      <option>II</option>
                      <option>III</option>
                      <option>IV</option>
                    </select>
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
                <CommonSelect
                  className="select"
                  options={eventCategory}
                  defaultValue={eventCategory[0]}
                />
              </div>
              <div className="col-md-6">
                <div className="mb-3">
                  <label className="form-label">Start Date</label>
                  <div className="date-pic">
                    <DatePicker
                      className="form-control datetimepicker"
                      placeholder="Select Date"
                    />

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
                    <DatePicker
                      className="form-control datetimepicker"
                      placeholder="Select Date"
                    />

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
                    <TimePicker
                      placeholder="11:00 AM"
                      className="form-control timepicker"
                      onChange={onChange}
                      defaultValue={dayjs("00:00:00", "HH:mm:ss")}
                    />
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
                    <TimePicker
                      placeholder="11:00 AM"
                      className="form-control timepicker"
                      onChange={onChange}
                      defaultValue={dayjs("00:00:00", "HH:mm:ss")}
                    />
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
            <button type="submit" className="btn btn-primary">
              Save Changes
            </button>
          </div>
        </form>
      </Modal>
      {/* /Add Event */}
      {/* Event Details */}
      <Modal show={showEventDetailsModal} onHide={handleEventDetailsClose}>
        <div className="modal-header justify-content-between">
          <span className="d-inline-flex align-items-center">
            <i className="ti ti-circle-filled fs-8 me-1 text-info" />
            Meeting
          </span>
          <div className="d-flex align-items-center">
            <Link to="#" className="me-1 fs-18">
              <i className="ti ti-edit-circle" />
            </Link>
            <Link to="#" className="me-1 fs-18">
              <i className="ti ti-trash-x" />
            </Link>
            <Link to="#" className="fs-18" data-bs-dismiss="modal">
              <i className="ti ti-x" />
            </Link>
          </div>
        </div>
        <div className="modal-body pb-0">
          <div className="d-flex align-items-center mb-3">
            <span className="avatar avatar-xl bg-primary-transparent me-3 flex-shrink-0">
              <i className="ti ti-users-group fs-30" />
            </span>
            <div>
              <h3 id="eventTitle" className="mb-1">
                {eventDetails.title}
              </h3>
              <div className="d-flex align-items-center flex-wrap">
                <p className="me-3 mb-0">
                  <i className="ti ti-calendar me-1" />
                  10 July 2024
                </p>
                <p>
                  <i className="ti ti-calendar me-1" />
                  09:10AM - 10:50PM
                </p>
              </div>
            </div>
          </div>
          <div className="bg-light-400 p-3 rounded mb-3">
            <p>
              Meeting with Staffs on the Quality Improvement s and completion of
              syllabus before the August, enhance the students health issue
            </p>
          </div>
          <div className="d-flex align-items-center justify-content-between flex-wrap">
            <div className="avatar-list-stacked avatar-group-sm d-flex mb-3">
              <span className="avatar">
                <ImageWithBasePath
                  src="assets/img/teachers/teacher-01.jpg"
                  alt="img"
                />
              </span>
              <span className="avatar">
                <ImageWithBasePath
                  src="assets/img/teachers/teacher-02.jpg"
                  alt="img"
                />
              </span>
              <span className="avatar">
                <ImageWithBasePath
                  src="assets/img/teachers/teacher-03.jpg"
                  alt="img"
                />
              </span>
              <Link className="avatar bg-white text-default" to="#">
                +67
              </Link>
            </div>
            <div className="mb-3">
              <p className="mb-1">Event For</p>
              <h6>All Classes, All Sections</h6>
            </div>
          </div>
        </div>
      </Modal>
      {/* /Event Details */}
    </div>
  );
};

export default Events;
